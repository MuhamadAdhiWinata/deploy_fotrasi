<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kaih_activities', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('description')->nullable();
            $table->string('group')->default('rutinitas');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('kaih_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('activity_key');
            $table->date('tanggal');
            $table->enum('status', ['ya', 'belum'])->default('belum');
            $table->text('reason')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->foreignId('periode_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'activity_key', 'tanggal']);
        });

        Schema::create('kaih_reflections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('tanggal');
            $table->text('content')->nullable();
            $table->foreignId('periode_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kaih_reflections');
        Schema::dropIfExists('kaih_entries');
        Schema::dropIfExists('kaih_activities');
    }
};
