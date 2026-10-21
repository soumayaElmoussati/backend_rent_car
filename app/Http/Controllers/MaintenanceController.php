<?php


namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaintenanceRequest;
use App\Models\Maintenance;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MaintenanceController extends Controller
{
    /**
     * Liste des maintenances avec filtres
     */
    public function index(Request $request): JsonResponse
    {
        $query = Maintenance::with('vehicle');

        // Filtre par statut (pending, in_progress, completed)
        if ($request->has('status') && $request->status !== 'tous') {
            $query->where('status', $request->status);
        }

        // Filtre par priorité
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Recherche par immatriculation ou type
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('type', 'like', "%{$search}%")
                  ->orWhereHas('vehicle', function ($vq) use ($search) {
                      $vq->where('registration_number', 'like', "%{$search}%")
                         ->orWhere('brand', 'like', "%{$search}%")
                         ->orWhere('model', 'like', "%{$search}%");
                  });
            });
        }

        // Statistiques pour le dashboard
        $stats = [
            'total' => Maintenance::count(),
            'en_attente' => Maintenance::where('status', 'pending')->count(),
            'en_cours' => Maintenance::where('status', 'in_progress')->count(),
            'terminees' => Maintenance::where('status', 'completed')->count(),
        ];

        return response()->json([
            'data' => $query->latest('scheduled_date')->paginate(10),
            'stats' => $stats
        ]);
    }

    /**
     * Créer une nouvelle maintenance
     */
    public function store(StoreMaintenanceRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $maintenance = Maintenance::create($request->validated());

            // Optionnel : Mettre le véhicule en maintenance si la date est aujourd'hui
            // ou si c'est une priorité critique
            if ($request->priority === 'critical' || $request->scheduled_date <= now()->toDateString()) {
                $vehicle = Vehicle::find($request->vehicle_id);
                $vehicle->update(['status' => 'maintenance']);
            }

            return response()->json([
                'message' => 'Maintenance planifiée avec succès',
                'data' => $maintenance->load('vehicle')
            ], 201);
        });
    }

    /**
     * Afficher une maintenance
     */
    public function show(Maintenance $maintenance): JsonResponse
    {
        return response()->json($maintenance->load('vehicle'));
    }

    /**
     * Mettre à jour une maintenance (modification des détails)
     */
    public function update(StoreMaintenanceRequest $request, Maintenance $maintenance): JsonResponse
    {
        $maintenance->update($request->validated());

        return response()->json([
            'message' => 'Maintenance mise à jour',
            'data' => $maintenance
        ]);
    }

    /**
     * Marquer une maintenance comme terminée
     */
    public function complete(Request $request, Maintenance $maintenance): JsonResponse
    {
        $request->validate([
            'actual_cost' => ['nullable', 'numeric', 'min:0'],
            'completed_date' => ['nullable', 'date'],
        ]);

        return DB::transaction(function () use ($request, $maintenance) {
            $maintenance->update([
                'status' => 'completed',
                'actual_cost' => $request->actual_cost ?? $maintenance->estimated_cost,
                'completed_date' => $request->completed_date ?? now(),
            ]);

            // Remettre le véhicule en disponible
            $maintenance->vehicle->update(['status' => 'available']);

            return response()->json([
                'message' => 'Maintenance terminée, véhicule remis en service',
                'data' => $maintenance
            ]);
        });
    }

    /**
     * Annuler une maintenance
     */
    public function cancel(Maintenance $maintenance): JsonResponse
    {
        return DB::transaction(function () use ($maintenance) {
            $maintenance->update(['status' => 'cancelled']);
            
            // Si le véhicule était en maintenance, le remettre en disponible
            if ($maintenance->vehicle->status === 'maintenance') {
                $maintenance->vehicle->update(['status' => 'available']);
            }

            return response()->json([
                'message' => 'Maintenance annulée',
                'data' => $maintenance
            ]);
        });
    }

    /**
     * Supprimer une maintenance (Soft Delete)
     */
    public function destroy(Maintenance $maintenance): JsonResponse
    {
        $maintenance->delete();
        return response()->json(['message' => 'Maintenance supprimée']);
    }
}