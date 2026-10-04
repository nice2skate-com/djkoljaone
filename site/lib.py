import json, random
random.seed(11)
def uid(): return ''.join(random.choice('0123456789abcdef') for _ in range(7))

B1,B2,GOLD,OFF,MUTED="#0F0C07","#1A1712","#B29D75","#F3F1E9","#A39E93"
F="Fira Sans"
LINE="rgba(178,157,117,0.3)"
PHONE="+49 172 7273707"; TEL="tel:+491727273707"; WA="https://wa.me/491727273707"; MAIL="anfrage@dj-kolja-one.de"
LOGO_NAV="/wp-content/uploads/2026/09/dj-kolja-one-logo-ohne-claim.png"
LOGO_FULL="/wp-content/uploads/2026/09/dj-kolja-one-logo-transparent.png"
KONTAKT="/kontakt/"

def px(v,u="px"): return {"unit":u,"size":v,"sizes":[]}
def box(t,r,b,l,u="px"): return {"unit":u,"top":str(t),"right":str(r),"bottom":str(b),"left":str(l),"isLinked":False}
def gap(v): return {"unit":"px","size":v,"column":str(v),"row":str(v),"isLinked":True}
def lnk(u): return {"url":u,"is_external":"","nofollow":""}

def typo(p,size,weight="400",m=None,lh=None,ls=None,tr=None):
    s={f"{p}_typography":"custom",f"{p}_font_family":F,f"{p}_font_size":px(size),f"{p}_font_weight":weight}
    if m: s[f"{p}_font_size_mobile"]=px(m)
    if lh: s[f"{p}_line_height"]={"unit":"em","size":lh,"sizes":[]}
    if ls is not None: s[f"{p}_letter_spacing"]=px(ls)
    if tr: s[f"{p}_text_transform"]=tr
    return s

def W(t,s): return {"id":uid(),"elType":"widget","widgetType":t,"settings":s,"elements":[]}

def con(children,direction="column",bg=None,pad=None,inner=True,full=False,g=20,**extra):
    s={"content_width":"full" if full else "boxed","flex_direction":direction,"flex_gap":gap(g)}
    if not full: s["boxed_width"]=px(1200)
    if bg: s.update({"background_background":"classic","background_color":bg})
    if pad: s["padding"]=pad
    s.update(extra)
    return {"id":uid(),"elType":"container","isInner":inner,"settings":s,"elements":children}

def col(w,wt=48,wm=100): return {"width":px(w,"%"),"width_tablet":px(wt,"%"),"width_mobile":px(wm,"%")}
ROW=dict(flex_wrap="wrap",flex_direction_mobile="column")

def H(text,tag="h2",size=44,color=OFF,align="center",weight="300",m=None,link=None,lh=1.15,ls=None,tr=None,**kw):
    s={"title":text,"header_size":tag,"align":align,"title_color":color}
    s.update(typo("typography",size,weight,m=m,lh=lh,ls=ls,tr=tr))
    if link: s["link"]=lnk(link)
    s.update(kw); return W("heading",s)

def T(html,color=MUTED,align="center",size=17,**kw):
    if not html.startswith("<"): html=f"<p>{html}</p>"
    s={"editor":html,"align":align,"text_color":color}; s.update(typo("typography",size,"400",lh=1.7)); s.update(kw)
    return W("text-editor",s)

def BTN(label,url,primary=True,align="center",**kw):
    s={"text":label,"link":lnk(url),"align":align,"border_radius":box(2,2,2,2),"text_padding":box(16,34,16,34)}
    s.update(typo("typography",14,"500",ls=1.5,tr="uppercase"))
    if primary: s.update({"background_color":GOLD,"button_text_color":B1,"hover_color":B1,"button_background_hover_color":"#C4B08A"})
    else: s.update({"background_color":"rgba(0,0,0,0)","button_text_color":OFF,"border_border":"solid","border_width":box(1,1,1,1),"border_color":OFF,"hover_color":B1,"button_background_hover_color":OFF})
    s.update(kw); return W("button",s)

