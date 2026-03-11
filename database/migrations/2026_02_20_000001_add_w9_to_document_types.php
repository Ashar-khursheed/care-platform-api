<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Enum modification in Laravel/MySQL is tricky. 
        // We use a raw statement to modify the column definition.
        // NOTE: SQLite does NOT support MODIFY COLUMN so we skip the enum alteration there.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE profile_documents MODIFY COLUMN document_type ENUM('identity_proof', 'address_proof', 'certification', 'background_check', 'other', 'w9_form') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum list
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE profile_documents MODIFY COLUMN document_type ENUM('identity_proof', 'address_proof', 'certification', 'background_check', 'other') NOT NULL");
        }
    }
};
