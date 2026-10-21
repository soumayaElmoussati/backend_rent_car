<?php


namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Models\Reservation;
use App\Models\Vehicle;
use App\Services\ReservationPricingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    protected $pricingService;

    public function __construct(ReservationPricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    /**
     * Liste des réservations (Dashboard)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Reservation::with(['vehicle', 'client']);

        // Filtres
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('reference', 'like', "%{$search}%")
                  ->orWhereHas('client', function($q) use ($search) {
                      $q->where('nom_complet', 'like', "%{$search}%")
                        ->orWhere('nom_entreprise', 'like', "%{$search}%");
                  });
        }

        return response()->json($query->latest()->paginate(10));
    }

    /**
     * Calculer le tarif (Étape 3 du formulaire - Appel AJAX)
     */
    public function calculatePrice(Request $request): JsonResponse
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $vehicle = Vehicle::findOrFail($request->vehicle_id);
        
        $pricing = $this->pricingService->calculateRate(
            $vehicle, 
            $request->start_date, 
            $request->end_date
        );

        return response()->json($pricing);
    }

    /**
     * Créer la réservation finale (Validation du Stepper)
     */
    public function store(StoreReservationRequest $request): JsonResponse
    {
        // Utilisation d'une transaction pour garantir l'intégrité des données
        return DB::transaction(function () use ($request) {
            
            $validatedData = $request->validated();
            
            // 1. Création de la réservation
            $reservation = Reservation::create($validatedData);

            // 2. Mise à jour du statut du véhicule si la location est immédiate
            // (Logique optionnelle : on peut passer le véhicule en 'rented' dès la création)
            $vehicle = Vehicle::find($validatedData['vehicle_id']);
            if ($vehicle) {
                $vehicle->update(['status' => 'rented']);
            }

            return response()->json([
                'message' => 'Réservation créée avec succès',
                'data' => $reservation->load(['vehicle', 'client'])
            ], 201);
        });
    }

    /**
     * Afficher une réservation
     */
    public function show(Reservation $reservation): JsonResponse
    {
        return response()->json($reservation->load(['vehicle', 'client']));
    }

    /**
     * Mettre à jour une réservation (ex: modification des dates)
     */
    public function update(StoreReservationRequest $request, Reservation $reservation): JsonResponse
    {
        // Note: La validation de disponibilité (withValidator) doit ignorer la réservation actuelle
        // Pour simplifier, on suppose ici que les dates ne changent pas ou on refait la validation.
        
        $reservation->update($request->validated());

        return response()->json([
            'message' => 'Réservation mise à jour',
            'data' => $reservation
        ]);
    }

    /**
     * Annuler une réservation
     */
    public function cancel(Reservation $reservation): JsonResponse
    {
        return DB::transaction(function () use ($reservation) {
            $reservation->update(['status' => 'cancelled']);
            
            // Remettre le véhicule en disponible
            $reservation->vehicle->update(['status' => 'available']);

            return response()->json([
                'message' => 'Réservation annulée',
                'data' => $reservation
            ]);
        });
    }

    /**
     * Supprimer une réservation (Soft Delete)
     */
    public function destroy(Reservation $reservation): JsonResponse
    {
        $reservation->delete();
        return response()->json(['message' => 'Réservation supprimée']);
    }
}