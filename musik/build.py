import base64,re
s=open('musik-snippet.html').read()
b64=lambda f:base64.b64encode(open(f,'rb').read()).decode()
final=re.sub(r' \{id:"s1".*?\},\n','',s).replace('video:"__DEMOVIDEO__"','video:""')
assert '__SONG1__' not in final
open('musik-snippet-final.html','w').write(final)
demo=s.replace('__DEMOVIDEO__','data:video/mp4;base64,'+b64('demo-video.mp4')).replace('__SONG1__','data:audio/mpeg;base64,'+b64('songs/preview-128.mp3')).replace('__COVER1__','data:image/jpeg;base64,'+b64('songs/cover-300.jpg'))
open('musik-snippet-demo.html','w').write(demo)
fonts=open('./site/fonts.css.txt').read().replace('{{','{').replace('}}','}')
t=s.replace('__DEMOVIDEO__','data:video/webm;base64,'+b64('demo.webm')).replace('__SONG1__','data:audio/mpeg;base64,'+b64('songs/preview-128.mp3')).replace('__COVER1__','data:image/jpeg;base64,'+b64('songs/cover-300.jpg'))
open('test.html','w').write(f"<!doctype html><html><head><meta charset=utf-8><meta name=viewport content='width=device-width,initial-scale=1'><style>{fonts} body{{margin:0;background:#0F0C07;padding:40px 20px}}</style></head><body>{t}</body></html>")
print(len(final)//1024,len(demo)//1024)
