@extends('layouts.layout')

@section('content')
    <style>
        body.screenshot-chart .layout-menu, body.screenshot-table .layout-menu {
            display: none !important;
        }
        body.screenshot-chart .layout-navbar, body.screenshot-table .layout-navbar {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            left: 0 !important;
            right: 0 !important;
            border-radius: 0 !important;
            padding-left: 2rem !important;
            padding-right: 2rem !important;
        }
        body.screenshot-chart .layout-page, body.screenshot-table .layout-page {
            padding-left: 0 !important;
            margin-left: 0 !important;
        }
        body.screenshot-chart .action-buttons-container, body.screenshot-table .action-buttons-container {
            display: none !important;
        }
        body.screenshot-chart footer, body.screenshot-table footer {
            display: none !important;
        }
        body.screenshot-chart #datatable-container { display: none !important; }
        body.screenshot-table #chart-container { display: none !important; }
        body.screenshot-table table.dataTable th:last-child, 
        body.screenshot-table table.dataTable td:last-child { display: none !important; }

        .swal-z-top {
            z-index: 99999 !important;
        }

        /* ========== Dashboard Custom Styles for Charts ========== */
        .chart-card {
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 6px rgba(0,0,0,0.05);
            background: #fff;
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

        /* ---- Custom Legend Pills ---- */
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
    </style>
    <div class="flex-grow-1 container-p-y container-xxxl px-3 ">
        <div class="row g-6">
            <div class="container mt-10">
                <div class="card" style="overflow-x: scroll;">
                    <div class="card-header">
                        <div class="col-12 mb-4 action-buttons-container d-flex align-items-center">
                            <button type="button" data-bs-toggle="modal" class="btn btn-secondary waves-effect waves-light"
                                data-bs-target="#importModal">Import Ticket SWFM</button>
                            <button type="button" data-bs-toggle="modal" class="btn btn-secondary waves-effect waves-light ms-2"
                                data-bs-target="#importAlarmModal">Import Alarm</button>
                            <button type="button" data-bs-toggle="modal" class="btn btn-secondary waves-effect waves-light ms-2"
                                data-bs-target="#exportmodal">Export</button>
                            <button type="button" class="btn btn-secondary waves-effect waves-light ms-2"
                                onclick="confirmDeleteAll(this)">Hapus Semua Data
                            </button>
                            <button type="button" class="btn bg-transparent border-0 shadow-none p-0 ms-auto"
                                id="btn-screenshot" title="Screenshot">
                                <i class="ti ti-camera fs-3 text-dark" style="color: #000 !important;"></i>
                            </button>
                        </div>
                        
                        {{-- Chart Section (di bawah tombol) --}}
                        <div class="row w-100 m-0 mt-4" id="chart-container">
                            <div class="col-12 col-lg-8 mb-4">
                                <div class="chart-card h-100">
                                    <div class="card-header-custom">
                                        <div class="card-title-custom">Distribusi Incident per Jam</div>
                                        <div class="card-subtitle-custom">Total incident JATIM berdasarkan waktu</div>
                                    </div>
                                    <div class="card-body pt-2 d-flex flex-column">
                                        <div style="position:relative; flex-grow: 1; min-height: 380px;">
                                            <canvas id="tiketIssueChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-4 mb-4">
                                <div class="chart-card h-100">
                                    <div class="card-header-custom">
                                        <div class="card-title-custom">Data Incident JATIM (NOP)</div>
                                        <div class="card-subtitle-custom">Berdasarkan pembagian NOP</div>
                                    </div>
                                    <div class="card-body pt-2 d-flex flex-column">
                                        <div style="position:relative; height:220px; margin-bottom: 1rem;">
                                            <canvas id="nopPieChart"></canvas>
                                        </div>
                                        @php $grandTotalNOP = $totaltiket ?: 1; @endphp
                                        <div style="max-height: 240px; overflow-y: auto; padding-right: 5px;">
                                            @foreach($nops as $idx => $nopName)
                                            <div class="sev-row">
                                                <span class="sev-dot" style="background:{{ $nopColors[$idx] ?? '#ccc' }};"></span>
                                                <span class="sev-label" style="min-width: 60px;">{{ $nopName }}</span>
                                                <div class="sev-bar-wrap">
                                                    <div class="sev-bar" style="background:{{ $nopColors[$idx] ?? '#ccc' }};width:{{ round(($nopTotals[$idx] ?? 0)/$grandTotalNOP*100) }}%;"></div>
                                                </div>
                                                <span class="sev-count">{{ $nopTotals[$idx] ?? 0 }}</span>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body" id="datatable-container">
                        {{ $dataTable->table() }}
                        <div class="d-flex justify-content-end mt-3 pe-3">
                            <form id="bulk-edit-form" method="POST" action="{{ route('tiket.bulk-update') }}"
                                class="d-flex gap-2 d-none">
                                @csrf
                                <div class="col-auto">
                                    <input type="text" name="tim_fop" class="form-control"
                                        placeholder="Masukkan Tim FOP Baru">
                                </div>
                                <div class="col-auto pe-3">
                                    <input type="text" name="remark" class="form-control"
                                        placeholder="Masukkan Remark Baru">
                                </div>
                                <div class="col-auto">
                                    <select name="status_ticket" class="form-control">
                                        <option value="Open">Open</option>
                                        <option value="Close">Close</option>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <input type="hidden" name="selected_ids" id="selected-ids">
                                    <button type="submit" class="btn btn-secondary">Update Terpilih</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form class="modal-content" action="{{ route('tiket.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel1">Import Tiket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="formFile" class="form-label">Default file input example</label>
                        <input class="form-control" type="file" id="file" name="file" accept=".csv, .xls, .xlsx" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="importAlarmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form class="modal-content" action="{{ route('tiket.importAlarm') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="importAlarmModalLabel">Import Alarm</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="alarmFile" class="form-label">Upload File Excel Alarm (mulai baris 6)</label>
                        <input class="form-control" type="file" id="alarmFile" name="file" accept=".csv, .xls, .xlsx" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-info">Import Alarm</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="exportmodal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel1">Export</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('tiket.export', ['id' => 'JATIM']) }}" method="GET" id="exportForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12 mb-4">
                                <label for="jam" class="form-label">Waktu Time Down</label>
                                <input type="text" class="form-control flatpickr-input" placeholder="HH:MM"
                                    id="flatpickr-time" name="flatpickr-time" readonly="readonly">
                            </div>
                            <div class="col-12 mb-4">
                                <label for="piket1" class="form-label">Piket OMC 1</label>
                                <input type="text" id="piket1" name="piket1" class="form-control"
                                    placeholder="">
                            </div>
                            <div class="col-12 mb-4">
                                <label for="piket2" class="form-label">Piket OMC 2</label>
                                <input type="text" id="piket2" name="piket2" class="form-control"
                                    placeholder="">
                            </div>
                            <div class="col-12 mb-4">
                                <label for="flatpickr-datetime" class="form-label">Waktu Saat Ini</label>
                                <input type="text" class="form-control flatpickr-input" placeholder="YYYY-MM-DD HH:MM"
                                    id="flatpickr-datetime" name="flatpickr-datetime" readonly="readonly">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{ $dataTable->scripts(attributes: ['type' => 'module']) }}
    <script>
        $(document).ready(function() {
            let currentScreenshotMode = '';

            $('#btn-screenshot').on('click', function() {
                currentScreenshotMode = 'screenshot-chart';
                enterFullscreen();
            });

            $(document).on('init.dt', function(e, settings) {
                let btnHtml = '<button type="button" class="btn btn-sm bg-transparent shadow-none" id="btn-screenshot-table" title="Screenshot Data Table" style="margin-left:10px;"><i class="ti ti-camera fs-4 text-dark" style="color: #000 !important;"></i></button>';
                $('.dataTables_filter').append(btnHtml);
            });

            $(document).on('click', '#btn-screenshot-table', function() {
                currentScreenshotMode = 'screenshot-table';
                enterFullscreen();
            });

            function enterFullscreen() {
                var elem = document.documentElement;
                if (elem.requestFullscreen) {
                    elem.requestFullscreen();
                } else if (elem.webkitRequestFullscreen) { /* Safari */
                    elem.webkitRequestFullscreen();
                } else if (elem.msRequestFullscreen) { /* IE11 */
                    elem.msRequestFullscreen();
                }
            }

            $(document).on('fullscreenchange webkitfullscreenchange msfullscreenchange', function() {
                if (document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement) {
                    if (currentScreenshotMode) {
                        $('body').addClass(currentScreenshotMode);
                    }
                } else {
                    $('body').removeClass('screenshot-chart screenshot-table');
                    currentScreenshotMode = '';
                }
            });

            window.confirmDeleteAll = function(e) {
                Swal.fire({
                    title: "Are you sure?",
                    text: "Semua data JATIM akan dihapus dan tidak dapat dikembalikan!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, delete it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            type: 'DELETE',
                            url: `/tiket/deleteall`,
                            data: {
                                "_token": "{{ csrf_token() }}"
                            },
                            success: function(data) {
                                if (data.success) {
                                    Swal.fire({
                                        title: "Deleted!",
                                        text: "Your data has been deleted.",
                                        icon: "success"
                                    });
                                    $('#tiket-table').DataTable().ajax.reload(null, false);
                                }
                            },
                            error: function(xhr) {
                                var response = xhr.responseJSON;
                                if (response && response.lock) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Gagal Hapus Semua',
                                        text: response.message,
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Terjadi Kesalahan',
                                        text: 'Gagal menghapus data.',
                                    });
                                }
                            }
                        });
                    }
                });
            }

            window.confirmDelete = function(e) {
                let id = e.getAttribute('data-id');
                Swal.fire({
                    title: "Are you sure?",
                    text: "You won't be able to revert this!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, delete it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            type: 'POST',
                            url: `/tiket/${id}`,
                            data: {
                                "_token": "{{ csrf_token() }}",
                                "_method": 'DELETE',
                            },
                            success: function(data) {
                                if (data.success) {
                                    Swal.fire({
                                        title: "Deleted!",
                                        text: "Your file has been deleted.",
                                        icon: "success"
                                    });
                                    $('#tiket-table').DataTable().ajax.reload(null, false);
                                }
                            }
                        });
                    }
                });
            }
        });

        flatpickr("#flatpickr-datetime", {
            enableTime: true,
            dateFormat: "d-m-Y H:i:S",
            time_24hr: true
        });
        flatpickr("#flatpickr-time", {
            enableTime: true,
            noCalendar: true, 
            dateFormat: "H:i", 
            time_24hr: true,
            defaultHour: 12, 
            defaultMinute: 0, 
            allowInput: true 
        });

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('bulk-edit-form');
            const selectedIdsInput = document.getElementById('selected-ids');
            const selectAll = document.getElementById('select-all');

            function updateSelected() {
                const checkboxes = document.querySelectorAll('.row-checkbox');
                const selected = Array.from(checkboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);

                selectedIdsInput.value = selected.join(',');

                if (selected.length > 0) {
                    form.classList.remove('d-none');
                } else {
                    form.classList.add('d-none');
                }
            }

            function bindCheckboxEvents() {
                const checkboxes = document.querySelectorAll('.row-checkbox');
                checkboxes.forEach(cb => {
                    cb.removeEventListener('change', updateSelected);
                    cb.addEventListener('change', updateSelected);
                });
            }

            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('.row-checkbox');
                    checkboxes.forEach(cb => cb.checked = this.checked);
                    updateSelected();
                });
            }

            bindCheckboxEvents();

            $('#tiket-table').on('draw.dt', function() {
                bindCheckboxEvents();
            });
        });

        $(document).on('click', '.lock-btn', function() {
            var tiketId = $(this).data('id');
            var lockStatus = $(this).text() == 'Lock' ? 1 : 0; 

            $.ajax({
                url: '/tiket/lock/' + tiketId, 
                type: 'PUT',
                data: {
                    _token: '{{ csrf_token() }}',
                    lock: lockStatus,
                },
                success: function(response) {
                    if (response.success) {
                        var newStatus = lockStatus == 1 ? 'Unlock' : 'Lock';
                        var lockButton = $('[data-id="' + tiketId + '"].lock-btn'); 
                        lockButton.text(newStatus);
                    }
                },
                error: function() {
                    alert('Terjadi kesalahan saat mengubah status lock!');
                }
            });
        });

        // ======= LINE CHART: Distribusi Tiket per Jam (Total) =======
        (function() {
            const ctx = document.getElementById('tiketIssueChart');
            if(ctx) {
                const customDataLabels = {
                    id: 'customDataLabels',
                    afterDatasetsDraw(chart, args, pluginOptions) {
                        const { ctx, data } = chart;
                        ctx.save();
                        data.datasets.forEach((dataset, i) => {
                            const meta = chart.getDatasetMeta(i);
                            if (meta.hidden) return;
                            meta.data.forEach((element, index) => {
                                const value = dataset.data[index];
                                if (value > 0) {
                                    ctx.fillStyle = '#3b82f6';
                                    ctx.font = 'bold 12px sans-serif';
                                    ctx.textAlign = 'center';
                                    ctx.textBaseline = 'bottom';
                                    ctx.fillText(value, element.x, element.y - 8);
                                }
                            });
                        });
                        ctx.restore();
                    }
                };

                new Chart(ctx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: {!! json_encode($hours ?? []) !!},
                        datasets: [
                            {
                                label: 'Total Incident (JATIM)',
                                data: {!! json_encode($chartData ?? []) !!},
                                borderColor: '#3b82f6',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                fill: true,
                                tension: 0.4,
                                borderWidth: 2,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                pointBackgroundColor: '#3b82f6'
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
                                ticks: { font: { size: 10 }, stepSize: 1, precision: 0 },
                                grid: { color: '#f1f5f9' }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { font: { size: 10 } }
                            }
                        }
                    },
                    plugins: [customDataLabels]
                });
            }
        })();

        // ======= PIE CHART: Data NOP =======
        (function() {
            const ctx = document.getElementById('nopPieChart');
            if(ctx) {
                new Chart(ctx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: {!! json_encode($nops ?? []) !!},
                        datasets: [{
                            data: {!! json_encode($nopTotals ?? []) !!},
                            backgroundColor: {!! json_encode($nopColors ?? []) !!},
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
            }
        })();

        // ======= Loader on Import =======
        $('#importModal form, #importAlarmModal form').on('submit', function(e) {
            e.preventDefault();
            
            let $form = $(this);
            let $btn = $form.find('button[type="submit"]');

            if (!this.checkValidity()) {
                return;
            }

            // Disable button to prevent double submit
            $btn.prop('disabled', true);
            
            // Sembunyikan modal agar loading terkesan lebih bersih
            $form.closest('.modal').modal('hide');

            Swal.fire({
                title: 'Sedang Memproses Data...',
                html: 'Mohon tunggu sebentar, file Excel sedang diimport.',
                allowOutsideClick: false,
                customClass: {
                    container: 'swal-z-top'
                },
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            let formData = new FormData(this);

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if(response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Import Berhasil!',
                            text: response.message || 'Data berhasil diimport. Halaman akan dimuat ulang...',
                            timer: 2500,
                            showConfirmButton: false,
                            allowOutsideClick: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Import Gagal',
                            text: response.message || 'Gagal mengimport data.'
                        });
                        $btn.prop('disabled', false);
                    }
                },
                error: function(xhr) {
                    let errorMsg = 'Terjadi kesalahan saat import.';
                    if(xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Import Gagal',
                        text: errorMsg
                    });
                    $btn.prop('disabled', false);
                }
            });
        });

        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                Swal.close();
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
                $('#importModal form, #importAlarmModal form').trigger('reset');
                $('#importModal form, #importAlarmModal form').find('button[type="submit"]').prop('disabled', false);
            }
        });
    </script>
@endpush
