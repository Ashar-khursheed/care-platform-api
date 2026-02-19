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
        DB::statement("ALTER TABLE profile_documents MODIFY COLUMN document_type ENUM('identity_proof', 'address_proof', 'certification', 'background_check', 'other', 'w9_form') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum list (WARNING: this might fail if 'w9_form' data exists)
        // Check if any w9_form exists before reverting? For now, we just attempt revert.
        DB::statement("ALTER TABLE profile_documents MODIFY COLUMN document_type ENUM('identity_proof', 'address_proof', 'certification', 'background_check', 'other') NOT NULL");
    }
};
