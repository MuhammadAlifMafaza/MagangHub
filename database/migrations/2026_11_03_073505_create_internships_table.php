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
        Schema::create('internships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->string('company_name', 150);
            $table->string('company_supervisor_name', 100)->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('active')->index();
            $table->unsignedBigInteger('active_student_id')->nullable()->storedAs("CASE WHEN status = 'active' THEN student_id ELSE NULL END");
            $table->unique('active_student_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internships');
    }
};
