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
        Schema::create('guidance_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guidance_slot_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('booked');
            $table->timestamp('booked_at')->useCurrent();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancel_reason')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('lecturer_notes')->nullable();
            $activePairExpression = DB::getDriverName() === 'sqlite'
                ? "CASE WHEN status <> 'cancelled' THEN CAST(guidance_slot_id AS TEXT) || '-' || CAST(student_id AS TEXT) ELSE NULL END"
                : "CASE WHEN status <> 'cancelled' THEN CONCAT(guidance_slot_id, '-', student_id) ELSE NULL END";
            $table->string('active_pair_key', 41)->nullable()->storedAs($activePairExpression);
            $table->timestamps();
            $table->unique('active_pair_key');
            $table->index(['student_id', 'status']);
            $table->index(['guidance_slot_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guidance_bookings');
    }
};
