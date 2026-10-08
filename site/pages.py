from lib import *

FAQ_ALL={
"Buchung & Preise":[
 ("Was kostet ein DJ bei DJ KOLJA ONE?","Das hängt von Dauer, Gästezahl, Technik und Anfahrt ab – deshalb gibt es Basis- und Optionspakete und damit ein Angebot, das genau zu eurem Fest passt. Schickt mir einfach euer Datum, ich melde mich innerhalb von 24 Stunden."),
 ("Wie läuft eine Buchung ab?","In drei Schritten: Ihr schickt mir Datum, Ort und Anlass. Wir sprechen kostenlos und unverbindlich per Telefon oder Video über eure Wünsche. Danach bekommt ihr ein individuelles Angebot – mit eurer Bestätigung ist euer Termin fest reserviert."),
 ("Können wir uns vorher kennenlernen?","Ja, unbedingt. Das Erstgespräch per Telefon oder Video ist kostenlos und unverbindlich. So merkt ihr schnell, ob wir zusammenpassen."),
 ("Wie früh sollten wir buchen?","Für Hochzeiten und Samstage in der Hochsaison (Mai bis September) empfehle ich 9–12 Monate Vorlauf, für Weihnachtsfeiern eine Anfrage bis zum Frühsommer. Für Geburtstage und andere Firmenfeiern reichen oft 3–6 Monate. Auch kurzfristig lohnt sich eine Anfrage – prüft einfach euren Wunschtermin."),
 ("Was passiert, wenn du krank wirst?","Dann übernimmt ein erfahrener DJ aus meinem Netzwerk – mit derselben Vorbereitung und ohne Mehrkosten für euch. Euer Abend ist abgesichert."),
 ("Muss ich GEMA-Gebühren zahlen?","Private Feiern mit geladenen Gästen sind in der Regel nicht GEMA-pflichtig. Bei öffentlichen Veranstaltungen ist der Veranstalter für die Anmeldung zuständig – ich weise euch im Vorgespräch darauf hin."),
],
"Musik":[
 ("Können wir Musikwünsche angeben?","Unbedingt. Im Vorgespräch legen wir gemeinsam eine Wunsch- und eine No-Go-Liste fest. Eure Lieblingssongs bekommen ihren Platz – zum richtigen Zeitpunkt."),
 ("Nimmst du auch Wünsche von Gästen an?","Ja, gern – solange sie zur Stimmung und zu euren Vorgaben passen. So fühlt sich jeder Gast abgeholt, ohne dass der rote Faden verloren geht."),
 ("Welche Musik spielst du?","Von Pop, Rock und Schlager über 80er, 90er und 2000er bis zu aktuellen Charts, House und Latin. Entscheidend ist euer Publikum – deshalb stelle ich die Musik für jede Feier neu zusammen."),
 ("Warum ein DJ statt einer Playlist?","Eine Playlist kennt eure Gäste nicht. Ich sehe, wann die Tanzfläche voll ist, wann sie eine Pause braucht und welcher Song jetzt den Unterschied macht – und reagiere live darauf. Dazu kommen Moderation und Technik aus einer Hand."),
],
"Technik & Ablauf":[
 ("Bringst du eigene Technik mit?","Ja. Ton- und Lichtanlage sind geprüft, auf eure Raumgröße abgestimmt und werden dezent aufgebaut. Ein Funkmikrofon für Reden gehört immer dazu."),
 ("Was brauchst du vor Ort?","Nur eine ebene Fläche für den DJ-Platz und einen Stromanschluss in der Nähe. Alle Details kläre ich vorab direkt mit euch oder der Location."),
 ("Moderierst du auch?","Ja – professionell und auf Wunsch den ganzen Abend: Einlauf, Reden, Programmpunkte, Spiele und Ansagen. Souverän, herzlich und nie aufdringlich."),
 ("Stimmst du dich mit Location und Dienstleistern ab?","Ja. Ich spreche mich mit Location, Catering, Fotografen oder Agentur ab, damit Zeitplan und Technik reibungslos zusammenpassen – ihr müsst nichts koordinieren."),
 ("Wie groß ist dein Einsatzgebiet?","Mein Schwerpunkt liegt in Memmingen, im Allgäu und in Schwaben – von Ulm, Biberach und Ravensburg bis Kempten, Füssen und Landsberg. Für besondere Events komme ich auch weiter: Fragt einfach an."),
],
}
def fq(*qs):
    flat={q:a for v in FAQ_ALL.values() for q,a in v}
    return [(q,flat[q]) for q in qs]

WISH=("Wunschtermin prüfen",KONTAKT)

# ---------------- Startseite ----------------
START_STAERKEN=[
 ("fas fa-music","Musik, auf euch und eure Gäste angepasst","Im Vorgespräch legen wir Wunsch- und No-Go-Liste fest. Ihr hört und tanzt zu Songs, die ihr liebt – und es kommt nichts, was ihr nicht hören wollt."),
 ("fas fa-sliders-h","Minimaler Aufwand für euch","Eigene Ton- und Lichtanlage, Funkmikrofon für Reden, Abstimmung mit Location und Dienstleistern – alles aus einer Hand."),
 ("fas fa-microphone","Ein Abend mit rotem Faden","Einlauf, Reden, Spiele, Programmpunkte: souverän und herzlich moderiert, nie aufdringlich."),
 ("fas fa-shield-alt","Ausfallsicher","Sollte ich krank werden, übernimmt ein erfahrener DJ aus meinem Netzwerk – gleich vorbereitet, ohne Mehrkosten für euch.")]
def start():
    vor=split("Vorstellung","Hallo, ich bin Kolja.",[
        "Seit über 10 Jahren stehe ich als DJ und Moderator auf Hochzeiten, Geburtstagen, Firmenfeiern und Events zwischen Memmingen, Allgäu und Schwaben.",
        "Mein Versprechen steckt im Claim: <strong>Jedes Event findet nur einmal statt.</strong> Deshalb bereite ich jede Feier persönlich mit euch vor, lese die Tanzfläche live und halte euch den Rücken frei – damit ihr einfach feiern könnt."],
        buttons=[BTN("Mehr über mich","/ueber-mich/",False,"left"),BTN(*WISH,True,"left")],img="kolja_portrait.jpg",port=True,bg=B2)
    return [nav(),
      hero("DJ &amp; Moderator aus Fellheim · seit über 10 Jahren","Premium DJ für Hochzeiten &amp; Events in Memmingen, Allgäu &amp; Schwaben",
           "Ihr feiert – ich sorge für die volle Tanzfläche und einen Abend, der einfach läuft. Mit eurer Musik und einer Moderation, die euch alles abnimmt.",
           [BTN(*WISH),BTN("Per WhatsApp anfragen",WA,False)],
           stats=None,extra=[T("Unverbindlich · Antwort innerhalb von 24 Stunden",MUTED,"center",14),
                             W("html",{"html":open("./deck/deck-snippet.html").read(),"_element_width":"inherit","width":px(100,"%")})]),
      con([VIDEOS("start")],"column",bg=B1,pad=box(0,20,56,20),inner=False,flex_align_items="center",padding_mobile=box(0,20,40,20)),
      section(head("Meine Leistungen","Wofür bucht ihr mich?")+[tiles()],anchor="leistungen"),
      staerken("Ihr feiert. Ich kümmere mich um den Rest.",START_STAERKEN,eye="Was ihr davon habt"),
      reviews(["h1","f1","g1","h2","f2","g2","h3"]),
      vor,
      section(head("So einfach geht's","In drei Schritten zu eurem DJ")+[steps([
          ("Wunschtermin prüfen","Datum, Ort und Anlass – per Formular oder WhatsApp in 2 Minuten. Ich melde mich innerhalb von 24 Stunden."),
          ("Kennenlernen","Telefonisch oder per Video besprechen wir Musik, Ablauf und Wünsche – kostenlos und unverbindlich."),
          ("Termin sichern","Ihr bekommt ein individuelles Angebot. Mit eurer Bestätigung ist euer Datum fest reserviert.")]),
          SPACER(10),BTN("Jetzt Wunschtermin prüfen",KONTAKT)],bg=B1),
      faq(fq("Was kostet ein DJ bei DJ KOLJA ONE?","Wie früh sollten wir buchen?","Was passiert, wenn du krank wirst?","Moderierst du auch?"),bg=B2),
      orte(bg=B1),
      cta("Euer Datum ist noch frei? Dann sichert es euch.","Beliebte Samstage sind oft ein Jahr im Voraus vergeben. Eine Anfrage dauert zwei Minuten und ist unverbindlich."),
      section(head("Kontakt","Wie möchtet ihr anfragen?")+[con([
          con([ICONBOX(i,t,d,"center",u)],bg=B1,pad=box(36,24,36,24),**col(31,100,100)) for i,t,d,u in [
            ("fas fa-phone","Anrufen",PHONE+" – direkt mit mir sprechen",TEL),("fas fa-envelope-open-text","Formular","In 2 Minuten ausgefüllt – Antwort innerhalb von 24 Stunden",KONTAKT),
            ("fab fa-whatsapp","WhatsApp","Schnell und unkompliziert – gern auch per Sprachnachricht",WA)]],"row",g=24,**ROW,flex_justify_content="space-between")],bg=B1),
      footer()]

