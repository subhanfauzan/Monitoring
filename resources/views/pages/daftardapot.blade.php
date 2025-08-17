@extends('layouts.layout')

@section('content')
    <div class="flex-grow-1 container-p-y container-xxxl px-3">
        <div class="row g-6 ">
            <div class="container mt-10">
                <div class="card">
                    <div class="card-body">
                        <div class="flex flex-wrap gap-2 mb-3">
                            <button type="button" data-bs-toggle="modal" class="btn btn-secondary waves-effect waves-light"
                                data-bs-target="#basicModal">Import</button>

                            <button type="button" data-bs-toggle="modal" class="btn btn-secondary waves-effect waves-light"
                                data-bs-target="#ModalTambah">Tambah Data</button>
                        </div>
                        {{ $dataTable->table() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="basicModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form class="modal-content" action="{{ route('dapot.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel1">Import Dapot</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="formFile" class="form-label">Default file input example</label>
                        <input class="form-control" type="file" id="file" name="file">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="ModalTambah" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel1">Create Dapot</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('dapot.store') }}" method="POST">
                    @csrf
                    @method('POST')
                    <div class="modal-body">
                        <div class="row">
                            <div class="col mb-2">
                                <label for="nameBasic" class="form-label">Site ID</label>
                                <input type="text" id="site_id" name="site_id" class="form-control" placeholder="">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col mb-2">
                                <label for="nameBasic" class="form-label">Site Name</label>
                                <input type="text" id="site_name" name="site_name" class="form-control" placeholder="">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col mb-2">
                                <label for="nameBasic" class="form-label">NOP</label>
                                <input type="text" id="nop" name="nop" class="form-control" placeholder="">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col mb-2">
                                <label for="nameBasic" class="form-label">Cluster TO</label>
                                <input type="text" id="cluster_to" name="cluster_to" class="form-control" placeholder="">
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
                            url: `/dapot/${id}`,
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
                                    }); //inialisasi datatable
                                    $('#dapot-table').DataTable().ajax.reload(null, false);
                                }
                            }
                        });
                    }
                });
            }
        });

        // resources/js/dropzone-config.js
        document.addEventListener('DOMContentLoaded', function() {
            Dropzone.autoDiscover = false;

            if (document.getElementById('dropzone-basic')) {
                new Dropzone("#dropzone-basic", {
                    url: document.getElementById('dropzone-basic').action,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    paramName: "file",
                    maxFiles: 1,
                    maxFilesize: 10, // MB
                    acceptedFiles: ".csv,.xls,.xlsx",
                    addRemoveLinks: true,
                    dictDefaultMessage: "Drop files here or click to upload",
                    autoProcessQueue: false,
                    init: function() {
                        var myDropzone = this;

                        // Handle form submission
                        document.querySelector("form.modal-content").addEventListener("submit",
                            function(e) {
                                e.preventDefault();
                                if (myDropzone.files.length > 0) {
                                    myDropzone.processQueue();
                                } else {
                                    alert("Please select a file to upload");
                                }
                            });

                        // Handle successful upload
                        this.on("success", function(file, response) {
                            window.location.href = response.redirect;
                        });

                        // Handle errors
                        this.on("error", function(file, errorMessage) {
                            alert(errorMessage);
                        });
                    }
                });
            }
        });
    </script>
@endpush
