from lib import *

FAQ_ALL={
"Buchung & Preise":[
 ("Was kostet ein Event bei DJ KOLJA ONE?","Jede Veranstaltung ist anders: Dauer, Gästezahl, Technik und Anfahrt bestimmen den Preis. Deshalb bekommt ihr ein individuelles Angebot – schnell, transparent und unverbindlich."),
 ("Wie läuft eine Buchung ab?","Ihr schickt mir eine Anfrage mit Datum, Ort und Anlass. Danach spreche ich mit euch telefonisch oder per Video über eure Wünsche, ihr erhaltet ein individuelles Angebot, und nach eurer Bestätigung ist der Termin verbindlich für euch reserviert."),
 ("Wie früh sollten wir buchen?","Für Hochzeiten und Samstage in der Hochsaison (Mai bis September) empfehle ich 9–12 Monate Vorlauf. Für Geburtstage und Firmenfeiern reichen oft 3–6 Monate. Kurzfristige Anfragen lohnen sich trotzdem – fragt einfach nach."),
 ("Was passiert, wenn du krank wirst?","Dann übernimmt ein erfahrener DJ aus meinem Netzwerk – mit derselben Vorbereitung und ohne Mehrkosten für euch."),
 ("Muss ich GEMA-Gebühren zahlen?","Private Feiern mit geladenen Gästen sind in der Regel nicht GEMA-pflichtig. Bei öffentlichen Veranstaltungen ist der Veranstalter für die Anmeldung zuständig – ich weise euch im Vorgespräch darauf hin."),
],
"Musik":[
 ("Können wir Musikwünsche angeben?","Unbedingt. Im Vorgespräch erstelle ich mit euch eine Wunschliste und eine No-Go-Liste. Eure Lieblingssongs bekommen ihren Platz – zum richtigen Zeitpunkt."),
 ("Nimmst du auch Wünsche von Gästen an?","Ja, gern – solange sie zur Stimmung und zu euren Vorgaben passen. So fühlt sich jeder Gast abgeholt, ohne dass der rote Faden verloren geht."),
 ("Welche Musik spielst du?","Von Pop, Rock und Schlager über 80er, 90er und 2000er bis zu aktuellen Charts, House und Latin. Entscheidend ist nicht mein Geschmack, sondern euer Publikum."),
 ("Warum ein DJ statt einer Playlist?","Eine Playlist kennt eure Gäste nicht. Ich sehe, wann die Tanzfläche voll ist, wann sie eine Pause braucht und welcher Song jetzt den Unterschied macht – und reagiere live darauf."),
],
"Technik & Ablauf":[
 ("Bringst du eigene Technik mit?","Ja. Ton- und Lichtanlage sind geprüft, auf die Raumgröße abgestimmt und werden dezent aufgebaut. Ein Funkmikrofon für Reden gehört dazu."),
 ("Was brauchst du vor Ort?","Eine ebene Fläche für den DJ-Platz und einen Stromanschluss in der Nähe. Die Details stimme ich vorab mit euch oder der Location ab."),
 ("Moderierst du auch?","Ja – professionell und auf Wunsch den ganzen Abend: Einlauf, Reden, Programmpunkte, Spiele und Ansagen. Souverän, herzlich und nie aufdringlich."),
 ("Stimmst du dich mit Location und Dienstleistern ab?","Ja. Ich spreche mich mit Location, Catering, Fotografen oder Agentur ab, damit Zeitplan und Technik reibungslos zusammenpassen."),
 ("Wie groß ist dein Einsatzgebiet?","Mein Schwerpunkt liegt in Memmingen, Ulm, Oberschwaben und dem Allgäu – von Biberach und Ravensburg bis Kempten, Füssen und Landsberg. Weitere Orte auf Anfrage."),
],
}
def fq(*qs):
    flat={q:a for v in FAQ_ALL.values() for q,a in v}
    return [(q,flat[q]) for q in qs]

WISH=("Wunschtermin prüfen",KONTAKT)

# ---------------- Startseite ----------------
def start():
    vor=split("Vorstellung","Hallo, ich bin Kolja.",[
        "Hinter DJ KOLJA ONE stehe ich: DJ und Moderator. Seit über 10 Jahren sorge ich dafür, dass Hochzeiten, Geburtstage, Firmenfeiern und Events in Erinnerung bleiben.",
        "Mein Anspruch steckt schon im Claim: Jedes Event findet nur einmal statt. Deshalb plane ich jede Feier individuell, lese die Tanzfläche live und moderiere so, dass ihr euch um nichts kümmern müsst."],
        extra=T("Hochzeiten · Geburtstage · Firmenfeiern · Stadtfeste &amp; Open Airs",GOLD,"left",15),
        buttons=[BTN("Mehr über mich","/ueber-mich/",False,"left"),BTN(*WISH,True,"left")],img="kolja_portrait.jpg")
    return [nav(),
      hero("Memmingen · Ulm · Allgäu · Oberschwaben","Premium DJ für Hochzeiten, Firmenevents &amp; besondere Feste",
           "Eine volle Tanzfläche – vom ersten Song bis zum letzten.",[BTN(*WISH),BTN("Leistungen entdecken","#leistungen",False)],
           stats=None,extra=[W("html",{"html":open("./deck/deck-snippet.html").read(),"_element_width":"inherit","width":px(100,"%")})]),
      con([con([IMG(390,150,name=f"start_{i}.jpg",min_height_tablet=px(31,"vw"),min_height_mobile=px(29,"vw"),**col(23.5,23,22)) for i in range(1,5)],"row",g=12,flex_gap_mobile=gap(6),flex_wrap="nowrap",flex_direction_tablet="row",flex_direction_mobile="row",flex_wrap_tablet="nowrap",flex_wrap_mobile="nowrap",flex_justify_content="space-between")],
          "column",bg=B1,pad=box(0,20,56,20),inner=False,flex_align_items="center",padding_mobile=box(0,20,40,20)),
      section(head("Meine Leistungen","Der richtige Sound für jeden Anlass")+[tiles()],anchor="leistungen"),
      section(head("So einfach geht's","In drei Schritten zu eurem Wunschtermin")+[steps([
          ("Anfrage senden","Datum, Ort und Anlass per Formular oder WhatsApp. Ich melde mich innerhalb von 24 Stunden."),
          ("Persönliches Gespräch","Telefonisch oder per Video. Ich spreche mit euch über eure Musik, den Ablauf und eure Wünsche."),
          ("Termin fix","Ihr bekommt ein individuelles Angebot. Nach der Bestätigung ist euer Datum verbindlich reserviert.")]),
          SPACER(10),BTN("Jetzt Wunschtermin prüfen",KONTAKT)],bg=B2),
      vor, staerken(),
      reviews(["h1","f1","g1","h2","f2","g2","h3"]),
      faq(fq("Was kostet ein Event bei DJ KOLJA ONE?","Wie früh sollten wir buchen?","Was passiert, wenn du krank wirst?","Moderierst du auch?")),
      orte(), cta(),
      section(head("Individuelles Angebot","Maßgeschneidert statt Paket von der Stange")+[con([
          con([H(t,"h3",26,OFF,"left","400"),T(d,MUTED,"left",16),SPACER(4),BTN("Angebot anfragen",KONTAKT,False,"left")],bg=B2,pad=box(40,32,40,32),g=14,
              border_border="solid",border_width=box(2,0,0,0),border_color=GOLD,**col(31,100,100)) for t,d in [
            ("Hochzeit","Angebot nach eurem Ablauf und der Stundenzahl – vom Sektempfang bis zum letzten Song."),
            ("Geburtstag &amp; Privatfeier","Angebot nach Gästezahl, Location und Wunschprogramm."),
            ("Firmen- &amp; Großevents","Angebot inklusive Technikplanung und Abstimmung mit Location und Agentur.")]],
          "row",g=24,**ROW,flex_justify_content="space-between")]),
      section(head("Kontakt","Lasst uns über euer Fest sprechen")+[con([
          con([ICONBOX(i,t,d,"center",u)],bg=B1,pad=box(36,24,36,24),**col(31,100,100)) for i,t,d,u in [
            ("fas fa-phone","Anrufen",PHONE,TEL),("fas fa-envelope-open-text","Anfrage senden","Formular in 2 Minuten ausgefüllt",KONTAKT),
            ("fab fa-whatsapp","WhatsApp","Schnell und unkompliziert",WA)]],"row",g=24,**ROW,flex_justify_content="space-between")],bg=B2),
      footer()]

