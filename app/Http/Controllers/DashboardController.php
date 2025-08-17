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
        $totaltiket = Tiket::count();
        $totalCritical = Tiket::where('saverity', 'Critical')->count();
        $totalMajor = Tiket::where('saverity', 'Major')->count();
        // $chartData = Tiket::select('site_class', DB::raw('count(*) as total'))
        // ->groupBy('site_class')
        // ->orderBy('site_class')
        // ->pluck('total', 'site_class'); // hasil: ['Bronze' => 10, 'Gold' => 2, ...]

        // WHEN MOD(time_down * 24, 24) >= 22 OR MOD(time_down * 24, 24) < 2 THEN '00:00'
        // Pendekatan menggunakan FLOOR() dan MOD() untuk ekstrak jam langsung dari nilai numerik
        $rows = Tiket::select(
            DB::raw("
    CASE
        WHEN MOD(time_down * 24, 24) >= 2 AND MOD(time_down * 24, 24) < 4 THEN '04:00'
        WHEN MOD(time_down * 24, 24) >= 4 AND MOD(time_down * 24, 24) < 6 THEN '06:00'
        WHEN MOD(time_down * 24, 24) >= 6 AND MOD(time_down * 24, 24) < 8 THEN '08:00'
        WHEN MOD(time_down * 24, 24) >= 8 AND MOD(time_down * 24, 24) < 10 THEN '10:00'
        WHEN MOD(time_down * 24, 24) >= 10 AND MOD(time_down * 24, 24) < 12 THEN '12:00'
        WHEN MOD(time_down * 24, 24) >= 12 AND MOD(time_down * 24, 24) < 14 THEN '14:00'
        WHEN MOD(time_down * 24, 24) >= 14 AND MOD(time_down * 24, 24) < 16 THEN '16:00'
        WHEN MOD(time_down * 24, 24) >= 16 AND MOD(time_down * 24, 24) < 18 THEN '18:00'
        WHEN MOD(time_down * 24, 24) >= 18 AND MOD(time_down * 24, 24) < 20 THEN '20:00'
        WHEN MOD(time_down * 24, 24) >= 20 AND MOD(time_down * 24, 24) < 22 THEN '22:00'
        WHEN MOD(time_down * 24, 24) >= 22 AND MOD(time_down * 24, 24) < 00 THEN '00:00'
        WHEN MOD(time_down * 24, 24) >= 00 AND MOD(time_down * 24, 24) < 2 THEN '02:00'
    END as hour_slot
    "),
            DB::raw('UPPER(site_class) as site_class'),
            DB::raw('COUNT(*) as total'),
        )
            ->whereNotNull('time_down')
            ->groupBy(DB::raw('hour_slot'), DB::raw('UPPER(site_class)'))
            ->orderBy('hour_slot')
            ->get();

        $hours = ['00:00', '02:00', '04:00', '06:00', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00', '22:00'];
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

        // dd($rows);

        return view('pages.dashboard', compact('totaltiket', 'totalMajor', 'totalCritical', 'chartData', 'hours'));
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
