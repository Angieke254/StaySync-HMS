<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {

            // Rename columns
            $table->renameColumn('check_in', 'check_in_date');
            $table->renameColumn('check_out', 'check_out_date');

            // Add missing columns
            $table->integer('num_adults')->default(1);
            $table->integer('num_children')->default(0);

        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {

            $table->renameColumn('check_in_date', 'check_in');
            $table->renameColumn('check_out_date', 'check_out');

            $table->dropColumn([
                'num_adults',
                'num_children'
            ]);

        });
    }
};