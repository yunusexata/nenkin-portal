<?php

namespace App\Repositories\Client;

use App\Models\Client\Client;
use App\Repositories\MasterDataRepository;

class ClientRepository extends MasterDataRepository
{
    protected static function className(): string
    {
        return Client::class;
    }

    public static function datatable()
    {
        return Client::query();
    }

    public static function getMagicLink($batchName = null)
    {
        return Client::query()
            ->join('client_magic_links', 'client_magic_links.client_id', '=', 'clients.id')
            ->whereNull('client_magic_links.email')
            ->whereNull('client_magic_links.used_at')
            ->when($batchName, function ($q) use ($batchName) {
                $q->where('clients.source_file_name', $batchName);
            })
            ->select('clients.*');
    }

    public static function getClientSpeed($batchName = null)
    {
        return Client::query()
            ->whereIn('claim_type', [
                Client::CLAIM_TYPE_SPEED,
                Client::CLAIM_TYPE_ADJUSTMENT,
            ])
            ->when($batchName, function ($q) use ($batchName) {
                $q->where('clients.source_file_name', $batchName);
            })
            ->select('clients.*')
            ->selectRaw("
            EXISTS (
                SELECT 1
                FROM client_esigns
                WHERE client_esigns.client_id = clients.id
            ) as esign_exists
        ");
    }
}
