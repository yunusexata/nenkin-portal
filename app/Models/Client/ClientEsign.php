<?php

namespace App\Models\Client;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Muhammadyunus1072\TrackHistory\HasTrackHistory;

class ClientEsign extends Model
{
    use HasFactory, SoftDeletes, HasTrackHistory;

    protected $table = 'client_esigns';

    protected $fillable = [
        'client_id',
        'nik',
        'address',
        'agreement_version',
        'ip_address',
        'user_agent',
        'document_hash',
        'signature_data',
        'signed_at',
        'stored_name',
        'disk',
        'path',
        'note',
        'extension',
        'mime_type',
        'file_size',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