# ---------------- Leistungsseiten ----------------
def service_v2(c):
    note=T("Unverbindlich · Antwort innerhalb von 24 Stunden",MUTED,"center",14)
    out=[nav(),hero(c["eye"],c["h1"],c["sub"],[BTN(*WISH),BTN("Per WhatsApp anfragen",WA,False)],stats=None,minh=90,extra=[note]),
         section(head("Eindrücke",c.get("gal_title","So feiert ihr mit mir"))+[VIDEOS(c["key"])],bg=B2)]
    alt=[B1]
    def bg():
        v=alt[0]; alt[0]=B2 if v==B1 else B1; return v
    out.append(staerken(c["vorteile_title"],c["vorteile"],eye="Was ihr davon habt",bg=bg()))
    if c.get("akte"): out.append(section(head(c["akte_eye"],c["akte_title"])+[steps(c["akte"])],bg=bg()))
    if c.get("anlaesse"):
        sb=bg(); out.append(section(head("Anlässe",c["anl_title"])+[cards(c["anlaesse"],bg=B2 if sb==B1 else B1)],bg=sb))
    out.append(split(*c["split1"],img=f"event_{c['key']}_text.jpg",bg=bg()))
    out.append(reviews(c["reviews"],bg=bg()))
    out.append(section(head("Ablauf",c["steps_title"])+[steps(c["steps"]),SPACER(10),BTN("Jetzt Wunschtermin prüfen",KONTAKT)],anchor="ablauf",bg=bg()))
    if c.get("fit"): out.append(section(head("Passen wir zusammen?",c.get("fit_title","Finden wir das heraus!"))+[fit(*c["fit"]),SPACER(10),BTN("Passt? Dann Wunschtermin prüfen",KONTAKT)],bg=bg()))
    for blk in c.get("lists",[]):
        out.append(section(head(blk[0],blk[1],blk[2])+[con([list_card(t,it,w=48 if len(blk[3])==2 else 31) for t,it in blk[3]],"row",g=24,**ROW,flex_justify_content="center")],bg=bg()))
    if c.get("compare"):
        sb=bg(); out.append(section(head(*c["compare"][0])+[cards(c["compare"][1],bg=B2 if sb==B1 else B1)],bg=sb))
    out.append(faq(c["faq"],eye="Häufige Fragen",title=c.get("faq_title","Gut zu wissen"),bg=bg()))
    out.append(orte(c.get("orte_title","Euer DJ in Oberschwaben, Ulm und dem Allgäu"),bg=bg()))
    out.append(cta(*c.get("cta",())))
    out.append(section(head("Weitere Leistungen","Auch für andere Anlässe")+[tiles(c["cross"],w=31)],bg=B1))
    out.append(footer()); return out

def service(c):
    if c.get("v2"): return service_v2(c)
    out=[nav(),hero(c["eye"],c["h1"],c["sub"],[BTN(*WISH),BTN("So läuft's ab","#ablauf",False)],stats=None,minh=90),
         section(head("Eindrücke","So sieht ein Abend mit mir aus")+[VIDEOS(c["key"])],bg=B2)]
    if c.get("anlaesse"): out.append(section(head("Anlässe",c["anl_title"])+[cards(c["anlaesse"])]))
    out.append(split(*c["split1"],img=f"event_{c['key']}_text.jpg"))
    if c.get("fit"): out.append(section(head("Passen wir zusammen?",c.get("fit_title","Finden wir das heraus!"))+[fit(*c["fit"])],bg=B2))
    if c.get("akte"): out.append(section(head(c["akte_eye"],c["akte_title"])+[steps(c["akte"])]))
    for blk in c.get("lists",[]):
        out.append(section(head(blk[0],blk[1],blk[2])+[con([list_card(t,it,w=48 if len(blk[3])==2 else 31) for t,it in blk[3]],"row",g=24,**ROW,flex_justify_content="center")],bg=blk[4] if len(blk)>4 else B2))
    if c.get("split2"): out.append(split(*c["split2"],img_left=False))
    if c.get("compare"): out.append(section(head(*c["compare"][0])+[cards(c["compare"][1],bg=B1)],bg=B2))
    out.append(section(head("Ablauf",c["steps_title"])+[steps(c["steps"]),SPACER(10),BTN("Jetzt Wunschtermin prüfen",KONTAKT)],anchor="ablauf"))
    out.append(staerken())
    out.append(reviews(c["reviews"]))
    out.append(faq(c["faq"],eye="Häufige Fragen",title=c.get("faq_title","Gut zu wissen")))
    out.append(orte(c.get("orte_title","Euer DJ in Oberschwaben, Ulm und dem Allgäu")))
    out.append(QUIET())
    out.append(cta(*c.get("cta",())))
    out.append(section(head("Weitere Leistungen","Auch für andere Anlässe")+[tiles(c["cross"],w=31)],bg=B1))
    out.append(footer()); return out

HOCHZEIT=dict(key="hochzeit",v2=True,eye="Hochzeits-DJ · Memmingen · Allgäu · Schwaben",
 h1="Euer Hochzeits-DJ in Memmingen, Allgäu &amp; Schwaben",
 sub="Ihr genießt euren Tag – ich sorge vom Sektempfang bis zum letzten Song für die richtige Musik, eine Moderation mit rotem Faden und eine Tanzfläche, die voll bleibt.",
 vorteile_title="Ihr heiratet. Ich kümmere mich um den Rest.",
 vorteile=[("fas fa-hands-helping","Ihr könnt loslassen","Ich stimme mich mit Trauzeugen, Location, Fotograf und Catering ab."),
   ("fas fa-music","Eure Songs im richtigen Moment","Eröffnungstanz, Wunsch- und No-Go-Liste, eure Lieblingssongs – im Vorgespräch geplant, live auf die Stimmung abgestimmt."),
   ("fas fa-users","Alle Generationen auf der Tanzfläche","Von der Oma bis zum Trauzeugen: Klassiker, aktuelle Hits und eure Favoriten in der richtigen Reihenfolge."),
   ("fas fa-shield-alt","Ausfallsicher","Sollte ich krank werden, übernimmt ein erfahrener DJ aus meinem Netzwerk – gleich vorbereitet, ohne Mehrkosten für euch.")],
 split1=("Musik für alle Generationen","Oma und Trauzeuge auf derselben Tanzfläche",[
   "Auf eurer Hochzeit feiern Gäste zwischen 8 und 88. Ich hole alle ab: mit Klassikern, aktuellen Hits und euren ganz persönlichen Lieblingssongs – im richtigen Moment und in der richtigen Reihenfolge.",
   "Statt einer starren Playlist lese ich die Tanzfläche und reagiere live. So entsteht ein Abend, der sich anfühlt, als wäre er nur für euch gemacht – weil er es ist."],None,True,[BTN(*WISH,True,"left")]),
 fit=(["Ihr wollt eine volle Tanzfläche statt Hintergrundmusik","Euch ist eine persönliche Planung im Vorfeld wichtig","Ihr wünscht euch eine Moderation, die führt, aber nie aufdringlich ist","Ihr legt Wert auf guten Klang und stimmiges Licht"],
      ["Ihr sucht den günstigsten DJ der Region","Der DJ soll ausschließlich eine fertige Playlist abspielen","Euch reicht Musik vom Handy über eine Box"]),
 akte_eye="Euer Tag", akte_title="Euer Tag in vier Akten",
 akte=[("Sektempfang","Entspannte Musik, während eure Gäste ankommen und anstoßen – die Stimmung steht vom ersten Moment an."),
       ("Dinner &amp; Reden","Dezente Musik zum Essen, Funkmikrofon für Reden – moderiert und im Zeitplan, damit nichts hakt."),
       ("Eröffnungstanz","Euer Moment: perfekt angekündigt, perfekt eingeleitet – ein Moment für Gänsehaut."),
       ("Party","Vom ersten Tanzschritt bis zum letzten Song bleibt die Tanzfläche voll – und eure Gäste reden noch lange davon.")],
 lists=[("Technik &amp; Moderation","Alles aus einer Hand – ihr müsst nichts organisieren",None,[
   ("Technik",["Geprüfte Ton- und Lichtanlage, abgestimmt auf eure Location","Dezenter, sauberer Aufbau vor dem Eintreffen der Gäste","Funkmikrofon für Reden, Spiele und Beiträge","Ambientebeleuchtung auf Anfrage"]),
   ("Moderation",["Begrüßung und Einlauf des Brautpaares","Ankündigung von Reden, Spielen und Programmpunkten","Abstimmung mit Trauzeugen, Fotograf und Location","Souverän, herzlich und nie aufdringlich"])])],
 compare=(("Vergleich","DJ, Band oder Playlist? Der ehrliche Vergleich"),[
   ("DJ","Riesige Musikauswahl, reagiert live auf eure Gäste, übernimmt Moderation und Technik. Die flexibelste Lösung für volle Tanzflächen."),
   ("Live-Band","Tolles Live-Erlebnis, aber begrenztes Repertoire, Pausen zwischen den Sets und meist deutlich mehr Platzbedarf."),
   ("Playlist","Günstig, aber ohne Gespür für den Moment: keine Moderation, keine Reaktion auf die Stimmung, niemand für die Technik zuständig.")]),
 steps_title="In fünf Schritten zu eurer Hochzeitsparty",
 steps=[("Wunschtermin prüfen","Datum, Location und Gästezahl – in 2 Minuten per Formular oder WhatsApp. Antwort innerhalb von 24 Stunden."),
        ("Kennenlernen","Kostenloses, unverbindliches Gespräch per Telefon oder Video."),
        ("Termin sichern","Ihr bekommt ein individuelles Angebot – mit eurer Bestätigung ist euer Datum fest reserviert."),
        ("Feinplanung","Einige Wochen vorher planen wir Musik, Ablauf und Programmpunkte im Detail."),
        ("Eure Hochzeit","Ich bin pünktlich da, alles steht – ihr feiert.")],
 reviews=["h1","h2","h3"],
 faq=fq("Was kostet ein DJ bei DJ KOLJA ONE?","Wie früh sollten wir buchen?","Können wir Musikwünsche angeben?","Moderierst du auch?","Warum ein DJ statt einer Playlist?","Stimmst du dich mit Location und Dienstleistern ab?","Was passiert, wenn du krank wirst?"),
 faq_title="Fragen rund um eure Hochzeit",
 orte_title="Hochzeits-DJ in Oberschwaben, Ulm und dem Allgäu",
 cta=("Euer Hochzeitstermin ist noch frei? Sichert ihn euch.","Beliebte Samstage zwischen Mai und September sind oft ein Jahr im Voraus vergeben. Eine Anfrage dauert zwei Minuten und ist unverbindlich."),
 cross=["/geburtstags-dj/","/firmenfeier-dj/","/event-dj/"])

