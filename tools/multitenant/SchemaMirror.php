<?php

final class SchemaMirror
{
    public function __construct(private PDO $source)
    {
    }

    public function generateCreateTableSql(string $sourceSchema, string $table): string
    {
        $stmt = $this->source->prepare(
            'SELECT column_name, column_type, is_nullable, column_default, extra, column_key, data_type '
            . 'FROM information_schema.columns '
            . 'WHERE table_schema = :schema AND table_name = :table '
            . 'ORDER BY ordinal_position'
        );
        $stmt->execute(['schema' => $sourceSchema, 'table' => $table]);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $lines = [];
        $primaryKey = null;

        foreach ($columns as $col) {
            if ($col['data_type'] === 'enum') {
                throw new RuntimeException(
                    "SchemaMirror does not support ENUM columns (found `{$table}`.`{$col['column_name']}`) -- this was never encountered during this tool's development; extend it deliberately before trusting it here."
                );
            }

            $line = "`{$col['column_name']}` {$col['column_type']}";

            if ($col['is_nullable'] === 'NO') {
                $line .= ' NOT NULL';
            }

            if ($col['column_default'] !== null) {
                if (stripos((string) $col['extra'], 'auto_increment') !== false) {
                    // no default clause for auto_increment columns
                } elseif (strtolower((string) $col['column_default']) === 'current_timestamp()') {
                    $line .= ' DEFAULT CURRENT_TIMESTAMP';
                } else {
                    $line .= ' DEFAULT ' . $col['column_default'];
                }
            } elseif ($col['is_nullable'] === 'YES') {
                $line .= ' DEFAULT NULL';
            }

            if (stripos((string) $col['extra'], 'auto_increment') !== false) {
                $line .= ' AUTO_INCREMENT';
            }
            if (stripos((string) $col['extra'], 'on update current_timestamp') !== false) {
                $line .= ' ON UPDATE CURRENT_TIMESTAMP';
            }

            $lines[] = $line;

            if ($col['column_key'] === 'PRI') {
                $primaryKey = $col['column_name'];
            }
        }

        $lines[] = '`tenant_id` INT NOT NULL';

        if ($primaryKey !== null) {
            $lines[] = "PRIMARY KEY (`{$primaryKey}`)";
        }

        return "CREATE TABLE `{$table}` (\n    " . implode(",\n    ", $lines) . "\n)";
    }
}
