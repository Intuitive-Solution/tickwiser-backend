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
        Schema::table('projects', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['user_id']);
            
            // Change the column type to string
            $table->string('user_id')->change();
            
            // Remove the index as well since we don't have a users table with string IDs
            $table->dropIndex(['user_id', 'created_at']);
            
            // Add a new index for the string user_id
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Drop the string index
            $table->dropIndex(['user_id', 'created_at']);
            
            // Change back to unsigned big integer
            $table->unsignedBigInteger('user_id')->change();
            
            // Restore the foreign key and index
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'created_at']);
        });
    }
};
