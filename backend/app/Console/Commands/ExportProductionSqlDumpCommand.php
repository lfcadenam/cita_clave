<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ExportProductionSqlDumpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nuvex:export-production-sql {--output= : Ruta opcional de destino para el archivo .sql}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exporta la base de datos completa a un archivo SQL puro, listo e idóneo para despliegue en producción';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('===============================================================');
        $this->info('  📦 NUVEX — GENERADOR DE VOLCADO SQL PARA PRODUCCIÓN          ');
        $this->info('===============================================================');

        $backupDir = database_path('backups');
        if (! File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $defaultPath = $backupDir . DIRECTORY_SEPARATOR . 'nuvex_agendamiento_production_backup.sql';
        $outputPath = $this->option('output') ?: $defaultPath;

        $dbName = config('database.connections.' . config('database.default') . '.database');
        $this->line("• Base de datos origen: <info>{$dbName}</info>");
        $this->line("• Destino de exportación: <comment>{$outputPath}</comment>\n");

        $handle = fopen($outputPath, 'w');
        if (! $handle) {
            $this->error("No se pudo abrir el archivo de destino para escritura: {$outputPath}");
            return Command::FAILURE;
        }

        // Cabecera SQL
        $header = "-- ========================================================================\n"
                . "-- NUVEX TECNOLOGÍA — RESPALDO COMPLETO DE BASE DE DATOS PARA PRODUCCIÓN\n"
                . "-- Generado: " . now()->toIso8601String() . "\n"
                . "-- Base de datos: {$dbName}\n"
                . "-- ========================================================================\n\n"
                . "SET NAMES utf8mb4;\n"
                . "SET FOREIGN_KEY_CHECKS = 0;\n"
                . "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n";

        fwrite($handle, $header);

        // Listar tablas
        $tables = DB::select('SHOW FULL TABLES WHERE Table_Type = "BASE TABLE"');
        $tableKey = 'Tables_in_' . $dbName;

        $tableStats = [];

        foreach ($tables as $t) {
            $prop = (array) $t;
            $tableName = reset($prop);

            // Ignorar jobs y batch temporales si no tienen relevancia crítica
            $this->line("  Procesando tabla: <info>{$tableName}</info>...");

            // Estructura de la tabla (CREATE TABLE)
            $createTableRes = DB::select("SHOW CREATE TABLE `{$tableName}`");
            $createSql = $createTableRes[0]->{'Create Table'} ?? null;

            if ($createSql) {
                fwrite($handle, "-- --------------------------------------------------------\n");
                fwrite($handle, "-- Estructura de tabla para `{$tableName}`\n");
                fwrite($handle, "-- --------------------------------------------------------\n");
                fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");
                fwrite($handle, $createSql . ";\n\n");
            }

            // Datos de la tabla
            $rowCount = DB::table($tableName)->count();
            $tableStats[] = [$tableName, $rowCount];

            if ($rowCount > 0) {
                fwrite($handle, "-- Datos para la tabla `{$tableName}` ({$rowCount} registros)\n");

                DB::table($tableName)->orderBy(DB::raw('1'))->chunk(200, function ($rows) use ($handle, $tableName) {
                    $insertBase = "INSERT INTO `{$tableName}` VALUES ";
                    $values = [];

                    foreach ($rows as $row) {
                        $rowArray = (array) $row;
                        $escapedValues = array_map(function ($val) {
                            if (is_null($val)) {
                                return 'NULL';
                            }
                            if (is_numeric($val) && ! is_string($val)) {
                                return $val;
                            }
                            return DB::connection()->getPdo()->quote($val);
                        }, $rowArray);

                        $values[] = '(' . implode(', ', $escapedValues) . ')';
                    }

                    fwrite($handle, $insertBase . implode(",\n", $values) . ";\n");
                });

                fwrite($handle, "\n");
            }
        }

        // Cierre SQL
        $footer = "SET FOREIGN_KEY_CHECKS = 1;\n"
                . "-- ========================================================================\n"
                . "-- FIN DEL VOLCADO SQL — NUVEX AGENDAMIENTO\n"
                . "-- ========================================================================\n";
        fwrite($handle, $footer);
        fclose($handle);

        $fileSizeKb = round(filesize($outputPath) / 1024, 2);

        $this->line('');
        $this->info("✅ ¡Respaldo SQL exportado exitosamente!");
        $this->table(['Tabla', 'Registros Exportados'], $tableStats);

        $this->line("\n📁 Archivo generado: <comment>{$outputPath}</comment> ({$fileSizeKb} KB)");
        $this->line("💡 Este archivo puede importarse en cualquier servidor de producción ejecutando:");
        $this->line("   <info>mysql -u [usuario] -p [base_de_datos] < {$outputPath}</info>\n");

        return Command::SUCCESS;
    }
}
