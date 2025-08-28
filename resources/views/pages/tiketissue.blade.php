@extends('layouts.layout')

@section('content')
    <div class="flex-grow-1 container-p-y container-xxxl px-3 ">
        <div class="row g-6">
            <div class="container mt-10">
                <div class="card" style="overflow-x: scroll;">
                    <div class="card-header">
                        <div class="col-6">
                            <button type="button" data-bs-toggle="modal" class="btn btn-secondary waves-effect waves-light"
                                data-bs-target="#basicModal">Tambah Data</button>
                            <button type="button" data-bs-toggle="modal" class="btn btn-secondary waves-effect waves-light"
                                data-bs-target="#exportmodal">Export</button>
                            <button type="button" class="btn btn-secondary waves-effect waves-light"
                                data-nama="{{ $nop->nama_nop }}" onclick="confirmDeleteNop(this)">Hapus Data
                                {{ $nop->nama_nop }}</button>
                        </div>
                    </div>
                    <div class="card-body">
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
    <div class="modal fade" id="basicModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel1">Create Ticket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('tiket.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12 mb-4">
                                <label for="site_id" class="form-label">Site ID</label>
                                <input type="text" id="site_id" name="site_id" class="form-control"
                                    placeholder="Ketik Site ID Terlebih dahulu">
                            </div>
                            <div class="col-12 mb-4">
                                <select id="site_id_select" name="site_id_select" class="form-control">
                                </select>
                            </div>
                            <div class="col-12 mb-4">
                                <label for="nop" class="form-label">Nop</label>
                                <input type="text" id="nop" name="nop" class="form-control" placeholder=""
                                    disabled>
                            </div>
                            <div class="col-12 mb-4">
                                <label for="cluster_to" class="form-label">Cluster To</label>
                                <input type="text" id="cluster_to" name="cluster_to" class="form-control" placeholder=""
                                    disabled>
                            </div>
                            <div class="col-12 mb-4">
                                <label for="saverity" class="form-label">Saverity</label>
                                <select id="saverity" name="saverity" class="form-control">
                                    <option value="Low">Low</option>
                                    <option value="Minor">Minor</option>
                                    <option value="Critical">Critical</option>
                                </select>
                            </div>
                            <div class="col-12 mb-4">
                                <label for="status_site" class="form-label">Status Site</label>
                                <select id="status_site" name="status_site" class="form-control">
                                    <option value="Down">Down</option>
                                    <option value="Up">Up</option>
                                </select>
                            </div>
                            <div class="col-12 mb-4">
                                <label for="status_ticket" class="form-label">Status Ticket</label>
                                <select id="status_ticket" name="status_ticket" class="form-control">
                                    <option value="Open" selected>Open</option>
                                    <option value="Close">Close</option>
                                </select>
                            </div>
                            <div class="col-12 mb-4">
                                <label for="time_down" class="form-label">Time Down</label>
                                <input type="datetime-local" id="time_down" name="time_down" class="form-control"
                                    required>
                            </div>
                            <div class="col-12 mb-4">
                                <label for="tim_fop" class="form-label">Tim Fop</label>
                                <input type="text" id="tim_fop" name="tim_fop" class="form-control"
                                    placeholder="">
                            </div>
                            <div class="col-12 mb-4">
                                <label for="remark" class="form-label">Remark</label>
                                <input type="text" id="remark" name="remark" class="form-control"
                                    placeholder="">
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

            $('select#site_id_select').html(
                '<option value="" id="option_site_id_1">Masukkan Site ID terlebih dahulu</option>'
            );

            $('input#site_id').on('change', function() {
                let val = $(this).val();

                if (val) {
                    const initialOption = $('#site_id_select option:first').clone();
                    initialOption.text('Pilih Site ID');

                    $.ajax({
                        type: 'GET',
                        url: '{{ route('api.dapot.searchBySiteID') }}',
                        data: {
                            "site_id": val
                        },
                        success: function(data) {
                            let optionHTML = initialOption.prop(
                                'outerHTML');

                            if (data.success) {
                                data.payload.forEach(e => {
                                    optionHTML +=
                                        `<option value="${e.site_id}">${e.site_id} - ${e.site_name}</option>`;
                                });
                            }

                            $('select#site_id_select').html(optionHTML);
                        }
                    });
                } else {
                    $('select#site_id_select').html(
                        '<option value="" id="option_site_id_1">Masukkan Site ID terlebih dahulu</option>'
                    );
                }
            });

            $('select#site_id_select').on('change', function() {
                let val = $(this).val();

                if (val) {
                    $.ajax({
                        type: 'GET',
                        url: '{{ route('api.dapot.findBySiteID') }}',
                        data: {
                            "site_id": val
                        },
                        success: function(data) {
                            $('input#nop').val(data.payload.nop);
                            $('input#cluster_to').val(data.payload.cluster_to);
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
    </script>
@endpush
