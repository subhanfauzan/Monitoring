<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Tabel utama
        Schema::create('daftar_tiket', function (Blueprint $table) {
            $table->id();
            $table->string('site_id');
            $table->string('site_class');
            $table->string('saverity');
            $table->string('suspect_problem');
            $table->string('time_down');
            $table->string('status_site')->nullable();
            $table->string('tim_fop')->nullable();
            $table->string('remark')->nullable();
            $table->string('ticket_swfm');
            $table->string('nop');
            $table->string('cluster_to');
            $table->string('nossa');
            $table->enum('status_ticket', ['Open', 'Close'])->default('Open');
            $table->boolean('lock')->default(0);
            $table->timestamps();
        });

        // Tabel arsip (tikethapus)
        Schema::create('tikethapus', function (Blueprint $table) {
            $table->id();
            $table->string('site_id');
            $table->string('site_class');
            $table->string('saverity');
            $table->string('suspect_problem');
            $table->string('time_down');
            $table->string('status_site')->nullable();
            $table->string('tim_fop')->nullable();
            $table->string('remark')->nullable();
            $table->string('ticket_swfm');
            $table->string('nop');
            $table->string('cluster_to');
            $table->string('nossa');
            $table->enum('status_ticket', ['Open', 'Close'])->default('Open');
            $table->boolean('lock')->default(0);

            // kolom tambahan untuk catat kapan dipindah
            $table->timestamp('deleted_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tikethapus');
        Schema::dropIfExists('daftar_tiket');
    }
};
