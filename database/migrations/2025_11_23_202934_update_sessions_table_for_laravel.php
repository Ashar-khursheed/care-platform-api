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
        Schema::table('sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('sessions', 'user_id')) {
                $table->foreignId('user_id')->nullable()->index()->after('id');
            }
            if (!Schema::hasColumn('sessions', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('sessions', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }
            if (!Schema::hasColumn('sessions', 'payload')) {
                $table->longText('payload')->after('user_agent');
            }
            if (!Schema::hasColumn('sessions', 'last_activity')) {
                $table->integer('last_activity')->index()->after('payload');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn(['user_id','ip_address','user_agent','payload','last_activity']);
        });
    }
};
