<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use Illuminate\Http\Request;

class CabangController extends Controller
{

    public function index()
    {
        $cabang = Cabang::all();
        return view('cabang.index', compact('cabang'));
    }

    public function create()
    {
        return view('cabang.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_cabang' => 'required',
            'alamat' => 'required',
            'telepon' => 'required'
        ]);

        Cabang::create($request->only([
            'nama_cabang',
            'alamat',
            'telepon'
        ]));

        return redirect()->route('cabang.index');
    }

    public function edit($id)
    {
        $cabang = Cabang::findOrFail($id);
        return view('cabang.edit', compact('cabang'));
    }

    public function update(Request $request, $id)
    {
        $cabang = Cabang::findOrFail($id);
        $validated = $request->validate([
            'nama_cabang' => 'required|string|max:255',
            'alamat' => 'required|string',
            'telepon' => 'required|string|max:50',
        ]);

        $cabang->update($validated);

        return redirect('/cabang');
    }

    public function destroy($id)
    {
        $cabang = Cabang::findOrFail($id);
        $cabang->delete();
        return redirect('/cabang');
    }

}