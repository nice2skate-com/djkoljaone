import json,sys,base64,html
sys.path.insert(0,'.')
from pages import PAGES
LOGOS={n:"data:image/png;base64,"+base64.b64encode(open(f"./logo/{n}","rb").read()).decode() for n in ["dj-kolja-one-logo-ohne-claim.png","dj-kolja-one-logo-transparent.png"]}
css=[]; 
def sz(v): return f"{v['size']}{v['unit']}" if v and v.get('size')!='' else None
def bx(v): return f"{v['top']}px {v['right']}px {v['bottom']}px {v['left']}px"
def ty(s,p):
    if f"{p}_font_size" not in s: return ""
    c=f"font-family:'Fira Sans',sans-serif;font-size:{sz(s[p+'_font_size'])};font-weight:{s.get(p+'_font_weight',400)};"
    if s.get(p+'_line_height'): c+=f"line-height:{s[p+'_line_height']['size']};"
    if s.get(p+'_letter_spacing'): c+=f"letter-spacing:{sz(s[p+'_letter_spacing'])};"
    if s.get(p+'_text_transform'): c+=f"text-transform:{s[p+'_text_transform']};"
    if s.get(p+'_font_style'): c+=f"font-style:{s[p+'_font_style']};"
    return c
def rule(i,d,t=None,m=None):
    css.append(f"#{i}{{{d}}}")
    if t: css.append(f"@media(max-width:1024px){{#{i}{{{t}}}}}")
    if m: css.append(f"@media(max-width:767px){{#{i}{{{m}}}}}")
def href(u,slug):
    if u.startswith("/"): 
        p=u.strip("/").split("#")
        return f"#/{p[0] or 'start'}" + (f"|{p[1]}" if len(p)>1 else "")
    if u.startswith("#"): return f"#/{slug}|{u[1:]}"
    return u
def hid(s):
    c=[]
    if s.get("hide_desktop"): c.append("hd")
    if s.get("hide_tablet"): c.append("ht")
    if s.get("hide_mobile"): c.append("hm")
    return " ".join(c)
