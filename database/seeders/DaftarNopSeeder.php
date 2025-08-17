<?php

namespace Database\Seeders;

use App\Models\Nop;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DaftarNopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [['nama_nop' => 'SURABAYA'], ['nama_nop' => 'MALANG'], ['nama_nop' => 'LAMONGAN'], ['nama_nop' => 'JEMBER'], ['nama_nop' => 'SIDOARJO'], ['nama_nop' => 'MADIUN']];

        Nop::insert($data);
    }
}
