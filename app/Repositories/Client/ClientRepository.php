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

    public static function getMagicLink()
    {
        return Client::query()
            ->join('client_magic_links', 'client_magic_links.client_id', '=', 'clients.id')
            ->whereNull('client_magic_links.email')
            ->whereNull('client_magic_links.used_at')
            ->select('clients.*');
    }
}
