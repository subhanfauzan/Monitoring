<?php

namespace App\Imports;

use App\Models\Dapot;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;

class ImportDapot implements ToModel, WithStartRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function startRow(): int
    {
        return 2; // Skip baris pertama (header)
    }

    public function model(array $row)
    {
        // dd($row);
        return new Dapot([
            'site_id' => $row[0],
            'site_name' => $row[1],
            'site_class' => $row[4],
            'nop' => 'NOP ' . $row[2],
            'cluster_to' => 'TO ' . $row[3],
        ]);
    }
}
