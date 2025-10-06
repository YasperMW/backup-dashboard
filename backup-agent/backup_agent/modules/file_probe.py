import os
from typing import Optional


def file_exists(path: str) -> bool:
    """Return True if the given path exists and is a file.

    Handles Windows and POSIX paths. For directories, use os.path.isdir externally if needed.
    """
    if not path:
        return False
    try:
        return os.path.isfile(path)
    except Exception:
        # In case of permission or encoding issues, fall back to exists
        try:
            return os.path.exists(path)
        except Exception:
            return False
