#!/usr/bin/env python3
"""Vergleicht die Plugin-Dateien auf dem Server (per SFTP) mit dem Repo (SHA-256).
Zeigt, ob der Deploy wirklich angekommen ist. Zugangsdaten aus den GitHub-Secrets."""
import hashlib, os, sys

def sha(b):
    return hashlib.sha256(b).hexdigest()

def main():
    import paramiko
    host, user, pw, base = (os.environ[k] for k in ("SFTP_HOST", "SFTP_USER", "SFTP_PASSWORD", "SFTP_WP_CONTENT"))
    local_root = os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "plugin", "dj-kolja-one-plattenkiste")
    remote_root = base.rstrip("/") + "/plugins/dj-kolja-one-plattenkiste"
    t = paramiko.Transport((host, 22)); t.connect(username=user, password=pw)
    sftp = paramiko.SFTPClient.from_transport(t)
    rows, bad = [], 0
    for dp, _, fns in os.walk(local_root):
        for fn in sorted(fns):
            lp = os.path.join(dp, fn); rel = os.path.relpath(lp, local_root).replace(os.sep, "/")
            lb = open(lp, "rb").read()
            try:
                with sftp.open(remote_root + "/" + rel, "rb") as f:
                    rb = f.read()
                st = sftp.stat(remote_root + "/" + rel)
                ok = sha(lb) == sha(rb)
                info = "%d Bytes · %s" % (st.st_size, __import__("datetime").datetime.fromtimestamp(st.st_mtime).strftime("%d.%m.%Y %H:%M"))
            except IOError:
                ok, info = False, "FEHLT auf dem Server"
            bad += 0 if ok else 1
            rows.append((rel, "OK" if ok else "ABWEICHUNG", len(lb), info))
    sftp.close(); t.close()
    out = ["## Plugin-Dateien: Server vs. Repo", "", "| Datei | Ergebnis | Repo-Größe | Server |", "|---|---|---|---|"]
    out += ["| %s | %s | %d | %s |" % r for r in sorted(rows)]
    out += ["", "**%s**" % ("Alles identisch – der Deploy ist vollständig angekommen." if not bad else "%d Datei(en) weichen ab oder fehlen – der Server hat nicht den Stand des Repos." % bad)]
    text = "\n".join(out); print(text)
    if os.environ.get("GITHUB_STEP_SUMMARY"):
        open(os.environ["GITHUB_STEP_SUMMARY"], "a", encoding="utf-8").write(text + "\n")
    sys.exit(1 if bad else 0)

if __name__ == "__main__":
    main()