def el(e,slug):
    s=e["settings"]; i="e"+e["id"]+slug.replace("-","")[:6]
    if e["elType"]=="container":
        d=f"display:flex;flex-direction:{s.get('flex_direction','column')};gap:{s['flex_gap']['size']}px;"
        if s.get("flex_wrap"): d+="flex-wrap:wrap;"
        if s.get("flex_justify_content"): d+=f"justify-content:{s['flex_justify_content']};"
        if s.get("flex_align_items"): d+=f"align-items:{s['flex_align_items']};"
        if s.get("background_color"): d+=f"background:{s['background_color']};"
        if s.get("padding"): d+=f"padding:{bx(s['padding'])};"
        if s.get("min_height"): d+=f"min-height:{sz(s['min_height'])};"
        if s.get("width"): d+=f"width:{sz(s['width'])};"
        elif e["isInner"]: d+="width:100%;" if s.get("flex_direction")=="row" or not s.get("width") else ""
        if s.get("max_width"): d+=f"max-width:{sz(s['max_width'])};"
        if s.get("border_border"):
            b=s["border_width"]; d+=f"border-style:solid;border-color:{s['border_color']};border-width:{b['top']}px {b['right']}px {b['bottom']}px {b['left']}px;"
        t=""; m=""
        if s.get("width_tablet"): t+=f"width:{sz(s['width_tablet'])};"
        if s.get("flex_direction_mobile"): m+=f"flex-direction:{s['flex_direction_mobile']};"
        if s.get("width_mobile"): m+=f"width:{sz(s['width_mobile'])};"
        if s.get("padding_mobile"): m+=f"padding:{bx(s['padding_mobile'])};"
        if s.get("min_height_mobile"): m+=f"min-height:{sz(s['min_height_mobile'])};"
        rule(i,d,t,m)
        inner="".join(el(c,slug) for c in e["elements"])
        if not e["elements"] and s.get("background_color") and s.get("min_height"): inner="<span class=ph>"+(s["background_image"]["url"].split("/")[-1] if s.get("background_image") else "Foto")+"</span>"
        if s.get("content_width")=="boxed" and not e["isInner"]:
            inner=f"<div class=boxed style='gap:{s['flex_gap']['size']}px;align-items:{s.get('flex_align_items','stretch')}'>{inner}</div>"
        anc=f" data-anchor='{s['_element_id']}'" if s.get("_element_id") else ""
        return f"<div id={i} class='con {hid(s)} {s.get('css_classes','')}'{anc}>{inner}</div>"
    t=e["widgetType"]; cl=hid(s)
    if t=="heading":
        tag=s['header_size']; st=f"margin:0;color:{s['title_color']};text-align:{s['align']};"+ty(s,"typography")
        m=f"font-size:{sz(s['typography_font_size_mobile'])};" if s.get("typography_font_size_mobile") else None
        rule(i,st,None,m); tx=s["title"]
        if s.get("link"): tx=f"<a href='{href(s['link']['url'],slug)}'>{tx}</a>"
        return f"<div class='w {cl}' style='flex:none'><{tag} id={i}>{tx}</{tag}></div>"
    if t=="text-editor":
        st=f"color:{s['text_color']};text-align:{s['align']};"+ty(s,"typography")
        if s.get("_element_custom_width"): st+=f"max-width:{sz(s['_element_custom_width'])};"
        rule(i,st,None,f"font-size:{sz(s['typography_font_size_mobile'])};" if s.get("typography_font_size_mobile") else None)
        return f"<div id={i} class='w txt {cl}'>{s['editor']}</div>"
    if t=="button":
        st=f"background:{s['background_color']};color:{s['button_text_color']};padding:{bx(s['text_padding'])};"+ty(s,"typography")
        if s.get("border_border"): st+=f"border:1px solid {s['border_color']};"
        rule(i,st)
        u=s["link"]["url"]; tgt=" target=_blank" if u.startswith("http") else ""
        return f"<div class='w {cl}' style='text-align:{s['align']};{'flex:none;' if s.get('_flex_size')=='none' else ''}white-space:nowrap'><a id={i} class='btn {'pri' if s['background_color'].startswith('#') else 'sec'}' href='{href(u,slug)}'{tgt}>{s['text']}</a></div>"
    if t=="image":
        src=LOGOS[s['image']['url'].split('/')[-1]]
        rule(i,f"width:{sz(s['width'])};display:block",None,f"width:{sz(s['width_mobile'])}")
        return f"<div class='w {cl}' style='flex:none;text-align:{s['align']}'><a href='#/start'><img id={i} data-logo='{s['image']['url'].split('/')[-1]}' alt='DJ KOLJA ONE'></a></div>"
    if t=="spacer": return f"<div style='height:{sz(s['space'])}'></div>"
    if t=="divider": return "<div class=div></div>"
    if t=="icon-box":
        ic=s['selected_icon']['value'].replace('fas ','fa-solid ').replace('fab ','fa-brands ')
        a=f"<a href='{href(s['link']['url'],slug)}'>{s['title_text']}</a>" if s.get("link") else s['title_text']
        return f"<div class='w' style='text-align:{s['text_align']}'><i class='{ic}' style='color:{s['primary_color']};font-size:30px'></i><h3 style=\"color:{s['title_color']};{ty(s,'title_typography')}margin:16px 0 10px\">{a}</h3><p style=\"color:{s['description_color']};{ty(s,'description_typography')}margin:0\">{s['description_text']}</p></div>"
    if t=="icon-list":
        li="".join(f"<li><i class='{x['selected_icon']['value'].replace('fas ','fa-solid ')}' style='color:{s['icon_color']}'></i><span>{x['text']}</span></li>" for x in s["icon_list"])
        return f"<ul class=il style=\"color:{s['text_color']};{ty(s,'icon_typography')}\">{li}</ul>"
    if t=="accordion":
        return "<div class=acc>"+"".join(f"<details><summary style=\"{ty(s,'title_typography')}\">{x['tab_title']}<i class='fa-solid fa-plus'></i></summary><div style=\"{ty(s,'content_typography')}\">{x['tab_content']}</div></details>" for x in s["tabs"])+"</div>"
    if t=="html":
        h=s['html']
        if 'KJM_DEMO' in h:
            h=open('./musik/musik-snippet-demo.html').read()
        return f"<div class='w' style='width:100%'>{h}</div>"
    if t=="shortcode": return "<div class=form><label>Name<input></label><label>E-Mail<input></label><label>Telefon<input></label><label>Datum der Feier<input type=date></label><label>Ort / Location<input></label><label>Anlass<select><option>Hochzeit<option>Geburtstag<option>Firmenfeier<option>Event</select></label><label class=full>Nachricht<textarea rows=4></textarea></label><p class=note>Vorschau – hier erscheint später dein WPForms-Formular.</p></div>"
    return ""
pages=[]; opts=[]
for title,slug,fn in PAGES:
    body="".join(el(e,slug) for e in fn())
    pages.append(f"<main class=page data-page='{slug}'>{body}</main>"); opts.append(f"<option value='{slug}'>{html.escape(title)}</option>")
