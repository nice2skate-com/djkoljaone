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

## Gut zu wissen
- Video: MP4 (H.264, AAC), hochkant 9:16, 3–90 Sekunden. Meta lädt das Video selbst von dj-kolja-one.de herunter.
- Musik aus der Instagram-Bibliothek lässt sich über die Schnittstelle nicht hinzufügen.
- Schlüssel ungültig (z. B. Passwort geändert, App gelöscht)? Schritte 3–4 wiederholen.
- Meta schaltet alte API-Versionen nach etwa zwei Jahren ab. Standard ist `v23.0`; in WordPress unter Social bzw.
  als Umgebungsvariable `META_API_VERSION` änderbar.