def BTNS(*bs,align="center"):
    return con(list(bs),"row",g=16,flex_justify_content={"center":"center","left":"flex-start"}[align],flex_direction_mobile="column",flex_align_items="center" if align=="center" else "flex-start")

def ICONBOX(icon,title,desc,align="left",url=None):
    s={"selected_icon":{"value":icon,"library":"fa-brands" if icon.startswith("fab") else "fa-solid"},"title_text":title,"description_text":desc,"position":"top","text_align":align,
       "primary_color":GOLD,"icon_size":px(32),"title_color":OFF,"description_color":MUTED,"title_size":"h3"}
    s.update(typo("title_typography",22,"400")); s.update(typo("description_typography",16,"400",lh=1.7))
    if url: s["link"]=lnk(url)
    return W("icon-box",s)

def ILIST(items,icon="fas fa-check",color=GOLD,size=17):
    s={"icon_list":[{"text":t,"selected_icon":{"value":icon,"library":"fa-solid"},"_id":uid()} for t in items],
       "icon_color":color,"text_color":OFF,"icon_size":px(14),"space_between":px(12),"text_indent":px(10)}
    s.update(typo("icon_typography",size,"300",lh=1.5)); return W("icon-list",s)

def SPACER(h): return W("spacer",{"space":px(h)})
def DIV(): return W("divider",{"color":GOLD,"width":px(60),"align":"center","weight":px(1)})
def EYE(t,align="center"): return H(t,"p",13,GOLD,align,"500",ls=3,tr="uppercase")
UP="/wp-content/uploads/"
def IMG(h=320,hm=240,name=None,**kw):
    if name:
        kw.update(background_image={"url":UP+name,"id":""},background_position="center center",background_size="cover",background_repeat="no-repeat")
        kw.update(css_classes="kjo-m-"+name.rsplit(".",1)[0])
    kw.setdefault("min_height_mobile",px(hm)); return con([],bg=B2,min_height=px(h),**kw)

def LOGO(url,w,wm,align="left"):
    return W("image",{"image":{"url":url,"id":""},"image_size":"full","width":px(w),"width_mobile":px(wm),"align":align,
                      "link_to":"custom","link":lnk("/"),"_flex_size":"none"})

SEC=box(72,20,72,20)
def section(children,bg=B1,anchor=None,g=24,**kw):
    s=con(children,"column",bg=bg,pad=SEC,inner=False,g=g,flex_align_items="center",padding_mobile=box(48,20,48,20),**kw)
    if anchor: s["settings"]["_element_id"]=anchor
    return s
def head(eye,title,intro=None):
    out=[EYE(eye),H(title,"h2",46,m=32),DIV()]
    if intro: out.append(T(intro,MUTED,"center",17))
    out.append(SPACER(6)); return out

# ---------------- global parts ----------------
NAV=[("Hochzeit","/hochzeits-dj/"),("Geburtstag","/geburtstags-dj/"),("Firmenfeier","/firmenfeier-dj/"),
     ("Events","/event-dj/"),("Meine Musik","/meine-musik/"),("Über mich","/ueber-mich/"),("FAQ","/faq/"),("Regionen","/einsatzgebiete/")]
SERVICES=[("Hochzeits-DJ","/hochzeits-dj/"),("Geburtstags-DJ","/geburtstags-dj/"),("Firmenfeier-DJ","/firmenfeier-dj/"),("Event-DJ","/event-dj/")]
ORTE=[("Memmingen","dj-memmingen"),("Ulm","dj-ulm"),("Biberach","dj-biberach"),("Ravensburg","dj-ravensburg"),
      ("Kempten","dj-kempten"),("Füssen","dj-fuessen"),("Kaufbeuren","dj-kaufbeuren"),("Landsberg","dj-landsberg")]

