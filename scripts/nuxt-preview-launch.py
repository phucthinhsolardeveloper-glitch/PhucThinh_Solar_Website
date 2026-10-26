#!/usr/bin/env python3
"""Start Nuxt local SSR preview detached from the short-lived WSL command process."""
import os
import pathlib
import subprocess
import sys
import time
import urllib.request

root = pathlib.Path(__file__).resolve().parent.parent
frontend = root / "frontend"
artifacts = frontend / ".migration-artifacts"
artifacts.mkdir(parents=True, exist_ok=True)
logfile = artifacts / "pts-nuxt-preview.log"
pidfile = artifacts / "pts-nuxt-preview.pid"
env = {
    **os.environ,
    "NUXT_PHP_ORIGIN": "http://127.0.0.1:8088",
    "NUXT_PREVIEW_MEDIA_PROXY": "1",
    "NITRO_HOST": "127.0.0.1",
    "NITRO_PORT": "3100",
}
with logfile.open("a") as log:
    child = subprocess.Popen(
        ["node", ".output/server/index.mjs"],
        cwd=str(frontend),
        stdin=subprocess.DEVNULL,
        stdout=log,
        stderr=subprocess.STDOUT,
        env=env,
        start_new_session=True,
    )
pidfile.write_text(str(child.pid))
url = "http://127.0.0.1:3100/migration-site-preview"
for _ in range(50):
    if child.poll() is not None:
        print("Preview exited before becoming ready; see", logfile, file=sys.stderr)
        sys.exit(1)
    try:
        with urllib.request.urlopen(url, timeout=1) as response:
            if response.status == 200 and child.poll() is None:
                print("Nuxt preview ready:", url.replace("127.0.0.1", "localhost"), "PID", child.pid)
                sys.exit(0)
    except Exception:
        time.sleep(0.25)
print("Preview did not start; see", logfile, file=sys.stderr)
child.terminate()
sys.exit(1)