# ---------------- Leistungsseiten ----------------
def service(c):
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
    out.append(con([stats_quiet()],"column",bg=B1,pad=box(8,20,56,20),inner=False,flex_align_items="center",padding_mobile=box(0,20,40,20)))
    out.append(cta(*c.get("cta",())))
    out.append(section(head("Weitere Leistungen","Auch für andere Anlässe")+[tiles(c["cross"],w=31)],bg=B1))
    out.append(footer()); return out

HOCHZEIT=dict(key="hochzeit",eye="Hochzeits-DJ · Memmingen · Ulm · Allgäu",
 h1="Euer Hochzeits-DJ für einen Tag, der nur einmal stattfindet",
 sub="Vom Sektempfang bis zum letzten Song: Musik und Moderation, die zu euch passen – und eine Tanzfläche, die voll bleibt.",
 split1=("Musik für alle Generationen","Oma und Trauzeuge auf derselben Tanzfläche",[
   "Auf einer Hochzeit feiern Menschen zwischen 8 und 88. Meine Aufgabe ist es, alle mitzunehmen: mit Klassikern, aktuellen Hits und euren ganz persönlichen Lieblingssongs – im richtigen Moment und in der richtigen Reihenfolge.",
   "Ich spiele keine starre Playlist ab, sondern lese die Tanzfläche und reagiere live. So entsteht ein Abend, der sich anfühlt, als wäre er nur für euch gemacht. Denn genau das ist er."],None,True,[BTN(*WISH,True,"left")]),
 fit=(["Ihr wünscht euch eine volle Tanzfläche statt Hintergrundgedudel","Euch ist eine persönliche Planung im Vorfeld wichtig","Ihr möchtet eine souveräne Moderation, die nie aufdringlich ist","Ihr legt Wert auf hochwertigen Klang und stimmiges Licht"],
      ["Ihr sucht den günstigsten DJ der Region","Der DJ soll ausschließlich eine fertige Playlist abspielen","Euch reicht Musik vom Handy über eine Box"]),
 akte_eye="Euer Tag", akte_title="Musik und Moderation in vier Akten",
 akte=[("Sektempfang","Entspannte Lounge-Musik, während eure Gäste ankommen und anstoßen."),
       ("Dinner &amp; Reden","Dezente Musik zum Essen, Funkmikrofon für Reden und Beiträge – moderiert und im Zeitplan."),
       ("Eröffnungstanz","Euer Moment: perfekt angekündigt, perfekt eingeleitet."),
       ("Party","Von den ersten Tanzschritten bis zum letzten Song – die Tanzfläche bleibt voll.")],
 lists=[("Technik &amp; Moderation","Alles aus einer Hand",None,[
   ("Technik",["Geprüfte Ton- und Lichtanlage, abgestimmt auf eure Location","Dezenter, sauberer Aufbau vor dem Eintreffen der Gäste","Funkmikrofon für Reden, Spiele und Beiträge","Ambientebeleuchtung auf Anfrage"]),
   ("Moderation",["Begrüßung und Einlauf des Brautpaares","Ankündigung von Reden, Spielen und Programmpunkten","Abstimmung mit Trauzeugen, Fotograf und Location","Souverän, herzlich und nie aufdringlich"])])],
 compare=(("Vergleich","DJ, Band oder Playlist?"),[
   ("DJ","Riesige Musikauswahl, reagiert live auf eure Gäste, übernimmt Moderation und Technik. Die flexibelste Lösung für volle Tanzflächen."),
   ("Live-Band","Tolles Live-Erlebnis, aber begrenztes Repertoire, Pausen zwischen den Sets und meist deutlich mehr Platzbedarf."),
   ("Playlist","Günstig, aber ohne Gespür für den Moment: keine Moderation, keine Reaktion auf die Stimmung, niemand für die Technik zuständig.")]),
 steps_title="In fünf Schritten zu eurer Hochzeitsparty",
 steps=[("Anfrage","Datum, Location und Gästezahl per Formular oder WhatsApp."),("Kennenlernen","Unverbindliches Gespräch per Telefon oder Video."),
        ("Angebot","Ihr erhaltet ein individuelles Angebot und reserviert euren Termin."),("Planung","Einige Wochen vorher plane ich mit euch Musik, Ablauf und Programmpunkte im Detail."),
        ("Eure Hochzeit","Ich bin pünktlich da, alles steht – ihr feiert.")],
 reviews=["h1","h2","h3"],
 faq=fq("Was kostet ein Event bei DJ KOLJA ONE?","Wie früh sollten wir buchen?","Können wir Musikwünsche angeben?","Moderierst du auch?","Warum ein DJ statt einer Playlist?","Stimmst du dich mit Location und Dienstleistern ab?","Was passiert, wenn du krank wirst?"),
 faq_title="Fragen rund um eure Hochzeit",
 orte_title="Hochzeits-DJ in Oberschwaben, Ulm und dem Allgäu",
 cross=["/geburtstags-dj/","/firmenfeier-dj/","/event-dj/"])

