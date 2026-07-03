<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pengumpulan_tugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tugas_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('file')->nullable();
            $table->text('catatan')->nullable();
            $table->integer('nilai')->nullable();
            $table->enum('status', ['terkirim', 'dinilai'])->default('terkirim');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['tugas_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengumpulan_tugas');
    }
};
