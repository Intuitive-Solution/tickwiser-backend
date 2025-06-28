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
        Schema::table('tasks', function (Blueprint $table) {
            // Add project_id column as nullable (optional)
            $table->unsignedBigInteger('project_id')->nullable()->after('user_id');
            
            // Add foreign key constraint
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('set null');
            
            // Add index for better query performance
            $table->index(['project_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Drop the foreign key and index first
            $table->dropForeign(['project_id']);
            $table->dropIndex(['project_id', 'user_id']);
            
            // Drop the column
            $table->dropColumn('project_id');
        });
    }
};
