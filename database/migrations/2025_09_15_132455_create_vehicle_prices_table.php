<?php

declare(strict_types=1);

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
        Schema::create($this->getTableName('vehicle_prices'), function (Blueprint $table) {
            $table->id();
            $table->date('yielding_date');
            $table->string('car_group');
            $table->string('type');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('yield');
            $table->string('yield_code');
            $table->decimal('price', 10, 4)->nullable();
            $table->string('pool');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists($this->getTableName('vehicle_prices'));
    }
};
