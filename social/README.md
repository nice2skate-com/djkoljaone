# Reels auf Instagram und Facebook posten

Zwei Wege, beide direkt über die offizielle Schnittstelle von Meta (Graph-API), ohne Fremddienst:

- **GitHub-Ablauf „Social: Reel posten“** (`.github/workflows/social-reel.yml`, Skript `social/meta_reel.py`)
  – Claude kann ihn starten, oder du unter GitHub → Actions → „Social: Reel posten“ → *Run workflow*.
- **WordPress → Social** (Plugin-Datei `kjo-social.php`) – Video aus der Mediathek wählen, Text, „Jetzt veröffentlichen“.

Begleittexte liegen in `social/texte/`. Das Startseiten-Reel mit stiller Tonspur (Instagram lehnt Videos
ohne Tonspur teils ab): `media/motion/export/reel_dj-kolja-one_1080x1920_social.mp4`.

## Einmalige Einrichtung (ca. 30 Minuten)

### 1. Instagram vorbereiten
1. Instagram-App → Einstellungen → *Kontotyp und Tools* → auf **Business-Konto** (oder Creator) umstellen.
2. Instagram mit der **Facebook-Seite DJ KOLJA ONE** verknüpfen (Facebook-Seite → Einstellungen →
   *Verknüpfte Konten* → Instagram, oder in der Meta Business Suite).

### 2. Meta-App anlegen
1. <https://developers.facebook.com> → mit deinem Facebook-Konto anmelden → ggf. als Entwickler registrieren.
2. *Meine Apps* → **App erstellen**. Als Anwendungsfall „Andere“ bzw. den Typ **Business** wählen.
   Name z. B. „DJ KOLJA ONE Social“.
3. Die App bleibt im **Entwicklungsmodus**. Für deine eigenen Konten reicht das; eine Prüfung durch Meta ist nicht nötig,
   solange du selbst Administrator der App, der Seite und des Instagram-Kontos bist.

### 3. Dauerhaften Zugriffsschlüssel holen
1. <https://developers.facebook.com/tools/explorer> öffnen, oben rechts deine App wählen.
2. *User or Page* → **User Token**, unter *Permissions* hinzufügen:
   `pages_show_list`, `pages_read_engagement`, `pages_manage_posts`, `instagram_basic`,
   `instagram_content_publish`, `business_management`.
3. **Generate Access Token** → im Fenster die Seite DJ KOLJA ONE und das Instagram-Konto auswählen und erlauben.
4. Den Schlüssel verlängern: unten auf das blaue **i** → *Open in Access Token Tool* → **Extend Access Token**
   → den neuen (60 Tage gültigen) Schlüssel kopieren.
5. Zurück im Explorer: Schlüssel oben einfügen und abfragen:
   `me/accounts?fields=id,name,access_token,instagram_business_account`
6. In der Antwort bei *DJ KOLJA ONE*: **id** = Seiten-ID, **access_token** = Seiten-Zugriffsschlüssel.
   Dieser Seitenschlüssel läuft nicht ab (im Access Token Tool steht dann *Expires: Never*).

### 4. Eintragen – nicht in den Chat schicken
- **GitHub**: Repo → *Settings* → *Secrets and variables* → *Actions* → *New repository secret*:
  - `META_PAGE_ID` = Seiten-ID
  - `META_PAGE_TOKEN` = Seiten-Zugriffsschlüssel
- **WordPress**: Menü **Social** → Seiten-ID und Schlüssel eintragen → *Speichern* → **Verbindung prüfen**.

### 5. Testen
- GitHub: Ablauf mit `aktion = pruefen` starten – zeigt Seite und Instagram-Konto, veröffentlicht nichts.
- WordPress: „Verbindung prüfen“ zeigt *Verbunden: DJ KOLJA ONE · Instagram @…*.

## Eigene Musik darunterlegen, MOV- und Querformat-Videos

Dein Server kann Videos nicht schneiden. Das übernimmt der GitHub-Ablauf (`social/video_mischen.py`):
Video auf 1080×1920 bringen (Querformat mit schwarzen Rändern), H.264/AAC, eigene Musik mit Ein- und Ausblenden,
Originalton aus oder leise dazu. Ergebnis landet in `uploads/kjo-social/` und erscheint in WordPress → Social oben in
der Video-Liste als „Aufbereitet: …“. Veröffentlicht wird dabei nichts.

