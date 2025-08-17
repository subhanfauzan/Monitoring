<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nop extends Model
{
    use HasFactory;

    protected $table = 'daftar_nop';
    protected $fillable = ['nama_nop'];

    // Add scope for unique NOPs
    public function scopeUniqueNops($query)
    {
        return $query->select('id', 'nama_nop')->distinct();
    }

    public function setNamaNopAttribute($value)
    {
        $this->attributes['nama_nop'] = strtoupper($value);
    }
}