doc=f"""<!doctype html><html lang=de><head><meta charset=utf-8><meta name=viewport content='width=device-width,initial-scale=1'>
<title>DJ KOLJA ONE – Vorschau</title>

<link rel=stylesheet href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css'>
<style>
"""+open('./site/fonts.css.txt').read().replace('{{','{').replace('}}','}')+f"""
:root{{--b1:#0F0C07;--b2:#1A1712;--gold:#B29D75;--off:#F3F1E9;--muted:#A39E93}}
*{{box-sizing:border-box}}html{{scroll-behavior:smooth}}body{{margin:0;background:var(--b1);color:var(--off);font-family:'Fira Sans',sans-serif;-webkit-font-smoothing:antialiased}}
a{{color:inherit;text-decoration:none}} h1,h2,h3,p{{margin:0}} .txt p{{margin:0 0 1em}} .txt p:last-child{{margin:0}} .txt h3{{color:var(--off);font-weight:400;font-size:20px;margin:1.4em 0 .5em}}
.page{{display:none}}.page.on{{display:block}} .con{{position:relative;min-width:0}} .boxed{{width:100%;max-width:1200px;margin:0 auto;display:flex;flex-direction:column}}
.w a:hover{{color:var(--gold)}} .btn{{display:inline-block;border-radius:2px;transition:.2s}} .btn.pri:hover{{background:#C4B08A!important;color:var(--b1)!important}} .btn.sec:hover{{background:var(--off)!important;color:var(--b1)!important}}
.div{{width:60px;height:1px;background:var(--gold);margin:0 auto}}
.ph{{margin:auto;color:#7d7566;font-size:12px;letter-spacing:1px}} .con:has(>.ph){{display:flex;align-items:center;justify-content:center}}
.il{{list-style:none;padding:0;margin:0}} .il li{{display:flex;gap:12px;margin-bottom:12px;align-items:baseline}} .il i{{font-size:13px}}
.acc details{{border:1px solid rgba(178,157,117,.3);margin-bottom:-1px}} .acc summary{{list-style:none;cursor:pointer;padding:22px 24px;display:flex;justify-content:space-between;gap:16px;color:var(--off)}}
.acc summary::-webkit-details-marker{{display:none}} .acc summary i{{color:var(--gold);font-size:13px;transition:.2s}} .acc details[open] summary{{color:var(--gold)}} .acc details[open] summary i{{transform:rotate(45deg)}}
.acc details>div{{padding:0 24px 24px;color:var(--muted)}} .acc p{{margin:0}}
.form{{display:grid;grid-template-columns:1fr 1fr;gap:14px}} .form label{{display:flex;flex-direction:column;gap:6px;font-size:13px;color:var(--muted);letter-spacing:1px;text-transform:uppercase}}
.form input,.form select,.form textarea{{background:var(--b1);border:1px solid rgba(178,157,117,.35);color:var(--off);padding:12px;font:inherit;font-size:15px;text-transform:none;letter-spacing:0}} .form .full{{grid-column:1/-1}} .note{{grid-column:1/-1;font-size:13px;color:#6d675c}}
#bar{{position:fixed;right:16px;bottom:16px;z-index:99;background:rgba(26,23,18,.95);border:1px solid rgba(178,157,117,.4);padding:10px 12px;display:flex;gap:10px;align-items:center;font-size:12px;color:var(--muted);letter-spacing:1px;text-transform:uppercase;backdrop-filter:blur(6px)}}
#bar select{{background:var(--b1);color:var(--off);border:1px solid rgba(178,157,117,.4);padding:6px 8px;font:inherit;text-transform:none;letter-spacing:0;font-size:14px}}
@media(min-width:1025px){{.hd{{display:none!important}}}} @media(max-width:1024px) and (min-width:768px){{.ht{{display:none!important}}}} @media(max-width:767px){{.hm{{display:none!important}} .form{{grid-template-columns:1fr}} #bar{{left:12px;right:12px;bottom:12px}} #bar select{{flex:1}}}}
{''.join(css)}
</style></head><body>
{''.join(pages)}
<div id=bar><span>Vorschau</span><select id=sel>{''.join(opts)}</select></div>
<script>
const sel=document.getElementById('sel');
function go(){{let h=(location.hash||'#/start').slice(2).split('|');let p=h[0]||'start';
 if(!document.querySelector('[data-page="'+p+'"]'))p='start';
 document.querySelectorAll('.page').forEach(x=>x.classList.toggle('on',x.dataset.page===p));sel.value=p;
 if(h[1]){{const a=document.querySelector('[data-page="'+p+'"] [data-anchor="'+h[1]+'"]');if(a){{setTimeout(()=>a.scrollIntoView({{behavior:'smooth'}}),30);return}}}}
 window.scrollTo(0,0);}}
const L={json.dumps(LOGOS)};document.querySelectorAll('img[data-logo]').forEach(i=>i.src=L[i.dataset.logo]);\ndocument.addEventListener('click',e=>{{const a=e.target.closest('a[href^="/"]');if(a){{e.preventDefault();const u=a.getAttribute('href').replace(/^\/|\/$/g,'');location.hash='#/'+(u||'start')}}}});
sel.onchange=()=>location.hash='#/'+sel.value; addEventListener('hashchange',go); go();
</script></body></html>"""
import base64 as _b
_v="data:video/mp4;base64,"+_b.b64encode(open("./media/demo.mp4","rb").read()).decode()
_x="<style>"+open("./media/kjo-media.css").read()+"</style><script>window.KJO_MEDIA={start_2:{vid:'"+_v+"'},event_hochzeit_3:{vid:'"+_v+"'}};"+open("./media/kjo-media.js").read()+"</script>"
doc=doc.replace("</body></html>",_x+"</body></html>")
open("./dj-kolja-one-vorschau.html","w").write(doc); print(len(doc)//1024,"KB")
