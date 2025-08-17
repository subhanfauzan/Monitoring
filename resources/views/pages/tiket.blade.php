@extends('layouts.layout')

@section('content')
    <div class="flex-grow-1 container-p-y container-xxxl px-3">
        <div class="row g-6">
            <div class="container mt-10">
                <div class="card">
                    <div class="card-header">
                        <div class="col-6">
                            <button type="button" data-bs-toggle="modal" class="btn btn-secondary waves-effect waves-light"
                                data-bs-target="#basicModal">Import</button>
                            <button type="button" data-bs-toggle="modal" class="btn btn-secondary waves-effect waves-light"
                                data-bs-target="#basicModal">Export</button>
                            <button type="button" class="btn btn-secondary waves-effect waves-light"
                                onclick="confirmDeleteNop(this)">Hapus Semua Data
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive text-nowrap text-center">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Total Jumlah Tiket Down</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                                <tr>
                                    <td>
                                        <span class="fw-medium" id="totaltiket">{{ $totaltiket }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="basicModal" tabindex="-1" aria-hidden="true">
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

    <div id="loading-overlay"
        class="d-flex position-fixed top-0 start-0 w-100 h-100 d-none align-items-center justify-content-center bg-light bg-opacity-75"
        style="z-index: 1055;">
        <div class="text-center">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3">Mengimpor data, mohon tunggu...</p>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
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

    <script>
        $(document).ready(function() {
            window.confirmDeleteNop = function(e) {
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
                            url: `/tiket/deleteall`, // Ensure this URL matches your route
                            data: {
                                "_token": "{{ csrf_token() }}",
                                "_method": 'DELETE',
                            },
                            success: function(data) {
                                if (data.success) {
                                    Swal.fire({
                                        title: "Deleted!",
                                        text: data.message ||
                                            "Your data has been deleted.",
                                        icon: "success"
                                    });

                                    // Send another AJAX request to get the updated ticket count
                                    $.ajax({
                                        url: '{{ route('tiket.count') }}', // Use the route name here
                                        type: 'GET',
                                        success: function(response) {
                                            if (response.success) {
                                                // Update the ticket count on the page
                                                $('#totaltiket').text(response
                                                    .total);
                                            }
                                        },
                                        error: function(xhr, status, error) {
                                            console.error(
                                                "Error fetching the updated count:",
                                                xhr.responseText);
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        title: "Error!",
                                        text: data.message ||
                                            "Failed to delete data",
                                        icon: "error"
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error("Error details:", xhr.responseText);
                                Swal.fire({
                                    title: "Error!",
                                    text: "Something went wrong: " + error,
                                    icon: "error"
                                });
                            }
                        });
                    }
                });
            }

            // Loading overlay for the import process
            $('#basicModal form').on('submit', function() {
                $('#loading-overlay').removeClass('d-none');
                $('#basicModal').modal('hide');
            });
        });
    </script>
@endpush
