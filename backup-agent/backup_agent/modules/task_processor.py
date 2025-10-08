import os
import logging
import time
from typing import Dict, List, Optional
from .backup_manager import BackupManager
from . import file_probe
try:
    import paramiko  # type: ignore
except Exception:
    paramiko = None  # type: ignore

class TaskProcessor:
    def __init__(self, agent, config: Dict):
        """
        Initialize the task processor
        :param agent: BackupAgent instance
        :param config: Configuration dictionary
        """
        self.agent = agent
        self.config = config
        self.backup_manager = BackupManager(agent.agent_id, config.get('backup_dir', 'data/backups'), config)
        self.running = False
        self.logger = logging.getLogger('task_processor')
        
        # Ensure required directories exist
        os.makedirs(config.get('backup_dir', 'data/backups'), exist_ok=True)
        os.makedirs('logs', exist_ok=True)

    def start(self) -> None:
        """Start the task processing loop"""
        self.running = True
        self.logger.info("Starting task processor")
        
        while self.running:
            try:
                # Send heartbeat to server so UI sees agent as online
                self._heartbeat()
                self._process_tasks()
                # Poll interval: default 1s, minimum 1s
                interval = self.config.get('poll_interval', 1)
                try:
                    interval = float(interval)
                except Exception:
                    interval = 1.0
                if interval < 1.0:
                    interval = 1.0
                time.sleep(interval)
            except KeyboardInterrupt:
                self.logger.info("Received keyboard interrupt, shutting down...")
                self.running = False
            except Exception as e:
                self.logger.error(f"Error in task processor: {str(e)}", exc_info=True)
                time.sleep(60)  # Wait before retrying

    def stop(self) -> None:
        """Stop the task processing loop"""
        self.running = False
        self.logger.info("Task processor stopped")

    def _process_tasks(self) -> None:
        """Fetch and process pending tasks from the server"""
        try:
            # Opportunistic heartbeat before fetching tasks
            self._heartbeat()
            # Get pending tasks from the server
            tasks = self.agent.get_tasks()
            if not tasks:
                return
                
            self.logger.info(f"Found {len(tasks)} pending tasks")
            
            for task in tasks:
                try:
                    self._process_task(task)
                except Exception as e:
                    self.logger.error(f"Error processing task {task.get('id')}: {str(e)}", exc_info=True)
                    # Update task status to failed
                    self._update_task_status(task['id'], 'failed', {
                        'error': str(e),
                        'timestamp': time.strftime('%Y-%m-%dT%H:%M:%SZ', time.gmtime())
                    })
                    
        except Exception as e:
            self.logger.error(f"Error fetching tasks: {str(e)}", exc_info=True)
            raise

    def _heartbeat(self) -> None:
        """Post a heartbeat to the server; ignores failures."""
        try:
            resp = self.agent.session.post(f"{self.agent.server_url}/api/agent/heartbeat")
            # Do not raise; heartbeat is best-effort
            if resp.status_code >= 400:
                self.logger.debug(f"Heartbeat non-OK: {resp.status_code}")
        except Exception:
            # Suppress heartbeat errors
            pass

    def _process_task(self, task: Dict) -> None:
        """Process a single backup task"""
        task_id = task['id']
        self.logger.info(f"Processing task {task_id}: {task.get('name', 'Unnamed task')}")
        
        try:
            # Update task status to 'in_progress'
            self._update_task_status(task_id, 'in_progress')
            
            # Process the task based on its type
            if task['type'] == 'backup':
                result = self._process_backup_task(task)
            elif task['type'] == 'restore':
                result = self._process_restore_task(task)
            elif task['type'] == 'file_check':
                result = self._process_file_check_task(task)
            elif task['type'] == 'ping':
                # Immediately respond as completed
                details = {'phase': 'completed', 'pong': True, 'ts': time.strftime('%Y-%m-%dT%H:%M:%SZ', time.gmtime())}
                self._update_task_status(task_id, 'completed', {'details': details})
                result = {'status': 'completed', 'details': details}
            elif task['type'] == 'remote_file_check':
                result = self._process_remote_file_check_task(task)
            elif task['type'] == 'integrity_check':
                result = self._process_integrity_check_task(task)
            else:
                raise ValueError(f"Unsupported task type: {task['type']}")
            
            # Update task status based on result
            status = 'completed' if result.get('status') == 'completed' else 'failed'
            self._update_task_status(task_id, status, result)
            
        except Exception as e:
            self.logger.error(f"Error processing task {task_id}: {str(e)}", exc_info=True)
            self._update_task_status(task_id, 'failed', {
                'error': str(e),
                'timestamp': time.strftime('%Y-%m-%dT%H:%M:%SZ', time.gmtime())
            })
            raise

    def _process_backup_task(self, task: Dict) -> Dict:
        """Process a backup task"""
        self.logger.info(f"Starting backup task: {task.get('name', 'Unnamed backup')}")
        
        # Perform the backup
        # Make current task available to the backup manager for context (e.g., excludes)
        try:
            if isinstance(self.backup_manager.config, dict):
                self.backup_manager.config['last_task'] = task
        except Exception:
            pass
        result = self.backup_manager.create_backup(task)
        
        # If backup was successful, verify it
        if result['status'] == 'completed' and task.get('verify', False):
            if not self.backup_manager.verify_backup(result['backup_path']):
                raise Exception("Backup verification failed")
        
        return result

    def _process_file_check_task(self, task: Dict) -> Dict:
        """Process a file existence check task"""
        opts = task.get('options') or {}
        file_info = (opts.get('file') or {})
        directory = file_info.get('directory') or ''
        filename = file_info.get('filename') or ''
        target = os.path.join(directory, filename) if filename else directory
        exists = False
        try:
            exists = file_probe.file_exists(target)
            details = {'phase': 'completed', 'exists': bool(exists), 'path': target}
            self._update_task_status(task['id'], 'completed', {'details': details})
            return {'status': 'completed', 'exists': bool(exists), 'path': target}
        except Exception as e:
            self._update_task_status(task['id'], 'failed', {'error': str(e)})
            return {'status': 'failed', 'error': str(e)}

    def _process_integrity_check_task(self, task: Dict) -> Dict:
        """Verify integrity by computing SHA-256 of the encrypted archive and comparing to expected_hash.

        Expects options:
          - archive: { type: 'local'|'remote', directory, filename }
          - expected_hash: str
          - remote: { host, user, pass, path } when archive.type == 'remote'
        """
        opts = task.get('options') or {}
        archive = (opts.get('archive') or {})
        expected = (opts.get('expected_hash') or '')
        remote = (opts.get('remote') or {})
        if not archive.get('filename'):
            return {'status': 'failed', 'error': 'Missing archive filename for integrity check'}
        # Determine local path to check
        tmp_path = None
        try:
            if archive.get('type') == 'remote':
                # Build remote full path and download to temp
                import tempfile, os
                remote_full = os.path.join(archive.get('directory') or remote.get('path') or '', archive['filename'])
                fd, tmp_local = tempfile.mkstemp(suffix='.enc')
                import os as _os
                _os.close(fd)
                self.backup_manager._download_remote(remote_full, tmp_local, remote)
                tmp_path = tmp_local
                local_to_check = tmp_local
            else:
                import os
                local_to_check = os.path.join(archive.get('directory') or '', archive['filename'])
                if not os.path.isfile(local_to_check):
                    return {'status': 'failed', 'error': f'Local archive not found: {local_to_check}'}
            # Compute checksum using backup_manager helper
            actual = self.backup_manager._calculate_checksum(local_to_check)
            ok = (expected == actual) if expected else True
            details = {'phase': 'completed', 'ok': ok, 'expected': expected, 'actual': actual}
            self._update_task_status(task['id'], 'completed', {'details': details})
            return {'status': 'completed', 'ok': ok, 'expected': expected, 'actual': actual}
        except Exception as e:
            self._update_task_status(task['id'], 'failed', {'error': str(e)})
            return {'status': 'failed', 'error': str(e)}
        finally:
            # Clean up temp file if we downloaded
            if tmp_path:
                try:
                    import os
                    if os.path.exists(tmp_path):
                        os.remove(tmp_path)
                except Exception:
                    pass

    def _process_restore_task(self, task: Dict) -> Dict:
        """Process a restore task"""
        self.logger.info(f"Starting restore task: {task.get('name', 'Unnamed restore')}")
        def progress_cb(details):
            try:
                # post an in_progress update with details so UI can show phase
                self._update_task_status(task['id'], 'in_progress', {'details': details})
            except Exception:
                pass
        result = self.backup_manager.restore_backup(task, progress_cb=progress_cb)
        return result

    def _process_remote_file_check_task(self, task: Dict) -> Dict:
        """Check existence of a remote file via SFTP using provided remote config."""
        if paramiko is None:
            return {'status': 'failed', 'error': 'Paramiko not installed on agent'}
        opts = task.get('options') or {}
        remote = (opts.get('remote') or {})
        file_info = (opts.get('file') or {})
        # Normalize directory to POSIX separators for SFTP
        directory = (file_info.get('directory') or '').replace('\\', '/').rstrip('/')
        filename = file_info.get('filename') or ''
        if not (remote.get('host') and remote.get('user')):
            return {'status': 'failed', 'error': 'Missing remote host/user config'}
        host = remote.get('host')
        username = remote.get('user')
        password = remote.get('pass')
        # Build candidate paths: prefer explicit directory+filename, fallback to remote['path']+filename
        candidate_paths = []
        if filename:
            if directory:
                candidate_paths.append(directory + ('/' if not directory.endswith('/') else '') + filename)
            rp = (remote.get('path') or '').replace('\\', '/').rstrip('/')
            if rp:
                candidate_paths.append(rp + '/' + filename)
        else:
            if directory:
                candidate_paths.append(directory)
        # Ensure at least one candidate
        if not candidate_paths:
            candidate_paths.append(directory)
        try:
            transport = paramiko.Transport((host, 22))
            if password:
                transport.connect(username=username, password=password)
            else:
                # Try key auth
                pkey = None
                try:
                    pkey = paramiko.RSAKey.from_private_key_file(os.path.expanduser('~/.ssh/id_rsa'))
                except Exception:
                    pass
                if pkey is None:
                    return {'status': 'failed', 'error': 'No password provided and no SSH key found'}
                transport.connect(username=username, pkey=pkey)
            sftp = paramiko.SFTPClient.from_transport(transport)
            exists = False
            checked_path = candidate_paths[0]
            try:
                # Try candidates in order
                for p in candidate_paths:
                    self.logger.info(f"Checking remote path via SFTP: {p}")
                    try:
                        sftp.stat(p)
                        exists = True
                        checked_path = p
                        break
                    except IOError:
                        exists = False
                        checked_path = p
                        continue
            finally:
                sftp.close()
                transport.close()
            details = {'phase': 'completed', 'exists': bool(exists), 'path': checked_path}
            self._update_task_status(task['id'], 'completed', {'details': details})
            return {'status': 'completed', 'exists': bool(exists), 'path': checked_path}
        except Exception as e:
            self._update_task_status(task['id'], 'failed', {'error': str(e)})
            return {'status': 'failed', 'error': str(e)}

    def _update_task_status(self, task_id: str, status: str, data: Dict = None) -> bool:
        """Update task status on the server (matches Laravel controller)"""
        if data is None:
            data = {}

        # Build payload according to AgentTaskController@updateTaskStatus
        payload: Dict = {'status': status}
        # Optional fields if provided
        if 'error' in data and data['error'] is not None:
            payload['error'] = data['error']
        if 'files_processed' in data and data['files_processed'] is not None:
            payload['files_processed'] = data['files_processed']
        if 'size_processed' in data and data['size_processed'] is not None:
            payload['size_processed'] = data['size_processed']
        if 'details' in data and data['details'] is not None:
            payload['details'] = data['details']
        # Final artifact metadata (added by backup_manager)
        if 'backup_path' in data and data['backup_path'] is not None:
            payload['backup_path'] = data['backup_path']
        if 'size' in data and data['size'] is not None:
            payload['size'] = data['size']
        if 'checksum' in data and data['checksum'] is not None:
            payload['checksum'] = data['checksum']
        # Remote artifact location (when uploaded via SFTP)
        if 'remote_path' in data and data['remote_path'] is not None:
            payload['remote_path'] = data['remote_path']

        try:
            response = self.agent.session.post(
                f"{self.agent.server_url}/api/agent/tasks/{task_id}/status",
                json=payload
            )
            response.raise_for_status()
            return True
        except Exception as e:
            self.logger.error(f"Failed to update task status: {str(e)}")
            return False