GEBURTSTAG=dict(key="geburtstag",eye="Geburtstags-DJ · Memmingen · Ulm · Allgäu",
 h1="Geburtstags-DJ für Feste, von denen man noch lange spricht",
 sub="Ob 18., 30., 50. oder 80.: Ich sorge für die Musik, die zu euch und euren Gästen passt – und für eine Tanzfläche, die bis zum Schluss voll bleibt.",
 anl_title="Für welche Feste?",
 anlaesse=[("Runde Geburtstage","18., 30., 40., 50., 60. und mehr – jeder Meilenstein verdient seinen eigenen Soundtrack."),
           ("Überraschungspartys","Diskret geplant, perfekt getimt: Ich stimme mich mit den Organisatoren ab."),
           ("Jubiläen &amp; Familienfeiern","Silberhochzeit, Familientreffen oder Firmenjubiläum im privaten Kreis."),
           ("Garten- &amp; Scheunenpartys","Auch draußen und in besonderen Locations mit passender Technik."),
           ("Abifeiern &amp; Jugendpartys","Aktuelle Hits, kurze Übergänge und ein Gespür für junges Publikum."),
           ("Feste im Vereinsheim","Unkompliziert, zuverlässig und mit der richtigen Musik für alle Generationen.")],
 split1=("Gemischtes Publikum","Von 18 bis 80 – alle auf der Tanzfläche",[
   "Auf einem Geburtstag treffen Familie, Freunde und Kollegen aufeinander. Ich finde die Songs, die alle verbinden, und baue den Abend so auf, dass die Stimmung Schritt für Schritt steigt.",
   "Eure Lieblingssongs und Erinnerungsstücke bekommen ihren großen Moment – und was ihr auf keinen Fall hören wollt, kommt auf die No-Go-Liste."],None,True,[BTN(*WISH,True,"left")]),
 fit=(["Ihr wollt richtig feiern, nicht nur Hintergrundmusik","Die Musik soll zu euren Gästen passen, nicht zu einer Schablone","Ihr wünscht euch Moderation für Reden, Spiele oder Überraschungen","Ihr möchtet euch am Abend um nichts kümmern müssen"],
      ["Ihr sucht die günstigste Lösung","Musik soll nur leise im Hintergrund laufen","Ihr wollt nur eure eigene Playlist abspielen lassen"]),
 akte_eye="Euer Abend", akte_title="So wird aus einem Geburtstag ein Fest",
 akte=[("Ankommen","Lockere Musik zum Empfang, damit alle entspannt ins Gespräch kommen."),
       ("Essen &amp; Reden","Dezente Begleitung und ein Mikrofon für Reden und Glückwünsche."),
       ("Überraschungen","Spiele, Videos oder Ständchen – moderiert und mit den Beteiligten abgestimmt."),
       ("Party","Jetzt wird getanzt: mit den Songs, die euren Abend unvergesslich machen.")],
 steps_title="In drei Schritten zu eurer Party",
 steps=[("Anfrage","Datum, Ort und Anlass per Formular oder WhatsApp."),("Gespräch","Ich kläre mit euch Musik, Ablauf und Überraschungen."),("Party","Ich baue auf, ihr feiert.")],
 reviews=["g1","g2","h3"],
 faq=fq("Was kostet ein Event bei DJ KOLJA ONE?","Können wir Musikwünsche angeben?","Nimmst du auch Wünsche von Gästen an?","Moderierst du auch?","Was brauchst du vor Ort?"),
 faq_title="Fragen rund um euren Geburtstag",
 orte_title="Geburtstags-DJ in Oberschwaben, Ulm und dem Allgäu",
 cross=["/hochzeits-dj/","/firmenfeier-dj/","/event-dj/"])

