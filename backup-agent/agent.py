#!/usr/bin/env python3
import os
import sys
import json
import time
import hashlib
import getpass
import requests
import socket
import platform
from pathlib import Path
from typing import Dict, Optional, List, Any, Union
import logging
import signal
from backup_agent.modules.task_processor import TaskProcessor

class BackupAgent:
    def __init__(self, server_url: str, config: Dict[str, Any] = None):
        self.server_url = server_url.rstrip('/')
        self.config = config or {}
        self.token = self.config.get('token')
        self.agent_id = self.config.get('agent_id')
        self.session = requests.Session()
        self.task_processor = None
        self.running = False
        
        # Set up logging
        self._setup_logging()
        self.logger = logging.getLogger('agent')
        
        # Set up session headers if token exists
        if self.token:
            self.set_auth_header(self.token)
            
    def _setup_logging(self) -> None:
        """Set up logging configuration"""
        log_dir = Path('logs')
        log_dir.mkdir(exist_ok=True)
        
        logging.basicConfig(
            level=logging.INFO,
            format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
            handlers=[
                logging.FileHandler(log_dir / 'agent.log'),
                logging.StreamHandler()
            ]
        )
    
    def set_auth_header(self, token: str):
        """Set the authorization header with the provided token"""
        self.token = token
        self.session.headers.update({
            'Authorization': f'Bearer {self.token}',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        })
    
    def get_user_token(self, email: str, password: str) -> Optional[str]:
        """Get an API token for the user"""
        try:
            login_url = f"{self.server_url}/api/login"
            response = requests.post(
                login_url,
                json={
                    'email': email,
                    'password': password
                },
                headers={
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            )
            
            if response.status_code == 200:
                data = response.json()
                if 'token' in data:
                    return data['token']
                self.logger.error("No token in response: %s", data)
            
            self.logger.error("Login failed. Status code: %s", response.status_code)
            self.logger.error("Response: %s", response.text)
            return None
            
        except Exception as e:
            self.logger.error("Login error: %s", str(e))
            return None
    
    def register_agent(self, name: str, user_email: str, user_password: str) -> bool:
        """Register a new agent with the server"""
        try:
            self.logger.info("Starting agent registration...")
            
            # First, get a user token
            user_token = self.get_user_token(user_email, user_password)
            if not user_token:
                self.logger.error("Failed to get user token")
                return False
                
            # Get system information
            hostname = socket.gethostname()
            os_info = f"{platform.system()} {platform.release()}"
            
            # Prepare agent data
            data = {
                'name': name,
                'hostname': hostname,
                'os': os_info,
                'version': '1.0.0',
                'capabilities': ['file_backup', 'incremental_backup']
            }
            
            self.logger.info(f"Registering agent with data: {data}")
            
            # Register the agent
            response = self.session.post(
                f"{self.server_url}/api/agent/register",
                json=data,
                headers={
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'Authorization': f'Bearer {user_token}'
                }
            )
            
            response.raise_for_status()
            agent_data = response.json()
            self.logger.debug(f"Registration response: {agent_data}")
            
            # Handle both response formats
            if 'agent' in agent_data and 'token' in agent_data['agent']:
                self.token = agent_data['agent']['token']
                self.agent_id = agent_data['agent'].get('id')
            else:
                self.token = agent_data.get('token')
                self.agent_id = agent_data.get('id')
            
            if not self.token or not self.agent_id:
                error_msg = f"Failed to get agent token or ID from response: {agent_data}"
                self.logger.error(error_msg)
                return False
                
            # Update auth header with the new token
            self.set_auth_header(self.token)
            
            # Update config
            self.config.update({
                'token': self.token,
                'agent_id': self.agent_id,
                'name': name,
                'hostname': hostname,
                'os': os_info,
                'registered_at': time.strftime('%Y-%m-%dT%H:%M:%SZ')
            })
            
            self.logger.info(f"Successfully registered agent with ID: {self.agent_id}")
            return True
            
        except requests.exceptions.RequestException as e:
            error_msg = f"Registration failed: {str(e)}"
            if hasattr(e, 'response') and e.response is not None:
                error_msg += f"\nResponse: {e.response.text}"
            self.logger.error(error_msg, exc_info=True)
            return False
    
    def get_tasks(self) -> List[Dict]:
        """Get pending tasks from the server"""
        try:
            self.logger.debug("Fetching pending tasks...")
            response = self.session.get(
                f"{self.server_url}/api/agent/tasks",
                params={'status': 'pending'}
            )
            response.raise_for_status()
            
            tasks = response.json().get('data', [])
            self.logger.debug(f"Found {len(tasks)} pending tasks")
            return tasks
            
        except requests.exceptions.RequestException as e:
            error_msg = f"Failed to get tasks: {str(e)}"
            if hasattr(e, 'response') and e.response is not None:
                error_msg += f"\nResponse: {e.response.text}"
            self.logger.error(error_msg)
            return []
    
    def update_task_status(self, task_id: int, status: str, error: str = None, 
                         files_processed: int = None, size_processed: int = None) -> bool:
        """Update the status of a backup task"""
        try:
            url = f"{self.server_url}/api/agent/tasks/{task_id}/status"
            data = {'status': status}
            
            if error:
                data['error'] = error
            if files_processed is not None:
                data['files_processed'] = files_processed
            if size_processed is not None:
                data['size_processed'] = size_processed
                
            response = self.session.post(url, json=data)
            response.raise_for_status()
            return True
        except requests.exceptions.RequestException as e:
            self.logger.error(f"Failed to update task status: {str(e)}")
            return False
    
    def upload_backup(self, task_id: int, source_path: str) -> bool:
        """Upload a backup file to the server"""
        try:
            url = f"{self.server_url}/api/agent/backup/upload"
            
            with open(source_path, 'rb') as f:
                files = {
                    'file': (os.path.basename(source_path), f, 'application/octet-stream')
                }
                data = {
                    'task_id': task_id,
                    'checksum': self._calculate_checksum(source_path),
                    'size': os.path.getsize(source_path)
                }
                
                # Use a new session without the JSON content type for file upload
                upload_session = requests.Session()
                upload_session.headers.update({
                    'Authorization': f'Bearer {self.token}'
                })
                
                response = upload_session.post(url, files=files, data=data)
                response.raise_for_status()
                return True
                
        except Exception as e:
            self.logger.error(f"Failed to upload backup: {str(e)}")
            return False
    
    def backup_file(self, source_path: str, dest_path: str) -> bool:
        """Create a backup of a single file"""
        try:
            # In a real implementation, this would create a backup
            # For now, we'll just simulate it
            self.logger.info(f"Backing up {source_path} to {dest_path}")
            # Simulate backup time
            time.sleep(1)
            return True
        except Exception as e:
            self.logger.error(f"Backup failed: {str(e)}")
            return False
    
    def start(self) -> None:
        """Start the backup agent"""
        if not self.token or not self.agent_id:
            self.logger.error("Agent not registered. Please register the agent first.")
            return
            
        self.running = True
        self.logger.info(f"Starting backup agent (ID: {self.agent_id})")
        
        # Set up signal handlers for graceful shutdown
        signal.signal(signal.SIGINT, self._handle_signal)
        signal.signal(signal.SIGTERM, self._handle_signal)
        
        try:
            # Initialize task processor
            self.task_processor = TaskProcessor(self, self.config)
            
            # Start the task processor in a separate thread
            import threading
            self.processor_thread = threading.Thread(target=self.task_processor.start)
            self.processor_thread.daemon = True
            self.processor_thread.start()
            
            # Keep the main thread alive
            while self.running and self.processor_thread.is_alive():
                time.sleep(1)
                
        except Exception as e:
            self.logger.error(f"Error in agent: {str(e)}", exc_info=True)
        finally:
            self.stop()
    
    def stop(self) -> None:
        """Stop the backup agent"""
        if not self.running:
            return
            
        self.logger.info("Stopping backup agent...")
        self.running = False
        
        # Stop the task processor if it exists
        if hasattr(self, 'task_processor') and self.task_processor:
            self.task_processor.stop()
        
        self.logger.info("Backup agent stopped")
    
    def _handle_signal(self, signum, frame) -> None:
        """Handle shutdown signals"""
        self.logger.info(f"Received signal {signum}, shutting down...")
        self.stop()
        sys.exit(0)

def get_config_path() -> str:
    """Get the path to the config file in the current directory"""
    return os.path.join(os.getcwd(), "agent_config.json")

def load_config() -> Dict[str, Any]:
    """Load the agent configuration"""
    config_path = get_config_path()
    if os.path.exists(config_path):
        try:
            with open(config_path, 'r') as f:
                config = json.load(f)
                print(f"✅ Loaded configuration from {os.path.abspath(config_path)}")
                return config
        except Exception as e:
            print(f"⚠️ Error reading config: {str(e)}")
    return {}

def save_config(config: Dict[str, Any]) -> bool:
    """Save the agent configuration to the current directory"""
    config_path = get_config_path()
    try:
        with open(config_path, 'w') as f:
            json.dump(config, f, indent=2)
        # Set permissions to read/write for user only
        os.chmod(config_path, 0o600)
        print(f"✅ Configuration saved to: {os.path.abspath(config_path)}")
        return True
    except Exception as e:
        print(f"⚠️ Error saving config: {str(e)}")
        return False

def main() -> None:
    """Main entry point for the backup agent"""
    # Load existing config
    config = load_config()

    # Resolve server URL (priority: config -> env -> default)
    server_url = (
        config.get('server_url')
        or os.getenv('AGENT_SERVER_URL')
        or "http://localhost:8000"
    )
    # Normalize and persist server_url in config if missing
    server_url = server_url.rstrip('/')
    if config.get('server_url') != server_url:
        config['server_url'] = server_url
        try:
            save_config(config)
        except Exception:
            pass

    # If running interactively without a token and server_url looks like a placeholder/local default,
    # prompt the user to enter the correct server URL.
    if not config.get('token'):
        if server_url in ("http://localhost:8000", "http://127.0.0.1:8000", ""):
            entered = input("🌐 Enter server URL (e.g., http://192.168.1.50:8000 or https://backup.example.com): ").strip().rstrip('/')
            if entered:
                server_url = entered
                config['server_url'] = server_url
                save_config(config)

    # Optional: prompt for OpenSSL path on Windows if not resolvable from config/env
    if os.name == 'nt':
        # Only prompt if not set in config and not set via env variable
        if not config.get('openssl_path') and not os.getenv('AGENT_OPENSSL_PATH'):
            print("🔐 OpenSSL is required for encryption. If installed, provide the full path to openssl.exe.")
            print("    Common paths: C:\\Program Files\\OpenSSL-Win64\\bin\\openssl.exe or C:\\Program Files\\Git\\usr\\bin\\openssl.exe")
            openssl_path = input("➡️  Enter OpenSSL path (leave blank to auto-detect): ").strip()
            if openssl_path:
                config['openssl_path'] = openssl_path
                save_config(config)

    # Initialize the agent with the resolved server_url and config
    agent = BackupAgent(server_url, config)
    
    # If no token, register a new agent
    if not config.get('token'):
        print("🔍 No agent token found. Let's register a new agent.")
        name = input("🤖 Enter agent name: ")
        email = input("📧 Enter your email: ")
        password = getpass.getpass("🔑 Enter your password: ")
        
        if agent.register_agent(name, email, password) and agent.token:
            # Save the updated config
            if save_config(agent.config):
                print("✅ Agent registered and configuration saved.")
            else:
                print("⚠️ Agent registered but could not save configuration.")
                sys.exit(1)
        else:
            print("❌ Failed to register agent. Please check your credentials and try again.")
            sys.exit(1)
    
    # Verify the token works
    try:
        tasks = agent.get_tasks()
        print(f"🔍 Found {len(tasks)} pending tasks")
    except Exception as e:
        print(f"❌ Failed to connect to server: {str(e)}")
        print("The saved token might be invalid. Please delete the config file and try again.")
        print(f"Config location: {get_config_path()}")
        sys.exit(1)
    
    # Run the agent
    print("🚀 Starting backup agent...")
    agent.start()

if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        print("\n👋 Shutting down gracefully...")
        sys.exit(0)
    except Exception as e:
        print(f"\n❌ Fatal error: {str(e)}")
        if '--debug' in sys.argv:
            import traceback
            traceback.print_exc()
        sys.exit(1)
