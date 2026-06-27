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

        // === Chart Data per Jam untuk NOP ini ===
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
            ->where('nop', 'NOP ' . $nop->nama_nop)
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

        // === Chart Bulat (Pie/Doughnut) Data Cluster TO ===
        $clusterData = \Illuminate\Support\Facades\DB::table('daftar_tiket')
            ->select('cluster_to', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'))
            ->where('nop', 'NOP ' . $nop->nama_nop)
            ->groupBy('cluster_to')
            ->get();

        $clusters = [];
        $clusterTotals = [];
        $clusterColors = [];
        
        $colors = [
            '#fca5a5', '#fdba74', '#fcd34d', '#fde047', '#bef264', 
            '#86efac', '#6ee7b7', '#5eead4', '#67e8f9', '#7dd3fc', 
            '#93c5fd', '#a5b4fc', '#c4b5fd', '#d8b4fe', '#f0abfc', 
            '#f9a8d4', '#fda4af'
        ];

        foreach ($clusterData as $index => $row) {
            $clusters[] = $row->cluster_to ?? 'Unknown Cluster';
            $clusterTotals[] = $row->total;
            $clusterColors[] = $colors[$index % count($colors)];
        }

        $totaltiket = array_sum($clusterTotals);

        // Render the view with DataTables
        return $dataTable->with('nop_id', $nop->nama_nop)->render('pages.tiketissue', compact(['nop', 'dapots', 'hours', 'chartData', 'totaltiket', 'clusters', 'clusterTotals', 'clusterColors']));
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