def nav():
    links=con([H(t,"p",14,OFF,"center","400",link=u,ls=0.3,_flex_size="none") for t,u in NAV],"row",g=20,flex_justify_content="center",
              flex_align_items="center",hide_tablet="hidden-tablet",hide_mobile="hidden-mobile")
    top=con([LOGO(LOGO_NAV,210,170),links,
             BTN("Wunschtermin prüfen",KONTAKT,True,"right",text_padding=box(12,18,12,18),_flex_size="none",hide_mobile="hidden-mobile",
                 typography_font_size=px(13))],
            "row",g=18,flex_justify_content="space-between",flex_align_items="center",flex_direction_mobile="row",
            content_width="full")
    mob=con([H(t,"p",13,OFF,"center","400",link=u) for t,u in NAV+[("Kontakt",KONTAKT)]],"row",g=14,
            flex_wrap="wrap",flex_justify_content="center",flex_direction_mobile="row",hide_desktop="hidden-desktop",
            padding=box(12,0,0,0),border_border="solid",border_width=box(1,0,0,0),border_color=LINE)
    return con([top,mob],"column",bg=B1,pad=box(16,40,16,40),inner=False,full=True,g=8,padding_mobile=box(14,20,14,20),
               border_border="solid",border_width=box(0,0,1,0),border_color="rgba(178,157,117,0.25)")

def footer():
    def fcol(title,links,w=20):
        return con([H(title,"p",13,GOLD,"left","500",ls=2,tr="uppercase")]+[H(t,"p",15,MUTED,"left","300",link=u) for t,u in links],g=10,**col(w,48,100))
    cols=con([
        con([LOGO(LOGO_FULL,300,260),T("Premium DJ &amp; Moderation für Hochzeiten, Geburtstage, Firmenfeiern und Events in Oberschwaben, Ulm und dem Allgäu.",MUTED,"left",15),
             H(PHONE,"p",15,OFF,"left","400",link=TEL),H("WhatsApp schreiben","p",15,OFF,"left","400",link=WA)],g=12,**col(30,100,100)),
        fcol("Leistungen",SERVICES),
        fcol("Info",[("Meine Musik","/meine-musik/"),("Über mich","/ueber-mich/"),("FAQ","/faq/"),("Einsatzgebiete","/einsatzgebiete/"),("Kontakt",KONTAKT)]),
        fcol("Regionen",[("DJ "+o,f"/{s}/") for o,s in ORTE]),
    ],"row",g=30,**ROW,flex_justify_content="space-between")
    bottom=con([T("© 2026 DJ KOLJA ONE · Jedes Event findet nur einmal statt.",MUTED,"left",13),
                con([H(t,"p",13,MUTED,"right","300",link=u) for t,u in [("Impressum","/impressum/"),("Datenschutz","/datenschutz/")]],"row",g=20)],
               "row",flex_justify_content="space-between",flex_align_items="center",padding=box(24,0,0,0),
               border_border="solid",border_width=box(1,0,0,0),border_color="rgba(163,158,147,0.2)",flex_direction_mobile="column")
    return con([cols,SPACER(30),bottom],"column",bg=B1,pad=box(56,20,26,20),inner=False,g=10,
               border_border="solid",border_width=box(1,0,0,0),border_color="rgba(178,157,117,0.25)")

def stat(big,small,wide=False):
    return con([H(big,"p",32,GOLD,"center","300",m=24),T(small,MUTED,"center",14)],g=4,**col(23,48,48))
STATS=[("10+ Jahre","DJ-Erfahrung"),("Moderation","professionell &amp; souverän"),("Individuell","Musik nach euren Wünschen"),("Süddeutschland &amp; Überregional","Allgäu, Schwaben &amp; Auf Anfrage")]
def stats_row(stats=STATS,border=True):
    kw=dict(border_border="solid",border_width=box(1,0,0,0),border_color=LINE,padding=box(28,0,0,0)) if border else {}
    return con([stat(a,b) for a,b in stats],"row",g=20,flex_justify_content="center",flex_wrap="wrap",flex_direction_mobile="row",width=px(100,"%"),max_width=px(1000),**kw)

