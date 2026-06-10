<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Document;
use App\Models\Review;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user && $user->hasRole(\App\Models\User::ROLE_CLIENT)) {
            $client = $user->client;

            // Eager-load all relations in one go
            $client->loadMissing(['confirmation', 'esign', 'review', 'documents']);

            $documents = $client->documents->keyBy('type');

            // Build progress steps for the tracker
            $steps = [
                [
                    'key'    => 'confirmation',
                    'label'  => 'Konfirmasi Data',
                    'done'   => (bool) $client->confirmation,
                    'icon'   => 'check_circle',
                ],
                [
                    'key'    => 'esign',
                    'label'  => 'Tanda Tangan',
                    'done'   => (bool) $client->esign,
                    'icon'   => 'edit',
                ],
                [
                    'key'    => 'documents',
                    'label'  => 'Dokumen Diterima',
                    'done'   => $documents->isNotEmpty(),
                    'icon'   => 'folder',
                ],
                [
                    'key'    => 'review',
                    'label'  => 'Proses Selesai',
                    'done'   => (bool) $client->review,
                    'icon'   => 'star',
                ],
            ];

            $completedSteps = collect($steps)->where('done', true)->count();
            $totalSteps     = count($steps);

            return view('app.dashboard.client-home', compact(
                'client',
                'documents',
                'steps',
                'completedSteps',
                'totalSteps'
            ));
        }

        // Admin / Internal dashboard
        $stats = [
            'total_clients'   => Client::count(),
            'speed_clients'   => Client::whereIn('claim_type', ['speed', 'adjusment'])->count(),
            'total_documents' => Document::count(),
            'pending_reviews' => Review::where('status', 'intercepted')->count(),
        ];

        return view('app.dashboard.index', compact('stats'));
    }
}
