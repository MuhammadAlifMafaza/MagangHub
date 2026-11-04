<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weekly_report_id')->constrained()->restrictOnDelete();
            $table->foreignId('report_version_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('lecturer_id')->constrained()->restrictOnDelete();
            $table->string('decision', 30);
            $table->text('feedback')->nullable();
            $table->timestamp('reviewed_at');
            $table->timestamps();
            $table->index('weekly_report_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_reviews');
    }
};
