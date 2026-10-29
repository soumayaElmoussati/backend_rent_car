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
        $query = Reservation::with(['vehicle', 'client'])->latest('start_date');

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('reference', 'like', "%{$s}%")
                  ->orWhereHas('client', fn($c) => $c->where('nom_complet', 'like', "%{$s}%")->orWhere('nom_entreprise', 'like', "%{$s}%"))
                  ->orWhereHas('vehicle', fn($v) => $v->where('brand', 'like', "%{$s}%")->orWhere('model', 'like', "%{$s}%")->orWhere('registration_number', 'like', "%{$s}%"));
            });
        }

        if ($request->status && $request->status !== 'tous') {
            $query->where('status', $request->status);
        }

        $stats = [
            'total'     => Reservation::count(),
            'confirmed' => Reservation::where('status', 'confirmed')->count(),
            'ongoing'   => Reservation::where('status', 'ongoing')->count(),
            'pending'   => Reservation::where('status', 'pending')->count(),
            'cancelled' => Reservation::where('status', 'cancelled')->count(),
        ];

        return response()->json([
            'data'  => $query->paginate($request->per_page ?? 15),
            'stats' => $stats,
        ]);
    }

    public function calculatePrice(Request $request): JsonResponse
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
        ]);

        $vehicle  = Vehicle::findOrFail($request->vehicle_id);
        $start    = Carbon::parse($request->start_date);
        $end      = Carbon::parse($request->end_date);
        $days     = max(1, $start->diffInDays($end));
        $subtotal = $days * $vehicle->daily_rate;

        return response()->json([
            'days'             => $days,
            'subtotal'         => $subtotal,
            'deposit_amount'   => $vehicle->deposit_amount,
            'calculated_rate'  => $subtotal,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_id'                    => 'required|exists:vehicles,id',
            'client_id'                     => 'required|exists:clients,id',
            'start_date'                    => 'required|date',
            'end_date'                      => 'required|date|after:start_date',
            'pickup_location'               => 'required|string',
            'is_different_return_location'  => 'boolean',
            'return_location'               => 'nullable|string',
            'calculated_rate'               => 'required|numeric',
            'is_negotiated_price'           => 'boolean',
            'negotiated_price'              => 'nullable|numeric',
            'has_commercial_discount'       => 'boolean',
            'discount_amount'               => 'nullable|numeric',
            'subtotal'                      => 'required|numeric',
            'total_amount'                  => 'required|numeric',
            'deposit_amount'                => 'required|numeric',
            'source'                        => 'required|string',
            'internal_notes'                => 'nullable|string',
            'client_notes'                  => 'nullable|string',
        ]);

        $isOverlapping = Reservation::where('vehicle_id', $validated['vehicle_id'])
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($validated) {
                $q->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                  ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                  ->orWhere(function ($q2) use ($validated) {
                      $q2->where('start_date', '<=', $validated['start_date'])
                         ->where('end_date', '>=', $validated['end_date']);
                  });
            })->exists();

        if ($isOverlapping) {
            return response()->json([
                'message' => 'Ce véhicule est déjà réservé pour ces dates.',
                'errors'  => ['vehicle_id' => ['Véhicule indisponible sur cette période.']],
            ], 422);
        }

        return DB::transaction(function () use ($validated) {
            $validated['status'] = 'pending';
            $reservation = Reservation::create($validated);
            Vehicle::find($validated['vehicle_id'])->update(['status' => 'rented']);

            return response()->json([
                'message' => 'Réservation créée avec succès',
                'data'    => $reservation->load(['vehicle', 'client']),
            ], 201);
        });
    }

    public function show(Reservation $reservation): JsonResponse
    {
        return response()->json($reservation->load(['vehicle', 'client']));
    }

    public function update(Request $request, Reservation $reservation): JsonResponse
    {
        $reservation->update($request->all());
        return response()->json(['message' => 'Réservation mise à jour', 'data' => $reservation->load(['vehicle', 'client'])]);
    }

    public function destroy(Reservation $reservation): JsonResponse
    {
        if ($reservation->vehicle && !in_array($reservation->status, ['cancelled', 'completed'])) {
            $reservation->vehicle->update(['status' => 'available']);
        }
        $reservation->delete();
        return response()->json(['message' => 'Réservation supprimée avec succès']);
    }

    public function cancel(Reservation $reservation): JsonResponse
    {
        $reservation->update(['status' => 'cancelled']);
        if ($reservation->vehicle) {
            $reservation->vehicle->update(['status' => 'available']);
        }
        return response()->json(['message' => 'Réservation annulée', 'data' => $reservation]);
    }

    public function confirm(Request $request, Reservation $reservation): JsonResponse
    {
        $validated = $request->validate([
            'deposit_received'          => 'required|numeric|min:0',
            'paid_amount'               => 'required|numeric|min:0',
            'payment_method'            => 'required|string',
            'generate_documents_now'    => 'boolean',
            'driver_birth_date'         => 'nullable|date',
            'driver_nationality'        => 'nullable|string',
            'driver_address'            => 'nullable|string',
            'driver_city'               => 'nullable|string',
            'driver_postal_code'        => 'nullable|string',
            'driver_cin'                => 'nullable|string',
            'driver_cin_expiry'         => 'nullable|date',
            'driver_license_number'     => 'nullable|string',
            'driver_license_issue_date' => 'nullable|date',
            'driver_license_expiry'     => 'nullable|date',
            'driver_passport_number'    => 'nullable|string',
            'driver_passport_expiry'    => 'nullable|date',
        ]);

        $reservation->update(array_merge($validated, ['status' => 'confirmed']));

        return response()->json([
            'message' => 'Réservation confirmée avec succès',
            'data'    => $reservation->load(['vehicle', 'client']),
        ]);
    }

    /**
     * ── DÉMARRER LA LOCATION ──
     * PATCH /api/reservations/{id}/start
     * Statut : confirmed → ongoing
     * Enregistre : kilométrage départ, niveau carburant, notes, dommages départ
     */
    public function start(Request $request, Reservation $reservation): JsonResponse
    {
        if ($reservation->status !== 'confirmed') {
            return response()->json(['message' => 'La réservation doit être confirmée pour démarrer.'], 422);
        }

        $validated = $request->validate([
            'start_mileage'       => 'required|integer|min:0',
            'start_fuel_level'    => 'required|integer|min:0|max:100',
            'start_notes'         => 'nullable|string',
            'start_damages'       => 'nullable|array',     // Tableau des zones endommagées
            'start_damages.*.zone'         => 'nullable|string',
            'start_damages.*.damage_type'  => 'nullable|string',
            'start_damages.*.notes'        => 'nullable|string',
            'actual_start_date'   => 'nullable|date',
        ]);

        return DB::transaction(function () use ($validated, $reservation) {
            $reservation->update([
                'status'             => 'ongoing',
                'start_mileage'      => $validated['start_mileage'],
                'start_fuel_level'   => $validated['start_fuel_level'],
                'start_notes'        => $validated['start_notes'] ?? null,
                'start_damages'      => json_encode($validated['start_damages'] ?? []),
                'actual_start_date'  => $validated['actual_start_date'] ?? now(),
            ]);

            // Véhicule → rented (déjà rented normalement)
            $reservation->vehicle->update(['status' => 'rented']);

            return response()->json([
                'message' => 'Location démarrée avec succès',
                'data'    => $reservation->load(['vehicle', 'client']),
            ]);
        });
    }

    /**
     * ── TERMINER LA LOCATION ──
     * PATCH /api/reservations/{id}/end
     * Statut : ongoing → completed
     * Enregistre : kilométrage retour, carburant, notes retour, dommages retour
     */
    public function end(Request $request, Reservation $reservation): JsonResponse
    {
        if ($reservation->status !== 'ongoing') {
            return response()->json(['message' => 'La location doit être en cours pour être terminée.'], 422);
        }

        $validated = $request->validate([
            'end_mileage'           => 'required|integer|min:0',
            'end_fuel_level'        => 'required|integer|min:0|max:100',
            'end_notes'             => 'nullable|string',
            'end_damages'           => 'nullable|array',
            'end_damages.*.zone'          => 'nullable|string',
            'end_damages.*.damage_type'   => 'nullable|string',
            'end_damages.*.notes'         => 'nullable|string',
            'requires_maintenance'  => 'boolean',
            'damage_description'    => 'nullable|string',
            'damage_penalty'        => 'nullable|numeric|min:0',
            'final_payment'         => 'nullable|numeric|min:0',
            'actual_end_date'       => 'nullable|date',
        ]);

        return DB::transaction(function () use ($validated, $reservation) {
            $reservation->update([
                'status'               => 'completed',
                'end_mileage'          => $validated['end_mileage'],
                'end_fuel_level'       => $validated['end_fuel_level'],
                'end_notes'            => $validated['end_notes'] ?? null,
                'end_damages'          => json_encode($validated['end_damages'] ?? []),
                'requires_maintenance' => $validated['requires_maintenance'] ?? false,
                'damage_description'   => $validated['damage_description'] ?? null,
                'damage_penalty'       => $validated['damage_penalty'] ?? 0,
                'actual_end_date'      => $validated['actual_end_date'] ?? now(),
            ]);

            // Si dommages → véhicule en maintenance, sinon disponible
            $newStatus = ($validated['requires_maintenance'] ?? false) ? 'maintenance' : 'available';
            $reservation->vehicle->update(['status' => $newStatus]);

            return response()->json([
                'message' => 'Location terminée avec succès',
                'data'    => $reservation->load(['vehicle', 'client']),
            ]);
        });
    }
}