<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tiket extends Model
{
    protected $table = 'daftar_tiket';

    protected $fillable = ['site_id', 'site_class', 'saverity', 'suspect_problem', 'time_down', 'status_site', 'tim_fop', 'remark', 'ticket_swfm', 'nop', 'cluster_to', 'nossa'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
