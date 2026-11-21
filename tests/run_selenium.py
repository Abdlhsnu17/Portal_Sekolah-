#!/usr/bin/env python3
import argparse
import os
from pathlib import Path
import signal
import socket
import subprocess
import sys
import time
import unittest
from urllib.request import urlopen


ROOT = Path(__file__).resolve().parents[1]
DB_PATH = ROOT / "storage/framework/testing/e2e.sqlite"
HOST = "127.0.0.1"
PORT = int(os.environ.get("E2E_PORT", "9515"))
BASE_URL = f"http://{HOST}:{PORT}"


def test_environment():
    env = os.environ.copy()
    env.update({
        "APP_ENV": "testing",
        "APP_DEBUG": "true",
        "APP_URL": BASE_URL,
        "DB_CONNECTION": "sqlite",
        "DB_DATABASE": str(DB_PATH),
        "CACHE_STORE": "array",
        "SESSION_DRIVER": "file",
        "QUEUE_CONNECTION": "sync",
        "MAIL_MAILER": "array",
        "MFA_ENABLED": "false",
        "E2E_BASE_URL": BASE_URL,
    })
    return env


def run_artisan(*args, env):
    subprocess.run(["php", "artisan", *args], cwd=ROOT, env=env, check=True)


def prepare_database(env):
    DB_PATH.parent.mkdir(parents=True, exist_ok=True)
    DB_PATH.unlink(missing_ok=True)
    DB_PATH.touch()
    run_artisan("migrate:fresh", "--force", "--no-interaction", env=env)
    run_artisan("db:seed", "--class=Database\\Seeders\\E2ETestSeeder", "--force", "--no-interaction", env=env)


def wait_for_server(process):
    deadline = time.monotonic() + 20
    while time.monotonic() < deadline:
        if process.poll() is not None:
            raise RuntimeError("Server Laravel berhenti sebelum siap.")
        try:
            with urlopen(BASE_URL, timeout=1) as response:
                if response.status < 500:
                    return
        except Exception:
            time.sleep(0.25)
    raise RuntimeError(f"Server tidak siap dalam 20 detik: {BASE_URL}")


def suite_for(kind):
    pattern = "test_smoke.py" if kind == "smoke" else "test_e2e.py" if kind == "e2e" else "test_*.py"
    return unittest.defaultTestLoader.discover(str(ROOT / "tests/browser"), pattern=pattern, top_level_dir=str(ROOT))


def main():
    parser = argparse.ArgumentParser(description="Jalankan smoke/E2E test Portal Sekolah dengan Selenium.")
    parser.add_argument("kind", choices=("smoke", "e2e", "all"), nargs="?", default="all")
    args = parser.parse_args()
    env = test_environment()
    prepare_database(env)

    server = subprocess.Popen(
        ["php", "artisan", "serve", f"--host={HOST}", f"--port={PORT}", "--no-reload"],
        cwd=ROOT,
        env=env,
        stdout=subprocess.DEVNULL,
        stderr=subprocess.STDOUT,
        start_new_session=True,
    )
    try:
        wait_for_server(server)
        os.environ.update({"E2E_BASE_URL": BASE_URL})
        result = unittest.TextTestRunner(verbosity=2).run(suite_for(args.kind))
        return 0 if result.wasSuccessful() else 1
    finally:
        if server.poll() is None:
            os.killpg(server.pid, signal.SIGTERM)
            server.wait(timeout=5)
        DB_PATH.unlink(missing_ok=True)


if __name__ == "__main__":
    sys.exit(main())
