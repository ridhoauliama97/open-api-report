<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('personal_access_tokens')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'sqlsrv') {
            DB::statement("
                IF EXISTS (
                    SELECT 1
                    FROM sys.indexes
                    WHERE name = 'personal_access_tokens_tokenable_type_tokenable_id_index'
                      AND object_id = OBJECT_ID('personal_access_tokens')
                )
                DROP INDEX [personal_access_tokens_tokenable_type_tokenable_id_index] ON [personal_access_tokens]
            ");

            DB::statement('ALTER TABLE [personal_access_tokens] ALTER COLUMN [tokenable_id] NVARCHAR(255) NOT NULL');
            DB::statement('CREATE INDEX [personal_access_tokens_tokenable_type_tokenable_id_index] ON [personal_access_tokens] ([tokenable_type], [tokenable_id])');

            return;
        }

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `personal_access_tokens` DROP INDEX `personal_access_tokens_tokenable_type_tokenable_id_index`');
            DB::statement('ALTER TABLE `personal_access_tokens` MODIFY `tokenable_id` VARCHAR(255) NOT NULL');
            DB::statement('CREATE INDEX `personal_access_tokens_tokenable_type_tokenable_id_index` ON `personal_access_tokens` (`tokenable_type`, `tokenable_id`)');
        }
    }

    public function down(): void
    {
        // No-op. Reverting to bigint is not safe for existing string identifiers.
    }
};
