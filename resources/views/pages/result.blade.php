@extends('layouts.layout-excel')

@section('content')
    <div class="flex-grow-1 container-p-y" style="width: 100vw; padding: 0px 30px;">
        <div class="row g-6">
            <div class="container mt-4">
                <!-- Header -->
                <div class="row text-center p-3 bg-success text-white fw-bold rounded-top shadow-sm mb-2">
                    <h4>UPDATE INFO SITE DOWN NOP {{ $namanop }} {{ $jam }}</h4>
                    <div class="col">Piket 1: {{ $piket1 }}</div>
                    <div class="col">Piket 2: {{ $piket2 }}</div>
                    <div class="col">Reporting : -</div>
                </div>
                <!-- Table Section -->
                <div class="mt-3 row">
                    @foreach ($tikets as $cluster_to => $dataGroup)
                        <div
                            class="fw-bold text-white bg-warning p-2 text-center text-uppercase rounded-top shadow-sm mb-0">
                            {{ str_replace('_', ' ', $cluster_to) }}
                        </div>
                        <table class="table table-bordered text-center mb-5 shadow-sm">
                            <thead class="table-primary">
                                <tr>
                                    <th>No</th>
                                    <th>Site Name</th>
                                    <th>Suspect Problem</th>
                                    <th>Time Down</th>
                                    <th>Waktu Saat Ini</th>
                                    <th>Durasi</th>
                                    <th>Status Site</th>
                                    <th>Tim FOP</th>
                                    <th>Remark</th>
                                    <th>Ticket SWFM</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($dataGroup as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $item->site_id }}</td>
                                        <td>{{ $item->suspect_problem }}</td>
                                        <td>
                                            @php
                                                if ($item->time_down) {
                                                    $excel_time_down = floatval($item->time_down);
                                                    $days_since_1900 = floor($excel_time_down);
                                                    $time_fraction = $excel_time_down - $days_since_1900;
                                                    $seconds_in_day = 24 * 60 * 60;
                                                    $time_seconds = round($time_fraction * $seconds_in_day);

                                                    $excel_date = \Carbon\Carbon::parse('1899-12-30')->addDays(
                                                        $days_since_1900,
                                                    );
                                                    $time_down_datetime = $excel_date
                                                        ->copy()
                                                        ->addSeconds($time_seconds);

                                                    echo $time_down_datetime->format('d-m-Y H:i:s');
                                                } else {
                                                    echo '-';
                                                }
                                            @endphp
                                        </td>
                                        <td>
                                            {{ $time }}
                                        </td>
                                        <td>
                                            @php
                                                if ($item->time_down && $time) {
                                                    $excel_time_down = floatval($item->time_down);
                                                    $days_since_1900 = floor($excel_time_down);
                                                    $time_fraction = $excel_time_down - $days_since_1900;
                                                    $seconds_in_day = 24 * 60 * 60;
                                                    $time_seconds = round($time_fraction * $seconds_in_day);

                                                    $time_down = \Carbon\Carbon::parse('1899-12-30')
                                                        ->addDays($days_since_1900)
                                                        ->addSeconds($time_seconds);

                                                    $diffInSeconds = $time_down->diffInSeconds($time);

                                                    $days = floor($diffInSeconds / 86400);
                                                    $hours = floor(($diffInSeconds % 86400) / 3600);
                                                    $minutes = floor(($diffInSeconds % 3600) / 60);
                                                    $seconds = $diffInSeconds % 60;

                                                    $durasi = '';
                                                    if ($days > 0) {
                                                        $durasi .= $days . ' hari ';
                                                    }
                                                    if ($hours > 0) {
                                                        $durasi .= $hours . ' jam ';
                                                    }
                                                    if ($minutes > 0) {
                                                        $durasi .= $minutes . ' menit ';
                                                    }
                                                    if ($durasi === '') {
                                                        $durasi = $seconds . ' detik';
                                                    }

                                                    echo trim($durasi);
                                                } else {
                                                    echo '-';
                                                }
                                            @endphp
                                        </td>
                                        <td
                                            @if (strtolower($item->status_site) === 'down') style="background-color: #dc3545; color: #fff; font-weight: bold;"
    @elseif(strtolower($item->status_site) === 'up')
        style="background-color: #198754; color: #fff; font-weight: bold;" @endif>
                                            {{ $item->status_site }}
                                        </td>
                                        <td>{{ $item->tim_fop }}</td>
                                        <td>{{ $item->remark }}</td>
                                        <td>{{ $item->ticket_swfm }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10">Tidak ada data untuk {{ $cluster_to }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
