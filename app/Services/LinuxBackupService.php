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


}
