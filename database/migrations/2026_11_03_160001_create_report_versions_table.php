<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weekly_report_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('version_number');
            $table->string('original_name');
            $table->string('stored_path', 500)->unique();
            $table->string('disk', 30)->default('local');
            $table->string('extension', 10);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->char('checksum_sha256', 64)->nullable();
            $table->text('student_note')->nullable();
            $table->timestamp('submitted_at');
            $table->boolean('is_late')->default(false);
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['weekly_report_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_versions');
    }
};