FIRMA=dict(key="firmenfeier",eye="Firmenfeier-DJ · Memmingen · Ulm · Allgäu",
 h1="DJ für Firmenfeiern, die eure Marke stärken",
 sub="Weihnachtsfeier, Sommerfest, Gala oder Jubiläum: professionelle Musik und Moderation, abgestimmt auf euer Unternehmen, eure Gäste und euren Ablauf.",
 anl_title="Für welche Veranstaltungen?",
 anlaesse=[("Weihnachtsfeiern","Vom festlichen Dinner bis zur ausgelassenen Party – der Höhepunkt des Firmenjahres."),
           ("Sommerfeste","Entspannte Atmosphäre am Nachmittag, Stimmung am Abend – auch Open Air."),
           ("Galas &amp; Preisverleihungen","Stilvolle Begleitung, präzise Einspieler und souveräne Ansagen."),
           ("Firmenjubiläen","Ein Anlass, der eure Geschichte feiert – mit Musik, die alle Generationen verbindet."),
           ("Mitarbeiter- &amp; Kundenevents","Musik, die eure Gäste abholt und euer Unternehmen positiv in Erinnerung hält."),
           ("Messen &amp; Präsentationen","Dezente Hintergrundmusik, Jingles und Moderation für euren Auftritt.")],
 split1=("Musikalisches Konzept","Vom Empfang bis zur Aftershow",[
   "Eine Firmenfeier hat eine eigene Dramaturgie: ankommen, netzwerken, zuhören, feiern. Ich begleite jede Phase mit der passenden Musik – dezent, wenn Gespräche im Vordergrund stehen, mitreißend, wenn es auf die Tanzfläche geht.",
   "Dabei behalte ich euren Zeitplan, eure Programmpunkte und euer Markenbild im Blick. Das Ergebnis: ein Abend, über den eure Mitarbeiter und Gäste noch lange sprechen."],None,True,[BTN(*WISH,True,"left")]),
 lists=[("Zusammenarbeit","Professionell von der Anfrage bis zum letzten Song",None,[
   ("Organisation",["Ein fester Ansprechpartner von Anfang bis Ende","Abstimmung mit Location, Agentur und Catering","Pünktlicher Aufbau vor dem Eintreffen der Gäste","Angebot und Rechnung für eure Buchhaltung"]),
   ("Technik &amp; Moderation",["Geprüfte Ton- und Lichtanlage, skalierbar bis zu großen Sälen","Funkmikrofone für Reden und Ehrungen","Einspieler, Jingles und Ansagen nach Ablaufplan","Professionelle Moderation durch den Abend"])])],
 steps_title="In fünf Schritten zu eurem Event",
 steps=[("Anfrage","Datum, Location und Gästezahl per Formular oder Telefon."),("Briefing","Ich bespreche mit euch Anlass, Zielgruppe, Ablauf und Markenwelt."),
        ("Angebot","Ihr erhaltet ein individuelles Angebot inklusive Technikplanung."),("Feinabstimmung","Ablaufplan, Einspieler und Absprachen mit Location und Agentur."),
        ("Euer Event","Pünktlicher Aufbau, souveräne Durchführung, sauberer Abbau.")],
 reviews=["f1","f2","g2"],
 faq=[("Spielst du auch dezente Hintergrundmusik?","Ja. Beim Empfang, beim Dinner oder während des Networkings läuft die Musik bewusst im Hintergrund – die Lautstärke passe ich laufend an."),
      ("Kannst du Programmpunkte moderieren?","Ja. Von der Begrüßung über Ehrungen und Reden bis zu Gewinnspielen moderiere ich professionell und nach eurem Ablaufplan."),
      ("Stimmst du dich mit unserer Agentur oder Location ab?","Selbstverständlich. Ich kläre Technik, Zeitplan und Aufbau direkt mit allen Beteiligten, damit ihr euch auf eure Gäste konzentrieren könnt."),
      ("Wie viel Vorlauf brauchst du?","Für Weihnachtsfeiern im Dezember empfehle ich eine Anfrage bis zum Frühsommer. Für andere Termine reichen oft 2–4 Monate."),
      ("Ist deine Technik auch für große Räume geeignet?","Ja. Ton- und Lichtanlage werden auf Raumgröße und Gästezahl abgestimmt – vom Besprechungsraum bis zum großen Festsaal.")],
 faq_title="Fragen rund um eure Firmenfeier",
 orte_title="Firmenfeier-DJ in Oberschwaben, Ulm und dem Allgäu",
 cta=("Euer Wunschtermin ist noch frei?","Gerade im Dezember sind die Termine schnell vergeben. Fragt jetzt unverbindlich an."),
 cross=["/hochzeits-dj/","/geburtstags-dj/","/event-dj/"])

EVENT=dict(key="events",eye="Event-DJ · Memmingen · Ulm · Allgäu",
 h1="Event-DJ für Stadtfeste, Vereinsfeiern &amp; Open Airs",
 sub="Große Flächen, gemischtes Publikum, straffer Zeitplan: Ich sorge für Stimmung, die trägt – und für einen reibungslosen Ablauf mit Veranstalter und Technik.",
 anl_title="Für welche Events?",
 anlaesse=[("Stadt- &amp; Dorffeste","Musik und Moderation für Hunderte Gäste – vom Frühschoppen bis in die Nacht."),
           ("Vereinsfeiern &amp; Jubiläen","Vom Sportverein bis zur Feuerwehr: Feste mit Tradition und Stimmung."),
           ("Open Airs &amp; Festzelte","Technik und Erfahrung für draußen und für große Zelte."),
           ("Silvesterpartys","Der perfekte Countdown und eine Tanzfläche, die ins neue Jahr trägt."),
           ("Fasching &amp; Mottopartys","Vom Kinderfasching bis zum Ball – Musik passend zum Motto."),
           ("Abibälle &amp; Schulfeiern","Feierlicher Rahmen, ausgelassene Party – und ein Gespür für junges Publikum.")],
 split1=("Stimmung für viele","Wenn aus Publikum eine Party wird",[
   "Bei öffentlichen Events kommen Menschen mit ganz unterschiedlichen Erwartungen zusammen. Ich spiele Musik, die verbindet, halte den Spannungsbogen über Stunden und reagiere flexibel auf Wetter, Programm und Publikum.",
   "Als erfahrener Moderator übernehme ich auf Wunsch auch Ansagen, Programmpunkte und die Begleitung von Auftritten."],None,True,[BTN(*WISH,True,"left")]),
 lists=[("Organisation","Verlässlich für Veranstalter",None,[
   ("Abstimmung",["Enge Absprache mit Veranstalter und Bühnentechnik","Einhaltung von Zeitplänen und Lautstärkevorgaben","Hinweis auf GEMA-Anmeldung durch den Veranstalter","Flexible Anpassung an Wetter und Programm"]),
   ("Technik &amp; Moderation",["Geprüfte, skalierbare Ton- und Lichttechnik","Anbindung an vorhandene Bühnentechnik möglich","Moderation von Programmpunkten und Ansagen","Durchsagen für Organisation und Sponsoren"])])],
 steps_title="In drei Schritten zu eurem Event",
 steps=[("Anfrage","Datum, Ort, Besucherzahl und Programm per Formular oder Telefon."),("Planung","Ich kläre mit euch Technik, Zeitplan und Moderation."),("Event","Ich sorge für Stimmung – ihr für den Rest.")],
 reviews=["f2","g1","f1"],
 faq=[("Muss ich GEMA-Gebühren zahlen?",dict(fq("Muss ich GEMA-Gebühren zahlen?"))["Muss ich GEMA-Gebühren zahlen?"]),
      ("Kannst du vorhandene Bühnentechnik nutzen?","Ja. Ich kann mich in eine bestehende Beschallungsanlage einklinken oder eigene Technik mitbringen – das kläre ich vorab mit eurer Technik."),
      ("Moderierst du auch Programmpunkte?","Ja. Ansagen, Auftritte, Verlosungen und Sponsorenhinweise moderiere ich professionell und nach eurem Ablaufplan."),
      ("Spielst du auch Open Air?","Ja – mit wetterfester Planung. Stromversorgung und Überdachung für den DJ-Platz stimme ich vorab mit euch ab.")],
 faq_title="Fragen rund um euer Event",
 orte_title="Event-DJ in Oberschwaben, Ulm und dem Allgäu",
 cta=("Euer Event steht im Kalender?","Fragt jetzt unverbindlich an – ich melde mich innerhalb von 24 Stunden."),
 cross=["/hochzeits-dj/","/geburtstags-dj/","/firmenfeier-dj/"])

