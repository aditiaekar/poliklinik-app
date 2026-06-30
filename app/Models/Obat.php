<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Obat extends Model
{
    protected $table = 'obat';

    protected $fillable = [
        'nama_obat',
        'kemasan',
        'harga',
        'stok',
        'stok_minimum',
    ];

    protected $casts = [
        'harga' => 'integer',
        'stok' => 'integer',
        'stok_minimum' => 'integer',
    ];

    
    // Relasi ke detail pemeriksaan, Satu obat bisa digunakan di banyak detail pemeriksaan.
    public function detailPeriksas()
    {
        return $this->hasMany(DetailPeriksa::class, 'id_obat');
    }

    public function getStatusStokAttribute(): string
    {
        // Jika stok 0 atau kurang, maka statusnya habis
        if ($this->stok <= 0) {
            return 'habis';
        }

        // Jika stok sudah berada di bawah / sama dengan batas minimum
        if ($this->stok_minimum > 0 && $this->stok <= $this->stok_minimum) {
            return 'menipis';
        }

        return 'aman';
    }
}