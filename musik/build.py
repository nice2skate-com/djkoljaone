"""Baut aus musik-snippet.html die Plugin-Datei (assets/musikpult.html) und – falls die
Testdateien songs/preview-128.mp3 + songs/cover-300.jpg vorhanden sind – Demo-/Testseiten.
Aufruf: python3 musik/build.py (aus beliebigem Ordner)"""
import base64, os, re
HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(HERE)
P = lambda *a: os.path.join(ROOT, *a)
s = open(os.path.join(HERE, 'musik-snippet.html'), encoding='utf-8').read()
b64 = lambda f: base64.b64encode(open(f, 'rb').read()).decode()
final = re.sub(r' \{id:"s1".*?\},\n', '', s).replace('video:"__DEMOVIDEO__"', 'video:""')
assert '__SONG1__' not in final
open(os.path.join(HERE, 'musik-snippet-final.html'), 'w', encoding='utf-8').write(final)
open(P('plugin', 'dj-kolja-one-plattenkiste', 'assets', 'musikpult.html'), 'w', encoding='utf-8').write(final)
print('Plugin-Pult geschrieben:', len(final) // 1024, 'KB')
if os.path.exists(P('songs', 'preview-128.mp3')) and os.path.exists(P('songs', 'cover-300.jpg')):
    song, cover = b64(P('songs', 'preview-128.mp3')), b64(P('songs', 'cover-300.jpg'))
    rep = lambda t, vid, vm: t.replace('__DEMOVIDEO__', 'data:%s;base64,%s' % (vm, b64(os.path.join(HERE, vid)))).replace('__SONG1__', 'data:audio/mpeg;base64,' + song).replace('__COVER1__', 'data:image/jpeg;base64,' + cover)
    open(os.path.join(HERE, 'musik-snippet-demo.html'), 'w', encoding='utf-8').write(rep(s, 'demo-video.mp4', 'video/mp4'))
    fonts = open(P('site', 'fonts.css.txt'), encoding='utf-8').read().replace('{{', '{').replace('}}', '}')
    t = rep(s, 'demo.webm', 'video/webm')
    open(os.path.join(HERE, 'test.html'), 'w', encoding='utf-8').write("<!doctype html><html><head><meta charset=utf-8><meta name=viewport content='width=device-width,initial-scale=1'><style>%s body{margin:0;background:#0F0C07;padding:40px 20px}</style></head><body>%s</body></html>" % (fonts, t))
    print('Demo- und Testseite geschrieben')
