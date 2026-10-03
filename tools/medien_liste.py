#!/usr/bin/env python3
"""Liest wp-content/uploads per SFTP aus und schreibt docs/MEDIEN.md.

Zugangsdaten kommen aus Umgebungsvariablen (GitHub-Secrets):
SFTP_HOST, SFTP_USER, SFTP_PASSWORD, SFTP_WP_CONTENT (z. B. /app/wp-content)
Es werden nur Metadaten (Pfad, Größe, Datum) gelesen, keine Dateien heruntergeladen.
"""
import os, stat, sys, datetime, collections

SKIP_DIRS = {"cache", "wpforms", "elementor/css", "complianz", "wp-statistics"}


def human(n):
    for u in ("B", "KB", "MB", "GB"):
        if n < 1024 or u == "GB":
            return f"{n:.0f} {u}" if u == "B" else f"{n:.1f} {u}"
        n /= 1024


def walk(sftp, base, rel=""):
    path = base + ("/" + rel if rel else "")
    for e in sorted(sftp.listdir_attr(path), key=lambda a: a.filename):
        r = f"{rel}/{e.filename}" if rel else e.filename
        if stat.S_ISDIR(e.st_mode):
            if r in SKIP_DIRS:
                continue
            yield from walk(sftp, base, r)
        else:
            yield r, e.st_size, e.st_mtime


def render(files):
    total = sum(s for _, s, _ in files)
    by_dir = collections.OrderedDict()
    for p, s, m in files:
        d = os.path.dirname(p) or "."
        by_dir.setdefault(d, []).append((os.path.basename(p), s, m))
    out = ["# Medien auf dj-kolja-one.de (wp-content/uploads)", "",
           f"Stand: {datetime.datetime.now(datetime.timezone.utc):%d.%m.%Y %H:%M} UTC · "
           f"{len(files)} Dateien · {human(total)}", "",
           "_Automatisch erzeugt von `tools/medien_liste.py` – nicht von Hand ändern._", ""]
    for d, items in by_dir.items():
        out += [f"## {d}", "", "| Datei | Größe | Geändert |", "|---|---|---|"]
        for n, s, m in items:
            out.append(f"| {n} | {human(s)} | {datetime.datetime.fromtimestamp(m):%d.%m.%Y} |")
        out.append("")
    return "\n".join(out)


def main():
    import paramiko
    host, user, pw, base = (os.environ[k] for k in
                            ("SFTP_HOST", "SFTP_USER", "SFTP_PASSWORD", "SFTP_WP_CONTENT"))
    t = paramiko.Transport((host, 22))
    t.connect(username=user, password=pw)
    sftp = paramiko.SFTPClient.from_transport(t)
    try:
        files = list(walk(sftp, base.rstrip("/") + "/uploads"))
    finally:
        sftp.close(); t.close()
    os.makedirs("docs", exist_ok=True)
    open("docs/MEDIEN.md", "w", encoding="utf-8").write(render(files))
    print(f"{len(files)} Dateien -> docs/MEDIEN.md")


if __name__ == "__main__":
    main()
