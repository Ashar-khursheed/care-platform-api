<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add 'rejected', 'notes', 'approved_by', 'rejected_at' to the payouts table.
     * NOTE: SQLite does NOT support MODIFY COLUMN so we skip the enum alteration.
     * The 'rejected' status is enforced at the application/service layer instead.
     */
    public function up(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            if (!Schema::hasColumn('payouts', 'notes')) {
                $table->text('notes')->nullable()->after('failure_reason');
            }
            if (!Schema::hasColumn('payouts', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('payouts', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('paid_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $columns = ['notes', 'approved_by', 'rejected_at'];
            $toRemove = array_filter($columns, fn($col) => Schema::hasColumn('payouts', $col));
            if (!empty($toRemove)) {
                $table->dropColumn(array_values($toRemove));
            }
        });
    }
};
