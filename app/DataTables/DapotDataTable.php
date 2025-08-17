<?php

namespace App\DataTables;

use App\Models\Dapot;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class DapotDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($row) {
                $updateUrl = ''; // Ganti dengan route update
                $deleteUrl = ''; // Ganti dengan route delete

                return '
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" data-bs-toggle="modal" data-bs-target="#modal-update-'. $row->id .'" class="btn btn-primary btn-sm">Update</button>
                        <button type="button" class="btn btn-sm btn-danger" data-id="'. $row->id .'" onclick="confirmDelete(this)">Delete</button>
                    </div>

            <div class="modal fade" id="modal-update-'. $row->id .'" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLabel1">Update Dapot</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="'.  route('dapot.update', $row->id)  . '" method="POST">
                ' . csrf_field() . '
                ' . method_field('PUT') . '
                <div class="modal-body">
                  <div class="row">
                    <div class="col-12 mb-4">
                      <label for="nameBasic" class="form-label float-start">NOP</label>
                      <input value="'. $row->site_id .'" type="text" id="site_id" name="site_id" class="form-control" placeholder="">
                    </div>
                    <div class="col-12 mb-4">
                      <label for="nameBasic" class="form-label float-start">NOP</label>
                      <input value="'. $row->site_name .'" type="text" id="site_name" name="site_name" class="form-control" placeholder="">
                    </div>
                    <div class="col-12 mb-4">
                      <label for="nameBasic" class="form-label float-start">NOP</label>
                      <input value="'. $row->nop .'" type="text" id="nop" name="nop" class="form-control" placeholder="">
                    </div>
                    <div class="col-12 mb-4">
                      <label for="nameBasic" class="form-label float-start">NOP</label>
                      <input value="'. $row->cluster_to .'" type="text" id="cluster_to" name="cluster_to" class="form-control" placeholder="">
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
                    '

                    ;
            })
            ->rawColumns(['action']) // Mark the 'action' column as raw HTML
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(DapoT $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('dapot-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    //->dom('Bfrtip')
                    ->orderBy(1)
                    ->selectStyleSingle()
                    ->buttons([
                        Button::make('excel'),
                        Button::make('csv'),
                        Button::make('pdf'),
                        Button::make('print'),
                        Button::make('reset'),
                        Button::make('reload')
                    ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('site_id'),
            Column::make('site_name'),
            Column::make('site_class'),
            Column::make('nop'),
            Column::make('cluster_to'),
            Column::computed('action')
                  ->exportable(false)
                  ->printable(false)
                  ->width(100)
                  ->addClass('text-center'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Dapot_' . date('YmdHis');
    }
}
