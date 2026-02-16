<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ExportDatabaseStructureCommand extends Command
{
    protected $signature = 'db:export-structure
                            {connection=sqlsrv : Nama koneksi database di config/database.php}
                            {--output=storage/app/private/db-structure : Folder output file}
                            {--with-definitions : Sertakan definisi SQL stored procedure}';

    protected $description = 'Export struktur database lengkap (tables, columns, PK/FK, views, procedures, functions, dependencies).';

    /**
     * Execute handle logic.
     */
    public function handle(): int
    {
        $connectionName = (string) $this->argument('connection');
        $outputDir = base_path((string) $this->option('output'));
        $withDefinitions = (bool) $this->option('with-definitions');

        try {
            $connection = DB::connection($connectionName);
            $connection->getPdo();
        } catch (\Throwable $exception) {
            $this->error('Koneksi database gagal: '.$exception->getMessage());

            return self::FAILURE;
        }

        $databaseName = (string) ($connection->getDatabaseName() ?? $connectionName);
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $databaseName) ?: $connectionName);

        if (!is_dir($outputDir) && !mkdir($outputDir, 0777, true) && !is_dir($outputDir)) {
            throw new RuntimeException(sprintf('Tidak bisa membuat folder output: %s', $outputDir));
        }

        $this->info("Mengambil metadata dari [{$connectionName}] database [{$databaseName}]...");

        $schemas = $connection->select("
            SELECT
                s.name AS schema_name,
                SUM(CASE WHEN o.type = 'U' THEN 1 ELSE 0 END) AS table_count,
                SUM(CASE WHEN o.type = 'V' THEN 1 ELSE 0 END) AS view_count,
                SUM(CASE WHEN o.type = 'P' THEN 1 ELSE 0 END) AS procedure_count,
                SUM(CASE WHEN o.type IN ('FN','IF','TF') THEN 1 ELSE 0 END) AS function_count
            FROM sys.schemas s
            LEFT JOIN sys.objects o ON o.schema_id = s.schema_id AND o.is_ms_shipped = 0
            GROUP BY s.name
            ORDER BY s.name
        ");

        $tables = $connection->select("
            SELECT
                s.name AS schema_name,
                t.name AS table_name,
                t.object_id
            FROM sys.tables t
            JOIN sys.schemas s ON s.schema_id = t.schema_id
            WHERE t.is_ms_shipped = 0
            ORDER BY s.name, t.name
        ");

        $columns = $connection->select("
            SELECT
                s.name AS schema_name,
                t.name AS table_name,
                c.column_id,
                c.name AS column_name,
                ty.name AS data_type,
                c.max_length,
                c.precision,
                c.scale,
                c.is_nullable
            FROM sys.columns c
            JOIN sys.tables t ON t.object_id = c.object_id
            JOIN sys.schemas s ON s.schema_id = t.schema_id
            JOIN sys.types ty ON ty.user_type_id = c.user_type_id
            WHERE t.is_ms_shipped = 0
            ORDER BY s.name, t.name, c.column_id
        ");

        $primaryKeys = $connection->select("
            SELECT
                ss.name AS schema_name,
                st.name AS table_name,
                kc.name AS pk_name,
                c.name AS column_name,
                ic.key_ordinal
            FROM sys.key_constraints kc
            JOIN sys.index_columns ic ON ic.object_id = kc.parent_object_id AND ic.index_id = kc.unique_index_id
            JOIN sys.columns c ON c.object_id = ic.object_id AND c.column_id = ic.column_id
            JOIN sys.tables st ON st.object_id = kc.parent_object_id
            JOIN sys.schemas ss ON ss.schema_id = st.schema_id
            WHERE kc.type = 'PK'
            ORDER BY ss.name, st.name, ic.key_ordinal
        ");

        $foreignKeys = $connection->select("
            SELECT
                ps.name AS parent_schema,
                pt.name AS parent_table,
                fk.name AS fk_name,
                pc.name AS parent_column,
                rs.name AS referenced_schema,
                rt.name AS referenced_table,
                rc.name AS referenced_column,
                fkc.constraint_column_id
            FROM sys.foreign_keys fk
            JOIN sys.foreign_key_columns fkc ON fkc.constraint_object_id = fk.object_id
            JOIN sys.tables pt ON pt.object_id = fk.parent_object_id
            JOIN sys.schemas ps ON ps.schema_id = pt.schema_id
            JOIN sys.columns pc ON pc.object_id = pt.object_id AND pc.column_id = fkc.parent_column_id
            JOIN sys.tables rt ON rt.object_id = fk.referenced_object_id
            JOIN sys.schemas rs ON rs.schema_id = rt.schema_id
            JOIN sys.columns rc ON rc.object_id = rt.object_id AND rc.column_id = fkc.referenced_column_id
            ORDER BY ps.name, pt.name, fk.name, fkc.constraint_column_id
        ");

        $views = $connection->select("
            SELECT s.name AS schema_name, v.name AS view_name
            FROM sys.views v
            JOIN sys.schemas s ON s.schema_id = v.schema_id
            WHERE v.is_ms_shipped = 0
            ORDER BY s.name, v.name
        ");

        $functions = $connection->select("
            SELECT s.name AS schema_name, o.name AS function_name, o.type_desc
            FROM sys.objects o
            JOIN sys.schemas s ON s.schema_id = o.schema_id
            WHERE o.type IN ('FN','IF','TF') AND o.is_ms_shipped = 0
            ORDER BY s.name, o.name
        ");

        $procedures = $connection->select("
            SELECT p.object_id, s.name AS schema_name, p.name AS procedure_name
            FROM sys.procedures p
            JOIN sys.schemas s ON s.schema_id = p.schema_id
            WHERE p.is_ms_shipped = 0
            ORDER BY s.name, p.name
        ");

        $procedureParams = $connection->select("
            SELECT
                p.object_id,
                prm.parameter_id,
                prm.name AS parameter_name,
                t.name AS data_type,
                prm.max_length,
                prm.precision,
                prm.scale,
                prm.is_output
            FROM sys.procedures p
            LEFT JOIN sys.parameters prm ON prm.object_id = p.object_id
            LEFT JOIN sys.types t ON t.user_type_id = prm.user_type_id
            WHERE p.is_ms_shipped = 0
            ORDER BY p.object_id, prm.parameter_id
        ");

        $procedureDependencies = $connection->select("
            SELECT
                p.object_id,
                ss.name AS schema_name,
                p.name AS procedure_name,
                COALESCE(ds.name, OBJECT_SCHEMA_NAME(d.referenced_id)) AS referenced_schema,
                COALESCE(o.name, d.referenced_entity_name) AS referenced_name,
                COALESCE(o.type_desc, d.referenced_class_desc) AS referenced_type
            FROM sys.procedures p
            JOIN sys.schemas ss ON ss.schema_id = p.schema_id
            LEFT JOIN sys.sql_expression_dependencies d ON d.referencing_id = p.object_id
            LEFT JOIN sys.objects o ON o.object_id = d.referenced_id
            LEFT JOIN sys.schemas ds ON ds.schema_id = o.schema_id
            WHERE p.is_ms_shipped = 0
            ORDER BY ss.name, p.name, referenced_schema, referenced_name
        ");

        $procedureDefinitions = [];
        if ($withDefinitions) {
            $definitions = $connection->select("
                SELECT p.object_id, sm.definition
                FROM sys.procedures p
                LEFT JOIN sys.sql_modules sm ON sm.object_id = p.object_id
                WHERE p.is_ms_shipped = 0
            ");

            foreach ($definitions as $definition) {
                $procedureDefinitions[(int) $definition->object_id] = $definition->definition;
            }
        }

        $tablesMap = [];
        foreach ($tables as $table) {
            $key = $table->schema_name.'.'.$table->table_name;
            $tablesMap[$key] = [
                'schema' => $table->schema_name,
                'table' => $table->table_name,
                'columns' => [],
                'primary_key' => [],
                'foreign_keys' => [],
            ];
        }

        foreach ($columns as $column) {
            $key = $column->schema_name.'.'.$column->table_name;
            $tablesMap[$key]['columns'][] = [
                'name' => $column->column_name,
                'data_type' => $this->formatType($column->data_type, $column->max_length, $column->precision, $column->scale),
                'nullable' => (bool) $column->is_nullable,
                'ordinal' => (int) $column->column_id,
            ];
        }

        foreach ($primaryKeys as $primaryKey) {
            $key = $primaryKey->schema_name.'.'.$primaryKey->table_name;
            $tablesMap[$key]['primary_key'][] = [
                'name' => $primaryKey->pk_name,
                'column' => $primaryKey->column_name,
                'ordinal' => (int) $primaryKey->key_ordinal,
            ];
        }

        foreach ($foreignKeys as $foreignKey) {
            $key = $foreignKey->parent_schema.'.'.$foreignKey->parent_table;
            $tablesMap[$key]['foreign_keys'][] = [
                'name' => $foreignKey->fk_name,
                'column' => $foreignKey->parent_column,
                'references' => $foreignKey->referenced_schema.'.'.$foreignKey->referenced_table.'.'.$foreignKey->referenced_column,
                'ordinal' => (int) $foreignKey->constraint_column_id,
            ];
        }

        ksort($tablesMap);

        $paramsByProcedure = [];
        foreach ($procedureParams as $parameter) {
            if ($parameter->parameter_name === null) {
                continue;
            }

            $paramsByProcedure[(int) $parameter->object_id][] = [
                'name' => $parameter->parameter_name,
                'data_type' => $this->formatType($parameter->data_type, $parameter->max_length, $parameter->precision, $parameter->scale),
                'output' => (bool) $parameter->is_output,
                'ordinal' => (int) $parameter->parameter_id,
            ];
        }

        $depsByProcedure = [];
        foreach ($procedureDependencies as $dependency) {
            $objectId = (int) $dependency->object_id;
            if ($dependency->referenced_name === null) {
                continue;
            }

            $depsByProcedure[$objectId][] = [
                'schema' => $dependency->referenced_schema,
                'name' => $dependency->referenced_name,
                'type' => $dependency->referenced_type,
            ];
        }

        $proceduresOut = [];
        foreach ($procedures as $procedure) {
            $objectId = (int) $procedure->object_id;
            $item = [
                'schema' => $procedure->schema_name,
                'name' => $procedure->procedure_name,
                'parameters' => $paramsByProcedure[$objectId] ?? [],
                'dependencies' => $depsByProcedure[$objectId] ?? [],
            ];

            if ($withDefinitions) {
                $item['definition'] = $procedureDefinitions[$objectId] ?? null;
            }

            $proceduresOut[] = $item;
        }

        $summary = [
            'generated_at' => now()->toIso8601String(),
            'connection' => $connectionName,
            'database' => $databaseName,
            'totals' => [
                'schemas' => count($schemas),
                'tables' => count($tables),
                'views' => count($views),
                'procedures' => count($procedures),
                'functions' => count($functions),
            ],
            'by_schema' => array_map(static fn($row): array => [
                'schema' => $row->schema_name,
                'tables' => (int) $row->table_count,
                'views' => (int) $row->view_count,
                'procedures' => (int) $row->procedure_count,
                'functions' => (int) $row->function_count,
            ], $schemas),
        ];

        $paths = [
            'summary' => $outputDir.'/'.$slug.'_summary.json',
            'tables' => $outputDir.'/'.$slug.'_tables.json',
            'views' => $outputDir.'/'.$slug.'_views.json',
            'functions' => $outputDir.'/'.$slug.'_functions.json',
            'procedures' => $outputDir.'/'.$slug.'_procedures.json',
            'readme' => $outputDir.'/'.$slug.'_README.md',
        ];

        file_put_contents($paths['summary'], json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        file_put_contents($paths['tables'], json_encode(array_values($tablesMap), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        file_put_contents($paths['views'], json_encode(array_map(static fn($row): array => [
            'schema' => $row->schema_name,
            'name' => $row->view_name,
        ], $views), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        file_put_contents($paths['functions'], json_encode(array_map(static fn($row): array => [
            'schema' => $row->schema_name,
            'name' => $row->function_name,
            'type' => $row->type_desc,
        ], $functions), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        file_put_contents($paths['procedures'], json_encode($proceduresOut, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        file_put_contents($paths['readme'], $this->buildReadme($summary, $paths, $withDefinitions));

        $this->info('Export selesai.');
        $this->line('- '.$paths['summary']);
        $this->line('- '.$paths['tables']);
        $this->line('- '.$paths['views']);
        $this->line('- '.$paths['functions']);
        $this->line('- '.$paths['procedures']);
        $this->line('- '.$paths['readme']);

        return self::SUCCESS;
    }

    /**
     * Execute format type logic.
     */
    private function formatType(?string $baseType, ?int $maxLength, ?int $precision, ?int $scale): string
    {
        $type = (string) $baseType;

        if (in_array($type, ['varchar', 'nvarchar', 'char', 'nchar', 'varbinary', 'binary'], true)) {
            $length = (int) ($maxLength ?? 0);

            if (in_array($type, ['nvarchar', 'nchar'], true) && $length > 0) {
                $length = (int) ($length / 2);
            }

            return sprintf('%s(%s)', $type, $length === -1 ? 'max' : (string) $length);
        }

        if (in_array($type, ['decimal', 'numeric'], true)) {
            return sprintf('%s(%d,%d)', $type, (int) ($precision ?? 0), (int) ($scale ?? 0));
        }

        return $type;
    }

    /**
     * @param array<string, mixed> $summary
     * @param array<string, string> $paths
     */
    private function buildReadme(array $summary, array $paths, bool $withDefinitions): string
    {
        $lines = [];
        $lines[] = '# Database Structure Export';
        $lines[] = '';
        $lines[] = '- Generated at: `'.$summary['generated_at'].'`';
        $lines[] = '- Connection: `'.$summary['connection'].'`';
        $lines[] = '- Database: `'.$summary['database'].'`';
        $lines[] = '';
        $lines[] = '## Totals';
        $lines[] = '- Schemas: '.$summary['totals']['schemas'];
        $lines[] = '- Tables: '.$summary['totals']['tables'];
        $lines[] = '- Views: '.$summary['totals']['views'];
        $lines[] = '- Stored Procedures: '.$summary['totals']['procedures'];
        $lines[] = '- Functions: '.$summary['totals']['functions'];
        $lines[] = '';
        $lines[] = '## Files';
        $lines[] = '- Summary: `'.$paths['summary'].'`';
        $lines[] = '- Tables + columns + PK/FK: `'.$paths['tables'].'`';
        $lines[] = '- Views: `'.$paths['views'].'`';
        $lines[] = '- Functions: `'.$paths['functions'].'`';
        $lines[] = '- Procedures + params + dependencies'.($withDefinitions ? ' + definition' : '').': `'.$paths['procedures'].'`';

        return implode(PHP_EOL, $lines).PHP_EOL;
    }
}
