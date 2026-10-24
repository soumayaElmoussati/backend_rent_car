<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReservationController extends Controller
{
   public function index(Request $request): JsonResponse
    {
        // On récupère TOUTES les réservations avec leurs relations
        // On trie par date de début la plus récente
        $reservations = Reservation::with(['vehicle', 'client'])
            ->latest('start_date')
            ->paginate($request->per_page ?? 15);

        // Statistiques pour les cartes du haut
        $stats = [
            'total'     => Reservation::count(),
            'confirmed' => Reservation::where('status', 'confirmed')->count(),
            'pending'   => Reservation::where('status', 'pending')->count(),
            'cancelled' => Reservation::where('status', 'cancelled')->count(),
        ];

        return response()->json([
            'data' => $reservations,
            'stats' => $stats
        ]);
    }

    /**
     * Calcule le prix (Appelé à l'Étape 3 du formulaire)
     */
    public function calculatePrice(Request $request): JsonResponse
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $vehicle = Vehicle::findOrFail($request->vehicle_id);
        
        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        
        // Calcul du nombre de jours (minimum 1 jour)
        $days = $start->diffInDays($end);
        if ($days == 0) $days = 1;

        // Calcul du sous-total (Logique simple : prix journalier * jours)
        $subtotal = $days * $vehicle->daily_rate;

        return response()->json([
            'days' => $days,
            'subtotal' => $subtotal,
            'deposit_amount' => $vehicle->deposit_amount,
            'calculated_rate' => $subtotal,
        ]);
    }

    /**
     * Enregistre la réservation finale (Appelé à la fin de l'Étape 4)
     */
    public function store(Request $request): JsonResponse
    {
        // 1. Validation de toutes les données du formulaire
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'client_id' => 'required|exists:clients,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'pickup_location' => 'required|string',
            'is_different_return_location' => 'boolean',
            'return_location' => 'nullable|string',
            
            'calculated_rate' => 'required|numeric',
            'is_negotiated_price' => 'boolean',
            'negotiated_price' => 'nullable|numeric',
            'has_commercial_discount' => 'boolean',
            'discount_amount' => 'nullable|numeric',
            
            'subtotal' => 'required|numeric',
            'total_amount' => 'required|numeric',
            'deposit_amount' => 'required|numeric',
            
            'source' => 'required|string',
            'internal_notes' => 'nullable|string',
            'client_notes' => 'nullable|string',
        ]);

        // 2. Vérification de la disponibilité du véhicule (Anti-chevauchement)
        $isOverlapping = Reservation::where('vehicle_id', $validated['vehicle_id'])
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($validated) {
                $query->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                      ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                      ->orWhere(function ($q) use ($validated) {
                          $q->where('start_date', '<=', $validated['start_date'])
                            ->where('end_date', '>=', $validated['end_date']);
                      });
            })->exists();

        if ($isOverlapping) {
            return response()->json([
                'message' => 'Ce véhicule est déjà réservé pour ces dates.',
                'errors' => ['vehicle_id' => ['Véhicule indisponible sur cette période.']]
            ], 422);
        }

        // 3. Création de la réservation
        return DB::transaction(function () use ($validated) {
            // Ajout du statut par défaut
            $validated['status'] = 'pending'; 

            $reservation = Reservation::create($validated);

            // Mettre le véhicule en statut "réservé" ou "loué" (optionnel)
            $vehicle = Vehicle::find($validated['vehicle_id']);
            $vehicle->update(['status' => 'rented']);

            return response()->json([
                'message' => 'Réservation créée avec succès',
                'data' => $reservation->load(['vehicle', 'client'])
            ], 201);
        });
    }

     /**
     * Supprimer une réservation (Appelé par le bouton Poubelle)
     */
    public function destroy(Reservation $reservation): JsonResponse
    {
        // Optionnel : Remettre le véhicule en disponible si la réservation est supprimée
        if ($reservation->vehicle && $reservation->status !== 'cancelled' && $reservation->status !== 'completed') {
            $reservation->vehicle->update(['status' => 'available']);
        }

        $reservation->delete();

        return response()->json([
            'message' => 'Réservation supprimée avec succès'
        ]);
    }

    /**
     * Annuler une réservation
     */
    public function cancel(Reservation $reservation): JsonResponse
    {
        $reservation->update(['status' => 'cancelled']);
        
        if ($reservation->vehicle) {
            $reservation->vehicle->update(['status' => 'available']);
        }

        return response()->json([
            'message' => 'Réservation annulée',
            'data' => $reservation
        ]);
    }

        /**
     * Afficher une réservation spécifique (Détails)
     */
    public function show(Reservation $reservation): JsonResponse
    {
        return response()->json($reservation->load(['vehicle', 'client']));
    }

      public function confirm(Request $request, Reservation $reservation): JsonResponse
    {
        $validated = $request->validate([
            // Paiement
            'deposit_received' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'generate_documents_now' => 'boolean',

            // Contrat (optionnels mais recommandés)
            'driver_birth_date' => 'nullable|date',
            'driver_nationality' => 'nullable|string',
            'driver_address' => 'nullable|string',
            'driver_city' => 'nullable|string',
            'driver_postal_code' => 'nullable|string',
            'driver_cin' => 'nullable|string',
            'driver_cin_expiry' => 'nullable|date',
            'driver_license_number' => 'nullable|string',
            'driver_license_issue_date' => 'nullable|date',
            'driver_license_expiry' => 'nullable|date',
            'driver_passport_number' => 'nullable|string',
            'driver_passport_expiry' => 'nullable|date',
        ]);

        $reservation->update(array_merge($validated, [
            'status' => 'confirmed',
        ]));

        return response()->json([
            'message' => 'Réservation confirmée avec succès',
            'data' => $reservation->load(['vehicle', 'client'])
        ]);
    }

}