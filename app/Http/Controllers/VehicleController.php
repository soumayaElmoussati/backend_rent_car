<?php


namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVehicleRequest;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class VehicleController extends Controller
{
    /**
     * Liste des véhicules avec filtres et recherche (Dashboard)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Vehicle::query();

        // Filtre par statut (Disponible, Loué, Maintenance, Hors service)
        if ($request->has('status') && $request->status !== 'tous') {
            $query->where('status', $request->status);
        }

        // Recherche (Marque, Modèle, Immatriculation)
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('brand', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('registration_number', 'like', "%{$search}%");
            });
        }

        // Statistiques pour les cartes du dashboard
        $stats = [
            'total' => Vehicle::count(),
            'disponibles' => Vehicle::where('status', 'available')->count(),
            'louees' => Vehicle::where('status', 'rented')->count(),
            'maintenance' => Vehicle::where('status', 'maintenance')->count(),
        ];

        return response()->json([
            'data' => $query->latest()->paginate(10),
            'stats' => $stats
        ]);
    }

    /**
     * Création d'un nouveau véhicule
     */
    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        // Gestion de l'upload des photos
        if ($request->hasFile('photos')) {
            $photoPaths = [];
            foreach ($request->file('photos') as $photo) {
                // Stocke l'image dans le dossier 'vehicles' du disque public
                $path = $photo->store('vehicles', 'public');
                $photoPaths[] = Storage::url($path); // Génère l'URL publique
            }
            $validatedData['photos'] = $photoPaths;
        }

        $vehicle = Vehicle::create($validatedData);

        return response()->json([
            'message' => 'Véhicule créé avec succès',
            'data' => $vehicle
        ], 201);
    }

    /**
     * Afficher un véhicule spécifique
     */
    public function show(Vehicle $vehicle): JsonResponse
    {
        return response()->json($vehicle);
    }

    /**
     * Mise à jour d'un véhicule
     */
    public function update(StoreVehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        $validatedData = $request->validated();

        // Gestion de l'upload des nouvelles photos
        if ($request->hasFile('photos')) {
            // Optionnel : Supprimer les anciennes photos du stockage ici
            // foreach ($vehicle->photos as $oldPhoto) { Storage::delete(...); }

            $photoPaths = [];
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('vehicles', 'public');
                $photoPaths[] = Storage::url($path);
            }
            $validatedData['photos'] = $photoPaths;
        }

        $vehicle->update($validatedData);

        return response()->json([
            'message' => 'Véhicule mis à jour avec succès',
            'data' => $vehicle
        ]);
    }

    /**
     * Suppression (Soft Delete)
     */
    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $vehicle->delete();

        return response()->json([
            'message' => 'Véhicule supprimé avec succès'
        ]);
    }

    /**
     * Changer le statut d'un véhicule (ex: Disponible -> Loué)
     */
    public function updateStatus(Request $request, Vehicle $vehicle): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:available,rented,maintenance,archived']
        ]);

        $vehicle->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Statut mis à jour',
            'data' => $vehicle
        ]);
    }
}