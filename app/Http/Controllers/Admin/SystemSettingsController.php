<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class SystemSettingsController extends Controller
{
    /**
     * Get all system settings.
     */
    public function index()
    {
        $settings = [
            'maintenance_mode' => app()->isDownForMaintenance(),
            'session_timeout' => config('session.lifetime', 120),
            'last_cache_clear' => Cache::get('last_cache_clear'),
            'last_optimization' => Cache::get('last_optimization'),
            'last_backup' => Cache::get('last_backup'),
            'log_size' => $this->getLogSize(),
        ];

        return response()->json($settings);
    }

    /**
     * Toggle maintenance mode.
     */
    public function toggleMaintenance(Request $request)
    {
        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        if ($request->enabled) {
            // Enable maintenance mode with secret bypass
            $secret = 'admin-access-' . md5(config('app.key'));
            
            Artisan::call('down', [
                '--retry' => 60,
                '--secret' => $secret,
            ]);
            
            // Update .env file with the maintenance URL
            $maintenanceUrl = url($secret);
            $this->updateEnvFile('# MAINTENANCE URL', "# MAINTENANCE URL: {$maintenanceUrl}");
            
            $message = 'Maintenance mode enabled. Admins can still access the application.';
        } else {
            Artisan::call('up');
            $message = 'Maintenance mode disabled';
        }

        return response()->json([
            'message' => $message,
        ]);
    }

    /**
     * Clear all caches.
     */
    public function clearCache()
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        $timestamp = Carbon::now();
        Cache::put('last_cache_clear', $timestamp, now()->addYear());

        return response()->json([
            'message' => 'All caches cleared successfully',
            'last_cache_clear' => $timestamp,
        ]);
    }

    /**
     * Optimize application.
     */
    public function optimize()
    {
        Artisan::call('optimize');
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');

        $timestamp = Carbon::now();
        Cache::put('last_optimization', $timestamp, now()->addYear());

        return response()->json([
            'message' => 'Application optimized successfully',
            'last_optimization' => $timestamp,
        ]);
    }

    /**
     * Get list of log files.
     */
    public function getLogs()
    {
        $logPath = storage_path('logs');
        $logs = [];

        if (File::exists($logPath)) {
            $files = File::files($logPath);
            foreach ($files as $file) {
                $logs[] = [
                    'name' => $file->getFilename(),
                    'size' => $this->formatBytes($file->getSize()),
                    'modified' => Carbon::createFromTimestamp($file->getMTime())->toDateTimeString(),
                ];
            }
        }

        return response()->json(['logs' => $logs]);
    }

    /**
     * Get log file content.
     */
    public function getLogContent(Request $request)
    {
        $request->validate([
            'filename' => 'required|string',
        ]);

        $logPath = storage_path('logs/' . $request->filename);

        if (!File::exists($logPath)) {
            return response()->json(['error' => 'Log file not found'], 404);
        }

        $content = File::get($logPath);

        return response()->json(['content' => $content]);
    }

    /**
     * Clear all log files.
     */
    public function clearLogs()
    {
        $logPath = storage_path('logs');

        if (File::exists($logPath)) {
            $files = File::files($logPath);
            foreach ($files as $file) {
                File::delete($file);
            }
        }

        return response()->json(['message' => 'All logs cleared successfully']);
    }

    /**
     * Update session timeout.
     */
    public function updateSessionTimeout(Request $request)
    {
        $request->validate([
            'timeout' => 'required|integer|min:5|max:1440',
        ]);

        // Update .env file
        $this->updateEnvFile('SESSION_LIFETIME', $request->timeout);

        return response()->json([
            'message' => 'Session timeout updated successfully',
            'session_timeout' => $request->timeout,
        ]);
    }

    /**
     * Create database backup.
     */
    public function createBackup()
    {
        $backupPath = storage_path('app/backups');

        if (!File::exists($backupPath)) {
            File::makeDirectory($backupPath, 0755, true);
        }

        $filename = 'backup-' . date('Y-m-d-His') . '.sql';
        $filepath = $backupPath . '/' . $filename;

        // Simple backup using mysqldump (adjust for your database)
        $command = sprintf(
            'mysqldump -u%s -p%s %s > %s',
            config('database.connections.mysql.username'),
            config('database.connections.mysql.password'),
            config('database.connections.mysql.database'),
            $filepath
        );

        exec($command);

        $timestamp = Carbon::now();
        Cache::put('last_backup', $timestamp, now()->addYear());

        return response()->json([
            'message' => 'Backup created successfully',
            'last_backup' => $timestamp,
            'filename' => $filename,
        ]);
    }

    /**
     * List all backups.
     */
    public function listBackups()
    {
        $backupPath = storage_path('app/backups');
        $backups = [];

        if (File::exists($backupPath)) {
            $files = File::files($backupPath);
            foreach ($files as $file) {
                $backups[] = [
                    'name' => $file->getFilename(),
                    'size' => $this->formatBytes($file->getSize()),
                    'created' => Carbon::createFromTimestamp($file->getMTime())->toDateTimeString(),
                ];
            }
        }

        return response()->json(['backups' => $backups]);
    }

    /**
     * Download a backup file.
     */
    public function downloadBackup($filename)
    {
        $filepath = storage_path('app/backups/' . $filename);

        if (!File::exists($filepath)) {
            return response()->json(['error' => 'Backup file not found'], 404);
        }

        return response()->download($filepath);
    }

    /**
     * Delete a backup file.
     */
    public function deleteBackup($filename)
    {
        $filepath = storage_path('app/backups/' . $filename);

        if (!File::exists($filepath)) {
            return response()->json(['error' => 'Backup file not found'], 404);
        }

        // Security check: ensure filename doesn't contain path traversal
        if (str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            return response()->json(['error' => 'Invalid filename'], 400);
        }

        try {
            File::delete($filepath);
            return response()->json(['message' => 'Backup deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete backup file'], 500);
        }
    }

    /**
     * Get total log size.
     */
    private function getLogSize()
    {
        $logPath = storage_path('logs');
        $totalSize = 0;

        if (File::exists($logPath)) {
            $files = File::files($logPath);
            foreach ($files as $file) {
                $totalSize += $file->getSize();
            }
        }

        return $this->formatBytes($totalSize);
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Update .env file.
     */
    private function updateEnvFile($key, $value)
    {
        $path = base_path('.env');

        if (File::exists($path)) {
            $content = File::get($path);
            $content = preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$value}",
                $content
            );
            File::put($path, $content);
        }
    }
}
