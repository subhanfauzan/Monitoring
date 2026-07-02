@extends('layouts.layout')

@section('content')
<style>
    body.screenshot-mode .layout-menu {
        display: none !important;
    }
    body.screenshot-mode .layout-navbar {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        left: 0 !important;
        right: 0 !important;
        border-radius: 0 !important;
        padding-left: 2rem !important;
        padding-right: 2rem !important;
    }
    body.screenshot-mode .layout-page {
        padding-left: 0 !important;
        margin-left: 0 !important;
    }
    body.screenshot-mode .action-buttons-container {
        display: none !important;
    }
    body.screenshot-mode footer {
        display: none !important;
    }

    /* ========== Dashboard Custom Styles ========== */
    .dashboard-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        letter-spacing: -0.3px;
    }
    .dashboard-subtitle {
        font-size: 0.85rem;
        color: #64748b;
    }

    /* ---- Summary Cards ---- */
    .stat-card {
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 6px rgba(0,0,0,0.05);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(0,0,0,0.09);
    }
    .stat-card .card-body {
        padding: 1.25rem 1.5rem;
    }
    .stat-card .stat-icon {
        width: 44px; height: 44px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
    }
    .stat-card .stat-value {
        font-size: 1.9rem;
        font-weight: 800;
        line-height: 1.1;
        color: #fff;
    }
    .stat-card .stat-label {
        font-size: 0.78rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        opacity: 0.80;
        color: #fff;
    }
    .stat-card .stat-sub {
        font-size: 0.78rem;
        opacity: 0.65;
        color: #fff;
    }

    /* ---- Down By Site Class ---- */
    .section-header {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        color: #64748b;
        background: #f1f5f9;
        padding: 0.35rem 0.9rem;
        border-radius: 5px;
        display: inline-block;
        margin-bottom: 0.75rem;
    }
    .site-class-badge {
        border-radius: 10px;
        padding: 1rem 0.75rem;
        text-align: center;
        transition: transform 0.2s, box-shadow 0.2s;
        cursor: default;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
    }
    .site-class-badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .site-class-badge .class-count {
        font-size: 1.8rem;
        font-weight: 800;
        line-height: 1;
        color: #334155;
    }
    .site-class-badge .class-name {
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #94a3b8;
        margin-top: 0.25rem;
    }
    /* .badge-total   { background: #f1f5f9; border-color: #cbd5e1; }
    .badge-total .class-count { color: #1e293b; }
    
    .badge-diamond { background: #ecfeff; border-color: #a5f3fc; }
    .badge-diamond .class-count { color: #0891b2; }
    .badge-diamond .class-name { color: #06b6d4; }
    
    .badge-platinum{ background: #f5f3ff; border-color: #ddd6fe; }
    .badge-platinum .class-count { color: #7c3aed; }
    .badge-platinum .class-name { color: #8b5cf6; }
    
    .badge-gold    { background: #fefce8; border-color: #fef08a; }
    .badge-gold .class-count { color: #ca8a04; }
    .badge-gold .class-name { color: #eab308; }
    
    .badge-silver  { background: #f8fafc; border-color: #e2e8f0; }
    .badge-silver .class-count { color: #475569; }
    .badge-silver .class-name { color: #94a3b8; }
    
    .badge-bronze  { background: #fffbeb; border-color: #fde68a; }
    .badge-bronze .class-count { color: #b45309; }
    .badge-bronze .class-name { color: #d97706; } */

    /* ---- Chart Cards ---- */
    .chart-card {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 6px rgba(0,0,0,0.05);
    }
    .chart-card .card-header-custom {
        padding: 1rem 1.25rem 0.5rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .chart-card .card-title-custom {
        font-size: 0.9rem;
        font-weight: 700;
        color: #1e293b;
    }
    .chart-card .card-subtitle-custom {
        font-size: 0.75rem;
        color: #94a3b8;
    }

    /* ---- Severity Pills ---- */
    .sev-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.55rem 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .sev-row:last-child { border-bottom: none; }
    .sev-dot {
        width: 9px; height: 9px;
        border-radius: 50%;
        flex-shrink: 0;
        margin-right: 0.6rem;
    }
    .sev-label { font-size: 0.82rem; font-weight: 600; color: #475569; }
    .sev-count { font-size: 0.9rem; font-weight: 700; color: #1e293b; }
    .sev-bar-wrap {
        flex: 1;
        margin: 0 0.75rem;
        background: #e2e8f0;
        border-radius: 100px;
        height: 5px;
        overflow: hidden;
    }
    .sev-bar { height: 100%; border-radius: 100px; transition: width 0.8s ease; }

    /* ---- Top Cluster ---- */
    .cluster-row {
        display: flex;
        align-items: center;
        padding: 0.45rem 0;
        border-bottom: 1px solid #f8fafc;
    }
    .cluster-row:last-child { border-bottom: none; }
    .cluster-rank {
        width: 24px; height: 24px;
        border-radius: 6px;
        background: #f1f5f9;
        color: #64748b;
        font-size: 0.7rem;
        font-weight: 700;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        margin-right: 0.75rem;
    }
    .cluster-rank.top-1 { background: #e2e8f0; color: #334155; }
    .cluster-rank.top-2 { background: #f1f5f9; color: #475569; }
    .cluster-rank.top-3 { background: #f8fafc; color: #64748b; }
    .cluster-name { font-size: 0.82rem; font-weight: 600; color: #374151; flex: 1; }
    .cluster-count { font-size: 0.82rem; font-weight: 700; color: #1e293b; }

    /* ---- Recent Tickets Table ---- */
    .recent-table th {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #94a3b8;
        background: #f8fafc;
        border: none;
        padding: 0.6rem 0.9rem;
    }
    .recent-table td {
        font-size: 0.8rem;
        color: #374151;
        border-color: #f1f5f9;
        padding: 0.65rem 0.9rem;
        vertical-align: middle;
    }
    .recent-table tbody tr:hover { background: #f8fafc; }
    .badge-severity {
        font-size: 0.65rem;
        font-weight: 600;
        padding: 0.22em 0.6em;
        border-radius: 4px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        border: 1px solid;
    }
    .badge-critical { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
    .badge-major    { background: #fff7ed; color: #c2410c; border-color: #fed7aa; }
    .badge-minor    { background: #f8fafc; color: #64748b; border-color: #e2e8f0; }
    .badge-low      { background: #f8fafc; color: #94a3b8; border-color: #e2e8f0; }
    .badge-open     { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
    .badge-close    { background: #f1f5f9; color: #334155; border-color: #cbd5e1; }
    .badge-down     { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
    .badge-up       { background: #f1f5f9; color: #334155; border-color: #cbd5e1; }

    /* ---- Status Cards ---- */
    .status-card {
        border-radius: 10px;
        padding: 1rem;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    }

    /* ---- Animate number ---- */
    .counter-num { transition: all 0.5s; }

    /* ---- Responsive ---- */
    @media (max-width: 1199.98px) {
        .chart-card .card-body canvas { max-height: 260px; }
    }
    @media (max-width: 991.98px) {
        .site-class-badge .class-count { font-size: 1.5rem; }
        .site-class-badge { padding: 0.85rem 0.5rem; }
        .chart-card .card-header-custom { padding: 0.85rem 1rem 0.4rem; }
        .chart-card .card-body { padding: 0.85rem 1rem; }
    }
    @media (max-width: 767.98px) {
        .recent-table th, .recent-table td { padding: 0.45rem 0.55rem; font-size: 0.72rem; }
        .recent-table th { font-size: 0.62rem; }
        .badge-severity { font-size: 0.58rem; padding: 0.18em 0.45em; }
        .cluster-name { font-size: 0.78rem; }
        .cluster-count { font-size: 0.78rem; }
        .sev-label { font-size: 0.78rem; }
        .sev-count { font-size: 0.82rem; }
    }
    @media (max-width: 575.98px) {
        .site-class-badge .class-count { font-size: 1.25rem; }
        .site-class-badge .class-name  { font-size: 0.58rem; letter-spacing: 0.4px; }
        .site-class-badge { padding: 0.65rem 0.3rem; border-radius: 8px; }
        .chart-card { border-radius: 10px; }
        .chart-card .card-title-custom { font-size: 0.82rem; }
        .chart-card .card-subtitle-custom { font-size: 0.68rem; }
        .chart-card .card-header-custom { padding: 0.75rem 0.85rem 0.35rem; }
        .chart-card .card-body { padding: 0.75rem 0.85rem; }
        .section-header { font-size: 0.62rem; padding: 0.3rem 0.7rem; }
    }
    /* ---- Fix Horizontal Scroll & Match Navbar ---- */
    .dashboard-wrapper {
        width: 100%;
        padding-left: 1.5rem;
        padding-right: 1.5rem;
        overflow-x: hidden;
    }
    @media (max-width: 991.98px) {
        .dashboard-wrapper {
            padding-left: 1rem;
            padding-right: 1rem;
        }
    }
</style>

<div class="flex-grow-1 container-p-y dashboard-wrapper">

    {{-- ======= DOWN BY SITE CLASS ======= --}}
    <div class="card chart-card mb-4 mt-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="section-header mb-0">⬇ Down By Site Class</span>
                <div class="action-buttons-container">
                    <button type="button" class="btn bg-transparent border-0 shadow-none p-0" id="btn-screenshot" title="Screenshot">
                        <i class="ti ti-camera fs-3 text-dark" style="color: #000 !important;"></i>
                    </button>
                </div>
            </div>
            <div class="row g-2 g-md-3">

                {{-- Total --}}
                <div class="col-4 col-sm-4 col-md-2">
                    <div class="site-class-badge badge-total">
                        <div class="class-count">{{ $totaltiket }}</div>
                        <div class="class-name">Total Site Down</div>
                    </div>
                </div>

                {{-- Diamond --}}
                <div class="col-4 col-sm-4 col-md-2">
                    <div class="site-class-badge badge-diamond">
                        <div class="class-count">{{ $downBySiteClass['DIAMOND'] }}</div>
                        <div class="class-name">Diamond</div>
                    </div>
                </div>

                {{-- Platinum --}}
                <div class="col-4 col-sm-4 col-md-2">
                    <div class="site-class-badge badge-platinum">
                        <div class="class-count">{{ $downBySiteClass['PLATINUM'] }}</div>
                        <div class="class-name">Platinum</div>
                    </div>
                </div>

                {{-- Gold --}}
                <div class="col-4 col-sm-4 col-md-2">
                    <div class="site-class-badge badge-gold">
                        <div class="class-count">{{ $downBySiteClass['GOLD'] }}</div>
                        <div class="class-name">Gold</div>
                    </div>
                </div>

                {{-- Silver --}}
                <div class="col-4 col-sm-4 col-md-2">
                    <div class="site-class-badge badge-silver">
                        <div class="class-count">{{ $downBySiteClass['SILVER'] }}</div>
                        <div class="class-name">Silver</div>
                    </div>
                </div>

                {{-- Bronze --}}
                <div class="col-4 col-sm-4 col-md-2">
                    <div class="site-class-badge badge-bronze">
                        <div class="class-count">{{ $downBySiteClass['BRONZE'] }}</div>
                        <div class="class-name">Bronze</div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ======= CHART ROW: Line + Severity Pie ======= --}}
    <div class="row g-3 mb-4">

        {{-- Line Chart per Jam --}}
        <div class="col-12 col-lg-8">
            <div class="card chart-card h-100">
                <div class="card-header-custom">
                    <div class="card-title-custom">Distribusi Tiket per Jam</div>
                    <div class="card-subtitle-custom">Berdasarkan Site Class — hari ini</div>
                </div>
                <div class="card-body pt-2">
                    <div style="position:relative; height:300px;">
                        <canvas id="severityLineChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Severity + Status ---- --}}
        <div class="col-12 col-lg-4">
            <div class="card chart-card h-100">
                <div class="card-header-custom">
                    <div class="card-title-custom">Distribusi Severity</div>
                    <div class="card-subtitle-custom">Semua tiket aktif</div>
                </div>
                <div class="card-body pt-2">
                    <div style="position:relative;height:180px;margin-bottom:1rem;">
                        <canvas id="severityDoughnut"></canvas>
                    </div>
                    @php $grandTotal = $totaltiket ?: 1; @endphp
                    <div>
                        <div class="sev-row">
                            <span class="sev-dot" style="background:#e11d48;"></span>
                            <span class="sev-label">Critical</span>
                            <div class="sev-bar-wrap" style="background:#ffe4e6;"><div class="sev-bar" style="background:#e11d48;width:{{ round($totalCritical/$grandTotal*100) }}%;"></div></div>
                            <span class="sev-count">{{ $totalCritical }}</span>
                        </div>
                        <div class="sev-row">
                            <span class="sev-dot" style="background:#ea580c;"></span>
                            <span class="sev-label">Major</span>
                            <div class="sev-bar-wrap" style="background:#ffedd5;"><div class="sev-bar" style="background:#ea580c;width:{{ round($totalMajor/$grandTotal*100) }}%;"></div></div>
                            <span class="sev-count">{{ $totalMajor }}</span>
                        </div>
                        <div class="sev-row">
                            <span class="sev-dot" style="background:#3b82f6;"></span>
                            <span class="sev-label">Minor</span>
                            <div class="sev-bar-wrap" style="background:#dbeafe;"><div class="sev-bar" style="background:#3b82f6;width:{{ round($totalMinor/$grandTotal*100) }}%;"></div></div>
                            <span class="sev-count">{{ $totalMinor }}</span>
                        </div>
                        <div class="sev-row">
                            <span class="sev-dot" style="background:#10b981;"></span>
                            <span class="sev-label">Low</span>
                            <div class="sev-bar-wrap" style="background:#d1fae5;"><div class="sev-bar" style="background:#10b981;width:{{ round($totalLow/$grandTotal*100) }}%;"></div></div>
                            <span class="sev-count">{{ $totalLow }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ======= BOTTOM ROW: Top Cluster + Recent Tickets ======= --}}
    <div class="row g-3 mb-4">

        {{-- Kolom Kiri: Top Cluster & Top Category --}}
        <div class="col-12 col-lg-4 d-flex flex-column gap-3">
            {{-- Top Cluster --}}
            <div class="card chart-card flex-fill">
                <div class="card-header-custom">
                    <div class="card-title-custom">Top 5 Cluster</div>
                    <div class="card-subtitle-custom">Jumlah site down terbanyak</div>
                </div>
                <div class="card-body pt-2">
                    @forelse($topClusters as $i => $cluster)
                    <div class="cluster-row">
                        <div class="cluster-rank {{ $i==0 ? 'top-1' : ($i==1 ? 'top-2' : ($i==2 ? 'top-3' : '')) }}">{{ $i+1 }}</div>
                        <div class="cluster-name">{{ $cluster->cluster_to ?: '-' }}</div>
                        <div class="cluster-count">{{ $cluster->total }} site</div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-3" style="font-size:0.82rem;">Tidak ada data cluster</div>
                    @endforelse
                </div>
            </div>

            {{-- Top 5 Kategori --}}
            <div class="card chart-card flex-fill">
                <div class="card-header-custom">
                    <div class="card-title-custom">Top 5 Kategori</div>
                    <div class="card-subtitle-custom">Berdasarkan Suspect Problem</div>
                </div>
                <div class="card-body pt-2">
                    @forelse($topCategories as $i => $category)
                    @php
                        $catName = $category->suspect_problem === 'ESCALATED TO INSERA' ? 'Transmisi' : ($category->suspect_problem ?: '-');
                    @endphp
                    <div class="cluster-row">
                        <div class="cluster-rank {{ $i==0 ? 'top-1' : ($i==1 ? 'top-2' : ($i==2 ? 'top-3' : '')) }}">{{ $i+1 }}</div>
                        <div class="cluster-name" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-right: 0.5rem;" title="{{ $catName }}">
                            {{ $catName }}
                        </div>
                        <div class="cluster-count">{{ $category->total }} tiket</div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-3" style="font-size:0.82rem;">Tidak ada data kategori</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Recent Tickets --}}
        <div class="col-12 col-lg-8">
            <div class="card chart-card h-100">
                <div class="card-header-custom d-flex align-items-center justify-content-between">
                    <div>
                        <div class="card-title-custom">Tiket Terbaru</div>
                        <div class="card-subtitle-custom">10 tiket paling baru</div>
                    </div>
                    <a href="{{ route('tiket.index') }}" style="font-size:0.75rem;color:#475569;font-weight:600;text-decoration:none;">Lihat semua →</a>
                </div>
                <div class="card-body pt-2">
                    <div class="table-responsive">
                    <table class="table recent-table mb-0">
                        <thead>
                            <tr>
                                <th>Site ID</th>
                                <th>Class</th>
                                <th>Severity</th>
                                <th>Status Site</th>
                                <th>Tiket</th>
                                <th>Cluster</th>
                                <th>Durasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTickets as $ticket)
                            <tr>
                                <td style="max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $ticket->site_id }}">
                                    {{ $ticket->site_id }}
                                </td>
                                <td>{{ strtoupper($ticket->site_class) }}</td>
                                <td>
                                    @php $sev = strtolower($ticket->saverity); @endphp
                                    <span class="badge-severity badge-{{ $sev }}">{{ $ticket->saverity }}</span>
                                </td>
                                <td>
                                    @php $ss = strtolower($ticket->status_site ?? ''); @endphp
                                    <span class="badge-severity badge-{{ $ss }}">{{ $ticket->status_site ?? '-' }}</span>
                                </td>
                                <td>
                                    @php $st = strtolower($ticket->status_ticket ?? ''); @endphp
                                    <span class="badge-severity badge-{{ $st }}">{{ $ticket->status_ticket }}</span>
                                </td>
                                <td>{{ $ticket->cluster_to ?: '-' }}</td>
                                <td style="white-space:nowrap;">
                                    @php
                                        if ($ticket->time_down && is_numeric($ticket->time_down)) {
                                            $excelSerial = (float) $ticket->time_down;
                                            $unixTs = ($excelSerial - 25569) * 86400;
                                            $dtDown = \Carbon\Carbon::createFromTimestampUTC($unixTs)->setTimezone('Asia/Jakarta');
                                            $dtImport = $ticket->created_at ? \Carbon\Carbon::parse($ticket->created_at) : \Carbon\Carbon::now();
                                            
                                            $diff = $dtDown->diff($dtImport);
                                            $parts = [];
                                            if ($diff->d > 0) $parts[] = $diff->d . ' Hari';
                                            if ($diff->h > 0) $parts[] = $diff->h . ' Jam';
                                            if ($diff->i > 0) $parts[] = $diff->i . ' Mnt';
                                            
                                            echo empty($parts) ? '< 1 Mnt' : implode(' ', $parts);
                                        } else {
                                            echo '-';
                                        }
                                    @endphp
                                </td>

                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Tidak ada data tiket</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#btn-screenshot').on('click', function() {
            var elem = document.documentElement;
            if (elem.requestFullscreen) {
                elem.requestFullscreen();
            } else if (elem.webkitRequestFullscreen) { /* Safari */
                elem.webkitRequestFullscreen();
            } else if (elem.msRequestFullscreen) { /* IE11 */
                elem.msRequestFullscreen();
            }
        });

        $(document).on('fullscreenchange webkitfullscreenchange msfullscreenchange', function() {
            if (document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement) {
                $('body').addClass('screenshot-mode');
            } else {
                $('body').removeClass('screenshot-mode');
            }
        });
    });

    // ======= LINE CHART: Distribusi per Jam =======
    (function() {
        const ctx = document.getElementById('severityLineChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($hours) !!},
                datasets: [
                    {
                        label: 'PLATINUM',
                        data: {!! json_encode($chartData['PLATINUM']) !!},
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139,92,246,0.07)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#8b5cf6'
                    },
                    {
                        label: 'GOLD',
                        data: {!! json_encode($chartData['GOLD']) !!},
                        borderColor: '#eab308',
                        backgroundColor: 'rgba(234,179,8,0.07)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#eab308'
                    },
                    {
                        label: 'SILVER',
                        data: {!! json_encode($chartData['SILVER']) !!},
                        borderColor: '#94a3b8',
                        backgroundColor: 'rgba(148,163,184,0.07)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#94a3b8'
                    },
                    {
                        label: 'BRONZE',
                        data: {!! json_encode($chartData['BRONZE']) !!},
                        borderColor: '#d97706',
                        backgroundColor: 'rgba(217,119,6,0.07)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#d97706'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { font: { size: 11 }, boxWidth: 12, padding: 12 }
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleFont: { size: 11 },
                        bodyFont: { size: 11 },
                        padding: 10,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { font: { size: 10 }, stepSize: 1 }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });
    })();

    // ======= DOUGHNUT CHART: Severity =======
    (function() {
        const ctx2 = document.getElementById('severityDoughnut').getContext('2d');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Critical', 'Major', 'Minor', 'Low'],
                datasets: [{
                    data: [
                        {{ $severityPie['Critical'] }},
                        {{ $severityPie['Major'] }},
                        {{ $severityPie['Minor'] }},
                        {{ $severityPie['Low'] }}
                    ],
                    backgroundColor: ['#e11d48','#ea580c','#3b82f6','#10b981'],
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleFont: { size: 11 },
                        bodyFont: { size: 11 },
                        padding: 10,
                        cornerRadius: 8
                    }
                }
            }
        });
    })();
</script>
@endpush
