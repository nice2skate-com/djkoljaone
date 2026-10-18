"""Baut pro Song eine eigenständige Reel-HTML (arbeit/pages/<id>.html), rendert sie mit
media/motion/render.js (1080×1920, 30 s, 30 fps) und legt den Song-Ausschnitt darunter:
arbeit/out/reel_<titel>_<id>.mp4. Vorher prep.py ausführen.
Aufruf: python3 social/song-reels/build.py [--html-only] [id ...]"""
import base64, glob, json, os, re, shutil, subprocess, sys, unicodedata

HERE = os.path.dirname(os.path.abspath(__file__))
REPO = os.path.abspath(os.path.join(HERE, '..', '..'))
WORK = os.path.join(HERE, 'arbeit')
b64 = lambda p, m: 'data:%s;base64,%s' % (m, base64.b64encode(open(p, 'rb').read()).decode())


def slug(s):
    s = unicodedata.normalize('NFKD', s).encode('ascii', 'ignore').decode()
    return re.sub(r'[^a-z0-9]+', '-', s.lower()).strip('-')


def page(d):
    t = open(os.path.join(HERE, 'reel.html'), encoding='utf-8').read()
    for w in ('400', '600', '800'):
        t = t.replace('__F%s__' % w, b64(f'{REPO}/media/motion/fonts/fira{w}.woff2', 'font/woff2'))
    t = t.replace('__QRLIB__', open(f'{REPO}/plugin/dj-kolja-one-plattenkiste/assets/qrcode.js', encoding='utf-8').read())
    cov = os.path.join(WORK, 'songs', f"{d['id']}_cover.webp")
    t = t.replace('__COVER__', b64(cov, 'image/webp') if d['cover'] else '')
    t = t.replace('__PROFIL__', b64(os.path.join(HERE, 'profil.webp'), 'image/webp'))
    t = t.replace('__DATA__', json.dumps(d, ensure_ascii=False))
    return t


def main(args):
    html_only = '--html-only' in args
    ids = [a for a in args if a.isdigit()]
    os.makedirs(os.path.join(WORK, 'pages'), exist_ok=True)
    os.makedirs(os.path.join(WORK, 'out'), exist_ok=True)
    shutil.copy(f'{REPO}/media/motion/render.js', os.path.join(WORK, 'pages', 'render.js'))
    for f in sorted(glob.glob(os.path.join(WORK, 'data', '*.json'))):
        d = json.load(open(f))
        if ids and str(d['id']) not in ids:
            continue
        name = f"{d['id']}.html"
        open(os.path.join(WORK, 'pages', name), 'w', encoding='utf-8').write(page(d))
        if html_only:
            continue
        fr = os.path.join(WORK, 'frames_%s' % d['id'])
        shutil.rmtree(fr, ignore_errors=True)
        subprocess.run(['node', os.path.join(WORK, 'pages', 'render.js'), 'v', '30', '30', fr, name], check=True,
                       stdout=subprocess.DEVNULL)
        out = os.path.join(WORK, 'out', f"reel_{slug(d['titel'])}_{d['id']}.mp4")
        subprocess.run(['ffmpeg', '-v', 'error', '-y', '-framerate', '30', '-i', os.path.join(fr, '%05d.jpg'),
                        '-i', os.path.join(WORK, 'clips', f"{d['id']}.m4a"), '-map', '0:v', '-map', '1:a',
                        '-c:v', 'libx264', '-crf', '21', '-pix_fmt', 'yuv420p', '-c:a', 'copy', '-shortest',
                        '-movflags', '+faststart', out], check=True)
        shutil.rmtree(fr, ignore_errors=True)
        print('fertig', out, flush=True)


if __name__ == '__main__':
    main(sys.argv[1:])
