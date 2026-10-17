#!/usr/bin/env python3
"""Video für Reels aufbereiten: 1080×1920 (hochkant, schwarze Ränder statt Abschneiden), H.264/AAC, 30 fps,
optional eigene Musik darunterlegen (mit Ein- und Ausblenden), Originalton aus oder leise dazu.

    python3 social/video_mischen.py EINGANG.mov AUSGANG.mp4 [--musik song.mp3] [--start 32] [--originalton aus|leise]

Braucht ffmpeg/ffprobe (im GitHub-Runner vorhanden). Länge = Länge des Videos.
"""
import argparse
import json
import subprocess
import sys


def info(pfad):
    r = subprocess.run(["ffprobe", "-v", "error", "-show_entries", "format=duration:stream=codec_type",
                        "-of", "json", pfad], capture_output=True, text=True, check=True)
    j = json.loads(r.stdout)
    return float(j["format"]["duration"]), any(s.get("codec_type") == "audio" for s in j.get("streams", []))


def main():
    p = argparse.ArgumentParser()
    p.add_argument("eingang")
    p.add_argument("ausgang")
    p.add_argument("--musik", default="")
    p.add_argument("--start", type=float, default=0.0, help="Ab welcher Sekunde im Song")
    p.add_argument("--originalton", choices=["aus", "leise"], default="aus")
    a = p.parse_args()

    dauer, hat_ton = info(a.eingang)
    if dauer > 90:
        print("::warning::Das Video ist %.0f Sekunden lang. Für Reels sind bis 90 Sekunden üblich." % dauer)
    bild = ("[0:v]scale=1080:1920:force_original_aspect_ratio=decrease,"
            "pad=1080:1920:(ow-iw)/2:(oh-ih)/2:color=black,fps=30,format=yuv420p,setsar=1[v]")
    cmd = ["ffmpeg", "-v", "error", "-y", "-i", a.eingang]
    if a.musik:
        cmd += ["-ss", "%.2f" % max(0.0, a.start), "-i", a.musik]
        aus = max(0.0, dauer - 2.0)
        musik = "[1:a]aresample=48000,afade=t=in:d=0.5,afade=t=out:st=%.2f:d=2[m]" % aus
        if a.originalton == "leise" and hat_ton:
            ton = musik + ";[0:a]aresample=48000,volume=0.25[o];[m][o]amix=inputs=2:duration=first:normalize=0[a]"
        else:
            ton = musik.replace("[m]", "[a]")
    elif hat_ton:
        ton = "[0:a]aresample=48000[a]"
    else:  # Instagram lehnt Videos ohne Tonspur teils ab → stille Spur
        cmd += ["-f", "lavfi", "-i", "anullsrc=channel_layout=stereo:sample_rate=48000"]
        ton = "[1:a]anull[a]"
    cmd += ["-filter_complex", bild + ";" + ton, "-map", "[v]", "-map", "[a]", "-t", "%.3f" % dauer,
            "-c:v", "libx264", "-preset", "medium", "-crf", "20", "-profile:v", "high", "-level", "4.1",
            "-c:a", "aac", "-b:a", "192k", "-ac", "2", "-movflags", "+faststart", a.ausgang]
    subprocess.run(cmd, check=True)
    d2, _ = info(a.ausgang)
    print("Fertig: %s (%.1f s, 1080×1920%s)" % (a.ausgang, d2, ", mit Musik" if a.musik else ""))
    return 0


if __name__ == "__main__":
    sys.exit(main())
