import os
import shutil
import hashlib
import logging
from datetime import datetime
from typing import Dict, List, Optional, Tuple
import json
import subprocess
import shutil as _shutil
from typing import Any
import tempfile
import zipfile
import sys
import stat
import time
import fnmatch

# Optional dependency for SFTP uploads
try:
    import paramiko  # type: ignore
except Exception:  # pragma: no cover
    paramiko = None  # type: ignore

class BackupManager:
    def __init__(self, agent_id: str, base_path: str = 'data/backups', config: Optional[Dict[str, Any]] = None):
        """
        Initialize the backup manager
        :param agent_id: ID of the agent
        :param base_path: Base path for storing backups
        """
        self.agent_id = agent_id
        self.base_path = os.path.abspath(base_path)
        self.config = config or {}
        self.logger = self._setup_logging()
        os.makedirs(self.base_path, exist_ok=True)
        # Store persisted manifests outside of encrypted archives for incremental diffs
        self.manifest_dir = os.path.join(os.path.dirname(self.base_path), 'manifests')
        os.makedirs(self.manifest_dir, exist_ok=True)

    def _setup_logging(self) -> logging.Logger:
        """Set up logging configuration without duplicating handlers"""
        log_dir = os.path.join(os.path.dirname(self.base_path), 'logs')
        os.makedirs(log_dir, exist_ok=True)
        
        logger = logging.getLogger('backup_manager')
        logger.setLevel(logging.INFO)
        logger.propagate = False
        
        if not logger.handlers:
            # Create file handler
            log_file = os.path.join(log_dir, f'backup_{datetime.now().strftime("%Y%m%d")}.log')
            file_handler = logging.FileHandler(log_file)
            file_handler.setLevel(logging.INFO)
            
            # Create console handler
            console_handler = logging.StreamHandler()
            console_handler.setLevel(logging.INFO)
            
            # Create formatter
            formatter = logging.Formatter('%(asctime)s - %(name)s - %(levelname)s - %(message)s')
            file_handler.setFormatter(formatter)
            console_handler.setFormatter(formatter)
            
            # Add handlers once
            logger.addHandler(file_handler)
            logger.addHandler(console_handler)
        
        return logger

    # ------------------- FS helpers (Windows-safe delete) -------------------
    def _safe_unlink(self, path: str, retries: int = 3, delay: float = 0.2) -> None:
        """Attempt to delete a file, clearing read-only attribute and retrying on Windows."""
        if not path:
            return
        for i in range(retries):
            try:
                if os.path.exists(path):
                    # Clear read-only attribute on Windows
                    if sys.platform.startswith('win'):
                        try:
                            os.chmod(path, stat.S_IWRITE | stat.S_IREAD)
                        except Exception:
                            pass
                    os.remove(path)
                return
            except PermissionError:
                time.sleep(delay)
            except Exception:
                # Best-effort: break if it is gone or cannot be handled
                break

    def _onerror_rmtree(self, func, path, exc_info):
        """Error handler for shutil.rmtree to handle read-only files on Windows."""
        try:
            os.chmod(path, stat.S_IWRITE | stat.S_IREAD)
            func(path)
        except Exception:
            pass

    def _safe_rmtree(self, path: str) -> None:
        """Remove a directory tree safely, including Windows read-only files."""
        if path and os.path.isdir(path):
            _shutil.rmtree(path, onerror=self._onerror_rmtree)

    # ------------------- Utility: Resolve OpenSSL path (Windows-friendly) -------------------
    def _resolve_openssl(self) -> str:
        """Return the OpenSSL executable name/path. On Windows, try common install paths if not in PATH.

        Checks in order:
          0) Config key openssl_path (absolute path)
          1) Environment variable AGENT_OPENSSL_PATH
          2) 'openssl' (in PATH)
          3) Common Windows install locations
        """
        # Config override
        cfg_path = (self.config or {}).get('openssl_path')
        if cfg_path and os.path.exists(cfg_path):
            return cfg_path
        # Explicit override
        env_path = os.environ.get('AGENT_OPENSSL_PATH')
        if env_path and os.path.exists(env_path):
            return env_path

        # Default
        candidate = 'openssl'
        if sys.platform.startswith('win'):
            # Probe typical Windows locations
            common_paths = [
                r"C:\\Program Files\\OpenSSL-Win64\\bin\\openssl.exe",
                r"C:\\Program Files\\OpenSSL-Win32\\bin\\openssl.exe",
                r"C:\\Program Files\\Git\\usr\\bin\\openssl.exe",
                r"C:\\OpenSSL-Win64\\bin\\openssl.exe",
                r"C:\\OpenSSL-Win32\\bin\\openssl.exe",
            ]
            for p in common_paths:
                if os.path.exists(p):
                    return p
        return candidate

    def create_backup(self, task: Dict) -> Dict:
        """
        Create a backup based on the task
        :param task: Dictionary containing backup task details
        :return: Dictionary with backup results
        """
        try:
            source = task.get('source_path')
            # Prefer the destination provided by the job; fallback to agent base path
            destination_root = task.get('destination_path') or self.base_path
            # Use Laravel field 'backup_type' (e.g., 'full' | 'incremental')
            backup_type = task.get('backup_type', 'full')
            
            if not source or not destination_root:
                raise ValueError("Source and destination paths are required")
            
            self.logger.info(f"Starting {backup_type} backup from {source} to {destination_root}")
            
            # Create backup directory with timestamp
            timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
            backup_dir = os.path.join(os.path.abspath(destination_root), f"{task['id']}_{timestamp}")
            os.makedirs(backup_dir, exist_ok=True)
            
            # Perform the backup based on type
            if backup_type == 'full':
                self._full_backup(source, backup_dir)
                previous_manifest = None
            elif backup_type == 'incremental':
                # Load previous manifest for this source+destination if available
                previous_manifest = self._load_previous_manifest(source, destination_root)
                self._incremental_backup(source, backup_dir, previous_manifest)
            else:
                raise ValueError(f"Unsupported backup type: {backup_type}")
            
            # Determine archive base name and paths
            source_name = os.path.basename(os.path.abspath(source)) or 'backup'
            archive_base = f"{source_name}_{backup_type}_{timestamp}"
            zip_base_path = os.path.join(os.path.abspath(destination_root), archive_base)

            # Zip the backup directory -> zip_base_path + '.zip'
            zip_path = self._zip_directory(backup_dir, zip_base_path)

            # Optional encryption based on options.encryption
            enc_config = (task.get('options') or {}).get('encryption') or {}
            final_path = zip_path
            if enc_config.get('enabled'):
                algo = (enc_config.get('algorithm') or 'AES-256-CBC').lower().replace('_', '-')
                password = enc_config.get('password')
                key = enc_config.get('key')
                iv = enc_config.get('iv')
                enc_path = zip_path + '.enc'
                try:
                    self._encrypt_file(zip_path, enc_path, algo, password=password, key=key, iv=iv)
                    final_path = enc_path
                    # Remove the original zip after successful encryption
                    try:
                        os.remove(zip_path)
                    except Exception as re:
                        self.logger.warning(f"Failed to remove temp zip: {zip_path}: {re}")
                except Exception as ee:
                    self.logger.error(f"Encryption failed: {ee}")
                    # If encryption fails, we still return the zip
                    final_path = zip_path

            # Create backup manifest (includes checksum for files in backup_dir)
            manifest = self._create_manifest(backup_dir, task)
            # Persist manifest for future incremental runs
            try:
                self._save_manifest(manifest, source, destination_root)
            except Exception as sm_err:
                self.logger.warning(f"Failed to persist manifest: {sm_err}")

            # Compute final file size and checksum
            final_size = os.path.getsize(final_path)
            final_checksum = self._calculate_checksum(final_path)

            # Optional remote transfer
            remote_path_result: Optional[str] = None
            storage_location = (task.get('options') or {}).get('storage_location')
            remote_cfg = (task.get('options') or {}).get('remote') or {}
            if storage_location in ['remote', 'both'] and remote_cfg.get('host') and remote_cfg.get('user') and remote_cfg.get('path'):
                try:
                    remote_path_result = self._transfer_remote(final_path, remote_cfg)
                    self.logger.info(f"Uploaded backup to remote: {remote_path_result}")
                    # If storage_location == 'remote', you may choose to remove local final_path
                    # Keeping local by default for safety
                except Exception as up_err:
                    self.logger.error(f"Remote upload failed: {up_err}")
                    if storage_location == 'remote':
                        # If remote only requested and upload failed, mark as failed
                        raise

            # Cleanup working directory
            try:
                shutil.rmtree(backup_dir)
            except Exception as ce:
                self.logger.warning(f"Failed to remove working directory {backup_dir}: {ce}")

            self.logger.info(f"Backup completed successfully: {final_path}")
            return {
                'status': 'completed',
                'backup_path': final_path,
                'size': final_size,
                'checksum': final_checksum,
                'manifest': manifest,
                'remote_path': remote_path_result,
                'timestamp': datetime.now().isoformat()
            }
            
        except Exception as e:
            self.logger.error(f"Backup failed: {str(e)}", exc_info=True)
            return {
                'status': 'failed',
                'error': str(e),
                'timestamp': datetime.now().isoformat()
            }

    # ------------------- Restore -------------------
    def restore_backup(self, task: Dict, progress_cb: Optional[Any] = None) -> Dict:
        """Restore a backup archive to the specified path.

        Expects task.options to include:
          - encryption: { algorithm, password, key_version }
          - archive: { type: 'local'|'remote', directory, filename }
          - restore: { path, overwrite: bool, preserve_permissions: bool }
          - remote: { host, user, pass, path } (for remote download)
        """
        try:
            options = task.get('options') or {}
            enc = options.get('encryption') or {}
            archive = options.get('archive') or {}
            restore_opts = options.get('restore') or {}
            remote_cfg = options.get('remote') or {}

            if progress_cb:
                progress_cb({'phase': 'pending', 'message': 'Waiting to start restore'})

            # Validate inputs
            if not archive.get('filename'):
                raise ValueError('Archive filename is required for restore')
            restore_path = restore_opts.get('path')
            if not restore_path:
                raise ValueError('Restore path is required')
            overwrite = bool(restore_opts.get('overwrite', False))
            preserve_perms = bool(restore_opts.get('preserve_permissions', False))

            # Determine local archive path
            local_archive_path = None
            if archive.get('type') == 'remote':
                if progress_cb:
                    progress_cb({'phase': 'downloading', 'message': 'Downloading encrypted archive from remote'})
                # Download from remote to temp file
                if not (remote_cfg.get('host') and remote_cfg.get('user') and remote_cfg.get('path')):
                    raise ValueError('Remote configuration is required to download remote archive')
                remote_full = os.path.join(archive.get('directory') or remote_cfg.get('path'), archive['filename'])
                fd, tmp_local = tempfile.mkstemp(suffix='.zip.enc')
                os.close(fd)
                try:
                    self._download_remote(remote_full, tmp_local, remote_cfg)
                except Exception as de:
                    # Cleanup temp file
                    try:
                        os.remove(tmp_local)
                    except Exception:
                        pass
                    raise de
                local_archive_path = tmp_local
            else:
                local_archive_path = os.path.join(archive.get('directory') or '', archive['filename'])
                if not os.path.isfile(local_archive_path):
                    raise FileNotFoundError(f"Local archive not found: {local_archive_path}")

            # Decrypt to temp zip
            password = enc.get('password')
            if not password:
                raise ValueError('Encryption password is required for restore')
            algo = (enc.get('algorithm') or 'AES-256-CBC').lower().replace('_', '-')
            fd_out, tmp_zip = tempfile.mkstemp(suffix='.zip')
            os.close(fd_out)
            try:
                if progress_cb:
                    progress_cb({'phase': 'decrypting', 'message': 'Decrypting archive'})
                self._decrypt_file(local_archive_path, tmp_zip, algo, password=password)
            finally:
                # If the archive was downloaded, remove it
                if archive.get('type') == 'remote':
                    self._safe_unlink(local_archive_path)

            # Extract zip to restore_path
            restored_files = 0
            if progress_cb:
                progress_cb({'phase': 'extracting', 'message': 'Extracting files'})
            with zipfile.ZipFile(tmp_zip, 'r') as zf:
                for info in zf.infolist():
                    out_path = os.path.join(restore_path, info.filename)
                    if info.is_dir():
                        os.makedirs(out_path, exist_ok=True)
                        continue
                    # Ensure parent dir
                    os.makedirs(os.path.dirname(out_path), exist_ok=True)
                    if os.path.exists(out_path) and not overwrite:
                        continue
                    # Extract to temp then move to preserve permissions if needed
                    with zf.open(info, 'r') as src, open(out_path, 'wb') as dst:
                        shutil.copyfileobj(src, dst)
                    restored_files += 1
                    if preserve_perms:
                        # Apply permission bits if available
                        perm = (info.external_attr >> 16) & 0o7777
                        if perm:
                            try:
                                os.chmod(out_path, perm)
                            except Exception:
                                pass

            # Remove temp zip
            self._safe_unlink(tmp_zip)

            if progress_cb:
                progress_cb({'phase': 'completed', 'message': 'Restore completed'})
            return {
                'status': 'completed',
                'restored_files': restored_files,
                'restore_path': restore_path,
                'timestamp': datetime.now().isoformat()
            }
        except Exception as e:
            self.logger.error(f"Restore failed: {str(e)}", exc_info=True)
            if progress_cb:
                progress_cb({'phase': 'failed', 'message': str(e)})
            return {
                'status': 'failed',
                'error': str(e),
                'timestamp': datetime.now().isoformat()
            }

    def _full_backup(self, source: str, destination: str) -> None:
        """Perform a full backup from source to destination"""
        self.logger.info(f"Starting full backup from {source} to {destination}")
        
        # Resolve excludes from config/task if present on self.config under last_task
        task = (self.config or {}).get('last_task') or {}
        excludes = self._build_excludes(task)

        def excluded(rel: str) -> bool:
            return self._path_is_excluded(rel, excludes)

        if os.path.isfile(source):
            os.makedirs(destination, exist_ok=True)
            dst = os.path.join(destination, os.path.basename(source))
            try:
                shutil.copy2(source, dst)
            except PermissionError as pe:
                self.logger.warning(f"Permission denied copying file: {source}: {pe}. Skipping.")
            except Exception as e:
                # Re-raise non-permission errors
                raise e
        else:
            base_name = os.path.basename(os.path.abspath(source)) or 'source'
            dest_root = os.path.join(destination, base_name)
            os.makedirs(dest_root, exist_ok=True)
            base = os.path.abspath(source)
            for root, dirs, files in os.walk(source, topdown=True):
                # Compute relative path from base to root for directory pruning
                rel_dir = os.path.relpath(root, base)
                # Prune excluded directories in-place
                pruned = []
                for d in list(dirs):
                    rel_path = os.path.normpath(os.path.join(rel_dir, d))
                    if excluded(rel_path):
                        pruned.append(d)
                if pruned:
                    self.logger.info(f"Pruning excluded directories: {', '.join(pruned)} under {root}")
                dirs[:] = [d for d in dirs if d not in pruned]

                # Ensure destination directory exists
                out_dir = os.path.join(dest_root, os.path.relpath(root, base))
                os.makedirs(out_dir, exist_ok=True)

                for f in files:
                    rel_path = os.path.normpath(os.path.join(rel_dir, f))
                    if excluded(rel_path):
                        continue
                    src_path = os.path.join(root, f)
                    dst_path = os.path.join(out_dir, f)
                    try:
                        shutil.copy2(src_path, dst_path)
                    except PermissionError as pe:
                        self.logger.warning(f"Permission denied copying: {src_path}: {pe}. Skipping.")
                        continue
                    except FileNotFoundError:
                        # File disappeared during scan; skip
                        continue
                    except Exception as e:
                        # Log and continue (best-effort)
                        self.logger.warning(f"Failed to copy {src_path}: {e}. Skipping.")

    def _incremental_backup(self, source: str, destination: str, previous_manifest: Optional[Dict] = None) -> None:
        """Perform an incremental backup by comparing current filesystem to previous manifest.

        Strategy: copy only files that are new or whose CONTENT changed relative to the previous manifest.
        This uses SHA-256 checksums for accuracy (slower but exact).
        """
        self.logger.info(f"Starting incremental backup from {source}")

        # If no previous manifest, fall back to full backup
        if previous_manifest is None:
            self.logger.warning("No previous manifest found; performing full backup instead")
            return self._full_backup(source, destination)

        # Resolve excludes from config/task if present on self.config under last_task
        task = (self.config or {}).get('last_task') or {}
        excludes = self._build_excludes(task)
        def excluded(rel: str) -> bool:
            return self._path_is_excluded(rel, excludes)

        # Build a quick lookup from previous manifest
        # Normalize previous paths to be relative to the source root, not including
        # the top-level basename that a full backup might have introduced.
        prev_index = {}
        base_name = os.path.basename(os.path.abspath(source)) or ''
        # Prepare both slash variants for robust matching
        bn_fwd = base_name
        bn_back = base_name
        for f in previous_manifest.get('files', []):
            p = f.get('path') or ''
            norm = p.lstrip('.\\/')
            # Strip leading base_name/ if present (both slash styles)
            if base_name:
                if norm.startswith(base_name + '/'):
                    norm = norm[len(base_name) + 1:]
                elif norm.startswith(base_name + '\\'):
                    norm = norm[len(base_name) + 1:]
            prev_index[norm] = {
                'size': f.get('size'),
                'modified': f.get('modified'),
                'checksum': f.get('checksum')
            }

        # Walk current source tree and copy changed/new files
        if os.path.isfile(source):
            rel_path = os.path.basename(source)
            prev = prev_index.get(rel_path)
            try:
                current_checksum = self._calculate_checksum(source)
            except PermissionError as pe:
                self.logger.warning(f"Permission denied reading file: {source}: {pe}. Skipping.")
                return
            if (prev is None) or (current_checksum != prev.get('checksum')):
                os.makedirs(destination, exist_ok=True)
                try:
                    shutil.copy2(source, os.path.join(destination, rel_path))
                except PermissionError as pe:
                    self.logger.warning(f"Permission denied copying file: {source}: {pe}. Skipping.")
                except Exception as e:
                    self.logger.warning(f"Failed to copy file {source}: {e}. Skipping.")
        else:
            base = os.path.abspath(source)
            for root, dirs, files in os.walk(source, topdown=True):
                # Prune excluded directories
                rel_dir = os.path.relpath(root, base)
                pruned = []
                for d in list(dirs):
                    rel_p = os.path.normpath(os.path.join(rel_dir, d))
                    if excluded(rel_p):
                        pruned.append(d)
                if pruned:
                    self.logger.info(f"Pruning excluded directories: {', '.join(pruned)} under {root}")
                dirs[:] = [d for d in dirs if d not in pruned]
                for file in files:
                    file_path = os.path.join(root, file)
                    rel_path = os.path.relpath(file_path, base)
                    if excluded(rel_path):
                        continue
                    try:
                        current_checksum = self._calculate_checksum(file_path)
                    except FileNotFoundError:
                        continue
                    except PermissionError as pe:
                        self.logger.warning(f"Permission denied reading: {file_path}: {pe}. Skipping.")
                        continue
                    prev = prev_index.get(rel_path)
                    if (prev is None) or (current_checksum != prev.get('checksum')):
                        dest_path = os.path.join(destination, rel_path)
                        os.makedirs(os.path.dirname(dest_path), exist_ok=True)
                        try:
                            shutil.copy2(file_path, dest_path)
                        except PermissionError as pe:
                            self.logger.warning(f"Permission denied copying: {file_path}: {pe}. Skipping.")
                            continue
                        except FileNotFoundError:
                            continue
                        except Exception as e:
                            self.logger.warning(f"Failed to copy {file_path}: {e}. Skipping.")

    # ------------------- Excludes helpers -------------------
    def _build_excludes(self, task: Dict) -> List[str]:
        """Build a list of exclude patterns from task.options.excludes with Windows-safe defaults.

        Patterns are matched against paths relative to the source root, using fnmatch.
        Accept both POSIX and Windows separators.
        """
        options = (task or {}).get('options') or {}
        excludes: List[str] = list(options.get('excludes') or [])
        # Add some safe Windows defaults to avoid common Access Denied locations
        if sys.platform.startswith('win'):
            defaults = [
                '**/System Volume Information/**',
                '**/$Recycle.Bin/**',
                '**/Windows/**',
                '**/ProgramData/Microsoft/Windows Defender/**',
                '**/AppData/Local/Packages/**',
            ]
            # Only append defaults that are not already present
            for p in defaults:
                if p not in excludes:
                    excludes.append(p)
        return excludes

    def _path_is_excluded(self, rel_path: str, patterns: List[str]) -> bool:
        """Return True if rel_path matches any of the given glob patterns.

        We check using both forward- and back-slash variants to be robust on Windows.
        """
        if not patterns:
            return False
        norm = rel_path.strip('.\\/')
        alt = norm.replace(os.sep, '/' if os.sep == '\\' else '\\')
        for pat in patterns:
            # normalize pattern to use forward slashes for matching, and also try raw
            p1 = pat.replace('\\', '/')
            if fnmatch.fnmatch(norm.replace('\\', '/'), p1):
                return True
            if fnmatch.fnmatch(norm, pat):
                return True
            if fnmatch.fnmatch(alt, p1) or fnmatch.fnmatch(alt, pat):
                return True
        return False

    def _create_manifest(self, backup_dir: str, task: Dict) -> Dict:
        """Create a manifest file for the backup"""
        manifest = {
            'task_id': task['id'],
            'agent_id': self.agent_id,
            'backup_type': task.get('backup_type', 'full'),
            'start_time': datetime.now().isoformat(),
            'source': task.get('source_path'),
            'destination': backup_dir,
            'files': [],
            'status': 'completed',
            'checksum': None
        }
        
        # Calculate checksum of important files
        checksum = hashlib.sha256()
        
        for root, _, files in os.walk(backup_dir):
            for file in files:
                file_path = os.path.join(root, file)
                rel_path = os.path.relpath(file_path, backup_dir)
                file_info = {
                    'path': rel_path,
                    'size': os.path.getsize(file_path),
                    'modified': os.path.getmtime(file_path),
                    'checksum': self._calculate_checksum(file_path)
                }
                manifest['files'].append(file_info)
                
                # Update overall checksum
                checksum.update(file_info['checksum'].encode())
        
        manifest['checksum'] = checksum.hexdigest()
        manifest['end_time'] = datetime.now().isoformat()
        
        # Save manifest to file
        manifest_path = os.path.join(backup_dir, 'manifest.json')
        with open(manifest_path, 'w') as f:
            json.dump(manifest, f, indent=2)
            
        return manifest

    # Manifest persistence helpers
    def _manifest_key(self, source: str, destination_root: str) -> str:
        key_src = f"{os.path.abspath(source)}|{os.path.abspath(destination_root)}".encode('utf-8')
        return hashlib.sha256(key_src).hexdigest()

    def _manifest_path(self, source: str, destination_root: str) -> str:
        return os.path.join(self.manifest_dir, f"{self._manifest_key(source, destination_root)}.json")

    def _load_previous_manifest(self, source: str, destination_root: str) -> Optional[Dict]:
        path = self._manifest_path(source, destination_root)
        if not os.path.exists(path):
            return None
        try:
            with open(path, 'r') as f:
                return json.load(f)
        except Exception as e:
            self.logger.warning(f"Failed to load previous manifest {path}: {e}")
            return None

    def _save_manifest(self, manifest: Dict, source: str, destination_root: str) -> None:
        path = self._manifest_path(source, destination_root)
        try:
            with open(path, 'w') as f:
                json.dump(manifest, f)
        except Exception as e:
            raise e

    def _zip_directory(self, src_dir: str, base_output_path: str) -> str:
        """Create a ZIP archive of src_dir at base_output_path.zip"""
        # Use shutil.make_archive for simplicity
        archive_path = _shutil.make_archive(base_output_path, 'zip', root_dir=src_dir)
        return archive_path

    def _encrypt_file(self, input_path: str, output_path: str, algorithm: str,
                      password: Optional[str] = None, key: Optional[str] = None, iv: Optional[str] = None) -> None:
        """Encrypt a file using system openssl. Supports password or raw key/iv.

        algorithm examples: 'aes-256-cbc', 'aes-128-cbc'
        password: if provided, uses -pass pass:<password>
        key/iv: if provided (hex), uses -K <key> -iv <iv>
        """
        cipher = algorithm.lower()
        openssl = self._resolve_openssl()
        cmd = [openssl, 'enc', f'-{cipher}', '-salt', '-pbkdf2', '-in', input_path, '-out', output_path]
        if password:
            cmd += ['-pass', f'pass:{password}']
        elif key and iv:
            cmd += ['-K', key, '-iv', iv]
        else:
            raise ValueError('Encryption requires either password or key+iv')

        self.logger.info(f"Encrypting {os.path.basename(input_path)} -> {os.path.basename(output_path)} using {cipher}")
        result = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
        if result.returncode != 0:
            raise RuntimeError(f"OpenSSL error: {result.stderr.strip()}")

    def _decrypt_file(self, input_path: str, output_path: str, algorithm: str, password: Optional[str] = None,
                      key: Optional[str] = None, iv: Optional[str] = None) -> None:
        """Decrypt a file using system openssl to output_path."""
        cipher = algorithm.lower()
        openssl = self._resolve_openssl()
        cmd = [openssl, 'enc', f'-{cipher}', '-d', '-pbkdf2', '-in', input_path, '-out', output_path]
        if password:
            cmd += ['-pass', f'pass:{password}']
        elif key and iv:
            cmd += ['-K', key, '-iv', iv]
        else:
            raise ValueError('Decryption requires either password or key+iv')
        
        self.logger.info(f"Decrypting {os.path.basename(input_path)} -> {os.path.basename(output_path)} using {cipher}")
        result = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
        if result.returncode != 0:
            raise RuntimeError(f"OpenSSL decrypt error: {result.stderr.strip()}")

    def _download_remote(self, remote_file: str, local_target: str, remote_cfg: Dict[str, Any]) -> None:
        """Download remote_file to local_target via SFTP."""
        if paramiko is None:
            raise RuntimeError("Paramiko is not installed. Please install with: pip install paramiko")
        host = remote_cfg.get('host')
        username = remote_cfg.get('user')
        password = remote_cfg.get('pass')
        if not (host and username and remote_file and local_target):
            raise ValueError('Incomplete remote download parameters')
        transport = paramiko.Transport((host, 22))
        try:
            if password:
                transport.connect(username=username, password=password)
            else:
                private_key = None
                try:
                    private_key = paramiko.RSAKey.from_private_key_file(os.path.expanduser('~/.ssh/id_rsa'))
                except Exception:
                    pass
                if private_key is None:
                    raise RuntimeError("No password provided and no default SSH key found for authentication")
                transport.connect(username=username, pkey=private_key)
            sftp = paramiko.SFTPClient.from_transport(transport)
            try:
                sftp.get(remote_file, local_target)
            finally:
                sftp.close()
        finally:
            transport.close()

    def _transfer_remote(self, local_file: str, remote_cfg: Dict[str, Any]) -> str:
        """Upload local_file to remote via SFTP and return the remote path.

        remote_cfg expects keys: host, user, pass, path
        """
        if paramiko is None:
            raise RuntimeError("Paramiko is not installed. Please install with: pip install paramiko")
        if not os.path.isfile(local_file):
            raise FileNotFoundError(f"Local file not found: {local_file}")

        host = remote_cfg.get('host')
        username = remote_cfg.get('user')
        password = remote_cfg.get('pass')
        base_path = (remote_cfg.get('path') or '').rstrip('/')
        if not (host and username and base_path):
            raise ValueError('Incomplete remote upload parameters')

        filename = os.path.basename(local_file)
        remote_dir = base_path
        remote_file = os.path.join(base_path, filename).replace('\\', '/')

        transport = paramiko.Transport((host, 22))
        try:
            if password:
                transport.connect(username=username, password=password)
            else:
                private_key = None
                try:
                    private_key = paramiko.RSAKey.from_private_key_file(os.path.expanduser('~/.ssh/id_rsa'))
                except Exception:
                    pass
                if private_key is None:
                    raise RuntimeError("No password provided and no default SSH key found for authentication")
                transport.connect(username=username, pkey=private_key)

            sftp = paramiko.SFTPClient.from_transport(transport)
            try:
                # Ensure remote directory exists
                try:
                    sftp.stat(remote_dir)
                except IOError:
                    self._sftp_mkdirs(sftp, remote_dir)
                # Upload file
                sftp.put(local_file, remote_file)
            finally:
                sftp.close()
        finally:
            transport.close()

        return remote_file

    def _sftp_mkdirs(self, sftp: Any, remote_dir: str) -> None:
        """Recursively create remote_dir if it doesn't exist"""
        parts = []
        head = remote_dir
        while True:
            head, tail = os.path.split(head)
            if tail:
                parts.insert(0, tail)
            else:
                if head:
                    parts.insert(0, head)
                break
        path = ''
        for p in parts:
            if p in ('', '/'):
                path = p if p else '/'
                continue
            if path in ('', '/'):
                path = os.path.join('/', p)
            else:
                path = os.path.join(path, p)
            try:
                sftp.stat(path)
            except IOError:
                sftp.mkdir(path)

    def _calculate_checksum(self, file_path: str, block_size: int = 65536) -> str:
        """Calculate SHA-256 checksum of a file"""
        sha256 = hashlib.sha256()
        with open(file_path, 'rb') as f:
            for block in iter(lambda: f.read(block_size), b''):
                sha256.update(block)
        return sha256.hexdigest()

    def verify_backup(self, backup_dir: str) -> bool:
        """Verify the integrity of a backup"""
        manifest_path = os.path.join(backup_dir, 'manifest.json')
        if not os.path.exists(manifest_path):
            self.logger.error(f"Manifest file not found in {backup_dir}")
            return False
            
        with open(manifest_path, 'r') as f:
            manifest = json.load(f)
            
        # Verify all files exist and have correct checksums
        for file_info in manifest.get('files', []):
            file_path = os.path.join(backup_dir, file_info['path'])
            if not os.path.exists(file_path):
                self.logger.error(f"File not found: {file_path}")
                return False
                
            if os.path.getsize(file_path) != file_info['size']:
                self.logger.error(f"File size mismatch: {file_path}")
                return False
                
            current_checksum = self._calculate_checksum(file_path)
            if current_checksum != file_info['checksum']:
                self.logger.error(f"Checksum mismatch for file: {file_path}")
                return False
                
        return True
