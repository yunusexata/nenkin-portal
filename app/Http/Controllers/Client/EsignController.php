<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client\ClientEsign;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EsignController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        $client = $user->client;

        if (!$client) {
            return redirect()->route('dashboard.index')->with('error', 'Data klien tidak ditemukan.');
        }

        // Check if already signed
        if ($client->esign()->exists()) {
            return redirect()->route('dashboard.index')->with('info', 'Anda sudah menandatangani perjanjian.');
        }

        $now = Carbon::now()->locale('id');
        $dateFormatted = $now->translatedFormat('l, d F Y');
        $letterNumber = str_pad($client->id, 4, '0', STR_PAD_LEFT) . '/LSN/SREI/2026';

        return view('client.esign', compact('client', 'dateFormatted', 'letterNumber'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $client = $user->client;

        if (!$client) {
            return redirect()->route('dashboard.index')->with('error', 'Data klien tidak ditemukan.');
        }

        if ($client->esign()->exists()) {
            return redirect()->route('dashboard.index')->with('info', 'Anda sudah menandatangani perjanjian.');
        }

        $request->validate([
            'nik' => 'required|string|max:20',
            'address' => 'required|string',
            'agreement_accepted' => 'required|accepted',
            'signature_data' => 'required|string', // base64 string
        ]);

        // Generate Document Hash
        $now = Carbon::now()->locale('id');
        $dateFormatted = $now->translatedFormat('l, d F Y');
        $letterNumber = str_pad($client->id, 4, '0', STR_PAD_LEFT) . '/LSN/SREI/2026';

        // Render the agreement content to string for hashing
        $agreementHtml = view('client.partials.agreement-content', [
            'client' => $client,
            'nik' => $request->nik,
            'address' => $request->address,
            'dateFormatted' => $dateFormatted,
            'letterNumber' => $letterNumber,
        ])->render();

        $documentHash = hash('sha256', $agreementHtml);

        // Save to Database
        ClientEsign::create([
            'client_id' => $client->id,
            'nik' => $request->nik,
            'address' => $request->address,
            'agreement_version' => 'v1.0',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'document_hash' => $documentHash,
            'signature_data' => $request->signature_data,
            'signed_at' => Carbon::now('Asia/Jakarta'),
        ]);

        return redirect()->route('dashboard.index')->with('success', 'Perjanjian berhasil ditandatangani.');
    }
}
