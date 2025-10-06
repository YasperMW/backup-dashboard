# Backup Agent

A Python-based agent that communicates with the Backup Dashboard to perform scheduled backups.

## Features

- Automatic registration with the Backup Dashboard
- Secure token-based authentication
- Support for file and incremental backups
- Progress reporting and error handling
- Configurable through environment variables

## Prerequisites

- Python 3.7+
- pip (Python package manager)
- Access to the Backup Dashboard API

## Installation

1. Clone this repository or download the agent files
2. Install the required dependencies:

```bash
pip install -r requirements.txt
```

## Configuration

Create a `.env` file in the agent directory with the following variables:

```
SERVER_URL=http://your-backup-dashboard.com
# Optional: If you already have an agent token
AGENT_TOKEN=your_agent_token_here
```

## Usage

### First-time Setup

1. Run the agent without a token to start the registration process:

```bash
python agent.py
```

2. Follow the prompts to register a new agent with the Backup Dashboard.
3. The agent will save your token for future use.

### Running the Agent

To start the agent with an existing token:

```bash
python agent.py
```

The agent will:
1. Check for new backup tasks every 30 seconds
2. Process any pending tasks
3. Upload completed backups to the server
4. Report task status and any errors

### Running as a Service (Linux)

To run the agent as a systemd service:

1. Create a service file at `/etc/systemd/system/backup-agent.service`:

```ini
[Unit]
Description=Backup Agent
After=network.target

[Service]
User=your_username
WorkingDirectory=/path/to/backup-agent
ExecStart=/usr/bin/python3 /path/to/backup-agent/agent.py
Restart=always
Environment=PYTHONUNBUFFERED=1

[Install]
WantedBy=multi-user.target
```

2. Reload systemd and start the service:

```bash
sudo systemctl daemon-reload
sudo systemctl enable backup-agent
sudo systemctl start backup-agent
```

## Logs

Check the agent's logs with:

```bash
journalctl -u backup-agent -f
```

## Security

- The agent stores its authentication token in `~/.backup_agent/config.json`
- Make sure to protect this file with appropriate permissions
- The agent requires network access to your Backup Dashboard server

## License

MIT
