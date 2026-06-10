<?php

namespace App\Http\Controllers\MasterClient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MasterClientSpeedController extends Controller
{
    public function index()
    {
        return view('app.master-client-speed.index');
    }

    public function create()
    {
        return view('app.master-client-speed.import', ["objId" => null]);
    }

    public function edit(Request $request)
    {
        return view('app.master-client-speed.detail', ["objId" => $request->id]);
    }
}
