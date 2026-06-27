<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alarm extends Model
{
    use HasFactory;

    protected $table = 'alarms';

    protected $fillable = [
        'first_occurred',
        'mo_name',
        'comment',
        'nop',
        'status_mapping',
    ];
}
