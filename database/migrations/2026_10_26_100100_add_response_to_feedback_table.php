<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow admins to record a written response for each piece of feedback.
     */
    public function up(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->text('response')->nullable()->after('message');
            $table->foreignId('responded_by')->nullable()->after('response')->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable()->after('responded_by');
        });
    }

    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropForeign(['responded_by']);
            $table->dropColumn(['response', 'responded_by', 'responded_at']);
        });
    }
};