**Aus WordPress:** Social → „Video hochladen oder aus Mediathek wählen“ → „Musik hochladen oder aus Mediathek wählen“,
„Ab Sekunde im Song“ und Originalton einstellen → **In GitHub aufbereiten** → nach 2–4 Minuten Seite neu laden →
das aufbereitete Video wählen, über „ansehen“ prüfen → veröffentlichen.

Dafür braucht WordPress einmalig einen **GitHub-Schlüssel**:
1. GitHub → oben rechts dein Profilbild → *Settings* → ganz unten *Developer settings* →
   *Personal access tokens* → **Fine-grained tokens** → *Generate new token*.
2. Name „WordPress Social“, Ablauf z. B. 1 Jahr (danach neu erzeugen).
3. *Resource owner*: **nice2skate-com**, *Repository access*: **Only select repositories** → `djkoljaone`.
4. *Permissions* → *Repository permissions* → **Actions: Read and write** (sonst nichts).
5. *Generate token*, kopieren und in WordPress → Social → „GitHub-Schlüssel“ einfügen → Speichern.

**Direkt in GitHub:** Ablauf „Social: Reel posten“ mit `aktion = vorschau` (oder `posten`), `video` und `musik`
als Pfad im Repo oder https-Adresse aus der Mediathek.

Nur Musik verwenden, an der du die Rechte hast.

## Animation aus Claude Motion (HTML)

Social → **Claude-Motion-HTML hochladen** → (optional Musik, „Länge bei HTML“ leer = alle Szenen) → **In GitHub aufbereiten**.
GitHub öffnet die Animation in Chromium, rendert sie Bild für Bild hochkant (1080×1920, 30 fps, höchstens 90 s,
`social/motion_render.js`) und setzt Musik darunter. Die HTML-Datei liegt geschützt in `uploads/kjo-social-html/`.
Fehlende Schriften wie `fonts/fira400.woff2` kommen aus `media/motion/fonts/`; Bilder sollten in der HTML-Datei eingebettet sein.
Der Ton der Animation selbst wird nicht übernommen.

## Gut zu wissen
- Video: MP4 (H.264, AAC), hochkant 9:16, 3–90 Sekunden. Meta lädt das Video selbst von dj-kolja-one.de herunter.
- Musik aus der Instagram-Bibliothek lässt sich über die Schnittstelle nicht hinzufügen.
- Schlüssel ungültig (z. B. Passwort geändert, App gelöscht)? Schritte 3–4 wiederholen.
- Meta schaltet alte API-Versionen nach etwa zwei Jahren ab. Standard ist `v23.0`; in WordPress unter Social bzw.
  als Umgebungsvariable `META_API_VERSION` änderbar.

## Song-Reels (ein Reel pro eigenem Song)

30-Sekunden-Reels mit dem Song-Ende, Cover, Profilbild und QR-Code zum Song im DJ-Pult: siehe
`social/song-reels/README.md`. Fertige Begleittexte: `social/texte/song-reels.md`.

## Intro-Videos der Seiten (seit 1.65.0)

Kurze stumme Loops (12 s) im Kopfbereich der Leistungsseiten: Fotos mit Kamerafahrt, darüber animierter Gold-Titel,
am Ende Logo und Claim. Pro Seite zwei Fassungen: quer (1280×720) und 4:5 fürs Handy (864×1080).

- Inhalte: `social/intro/intros.json` (Fotos, Zeile oben, Textzeilen, Länge), Vorlage `social/intro/intro.html`
- Rendern: `node social/intro_render.js hochzeit BILDORDNER plugin/dj-kolja-one-plattenkiste/assets/intro`
- Clips statt Fotos: `{"clip": "event_events_3.mp4", "ab": 2, "unten": 0.14}` – 6 s ab Sekunde `ab`, unten 14 % abgeschnitten (GEMA-Hinweis). Fertig: hochzeit, geburtstag, firmenfeier, events
- Website: `intro_<seite>.mp4` / `intro_<seite>_hoch.mp4` aus `assets/intro/` – gleichnamige Dateien in der Mediathek
  haben Vorrang. Ohne Video bleibt der Kopfbereich wie bisher.
