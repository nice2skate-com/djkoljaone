# DJ KOLJA ONE Motion (40 s)

Quelle: `motion.html` (aus Claude „DJ KOLJA ONE Motion“, Texte an die Website angepasst: ihr/euch, kein Ersatz-DJ-Versprechen, ohne Lechfeld, Kennenlerngespräch). Schrift Fira Sans liegt lokal in `fonts/` (SIL Open Font License).

Rendern (Einzelbilder mit gesteuerter Zeit, dann ffmpeg):

    node media/motion/render.js h 40 30 /tmp/h   # 1920×1080
    node media/motion/render.js v 40 30 /tmp/v   # 1080×1920
    ffmpeg -framerate 30 -i /tmp/h/%05d.jpg -vf scale=1280:720 -c:v libx264 -crf 24 -pix_fmt yuv420p -movflags +faststart -an start_motion.mp4
    ffmpeg -framerate 30 -i /tmp/v/%05d.jpg -vf scale=720:1280 -c:v libx264 -crf 24 -pix_fmt yuv420p -movflags +faststart -an start_motion_hoch.mp4

Website: Die Videos liegen im Plugin unter `assets/motion/` und werden automatisch verwendet. Gleichnamige Dateien in der Mediathek (`start_motion.mp4` usw.) haben Vorrang.

Für Instagram/Facebook: `export/reel_dj-kolja-one_1080x1920.mp4` (Reel) und `export/dj-kolja-one_1920x1080.mp4` (16:9).