# ---------------- Über mich ----------------
def ueber():
    quote=section([H("„Jedes Event findet nur einmal statt.“","p",48,OFF,m=30,lh=1.25,_element_width="initial",_element_custom_width=px(900)),DIV(),
        T("Dieser Satz ist mein Versprechen. Es gibt keine Generalprobe und keine Wiederholung – nur diesen einen Abend. Deshalb nehme ich mir Zeit für die Vorbereitung, höre genau zu und bin am Tag selbst mit voller Aufmerksamkeit bei euch.",MUTED,"center",18,_element_width="initial",_element_custom_width=px(760))],bg=B2)
    werte=section(head("Was mich ausmacht","Musik, Moderation, Verlässlichkeit")+[con([con([ICONBOX(*i)],bg=B2,pad=box(36,30,36,30),**col(48,48,100)) for i in [
        ("fas fa-headphones","Gespür für die Tanzfläche","Ich spiele nicht nach Plan, sondern nach Stimmung. Wer tanzt, wer zögert, was kommt als Nächstes? Darauf reagiere ich live."),
        ("fas fa-microphone","Professionelle Moderation","Ich führe souverän durch den Abend – herzlich, klar und nie aufdringlich."),
        ("fas fa-sliders-h","Sauberer Sound","Geprüfte Technik, abgestimmt auf eure Location – laut genug zum Tanzen, angenehm genug zum Reden."),
        ("fas fa-handshake","Verlässlichkeit","Pünktlich, vorbereitet, erreichbar – vom ersten Gespräch bis zum letzten Song.")]],
        "row",g=24,**ROW,flex_justify_content="space-between")])
    musik=section(head("Musik","Mein Repertoire")+[cards([
        ("Pop &amp; Charts","Aktuelle Hits und die großen Songs der letzten Jahrzehnte."),("80er, 90er &amp; 2000er","Die Klassiker, bei denen jede Generation mitsingt."),
        ("Schlager &amp; Party","Wenn es zur richtigen Zeit passt – mit Augenmaß."),("Rock &amp; Indie","Gitarren für die, die es etwas rauer mögen."),
        ("House &amp; Dance","Für späte Stunden und volle Tanzflächen."),("Latin, Soul &amp; Lounge","Für Empfang, Dinner und besondere Momente.")])],bg=B1)
    galerie=section(head("Einblicke","Hinter dem DJ-Pult")+[con([IMG(280,220,name=f"ueber_{i}.jpg",**col(31,48,100)) for i in (1,2,3)],"row",g=20,**ROW,flex_justify_content="space-between")],bg=B2)
    return [nav(),hero("Über mich","Hallo, ich bin Kolja.","DJ und Moderator aus Fellheim – und überzeugt davon, dass jedes Event nur einmal stattfindet.",[BTN(*WISH),BTN("Meine Leistungen","/#leistungen",False)],minh=80),
        split("Meine Geschichte","DJ KOLJA ONE",[
            "Seit über 10 Jahren stehe ich hinter dem DJ-Pult: auf Hochzeiten, Geburtstagen, Firmenfeiern und Stadtfesten zwischen Memmingen, Ulm und dem Allgäu.",
            "Was mich antreibt, ist der Moment, in dem eine Feier kippt – von „nett“ zu „unvergesslich“. Wenn die Tanzfläche voll ist, das Brautpaar strahlt oder das ganze Team mitsingt. Genau diese Momente plane ich mit euch und sorge am Abend dafür, dass sie passieren."],img="kolja_portrait.jpg"),
        quote, werte, musik, galerie, reviews(["h2","f2","g1"]), cta(), footer()]

# ---------------- Meine Musik ----------------
MUSIK_SNIPPET="./musik/musik-snippet-final.html"
def _tight(c,pad,mob):
    c["settings"]["padding"]=pad; c["settings"]["padding_mobile"]=mob; return c
def musik():
    return [nav(),
      _tight(hero("Meine Musik","Leg selbst auf","Such dir eine Platte aus der Kiste, leg sie aufs Deck und hör rein, wie DJ KOLJA ONE klingt.",
           [],stats=None,minh=55),box(72,20,0,20),box(40,20,0,20)),
      _tight(section([W("html",{"html":open(MUSIK_SNIPPET).read(),"_element_width":"inherit","width":px(100,"%")})],anchor="auflegen"),box(0,20,64,20),box(0,12,40,12)),
      section(head("So geht's","Dein eigener Mix in vier Schritten")+[steps([
          ("Platte wählen","Zieh einen Song aus der Plattenkiste. Er landet abwechselnd auf Deck A oder Deck B und startet sofort."),
          ("Mixen","Mit dem Crossfader blendest du zwischen den beiden Decks über. Tippst du auf eine Platte, bremst sie ab – noch einmal tippen, und sie läuft weiter."),
          ("Video ansehen","Songs mit dem Label VIDEO laufen mit Bild im Laptop neben dem Pult."),
          ("Bewerten","Gib jedem Song 1 bis 5 Sterne. Der Durchschnitt aller Besucher steht direkt am Song.")])],bg=B2),
      section(head("Musik","Mein Repertoire")+[cards([
        ("Pop &amp; Charts","Aktuelle Hits und die großen Songs der letzten Jahrzehnte."),("80er, 90er &amp; 2000er","Die Klassiker, bei denen jede Generation mitsingt."),
        ("Schlager &amp; Party","Wenn es zur richtigen Zeit passt – mit Augenmaß."),("Rock &amp; Indie","Gitarren für die, die es etwas rauer mögen."),
        ("House &amp; Dance","Für späte Stunden und volle Tanzflächen."),("Latin, Soul &amp; Lounge","Für Empfang, Dinner und besondere Momente.")])]),
      cta("Euer Lieblingssong fehlt?","Im Vorgespräch plane ich eure Musik mit euch – mit Wunsch- und No-Go-Liste. Fragt jetzt unverbindlich an."),
      footer()]

