<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RotateLogCommand extends Command
{
    protected $signature = 'log:rotate';
    protected $description = 'Archives current log files (laravel.log and browser.log) to backup files and wipes them clean.';

    public function handle(): int
    {
        // Define the logs we want to rotate
        $logFiles = ['laravel.log', 'browser.log'];
        $timestamp = now()->format('Y-m-d_His');

        foreach ($logFiles as $logFile) {
            $logPath = storage_path("logs/{$logFile}");

            if (!File::exists($logPath)) {
                $this->warn("No {$logFile} file found.");
                continue;
            }

            $nameWithoutExt = pathinfo($logFile, PATHINFO_FILENAME);
            $backupPath = storage_path("logs/{$nameWithoutExt}_backup_{$timestamp}.log");

            // Copy current log to backup
            File::copy($logPath, $backupPath);

            // Wipe the active log clean
            File::put($logPath, '');

            $this->info("Archived {$logFile} to: {$backupPath}");
            $this->info("{$logFile} wiped clean for fresh data.");
        }

        return self::SUCCESS;
    }
}
