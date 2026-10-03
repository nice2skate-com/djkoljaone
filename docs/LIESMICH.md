# DJ KOLJA ONE – Quellcode (Stand 03.10.2026, Plugin 1.10.0)

Dieses Paket enthält alles, um das Plugin „DJ KOLJA ONE Plattenkiste“ weiterzuentwickeln und neue Versionen zu veröffentlichen.

## Ordner
- `plugin/dj-kolja-one-plattenkiste/` – das Plugin (PHP + assets). Version steht an zwei Stellen: Kopfzeile `Version:` und `define( 'KJO_VERSION', … )`.
- `musik/musik-snippet.html` – Quelle des Musik-Pults (#kjm). `build.py` erzeugt daraus `musik-snippet-final.html`; diese Datei wird nach `plugin/…/assets/musikpult.html` kopiert. Das Plugin ersetzt damit automatisch das HTML-Widget auf der Seite „Meine Musik“.
- `deck/deck-snippet.html` – DJ-Pult der Startseite (#kjo), steckt im HTML-Widget der Startseite.
- `media/` – Bild-/Video-Platzhalter (kjo-media), BPM-Erkennung (kjm-bpm.js).
- `site/` – Generator der 20 Elementor-Seiten (lib.py, pages.py, export.py, render_all.py).
- `release/make_release.sh` – baut ZIP + `plattenkiste.json` (Pfade im Skript ggf. anpassen).

## Neue Version veröffentlichen
1. Änderung machen, Version an beiden Stellen im Plugin erhöhen.
2. ZIP des Ordners `dj-kolja-one-plattenkiste` bauen, SHA-256 berechnen.
3. `plattenkiste.json` (version, download_url = Dateiname der ZIP, sha256, changelog) und die ZIP in die oberste Ebene des Repos `nice2skate-com/djkoljaone` (Branch `main`) legen.
4. WordPress liest `https://raw.githubusercontent.com/nice2skate-com/djkoljaone/main/plattenkiste.json` und aktualisiert automatisch (Auto-Updates sind aktiv, Prüfung ca. alle 6–12 Std.).

## Hinweise
- `build.py` erwartet für die Testseite einen Beispielsong unter `songs/preview-128.mp3` und ein Cover `songs/cover-300.jpg`; beides liegt bewusst NICHT in diesem öffentlichen Paket. Für die Plugin-Datei (`musik-snippet-final.html`) werden sie nicht gebraucht.
- Tests bisher: Chromium (Playwright) gegen lokales WordPress; nicht bei Strato, nicht in Safari.
- Offene Punkte und Entscheidungen: siehe Projektdokumente im Claude-Projekt „_DJ_Webseite“.
