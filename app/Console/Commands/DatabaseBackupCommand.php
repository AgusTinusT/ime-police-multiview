<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

#[Signature('db:backup {--filename=}')]
#[Description('Backup the current application database (MySQL or SQLite) to storage/app/backups')]
class DatabaseBackupCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $connection = config('database.default');
        $backupDir = storage_path('app/backups');

        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $this->info("Starting database backup for connection: [{$connection}]...");

        if ($connection === 'sqlite') {
            return $this->backupSqlite($backupDir, $timestamp);
        }

        if ($connection === 'mysql') {
            return $this->backupMysql($backupDir, $timestamp);
        }

        $this->error("Unsupported database connection: {$connection}");
        return 1;
    }

    /**
     * Backup SQLite Database
     */
    protected function backupSqlite(string $backupDir, string $timestamp)
    {
        $dbPath = config('database.connections.sqlite.database');

        if (!File::exists($dbPath)) {
            $this->error("SQLite database file not found at: {$dbPath}");
            return 1;
        }

        $customFilename = $this->option('filename');
        $filename = $customFilename ?: "backup_sqlite_{$timestamp}.sqlite";
        $targetPath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        if (File::copy($dbPath, $targetPath)) {
            $size = round(File::size($targetPath) / 1024, 2);
            $this->info("SUCCESS: SQLite database backed up successfully!");
            $this->line("Location: <comment>{$targetPath}</comment>");
            $this->line("File Size: <comment>{$size} KB</comment>");
            return 0;
        }

        $this->error("FAILED: Could not copy SQLite database file.");
        return 1;
    }

    /**
     * Backup MySQL Database
     */
    protected function backupMysql(string $backupDir, string $timestamp)
    {
        $dbHost = config('database.connections.mysql.host');
        $dbPort = config('database.connections.mysql.port');
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        $customFilename = $this->option('filename');
        $filename = $customFilename ?: "backup_{$dbName}_{$timestamp}.sql";
        $targetPath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        $mysqldumpBin = 'mysqldump';

        $laragonMysqldump = 'C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqldump.exe';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && File::exists($laragonMysqldump)) {
            $mysqldumpBin = $laragonMysqldump;
        }

        $cmd = [
            $mysqldumpBin,
            "--host={$dbHost}",
            "--port={$dbPort}",
            "--user={$dbUser}",
        ];

        if (!empty($dbPass)) {
            $cmd[] = "--password={$dbPass}";
        }

        $cmd[] = $dbName;

        $this->line("Executing MySQL Dump for database <comment>{$dbName}</comment>...");

        $process = new Process($cmd);
        $process::setTimeout(300);

        try {
            $process->run();

            if (!$process->isSuccessful()) {
                $this->error("MySQL Dump Error: " . $process->getErrorOutput());
                return 1;
            }

            File::put($targetPath, $process->getOutput());
            $size = round(File::size($targetPath) / 1024, 2);

            $this->info("SUCCESS: MySQL Database backed up successfully!");
            $this->line("Location: <comment>{$targetPath}</comment>");
            $this->line("File Size: <comment>{$size} KB</comment>");
            return 0;
        } catch (\Exception $e) {
            $this->error("Exception during MySQL backup: " . $e->getMessage());
            return 1;
        }
    }
}
