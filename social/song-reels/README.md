# Song-Reels (Instagram, 30 s pro Song)

Pro Song ein Reel im Hochformat 1080×1920, 30 Sekunden, mit den **letzten 30 Sekunden des Songs bis zum echten Ende**.
Farben nur aus dem Brand-Guide (Bronze `#B29D75`, Gold `#E0B36A`, Fast-Schwarz `#0F0C07`, Creme `#F3F1E9`).

Ablauf im Reel:

| Zeit | Inhalt |
|---|---|
| 0–10 s | Cover, Genre, Titel, Spruch zum Song, BPM und Tonart |
| 10–19 s | Plattenteller mit Cover, Profilbild, „Ganzer Song im DJ-Pult auf meiner Website“ |
| 19–30 s | QR-Code zum Song, `dj-kolja-one.de/meine-musik`, „Link in der Bio“ |

Das Profilbild steht oben durchgehend. Lichteffekt je Genre (Glitzer, Lichtkegel, Neon, Lichtpunkte, Glühwürmchen),
Pegel und Leuchten folgen der echten Lautstärke des Songs. Songs ohne Cover zeigen das Profilbild.

Der QR-Code öffnet `https://dj-kolja-one.de/meine-musik/?song=<ID>&utm_source=instagram&utm_medium=reel&utm_campaign=song-<ID>`,
der Song liegt dann auf Deck A. In Google Analytics unter Quelle „instagram“ / Medium „reel“ auswertbar.

## Bauen

Braucht Python 3 mit numpy, ffmpeg, Node und Playwright (wie `media/motion/render.js`).

    python3 social/song-reels/prep.py          # alle Songs; oder nur bestimmte: prep.py 5337 5111
    python3 social/song-reels/build.py         # rendert alle vorbereiteten Songs, ca. 5 Min. pro Reel

`prep.py` holt Songliste, MP3 und Cover von dj-kolja-one.de (`/wp-json/kjm/v1/songs`) nach `arbeit/`.
Ergebnis: `social/song-reels/arbeit/out/reel_<titel>_<id>.mp4`. Der Ordner `arbeit/` ist nicht im Repo.
Mit `build.py --html-only <id>` entsteht nur die Vorschau-Seite `arbeit/pages/<id>.html` zum Ansehen im Browser.

## Anpassen

- **Spruch pro Song:** `TAG` in `prep.py` (Song-ID → Text). Neue Songs ohne Eintrag bekommen keinen Spruch.
- **Effekt pro Genre:** `FX` in `prep.py`.
- **Länge:** `LEN` in `prep.py` und die Szenenlängen (`data-dur`) in `reel.html` müssen zusammen 30 ergeben;
  die `30` im Aufruf von `render.js` in `build.py` mitändern.
- **Profilbild:** `profil.webp` ersetzen (quadratisch, ca. 480 px).

Instagram-Texte zu jedem Reel: `social/texte/song-reels.md`.

## Hinweise für Instagram

- Links in Reels sind nicht anklickbar. Die meisten Besucher kommen über den Link in der Bio:
  Instagram-App → Profil → **Profil bearbeiten** → **Links** → **Externen Link hinzufügen** → `https://dj-kolja-one.de/meine-musik/`.
  Das geht nur in der App am Handy, nicht im Browser.
- Der QR-Code hilft, wenn das Reel auf einem zweiten Gerät läuft (Laptop, Bildschirm, bei Auftritten).
- Nur Musik verwenden, an der du die Rechte hast (hier: eigene Produktionen).
