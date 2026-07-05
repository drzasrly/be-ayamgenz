<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cabang;

class CabangController extends Controller
{
    /**
     * Display a listing of the branches.
     */
    public function index()
    {
        $cabangs = Cabang::all();

        return response()->json([
            'success' => true,
            'data' => $cabangs
        ]);
    }

    /**
     * Display the specified branch.
     */
    public function show($id)
    {
        $cabang = Cabang::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $cabang
        ]);
    }
}
