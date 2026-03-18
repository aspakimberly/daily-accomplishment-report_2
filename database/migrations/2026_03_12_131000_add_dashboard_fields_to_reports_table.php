<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (!Schema::hasColumn('reports', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            }

            if (!Schema::hasColumn('reports', 'file_name')) {
                $table->string('file_name')->nullable()->after('user_id');
            }

            if (!Schema::hasColumn('reports', 'file_path')) {
                $table->string('file_path')->nullable()->after('file_name');
            }

            if (!Schema::hasColumn('reports', 'status')) {
                $table->string('status')->default('pending')->after('file_path');
            }

            if (!Schema::hasColumn('reports', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('reports', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('submitted_at');
            }

            if (!Schema::hasColumn('reports', 'reviewed_by')) {
                $table->unsignedBigInteger('reviewed_by')->nullable()->after('reviewed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $columns = [
                'user_id',
                'file_name',
                'file_path',
                'status',
                'submitted_at',
                'reviewed_at',
                'reviewed_by',
            ];

            $existingColumns = array_filter($columns, fn (string $column) => Schema::hasColumn('reports', $column));

            if ($existingColumns !== []) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};
