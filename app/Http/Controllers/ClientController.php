<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ClientController extends Controller
{
    /**
     * Liste des clients avec filtres et recherche (Dashboard)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Client::query();

        // Filtre par type (Tous, Particuliers, Entreprises)
        if ($request->has('type') && $request->type !== 'tous') {
            $query->where('type', $request->type);
        }

        // Filtre Blacklistés
        if ($request->has('blacklistes') && $request->blacklistes == true) {
            $query->where('est_blackliste', true);
        }

        // Recherche (Nom, Email, Tel, CIN)
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom_complet', 'like', "%{$search}%")
                  ->orWhere('nom_entreprise', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('telephone', 'like', "%{$search}%")
                  ->orWhere('cin', 'like', "%{$search}%");
            });
        }

        // Statistiques pour les cartes du dashboard (Optionnel, peut être fait dans un autre endpoint)
        $stats = [
            'total' => Client::count(),
            'particuliers' => Client::where('type', 'particulier')->count(),
            'entreprises' => Client::where('type', 'entreprise')->count(),
            'blacklistes' => Client::where('est_blackliste', true)->count(),
        ];

        return response()->json([
            'data' => $query->latest()->paginate(10),
            'stats' => $stats
        ]);
    }

    /**
     * Création d'un nouveau client
     */
    public function store(StoreClientRequest $request): JsonResponse
    {
        // Les données sont déjà validées par StoreClientRequest
        $client = Client::create($request->validated());

        return response()->json([
            'message' => 'Client créé avec succès',
            'data' => $client
        ], 201);
    }

    /**
     * Afficher un client spécifique
     */
    public function show(Client $client): JsonResponse
    {
        return response()->json($client);
    }

    /**
     * Mise à jour d'un client
     */
    public function update(StoreClientRequest $request, Client $client): JsonResponse
    {
        $client->update($request->validated());

        return response()->json([
            'message' => 'Client mis à jour avec succès',
            'data' => $client
        ]);
    }

    /**
     * Suppression (Soft Delete)
     */
    public function destroy(Client $client): JsonResponse
    {
        $client->delete();

        return response()->json([
            'message' => 'Client supprimé avec succès'
        ]);
    }

    /**
     * Basculer le statut Blacklisté
     */
    public function toggleBlacklist(Client $client): JsonResponse
    {
        $client->est_blackliste = !$client->est_blackliste;
        $client->save();

        return response()->json([
            'message' => $client->est_blackliste ? 'Client blacklisté' : 'Client retiré de la blacklist',
            'data' => $client
        ]);
    }
}