# ---------------- FAQ ----------------
def faqpage():
    secs=[faq(v,title=k,more=False,bg=(B1 if i%2==0 else B2),eye="FAQ") for i,(k,v) in enumerate(FAQ_ALL.items())]
    return [nav(),hero("FAQ &amp; Wissenswertes","Häufige Fragen rund um euren DJ","Preise, Buchung, Musik, Technik und Ablauf – hier findet ihr die Antworten. Und wenn eure Frage fehlt: einfach anrufen oder schreiben.",
        [BTN(*WISH),BTN("WhatsApp schreiben",WA,False)],stats=None,minh=60)]+secs+[cta("Noch Fragen?","Ich beantworte sie gern persönlich – per Telefon, WhatsApp oder über das Anfrageformular."),footer()]

# ---------------- Einsatzgebiete ----------------
GROUPS=[("Memmingen &amp; Unterallgäu",["Memmingen"],"Mein Heimatgebiet rund um Fellheim."),
        ("Ulm &amp; Donau",["Ulm"],"Ulm, Neu-Ulm und das Umland entlang der Donau."),
        ("Oberschwaben &amp; Bodensee",["Biberach","Ravensburg"],"Vom Riß-Tal bis ins Schussental."),
        ("Allgäu",["Kempten","Kaufbeuren","Füssen"],"Vom Oberallgäu bis ins Ostallgäu."),
        ("Lechrain",["Landsberg"],"Landsberg am Lech und Umgebung.")]
def regionen():
    slugs=dict(ORTE)
    grp=[con([H(t,"h3",24,OFF,"left","400"),T(d,MUTED,"left",15)]+[H("DJ "+o+" →","p",17,GOLD,"left","400",link=f"/{slugs[o]}/") for o in os_],
             bg=B2,pad=box(34,28,34,28),g=10,border_border="solid",border_width=box(2,0,0,0),border_color=GOLD,**col(31,48,100)) for t,os_,d in GROUPS]
    return [nav(),hero("Einsatzgebiete","Euer DJ in Oberschwaben, Ulm und dem Allgäu","Mobiler DJ mit Heimat in Fellheim bei Memmingen – für Hochzeiten, Geburtstage, Firmenfeiern und Events in der ganzen Region.",
        [BTN(*WISH),BTN("Alle Leistungen","/#leistungen",False)],stats=[("Fellheim","Heimat bei Memmingen"),("8 Städte","feste Einsatzgebiete"),("rund 100 km","Umkreis"),("Weiter?","gern auf Anfrage")],minh=80),
        section(head("Regionen","Hier bin ich für euch unterwegs")+[con(grp,"row",g=24,**ROW,flex_justify_content="center")]),
        section(head("Leistungen","Für jeden Anlass")+[tiles()],bg=B2),
        cta("Euren Ort nicht gefunden?","Kein Problem – ich komme auch darüber hinaus. Fragt einfach unverbindlich an."),footer()]

# ---------------- Städte ----------------
CITIES={
 "Memmingen":("15","Memmingen ist mein Heimspiel: Von Fellheim aus bin ich in rund einer Viertelstunde bei euch. Ob Feier in der historischen Altstadt, im Landgasthof im Unterallgäu oder in einer Firmenlocation am Stadtrand – ich kenne die Region und ihre Menschen.",["Ulm","Biberach","Kempten"]),
 "Ulm":("50","Zwischen Münster und Donau wird gern gefeiert – von der Firmenveranstaltung in Ulm und Neu-Ulm bis zur Hochzeit im Umland. Über die A7 bin ich von Fellheim aus schnell vor Ort.",["Memmingen","Biberach","Kaufbeuren"]),
 "Biberach":("40","Biberach an der Riß und das oberschwäbische Land bieten Locations zwischen Gutshof, Scheune und Festsaal. Genau dort sorge ich für die Musik, die zu eurer Feier passt.",["Memmingen","Ulm","Ravensburg"]),
 "Ravensburg":("85","Die Stadt der Türme, das Schussental und der nahe Bodensee: Rund um Ravensburg wird mit Stil gefeiert. Ich bringe Musik, Moderation und Technik mit – ihr bringt die Gäste.",["Biberach","Kempten","Memmingen"]),
 "Kempten":("50","Kempten ist das Herz des Allgäus – und die Kulisse für Hochzeiten mit Bergblick, Firmenfeiern und große Geburtstage. Von Fellheim aus bin ich schnell über die A7 bei euch.",["Memmingen","Kaufbeuren","Füssen"]),
 "Füssen":("95","Königsschlösser, Forggensee und Alpenpanorama: Wer in Füssen feiert, hat die schönste Kulisse schon gebucht. Den passenden Soundtrack liefere ich.",["Kempten","Kaufbeuren","Landsberg"]),
 "Kaufbeuren":("55","Kaufbeuren und das Ostallgäu verbinden Tradition und Lebensfreude. Ob Hochzeit, Vereinsfest oder Firmenfeier – ich sorge dafür, dass die Tanzfläche voll bleibt.",["Kempten","Füssen","Landsberg"]),
 "Landsberg":("75","Historische Altstadt, Lechwehr und Lechrain: Landsberg am Lech ist wie gemacht für besondere Feste. Ich bin gern für euch vor Ort.",["Kaufbeuren","Memmingen","Füssen"]),
}
def city(name):
    km,intro,near=CITIES[name]; slugs=dict(ORTE)
    long="Landsberg am Lech" if name=="Landsberg" else ("Biberach an der Riß" if name=="Biberach" else name)
    tips=[("Location","Jede Location hat ihre Eigenheiten. Ich kläre Aufbau, Strom und Lautstärke vorab direkt mit dem Haus."),
          ("Musik","Wir planen eure Musik gemeinsam – mit Wunsch- und No-Go-Liste und Raum für Spontanes."),
          ("Ton &amp; Licht","Die Technik wird auf Raumgröße und Gästezahl abgestimmt – dezent im Aufbau, stark im Klang."),
          ("Termin","Gerade samstags in der Hochsaison lohnt sich eine frühe Anfrage.")]
    chips=[con([H("DJ "+o,"p",19,OFF,"center","300",link=f"/{slugs[o]}/")],pad=box(18,10,18,10),border_border="solid",
               border_width=box(1,1,1,1),border_color="rgba(178,157,117,0.35)",**col(23,31,48)) for o in near]
    return [nav(),
      hero(f"DJ {name} · Hochzeit · Geburtstag · Firmenfeier · Event",f"Euer DJ für {long} und Umgebung",
           f"Musik, Moderation und Technik aus einer Hand – für Feiern in {name}, die man so schnell nicht vergisst.",
           [BTN(*WISH),BTN("Leistungen ansehen","#leistungen",False)],
           stats=[("10+ Jahre","DJ-Erfahrung"),("4 Anlässe","Hochzeit · Geburtstag · Firma · Event"),("Moderation","professionell &amp; souverän"),(f"ca. {km} km","ab Fellheim")],minh=85),
      split(f"Feiern in {name}",f"DJ in {name}",[intro,"Ich plane jede Feier individuell: mit eurer Musik, eurem Ablauf und einem Gespür dafür, was eure Gäste gerade brauchen."],
            buttons=[BTN(*WISH,True,"left")],img="start_1.jpg"),
      section(head("Leistungen",f"Mein Angebot in {name}")+[tiles()],bg=B2,anchor="leistungen"),
      section(head("Planung","Darauf kommt es an")+[steps(tips)]),
      staerken(),
      section(head("In der Nähe","Auch hier bin ich für euch da")+[con(chips,"row",g=16,flex_wrap="wrap",flex_direction_mobile="row",flex_justify_content="center"),
          SPACER(6),H("Alle Einsatzgebiete →","p",13,GOLD,"center","500",link="/einsatzgebiete/",ls=1.5,tr="uppercase")]),
      faq([(f"Kommst du auch nach {name}?",f"Ja – {name} gehört zu meinem festen Einsatzgebiet. Von Fellheim aus sind es nur rund {km} Kilometer."),
           ("Wird die Anfahrt extra berechnet?","Die Anfahrt ist Teil eures individuellen Angebots – transparent und ohne Überraschungen."),
           (f"Spielst du in {name} auch Firmenfeiern und Events?","Ja. Neben Hochzeiten und Geburtstagen begleite ich auch Weihnachtsfeiern, Sommerfeste, Vereinsfeiern und Stadtfeste."),
           ("Wie früh sollten wir buchen?",dict(fq("Wie früh sollten wir buchen?"))["Wie früh sollten wir buchen?"])],title=f"Fragen zu DJ {name}"),
      cta(f"Feier in {name} geplant?","Fragt jetzt unverbindlich an – ich melde mich innerhalb von 24 Stunden."),footer()]

