<?php

// app/Http/Controllers/Api/WashController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWashRequest;
use App\Models\Wash;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WashController extends Controller
{
    /**
     * Liste des lavages avec filtres
     */
    public function index(Request $request): JsonResponse
    {
        $query = Wash::with(['vehicle', 'reservation']);

        // Filtre par type
        if ($request->has('type') && $request->type !== 'tous') {
            $query->where('type', $request->type);
        }

        // Filtre par date (ex: ce mois-ci)
        if ($request->has('date_from')) {
            $query->where('completed_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('completed_date', '<=', $request->date_to);
        }

        // Recherche par immatriculation ou fournisseur
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('service_provider', 'like', "%{$search}%")
                  ->orWhereHas('vehicle', function ($vq) use ($search) {
                      $vq->where('registration_number', 'like', "%{$search}%")
                         ->orWhere('brand', 'like', "%{$search}%")
                         ->orWhere('model', 'like', "%{$search}%");
                  });
            });
        }

        // Statistiques pour le dashboard
        $stats = [
            'total' => Wash::count(),
            'ce_mois' => Wash::whereMonth('completed_date', now()->month)->count(),
            'cout_total' => Wash::sum('cost'),
            'par_type' => Wash::select('type', DB::raw('count(*) as count'))
                            ->groupBy('type')
                            ->get()
        ];

        return response()->json([
            'data' => $query->latest('completed_date')->paginate(10),
            'stats' => $stats
        ]);
    }

    /**
     * Créer un nouveau lavage
     */
    public function store(StoreWashRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $wash = Wash::create($request->validated());

            // Optionnel : Mettre à jour le champ 'last_wash_date' sur le véhicule
            // si vous ajoutez ce champ à la table vehicles
            // $wash->vehicle->update(['last_wash_date' => $wash->completed_date]);

            return response()->json([
                'message' => 'Lavage enregistré avec succès',
                'data' => $wash->load('vehicle')
            ], 201);
        });
    }

    /**
     * Afficher un lavage spécifique
     */
    public function show(Wash $wash): JsonResponse
    {
        return response()->json($wash->load(['vehicle', 'reservation']));
    }

    /**
     * Mettre à jour un lavage
     */
    public function update(StoreWashRequest $request, Wash $wash): JsonResponse
    {
        $wash->update($request->validated());

        return response()->json([
            'message' => 'Lavage mis à jour',
            'data' => $wash
        ]);
    }

    /**
     * Supprimer un lavage (Soft Delete)
     */
    public function destroy(Wash $wash): JsonResponse
    {
        $wash->delete();
        return response()->json(['message' => 'Lavage supprimé']);
    }
}