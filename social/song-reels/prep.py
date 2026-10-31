"""Bereitet pro Song die Daten fürs Reel vor: die letzten 30 s bis zum echten Song-Ende,
Lautstärkekurve (30 fps) und QR-Link. Lädt Songliste, MP3 und Cover von dj-kolja-one.de
nach arbeit/songs/ und schreibt arbeit/data/<id>.json und arbeit/clips/<id>.m4a.
Aufruf: python3 social/song-reels/prep.py [id ...]"""
import json, os, subprocess, sys, urllib.request
import numpy as np

HERE = os.path.dirname(os.path.abspath(__file__))
SITE = 'https://dj-kolja-one.de'
WORK = os.path.join(HERE, 'arbeit')
SONGS = os.path.join(WORK, 'songs')
LEN, FPS, SR = 30.0, 30, 22050

TAG = {
    5111: 'Großstadtnacht in Neonblau.', 5169: 'Sommer zum Mitnehmen.', 5317: 'Heute Nacht wird getanzt.',
    5353: 'Bis der Morgen kommt.', 5160: 'Segel setzen über dem Horizont.', 5337: 'Für die Menschen, die zählen.',
    5338: 'Nach dem Sturm kommt das Licht.', 5342: 'Klingt schon nach morgen.', 5150: 'Mittelmeer für die Ohren.',
    5152: 'Licht auf dem Wasser.', 5156: 'Leere Straßen, volle Gedanken.', 5158: 'Kleine Momente, großes Gefühl.',
    5119: 'Einfach mal gut sein lassen.', 5117: 'Vielleicht in einem anderen Leben.', 5123: 'Ein Name, eine Melodie.',
    5121: 'Treiben lassen, bis die Sonne sinkt.', 5242: 'Über den Dächern der Stadt.', 5106: 'Wenn dich das Leben überrascht.',
    5109: 'Für diesen Song nimmst du sie dir.', 5171: 'Einladung aufs Dach. Kommst du?', 5173: 'Die Welt dreht sich. Du tanzt mit.',
    5179: 'Weitergehen, Schritt für Schritt.', 5177: 'Kleine Beweise, dass es mich gibt.', 5175: 'Der Weg nach Hause.',
    5167: 'Zehn Tage Wald. Ein Atemzug.', 5165: 'Flöte, Fluss und Trommeln.', 5355: 'Weiter, als das Blau reicht.',
    5351: 'Erinnerst du dich an den Weg?', 5343: 'Stille, die man hören kann.', 5369: 'Verschlafen? Dann eben laut.',
}
# Genre → Effekt im Hintergrund
FX = {'Disco-Hits': 'sparkle', 'R&B, Crunk und Hip-Hop': 'neon', 'Euro-Dance': 'neon', 'Progressive House': 'beams',
      'Melodic House': 'beams', 'Lounge': 'bokeh', 'Chill': 'bokeh', 'Melodischer Pop-Rap': 'grain',
      'Folk-Songs': 'dust', 'Celtic & Tribal': 'dust', 'Rock & Metal': 'beams'}


def pcm(path):
    raw = subprocess.run(['ffmpeg', '-v', 'error', '-i', path, '-ac', '1', '-ar', str(SR), '-f', 'f32le', '-'],
                         capture_output=True, check=True).stdout
    return np.frombuffer(raw, dtype=np.float32)


def song_end(x):
    # Ende = letzte Stelle über -50 dB plus 0,4 s Ausklang; Stille am Dateiende fällt weg
    a = np.abs(x)
    loud = np.nonzero(a > 10 ** (-50 / 20))[0]
    return min(len(x) / SR, (loud[-1] / SR + 0.4) if len(loud) else len(x) / SR)


def envelope(x, start):
    seg = x[int(start * SR):int((start + LEN) * SR)]
    hop = SR // FPS
    e = np.array([np.sqrt(np.mean(seg[i * hop:(i + 1) * hop] ** 2)) if len(seg[i * hop:(i + 1) * hop]) else 0
                  for i in range(int(LEN * FPS))])
    e = e / (np.percentile(e, 98) + 1e-9)
    return [round(float(min(v, 1.0)), 3) for v in e]


def main(ids=None):
    for d in ('songs', 'data', 'clips'):
        os.makedirs(os.path.join(WORK, d), exist_ok=True)
    songs = json.load(urllib.request.urlopen(SITE + '/wp-json/kjm/v1/songs'))
    for s in songs:
        i = s['id']
        if ids and str(i) not in ids:
            continue
        mp3 = os.path.join(SONGS, f'{i}.mp3')
        if not os.path.exists(mp3):
            urllib.request.urlretrieve(SITE + s['audio'], mp3)
        x = pcm(mp3)
        end = song_end(x)
        st = max(0.0, round(end - LEN, 2))
        cov = os.path.join(SONGS, f'{i}_cover.webp')
        if s.get('cover') and not os.path.exists(cov):
            urllib.request.urlretrieve(SITE + s['cover'], cov)
        has_cover = os.path.exists(cov)
        d = dict(id=i, titel=s['titel'], genre=s['genre'], bpm=s['bpm'], key=s['key'], album=s.get('album') or '',
                 tag=TAG.get(i, ''), fx=FX.get(s['genre'], 'bokeh'), start=st, env=envelope(x, st),
                 accent='#B29D75', glow='#E0B36A', cover=has_cover,
                 url=f'https://dj-kolja-one.de/meine-musik/?song={i}&utm_source=instagram&utm_medium=reel&utm_campaign=song-{i}')
        json.dump(d, open(os.path.join(WORK, 'data', f'{i}.json'), 'w'), ensure_ascii=False)
        subprocess.run(['ffmpeg', '-v', 'error', '-y', '-ss', str(st), '-t', str(LEN), '-i', mp3, '-vn',
                        '-af', 'afade=t=in:d=0.8',
                        '-c:a', 'aac', '-b:a', '192k', os.path.join(WORK, 'clips', f'{i}.m4a')], check=True)
        print(i, s['titel'], 'ab', st, 's', d['accent'], d['fx'])


if __name__ == '__main__':
    main(sys.argv[1:] or None)
