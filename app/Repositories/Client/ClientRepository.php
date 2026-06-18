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

    public static function getMagicLink($batchName = null, $claimType = null, $pic = null, $data_type = null)
    {
        // dd([
        //     $batchName,
        //     $claimType,
        //     $pic,
        //     $data_type
        // ]);
        // dd(Client::query()
        //     ->join('client_magic_links', 'client_magic_links.client_id', '=', 'clients.id')
        //     ->whereNull('client_magic_links.email')
        //     ->whereNull('client_magic_links.used_at')
        //     ->whereDoesntHave('esign')->get());
        return Client::query()
            ->join('client_magic_links', 'client_magic_links.client_id', '=', 'clients.id')
            ->whereNull('client_magic_links.email')
            ->whereNull('client_magic_links.used_at')
            ->whereDoesntHave('esign')
            ->when($batchName, function ($q) use ($batchName) {
                $q->where('clients.source_file_name', $batchName);
            })
            ->when($claimType, function ($q) use ($claimType) {
                $q->where('clients.claim_type', $claimType);
            })
            ->when($pic, function ($q) use ($pic) {
                $q->where('clients.pic', 'ILIKE', '%' . $pic . '%');
            })
            ->when($data_type && $data_type === Client::FILTER_DATA_TYPE_CONFIRMATION, function ($q) {
                $q->whereHas('confirmation');
            })
            ->when($data_type && $data_type === Client::FILTER_DATA_TYPE_NOT_CONFIRMATION, function ($q) {
                $q->whereDoesntHave('confirmation');
            })
            ->when($data_type && $data_type === Client::FILTER_DATA_TYPE_REGISTERED, function ($q) {
                $q->whereHas('user');
            })
            ->when($data_type && $data_type === Client::FILTER_DATA_TYPE_NOT_REGISTERED, function ($q) {
                $q->whereDoesntHave('user');
            });
    }

    public static function getClientRegistered($batchName = null, $claimType = null, $pic = null)
    {
        return Client::query()
            ->when($batchName && $batchName != 'all', function ($q) use ($batchName) {
                $q->where('clients.source_file_name', $batchName);
            })
            ->when($claimType, function ($q) use ($claimType) {
                $q->where('clients.claim_type', $claimType);
            })
            ->when($pic, function ($q) use ($pic) {
                $q->where('clients.pic', 'ILIKE', '%' . $pic . '%');
            })
            ->whereHas('user')
            ->select('clients.*')
            ->selectRaw("
            EXISTS (
                SELECT 1
                FROM client_esigns
                WHERE client_esigns.client_id = clients.id
            ) as esign_exists
        ");
    }

    public static function getClientUpodSurvey($batchName = null, $claimType = null, $pic = null)
    {
        return Client::query()
            ->when($batchName && $batchName != 'all', function ($q) use ($batchName) {
                $q->where('clients.source_file_name', $batchName);
            })
            ->when($claimType, function ($q) use ($claimType) {
                $q->where('clients.claim_type', $claimType);
            })
            ->when($pic, function ($q) use ($pic) {
                $q->where('clients.pic', $pic);
            })
            ->whereHas('upodSurveyResponse')
            ->select('clients.*');
    }
}
