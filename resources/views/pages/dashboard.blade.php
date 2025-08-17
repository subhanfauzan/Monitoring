@extends('layouts.layout')

@section('content')
    <div class="flex-grow-1 container-p-y container-xxxl px-3">
        <div class="row g-6">
            <div class="container mt-10">
                <div class="card" style="">
                    <div class="flex-grow-1 container-p-y container-xxxl px-3">
                        <div class="row g-6">

                            <div class="col-lg-3 col-sm-6">
                                <div class="card card-border-shadow-primary h-100">
                                    <div class="card-body rounded"
                                        style="background: radial-gradient(circle at 0% 0%, #00adef, #3871c1);">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="avatar me-4">
                                                <span class="avatar-initial rounded bg-gray-400">
                                                    <i class='ti ti-truck ti-28px'></i>
                                                </span>
                                            </div>
                                            <h4 class="mb-0">42</h4>
                                        </div>
                                        <p class="mb-1">On route vehicles</p>
                                        <p class="mb-0">
                                            <span class="text-heading fw-medium me-2">+18.2%</span>
                                            <small class="text-muted">than last week</small>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-sm-6">
                                <div class="card card-border-shadow-warning h-100">
                                    <div class="card-body rounded"
                                        style="background: radial-gradient(circle at 0% 0%, #00adef, #3871c1);">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="avatar me-4">
                                                <span class="avatar-initial rounded bg-label-warning">
                                                    <i class='ti ti-alert-triangle ti-28px'></i>
                                                </span>
                                            </div>
                                            <h4 class="mb-0">8</h4>
                                        </div>
                                        <p class="mb-1">Vehicles with errors</p>
                                        <p class="mb-0">
                                            <span class="text-heading fw-medium me-2">-8.7%</span>
                                            <small class="text-muted">than last week</small>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-sm-6">
                                <div class="card card-border-shadow-danger h-100">
                                    <div class="card-body rounded"
                                        style="background: radial-gradient(circle at 0% 0%, #00adef, #3871c1);">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="avatar me-4">
                                                <span class="avatar-initial rounded bg-label-danger">
                                                    <i class='ti ti-git-fork ti-28px'></i>
                                                </span>
                                            </div>
                                            <h4 class="mb-0">27</h4>
                                        </div>
                                        <p class="mb-1">Deviated from route</p>
                                        <p class="mb-0">
                                            <span class="text-heading fw-medium me-2">+4.3%</span>
                                            <small class="text-muted">than last week</small>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-sm-6">
                                <div class="card card-border-shadow-info h-100">
                                    <div class="card-body rounded"
                                        style="background: radial-gradient(circle at 0% 0%, #00adef, #3871c1);">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="avatar me-4">
                                                <span class="avatar-initial rounded bg-label-info">
                                                    <i class='ti ti-clock ti-28px'></i>
                                                </span>
                                            </div>
                                            <h4 class="mb-0">13</h4>
                                        </div>
                                        <p class="mb-1">Late vehicles</p>
                                        <p class="mb-0">
                                            <span class="text-heading fw-medium me-2">-2.5%</span>
                                            <small class="text-muted">than last week</small>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-4 align-items-stretch">
                                <div class="col-lg-4">
                                    <div class=" h-100">
                                        <div class="card-body">
                                            <ul class="p-0 m-0">
                                                <li class="d-flex mb-4">
                                                    <div class="avatar flex-shrink-0 me-4 avatar-lg">
                                                        <span class="avatar-initial rounded bg-label-secondary">
                                                            <i class="ti ti-clock ti-36px"></i>
                                                        </span>
                                                    </div>
                                                    <div class="d-flex w-100 flex-column">
                                                        <h6 class="mb-0 fw-bold fs-5">Total Site Down</h6>
                                                        <small class="text-danger fw-semibold fs-6">
                                                            <i class="ti ti-chevron-down me-1"></i>{{ $totaltiket }}
                                                        </small>
                                                    </div>
                                                </li>
                                                <li class="d-flex mb-4">
                                                    <div class="avatar flex-shrink-0 me-4 avatar-lg">
                                                        <span class="avatar-initial rounded bg-label-secondary">
                                                            <i class="ti ti-clock ti-36px"></i>
                                                        </span>
                                                    </div>
                                                    <div class="d-flex w-100 flex-column">
                                                        <h6 class="mb-0 fw-bold fs-5">Ticket Critical</h6>
                                                        <small class="text-danger fw-semibold fs-6">
                                                            <i class="ti ti-chevron-down me-1"></i>{{ $totalCritical }}
                                                        </small>
                                                    </div>
                                                </li>
                                                <li class="d-flex">
                                                    <div class="avatar flex-shrink-0 me-4 avatar-lg">
                                                        <span class="avatar-initial rounded bg-label-secondary">
                                                            <i class="ti ti-clock ti-36px"></i>
                                                        </span>
                                                    </div>
                                                    <div class="d-flex w-100 flex-column">
                                                        <h6 class="mb-0 fw-bold fs-5">Ticket Major</h6>
                                                        <small class="text-danger fw-semibold fs-6">
                                                            <i class="ti ti-chevron-down me-1"></i>{{ $totalMajor }}
                                                        </small>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-8">
                                    <div class=" h-100">
                                        <div class="card-body">
                                            <div style="position: relative; height: 400px; width: 100%;">
                                                <canvas id="severityLineChart" style="width: 100%; height: 100%;"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>


                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        const ctx = document.getElementById('severityLineChart').getContext('2d');

        const data = {
            labels: {!! json_encode($hours) !!},
            datasets: [{
                    label: 'PLATINUM',
                    data: {!! json_encode($chartData['PLATINUM']) !!},
                    borderColor: '#1f2f98',
                    backgroundColor: '#1f2f98',
                    fill: false,
                    tension: 0.4,
                    borderWidth: 3 // tebal garis
                },
                {
                    label: 'GOLD',
                    data: {!! json_encode($chartData['GOLD']) !!},
                    borderColor: '#f1c40f',
                    backgroundColor: '#f1c40f',
                    fill: false,
                    tension: 0.4,
                    borderWidth: 3
                },
                {
                    label: 'SILVER',
                    data: {!! json_encode($chartData['SILVER']) !!},
                    borderColor: '#7f8c8d',
                    backgroundColor: '#7f8c8d',
                    fill: false,
                    tension: 0.4,
                    borderWidth: 3
                },
                {
                    label: 'BRONZE',
                    data: {!! json_encode($chartData['BRONZE']) !!},
                    borderColor: '#e67e22',
                    backgroundColor: '#e67e22',
                    fill: false,
                    tension: 0.4,
                    borderWidth: 3
                }
            ]
        };

        const config = {
            type: 'line',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Distribusi Tiket per Jam Berdasarkan Site Class',
                        font: {
                            size: 18
                        }
                    },
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                size: 14
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 5,
                            font: {
                                size: 12
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    }
                },
                elements: {
                    line: {
                        borderWidth: 3
                    },
                    point: {
                        radius: 4,
                        hoverRadius: 6
                    }
                }
            }
        };

        new Chart(ctx, config);
    </script>
@endpush
