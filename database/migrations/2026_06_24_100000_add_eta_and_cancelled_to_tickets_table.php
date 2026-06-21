<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('estimated_completion_at')->nullable()->after('priority');
            $table->timestamp('cancelled_at')->nullable()->after('estimated_completion_at');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['estimated_completion_at', 'cancelled_at']);
        });
    }
};