def stats_quiet(stats=STATS):
    k=lambda a,b: con([H(a,"p",22,GOLD,"center","300",m=18),T(b,MUTED,"center",13)],g=2,**col(23,48,48))
    return con([k(a,b) for a,b in stats],"row",g=16,flex_justify_content="center",flex_wrap="wrap",flex_direction_mobile="row",width=px(100,"%"),max_width=px(900))

def QUIET(stats=None):
    return con([stats_quiet(stats or STATS)],"column",bg=B1,pad=box(8,20,56,20),inner=False,flex_align_items="center",padding_mobile=box(0,20,40,20))

def VIDEOS(*keys):
    h=open("./site/snippets/videos.html").read().replace("__KEY__",",".join(keys))
    return W("html",{"html":h,"_element_width":"inherit","width":px(100,"%")})

def hero(eye,h1,sub,buttons,stats=STATS,minh=100,extra=None):
    kids=[EYE(eye),H(h1,"h1",64,OFF,m=34,lh=1.1),T(sub,OFF,"center",20,typography_font_size_mobile=px(17),_element_width="initial",
          _element_custom_width=px(820))]
    if buttons: kids+= [SPACER(8),BTNS(*buttons)]
    if stats: kids+= [SPACER(24),stats_row(stats)]
    if extra: kids+= [SPACER(22)]+extra
    return con(kids,"column",bg=B1,pad=box(72,20,56,20),inner=False,g=16,
               flex_justify_content="center",flex_align_items="center",padding_mobile=box(48,20,40,20))

def cta(title="Euer Termin ist noch frei?",text="Beliebte Samstage sind oft schon ein Jahr im Voraus vergeben. Fragt jetzt unverbindlich an."):
    return section([H(title,"h2",50,m=34),T(text,OFF),SPACER(8),
        BTNS(BTN("Wunschtermin prüfen",KONTAKT),BTN("WhatsApp schreiben",WA,False))],bg=B2,
        border_border="solid",border_width=box(1,0,1,0),border_color=LINE)

def tile(title,teaser,url,w=23):
    return con([IMG(240,200,name=TILE_IMG[url],border_radius=box(2,2,2,2)),H(title,"h3",26,OFF,"left","400"),T(teaser,MUTED,"left",16),
                H("Mehr erfahren →","p",13,GOLD,"left","500",link=url,ls=1.5,tr="uppercase")],g=14,**col(w))
TILE_IMG={"/hochzeits-dj/":"start_hochzeit.jpg","/geburtstags-dj/":"start_geburtstag.jpg","/firmenfeier-dj/":"start_firmenfeier.jpg","/event-dj/":"start_events.jpg"}
TILES={"/hochzeits-dj/":("Hochzeits-DJ","Der schönste Tag verdient den richtigen Soundtrack. Vom Sektempfang bis zum letzten Tanz."),
       "/geburtstags-dj/":("Geburtstags-DJ","Euer Fest, eure Musik – ausgelassen bis zum Schluss. Für den 18. genauso wie für den 80."),
       "/firmenfeier-dj/":("Firmenfeier-DJ","Musik, die eure Marke stärkt. Weihnachtsfeiern, Sommerfeste, Galas und Jubiläen."),
       "/event-dj/":("Event-DJ","Stadtfest, Vereinsfeier, Open Air oder Silvester: Stimmung auch für große Menschenmengen.")}
def tiles(urls=None,w=23):
    urls=urls or list(TILES)
    return con([tile(*TILES[u],u,w=w) for u in urls],"row",g=24,**ROW,flex_justify_content="center" if len(urls)<4 else "space-between")

