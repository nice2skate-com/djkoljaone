#!/usr/bin/env python3
"""Reel auf Instagram und/oder der Facebook-Seite veröffentlichen – direkt über die Graph-API von Meta.

Läuft im GitHub-Ablauf „Social: Reel posten“ (.github/workflows/social-reel.yml), lokal geht es genauso:

    META_PAGE_TOKEN=… META_PAGE_ID=… python3 social/meta_reel.py pruefen
    META_PAGE_TOKEN=… META_PAGE_ID=… python3 social/meta_reel.py posten \
        --video-url https://dj-kolja-one.de/…/reel.mp4 --text-datei social/texte/reel-start.txt --ziele instagram,facebook

Umgebung:
  META_PAGE_TOKEN   Seiten-Zugriffsschlüssel (dauerhaft, siehe social/README.md)
  META_PAGE_ID      ID der Facebook-Seite
  META_IG_USER_ID   optional – sonst aus der Seite ermittelt
  META_API_VERSION  optional, Standard v23.0
Nur Standardbibliothek, keine Pakete nötig.
"""
import argparse
import json
import os
import sys
import time
import urllib.error
import urllib.parse
import urllib.request

VERSION = os.environ.get("META_API_VERSION", "v23.0")
GRAPH = os.environ.get("META_GRAPH_URL", "https://graph.facebook.com/") + VERSION + "/"  # META_GRAPH_URL nur für Tests


class MetaFehler(Exception):
    pass


def _anfrage(url, daten=None, header=None):
    body = urllib.parse.urlencode(daten).encode() if daten is not None else None
    req = urllib.request.Request(url, data=body, headers=header or {})
    try:
        with urllib.request.urlopen(req, timeout=120) as r:
            return json.loads(r.read().decode() or "{}")
    except urllib.error.HTTPError as e:
        try:
            err = json.loads(e.read().decode()).get("error", {})
            msg = err.get("error_user_msg") or err.get("message") or str(e)
            code = err.get("code")
        except Exception:  # noqa: BLE001
            msg, code = str(e), None
        raise MetaFehler("Meta meldet: %s%s" % (msg, " (Code %s)" % code if code else "")) from None
    except urllib.error.URLError as e:
        raise MetaFehler("Keine Verbindung zu Meta: %s" % e.reason) from None


def api(pfad, daten=None, get=None, token=None):
    token = token or os.environ["META_PAGE_TOKEN"]
    if daten is None:
        q = dict(get or {}, access_token=token)
        return _anfrage(GRAPH + pfad + "?" + urllib.parse.urlencode(q))
    return _anfrage(GRAPH + pfad, dict(daten, access_token=token))


def seite():
    pid = os.environ["META_PAGE_ID"]
    s = api(pid, get={"fields": "id,name,instagram_business_account{id,username}"})
    ig = s.get("instagram_business_account") or {}
    return {"page_id": s.get("id"), "page_name": s.get("name"),
            "ig_id": os.environ.get("META_IG_USER_ID") or ig.get("id"), "ig_name": ig.get("username")}


def instagram_reel(ig_id, video_url, text, log=print):
    c = api(ig_id + "/media", {"media_type": "REELS", "video_url": video_url, "caption": text, "share_to_feed": "true"})
    cid = c["id"]
    log("Instagram: Video wird verarbeitet (Container %s) …" % cid)
    for _ in range(120):  # bis 10 Minuten
        st = api(cid, get={"fields": "status_code,status"})
        code = st.get("status_code")
        if code == "FINISHED":
            break
        if code in ("ERROR", "EXPIRED"):
            raise MetaFehler("Instagram konnte das Video nicht verarbeiten: %s" % (st.get("status") or code))
        time.sleep(float(os.environ.get("META_POLL", "5")))
    else:
        raise MetaFehler("Instagram: Verarbeitung dauert länger als 10 Minuten – bitte später in der App prüfen.")
    m = api(ig_id + "/media_publish", {"creation_id": cid})
    link = api(m["id"], get={"fields": "permalink"}).get("permalink", "")
    return {"id": m["id"], "link": link}


def facebook_reel(page_id, video_url, text, log=print):
    s = api(page_id + "/video_reels", {"upload_phase": "start"})
    vid, up = s["video_id"], s["upload_url"]
    log("Facebook: Video wird übertragen (%s) …" % vid)
    r = _anfrage(up, {}, {"Authorization": "OAuth " + os.environ["META_PAGE_TOKEN"], "file_url": video_url})
    if not r.get("success", True):
        raise MetaFehler("Facebook hat das Video nicht angenommen.")
    api(page_id + "/video_reels", {"upload_phase": "finish", "video_id": vid, "video_state": "PUBLISHED", "description": text})
    for _ in range(60):  # Status ist informativ; Facebook veröffentlicht nach der Verarbeitung selbst
        st = api(vid, get={"fields": "status"}).get("status", {})
        if st.get("video_status") in ("ready", "published") or (st.get("publishing_phase") or {}).get("status") == "complete":
            break
        if st.get("video_status") == "error":
            raise MetaFehler("Facebook konnte das Video nicht verarbeiten.")
        time.sleep(float(os.environ.get("META_POLL", "5")))
    return {"id": vid, "link": "https://www.facebook.com/reel/%s" % vid}


def zusammenfassung(zeilen):
    datei = os.environ.get("GITHUB_STEP_SUMMARY")
    if datei:
        with open(datei, "a", encoding="utf-8") as f:
            f.write("\n".join(zeilen) + "\n")


def main():
    p = argparse.ArgumentParser()
    p.add_argument("aktion", choices=["pruefen", "posten"])
    p.add_argument("--video-url", default="")
    p.add_argument("--text", default="")
    p.add_argument("--text-datei", default="")
    p.add_argument("--ziele", default="instagram,facebook")
    a = p.parse_args()
    for v in ("META_PAGE_TOKEN", "META_PAGE_ID"):
        if not os.environ.get(v):
            print("::error::%s fehlt (GitHub → Settings → Secrets and variables → Actions)." % v)
            return 2
    try:
        s = seite()
        info = ["## Verbindung", "- Facebook-Seite: **%s** (%s)" % (s["page_name"], s["page_id"]),
                "- Instagram: **@%s** (%s)" % (s["ig_name"], s["ig_id"]) if s["ig_id"] else "- Instagram: **nicht verknüpft**"]
        print("\n".join(info))
        zusammenfassung(info)
        if a.aktion == "pruefen":
            return 0 if s["ig_id"] else 1
        text = a.text.strip() or (open(a.text_datei, encoding="utf-8").read().strip() if a.text_datei else "")
        if not a.video_url.startswith("https://"):
            raise MetaFehler("Die Video-Adresse muss öffentlich mit https:// erreichbar sein.")
        ziele = [z.strip() for z in a.ziele.split(",") if z.strip()]
        erg = ["## Veröffentlicht"]
        if "instagram" in ziele:
            if not s["ig_id"]:
                raise MetaFehler("Instagram ist nicht mit der Facebook-Seite verknüpft.")
            r = instagram_reel(s["ig_id"], a.video_url, text)
            erg.append("- Instagram: %s" % (r["link"] or r["id"]))
            print(erg[-1])
        if "facebook" in ziele:
            r = facebook_reel(s["page_id"], a.video_url, text)
            erg.append("- Facebook: %s" % r["link"])
            print(erg[-1])
        zusammenfassung(erg)
        return 0
    except MetaFehler as e:
        print("::error::%s" % e)
        zusammenfassung(["## Fehler", str(e)])
        return 1


if __name__ == "__main__":
    sys.exit(main())
