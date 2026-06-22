<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // Fresh SQLite installs include demo_admin in the base migration; nothing to alter.
            return;
        }

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'demo_admin', 'technician', 'customer') NOT NULL DEFAULT 'customer'");
        }
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'demo_admin')->update(['role' => 'customer']);

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'technician', 'customer') NOT NULL DEFAULT 'customer'");
        }
    }
};
