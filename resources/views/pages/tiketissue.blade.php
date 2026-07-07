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
                                data-bs-target="#basicModal">Tambah Data</button>
                            <button type="button" data-bs-toggle="modal" class="btn btn-secondary waves-effect waves-light ms-2"
                                data-bs-target="#exportmodal">Export / Import</button>
                            <button type="button" class="btn btn-secondary waves-effect waves-light ms-2"
                                data-nama="{{ $nop->nama_nop }}" onclick="confirmDeleteNop(this)">Hapus Data
                                {{ $nop->nama_nop }}</button>
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
                                        <div class="card-subtitle-custom">Total incident NOP {{ $nop->nama_nop }} berdasarkan waktu</div>
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
                                        <div class="card-title-custom">Data Incident Cluster TO</div>
                                        <div class="card-subtitle-custom">Berdasarkan pembagian Cluster TO</div>
                                    </div>
                                    <div class="card-body pt-2 d-flex flex-column">
                                        <div style="position:relative; height:220px; margin-bottom: 1rem;">
                                            <canvas id="clusterPieChart"></canvas>
                                        </div>
                                        @php $grandTotalCluster = $totaltiket ?: 1; @endphp
                                        <div style="max-height: 240px; overflow-y: auto; padding-right: 5px;">
                                            @foreach($clusters as $idx => $clusterName)
                                            <div class="sev-row">
                                                <span class="sev-dot" style="background:{{ $clusterColors[$idx] ?? '#ccc' }};"></span>
                                                <span class="sev-label" style="min-width: 60px;">{{ $clusterName }}</span>
                                                <div class="sev-bar-wrap">
                                                    <div class="sev-bar" style="background:{{ $clusterColors[$idx] ?? '#ccc' }};width:{{ round(($clusterTotals[$idx] ?? 0)/$grandTotalCluster*100) }}%;"></div>
                                                </div>
                                                <span class="sev-count">{{ $clusterTotals[$idx] ?? 0 }}</span>
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
                                class="d-flex gap-2 d-none"> <!-- <-- tambah d-none di sini -->
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
@endsection
@push('modal')
    {{-- Modern Modal Styles --}}
    <style>
        #basicModal .modal-content {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            background: #fff;
        }
        #basicModal .modal-header {
            background: #f8f9fa;
            border-bottom: 1px solid #e5e7eb;
            padding: 1.25rem 1.75rem;
            position: relative;
        }
        #basicModal .modal-title {
            color: #111827;
            font-weight: 700;
            font-size: 1.15rem;
            letter-spacing: 0.3px;
        }
        #basicModal .modal-header .btn-close {
            opacity: 0.85;
            transition: opacity 0.2s, transform 0.2s;
        }
        #basicModal .modal-header .btn-close:hover {
            opacity: 1;
            transform: rotate(90deg);
        }
        #basicModal .modal-body {
            padding: 1.5rem 1.75rem;
            max-height: 65vh;
            overflow-y: auto;
        }
        #basicModal .modal-body::-webkit-scrollbar {
            width: 5px;
        }
        #basicModal .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        #basicModal .modal-body::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        #basicModal .modal-footer {
            border-top: 1px solid #f0f0f0;
            padding: 1rem 1.75rem 1.5rem;
            gap: 0.75rem;
        }
        #basicModal .modern-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #4b5563;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.4rem;
            display: block;
        }
        #basicModal .modern-input,
        #basicModal .modern-select {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 0.55rem 0.9rem;
            font-size: 0.9rem;
            color: #111827;
            transition: border-color 0.2s, box-shadow 0.2s;
            width: 100%;
            outline: none;
            background: #fff;
        }
        #basicModal .modern-input:focus,
        #basicModal .modern-select:focus {
            border-color: #6b7280;
            box-shadow: 0 0 0 3px rgba(107,114,128,0.1);
        }
        #basicModal .modern-input::placeholder {
            color: #9ca3af;
        }
        #basicModal .modern-input:disabled {
            background: #f3f4f6;
            color: #9ca3af;
            cursor: not-allowed;
        }
        #basicModal .section-divider {
            font-size: 0.75rem;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 1.25rem 0 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        #basicModal .section-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }
        #basicModal .btn-modern-cancel {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 0.5rem 1.2rem;
            font-weight: 600;
            transition: all 0.2s;
            cursor: pointer;
        }
        #basicModal .btn-modern-cancel:hover {
            background: #e5e7eb;
        }
        #basicModal .btn-modern-primary {
            background: #1f2937;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1.4rem;
            font-weight: 600;
            transition: all 0.25s;
            cursor: pointer;
        }
        #basicModal .btn-modern-primary:hover {
            background: #111827;
        }
    </style>

    <div class="modal fade" id="basicModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel1">
                        <span style="margin-right:0.5rem;"></span> Create Ticket
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('tiket.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div id="tiket-container">
                            <div class="tiket-row mb-3 border rounded">
                                <div class="tiket-header p-3 bg-light d-flex justify-content-between align-items-center" style="cursor: pointer; border-radius: 10px 10px 0 0;">
                                    <h6 class="mb-0 tiket-title font-weight-bold">Tiket 1</h6>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-modern-cancel btn-remove-row d-none me-2" style="padding: 0.2rem 0.6rem; font-size: 0.7rem;"><i class="bx bx-trash"></i> Hapus</button>
                                        <i class="bx bx-chevron-up toggle-icon" style="font-size: 1.2rem;"></i>
                                    </div>
                                </div>
                                <div class="tiket-body p-3">
                                    <div class="section-divider mt-0">Informasi Site</div>
                                    <div class="mb-3">
                                        <label class="modern-label">Site ID</label>
                                        <input type="text" name="site_id[]" class="modern-input site_id"
                                            placeholder="Ketik Site ID Terlebih dahulu">
                                    </div>
                                    <div class="mb-3">
                                        <select name="site_id_select[]" class="modern-select site_id_select">
                                        </select>
                                    </div>
                                    <div class="row">
                                        <div class="col-6 mb-3">
                                            <label class="modern-label">NOP</label>
                                            <input type="text" name="nop[]" class="modern-input nop" placeholder="-" disabled>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <label class="modern-label">Cluster To</label>
                                            <input type="text" name="cluster_to[]" class="modern-input cluster_to" placeholder="-" disabled>
                                        </div>
                                    </div>

                                    <div class="section-divider">Status & Severity</div>
                                    <div class="row">
                                        <div class="col-6 mb-3">
                                            <label class="modern-label">Saverity</label>
                                            <select name="saverity[]" class="modern-select saverity">
                                                <option value="Low">Low</option>
                                                <option value="Minor">Minor</option>
                                                <option value="Critical">Critical</option>
                                            </select>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <label class="modern-label">Status Site</label>
                                            <select name="status_site[]" class="modern-select status_site">
                                                <option value="Down">Down</option>
                                                <option value="Up">Up</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="modern-label">Status Ticket</label>
                                        <select name="status_ticket[]" class="modern-select status_ticket">
                                            <option value="Open" selected>Open</option>
                                            <option value="Close">Close</option>
                                        </select>
                                    </div>

                                    <div class="section-divider">Detail Tiket</div>
                                    <div class="mb-3">
                                        <label class="modern-label">Time Down</label>
                                        <input type="datetime-local" name="time_down[]" class="modern-input time_down" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="modern-label">Tim Fop</label>
                                        <input type="text" name="tim_fop[]" class="modern-input tim_fop" placeholder="Nama Tim FOP">
                                    </div>
                                    <div class="mb-3">
                                        <label class="modern-label">Remark</label>
                                        <input type="text" name="remark[]" class="modern-input remark" placeholder="Catatan tambahan">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-center mt-3">
                            <button type="button" class="btn-modern-cancel btn-sm" id="btn-add-row"><i class="bx bx-plus"></i> Tambah Baris</button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-modern-cancel" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn-modern-primary">Simpan Tiket</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="exportmodal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel1">Export</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('tiket.export', ['id' => $nop->nama_nop]) }}" method="GET" id="exportForm">
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
@endpush
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

            window.confirmDeleteNop = function(e) {
                let id = e.getAttribute('data-nama');

                Swal.fire({
                    title: "Are you sure?",
                    text: `Data dengan NOP ${id} akan dihapus semua dan tidak dapat dikembalikan!`,
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, delete it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            type: 'POST',
                            url: `/daftartiketnop/${id}`,
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

            $('select.site_id_select').html(
                '<option value="">Masukkan Site ID terlebih dahulu</option>'
            );

            // Handle Tambah Baris
            $('#btn-add-row').on('click', function() {
                // Tutup baris yang sudah ada
                $('.tiket-body').slideUp();
                $('.toggle-icon').removeClass('bx-chevron-up').addClass('bx-chevron-down');

                let firstRow = $('.tiket-row').first();
                let newRow = firstRow.clone();
                
                // Clear inputs
                newRow.find('input').not('[type="hidden"]').val('');
                newRow.find('.nop, .cluster_to').prop('disabled', true);
                
                // Reset select options
                newRow.find('.site_id_select').html('<option value="">Masukkan Site ID terlebih dahulu</option>');
                newRow.find('.saverity').val('Low');
                newRow.find('.status_site').val('Down');
                newRow.find('.status_ticket').val('Open');
                
                // Show delete button
                newRow.find('.btn-remove-row').removeClass('d-none');

                // Buka baris yang baru
                newRow.find('.tiket-body').show();
                newRow.find('.toggle-icon').removeClass('bx-chevron-down').addClass('bx-chevron-up');
                
                // Update nomor tiket
                let rowCount = $('.tiket-row').length + 1;
                newRow.find('.tiket-title').text('Tiket ' + rowCount);
                
                $('#tiket-container').append(newRow);
            });

            // Handle Hapus Baris
            $(document).on('click', '.btn-remove-row', function(e) {
                e.stopPropagation(); // Mencegah agar klik Hapus tidak membuka/menutup accordion
                $(this).closest('.tiket-row').remove();
                
                // Update urutan nomor tiket
                $('.tiket-row').each(function(index) {
                    $(this).find('.tiket-title').text('Tiket ' + (index + 1));
                });
            });

            // Handle Accordion Toggle
            $(document).on('click', '.tiket-header', function() {
                let body = $(this).siblings('.tiket-body');
                let icon = $(this).find('.toggle-icon');
                
                body.slideToggle(300);
                if(icon.hasClass('bx-chevron-up')) {
                    icon.removeClass('bx-chevron-up').addClass('bx-chevron-down');
                } else {
                    icon.removeClass('bx-chevron-down').addClass('bx-chevron-up');
                }
            });

            $(document).on('change', 'input.site_id', function() {
                let val = $(this).val();
                let row = $(this).closest('.tiket-row');
                let selectTarget = row.find('select.site_id_select');

                if (val) {
                    let initialOptionHTML = '<option value="">Pilih Site ID</option>';

                    $.ajax({
                        type: 'GET',
                        url: '{{ route('api.dapot.searchBySiteID') }}',
                        data: {
                            "site_id": val
                        },
                        success: function(data) {
                            let optionHTML = initialOptionHTML;

                            if (data.success) {
                                data.payload.forEach(e => {
                                    optionHTML +=
                                        `<option value="${e.site_id}">${e.site_id} - ${e.site_name}</option>`;
                                });
                            }

                            selectTarget.html(optionHTML);
                        }
                    });
                } else {
                    selectTarget.html(
                        '<option value="">Masukkan Site ID terlebih dahulu</option>'
                    );
                }
            });

            $(document).on('change', 'select.site_id_select', function() {
                let val = $(this).val();
                let row = $(this).closest('.tiket-row');

                if (val) {
                    $.ajax({
                        type: 'GET',
                        url: '{{ route('api.dapot.findBySiteID') }}',
                        data: {
                            "site_id": val
                        },
                        success: function(data) {
                            row.find('input.nop').val(data.payload.nop);
                            row.find('input.cluster_to').val(data.payload.cluster_to);
                        }
                    });
                }
            });
        });

        flatpickr("#flatpickr-datetime", {
            enableTime: true,
            dateFormat: "d-m-Y H:i:S",
            time_24hr: true
        });
        flatpickr("#flatpickr-time", {
            enableTime: true,
            noCalendar: true, // ⬅️ hanya tampilkan jam, tidak tanggal
            dateFormat: "H:i", // ⬅️ format jam dan menit (24 jam)
            time_24hr: true,
            defaultHour: 12, // opsional: default waktu
            defaultMinute: 0, // opsional: default menit
            allowInput: true // opsional: bisa edit manual
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

            // Bind updateSelected to individual checkboxes
            function bindCheckboxEvents() {
                const checkboxes = document.querySelectorAll('.row-checkbox');
                checkboxes.forEach(cb => {
                    cb.removeEventListener('change', updateSelected); // avoid duplicate
                    cb.addEventListener('change', updateSelected);
                });
            }

            // Select All checkbox logic
            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('.row-checkbox');
                    checkboxes.forEach(cb => cb.checked = this.checked);
                    updateSelected();
                });
            }

            // Initial bind
            bindCheckboxEvents();

            // Rebind after DataTables redraws
            $('#tiket-table').on('draw.dt', function() {
                bindCheckboxEvents();
            });
        });

        flatpickr("#bs-datepicker-autoclose", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            time_24hr: true,
            allowInput: true,


        });


        //LOCK
        $(document).on('click', '.lock-btn', function() {
            var tiketId = $(this).data('id');
            var lockStatus = $(this).text() == 'Lock' ? 1 : 0; // Tentukan status baru berdasarkan teks tombol

            // Update only the Lock button text
            $.ajax({
                url: '/tiket/lock/' + tiketId, // Route untuk mengupdate status lock
                type: 'PUT',
                data: {
                    _token: '{{ csrf_token() }}',
                    lock: lockStatus,
                },
                success: function(response) {
                    if (response.success) {
                        // Update teks tombol Lock dan Unlock
                        var newStatus = lockStatus == 1 ? 'Unlock' : 'Lock';
                        var lockButton = $('[data-id="' + tiketId +
                            '"].lock-btn'); // Target hanya tombol Lock
                        lockButton.text(newStatus);

                        // Optionally, you can add a class to change the button color to indicate its status
                        if (lockStatus == 1) {
                            lockButton.removeClass('btn-dark').addClass('btn-dark');
                        } else {
                            lockButton.removeClass('btn-dark').addClass('btn-dark');
                        }
                    }
                },
                error: function() {
                    alert('Terjadi kesalahan saat mengubah status lock!');
                }
            });
        });


        $(document).on('click', '#delete-all', function() {
            $.ajax({
                url: '/tiket/destroyall',
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}',
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Semua Data Berhasil Dihapus',
                            text: response.message,
                        }).then(() => {
                            location.reload(); // Reload halaman setelah sukses
                        });
                    }
                },
                error: function(xhr) {
                    var response = xhr.responseJSON;
                    if (response && response.lock) {
                        // Jika ada tiket yang terkunci, tampilkan pesan error
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Hapus Semua',
                            text: response.message,
                        });
                    } else {
                        // Pesan error umum jika ada masalah lainnya
                        Swal.fire({
                            icon: 'error',
                            title: 'Terjadi Kesalahan',
                            text: 'Gagal menghapus data.',
                        });
                    }
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
                                label: 'Total Tiket',
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

        // ======= PIE CHART: Data Cluster TO =======
        (function() {
            const ctx = document.getElementById('clusterPieChart');
            if(ctx) {
                new Chart(ctx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: {!! json_encode($clusters ?? []) !!},
                        datasets: [{
                            data: {!! json_encode($clusterTotals ?? []) !!},
                            backgroundColor: {!! json_encode($clusterColors ?? []) !!},
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
    </script>
@endpush