def step(n,t,d,w=31):
    return con([H(n,"p",56,GOLD,"left","200",lh=1),H(t,"h3",24,OFF,"left","400"),T(d,MUTED,"left",16)],
               g=10,padding=box(0,0,0,22),border_border="solid",border_width=box(0,0,0,1),border_color="rgba(178,157,117,0.35)",**col(w,48,100))
def steps(items):
    w={3:31,4:23,5:18}[len(items)]
    return con([step(f"{i+1:02d}",t,d,w) for i,(t,d) in enumerate(items)],"row",g=24,**ROW,flex_justify_content="space-between")

def card(title,text,w=31,bg=B2,top=True):
    kw=dict(border_border="solid",border_width=box(2,0,0,0),border_color=GOLD) if top else {}
    return con([H(title,"h3",23,OFF,"left","400"),T(text,MUTED,"left",16)],bg=bg,pad=box(34,28,34,28),g=12,**col(w,48,100),**kw)
def cards(items,w=31,bg=B2):
    return con([card(t,d,w,bg) for t,d in items],"row",g=24,**ROW,flex_justify_content="center")

def list_card(title,items,w=31,bg=B1):
    return con([H(title,"h3",23,OFF,"left","400"),ILIST(items,size=16)],bg=bg,pad=box(34,28,34,28),g=16,
               border_border="solid",border_width=box(2,0,0,0),border_color=GOLD,**col(w,100,100))

def fit(yes,no):
    y=con([H("Das passt","h3",26,OFF,"left","400"),ILIST(yes,"fas fa-check",GOLD)],bg=B2,pad=box(40,34,40,34),g=18,**col(48,100,100))
    n=con([H("Eher nicht","h3",26,MUTED,"left","400"),ILIST(no,"fas fa-times",MUTED)],bg=B2,pad=box(40,34,40,34),g=18,**col(48,100,100))
    return con([y,n],"row",g=24,**ROW,flex_justify_content="space-between")

def split(eye,title,paras,extra=None,img_left=True,buttons=None,img=None):
    txt=[EYE(eye,"left"),H(title,"h2",44,OFF,"left",m=32)]+[T(p,MUTED,"left") for p in paras]
    if extra: txt.append(extra)
    if buttons: txt+= [SPACER(6),BTNS(*buttons,align="left")]
    img=IMG(440,300,name=img,**col(45,100,100)); t=con(txt,g=16,css_classes="kjo-txt",**col(50,100,100))
    return section([con([img,t] if img_left else [t,img],"row",g=50,flex_align_items="center",flex_justify_content="space-between",css_classes="kjo-split",**ROW)])

STAERKEN=[("fas fa-music","Individuelle Musikplanung","Vorgespräch, Wunsch- und No-Go-Listen. Keine Playlist von der Stange, sondern eure Musik."),
          ("fas fa-sliders-h","Geprüfte Profi-Technik","Eigene Ton- und Lichtanlage, regelmäßig geprüft, dezent aufgebaut. Mit Funkmikrofon für Reden."),
          ("fas fa-microphone","Professionelle Moderation","Einlauf, Reden, Spiele und Programmpunkte: souverän moderiert, nie aufdringlich.")]
def staerken(title="Warum DJ KOLJA ONE?"):
    cards_=[con([ICONBOX(*i)],bg=B1,pad=box(36,30,36,30),**col(31,100,100)) for i in STAERKEN]
    return section(head("Meine Stärken",title)+[con(cards_,"row",g=24,**ROW,flex_justify_content="space-between")],bg=B2)

