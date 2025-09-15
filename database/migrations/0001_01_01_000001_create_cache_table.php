<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Get table name with schema prefix if using SQL Server
     */
    private function getTableName(string $tableName): string {
        return config('database.default') === 'sqlsrv' ? "cobrabot.{$tableName}" : $tableName;
    }

    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create($this->getTableName('cache'), function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create($this->getTableName('cache_locks'), function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists($this->getTableName('cache'));
        Schema::dropIfExists($this->getTableName('cache_locks'));
    }
};
