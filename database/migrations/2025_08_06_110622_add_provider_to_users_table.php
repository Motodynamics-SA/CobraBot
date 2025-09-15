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
        Schema::table($this->getTableName('users'), function (Blueprint $table) {
            $table->string('provider')->nullable()->after('password');
            $table->string('provider_id')->nullable()->after('provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table($this->getTableName('users'), function (Blueprint $table) {
            $table->dropColumn('provider');
            $table->dropColumn('provider_id');
        });
    }
};