GEBURTSTAG=dict(key="geburtstag",v2=True,eye="Geburtstags-DJ · Memmingen · Allgäu · Schwaben",
 h1="Euer Geburtstags-DJ in Memmingen, Allgäu &amp; Schwaben",
 sub="Ob 18., 30., 50. oder 80.: Ihr feiert mit euren Gästen – ich sorge für die passende Musik, eine volle Tanzfläche bis zum Schluss und dafür, dass ihr euch um nichts kümmern müsst.",
 vorteile_title="Ihr feiert. Ich kümmere mich um den Rest.",
 vorteile=[("fas fa-glass-cheers","Ihr seid Gast auf eurer eigenen Party","Technik, Musik und Ablauf liegen bei mir – ihr könnt mit euren Gästen feiern, statt nebenbei DJ zu spielen."),
   ("fas fa-users","Musik, die alle verbindet","Familie, Freunde, Kollegen: Ich finde die Songs, die alle Generationen auf die Tanzfläche holen."),
   ("fas fa-music","Eure Songs, eure Erinnerungen","Lieblingslieder und Erinnerungsstücke bekommen ihren großen Moment – was ihr nicht hören wollt, kommt auf die No-Go-Liste."),
   ("fas fa-shield-alt","Ausfallsicher","Sollte ich krank werden, übernimmt ein erfahrener DJ aus meinem Netzwerk – gleich vorbereitet, ohne Mehrkosten für euch.")],
 anl_title="Für welche Feste?",
 anlaesse=[("Runde Geburtstage","18., 30., 40., 50., 60. und mehr – jeder Meilenstein verdient seinen eigenen Soundtrack."),
           ("Überraschungspartys","Diskret geplant, perfekt getimt – ich stimme mich mit den Organisatoren ab, das Geburtstagskind ahnt nichts."),
           ("Jubiläen &amp; Familienfeiern","Silberhochzeit, Goldene Hochzeit oder großes Familientreffen."),
           ("Garten- &amp; Scheunenpartys","Auch draußen und in besonderen Locations – mit passender Technik."),
           ("Abifeiern &amp; Jugendpartys","Aktuelle Hits, kurze Übergänge und ein Gespür für junges Publikum."),
           ("Feste im Vereinsheim","Unkompliziert, zuverlässig und mit Musik für alle Generationen.")],
 split1=("Gemischtes Publikum","Von 18 bis 80 – alle auf der Tanzfläche",[
   "Auf einem Geburtstag treffen Familie, Freunde und Kollegen aufeinander. Ich finde die Songs, die alle verbinden, und baue den Abend so auf, dass die Stimmung Schritt für Schritt steigt.",
   "Statt einer starren Playlist lese ich die Tanzfläche und reagiere live – so fühlt sich jeder Gast abgeholt."],None,True,[BTN(*WISH,True,"left")]),
 fit=(["Ihr wollt richtig feiern, nicht nur Hintergrundmusik","Die Musik soll zu euren Gästen passen, nicht zu einer Schablone","Ihr wünscht euch Moderation für Reden, Spiele oder Überraschungen","Ihr möchtet euch am Abend um nichts kümmern müssen"],
      ["Ihr sucht die günstigste Lösung","Musik soll nur leise im Hintergrund laufen","Ihr wollt nur eure eigene Playlist abspielen lassen"]),
 akte_eye="Euer Abend", akte_title="So wird aus einem Geburtstag ein Fest",
 akte=[("Ankommen","Lockere Musik zum Empfang – eure Gäste kommen entspannt ins Gespräch."),
       ("Essen &amp; Reden","Dezente Begleitung und ein Funkmikrofon für Reden und Glückwünsche."),
       ("Überraschungen","Spiele, Videos oder Ständchen – moderiert und mit den Organisatoren abgestimmt."),
       ("Party","Jetzt wird getanzt – mit den Songs, über die eure Gäste noch lange sprechen.")],
 steps_title="In vier Schritten zu eurer Party",
 steps=[("Wunschtermin prüfen","Datum, Ort und Anlass – in 2 Minuten per Formular oder WhatsApp. Antwort innerhalb von 24 Stunden."),
        ("Kennenlernen","Kostenloses, unverbindliches Gespräch über Musik, Ablauf und Überraschungen."),
        ("Termin sichern","Ihr bekommt ein individuelles Angebot – mit eurer Bestätigung ist euer Datum fest reserviert."),
        ("Eure Party","Ich baue auf, ihr feiert.")],
 reviews=["g1","g2","h3"],
 faq=fq("Was kostet ein DJ bei DJ KOLJA ONE?","Können wir Musikwünsche angeben?","Nimmst du auch Wünsche von Gästen an?","Moderierst du auch?","Was brauchst du vor Ort?"),
 faq_title="Fragen rund um euren Geburtstag",
 orte_title="Geburtstags-DJ in Oberschwaben, Ulm und dem Allgäu",
 cta=("Euer Geburtstag steht fest? Sichert euch euren DJ.","Gerade Wochenenden sind schnell vergeben. Eine Anfrage dauert zwei Minuten und ist unverbindlich."),
 cross=["/hochzeits-dj/","/firmenfeier-dj/","/event-dj/"])