# ---------------- Kontakt ----------------
def kontakt():
    form=con([H("Anfrage senden","h2",32,OFF,"left","300"),T("Datum, Ort, Anlass und Gästezahl – mehr brauche ich für den Anfang nicht.",MUTED,"left",16),
              W("html",{"html":open("./formcss/wpforms-dark.html").read()}),W("shortcode",{"shortcode":"[wpforms id=5020]"})],bg=B2,pad=box(44,40,44,40),g=16,**col(58,100,100),
             border_border="solid",border_width=box(2,0,0,0),border_color=GOLD)
    side=con([con([ICONBOX(i,t,d,"left",u)],bg=B2,pad=box(28,26,28,26)) for i,t,d,u in [
        ("fas fa-phone","Anrufen",PHONE,TEL),("fab fa-whatsapp","WhatsApp","Schnell und unkompliziert",WA),("fas fa-envelope","E-Mail",MAIL,"mailto:"+MAIL)]],g=16,**col(38,100,100))
    return [nav(),hero("Anfrage","Wunschtermin prüfen","In drei Schritten zu eurem individuellen Angebot – unverbindlich und persönlich.",[BTN("Zum Formular","#formular"),BTN("WhatsApp schreiben",WA,False)],stats=None,minh=55),
        section([con([form,side],"row",g=30,**ROW,flex_justify_content="space-between",flex_align_items="flex-start")],anchor="formular"),
        section(head("So geht's weiter","Nach eurer Anfrage")+[steps([("Antwort in 24 Stunden","Ich prüfe euren Termin und melde mich persönlich."),
            ("Kennenlernen","Wir sprechen über Musik, Ablauf und eure Wünsche."),("Individuelles Angebot","Unverbindlich, transparent und auf euch zugeschnitten.")])],bg=B2),
        footer()]

# ---------------- Rechtliches ----------------
def impressum():
    return text_page("Impressum",[
      "<h3>Angaben gemäß § 5 DDG</h3><p>Kolja Tönges<br>DJ KOLJA ONE<br>Pfarrer-Ritter-Weg 9<br>87748 Fellheim</p>",
      f"<h3>Kontakt</h3><p>Telefon: {PHONE}<br>E-Mail: {MAIL}</p>",
      "<h3>Umsatzsteuer</h3><p>[BITTE ERGÄNZEN: Umsatzsteuer-Identifikationsnummer gemäß § 27a UStG – oder den Hinweis „Gemäß § 19 UStG wird keine Umsatzsteuer berechnet (Kleinunternehmerregelung).“]</p>",
      "<h3>Verantwortlich für den Inhalt nach § 18 Abs. 2 MStV</h3><p>Kolja Tönges, Anschrift wie oben</p>",
      "<h3>Verbraucherstreitbeilegung</h3><p>Ich bin nicht bereit und nicht verpflichtet, an Streitbeilegungsverfahren vor einer Verbraucherschlichtungsstelle teilzunehmen.</p>",
      "<h3>Haftung für Inhalte</h3><p>Die Inhalte dieser Seiten wurden mit größter Sorgfalt erstellt. Für die Richtigkeit, Vollständigkeit und Aktualität der Inhalte kann ich jedoch keine Gewähr übernehmen. Als Diensteanbieter bin ich für eigene Inhalte nach den allgemeinen Gesetzen verantwortlich.</p>",
      "<h3>Haftung für Links</h3><p>Diese Website enthält Links zu externen Websites Dritter, auf deren Inhalte ich keinen Einfluss habe. Für diese fremden Inhalte ist stets der jeweilige Anbieter oder Betreiber verantwortlich. Bei Bekanntwerden von Rechtsverletzungen werden derartige Links umgehend entfernt.</p>",
      "<h3>Urheberrecht</h3><p>Die durch den Seitenbetreiber erstellten Inhalte und Werke auf diesen Seiten unterliegen dem deutschen Urheberrecht. Vervielfältigung, Bearbeitung und Verbreitung außerhalb der Grenzen des Urheberrechts bedürfen der schriftlichen Zustimmung des Erstellers.</p>"])

