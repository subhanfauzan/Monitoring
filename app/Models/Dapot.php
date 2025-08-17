<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dapot extends Model
{
    protected $table = 'daftar_site';
    protected $fillable = [
        'site_id',
        'site_name',
        'site_class',
        'nop',
        'cluster_to'
    ];
}
