<?php

namespace App\Http\Controllers\MasterClient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function form_confirmation()
    {

        return view('app.client.form-confirmation');
    }
    public function document()
    {
        $user = auth()->user();

        if ($user && $user->hasRole(\App\Models\User::ROLE_CLIENT)) {
            $clientId = $user->client_id;

            // Retrieve documents for this client keyed by their type
            $documents = \App\Models\Document::where('client_id', $clientId)
                ->get()
                ->keyBy('type');

            return view('app.client.document', compact('documents'));
        }
        return view('app.client.document');
    }
    public function agreement()
    {
        $user = auth()->user();

        if ($user && $user->hasRole(\App\Models\User::ROLE_CLIENT)) {
            $clientId = $user->client_id;

            // Retrieve documents for this client keyed by their type
            $documents = \App\Models\Document::where('client_id', $clientId)
                ->get()
                ->keyBy('type');

            return view('app.client.agreement', compact('documents'));
        }
        return view('app.client.agreement');
    }

    public function create(Request $request)
    {
        return view('app.client.create', ["token" => $request->token]);
    }
}