def datenschutz():
    return text_page("Datenschutzerklärung",[
      f"<h3>1. Verantwortlicher</h3><p>Kolja Tönges, DJ KOLJA ONE<br>Pfarrer-Ritter-Weg 9, 87748 Fellheim<br>Telefon: {PHONE}<br>E-Mail: {MAIL}</p>",
      "<h3>2. Allgemeines</h3><p>Der Schutz deiner persönlichen Daten ist mir wichtig. Ich verarbeite personenbezogene Daten nur im Rahmen der gesetzlichen Bestimmungen, insbesondere der Datenschutz-Grundverordnung (DSGVO). Diese Erklärung informiert dich darüber, welche Daten beim Besuch dieser Website erhoben werden und wofür sie genutzt werden.</p>",
      "<h3>3. Hosting</h3><p>Diese Website wird bei der STRATO AG, Otto-Ostrowski-Straße 7, 10249 Berlin, gehostet. Beim Aufruf der Website werden durch den Hoster automatisch Informationen in sogenannten Server-Logfiles gespeichert (z. B. IP-Adresse, Datum und Uhrzeit des Zugriffs, aufgerufene Seite, Browsertyp). Die Verarbeitung erfolgt auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO; mein berechtigtes Interesse liegt in einem sicheren und stabilen Betrieb der Website. Mit STRATO besteht ein Vertrag zur Auftragsverarbeitung.</p>",
      "<h3>4. SSL-/TLS-Verschlüsselung</h3><p>Diese Seite nutzt aus Sicherheitsgründen eine SSL- bzw. TLS-Verschlüsselung. Eine verschlüsselte Verbindung erkennst du am Schloss-Symbol in der Adresszeile deines Browsers.</p>",
      "<h3>5. Kontaktaufnahme per Formular, E-Mail oder Telefon</h3><p>Wenn du mir eine Anfrage sendest, verarbeite ich die von dir angegebenen Daten (z. B. Name, E-Mail-Adresse, Telefonnummer, Veranstaltungsdatum und -ort, Nachricht) ausschließlich zur Bearbeitung deiner Anfrage und für mögliche Anschlussfragen. Rechtsgrundlage ist Art. 6 Abs. 1 lit. b DSGVO (vorvertragliche Maßnahmen) bzw. Art. 6 Abs. 1 lit. f DSGVO. Die Daten werden gelöscht, sobald sie für den Zweck nicht mehr erforderlich sind und keine gesetzlichen Aufbewahrungspflichten entgegenstehen.</p>",
      "<h3>6. WhatsApp</h3><p>Auf dieser Website befindet sich ein Link zu WhatsApp. Beim bloßen Besuch der Website werden keine Daten an WhatsApp übertragen. Erst wenn du den Link anklickst, wirst du zu WhatsApp weitergeleitet. Anbieter ist die WhatsApp Ireland Limited, 4 Grand Canal Square, Dublin 2, Irland, ein Unternehmen der Meta-Gruppe. Dabei können Daten auch in die USA übertragen werden. Bitte nutze WhatsApp nur, wenn du mit der Datenverarbeitung durch WhatsApp einverstanden bist; alternativ erreichst du mich jederzeit per Telefon, E-Mail oder Kontaktformular. Weitere Informationen findest du in der Datenschutzrichtlinie von WhatsApp.</p>",
      "<h3>7. Schriftarten</h3><p>Die auf dieser Website verwendeten Schriftarten werden lokal von meinem Server geladen. Eine Verbindung zu Servern von Google oder anderen Drittanbietern findet dabei nicht statt.</p>",
      "<h3>8. Song-Bewertungen</h3><p>Auf der Seite „Meine Musik“ kannst du Songs mit 1 bis 5 Sternen bewerten. Gespeichert wird nur die abgegebene Sternezahl, nicht dein Name. Damit jeder Song pro Besucher nur einmal bewertet werden kann, speichere ich für 30 Tage einen verschlüsselten, nicht umkehrbaren Prüfwert (Hash), der aus deiner IP-Adresse und dem Song gebildet wird; die IP-Adresse selbst wird nicht gespeichert. Zusätzlich merkt sich dein Browser im lokalen Speicher, welche Songs du bewertet hast. Rechtsgrundlage ist Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse an unverfälschten Bewertungen) bzw. § 25 Abs. 2 Nr. 2 TDDDG für die von dir angeforderte Bewertungsfunktion. Den Eintrag im Browser kannst du jederzeit über deine Browsereinstellungen löschen.</p>",
      "<h3>9. Cookies und Analyse</h3><p>Diese Website verwendet derzeit keine Analyse- oder Marketing-Tools und setzt keine Cookies zu Tracking-Zwecken ein. Technisch notwendige Cookies können durch das Content-Management-System gesetzt werden, etwa für angemeldete Administratoren.</p>",
      "<h3>10. Deine Rechte</h3><p>Du hast jederzeit das Recht auf Auskunft (Art. 15 DSGVO), Berichtigung (Art. 16), Löschung (Art. 17), Einschränkung der Verarbeitung (Art. 18), Datenübertragbarkeit (Art. 20) sowie Widerspruch gegen die Verarbeitung (Art. 21 DSGVO). Wende dich dazu einfach an die oben genannten Kontaktdaten.</p>",
      "<h3>11. Beschwerderecht</h3><p>Du hast das Recht, dich bei einer Datenschutz-Aufsichtsbehörde zu beschweren. Zuständig ist das Bayerische Landesamt für Datenschutzaufsicht (BayLDA), Promenade 18, 91522 Ansbach.</p>",
      "<h3>12. Aktualität</h3><p>Stand: September 2026. Ich passe diese Datenschutzerklärung an, sobald sich die Website oder die rechtlichen Vorgaben ändern.</p>"])

PAGES=[("Start","start",start),
 ("Hochzeits-DJ","hochzeits-dj",lambda: service(HOCHZEIT)),("Geburtstags-DJ","geburtstags-dj",lambda: service(GEBURTSTAG)),
 ("Firmenfeier-DJ","firmenfeier-dj",lambda: service(FIRMA)),("Event-DJ","event-dj",lambda: service(EVENT)),
 ("Meine Musik","meine-musik",musik),
 ("Über mich","ueber-mich",ueber),("FAQ","faq",faqpage),("Einsatzgebiete","einsatzgebiete",regionen)]
PAGES+=[("DJ "+o,s,(lambda o=o: city(o))) for o,s in ORTE]
PAGES+=[("Kontakt","kontakt",kontakt),("Impressum","impressum",impressum),("Datenschutz","datenschutz",datenschutz)]
