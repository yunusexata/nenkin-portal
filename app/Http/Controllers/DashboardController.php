<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user && $user->hasRole(\App\Models\User::ROLE_CLIENT)) {
            $clientId = $user->client_id;
            
            // Retrieve documents for this client keyed by their type
            $documents = \App\Models\Document::where('client_id', $clientId)
                ->get()
                ->keyBy('type');

            return view('app.dashboard.index', compact('documents'));
        }

        $stats = [
            'total_clients'    => \App\Models\Client\Client::count(),
            'speed_clients'    => \App\Models\Client\Client::whereIn('claim_type', ['speed', 'adjusment'])->count(),
            'total_documents'  => \App\Models\Document::count(),
            'pending_reviews'  => \App\Models\Review::where('status', 'intercepted')->count(),
        ];

        return view('app.dashboard.index', compact('stats'));
    }
}
