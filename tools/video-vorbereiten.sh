#!/bin/sh
# Handyvideos für die Website vorbereiten: verkleinert, komprimiert, fertig benannt (Ton bleibt erhalten; mit STUMM=1 wird er entfernt).
# Voraussetzung (einmalig, Mac):  brew install ffmpeg
# Optional (davor schreiben):  STUMM=1  entfernt den Ton.   GEMA=1  entfernt den Ton UND blendet unten rechts „Ohne Ton aus GEMA-Gründen“ ein.
# Aufruf:  sh video-vorbereiten.sh <seite> <video1> [<video2> ... bis 8]
#   <seite> = hochzeit | geburtstag | firmenfeier | events
# Beispiel: [STUMM=1] sh video-vorbereiten.sh events ~/Desktop/clip1.mov ~/Desktop/clip2.mov
# Ergebnis: Ordner „fertig“ mit event_<seite>_1.mp4, _2.mp4 … – diese in WordPress Medien → Datei hinzufügen hochladen.
set -e
S="$1"; shift || true
case "$S" in hochzeit|geburtstag|firmenfeier|events) ;; *) echo "Erstes Argument: hochzeit | geburtstag | firmenfeier | events"; exit 1;; esac
[ $# -ge 1 ] || { echo "Bitte mindestens ein Video angeben."; exit 1; }
command -v ffmpeg >/dev/null || { echo "ffmpeg fehlt: brew install ffmpeg"; exit 1; }
mkdir -p fertig; N=0
A="-c:a aac -b:a 128k"; [ "$STUMM" = 1 ] && A="-an"
G=""
case "$GEMA" in 1|True|true|TRUE|ja)
  A="-an"
  for T in /System/Library/Fonts/Helvetica.ttc /System/Library/Fonts/Supplemental/Arial.ttf /Library/Fonts/Arial.ttf /usr/share/fonts/truetype/dejavu/DejaVuSans.ttf /usr/share/fonts/dejavu/DejaVuSans.ttf; do [ -f "$T" ] && FONT="$T" && break; done
  [ -n "$FONT" ] || { echo "Keine Schriftdatei gefunden – GEMA-Hinweis nicht möglich."; exit 1; }
  G=",drawtext=fontfile=$FONT:text='Ohne Ton aus GEMA-Gründen':fontcolor=white:fontsize=min(w\,h)/26:box=1:boxcolor=black@0.55:boxborderw=min(w\,h)/90:x=w-tw-min(w\,h)/28:y=h-th-min(w\,h)/28";;
esac
for F in "$@"; do
  N=$((N+1)); [ $N -le 8 ] || { echo "Maximal 8 Videos."; break; }
  O="fertig/event_${S}_${N}.mp4"
  # max. 1080 Pixel Kantenlänge (iPhone-Drehung wird beachtet), H.264, kleine Datei, schneller Start im Browser
  ffmpeg -loglevel error -y -i "$F" $A -vf "scale='if(gt(iw,ih),min(1280,iw),min(720,iw))':-2$G" \
    -c:v libx264 -preset slow -crf 27 -pix_fmt yuv420p -movflags +faststart "$O"
  echo "$O  ($(du -h "$O" | cut -f1))"
done
