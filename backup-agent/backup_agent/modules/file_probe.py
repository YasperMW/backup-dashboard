import os
from typing import Optional


def file_exists(path: str) -> bool:
    """Return True if the given path exists (file or directory).

    Some backups may be stored as directories; treating only files as valid can produce false negatives.
    """
    if not path:
        return False
    try:
        return os.path.exists(path)
    except Exception:
        # In case of permission or encoding issues
        return False
