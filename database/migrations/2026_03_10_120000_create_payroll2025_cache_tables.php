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
        $cacheTable = env('DB_CACHE_TABLE', 'payroll2025_cache');
        $lockTable = env('DB_CACHE_LOCK_TABLE', 'payroll2025_cache_locks');

        if (!Schema::hasTable($cacheTable)) {
            Schema::create($cacheTable, function (Blueprint $table) {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->integer('expiration');
            });
        }

        if (!Schema::hasTable($lockTable)) {
            Schema::create($lockTable, function (Blueprint $table) {
                $table->string('key')->primary();
                $table->string('owner');
                $table->integer('expiration');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $cacheTable = env('DB_CACHE_TABLE', 'payroll2025_cache');
        $lockTable = env('DB_CACHE_LOCK_TABLE', 'payroll2025_cache_locks');

        Schema::dropIfExists($cacheTable);
        Schema::dropIfExists($lockTable);
    }
};
