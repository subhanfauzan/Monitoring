<?php

namespace App\Http\Controllers;

use App\DataTables\NopDataTable;
use App\Models\Nop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NopController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index(NopDataTable $dataTable)
    {

        return $dataTable->render('pages.daftarnop');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_nop' => 'required'
        ]);

        $create = Nop::create([
            'nama_nop' => $request->nama_nop
        ]);

        if($create) {
            return redirect()->route('nop.index');

        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $nop = Nop::findOrFail($id);
        return view('nop_edit', compact('nop'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'nama_nop' => 'required',
        ]);

        $nop = Nop::findOrFail($id);
        $nop->update([
            'nama_nop' => $request->nama_nop
        ]);

        return redirect()->back()->with('success', 'Data updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $nop = Nop::findOrFail($id);
            $nop->delete();

            return response()->json([
                'success' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