FIRMA=dict(key="firmenfeier",v2=True,eye="Firmenfeier-DJ · Memmingen · Allgäu · Schwaben",
 h1="Euer Firmenfeier-DJ in Memmingen, Allgäu &amp; Schwaben",
 sub="Weihnachtsfeier, Sommerfest, Gala oder Jubiläum: Ihr bekommt Musik und Moderation, die zu eurem Unternehmen passt – und einen Abend, über den euer Team noch lange spricht. Ohne Aufwand für euch.",
 gal_title="So feiert euer Team mit mir",
 vorteile_title="Ihr seid Gastgeber. Ich kümmere mich um den Rest.",
 vorteile=[("fas fa-handshake","Ein Ansprechpartner, null Abstimmungsaufwand","Ich kläre Technik, Zeitplan und Aufbau direkt mit Location, Agentur und Catering."),
   ("fas fa-users","Stimmung für das ganze Team","Vom Azubi bis zur Geschäftsführung: Musik, die alle abholt – dezent beim Dinner, mitreißend auf der Tanzfläche."),
   ("fas fa-clipboard-check","Professionell bis ins Detail","Pünktlicher Aufbau, Funkmikrofone für Reden und Ehrungen, Einspieler nach Ablaufplan – alles läuft, wie geplant."),
   ("fas fa-shield-alt","Ausfallsicher","Sollte ich krank werden, übernimmt ein erfahrener DJ aus meinem Netzwerk – gleich vorbereitet, ohne Mehrkosten für euch.")],
 akte_eye="Euer Abend", akte_title="Vom Empfang bis zur Aftershow",
 akte=[("Empfang &amp; Networking","Dezente Musik, während eure Gäste ankommen und ins Gespräch kommen."),
       ("Dinner &amp; Programm","Begleitung im Hintergrund, Reden, Ehrungen und Einspieler – moderiert und im Zeitplan."),
       ("Höhepunkt","Preisverleihung, Showact oder Überraschung – perfekt angekündigt und inszeniert."),
       ("Party","Jetzt wird gefeiert – die Tanzfläche bleibt voll, bis das Licht angeht.")],
 anl_title="Für welche Veranstaltungen?",
 anlaesse=[("Weihnachtsfeiern","Vom festlichen Dinner bis zur ausgelassenen Party – der Abend, auf den sich euer Team das ganze Jahr freut."),
           ("Sommerfeste","Entspannte Atmosphäre am Nachmittag, Stimmung am Abend – auch Open Air, mit passender Technik."),
           ("Galas &amp; Preisverleihungen","Stilvolle Begleitung, präzise Einspieler und souveräne Ansagen – damit eure Gewinner ihren großen Moment bekommen."),
           ("Firmenjubiläen","Ihr feiert eure Geschichte – mit Musik, die alle Generationen im Unternehmen verbindet."),
           ("Mitarbeiter- &amp; Kundenevents","Musik, die eure Gäste abholt und euer Unternehmen positiv in Erinnerung hält."),
           ("Messen &amp; Präsentationen","Dezente Hintergrundmusik, Jingles und Moderation – für einen Auftritt, der auffällt.")],
 split1=("Musikalisches Konzept","Musik, die zu eurem Unternehmen passt",[
   "Eine Firmenfeier hat ihre eigene Dramaturgie: ankommen, netzwerken, zuhören, feiern. Ich begleite jede Phase mit der passenden Musik – dezent, wenn Gespräche im Vordergrund stehen, mitreißend, wenn es auf die Tanzfläche geht.",
   "Euren Zeitplan, eure Programmpunkte und euer Markenbild behalte ich dabei im Blick. Das Ergebnis: ein Abend, der euer Team zusammenbringt und euer Unternehmen positiv in Erinnerung hält."],None,True,[BTN(*WISH,True,"left")]),
 lists=[("Zusammenarbeit","Professionell von der Anfrage bis zum letzten Song",None,[
   ("Organisation",["Ein fester Ansprechpartner von Anfang bis Ende","Abstimmung mit Location, Agentur und Catering","Pünktlicher Aufbau vor dem Eintreffen der Gäste","Angebot und Rechnung für eure Buchhaltung"]),
   ("Technik &amp; Moderation",["Geprüfte Ton- und Lichtanlage, skalierbar bis zu großen Sälen","Funkmikrofone für Reden und Ehrungen","Einspieler, Jingles und Ansagen nach Ablaufplan","Professionelle Moderation durch den Abend"])])],
 steps_title="In fünf Schritten zu eurem Event",
 steps=[("Wunschtermin prüfen","Datum, Location und Gästezahl – in 2 Minuten per Formular, Telefon oder WhatsApp. Antwort innerhalb von 24 Stunden."),
        ("Briefing","Kostenloses Gespräch über Anlass, Zielgruppe, Ablauf und Markenwelt."),
        ("Angebot","Individuelles Angebot inklusive Technikplanung – mit eurer Bestätigung ist der Termin fest reserviert."),
        ("Feinabstimmung","Ablaufplan, Einspieler und Absprachen mit Location und Agentur."),
        ("Euer Event","Pünktlicher Aufbau, souveräne Durchführung, sauberer Abbau.")],
 reviews=["f1","f2","g2"],
 faq=[("Spielst du auch dezente Hintergrundmusik?","Ja. Beim Empfang, beim Dinner oder während des Networkings läuft die Musik bewusst im Hintergrund – die Lautstärke passe ich laufend an."),
      ("Kannst du Programmpunkte moderieren?","Ja. Von der Begrüßung über Ehrungen und Reden bis zu Gewinnspielen moderiere ich professionell und nach eurem Ablaufplan."),
      ("Stimmst du dich mit unserer Agentur oder Location ab?","Selbstverständlich. Ich kläre Technik, Zeitplan und Aufbau direkt mit allen Beteiligten, damit ihr euch auf eure Gäste konzentrieren könnt."),
      ("Wie viel Vorlauf brauchst du?","Für Weihnachtsfeiern im Dezember empfehle ich eine Anfrage bis zum Frühsommer. Für andere Termine reichen oft 2–4 Monate."),
      ("Ist deine Technik auch für große Räume geeignet?","Ja. Ton- und Lichtanlage werden auf Raumgröße und Gästezahl abgestimmt – vom Besprechungsraum bis zum großen Festsaal.")],
 faq_title="Fragen rund um eure Firmenfeier",
 orte_title="Firmenfeier-DJ in Oberschwaben, Ulm und dem Allgäu",
 cta=("Eure Weihnachtsfeier steht an? Sichert euch jetzt den Termin.","Dezember-Termine sind oft schon im Frühsommer vergeben. Eine Anfrage dauert zwei Minuten und ist unverbindlich."),
 cross=["/hochzeits-dj/","/geburtstags-dj/","/event-dj/"])

EVENT=dict(key="events",v2=True,eye="Event-DJ · Memmingen · Allgäu · Schwaben",
 h1="Euer Event-DJ in Memmingen, Allgäu &amp; Schwaben",
 sub="Stadtfest, Vereinsfeier, Open Air oder Silvester: Ihr bekommt Stimmung, die auch große Menschenmengen mitreißt – und einen DJ, der sich an euren Zeitplan hält und mit eurer Technik zusammenarbeitet.",
 gal_title="So feiert euer Publikum mit mir",
 vorteile_title="Ihr organisiert das Fest. Ich sorge für die Stimmung.",
 vorteile=[("fas fa-clipboard-check","Verlässlich für Veranstalter","Zeitpläne, Lautstärkevorgaben und Absprachen mit Bühnentechnik halte ich ein – ihr könnt euch auf alles andere konzentrieren."),
   ("fas fa-users","Stimmung über Stunden","Musik, die ein gemischtes Publikum verbindet – vom Frühschoppen bis in die Nacht."),
   ("fas fa-microphone","Moderation inklusive","Ansagen, Programmpunkte, Verlosungen und Sponsorenhinweise – souverän und nach eurem Ablaufplan."),
   ("fas fa-shield-alt","Ausfallsicher","Sollte ich krank werden, übernimmt ein erfahrener DJ aus meinem Netzwerk – gleich vorbereitet, ohne Mehrkosten für euch.")],
 akte_eye="Euer Event", akte_title="Vom Aufbau bis zum letzten Song",
 akte=[("Aufbau &amp; Soundcheck","Pünktlich vor Einlass, abgestimmt mit eurer Bühnentechnik – bevor der erste Gast kommt, steht alles."),
       ("Ankommen","Lockere Musik, während sich der Platz füllt – die Stimmung baut sich auf."),
       ("Programm","Auftritte, Verlosungen, Ehrungen – angekündigt, begleitet und im Zeitplan."),
       ("Party","Jetzt geht's los – die Tanzfläche bleibt voll, bis ihr Schluss macht.")],
 anl_title="Für welche Events?",
 anlaesse=[("Stadt- &amp; Dorffeste","Musik und Moderation für Hunderte Gäste – vom Frühschoppen bis in die Nacht."),
           ("Vereinsfeiern &amp; Jubiläen","Vom Sportverein bis zur Feuerwehr: Feste mit Tradition, bei denen alle Mitglieder mitfeiern."),
           ("Open Airs &amp; Festzelte","Technik und Erfahrung für draußen und große Zelte – wetterfest geplant."),
           ("Silvesterpartys","Der perfekte Countdown und eine Tanzfläche, die euch ins neue Jahr trägt."),
           ("Fasching &amp; Mottopartys","Vom Kinderfasching bis zum Ball – Musik, die genau zum Motto passt."),
           ("Abibälle &amp; Schulfeiern","Feierlicher Rahmen, ausgelassene Party – mit Gespür für junges Publikum.")],
 split1=("Stimmung für viele","Wenn aus Publikum eine Party wird",[
   "Bei öffentlichen Events kommen Menschen mit ganz unterschiedlichen Erwartungen zusammen. Ich spiele Musik, die verbindet, halte den Spannungsbogen über Stunden und reagiere flexibel auf Wetter, Programm und Publikum.",
   "Auf Wunsch übernehme ich auch Ansagen, Programmpunkte und die Begleitung von Auftritten – ihr habt einen Ansprechpartner für Musik und Moderation."],None,True,[BTN(*WISH,True,"left")]),
 lists=[("Organisation","Verlässlich für Veranstalter – alles abgestimmt",None,[
   ("Abstimmung",["Enge Absprache mit Veranstalter und Bühnentechnik","Einhaltung von Zeitplänen und Lautstärkevorgaben","Hinweis auf GEMA-Anmeldung durch den Veranstalter","Flexible Anpassung an Wetter und Programm"]),
   ("Technik &amp; Moderation",["Geprüfte, skalierbare Ton- und Lichttechnik","Anbindung an vorhandene Bühnentechnik möglich","Moderation von Programmpunkten und Ansagen","Durchsagen für Organisation und Sponsoren"])])],
 steps_title="In vier Schritten zu eurem Event",
 steps=[("Wunschtermin prüfen","Datum, Ort, Besucherzahl und Programm – in 2 Minuten per Formular, Telefon oder WhatsApp. Antwort innerhalb von 24 Stunden."),
        ("Planung","Kostenloses Gespräch über Technik, Zeitplan und Moderation."),
        ("Termin sichern","Individuelles Angebot – mit eurer Bestätigung ist der Termin fest reserviert."),
        ("Euer Event","Ich sorge für Stimmung – ihr für den Rest.")],
 reviews=["f2","g1","f1"],
 faq=[("Muss ich GEMA-Gebühren zahlen?",dict(fq("Muss ich GEMA-Gebühren zahlen?"))["Muss ich GEMA-Gebühren zahlen?"]),
      ("Kannst du vorhandene Bühnentechnik nutzen?","Ja. Ich kann mich in eine bestehende Beschallungsanlage einklinken oder eigene Technik mitbringen – das kläre ich vorab mit eurer Technik."),
      ("Moderierst du auch Programmpunkte?","Ja. Ansagen, Auftritte, Verlosungen und Sponsorenhinweise moderiere ich professionell und nach eurem Ablaufplan."),
      ("Spielst du auch Open Air?","Ja – mit wetterfester Planung. Stromversorgung und Überdachung für den DJ-Platz stimme ich vorab mit euch ab.")],
 faq_title="Fragen rund um euer Event",
 orte_title="Event-DJ in Oberschwaben, Ulm und dem Allgäu",
 cta=("Euer Event steht im Kalender? Sichert euch euren DJ.","Sommerwochenenden und Silvester sind früh vergeben. Eine Anfrage dauert zwei Minuten und ist unverbindlich."),
 cross=["/hochzeits-dj/","/geburtstags-dj/","/firmenfeier-dj/"])

