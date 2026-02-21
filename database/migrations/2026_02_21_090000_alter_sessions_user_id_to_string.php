<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('sessions')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'sqlsrv') {
            DB::statement("
                IF EXISTS (
                    SELECT 1
                    FROM sys.indexes
                    WHERE name = 'sessions_user_id_index'
                      AND object_id = OBJECT_ID('sessions')
                )
                DROP INDEX [sessions_user_id_index] ON [sessions]
            ");
            DB::statement('ALTER TABLE [sessions] ALTER COLUMN [user_id] NVARCHAR(255) NULL');
            DB::statement('CREATE INDEX [sessions_user_id_index] ON [sessions] ([user_id])');
            return;
        }

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `sessions` DROP INDEX `sessions_user_id_index`');
            DB::statement('ALTER TABLE `sessions` MODIFY `user_id` VARCHAR(255) NULL');
            DB::statement('ALTER TABLE `sessions` ADD INDEX `sessions_user_id_index` (`user_id`)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('sessions')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'sqlsrv') {
            DB::statement("
                IF EXISTS (
                    SELECT 1
                    FROM sys.indexes
                    WHERE name = 'sessions_user_id_index'
                      AND object_id = OBJECT_ID('sessions')
                )
                DROP INDEX [sessions_user_id_index] ON [sessions]
            ");
            DB::statement('ALTER TABLE [sessions] ALTER COLUMN [user_id] BIGINT NULL');
            DB::statement('CREATE INDEX [sessions_user_id_index] ON [sessions] ([user_id])');
            return;
        }

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `sessions` DROP INDEX `sessions_user_id_index`');
            DB::statement('ALTER TABLE `sessions` MODIFY `user_id` BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE `sessions` ADD INDEX `sessions_user_id_index` (`user_id`)');
        }
    }
};
