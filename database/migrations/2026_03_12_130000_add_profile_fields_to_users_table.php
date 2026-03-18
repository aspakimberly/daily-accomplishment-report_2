<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'position')) {
                $table->string('position')->nullable()->after('department');
            }

            if (!Schema::hasColumn('users', 'project')) {
                $table->string('project')->nullable()->after('position');
            }

            if (!Schema::hasColumn('users', 'bureau')) {
                $table->string('bureau')->nullable()->after('project');
            }

            if (!Schema::hasColumn('users', 'division')) {
                $table->string('division')->nullable()->after('bureau');
            }

            if (!Schema::hasColumn('users', 'office')) {
                $table->string('office')->nullable()->after('division');
            }

            if (!Schema::hasColumn('users', 'institution')) {
                $table->string('institution')->nullable()->after('office');
            }

            if (!Schema::hasColumn('users', 'otp_code')) {
                $table->string('otp_code')->nullable()->after('otp_hash');
            }

            if (!Schema::hasColumn('users', 'otp_expiration')) {
                $table->timestamp('otp_expiration')->nullable()->after('otp_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'position',
                'project',
                'bureau',
                'division',
                'office',
                'institution',
                'otp_code',
                'otp_expiration',
            ];

            $existingColumns = array_filter($columns, fn (string $column) => Schema::hasColumn('users', $column));

            if ($existingColumns !== []) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};
