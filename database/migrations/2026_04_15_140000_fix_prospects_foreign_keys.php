<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasTable('prospects')) {
            return;
        }

        if (
            Schema::hasColumn('prospects', 'employee_id') &&
            Schema::hasTable('employees') &&
            Schema::hasTable('users')
        ) {
            DB::statement('UPDATE prospects p LEFT JOIN employees e ON e.id = p.employee_id SET p.employee_id = NULL WHERE p.employee_id IS NOT NULL AND e.id IS NULL');

            DB::statement('UPDATE prospects p JOIN employees e ON e.id = p.user_id LEFT JOIN users u ON u.id = p.user_id SET p.employee_id = COALESCE(p.employee_id, p.user_id), p.user_id = e.user_id WHERE u.id IS NULL AND e.user_id IS NOT NULL');

            $invalid = (int) DB::table('prospects as p')
                ->leftJoin('users as u', 'u.id', '=', 'p.user_id')
                ->whereNull('u.id')
                ->count();

            if ($invalid > 0) {
                throw new RuntimeException("Invalid prospects.user_id rows: {$invalid}. Fix prospects.user_id to reference users.id before adding FK.");
            }
        }

        $database = DB::getDatabaseName();

        $rows = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->select([
                'CONSTRAINT_NAME as constraint_name',
                'COLUMN_NAME as column_name',
                'REFERENCED_TABLE_NAME as referenced_table_name',
            ])
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', 'prospects')
            ->whereIn('COLUMN_NAME', ['user_id', 'employee_id'])
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->get();

        $byColumn = $rows->groupBy('column_name');

        $userFk = $byColumn->get('user_id')?->first();
        if ($userFk && $userFk->referenced_table_name !== 'users') {
            DB::statement('ALTER TABLE prospects DROP FOREIGN KEY `'.$userFk->constraint_name.'`');
            $userFk = null;
        }

        $employeeFk = $byColumn->get('employee_id')?->first();
        if ($employeeFk && $employeeFk->referenced_table_name !== 'employees') {
            DB::statement('ALTER TABLE prospects DROP FOREIGN KEY `'.$employeeFk->constraint_name.'`');
            $employeeFk = null;
        }

        if (! $userFk && Schema::hasColumn('prospects', 'user_id')) {
            DB::statement('ALTER TABLE prospects ADD CONSTRAINT `prospects_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE');
        }

        if (! $employeeFk && Schema::hasColumn('prospects', 'employee_id')) {
            DB::statement('ALTER TABLE prospects ADD CONSTRAINT `prospects_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE SET NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasTable('prospects')) {
            return;
        }

        $database = DB::getDatabaseName();

        $rows = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->select([
                'CONSTRAINT_NAME as constraint_name',
                'COLUMN_NAME as column_name',
            ])
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', 'prospects')
            ->whereIn('COLUMN_NAME', ['user_id', 'employee_id'])
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->get();

        foreach ($rows as $row) {
            DB::statement('ALTER TABLE prospects DROP FOREIGN KEY `'.$row->constraint_name.'`');
        }
    }
};
