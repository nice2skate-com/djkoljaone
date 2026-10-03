s=open('lib.py').read()
s=s.replace("def hero(eye,h1,sub,buttons,stats=STATS,minh=100):","def hero(eye,h1,sub,buttons,stats=STATS,minh=100,extra=None):")
s=s.replace("    if stats: kids+= [SPACER(40),stats_row(stats)]\n","    if stats: kids+= [SPACER(40),stats_row(stats)]\n    if extra: kids+= [SPACER(36)]+extra\n")
open('lib.py','w').write(s)
p=open('pages.py').read()
old='''           "Musik, die Marken stärkt. Stimmung, die Menschen verbindet.",[BTN(*WISH),BTN("Leistungen entdecken","#leistungen",False)]),'''
new='''           "Musik, die Marken stärkt. Stimmung, die Menschen verbindet.",[BTN(*WISH),BTN("Leistungen entdecken","#leistungen",False)],
           stats=None,extra=[W("html",{"html":open("./deck/deck-snippet.html").read()})]),
      con([stats_row(border=False)],"column",bg=B2,pad=box(40,20,40,20),inner=False,flex_align_items="center",
          border_border="solid",border_width=box(1,0,1,0),border_color=LINE),'''
assert old in p; p=p.replace(old,new); open('pages.py','w').write(p)
r=open('render_all.py').read()
r=r.replace('''    if t=="shortcode":''','''    if t=="html": return f"<div class='w' style='width:100%'>{s['html']}</div>"
    if t=="shortcode":''')
JS=r"""document.addEventListener('click',e=>{{const a=e.target.closest('a[href^="/"]');if(a){{e.preventDefault();const u=a.getAttribute('href').replace(/^\/|\/$/g,'');location.hash='#/'+(u||'start')}}}});
sel.onchange="""
r=r.replace("sel.onchange=",JS,1)
open('render_all.py','w').write(r)