# ---------------- Über mich ----------------
def ueber():
    quote=section([H("„Jedes Event findet nur einmal statt.“","p",48,OFF,m=30,lh=1.25,_element_width="initial",_element_custom_width=px(900)),DIV(),
        T("Dieser Satz ist mein Versprechen an euch. Es gibt keine Generalprobe und keine Wiederholung – nur diesen einen Abend. Deshalb nehme ich mir Zeit für die Vorbereitung, höre genau zu und bin am Tag selbst mit voller Aufmerksamkeit bei euch.",MUTED,"center",18,_element_width="initial",_element_custom_width=px(760))],bg=B1)
    momente=section(head("Erinnerungen","Jedes Event ist einzigartig")+[
        T("Es gibt unzählige Feiern, die mir in Erinnerung geblieben sind – von einer Hochzeit auf einem Boot vor der Skyline Frankfurts über Firmenpartys eines großen Tech-Konzerns in Dortmund, Frankfurt und Ulm bis zur Geburtstagsfeier im Bauernhofmuseum Illerbeuren und den Fußballtagen in Fellheim. Jedes Event hatte seinen eigenen Verlauf und seine eigene Atmosphäre – mit Menschen, die ausgelassen gefeiert und getanzt haben.",MUTED,"center",18,_element_width="initial",_element_custom_width=px(820))],bg=B2)
    werte=section(head("Was ihr von mir bekommt","Darauf könnt ihr euch verlassen")+[con([con([ICONBOX(*i)],bg=B2,pad=box(36,30,36,30),**col(48,48,100)) for i in [
        ("fas fa-headphones","Gespür für die Tanzfläche","Ich spiele nicht nach Plan, sondern nach Stimmung – ihr bekommt eine Tanzfläche, die voll bleibt."),
        ("fas fa-microphone","Moderation mit rotem Faden","Ich führe souverän durch den Abend – herzlich, klar und nie aufdringlich."),
        ("fas fa-sliders-h","Sound, der passt","Geprüfte Technik, abgestimmt auf eure Location – laut genug zum Tanzen, angenehm genug zum Reden."),
        ("fas fa-handshake","Verlässlichkeit","Pünktlich, vorbereitet, erreichbar – vom ersten Gespräch bis zum letzten Song. Und falls ich ausfalle, springt ein erfahrener DJ aus meinem Netzwerk ein.")]],
        "row",g=24,**ROW,flex_justify_content="space-between")],bg=B1)
    musik=section(head("Musik","Mein Repertoire – und eure Wünsche","Entscheidend ist euer Publikum. Diese Richtungen habe ich im Gepäck – eure Wunschliste kommt dazu.")+[cards([
        ("Pop &amp; Charts","Aktuelle Hits und die großen Songs der letzten Jahrzehnte."),("80er, 90er &amp; 2000er","Die Klassiker, bei denen jede Generation mitsingt."),
        ("Schlager &amp; Party","Wenn es zur richtigen Zeit passt – mit Augenmaß."),("Rock &amp; Indie","Gitarren für die, die es etwas rauer mögen."),
        ("House &amp; Dance","Für späte Stunden und volle Tanzflächen."),("Latin, Soul &amp; Lounge","Für Empfang, Dinner und besondere Momente.")],bg=B1)],bg=B2)
    galerie=section(head("Einblicke","Hinter dem DJ-Pult")+[VIDEOS("ueber")],bg=B1)
    note=T("Unverbindlich · Antwort innerhalb von 24 Stunden",MUTED,"center",14)
    return [nav(),hero("Über mich","Hallo, ich bin Kolja.","DJ und Moderator aus Fellheim. Seit über 10 Jahren sorge ich dafür, dass eure Feier nicht nur nett wird, sondern unvergesslich.",
            [BTN(*WISH),BTN("Per WhatsApp anfragen",WA,False)],stats=None,minh=80,extra=[note]),
        split("Meine Geschichte","Der Mensch hinter DJ KOLJA ONE",[
            "Meine Leidenschaft für Musik begann 1996 beim Radio. Seitdem faszinieren mich Musikproduktion, Tontechnik – und vor allem die Energie, die Musik auf Menschen überträgt. Als die ersten DJ-Controller auf den Markt kamen, habe ich unzählige Stunden damit verbracht, Musik zu sichten, zu mixen und mit Songs eine Atmosphäre zu schaffen, die eine Geschichte erzählt.",
            "Seit über 10 Jahren stehe ich heute als DJ und Moderator auf Hochzeiten, Geburtstagen, Firmenfeiern und Stadtfesten zwischen Memmingen, Allgäu und Schwaben.",
            "Was mich antreibt, ist der Moment, in dem aus einer schönen Feier ein unvergesslicher Abend wird: wenn die Tanzfläche voll ist, das Brautpaar strahlt oder das ganze Team mitsingt. Genau diese Momente plane ich mit euch – und sorge am Abend dafür, dass sie passieren.",
            "Abseits des DJ-Pults bin ich gern draußen unterwegs: Ich entdecke die Natur, liebe Survival und Bushcrafting und segle mit viel Leidenschaft."],
            buttons=[BTN(*WISH,True,"left")],img="kolja_portrait.jpg",port=True,bg=B2),
        quote, momente, werte, reviews(["h2","f2","g1"],bg=B2), musik, galerie,
        cta("Lasst uns kennenlernen.","Im kostenlosen, unverbindlichen Gespräch finden wir heraus, ob wir zusammenpassen. Eine Anfrage dauert zwei Minuten."), footer()]

# ---------------- Meine Musik ----------------
MUSIK_SNIPPET="./musik/musik-snippet-final.html"
def _tight(c,pad,mob):
    c["settings"]["padding"]=pad; c["settings"]["padding_mobile"]=mob; return c
def musik():
    return [nav(),
      _tight(hero("Meine Musik","Legt selbst auf","Sucht euch eine Platte aus der Kiste, legt sie aufs Deck und hört, wie DJ KOLJA ONE klingt – so ähnlich klingt auch eure Feier.",
           [],stats=None,minh=55),box(72,20,0,20),box(40,20,0,20)),
      _tight(section([W("html",{"html":open(MUSIK_SNIPPET).read(),"_element_width":"inherit","width":px(100,"%")})],anchor="auflegen"),box(0,20,64,20),box(0,12,40,12)),
      section([H("Gefällt euch der Sound?","h2",36,OFF,m=34),T("Auf eurer Feier mixe ich live – abgestimmt auf eure Gäste, eure Wünsche und den Moment.",MUTED),SPACER(4),
          BTNS(BTN(*WISH),BTN("Per WhatsApp anfragen",WA,False))],bg=B2),
      section(head("So geht's","Euer eigener Mix in vier Schritten")+[steps([
          ("Platte wählen","Stöbert in der Plattenkiste oder öffnet BROWSE: Die Titelliste lässt sich nach BPM, Tonart (Camelot) und Genre sortieren. Mit A oder B ladet ihr einen Song gezielt auf Deck A oder B – gestartet wird er erst mit Play."),
          ("Selbst mixen","In den Wellenformen oben im Mixer seht ihr beide Songs samt Beatgrid und springt per Klick an jede Stelle. Blendet mit dem Crossfader über, gleicht das Tempo mit dem Regler und SYNC an und haltet mit KEY die Tonart. Grün markierte Platten passen harmonisch zum laufenden Song."),
          ("Automix","Lieber zurücklehnen? Mit ☰+ legt ihr Songs in die Playlist, „Automix starten“ mixt sie nacheinander. Sobald ihr eingreift, übernehmt ihr wieder selbst."),
          ("Ansehen &amp; bewerten","Songs mit dem Label VIDEO zeigen ihr Bild im Laptop. Gebt jedem Song 1 bis 5 Sterne – der Durchschnitt aller Besucher steht direkt am Song.")])],bg=B1),
      section(head("Musik","Mein Repertoire – und eure Wünsche","Entscheidend ist euer Publikum. Diese Richtungen habe ich im Gepäck – eure Wunschliste kommt dazu.")+[cards([
        ("Pop &amp; Charts","Aktuelle Hits und die großen Songs der letzten Jahrzehnte."),("80er, 90er &amp; 2000er","Die Klassiker, bei denen jede Generation mitsingt."),
        ("Schlager &amp; Party","Wenn es zur richtigen Zeit passt – mit Augenmaß."),("Rock &amp; Indie","Gitarren für die, die es etwas rauer mögen."),
        ("House &amp; Dance","Für späte Stunden und volle Tanzflächen."),("Latin, Soul &amp; Lounge","Für Empfang, Dinner und besondere Momente.")],bg=B1)],bg=B2),
      cta("Spielst du auch unsere Lieblingssongs?","Ja – im kostenlosen Vorgespräch planen wir eure Musik mit Wunsch- und No-Go-Liste. Eine Anfrage dauert zwei Minuten und ist unverbindlich."),
      footer()]

