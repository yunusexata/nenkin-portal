<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Download document via signed URL with 15 minutes expiration.
     */
    public function download($type)
    {
        // Ensure the logged in user is a client and has an associated client ID
        $clientId = auth()->user()->client_id;
        
        if (!$clientId) {
            abort(403, 'Akses ditolak: User tidak terasosiasi dengan klien manapun.');
        }

        // Find the document of specific type for this client
        $document = Document::where('client_id', $clientId)
            ->where('type', $type)
            ->firstOrFail();

        // Generate signed URL pointing to Supabase Storage with 15 minutes expiration
        $url = Storage::disk('supabase')->temporaryUrl(
            $document->bucket_path,
            now()->addMinutes(15)
        );

        return redirect($url);
    }
}
