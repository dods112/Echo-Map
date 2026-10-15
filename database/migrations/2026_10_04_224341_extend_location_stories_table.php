<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('location_stories', function (Blueprint $table) {
            if (! Schema::hasColumn('location_stories', 'title')) {
                $table->string('title')->nullable()->after('pin_count');
            }
            if (! Schema::hasColumn('location_stories', 'radius_m')) {
                $table->unsignedInteger('radius_m')->default(150)->after('center_lng');
            }
            if (! Schema::hasColumn('location_stories', 'status')) {
                $table->enum('status', ['pending', 'ready', 'failed'])->default('pending')->after('summary');
            }
            if (! Schema::hasColumn('location_stories', 'model')) {
                $table->string('model')->nullable()->after('status');
            }
            if (! Schema::hasColumn('location_stories', 'error')) {
                $table->text('error')->nullable()->after('model');
            }
            // summary was NOT NULL before; make it nullable so 'pending' rows can exist.
            if (Schema::hasColumn('location_stories', 'summary')) {
                $table->text('summary')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('location_stories', function (Blueprint $table) {
            foreach (['title', 'radius_m', 'status', 'model', 'error'] as $col) {
                if (Schema::hasColumn('location_stories', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};