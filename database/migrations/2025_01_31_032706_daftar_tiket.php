<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
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
            $table->boolean('lock')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daftar_tiket');
    }
};