# ---------------- FAQ ----------------
def faqpage():
    secs=[faq(v,title=k,more=False,bg=(B1 if i%2==0 else B2),eye="FAQ") for i,(k,v) in enumerate(FAQ_ALL.items())]
    return [nav(),hero("FAQ &amp; Wissenswertes","Häufige Fragen rund um euren DJ","Preise, Buchung, Musik, Technik und Ablauf – hier findet ihr die Antworten. Eure Frage fehlt? Ruft an oder schreibt mir – ich antworte innerhalb von 24 Stunden.",
        [BTN(*WISH),BTN("Per WhatsApp anfragen",WA,False)],stats=None,minh=60)]+secs+[cta("Eure Frage war nicht dabei?","Ich beantworte sie gern persönlich – per Telefon, WhatsApp oder Formular. Und wenn ihr schon wisst, wann gefeiert wird: Prüft gleich euren Wunschtermin."),footer()]

# ---------------- Einsatzgebiete ----------------
GROUPS=[("Memmingen &amp; Unterallgäu",["Memmingen"],"Mein Heimatgebiet rund um Fellheim – in wenigen Minuten bei euch."),
        ("Ulm &amp; Donau",["Ulm"],"Ulm, Neu-Ulm und das Umland entlang der Donau – über die A7 schnell erreicht."),
        ("Oberschwaben &amp; Bodensee",["Biberach","Ravensburg"],"Vom Riß-Tal bis ins Schussental – Gutshöfe, Scheunen und Festsäle."),
        ("Allgäu",["Kempten","Kaufbeuren","Füssen"],"Vom Oberallgäu bis ins Ostallgäu – Feiern mit Bergblick."),
        ("Lechrain",["Landsberg"],"Landsberg am Lech und Umgebung."),
        ("Überregional",[],"Für exklusive Events und besondere Locations komme ich auch deutschlandweit und international – fragt einfach an.")]
def regionen():
    slugs=dict(ORTE)
    grp=[con([H(t,"h3",24,OFF,"left","400"),T(d,MUTED,"left",15)]+[H("DJ "+o+" →","p",17,GOLD,"left","400",link=f"/{slugs[o]}/") for o in os_],
             bg=B2,pad=box(34,28,34,28),g=10,border_border="solid",border_width=box(2,0,0,0),border_color=GOLD,**col(31,48,100)) for t,os_,d in GROUPS]
    note=T("Unverbindlich · Antwort innerhalb von 24 Stunden",MUTED,"center",14)
    return [nav(),hero("Einsatzgebiete","Euer DJ in Memmingen, im Allgäu und in Schwaben","Mobiler DJ mit Heimat in Fellheim bei Memmingen – für Hochzeiten, Geburtstage, Firmenfeiern und Events in der ganzen Region. Kurze Wege, pünktlicher Aufbau, ein Ansprechpartner.",
        [BTN(*WISH),BTN("Per WhatsApp anfragen",WA,False)],stats=None,minh=80,extra=[note]),
        section(head("Regionen","Hier bin ich für euch unterwegs")+[con(grp,"row",g=24,**ROW,flex_justify_content="center")]),
        section(head("Kurz erklärt","Anfahrt? Immer transparent.")+[T("Die Anfahrt ist Teil eures individuellen Angebots – transparent und ohne Überraschungen. Euer Ort ist nicht dabei? Kein Problem: Schickt mir euren Wunschtermin und den Ort, ich sage euch innerhalb von 24 Stunden, ob es klappt.",MUTED,"center",18,_element_width="initial",_element_custom_width=px(760)),
            SPACER(6),BTN(*WISH)],bg=B2),
        section(head("Leistungen","Wofür bucht ihr mich?")+[tiles()],bg=B1),
        cta("Euer Wunschtermin ist noch frei? Sichert ihn euch.","Beliebte Samstage sind oft ein Jahr im Voraus vergeben. Eine Anfrage dauert zwei Minuten und ist unverbindlich."),footer()]

# ---------------- Städte ----------------
CITIES={
 "Memmingen":("15","Memmingen ist mein Heimspiel: Von Fellheim aus bin ich in rund einer Viertelstunde bei euch. Ob Feier in der historischen Altstadt, im Landgasthof im Unterallgäu oder in einer Firmenlocation am Stadtrand – ich kenne die Region, ihre Locations und ihre Menschen.",["Ulm","Biberach","Kempten"]),
 "Ulm":("50","Zwischen Münster und Donau wird gern gefeiert – von der Firmenveranstaltung in Ulm und Neu-Ulm bis zur Hochzeit im Umland. Über die A7 bin ich von Fellheim aus schnell und zuverlässig bei euch.",["Memmingen","Biberach","Kaufbeuren"]),
 "Biberach":("40","Biberach an der Riß und das oberschwäbische Land bieten Locations zwischen Gutshof, Scheune und Festsaal. Genau dort sorge ich für die Musik, die zu eurer Feier und euren Gästen passt.",["Memmingen","Ulm","Ravensburg"]),
 "Ravensburg":("85","Die Stadt der Türme, das Schussental und der nahe Bodensee: Rund um Ravensburg wird mit Stil gefeiert. Ich bringe Musik, Moderation und Technik mit – ihr bringt die Gäste.",["Biberach","Kempten","Memmingen"]),
 "Kempten":("50","Kempten ist das Herz des Allgäus – und die Kulisse für Hochzeiten mit Bergblick, Firmenfeiern und große Geburtstage. Über die A7 bin ich von Fellheim aus schnell bei euch.",["Memmingen","Kaufbeuren","Füssen"]),
 "Füssen":("95","Königsschlösser, Forggensee und Alpenpanorama: Wer in Füssen feiert, hat die schönste Kulisse schon gebucht. Den passenden Soundtrack liefere ich – und eine Tanzfläche, die voll bleibt.",["Kempten","Kaufbeuren","Landsberg"]),
 "Kaufbeuren":("55","Kaufbeuren und das Ostallgäu verbinden Tradition und Lebensfreude. Ob Hochzeit, Vereinsfest oder Firmenfeier – ich sorge dafür, dass eure Gäste den Abend nicht so schnell vergessen.",["Kempten","Füssen","Landsberg"]),
 "Landsberg":("75","Historische Altstadt, Lechwehr und Lechrain: Landsberg am Lech ist wie gemacht für besondere Feste. Ich bringe alles mit, was es für einen unvergesslichen Abend braucht.",["Kaufbeuren","Memmingen","Füssen"]),
}
def city(name):
    km,intro,near=CITIES[name]; slugs=dict(ORTE)
    long="Landsberg am Lech" if name=="Landsberg" else ("Biberach an der Riß" if name=="Biberach" else name)
    tips=[("Location","Jede Location hat ihre Eigenheiten. Ich kläre Aufbau, Strom und Lautstärke vorab direkt mit dem Haus – ihr müsst nichts organisieren."),
          ("Musik","Wir planen eure Musik gemeinsam – mit Wunsch- und No-Go-Liste und Raum für Spontanes."),
          ("Ton &amp; Licht","Die Technik wird auf Raumgröße und Gästezahl abgestimmt – dezent im Aufbau, stark im Klang."),
          ("Termin","Samstage in der Hochsaison sind früh vergeben – fragt rechtzeitig an.")]
    vorteile=[START_STAERKEN[0],START_STAERKEN[1],
              ("fas fa-route","Kurze Wege",f"Von Fellheim aus bin ich in rund {km} km in {long} – pünktlich und entspannt, auch für Aufbau und Soundcheck."),
              START_STAERKEN[3]]
    chips=[con([H("DJ "+o,"p",19,OFF,"center","300",link=f"/{slugs[o]}/")],pad=box(18,10,18,10),border_border="solid",
               border_width=box(1,1,1,1),border_color="rgba(178,157,117,0.35)",**col(23,31,48)) for o in near]
    note=T("Unverbindlich · Antwort innerhalb von 24 Stunden",MUTED,"center",14)
    return [nav(),
      hero(f"DJ {name} · Hochzeit · Geburtstag · Firmenfeier · Event",f"Euer DJ für {long} und Umgebung",
           f"Hochzeit, Geburtstag, Firmenfeier oder Stadtfest: Ihr feiert – ich bringe Musik, Moderation und Technik mit und bin von Fellheim aus in rund {km} km bei euch.",
           [BTN(*WISH),BTN("Per WhatsApp anfragen",WA,False)],stats=None,minh=85,extra=[note]),
      section(head("Eindrücke","So feiert ihr mit mir")+[VIDEOS(dict(ORTE)[name][3:],"region")],bg=B2),
      split(f"Feiern in {name}",f"DJ in {long}",[intro,"Ich plane jede Feier persönlich mit euch: mit eurer Musik, eurem Ablauf und einem Gespür dafür, was eure Gäste gerade brauchen."],
            buttons=[BTN(*WISH,True,"left")],img="start_1.jpg",bg=B1),
      staerken("Ihr feiert. Ich kümmere mich um den Rest.",vorteile,eye="Was ihr davon habt",bg=B2),
      section(head("Leistungen",f"Wofür bucht ihr mich in {name}?")+[tiles()],bg=B1,anchor="leistungen"),
      reviews(["h1","f1","g1"],bg=B2),
      section(head("Planung","Darauf kommt es an")+[steps(tips)],bg=B1),
      section(head("In der Nähe","Auch hier bin ich für euch da")+[con(chips,"row",g=16,flex_wrap="wrap",flex_direction_mobile="row",flex_justify_content="center"),
          SPACER(6),H("Alle Einsatzgebiete →","p",13,GOLD,"center","500",link="/einsatzgebiete/",ls=1.5,tr="uppercase")],bg=B2),
      faq([(f"Kommst du auch nach {name}?",f"Ja – {name} gehört zu meinem festen Einsatzgebiet. Von Fellheim aus sind es nur rund {km} Kilometer."),
           ("Wird die Anfahrt extra berechnet?","Die Anfahrt ist Teil eures individuellen Angebots – transparent und ohne Überraschungen."),
           (f"Spielst du in {name} auch Firmenfeiern und Events?","Ja. Neben Hochzeiten und Geburtstagen begleite ich auch Weihnachtsfeiern, Sommerfeste, Vereinsfeiern und Stadtfeste."),
           ("Wie früh sollten wir buchen?",dict(fq("Wie früh sollten wir buchen?"))["Wie früh sollten wir buchen?"])],title=f"Fragen zu DJ {name}",bg=B1),
      cta(f"Feier in {name} geplant? Sichert euch euren Termin.","Beliebte Samstage sind oft ein Jahr im Voraus vergeben. Eine Anfrage dauert zwei Minuten und ist unverbindlich."),footer()]

