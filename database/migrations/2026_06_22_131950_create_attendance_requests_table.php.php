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
        Schema::create('attendance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('break_id')->nullable()->constrained()->onDelete('set null');
            $table->datetime('requested_clock_in');
            $table->datetime('requested_clock_out');
            $table->datetime('requested_break_in');
            $table->datetime('requested_break_out');
             $table->enum('status', ['pending', 'approved'])->default('pending');
            $table->string('reason');
            $table->datetime('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_requests');
    }
};
