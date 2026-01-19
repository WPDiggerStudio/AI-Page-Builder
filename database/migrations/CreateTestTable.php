<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CreateTestTable Migration
 *
 * Modifies the test table.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('test', function (Blueprint $table) {
            // Add new columns
            // $table->string('new_column')->after('existing_column');

            // Modify columns
            // $table->string('name', 200)->change();

            // Add indexes
            // $table->index('new_column');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test', function (Blueprint $table) {
            // Remove new columns
            // $table->dropColumn('new_column');

            // Remove indexes
            // $table->dropIndex(['new_column']);
        });
    }
};
