<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->index('status');
            $table->index(['customer_id', 'created_at']);
            $table->index(['technician_id', 'status']);
            $table->index('cancelled_at');
            $table->index('estimated_completion_at');
        });

        Schema::table('ticket_comments', function (Blueprint $table) {
            $table->index(['ticket_id', 'created_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['customer_id', 'created_at']);
            $table->dropIndex(['technician_id', 'status']);
            $table->dropIndex(['cancelled_at']);
            $table->dropIndex(['estimated_completion_at']);
        });

        Schema::table('ticket_comments', function (Blueprint $table) {
            $table->dropIndex(['ticket_id', 'created_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
        });
    }
};
