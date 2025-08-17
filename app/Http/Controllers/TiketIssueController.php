<?php

namespace App\Http\Controllers;

use App\DataTables\TiketDataTable;
use App\Models\Dapot;
use App\Models\Nop;
use App\Models\Tiket;
use Illuminate\Http\Request;

class TiketIssueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show($id, TiketDataTable $dataTable)
    {
        // Find the selected NOP
        $nop = Nop::findOrFail($id);

        $dapots = Dapot::all();

        // Render the view with DataTables
        return $dataTable->with('nop_id', $nop->nama_nop)->render('pages.tiketissue', compact(['nop', 'dapots']));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
