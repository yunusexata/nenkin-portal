<?php

namespace App\Http\Controllers\MasterClient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index()
    {
        return view('app.master-client.index');
    }

    public function create()
    {
        return view('app.client.create', ["token" => null]);
    }
}
