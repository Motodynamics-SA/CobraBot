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
        Schema::create($this->getTableName('personal_access_tokens'), function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists($this->getTableName('personal_access_tokens'));
    }
};
