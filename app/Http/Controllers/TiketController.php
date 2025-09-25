<?php

namespace App\Http\Controllers;

use App\Imports\ImportTiket;
use App\Models\Dapot;
use App\Models\Tiket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Ticket;

class TiketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $totaltiket = Tiket::count();
        return view('pages.tiket', compact('totaltiket'));
    }

    // In your TiketController.php
    public function getCount()
    {
        $totaltiket = Tiket::count(); // Get the updated ticket count
        return response()->json(['success' => true, 'total' => $totaltiket]); // Return it as JSON
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
    public function export(Request $request, $id)
    {
        $namanop = $id;
        $jam = $request->query('flatpickr-time');
        $piket1 = $request->query('piket1');
        $piket2 = $request->query('piket2');
        $time = $request->query('flatpickr-datetime');
        $tikets = Tiket::where('nop', 'NOP ' . $id)
            ->get()
            ->groupBy('cluster_to');

        // Lakukan sesuatu, contoh:
        return view('pages.result', compact('namanop', 'jam', 'piket1', 'piket2', 'tikets', 'time'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'site_id' => 'required',
            'time_down' => 'required',
            'status_ticket' => 'nullable|in:Open,Close',
        ]);

        // Parse datetime dengan format yang benar: Y-m-d\TH:i
        try {
            $timeDown = Carbon::createFromFormat('Y-m-d\TH:i', $request->input('time_down'))->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            // Fallback jika parsing gagal
            $timeDown = Carbon::now()->format('Y-m-d H:i:s');
            toastr('Format tanggal tidak valid, menggunakan waktu sekarang!', 'warning');
        }

        // Get site data
        $site = Dapot::where('site_id', $request->site_id_select)->first();

        if (!$site) {
            toastr('Site tidak ditemukan!', 'error');
            return redirect()->back();
        }

        $create = Tiket::create([
            'site_id' => $site->site_id . '_' . $site->site_name,
            'site_class' => $site->site_class,
            'saverity' => $request->saverity,
            'nop' => $site->nop,
            'cluster_to' => $site->cluster_to,
            'suspect_problem' => 'Power',
            'status_site' => $request->status_site ?: 'Down',
            'time_down' => $timeDown,
            'waktu_saat_ini' => Carbon::now()->format('Y-m-d H:i:s'),
            'tim_fop' => $request->tim_fop,
            'ticket_swfm' => '',
            'nossa' => '',
            'remark' => $request->remark,
            // 'status_ticket' => $request->input('status_ticket', 'Open'),
        ]);

        if ($create) {
            toastr('Data Berhasil Ditambahkan!', 'success');
            return redirect()->route('tiket.index');
        } else {
            toastr('Gagal menambahkan data!', 'error');
            return redirect()->back();
        }
    }

    public function toggleLock(Request $request, $id)
    {
        $tiket = Tiket::findOrFail($id);
        $tiket->lock = $request->lock; // Set nilai lock (1 atau 0)
        $tiket->save();

        return response()->json(['success' => true]);
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
        $tiket = Tiket::findOrFail($id);

        $tiket->update([
            'status_site' => $request->status_site,
            'tim_fop' => $request->tim_fop,
            'remark' => $request->remark,
            'status_ticket' => $request->status_ticket ?? $tiket->status_ticket,
        ]);

        if ($tiket) {
            toastr('Data Berhasil Berubah!', 'success');
            return redirect()->back();
        }
    }

    public function destroy(string $id)
    {
        try {
            $tiket = Tiket::findOrFail($id);

            if ($tiket->lock == 1) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Data tidak bisa dihapus karena statusnya terkunci!',
                        'lock' => true,
                    ],
                    400,
                );
            }

            $this->archiveAndDelete($tiket);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function destroynop($id)
    {
        try {
            // Get all tickets associated with the given NOP id
            $tikets = Tiket::where('nop', "NOP $id")->get();

            // Initialize a counter to track the number of successfully deleted tickets
            $deletedCount = 0;

            foreach ($tikets as $tiket) {
                if ($tiket->lock == 1) {
                    continue;
                }

                $this->archiveAndDelete($tiket);
                $deletedCount++;
            }

            // If we deleted some tickets, return success, otherwise inform about locked tickets
            if ($deletedCount > 0) {
                return response()->json(
                    [
                        'success' => true,
                        'message' => "$deletedCount data berhasil dihapus",
                        'count' => $deletedCount,
                    ],
                    200,
                );
            }

            // If no tickets were deleted, inform the user about locked tickets
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Semua data terkunci dan tidak bisa dihapus.',
                    'lock' => true,
                ],
                400,
            );
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function destroyall()
    {
        try {
            // Get all tickets
            $tikets = Tiket::all();

            // Initialize a counter to track the number of successfully deleted tickets
            $deletedCount = 0;

            foreach ($tikets as $tiket) {
                if ($tiket->lock == 1) {
                    continue;
                }

                $this->archiveAndDelete($tiket);
                $deletedCount++;
            }

            // If we deleted some tickets, return success, otherwise inform about locked tickets
            if ($deletedCount > 0) {
                return response()->json(
                    [
                        'success' => true,
                        'message' => "$deletedCount data berhasil dihapus",
                        'count' => $deletedCount,
                    ],
                    200,
                );
            }

            // If no tickets were deleted, inform the user about locked tickets
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Semua data terkunci dan tidak bisa dihapus.',
                    'lock' => true,
                ],
                400,
            );
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
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

        $import = Excel::import(new ImportTiket(), public_path('storage/excel/' . $nama_file));

        //remove from server
        Storage::delete($path);

        if ($import) {
            //redirect
            // dd($file, $nama_file, $path);
            toastr('Data Berhasil Ditambahkan!', 'success');
            return redirect()->route('tiket.index');
        } else {
            //redirect
            // dd($file, $nama_file, $path);

            toastr('Data Gagal Ditambahkan!', 'error');
            return redirect()->route('tiket.index');
        }
    }

    public function autocomplete(Request $request)
    {
        $search = $request->get('site_id');
        $result = Dapot::where('site_id', 'LIKE', '%' . $search . '%')->get();
        return response()->json($result);
    }

    public function bulkUpdate(Request $request)
    {
        $ids = explode(',', $request->selected_ids);

        // Ambil hanya field yang diisi
        $updateData = [];
        if (!empty($request->tim_fop)) {
            $updateData['tim_fop'] = $request->tim_fop;
        }
        if (!empty($request->remark)) {
            $updateData['remark'] = $request->remark;
        }

        // Jika tidak ada data yang diisi, jangan update
        if (empty($updateData)) {
            return redirect()->back()->with('error', 'Tidak ada perubahan yang diberikan.');
        }

        Tiket::whereIn('id', $ids)->update($updateData);

        return redirect()->back()->with('success', 'Data berhasil diupdate!');
    }

    private function archiveAndDelete(\App\Models\Tiket $tiket): void
    {
        DB::transaction(function () use ($tiket) {
            // ambil seluruh atribut tiket, buang id agar auto-increment di tabel arsip
            $data = $tiket->getAttributes();
            unset($data['id']);

            // opsional: simpan kapan dihapus
            $data['deleted_at'] = now();

            // simpan ke tabel arsip
            DB::table('tikethapus')->insert($data);

            $tiket->forceDelete();
        });
    }
}
