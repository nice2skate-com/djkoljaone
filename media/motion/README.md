# DJ KOLJA ONE Motion (40 s)

Quelle: `motion.html` (aus Claude „DJ KOLJA ONE Motion“, Texte an die Website angepasst: ihr/euch, kein Ersatz-DJ-Versprechen, ohne Lechfeld, Kennenlerngespräch). Schrift Fira Sans liegt lokal in `fonts/` (SIL Open Font License).

Rendern (Einzelbilder mit gesteuerter Zeit, dann ffmpeg):

    node media/motion/render.js h 40 30 /tmp/h   # 1920×1080
    node media/motion/render.js v 40 30 /tmp/v   # 1080×1920
    ffmpeg -framerate 30 -i /tmp/h/%05d.jpg -vf scale=1280:720 -c:v libx264 -crf 24 -pix_fmt yuv420p -movflags +faststart -an start_motion.mp4
    ffmpeg -framerate 30 -i /tmp/v/%05d.jpg -vf scale=720:1280 -c:v libx264 -crf 24 -pix_fmt yuv420p -movflags +faststart -an start_motion_hoch.mp4

Website: Die Videos liegen im Plugin unter `assets/motion/` und werden automatisch verwendet. Gleichnamige Dateien in der Mediathek (`start_motion.mp4` usw.) haben Vorrang.

Für Instagram/Facebook: `export/reel_dj-kolja-one_1080x1920.mp4` (Reel) und `export/dj-kolja-one_1920x1080.mp4` (16:9).

# DJ KOLJA ONE Equipment-Motion (86 s)

Quelle: `equipment.html` („Seite B“ zum Start-Motion). Inhalte von der Equipment-Seite: Sound, DJ-Pult, Licht, Lichtshow, Technik-Pakete S/M/L, Vorteile, Erfahrung, Kontakt. Szenen mit viel Text laufen länger (7–13 s).

Bilder liegen in `equipment/`: freigestellte Gerätefotos und Partyfotos aus der Mediathek von dj-kolja-one.de (verkleinert, WebP), dazu das Porträt fürs Intro. Die Zuordnung steht oben im Skript (`const IMG=…`). Ein Bild tauschen: Datei gleichen Namens ersetzen.

Rendern (die Seite als 5. Argument):

    node media/motion/render.js h 86 30 /tmp/eh equipment.html   # 1920×1080
    node media/motion/render.js v 86 30 /tmp/ev equipment.html   # 1080×1920
    ffmpeg -framerate 30 -i /tmp/eh/%05d.jpg -c:v libx264 -crf 22 -pix_fmt yuv420p -movflags +faststart -an media/motion/export/equipment_1920x1080.mp4
    ffmpeg -framerate 30 -i /tmp/ev/%05d.jpg -c:v libx264 -crf 22 -pix_fmt yuv420p -movflags +faststart -an media/motion/export/reel_equipment_1080x1920.mp4

Die beiden Formate nacheinander rendern, nicht parallel; parallel bricht ein Lauf gelegentlich ab.

Für Instagram/Facebook: `export/reel_equipment_1080x1920.mp4` (Reel) und `export/equipment_1920x1080.mp4` (16:9). Beide ohne Ton.

# Reel „Google-Bewertung & Technik“ (27 s, seit 21.11.2026)

Quelle: `bewertung.html` (reine CSS-Animation, echte 5-Sterne-Bewertung von Jürgen N. auf Google, Wortlaut gekürzt; Bose S1 Pro und Shure Beta 58A aus `equipment/`). Musik: „Life Is Good“ ab 1:00.

    node media/motion/render_css.js media/motion/bewertung.html /tmp/bw 27
    ffmpeg -framerate 30 -i /tmp/bw/%05d.jpg -c:v libx264 -crf 20 -pix_fmt yuv420p -movflags +faststart -an /tmp/bw.mp4
    python3 social/video_mischen.py /tmp/bw.mp4 media/motion/export/reel_bewertung_technik_1080x1920.mp4 --musik song.mp3 --start 60

`render_css.js` hält alle CSS-Animationen an und setzt sie Bild für Bild auf die genaue Zeit (für Seiten ohne JS-Ablauf;
`social/motion_render.js` läuft bei reinen CSS-Animationen zu schnell).

# Reel „Karaoke“ (30 s, seit 21.11.2026)

KI-Clips aus Higgsfield (Kling 3.0, Handy-Look, Symbolszenen – im Reel als „Symbolszene (KI)“ gekennzeichnet, beim Posten „Mit KI erstellt“ setzen):
wilde Szene (seitlich geliefert, gedreht und als Handy-Schwenk zugeschnitten), Song-Battle mit Partyhüten, Bühne mit Beamer.
Darüber `karaoke.html` als transparentes Overlay (Texte, Technik-Szene, Abschluss), Musik „Life Is Good“ leise unter dem Jubel.

    node media/motion/render_css.js media/motion/karaoke.html /tmp/ko 30 30 png
    # Segmente: wild 0–9 s, Battle 1,5–8,5 s, 6 s dunkel, Bühne 3–7 s, 4 s dunkel → base.mp4, dann Overlay + Musik

Export: `export/reel_karaoke_1080x1920.mp4`.
