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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('technician_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('device_type'); // Desktop/Laptop
            $table->string('brand'); // Device brand
            $table->string('os'); // Operating System
            $table->string('issue_summary');
            $table->text('description');
            $table->enum('status', ['new', 'assigned', 'in_progress', 'awaiting_parts', 'resolved', 'closed'])->default('new');
            $table->integer('priority')->default(3); // 1-5 priority level
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
