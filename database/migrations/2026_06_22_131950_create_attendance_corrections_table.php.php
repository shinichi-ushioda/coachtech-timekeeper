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
        Schema::create('attendance_corrections', function (Blueprint $table) {
             $table->id();
             $table->foreignId('attendance_id')->constrained()->cascadeOnDelete();
             $table->datetime('requested_clock_in')->nullable();
             $table->datetime('requested_clock_out')->nullable();
             $table->json('requested_breaks')->nullable();
             $table->string('reason');
             $table->enum('status', ['pending', 'approved'])->default('pending');
             $table->datetime('approved_at')->nullable();
             $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_corrections');
    }
};
