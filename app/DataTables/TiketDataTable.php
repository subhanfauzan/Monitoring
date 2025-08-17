<?php

namespace App\DataTables;

use App\Helpers\Service;
use App\Models\Tiket;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class TiketDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('select', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
            })
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                return '
                    <div class="d-flex gap-2">
            <button type="button" data-id="' .
                    $row->id .
                    '" class="btn btn-sm btn-dark lock-btn">' .
                    ($row->lock == 1 ? 'Unlock' : 'Lock') .
                    '</button>
            <button type="button" data-bs-toggle="modal" data-bs-target="#modal-update-' .
                    $row->id .
                    '" class="btn btn-sm btn-primary">Update</button>
            <button type="button" class="btn btn-sm btn-danger" data-id="' .
                    $row->id .
                    '" onclick="confirmDelete(this)">Delete</button>
        </div>

            <div class="modal fade" id="modal-update-' .
                    $row->id .
                    '" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLabel1">Update Tiket</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="' .
                    route('tiket.update', $row->id) .
                    '" method="POST">
                ' .
                    csrf_field() .
                    '
                ' .
                    method_field('PUT') .
                    '
                <div class="modal-body">
                  <div class="row">
                    <div class="col-12 mb-4">
                    <label for="status_site" class="form-label float-start">Status Site</label>
                    <select id="status_site" name="status_site" class="form-control">
                        <option value="">-- Pilih Status --</option>
                        <option value="Down" ' .
                    ($row->status_site == 'Down' ? 'selected' : '') .
                    '>Down</option>
                        <option value="Up" ' .
                    ($row->status_site == 'Up' ? 'selected' : '') .
                    '>Up</option>
                    </select>
                    </div>
                    <div class="col-12 mb-4">
                      <label for="nameBasic" class="form-label float-start">Tim FOP</label>
                      <input value="' .
                    $row->tim_fop .
                    '" type="text" id="tim_fop" name="tim_fop" class="form-control" placeholder="">
                    </div>
                    <div class="col-12 mb-4">
                      <label for="nameBasic" class="form-label float-start">Remark</label>
                      <input value="' .
                    $row->remark .
                    '" type="text" id="remark" name="remark" class="form-control" placeholder="">
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
            ->addColumn('time_down', function ($row) {
                if ($row->time_down) {
                    try {
                        if (is_numeric($row->time_down)) {
                            // Jika value berupa angka serial Excel
                            $serialDate = (float) $row->time_down;
                            $epoch = 25569; // Excel epoch adalah 1900-01-01
                            $date = Carbon::createFromTimestamp(($serialDate - $epoch) * 86400);
                            return $date->format('d/m/Y H:i');
                        } else {
                            // Jika sudah dalam format yang benar, langsung konversi dengan Carbon
                            return Carbon::parse($row->time_down)->format('d/m/Y H:i');
                        }
                    } catch (\Exception $e) {
                        return Service::convertDate($row->time_down);
                    }
                }
                return '-';
            })
            ->addColumn('waktu_saat_ini', function ($row) {
                // Sama untuk waktu_saat_ini
                if ($row->waktu_saat_ini) {
                    try {
                        $date = \Carbon\Carbon::parse($row->waktu_saat_ini);
                        return $date->format('d/m/Y H:i');
                    } catch (\Exception $e) {
                        return Service::convertDate($row->waktu_saat_ini);
                    }
                }
                return '-';
            })
            ->rawColumns(['time_down', 'action', 'select'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Tiket $model): QueryBuilder
    {
        $query = $model->newQuery();

        if ($this->nop_id) {
            $query = $query->where('nop', 'NOP ' . $this->nop_id);
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('tiket-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            //->dom('Bfrtip')
            ->orderBy(1)
            ->selectStyleSingle()
            ->buttons([Button::make('excel'), Button::make('csv'), Button::make('pdf'), Button::make('print'), Button::make('reset'), Button::make('reload')]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::computed('select')
                ->title('<input type="checkbox" id="select-all">') // checkbox untuk select semua
                ->exportable(false)
                ->printable(false)
                ->width(10)
                ->addClass('text-center'),
            Column::make('DT_RowIndex')
                ->title('No') // Title of the column
                ->searchable(false) // Prevent searching on this column
                ->orderable(false),
            Column::make('site_id'),
            Column::make('saverity'),
            Column::make('suspect_problem'),
            Column::computed('time_down'),
            Column::make('status_site'),
            Column::make('tim_fop'),
            Column::make('remark'),
            Column::make('ticket_swfm'),
            Column::make('cluster_to'),
            Column::computed('action')->exportable(false)->printable(false)->width(60)->addClass('text-center'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Tiket_' . date('YmdHis');
    }
}
