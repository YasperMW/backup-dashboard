<?php

namespace App\Services;

use phpseclib3\Net\SFTP;
use phpseclib3\Net\SSH2;
use Illuminate\Support\Facades\Log;

class LinuxBackupService
{
    protected string $host;
    protected string $user;
    protected string $pass;
    protected string $remotePath;

    public function __construct()
    {
        $this->host = config('backup.linux_host');
        $this->user = config('backup.linux_user');
        $this->pass = config('backup.linux_pass');
        $this->remotePath = rtrim(config('backup.remote_path'), '/');
    }

    public function uploadFile(string $localPath): string
    {
        $filename = basename($localPath);
        $remoteFile = $this->remotePath . '/' . $filename;

        $sftp = new SFTP($this->host);
        if (!$sftp->login($this->user, $this->pass)) {
            throw new \Exception("SFTP login failed");
        }

        $sftp->put($remoteFile, file_get_contents($localPath));

        // Make immutable
        $ssh = new SSH2($this->host);
        if (!$ssh->login($this->user, $this->pass)) {
            throw new \Exception("SSH login failed");
        }
        $ssh->exec("sudo chattr +i " . escapeshellarg($remoteFile));

        return $remoteFile;
    }

    public function deleteFile(string $remoteFile): bool
    {
        $sftp = new SFTP($this->host);
        if (!$sftp->login($this->user, $this->pass)) {
            throw new \Exception("SFTP login failed");
        }

        if ($sftp->file_exists($remoteFile)) {
            // Unlock immutable file first
            $ssh = new SSH2($this->host);
            if (!$ssh->login($this->user, $this->pass)) {
                throw new \Exception("SSH login failed");
            }

            $ssh->exec("sudo chattr -i " . escapeshellarg($remoteFile));

            // Delete via SFTP
            return $sftp->delete($remoteFile);
        }

        return false; // File does not exist
    }

    
 

    /**
     * Download a remote file to a local path
     *
     * @param string $remoteFile Full remote file path
     * @param string $localPath  Full local path where file will be saved
     * @return bool True on success, false on failure
     * @throws \Exception
     */
    public function downloadFile(string $remoteFile, string $localPath): bool
    {
        $sftp = new SFTP($this->host);
        if (!$sftp->login($this->user, $this->pass)) {
            Log::error('SFTP login failed', ['user' => $this->user, 'host' => $this->host]);
            return false;
        }

        Log::info('SFTP connected', ['host' => $this->host, 'user' => $this->user]);

        $exists = $sftp->file_exists($remoteFile);
        Log::info('SFTP file_exists check', ['remoteFile' => $remoteFile, 'exists' => $exists]);

        if (!$exists) return false;

        $success = $sftp->get($remoteFile, $localPath);
        Log::info('SFTP get attempt', ['remoteFile' => $remoteFile, 'localPath' => $localPath, 'success' => $success]);

        return $success;
    }

    /**
     * Perform a lightweight reachability check to the remote SSH host (TCP/22).
     * Uses a short timeout and never throws; returns boolean instead.
     */
    private function hasInternetConnection(): bool
    {
        // Check if we can resolve a domain first (tests DNS)
        if (gethostbyname('google.com') === 'google.com') {
            return false; // DNS resolution failed
        }
        
        // Check if we can connect to a reliable service (Google DNS on port 53)
        $sock = @fsockopen('8.8.8.8', 53, $errno, $errstr, 3);
        if ($sock === false) {
            return false;
        }
        
        fclose($sock);
        return true;
    }

    /**
     * Get the remote backup path
     */
    public function getRemotePath(): string
    {
        return $this->remotePath;
    }

    public function isReachable(int $timeoutSeconds = 5): bool
    {
        $host = config('backup.linux_host');
        $port = 22;

        if (!$this->hasInternetConnection()) {
            return false;
        }
        $target = sprintf('tcp://%s:%d', $host, $port);
        $errstr = '';
        // Suppress warnings and handle via errno/errstr to avoid bubbling errors to UI
        $conn = @stream_socket_client(
            $target,
            $errno,
            $errstr,
            $timeoutSeconds,
            STREAM_CLIENT_CONNECT
        );

        if ($conn !== false) {
            // Try to read SSH banner to confirm it's really an SSH server
            stream_set_timeout($conn, $timeoutSeconds);
            $banner = @fgets($conn, 255); // SSH servers typically send banner immediately
            $trimmed = is_string($banner) ? trim($banner) : '';
            $isSsh = false;
            if ($trimmed !== '') {
                if (function_exists('str_starts_with')) {
                    $isSsh = str_starts_with($trimmed, 'SSH-');
                } else {
                    $isSsh = substr($trimmed, 0, 4) === 'SSH-';
                }
            }
            Log::debug('Reachability banner check', [
                'host' => $this->host,
                'port' => 22,
                'banner' => $trimmed,
                'is_ssh' => $isSsh,
            ]);
            fclose($conn);
            return $isSsh;
        }

        Log::debug('Remote host reachability check failed', [
            'host' => $this->host,
            'port' => 22,
            'errno' => $errno,
            'error' => $errstr,
        ]);
        return false;
    }
}
