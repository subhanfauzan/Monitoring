<?php

namespace App\Http\Controllers;

use App\Models\Tiket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // === Statistik Total Tiket ===
        $totaltiket = DB::table('daftar_tiket')->count();
        $totalOpen   = DB::table('daftar_tiket')->where('status_ticket', 'Open')->count();
        $totalClose  = DB::table('daftar_tiket')->where('status_ticket', 'Close')->count();

        // === Severity ===
        $totalCritical = DB::table('daftar_tiket')->where('saverity', 'Critical')->count();
        $totalMajor    = DB::table('daftar_tiket')->where('saverity', 'Major')->count();
        $totalMinor    = DB::table('daftar_tiket')->where('saverity', 'Minor')->count();
        $totalLow      = DB::table('daftar_tiket')->where('saverity', 'Low')->count();

        // === Down By Site Class ===
        $siteClassCounts = DB::table('daftar_tiket')
            ->select(DB::raw('UPPER(site_class) as site_class'), DB::raw('COUNT(*) as total'))
            ->groupBy(DB::raw('UPPER(site_class)'))
            ->pluck('total', 'site_class');

        $downBySiteClass = [
            'DIAMOND'  => $siteClassCounts->get('DIAMOND', 0),
            'PLATINUM' => $siteClassCounts->get('PLATINUM', 0),
            'GOLD'     => $siteClassCounts->get('GOLD', 0),
            'SILVER'   => $siteClassCounts->get('SILVER', 0),
            'BRONZE'   => $siteClassCounts->get('BRONZE', 0),
        ];

        // === Top Cluster dengan Site Down terbanyak ===
        $topClusters = DB::table('daftar_tiket')
            ->select('cluster_to', DB::raw('COUNT(*) as total'))
            ->groupBy('cluster_to')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // === Top 5 Kategori (Berdasarkan Suspect Problem) ===
        $topCategories = DB::table('daftar_tiket')
            ->select('suspect_problem', DB::raw('COUNT(*) as total'))
            ->groupBy('suspect_problem')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // === Tiket Terbaru ===
        $recentTickets = DB::table('daftar_tiket')
            ->select('site_id', 'site_class', 'saverity', 'status_ticket', 'status_site', 'time_down', 'cluster_to', 'created_at')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // === Chart Data per Jam per Site Class ===
        $rows = DB::table('daftar_tiket')
            ->select(
                DB::raw("
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
                DB::raw('UPPER(site_class) as site_class'),
                DB::raw('COUNT(*) as total'),
            )
            ->whereNotNull('time_down')
            ->groupBy(DB::raw('hour_slot'), DB::raw('UPPER(site_class)'))
            ->orderBy('hour_slot')
            ->get();

        $hours   = ['00:00', '02:00', '04:00', '06:00', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00', '22:00'];
        $classes = ['PLATINUM', 'GOLD', 'SILVER', 'BRONZE'];

        $chartData = [];
        foreach ($classes as $class) {
            $chartData[$class] = array_fill(0, count($hours), 0);
        }

        foreach ($rows as $row) {
            $hourIndex = array_search($row->hour_slot, $hours);
            if ($hourIndex !== false && in_array($row->site_class, $classes)) {
                $chartData[$row->site_class][$hourIndex] = $row->total;
            }
        }

        // === Pie chart severity ===
        $severityPie = [
            'Critical' => $totalCritical,
            'Major'    => $totalMajor,
            'Minor'    => $totalMinor,
            'Low'      => $totalLow,
        ];

        return view('pages.dashboard', compact(
            'totaltiket', 'totalOpen', 'totalClose',
            'totalCritical', 'totalMajor', 'totalMinor', 'totalLow',
            'downBySiteClass',
            'topClusters',
            'topCategories',
            'recentTickets',
            'chartData', 'hours',
            'severityPie'
        ));
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