# ---------------- Kontakt ----------------
def kontakt():
    form=con([H("In 2 Minuten angefragt","h2",32,OFF,"left","300"),T("Datum, Ort, Anlass und Gästezahl – mehr brauche ich für den Anfang nicht. Alles Weitere besprechen wir im kostenlosen Kennenlerngespräch.",MUTED,"left",16),
              W("html",{"html":open("./formcss/wpforms-dark.html").read()}),W("shortcode",{"shortcode":"[wpforms id=5020]"}),
              T("Eure Daten nutze ich nur für eure Anfrage. Keine Werbung, kein Newsletter.",MUTED,"left",13)],bg=B2,pad=box(44,40,44,40),g=16,**col(58,100,100),
             border_border="solid",border_width=box(2,0,0,0),border_color=GOLD)
    side=con([H("Lieber direkt?","h3",26,OFF,"left","300")]+[con([ICONBOX(i,t,d,"left",u)],bg=B2,pad=box(28,26,28,26)) for i,t,d,u in [
        ("fas fa-phone","Anrufen",PHONE+" – direkt mit mir sprechen",TEL),("fab fa-whatsapp","WhatsApp","Schnell und unkompliziert – gern auch per Sprachnachricht",WA),
        ("fas fa-envelope","E-Mail",MAIL,"mailto:"+MAIL),("fab fa-instagram","Instagram","Einblicke von meinen Events, Playlisten und nützliche Infos",INSTA),("fab fa-facebook-f","Facebook","DJ KOLJA ONE",FACEBOOK)]],g=16,**col(38,100,100))
    return [nav(),hero("Anfrage","Wunschtermin prüfen","Schickt mir euer Datum – ich prüfe sofort, ob es noch frei ist, und melde mich innerhalb von 24 Stunden persönlich bei euch. Unverbindlich und kostenlos.",
            [BTN("Zum Formular","#formular"),BTN("Per WhatsApp anfragen",WA,False)],stats=None,minh=55),
        section([con([form,side],"row",g=30,**ROW,flex_justify_content="space-between",flex_align_items="flex-start")],anchor="formular"),
        section(head("So geht's weiter","Nach eurer Anfrage")+[steps([("Antwort in 24 Stunden","Ich prüfe euren Termin und melde mich persönlich."),
            ("Kostenloses Kennenlernen","Telefonisch oder per Video besprechen wir Musik, Ablauf und eure Wünsche – unverbindlich."),
            ("Termin sichern","Ihr bekommt ein individuelles Angebot. Mit eurer Bestätigung ist euer Datum fest reserviert.")])],bg=B2),
        QUIET([("10+ Jahre","Erfahrung"),("Ausfallsicher","dank DJ-Netzwerk"),("Kostenlos","Erstgespräch")]),
        footer()]

# ---------------- Rechtliches ----------------
def impressum():
    return text_page("Impressum",[
      "<h3>Angaben gemäß § 5 DDG</h3><p>Artificial Sentiments (Einzelunternehmen)<br>Inhaber: Kolja Tönges<br>Marke: DJ KOLJA ONE<br>Pfarrer-Ritter-Weg 9<br>87748 Fellheim</p>",
      f"<h3>Kontakt</h3><p>Telefon: {PHONE}<br>E-Mail: {MAIL}</p>",
      "<h3>Umsatzsteuer</h3><p>Umsatzsteuer-Identifikationsnummer gemäß § 27a UStG: [BITTE ERGÄNZEN: DE… – liegt vor, wird nachgetragen]<br>Gemäß § 19 UStG wird keine Umsatzsteuer berechnet (Kleinunternehmerregelung).</p>",
      "<h3>Verantwortlich für den Inhalt nach § 18 Abs. 2 MStV</h3><p>Kolja Tönges, Anschrift wie oben</p>",
      "<h3>Verbraucherstreitbeilegung</h3><p>Ich bin nicht bereit und nicht verpflichtet, an Streitbeilegungsverfahren vor einer Verbraucherschlichtungsstelle teilzunehmen.</p>",
      "<h3>Haftung für Inhalte</h3><p>Die Inhalte dieser Seiten wurden mit größter Sorgfalt erstellt. Für die Richtigkeit, Vollständigkeit und Aktualität der Inhalte kann ich jedoch keine Gewähr übernehmen. Als Diensteanbieter bin ich für eigene Inhalte nach den allgemeinen Gesetzen verantwortlich.</p>",
      "<h3>Haftung für Links</h3><p>Diese Website enthält Links zu externen Websites Dritter, auf deren Inhalte ich keinen Einfluss habe. Für diese fremden Inhalte ist stets der jeweilige Anbieter oder Betreiber verantwortlich. Bei Bekanntwerden von Rechtsverletzungen werden derartige Links umgehend entfernt.</p>",
      "<h3>Urheberrecht</h3><p>Die durch den Seitenbetreiber erstellten Inhalte und Werke auf diesen Seiten unterliegen dem deutschen Urheberrecht. Vervielfältigung, Bearbeitung und Verbreitung außerhalb der Grenzen des Urheberrechts bedürfen der schriftlichen Zustimmung des Erstellers.</p>"])

