<?php

namespace App\Console\Commands\System;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class SystemBackup extends Command
{
    protected $signature = 'system:backup';

    protected $description = 'Create local backups for database and uploads with retention.';

    public function handle(): int
    {
        $timestamp = now()->format('Ymd_His');

        $baseDir = storage_path('backups');
        $dbDir = $baseDir . DIRECTORY_SEPARATOR . 'db';
        $uploadsDir = $baseDir . DIRECTORY_SEPARATOR . 'uploads';

        File::ensureDirectoryExists($dbDir);
        File::ensureDirectoryExists($uploadsDir);

        $dbDumpPath = $dbDir . DIRECTORY_SEPARATOR . "{$timestamp}.sql";
        $uploadsZipPath = $uploadsDir . DIRECTORY_SEPARATOR . "{$timestamp}.zip";
        $metadataPath = $baseDir . DIRECTORY_SEPARATOR . "{$timestamp}.json";

        $dbOk = $this->dumpDatabase($dbDumpPath);
        $uploadsOk = $this->zipUploads($uploadsZipPath);

        $this->writeMetadata($metadataPath, $dbDumpPath, $uploadsZipPath);
        $this->applyRetention($dbDir, $uploadsDir, 7);

        if (! $dbOk || ! $uploadsOk) {
            $this->error('Backup completed with errors. Check logs.');
            return self::FAILURE;
        }

        $this->info('Backup completed successfully.');
        return self::SUCCESS;
    }

    private function dumpDatabase(string $dumpPath): bool
    {
        try {
            $connection = config('database.default');
            $config = config("database.connections.{$connection}");

            $host = $config['host'] ?? '127.0.0.1';
            $port = $config['port'] ?? 3306;
            $database = $config['database'] ?? '';
            $username = $config['username'] ?? '';
            $password = $config['password'] ?? '';

            if ($database === '') {
                throw new \RuntimeException('Database name is missing.');
            }

            $command = [
                'mysqldump',
                "--host={$host}",
                "--port={$port}",
                "--user={$username}",
                "--password={$password}",
                '--single-transaction',
                '--quick',
                '--routines',
                '--events',
                '--triggers',
                "--result-file={$dumpPath}",
                $database
            ];

            $process = new Process($command);
            $process->setTimeout(300);
            $process->run();

            if (! $process->isSuccessful()) {
                Log::error('Database backup failed', [
                    'error' => $process->getErrorOutput()
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Database backup failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function zipUploads(string $zipPath): bool
    {
        try {
            $uploadsRoot = storage_path('app/public/uploads');

            if (! File::exists($uploadsRoot)) {
                File::ensureDirectoryExists(dirname($zipPath));
                File::put($zipPath, '');
                return true;
            }

            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Could not create uploads zip.');
            }

            $files = File::allFiles($uploadsRoot);
            foreach ($files as $file) {
                $relativePath = str_replace($uploadsRoot . DIRECTORY_SEPARATOR, '', $file->getRealPath());
                $zip->addFile($file->getRealPath(), $relativePath);
            }

            $zip->close();
            return true;
        } catch (\Throwable $e) {
            Log::error('Uploads backup failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function writeMetadata(string $path, string $dbDumpPath, string $uploadsZipPath): void
    {
        try {
            $metadata = [
                'created_at' => now()->toIso8601String(),
                'database_dump' => [
                    'path' => $dbDumpPath,
                    'size_bytes' => File::exists($dbDumpPath) ? File::size($dbDumpPath) : 0
                ],
                'uploads_zip' => [
                    'path' => $uploadsZipPath,
                    'size_bytes' => File::exists($uploadsZipPath) ? File::size($uploadsZipPath) : 0
                ],
                'counts' => [
                    'users' => DB::table('users')->count(),
                    'posts' => DB::table('posts')->count(),
                    'comments' => DB::table('comments')->count(),
                    'messages' => DB::table('messages')->count()
                ]
            ];

            File::put($path, json_encode($metadata, JSON_PRETTY_PRINT));
        } catch (\Throwable $e) {
            Log::warning('Backup metadata write failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function applyRetention(string $dbDir, string $uploadsDir, int $keep): void
    {
        $this->trimDirectory($dbDir, $keep, 'sql');
        $this->trimDirectory($uploadsDir, $keep, 'zip');
        $this->trimDirectory(storage_path('backups'), $keep, 'json');
    }

    private function trimDirectory(string $dir, int $keep, string $extension): void
    {
        $files = collect(File::files($dir))
            ->filter(fn ($file) => strtolower($file->getExtension()) === $extension)
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->values();

        if ($files->count() <= $keep) {
            return;
        }

        $files->slice($keep)->each(function ($file) {
            File::delete($file->getRealPath());
        });
    }
}
