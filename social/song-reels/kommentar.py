"""Kommentar-Reel: ein Song läuft lange (Standard 90 s ab Songanfang), dazu der Aufruf „Kommentiere <WORT>“
für eine ManyChat-Automation, die den Download per DM schickt. Vorlage: kommentar.html.

Aufruf (aus dem Repo-Ordner):
    python3 social/song-reels/kommentar.py 5169 --wort SUMMER
    python3 social/song-reels/kommentar.py 5169 --wort SUMMER --start 0 --laenge 90
Ergebnis: social/song-reels/arbeit/out/kommentar_<titel>_<WORT>.mp4

Die Szenenlängen in kommentar.html ergeben 90 s. Bei anderer --laenge werden sie anteilig gestreckt."""
import argparse, json, os, re, shutil, subprocess, sys, urllib.request

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)
import prep  # noqa: E402  (pcm, Sprüche, Effekte, Arbeitsordner)
from build import b64, slug, REPO  # noqa: E402

import numpy as np  # noqa: E402

FPS, SR = 30, prep.SR
# Wechselnde Sätze in der dritten Szene; <em> wird gold
SAETZE = {
    5169: ['Sonne auf der Haut.', 'Lange Abende.', 'Disco bis zum <em>Morgen</em>.', 'Sommer zum Mitnehmen.',
           '<em>Got the Summer Feeling.</em>'],
}


def envelope(x, start, length):
    seg = x[int(start * SR):int((start + length) * SR)]
    hop = SR // FPS
    e = np.array([np.sqrt(np.mean(seg[i * hop:(i + 1) * hop] ** 2)) if len(seg[i * hop:(i + 1) * hop]) else 0
                  for i in range(int(length * FPS))])
    e = e / (np.percentile(e, 98) + 1e-9)
    return [round(float(min(v, 1.0)), 3) for v in e]


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('id', type=int)
    ap.add_argument('--wort', required=True, help='Schlüsselwort für ManyChat, z. B. SUMMER')
    ap.add_argument('--start', type=float, default=0.0, help='Start im Song in Sekunden')
    ap.add_argument('--laenge', type=float, default=90.0, help='Länge in Sekunden')
    ap.add_argument('--html-only', action='store_true')
    a = ap.parse_args()

    W = prep.WORK
    for d in ('songs', 'pages', 'out'):
        os.makedirs(os.path.join(W, d), exist_ok=True)
    songs = json.load(urllib.request.urlopen(prep.SITE + '/wp-json/kjm/v1/songs'))
    s = next(x for x in songs if x['id'] == a.id)
    mp3 = os.path.join(prep.SONGS, f"{a.id}.mp3")
    if not os.path.exists(mp3):
        urllib.request.urlretrieve(prep.SITE + s['audio'], mp3)
    cov = os.path.join(prep.SONGS, f"{a.id}_cover.webp")
    if s.get('cover') and not os.path.exists(cov):
        urllib.request.urlretrieve(prep.SITE + s['cover'], cov)

    x = prep.pcm(mp3)
    length = min(a.laenge, len(x) / SR - a.start)
    d = dict(id=a.id, titel=s['titel'], genre=s['genre'], bpm=s['bpm'], key=s['key'], album=s.get('album') or '',
             tag=prep.TAG.get(a.id, ''), fx=prep.FX.get(s['genre'], 'bokeh'), env=envelope(x, a.start, length),
             accent='#B29D75', glow='#E0B36A', cover=os.path.exists(cov), keyword=a.wort.upper(),
             words=SAETZE.get(a.id) or [prep.TAG.get(a.id, s['titel'])])

    t = open(os.path.join(HERE, 'kommentar.html'), encoding='utf-8').read()
    f = length / 90.0
    t = re.sub(r'data-dur="(\d+(?:\.\d+)?)"', lambda m: 'data-dur="%.3f"' % (float(m.group(1)) * f), t)
    for w in ('400', '600', '800'):
        t = t.replace('__F%s__' % w, b64(f'{REPO}/media/motion/fonts/fira{w}.woff2', 'font/woff2'))
    t = t.replace('__COVER__', b64(cov, 'image/webp') if d['cover'] else '')
    t = t.replace('__PROFIL__', b64(os.path.join(HERE, 'profil.webp'), 'image/webp'))
    t = t.replace('__DATA__', json.dumps(d, ensure_ascii=False))
    name = f"kommentar_{a.id}.html"
    open(os.path.join(W, 'pages', name), 'w', encoding='utf-8').write(t)
    shutil.copy(f'{REPO}/media/motion/render.js', os.path.join(W, 'pages', 'render.js'))
    if a.html_only:
        print(os.path.join(W, 'pages', name))
        return

    clip = os.path.join(W, 'clips', f"kommentar_{a.id}.m4a")
    os.makedirs(os.path.dirname(clip), exist_ok=True)
    subprocess.run(['ffmpeg', '-v', 'error', '-y', '-ss', str(a.start), '-t', str(length), '-i', mp3, '-vn',
                    '-af', 'afade=t=out:st=%.2f:d=4' % (length - 4), '-c:a', 'aac', '-b:a', '192k', clip], check=True)
    fr = os.path.join(W, f'frames_k{a.id}')
    shutil.rmtree(fr, ignore_errors=True)
    subprocess.run(['node', os.path.join(W, 'pages', 'render.js'), 'v', str(length), '30', fr, name], check=True,
                   stdout=subprocess.DEVNULL)
    out = os.path.join(W, 'out', f"kommentar_{slug(s['titel'])}_{d['keyword']}.mp4")
    subprocess.run(['ffmpeg', '-v', 'error', '-y', '-framerate', '30', '-i', os.path.join(fr, '%05d.jpg'), '-i', clip,
                    '-map', '0:v', '-map', '1:a', '-c:v', 'libx264', '-crf', '21', '-pix_fmt', 'yuv420p', '-c:a', 'copy',
                    '-shortest', '-movflags', '+faststart', out], check=True)
    shutil.rmtree(fr, ignore_errors=True)
    print('fertig', out)


if __name__ == '__main__':
    main()
