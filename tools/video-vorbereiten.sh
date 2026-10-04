#!/bin/sh
# Handyvideos für die Website vorbereiten: stumm, verkleinert, komprimiert, fertig benannt.
# Voraussetzung (einmalig, Mac):  brew install ffmpeg
# Aufruf:  sh video-vorbereiten.sh <seite> <video1> [<video2> ... bis 8]
#   <seite> = hochzeit | geburtstag | firmenfeier | events
# Beispiel: sh video-vorbereiten.sh events ~/Desktop/clip1.mov ~/Desktop/clip2.mov
# Ergebnis: Ordner „fertig“ mit event_<seite>_1.mp4, _2.mp4 … – diese in WordPress Medien → Datei hinzufügen hochladen.
# Der Hinweis „Ohne Ton aus GEMA-Gründen“ wird von der Website selbst eingeblendet, nicht ins Video eingebrannt.
set -e
S="$1"; shift || true
case "$S" in hochzeit|geburtstag|firmenfeier|events) ;; *) echo "Erstes Argument: hochzeit | geburtstag | firmenfeier | events"; exit 1;; esac
[ $# -ge 1 ] || { echo "Bitte mindestens ein Video angeben."; exit 1; }
command -v ffmpeg >/dev/null || { echo "ffmpeg fehlt: brew install ffmpeg"; exit 1; }
mkdir -p fertig; N=0
for F in "$@"; do
  N=$((N+1)); [ $N -le 8 ] || { echo "Maximal 8 Videos."; break; }
  O="fertig/event_${S}_${N}.mp4"
  # an: stumm, max. 1080 Pixel Kantenlänge (iPhone-Drehung wird beachtet), H.264, kleine Datei, schneller Start im Browser
  ffmpeg -loglevel error -y -i "$F" -an -vf "scale='if(gt(iw,ih),min(1280,iw),min(720,iw))':-2" \
    -c:v libx264 -preset slow -crf 27 -pix_fmt yuv420p -movflags +faststart "$O"
  echo "$O  ($(du -h "$O" | cut -f1))"
done
