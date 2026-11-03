#!/usr/bin/env python3
"""Create a lightweight concept film from an existing PTS editorial image.

This is camera-motion treatment of a static illustration, NOT AI-generated
real-life footage or evidence of a completed Phuc Thinh Solar project.
Run with: FFMPEG_BIN=/path/to/ffmpeg python3 frontend/scripts/generate-solar-cinema-preview.py
"""
from pathlib import Path
import os
import shutil
import subprocess

FRONTEND = Path(__file__).resolve().parents[1]
SOURCE = FRONTEND / "public/assets/images/site-visuals/pdp-usecase-v2/panel-commercial-roof.webp"
OUTPUT_DIR = FRONTEND / "public/assets/video"
MOVIE = OUTPUT_DIR / "pts-solar-cinema-preview.mp4"
POSTER = OUTPUT_DIR / "pts-solar-cinema-poster.webp"
FFMPEG = os.environ.get("FFMPEG_BIN") or shutil.which("ffmpeg") or "/tmp/pts-film-tools/node_modules/@ffmpeg-installer/linux-x64/ffmpeg"

if not SOURCE.is_file():
    raise SystemExit(f"Missing approved source image: {SOURCE}")
if not Path(FFMPEG).is_file():
    raise SystemExit("FFmpeg not found. Set FFMPEG_BIN to a local ffmpeg executable.")

OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
# 8s, 24fps, 1280x720. Narrow, slow camera dolly. Text and logo are rendered
# by Nuxt, not burned into the video. No generated project claims or metrics.
video_filter = (
    "scale=1600:1000:flags=lanczos,"
    "zoompan=z='min(zoom+0.00031,1.075)':"
    "x='iw/2-(iw/zoom/2)+10*sin(on/192*PI)':"
    "y='ih/2-(ih/zoom/2)':d=1:s=1280x720:fps=24,"
    "eq=contrast=1.045:saturation=0.93:brightness=-0.015,"
    "format=yuv420p"
)
cmd = [
    FFMPEG, "-hide_banner", "-loglevel", "error", "-y",
    "-loop", "1", "-framerate", "24", "-t", "8", "-i", str(SOURCE),
    "-vf", video_filter,
    "-an", "-frames:v", "192", "-c:v", "libx264", "-preset", "medium",
    "-crf", "25", "-pix_fmt", "yuv420p", "-movflags", "+faststart",
    str(MOVIE),
]
subprocess.run(cmd, check=True)
poster_filter = (
    "scale=1600:1000:flags=lanczos,"
    "crop=1280:720:(iw-ow)/2:(ih-oh)/2,"
    "eq=contrast=1.045:saturation=0.93:brightness=-0.015"
)
subprocess.run([
    FFMPEG, "-hide_banner", "-loglevel", "error", "-y",
    "-i", str(SOURCE), "-vf", poster_filter,
    "-frames:v", "1", "-c:v", "libwebp", "-quality", "80", str(POSTER),
], check=True)
for output in (MOVIE, POSTER):
    print(f"{output}: {output.stat().st_size:,} bytes")