REVIEWS={
 "h1":("Die Tanzfläche war ab dem Eröffnungstanz nicht mehr leer. Genau so haben wir uns das gewünscht.","Brautpaar · Hochzeit im Allgäu"),
 "h2":("Kolja hat unseren ganzen Abend so souverän moderiert, dass wir uns um nichts kümmern mussten.","Brautpaar · Hochzeit in Memmingen"),
 "h3":("Von der Oma bis zu den Studienfreunden – alle haben getanzt. Die Musikauswahl war perfekt auf uns abgestimmt.","Brautpaar · Hochzeit in Oberschwaben"),
 "g1":("Von 20 bis 75 hat jeder getanzt. Besser hätte mein 50. nicht laufen können.","Gastgeber · 50. Geburtstag"),
 "g2":("Unkompliziert in der Planung und am Abend genau das richtige Gespür für unsere Gäste.","Gastgeberin · 30. Geburtstag in Ulm"),
 "f1":("Professionell in der Abstimmung, souverän am Abend. Unsere Mitarbeiter reden heute noch davon.","HR-Leitung · Weihnachtsfeier in Ulm"),
 "f2":("Pünktlich, perfekt vorbereitet und mit einem feinen Gespür für den Moment. Wir buchen wieder.","Geschäftsführung · Firmenjubiläum"),
}
def review(key,w=31):
    q,who=REVIEWS[key]
    return con([H("★★★★★","p",17,GOLD,"left","400",ls=3),T(f"„{q}“",OFF,"left",17,typography_font_style="italic"),
                H(who,"p",12,MUTED,"left","500",ls=1.5,tr="uppercase")],bg=B2,pad=box(34,30,34,30),g=14,**col(w,48,100))
def reviews(keys,title="Was Gastgeber sagen",bg=B1):
    w=23 if len(keys)>3 else 31
    return section(head("Bewertungen",title)+[con([review(k,w) for k in keys],"row",g=24,**ROW,flex_justify_content="center")],bg=bg)

def accordion(items):
    s={"tabs":[{"tab_title":q,"tab_content":f"<p>{a}</p>","_id":uid()} for q,a in items],
       "selected_icon":{"value":"fas fa-plus","library":"fa-solid"},"selected_active_icon":{"value":"fas fa-minus","library":"fa-solid"},
       "border_color":LINE,"border_width":px(1),"title_background":B1,"title_color":OFF,"tab_active_color":GOLD,
       "icon_color":GOLD,"icon_active_color":GOLD,"content_background_color":B1,"content_color":MUTED,
       "title_padding":box(22,24,22,24),"content_padding":box(0,24,24,24)}
    s.update(typo("title_typography",19,"400")); s.update(typo("content_typography",16,"400",lh=1.7))
    return con([W("accordion",s)],max_width=px(860),width=px(100,"%"))
def faq(items,title="Gut zu wissen",more=True,bg=B2,eye="Häufige Fragen"):
    kids=head(eye,title)+[accordion(items)]
    if more: kids+=[SPACER(8),H("Alle Fragen ansehen →","p",13,GOLD,"center","500",link="/faq/",ls=1.5,tr="uppercase")]
    return section(kids,bg=bg)

def orte(title="Euer DJ in Oberschwaben, Ulm und dem Allgäu",exclude=None,bg=B1):
    chips=[con([H("DJ "+o,"p",19,OFF,"center","300",link=f"/{s}/")],pad=box(18,10,18,10),border_border="solid",
               border_width=box(1,1,1,1),border_color="rgba(178,157,117,0.35)",**col(23,31,48)) for o,s in ORTE if o!=exclude]
    return section(head("Einsatzgebiete",title)+[T("Von Fellheim aus bin ich schnell bei euch – und gern auch darüber hinaus.",MUTED),
        con(chips,"row",g=16,flex_wrap="wrap",flex_direction_mobile="row",flex_justify_content="center"),SPACER(6),
        H("Alle Einsatzgebiete →","p",13,GOLD,"center","500",link="/einsatzgebiete/",ls=1.5,tr="uppercase")],bg=bg)

def text_page(title,html_blocks):
    body=[H(title,"h1",52,OFF,"left",m=36),DIV()]+[T(h,MUTED,"left",16) for h in html_blocks]
    return [nav(),section([con(body,g=18,max_width=px(860),width=px(100,"%"))],bg=B1),footer()]
