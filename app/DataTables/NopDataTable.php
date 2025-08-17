<?php

namespace App\DataTables;

use App\Models\Nop;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class NopDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                return '
                    <div class="d-flex gap-2">
                    <button type="button" data-bs-toggle="modal" data-bs-target="#modal-update-'. $row->id .'" class="btn btn-sm btn-primary">Update</button>
                    <button type="button" class="btn btn-sm btn-danger" data-id="'. $row->id .'" onclick="confirmDelete(this)">Delete</button>
                    </div>

            <div class="modal fade" id="modal-update-'. $row->id .'" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLabel1">Update NOP</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="'.  route('nop.update', $row->id)  . '" method="POST">
                ' . csrf_field() . '
                ' . method_field('PUT') . '
                <div class="modal-body">
                  <div class="row">
                    <div class="col-12 mb-4">
                      <label for="nameBasic" class="form-label float-start">NOP</label>
                      <input value="'. $row->nama_nop .'" type="text" id="nama_nop" name="nama_nop" class="form-control" placeholder="">
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
                ';
            })
            ->rawColumns(['action'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Nop $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('nop-table')
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
            Column::make('DT_RowIndex')
              ->title('No') // Title of the column
              ->searchable(false) // Prevent searching on this column
              ->orderable(false),
            Column::make('nama_nop'),
            Column::computed('action')
                  ->exportable(false)
                  ->printable(false)
                  ->width(60)
                  ->addClass('text-center'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Nop_' . date('YmdHis');
    }
}