def datenschutz():
    return text_page("Datenschutzerklärung",[
      f"<h3>1. Verantwortlicher</h3><p>Artificial Sentiments (Einzelunternehmen), Inhaber: Kolja Tönges, Marke DJ KOLJA ONE<br>Pfarrer-Ritter-Weg 9, 87748 Fellheim<br>Telefon: {PHONE}<br>E-Mail: {MAIL}</p>",
      "<h3>2. Allgemeines</h3><p>Der Schutz deiner persönlichen Daten ist mir wichtig. Ich verarbeite personenbezogene Daten nur im Rahmen der gesetzlichen Bestimmungen, insbesondere der Datenschutz-Grundverordnung (DSGVO). Diese Erklärung informiert dich darüber, welche Daten beim Besuch dieser Website erhoben werden und wofür sie genutzt werden.</p>",
      "<h3>3. Hosting</h3><p>Diese Website wird bei der STRATO AG, Otto-Ostrowski-Straße 7, 10249 Berlin, gehostet. Beim Aufruf der Website werden durch den Hoster automatisch Informationen in sogenannten Server-Logfiles gespeichert (z. B. IP-Adresse, Datum und Uhrzeit des Zugriffs, aufgerufene Seite, Browsertyp). Die Verarbeitung erfolgt auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO; mein berechtigtes Interesse liegt in einem sicheren und stabilen Betrieb der Website. Mit STRATO besteht ein Vertrag zur Auftragsverarbeitung.</p>",
      "<h3>4. SSL-/TLS-Verschlüsselung</h3><p>Diese Seite nutzt aus Sicherheitsgründen eine SSL- bzw. TLS-Verschlüsselung. Eine verschlüsselte Verbindung erkennst du am Schloss-Symbol in der Adresszeile deines Browsers.</p>",
      "<h3>5. Kontaktaufnahme per Formular, E-Mail oder Telefon</h3><p>Wenn du mir eine Anfrage sendest, verarbeite ich die von dir angegebenen Daten (z. B. Name, E-Mail-Adresse, Telefonnummer, Veranstaltungsdatum und -ort, Nachricht) ausschließlich zur Bearbeitung deiner Anfrage und für mögliche Anschlussfragen. Rechtsgrundlage ist Art. 6 Abs. 1 lit. b DSGVO (vorvertragliche Maßnahmen) bzw. Art. 6 Abs. 1 lit. f DSGVO. Die Daten werden gelöscht, sobald sie für den Zweck nicht mehr erforderlich sind und keine gesetzlichen Aufbewahrungspflichten entgegenstehen.</p>",
      "<h3>6. WhatsApp</h3><p>Auf dieser Website befindet sich ein Link zu WhatsApp. Beim bloßen Besuch der Website werden keine Daten an WhatsApp übertragen. Erst wenn du den Link anklickst, wirst du zu WhatsApp weitergeleitet. Anbieter ist die WhatsApp Ireland Limited, 4 Grand Canal Square, Dublin 2, Irland, ein Unternehmen der Meta-Gruppe. Dabei können Daten auch in die USA übertragen werden. Bitte nutze WhatsApp nur, wenn du mit der Datenverarbeitung durch WhatsApp einverstanden bist; alternativ erreichst du mich jederzeit per Telefon, E-Mail oder Kontaktformular. Weitere Informationen findest du in der Datenschutzrichtlinie von WhatsApp.</p>",
      "<h3>7. Schriftarten</h3><p>Die auf dieser Website verwendeten Schriftarten werden lokal von meinem Server geladen. Eine Verbindung zu Servern von Google oder anderen Drittanbietern findet dabei nicht statt.</p>",
      "<h3>8. Song-Bewertungen</h3><p>Auf der Seite „Meine Musik“ kannst du Songs mit 1 bis 5 Sternen bewerten. Gespeichert wird nur die abgegebene Sternezahl, nicht dein Name. Damit jeder Song pro Besucher nur einmal bewertet werden kann, speichere ich für 30 Tage einen verschlüsselten, nicht umkehrbaren Prüfwert (Hash), der aus deiner IP-Adresse und dem Song gebildet wird; die IP-Adresse selbst wird nicht gespeichert. Zusätzlich merkt sich dein Browser im lokalen Speicher, welche Songs du bewertet hast. Rechtsgrundlage ist Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse an unverfälschten Bewertungen) bzw. § 25 Abs. 2 Nr. 2 TDDDG für die von dir angeforderte Bewertungsfunktion. Den Eintrag im Browser kannst du jederzeit über deine Browsereinstellungen löschen.</p>",
      "<h3>9. Cookies und Einwilligung</h3><p>Diese Website setzt technisch notwendige Cookies ein, etwa für angemeldete Administratoren oder um deine Song-Bewertung zu speichern. Dafür ist keine Einwilligung nötig (§ 25 Abs. 2 Nr. 2 TDDDG). Für die Besucherstatistik nutze ich Google Analytics und Microsoft Clarity, für die Erfolgsmessung meiner Anzeigen das Conversion-Tracking von Google Ads. Cookies dafür werden nur gesetzt, wenn du zustimmst. Deine Einwilligung hole ich beim ersten Besuch über ein Cookie-Banner ein, das mit dem Plugin Complianz bereitgestellt wird. Deine Entscheidung wird in Cookies in deinem Browser gespeichert. An den Anbieter von Complianz werden dabei keine Daten übertragen. Rechtsgrundlage ist Art. 6 Abs. 1 lit. c DSGVO, weil ich gesetzlich verpflichtet bin, Einwilligungen nachweisen zu können. Du kannst deine Auswahl jederzeit über die Cookie-Richtlinie ändern oder widerrufen. Dort findest du auch eine Liste aller eingesetzten Cookies.</p>",
      "<h3>10. Google Analytics</h3><p>Mit deiner Einwilligung nutzt diese Website Google Analytics 4, einen Webanalysedienst der Google Ireland Limited, Gordon House, Barrow Street, Dublin 4, Irland. Die Einbindung erfolgt über das WordPress-Plugin Site Kit by Google. Google Analytics verwendet Cookies und erfasst unter anderem, welche Seiten du aufrufst, wie lange du bleibst, deinen ungefähren Standort, Geräte- und Browserinformationen sowie die Herkunft deines Besuchs. IP-Adressen werden von Google Analytics 4 nicht dauerhaft gespeichert. Die Funktion Google Signals ist deaktiviert. Rechtsgrundlage ist deine Einwilligung nach Art. 6 Abs. 1 lit. a DSGVO und § 25 Abs. 1 TDDDG. Ohne deine Einwilligung wird Google Analytics nicht geladen. Du kannst sie jederzeit mit Wirkung für die Zukunft über die Cookie-Richtlinie widerrufen. Die Daten können an Server der Google LLC in den USA übertragen werden. Google LLC ist unter dem EU-U.S. Data Privacy Framework zertifiziert. Mit Google besteht ein Vertrag zur Auftragsverarbeitung. Die erhobenen Daten werden nach 14 Monaten automatisch gelöscht. Weitere Informationen findest du unter https://policies.google.com/privacy.</p>",
      "<h3>11. Google Ads Conversion-Tracking</h3><p>Ich schalte Anzeigen über Google Ads, einen Dienst der Google Ireland Limited, Gordon House, Barrow Street, Dublin 4, Irland. Um zu messen, wie erfolgreich meine Anzeigen sind, nutze ich das Conversion-Tracking von Google Ads. Dabei wird erfasst, ob du nach dem Klick auf eine Anzeige eine Anfrage stellst – also das Anfrageformular abschickst, auf den WhatsApp-Link oder auf die Telefonnummer klickst. Erfasst werden dabei u. a. die angeklickte Anzeige, Zeitpunkt, Geräte- und Browserinformationen sowie die Art der Anfrage; Inhalte deiner Anfrage (z. B. Name, Nachricht, Telefonnummer) werden nicht an Google übermittelt. Ich erhalte nur zusammengefasste Statistiken und kann dich daraus nicht persönlich erkennen. Die Einbindung erfolgt über das WordPress-Plugin Site Kit by Google. Cookies (z. B. _gcl_au) setzt Google Ads nur mit deiner Einwilligung nach Art. 6 Abs. 1 lit. a DSGVO und § 25 Abs. 1 TDDDG. Ich nutze den Einwilligungsmodus von Google: Ohne deine Einwilligung werden keine Cookies gesetzt und keine Kennungen gespeichert; Google können dann lediglich cookielose Signale (z. B. dass eine Anfrage stattgefunden hat) übermittelt werden, die nicht mit einem Nutzerprofil verknüpft werden. Personalisierte Werbung und Remarketing nutze ich nicht. Du kannst deine Einwilligung jederzeit mit Wirkung für die Zukunft über die Cookie-Richtlinie widerrufen. Die Daten können an Server der Google LLC in den USA übertragen werden. Google LLC ist unter dem EU-U.S. Data Privacy Framework zertifiziert. Weitere Informationen findest du unter https://policies.google.com/privacy und https://business.safety.google/privacy/.</p>",
      "<h3>12. Microsoft Clarity</h3><p>Mit deiner Einwilligung nutze ich Microsoft Clarity, einen Analysedienst der Microsoft Ireland Operations Limited, One Microsoft Place, South County Business Park, Leopardstown, Dublin 18, Irland. Die Einbindung erfolgt über das WordPress-Plugin von Microsoft Clarity. Clarity zeigt mir, wie Besucher meine Website nutzen – etwa Klicks, Scrollverhalten und Mausbewegungen, auch in Form von Sitzungsaufzeichnungen und Heatmaps –, damit ich die Seite verständlicher und nutzerfreundlicher machen kann. Dabei werden u. a. Geräte- und Browserinformationen, ungefährer Standort, aufgerufene Seiten und Interaktionen verarbeitet; Eingaben in Formularfelder werden maskiert und nicht aufgezeichnet. Clarity speichert dazu Cookies (z. B. _clck, _clsk, CLID, MUID) mit einer Laufzeit von bis zu einem Jahr. Rechtsgrundlage ist deine Einwilligung nach Art. 6 Abs. 1 lit. a DSGVO und § 25 Abs. 1 TDDDG. Du kannst sie jederzeit mit Wirkung für die Zukunft über die Cookie-Richtlinie widerrufen. Die Daten können an Server der Microsoft Corporation in den USA übertragen werden. Microsoft ist unter dem EU-U.S. Data Privacy Framework zertifiziert. Weitere Informationen findest du unter https://privacy.microsoft.com/de-de/privacystatement.</p>",
      "<h3>13. Deine Rechte</h3><p>Du hast jederzeit das Recht auf Auskunft (Art. 15 DSGVO), Berichtigung (Art. 16), Löschung (Art. 17), Einschränkung der Verarbeitung (Art. 18), Datenübertragbarkeit (Art. 20) sowie Widerspruch gegen die Verarbeitung (Art. 21 DSGVO). Wende dich dazu einfach an die oben genannten Kontaktdaten.</p>",
      "<h3>14. Beschwerderecht</h3><p>Du hast das Recht, dich bei einer Datenschutz-Aufsichtsbehörde zu beschweren. Zuständig ist das Bayerische Landesamt für Datenschutzaufsicht (BayLDA), Promenade 18, 91522 Ansbach.</p>",
      "<h3>15. Aktualität</h3><p>Stand: Oktober 2026. Ich passe diese Datenschutzerklärung an, sobald sich die Website oder die rechtlichen Vorgaben ändern.</p>"])

PAGES=[("Start","start",start),
 ("Hochzeits-DJ","hochzeits-dj",lambda: service(HOCHZEIT)),("Geburtstags-DJ","geburtstags-dj",lambda: service(GEBURTSTAG)),
 ("Firmenfeier-DJ","firmenfeier-dj",lambda: service(FIRMA)),("Event-DJ","event-dj",lambda: service(EVENT)),
 ("Meine Musik","meine-musik",musik),
 ("Über mich","ueber-mich",ueber),("FAQ","faq",faqpage),("Einsatzgebiete","einsatzgebiete",regionen)]
PAGES+=[("DJ "+o,s,(lambda o=o: city(o))) for o,s in ORTE]
PAGES+=[("Kontakt","kontakt",kontakt),("Impressum","impressum",impressum),("Datenschutz","datenschutz",datenschutz)]
