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

    public function create(Request $request)
    {
        return view('app.client.create', ["token" => $request->token]);
    }
}
