import json,sys,html
sys.path.insert(0,'.')
from pages import PAGES
def sz(v,d=None):
    if not v: return d
    return f"{v['size']}{v['unit']}" if v.get('size')!='' else d
def bx(v): return f"{v['top']}px {v['right']}px {v['bottom']}px {v['left']}px" if v else ""
def ty(s,p):
    if f"{p}_font_size" not in s: return ""
    c=f"font-family:'Fira Sans';font-size:{sz(s[p+'_font_size'])};font-weight:{s.get(p+'_font_weight',400)};"
    if s.get(p+'_line_height'): c+=f"line-height:{s[p+'_line_height']['size']};"
    if s.get(p+'_letter_spacing'): c+=f"letter-spacing:{sz(s[p+'_letter_spacing'])};"
    if s.get(p+'_text_transform'): c+=f"text-transform:{s[p+'_text_transform']};"
    if s.get(p+'_font_style'): c+=f"font-style:{s[p+'_font_style']};"
    return c
def el(e,mob):
    s=e["settings"]
    if e["elType"]=="container":
        d=s.get("flex_direction_mobile" if mob and s.get("flex_direction_mobile") else "flex_direction","column")
        if mob and s.get("hide_mobile"): return ""
        if not mob and s.get("hide_desktop"): return ""
        w=s.get("width_mobile" if mob else "width")
        css=f"display:flex;flex-direction:{d};gap:{s['flex_gap']['size']}px;box-sizing:border-box;"
        if s.get("flex_wrap"): css+="flex-wrap:wrap;"
        if s.get("flex_justify_content"): css+=f"justify-content:{s['flex_justify_content']};"
        if s.get("flex_align_items"): css+=f"align-items:{s['flex_align_items']};"
        if s.get("background_color"): css+=f"background:{s['background_color']};"
        p=s.get("padding_mobile") if mob and s.get("padding_mobile") else s.get("padding")
        if p: css+=f"padding:{bx(p)};"
        mh=s.get("min_height_mobile") if mob and s.get("min_height_mobile") else s.get("min_height")
        if mh: css+=f"min-height:{sz(mh)};"
        if w: css+=f"width:{sz(w)};" if d!="x" else ""
        if s.get("max_width"): css+=f"max-width:{sz(s['max_width'])};"
        if s.get("border_border"): 
            b=s["border_width"]; css+=f"border-style:solid;border-color:{s['border_color']};border-width:{b['top']}px {b['right']}px {b['bottom']}px {b['left']}px;"
        inner="".join(el(c,mob) for c in e["elements"])
        if s.get("content_width")=="boxed" and not e["isInner"]:
            inner=f"<div style='width:100%;max-width:1200px;margin:0 auto;display:flex;flex-direction:column;gap:{s['flex_gap']['size']}px;align-items:{s.get('flex_align_items','stretch')}'>{inner}</div>"
        if w and s.get("flex_wrap") is None and False: pass
        return f"<div style=\"{css}\">{inner}</div>"
    t=e["widgetType"]
    if mob and s.get("hide_mobile"): return ""
    if t=="heading":
        c=f"margin:0;color:{s['title_color']};text-align:{s['align']};"+ty(s,"typography")
        tx=s["title"]; 
        if s.get("link"): tx=f"<a style='color:inherit;text-decoration:none' href='#'>{tx}</a>"
        return f"<div style='flex:none'><{s['header_size']} style=\"{c}\">{tx}</{s['header_size']}></div>"
    if t=="text-editor":
        return f"<div style=\"color:{s['text_color']};text-align:{s['align']};{ty(s,'typography')}\">{s['editor']}</div>"
    if t=="button":
        c=f"display:inline-block;background:{s['background_color']};color:{s['button_text_color']};padding:{bx(s['text_padding'])};{ty(s,'typography')}text-decoration:none;"
        if s.get("border_border"): c+=f"border:1px solid {s['border_color']};"
        a={"center":"center","left":"left","right":"right"}[s["align"]]
        return f"<div style='text-align:{a}'><a style=\"{c}\">{s['text']}</a></div>"
    if t=="image": return f"<div style='text-align:{s['align']}'><img src='../logo/{s['image']['url'].split('/')[-1]}' style='width:{sz(s['width_mobile'] if mob else s['width'])}'></div>"
    if t=="spacer": return f"<div style='height:{sz(s['space'])}'></div>"
    if t=="divider": return f"<div style='width:60px;height:1px;background:{s['color']};margin:0 auto'></div>"
    if t=="icon-box":
        return f"<div style='text-align:{s['text_align']}'><div style='color:{s['primary_color']};font-size:28px'>◆</div><h3 style=\"color:{s['title_color']};{ty(s,'title_typography')}margin:10px 0\">{s['title_text']}</h3><p style=\"color:{s['description_color']};{ty(s,'description_typography')}margin:0\">{s['description_text']}</p></div>"
    if t=="icon-list":
        li="".join(f"<li style='margin-bottom:12px'><span style='color:{s['icon_color']}'>{'✓' if 'check' in i['selected_icon']['value'] else '✕'}</span>&nbsp;&nbsp;{i['text']}</li>" for i in s["icon_list"])
        return f"<ul style=\"list-style:none;padding:0;margin:0;color:{s['text_color']};{ty(s,'icon_typography')}\">{li}</ul>"
    if t=="accordion":
        return "".join(f"<div style='border:1px solid {s['border_color']};padding:20px 24px;color:{s['title_color']};{ty(s,'title_typography')}margin-bottom:-1px'>{x['tab_title']}<span style='float:right;color:{s['icon_color']}'>+</span></div>" for x in s["tabs"])
    if t=="shortcode": return f"<div style='color:#888;border:1px dashed #555;padding:20px'>{s['shortcode']}</div>"
    return f"<div>[{t}]</div>"
for slug in sys.argv[1:]:
    fn=[f for t,s,f in PAGES if s==slug][0]; c=fn()
    for mob in (False,True):
        body="".join(el(e,mob) for e in c)
        open(f"prev-{slug}{'-m' if mob else ''}.html","w").write(f"<html><head><meta charset=utf-8><link href='https://fonts.googleapis.com/css2?family=Fira+Sans:wght@200;300;400;500&display=swap' rel=stylesheet><style>body{{margin:0;background:#0F0C07}} p{{margin:0 0 1em}} *{{box-sizing:border-box}}</style></head><body>{body}</body></html>")
