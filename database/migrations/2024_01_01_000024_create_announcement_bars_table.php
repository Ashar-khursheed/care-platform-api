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
        Schema::create('announcement_bars', function (Blueprint $table) {
            $table->id();
            $table->text('message');
            $table->string('link_text')->nullable();
            $table->string('link_url')->nullable();
            $table->string('background_color')->default('#3B82F6'); // Default blue
            $table->string('text_color')->default('#FFFFFF'); // Default white
            $table->string('icon')->nullable(); // FontAwesome icon class
            $table->boolean('is_dismissible')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Higher priority shows first
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcement_bars');
    }
};