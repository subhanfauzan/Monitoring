<?php

namespace App\Http\Controllers;

use App\DataTables\DapotDataTable;
use App\Imports\ImportDapot;
use App\Models\Dapot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class DapotController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(DapotDataTable $dataTable)
    {
        return $dataTable->render('pages.daftardapot');
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
            'site_id' => 'required',
            'site_name' => 'required',
            'nop' => 'required',
            'cluster_to' => 'required',
        ]);

        $create = Dapot::create([
            'site_id' => $request->site_id,
            'site_name' => $request->site_name,
            'nop' => $request->nop,
            'cluster_to' => $request->cluster_to,
        ]);

        if ($create) {
            return redirect()->route('dapot.index');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
        $request->validate([
            'site_id' => 'required',
            'site_name' => 'required',
            'nop' => 'required',
            'cluster_to' => 'required',
        ]);

        $dapot = Dapot::findOrFail($id);
        $dapot->update([
            'site_id' => $request->site_id,
            'site_name' => $request->site_name,
            'nop' => $request->nop,
            'cluster_to' => $request->cluster_to,
        ]);

        return redirect()->back()->with('success', 'Data updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $dapot = Dapot::findOrFail($id);
            $dapot->delete();

            return response()->json([
                'success' => true,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xls,xlsx',
        ]);

        $file = $request->file('file');

        // membuat nama file unik
        $nama_file = $file->hashName();

        //temporary file

        $path = $file->storeAs('excel', $nama_file, 'public');

        $import = Excel::import(new ImportDapot, public_path('storage/excel/'.$nama_file));

        //remove from server
        Storage::delete($path);

        if ($import) {
            //redirect
            // dd($file, $nama_file, $path);
            toastr('Data Berhasil Ditambahkan!', 'success');
            return redirect()->route('dapot.index');
        } else {
            //redirect
            dd($file, $nama_file, $path);

            toastr('Data Gagal Ditambahkan!', 'error');
            return redirect()->route('dapot.index');
        }
    }
}
