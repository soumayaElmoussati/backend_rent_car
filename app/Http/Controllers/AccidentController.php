<?php



namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccidentRequest;
use App\Models\Accident;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AccidentController extends Controller
{
     

public function index(Request $request): JsonResponse
    {
        // On récupère tous les accidents avec leur véhicule associé, triés par date
        $accidents = Accident::with('vehicle')
            ->latest('incident_date')
            ->paginate(10);

        // Statistiques globales pour le dashboard
        $stats = [
            'total' => Accident::count(),
            'signales' => Accident::where('status', 'reported')->count(),
            'en_revision' => Accident::where('status', 'under_review')->count(),
            'resolus' => Accident::where('status', 'resolved')->count(),
            'cout_total_estime' => Accident::sum('estimated_repair_cost'),
        ];

        return response()->json([
            'data' => $accidents,
            'stats' => $stats
        ]);
    }
    
    /**
     * Créer un nouvel accident
     */
    public function store(StoreAccidentRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $accident = Accident::create($request->validated());

            // Optionnel : Mettre le véhicule en maintenance si l'accident est sévère
            if (in_array($request->severity, ['severe', 'total_loss'])) {
                $vehicle = Vehicle::find($request->vehicle_id);
                $vehicle->update(['status' => 'maintenance']);
            }

            return response()->json([
                'message' => 'Accident déclaré avec succès',
                'data' => $accident->load('vehicle')
            ], 201);
        });
    }

    /**
     * Afficher un accident spécifique
     */
    public function show(Accident $accident): JsonResponse
    {
        return response()->json($accident->load('vehicle'));
    }

    /**
     * Mettre à jour un accident
     */
    public function update(StoreAccidentRequest $request, Accident $accident): JsonResponse
    {
        $accident->update($request->validated());

        return response()->json([
            'message' => 'Accident mis à jour',
            'data' => $accident
        ]);
    }

    /**
     * Mettre à jour le statut d'un accident (Workflow)
     */
    public function updateStatus(Request $request, Accident $accident): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:reported,under_review,resolved'],
            'actual_repair_cost' => ['nullable', 'numeric', 'min:0'],
            'insurance_payout' => ['nullable', 'numeric', 'min:0'],
            'internal_notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($request, $accident) {
            $accident->update([
                'status' => $request->status,
                'actual_repair_cost' => $request->actual_repair_cost ?? $accident->actual_repair_cost,
                'insurance_payout' => $request->insurance_payout ?? $accident->insurance_payout,
                'internal_notes' => $request->internal_notes ?? $accident->internal_notes,
            ]);

            // Si l'accident est résolu, remettre le véhicule en disponible
            if ($request->status === 'resolved') {
                $accident->vehicle->update(['status' => 'available']);
            }

            return response()->json([
                'message' => 'Statut de l\'accident mis à jour',
                'data' => $accident
            ]);
        });
    }

    /**
     * Supprimer un accident (Soft Delete)
     */
    public function destroy(Accident $accident): JsonResponse
    {
        $accident->delete();
        return response()->json(['message' => 'Accident supprimé']);
    }
}