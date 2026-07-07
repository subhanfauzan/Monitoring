<?php

namespace App\Http\Controllers;

use App\Imports\ImportTiket;
use App\Imports\ImportAlarm;
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
    public function index(\App\DataTables\TiketDataTable $dataTable)
    {
        $dapots = Dapot::all();

        // === Chart Data per Jam untuk JATIM (Semua Data) ===
        $rows = \Illuminate\Support\Facades\DB::table('daftar_tiket')
            ->select(
                \Illuminate\Support\Facades\DB::raw("
    CASE
        WHEN MOD(time_down * 24, 24) >= 2  AND MOD(time_down * 24, 24) < 4  THEN '04:00'
        WHEN MOD(time_down * 24, 24) >= 4  AND MOD(time_down * 24, 24) < 6  THEN '06:00'
        WHEN MOD(time_down * 24, 24) >= 6  AND MOD(time_down * 24, 24) < 8  THEN '08:00'
        WHEN MOD(time_down * 24, 24) >= 8  AND MOD(time_down * 24, 24) < 10 THEN '10:00'
        WHEN MOD(time_down * 24, 24) >= 10 AND MOD(time_down * 24, 24) < 12 THEN '12:00'
        WHEN MOD(time_down * 24, 24) >= 12 AND MOD(time_down * 24, 24) < 14 THEN '14:00'
        WHEN MOD(time_down * 24, 24) >= 14 AND MOD(time_down * 24, 24) < 16 THEN '16:00'
        WHEN MOD(time_down * 24, 24) >= 16 AND MOD(time_down * 24, 24) < 18 THEN '18:00'
        WHEN MOD(time_down * 24, 24) >= 18 AND MOD(time_down * 24, 24) < 20 THEN '20:00'
        WHEN MOD(time_down * 24, 24) >= 20 AND MOD(time_down * 24, 24) < 22 THEN '22:00'
        WHEN MOD(time_down * 24, 24) >= 0  AND MOD(time_down * 24, 24) < 2  THEN '02:00'
        WHEN MOD(time_down * 24, 24) >= 22 OR  MOD(time_down * 24, 24) < 0  THEN '00:00'
    END as hour_slot
    "),
                \Illuminate\Support\Facades\DB::raw('COUNT(*) as total')
            )
            ->whereNotNull('time_down')
            ->groupBy(\Illuminate\Support\Facades\DB::raw('hour_slot'))
            ->orderBy('hour_slot')
            ->get();

        $hours = ['00:00', '02:00', '04:00', '06:00', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00', '22:00'];
        $chartData = array_fill(0, count($hours), 0);

        foreach ($rows as $row) {
            $hourIndex = array_search($row->hour_slot, $hours);
            if ($hourIndex !== false) {
                $chartData[$hourIndex] = $row->total;
            }
        }

        $totaltiket = Tiket::count();
        
        // === Chart Bulat (Pie) Data NOP ===
        $nopData = \Illuminate\Support\Facades\DB::table('daftar_tiket')
            ->select('nop', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'))
            ->groupBy('nop')
            ->get();

        $nops = [];
        $nopTotals = [];
        $nopColors = [];
        
        $colors = [
            '#fca5a5', '#fdba74', '#fcd34d', '#fde047', '#bef264', 
            '#86efac', '#6ee7b7', '#5eead4', '#67e8f9', '#7dd3fc', 
            '#93c5fd', '#a5b4fc', '#c4b5fd', '#d8b4fe', '#f0abfc', 
            '#f9a8d4', '#fda4af'
        ];

        foreach ($nopData as $index => $row) {
            $nops[] = $row->nop ?? 'Unknown NOP';
            $nopTotals[] = $row->total;
            $nopColors[] = $colors[$index % count($colors)];
        }

        return $dataTable->render('pages.tiket', compact(['dapots', 'hours', 'chartData', 'totaltiket', 'nops', 'nopTotals', 'nopColors']));
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
        
        if ($id === 'JATIM') {
            $tikets = Tiket::get()->groupBy('cluster_to');
        } else {
            $tikets = Tiket::where('nop', 'NOP ' . $id)
                ->get()
                ->groupBy('cluster_to');
        }

        // Lakukan sesuatu, contoh:
        return view('pages.result', compact('namanop', 'jam', 'piket1', 'piket2', 'tikets', 'time'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'site_id_select' => 'required|array',
            'site_id_select.*' => 'required',
            'time_down' => 'required|array',
            'time_down.*' => 'required',
            'status_ticket' => 'nullable|array',
            'status_ticket.*' => 'nullable|in:Open,Close',
        ]);

        $successCount = 0;
        $failCount = 0;

        foreach ($request->site_id_select as $index => $siteIdSelect) {
            if (!$siteIdSelect) {
                continue;
            }

            $site = Dapot::where('site_id', $siteIdSelect)->first();

            if (!$site) {
                toastr("Site $siteIdSelect tidak ditemukan!", 'error');
                $failCount++;
                continue;
            }

            // Parse datetime dengan format yang benar: Y-m-d\TH:i
            $timeDownRaw = $request->input("time_down.$index");
            try {
                $timeDown = Carbon::createFromFormat('Y-m-d\TH:i', $timeDownRaw)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                // Fallback jika parsing gagal
                $timeDown = Carbon::now()->format('Y-m-d H:i:s');
                toastr('Format tanggal tidak valid, menggunakan waktu sekarang!', 'warning');
            }

            $create = Tiket::create([
                'site_id' => $site->site_id . '_' . $site->site_name,
                'site_class' => $site->site_class,
                'saverity' => $request->input("saverity.$index", 'Low'),
                'nop' => $site->nop,
                'cluster_to' => $site->cluster_to,
                'suspect_problem' => 'Power',
                'status_site' => $request->input("status_site.$index", 'Down'),
                'time_down' => $timeDown,
                'waktu_saat_ini' => Carbon::now()->format('Y-m-d H:i:s'),
                'tim_fop' => $request->input("tim_fop.$index"),
                'ticket_swfm' => '',
                'nossa' => '',
                'remark' => $request->input("remark.$index"),
                // 'status_ticket' => $request->input("status_ticket.$index", 'Open'),
            ]);

            if ($create) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        if ($successCount > 0) {
            toastr("$successCount Data Berhasil Ditambahkan!", 'success');
        }

        if ($failCount > 0) {
            toastr("$failCount Data Gagal Ditambahkan!", 'error');
        }

        return redirect()->route('tiket.index');
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
            'suspect_problem' => $request->suspect_problem ?? $tiket->suspect_problem,
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
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'file' => 'required',
            ]);

            if ($validator->fails()) {
                toastr('Validasi Gagal: File wajib diupload.', 'error');
                return redirect()->back();
            }

            $file = $request->file('file');
            if (!$file->isValid()) {
                toastr('File upload gagal atau terlalu besar.', 'error');
                return redirect()->back();
            }

            $nama_file = $file->hashName();
            $path = $file->storeAs('excel', $nama_file, 'public');

            $import = Excel::import(new ImportTiket(), public_path('storage/excel/' . $nama_file));
            Storage::delete($path);

            if ($import) {
                if ($request->ajax()) {
                    return response()->json(['success' => true, 'message' => 'Data Berhasil Ditambahkan!']);
                }
                toastr('Data Berhasil Ditambahkan!', 'success');
            } else {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Data Gagal Ditambahkan!']);
                }
                toastr('Data Gagal Ditambahkan!', 'error');
            }
            
            return redirect()->back();
        } catch (\Throwable $e) {
            if (isset($path)) Storage::delete($path);
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Terjadi Kesalahan: ' . $e->getMessage()], 500);
            }
            toastr('Terjadi Kesalahan: ' . $e->getMessage(), 'error');
            return redirect()->back();
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

    public function importAlarm(Request $request)
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'file' => 'required',
            ]);

            if ($validator->fails()) {
                toastr('Validasi Gagal: File wajib diupload.', 'error');
                return redirect()->back();
            }

            $file = $request->file('file');
            if (!$file->isValid()) {
                toastr('File upload gagal atau terlalu besar.', 'error');
                return redirect()->back();
            }

            $nama_file = rand() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('excel', $nama_file, 'public');

            $importInstance = new ImportAlarm();
            \Maatwebsite\Excel\Facades\Excel::import($importInstance, public_path('storage/excel/' . $nama_file));
            \Illuminate\Support\Facades\Storage::delete($path);
            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => "Import Selesai: {$importInstance->insertedCount} data baru ditambahkan, {$importInstance->skippedCount} dilewati (duplikat)."]);
            }
            
            toastr("Import Selesai: {$importInstance->insertedCount} data baru ditambahkan, {$importInstance->skippedCount} dilewati (duplikat).", 'success');
            return redirect()->back();
        } catch (\Throwable $e) {
            if (isset($path)) \Illuminate\Support\Facades\Storage::delete($path);
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Import Gagal! ' . $e->getMessage()], 500);
            }
            toastr('Import Gagal! ' . $e->getMessage(), 'error');
            return redirect()->back();
        }
    }
}
