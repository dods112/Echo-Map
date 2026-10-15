<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (! Schema::hasColumn('reports', 'comment_id')) {
                $table->foreignId('comment_id')->nullable()->after('pin_id')
                      ->constrained('comments')->cascadeOnDelete();
            }
            if (! Schema::hasColumn('reports', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->after('status')
                      ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('reports', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
            if (! Schema::hasColumn('reports', 'admin_note')) {
                $table->text('admin_note')->nullable()->after('reviewed_at');
            }
        });

        // Add "pending" as a valid status value if your original enum lacks it.
        // (Your original has pending/reviewed/dismissed, so this is a no-op safeguard.)
        // Also: relax pin_id to nullable so comment-only reports are possible.
        Schema::table('reports', function (Blueprint $table) {
            // MySQL: change pin_id nullable
            $table->foreignId('pin_id')->nullable()->change();
        });

        // Unique index: one report per user per pin, and per comment.
        // Uses partial-friendly column set; MySQL treats NULL as distinct so this works.
        Schema::table('reports', function (Blueprint $table) {
            $table->unique(['reporter_id', 'pin_id'],    'reports_user_pin_unique');
            $table->unique(['reporter_id', 'comment_id'],'reports_user_comment_unique');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropUnique('reports_user_pin_unique');
            $table->dropUnique('reports_user_comment_unique');
            foreach (['comment_id', 'reviewed_by', 'reviewed_at', 'admin_note'] as $col) {
                if (Schema::hasColumn('reports', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};