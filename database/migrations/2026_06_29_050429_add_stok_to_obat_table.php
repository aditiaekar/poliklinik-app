<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obat', function (Blueprint $table) {
            // Menyimpan jumlah stok obat yang tersedia
            $table->unsignedInteger('stok')->default(0)->after('harga');

            // Menyimpan batas minimum stok untuk menandai stok menipis
            $table->unsignedInteger('stok_minimum')->default(0)->after('stok');
        });
    }

    
    // Menghapus kolom stok jika migration di-rollback.
    public function down(): void
    {
        Schema::table('obat', function (Blueprint $table) {
            $table->dropColumn(['stok', 'stok_minimum']);
        });
    }
};