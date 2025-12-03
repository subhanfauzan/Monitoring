<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('ai_query_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // siapa yang tanya
            $table->string('provider')->nullable();            // openai / gemini
            $table->text('user_question');                     // pertanyaan asli
            $table->longText('generated_sql');                 // SQL dari LLM
            $table->string('risk_level')->default('low');      // low / medium / high
            $table->string('status')->default('pending');      // pending/approved/rejected/executed
            $table->unsignedBigInteger('reviewer_id')->nullable(); // siapa yang approve
            $table->timestamp('reviewed_at')->nullable();
            $table->longText('execution_result')->nullable();  // json hasil eksekusi (opsional)
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();                  // info tambahan (tables, limit, dsb)
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ai_query_reviews');
    }
};
