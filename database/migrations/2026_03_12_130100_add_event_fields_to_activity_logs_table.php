<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('activity_logs', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            }

            if (!Schema::hasColumn('activity_logs', 'action')) {
                $table->string('action')->nullable()->after('user_id');
            }

            if (!Schema::hasColumn('activity_logs', 'event')) {
                $table->string('event')->nullable()->after('action');
            }

            if (!Schema::hasColumn('activity_logs', 'description')) {
                $table->text('description')->nullable()->after('event');
            }

            if (!Schema::hasColumn('activity_logs', 'details')) {
                $table->text('details')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $columns = ['user_id', 'action', 'event', 'description', 'details'];

            $existingColumns = array_filter($columns, fn (string $column) => Schema::hasColumn('activity_logs', $column));

            if ($existingColumns !== []) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};
