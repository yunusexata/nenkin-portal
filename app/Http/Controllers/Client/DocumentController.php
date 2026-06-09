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
        $content = Storage::disk('supabase')->get($document->bucket_path);

        return response($content)
            ->header('Content-Type', Storage::disk('supabase')->mimeType($document->bucket_path))
            ->header(
                'Content-Disposition',
                'attachment; filename="' . basename($document->bucket_path) . '"'
            );
        // return redirect($url);
    }
}
