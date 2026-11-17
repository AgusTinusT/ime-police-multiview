<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Throwable;

class DatabaseBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup {--days=14 : Days to keep backup files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform an automated backup of the application database and configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting automated database backup process...');

        $backupDir = storage_path('app/backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $defaultConnection = config('database.default');
        $dbConfig = config("database.connections.{$defaultConnection}");

        $success = false;

        if ($defaultConnection === 'sqlite') {
            $dbPath = $dbConfig['database'];
            $backupFile = "{$backupDir}/backup-sqlite-{$timestamp}.sqlite";
            if (File::exists($dbPath)) {
                File::copy($dbPath, $backupFile);
                $this->info("SQLite database backed up to: {$backupFile}");
                $success = true;
            } else {
                $this->error("SQLite database file not found at: {$dbPath}");
            }
        } elseif ($defaultConnection === 'mysql') {
            $dbName = $dbConfig['database'];
            $host = $dbConfig['host'] ?? '127.0.0.1';
            $port = $dbConfig['port'] ?? '3306';
            $user = $dbConfig['username'] ?? 'root';
            $pass = $dbConfig['password'] ?? '';

            $sqlFile = "{$backupDir}/backup-{$dbName}-{$timestamp}.sql";

            // Method 1: Try mysqldump binary
            $mysqldump = $this->findMysqldumpBinary();

            if ($mysqldump) {
                $cmd = sprintf(
                    '"%s" --host="%s" --port="%s" --user="%s" %s "%s" > "%s"',
                    $mysqldump,
                    $host,
                    $port,
                    $user,
                    $pass ? "--password=" . escapeshellarg($pass) : "",
                    $dbName,
                    $sqlFile
                );

                $resultCode = 0;
                $output = [];
                exec($cmd, $output, $resultCode);

                if ($resultCode === 0 && File::exists($sqlFile) && File::size($sqlFile) > 0) {
                    $this->info("MySQL database dumped successfully via mysqldump to: {$sqlFile}");
                    $success = true;
                }
            }

            // Method 2: PHP Fallback DB Exporter if mysqldump binary is unavailable or failed
            if (!$success) {
                $this->warn("mysqldump binary unavailable or failed. Using PHP fallback DB exporter...");
                $success = $this->exportDatabaseViaPhp($sqlFile);
            }
        } else {
            $this->error("Unsupported database driver for backup: {$defaultConnection}");
            return 1;
        }

        // Clean old backups older than specified days
        $daysToKeep = (int) $this->option('days');
        $this->pruneOldBackups($backupDir, $daysToKeep);

        if ($success) {
            $this->info("✓ Backup completed successfully.");
            return 0;
        }

        $this->error("x Backup failed.");
        return 1;
    }

    /**
     * Find mysqldump binary in system PATH or common Laragon/MySQL paths.
     */
    protected function findMysqldumpBinary(): ?string
    {
        // Try system PATH
        $result = shell_exec(PHP_OS_FAMILY === 'Windows' ? 'where mysqldump 2>nul' : 'which mysqldump 2>/dev/null');
        if ($result) {
            $lines = array_filter(explode("\n", trim($result)));
            if (!empty($lines)) {
                return trim($lines[0]);
            }
        }

        // Common Laragon / MySQL paths on Windows
        $commonPaths = [
            'C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqldump.exe',
            'C:\Development\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqldump.exe',
            'C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
        ];

        foreach ($commonPaths as $path) {
            if (File::exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * PHP Fallback exporter when mysqldump binary is missing.
     */
    protected function exportDatabaseViaPhp(string $filePath): bool
    {
        try {
            $tables = DB::select('SHOW TABLES');
            $dbName = config('database.connections.mysql.database');
            $propName = "Tables_in_{$dbName}";

            $handle = fopen($filePath, 'w');
            if (!$handle) return false;

            fwrite($handle, "-- SASP Police Duty Automated PHP Backup\n");
            fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            foreach ($tables as $tableObj) {
                $tableName = $tableObj->$propName ?? array_values((array)$tableObj)[0] ?? null;
                if (!$tableName) continue;

                $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
                $createSql = $createTable[0]->{'Create Table'} ?? null;

                if ($createSql) {
                    fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");
                    fwrite($handle, $createSql . ";\n\n");

                    $rows = DB::table($tableName)->get();
                    foreach ($rows as $row) {
                        $rowArray = (array) $row;
                        $keys = array_keys($rowArray);
                        $escapedKeys = array_map(fn($k) => "`{$k}`", $keys);
                        
                        $values = array_values($rowArray);
                        $escapedValues = array_map(function ($val) {
                            if (is_null($val)) return 'NULL';
                            return DB::getPdo()->quote($val);
                        }, $values);

                        $insertSql = sprintf(
                            "INSERT INTO `%s` (%s) VALUES (%s);\n",
                            $tableName,
                            implode(', ', $escapedKeys),
                            implode(', ', $escapedValues)
                        );
                        fwrite($handle, $insertSql);
                    }
                    fwrite($handle, "\n");
                }
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($handle);

            return File::exists($filePath) && File::size($filePath) > 0;
        } catch (Throwable $e) {
            $this->error("PHP Backup Exporter error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Prune backups older than specified days.
     */
    protected function pruneOldBackups(string $backupDir, int $daysToKeep): void
    {
        $files = File::files($backupDir);
        $now = time();
        $deletedCount = 0;

        foreach ($files as $file) {
            $lastModified = $file->getMTime();
            if (($now - $lastModified) > ($daysToKeep * 86400)) {
                File::delete($file->getRealPath());
                $deletedCount++;
            }
        }

        if ($deletedCount > 0) {
            $this->info("Cleaned up {$deletedCount} old backup file(s) (older than {$daysToKeep} days).");
        }
    }
}
