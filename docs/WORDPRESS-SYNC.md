# Alles nach WordPress bringen – Einrichtung

## 1. Plugin (läuft bereits)
`sh release/make_release.sh "<h4>x.y.z</h4><ul><li>…</li></ul>"` (vorher Version an 2 Stellen erhöhen), ZIP + `plattenkiste.json` committen, nach `main` mergen. WordPress holt das Update selbst.

## 2. Code/Medien per SFTP-Deploy (GitHub Action `.github/workflows/deploy.yml`)
Einmalig im Repo unter *Settings → Secrets and variables → Actions*:
- Secrets: `SFTP_HOST`, `SFTP_USER`, `SFTP_PASSWORD`, `SFTP_WP_CONTENT` (Pfad zu `wp-content` beim Hoster)
- Variable: `DEPLOY_ENABLED` = `true`
(Strato: SFTP-Zugang im Kundenservicebereich anlegen. Empfehlung: eigener Benutzer nur für wp-content.)

Medien liegen dann in `wp-content/uploads/…` im Repo (nur Dateien, die öffentlich sein dürfen; große Videos besser per Git LFS).

## 2b. Zwischenspeicher nach dem Deploy
Der Deploy legt nach dem Hochladen eine Einmal-Token-Datei ab und ruft `/wp-json/kjm/v1/purge` auf. Das Plugin leert damit Elementor (wie „CSS & Daten neu generieren“) und gängige Cache-Plugins. Zusätzlich: Medien → Plattenkiste → „Zwischenspeicher leeren“. Kontrolle, ob alle Dateien ankamen: Actions → „Server-Check (Plugin-Dateien)“.

## 3. Elementor-Seiten
`python3 site/export.py` erzeugt Import-Dateien in `site/out/` (WordPress → Werkzeuge → Daten importieren).

## 4. Backup (nicht ins öffentliche Repo!)
Datenbank, Mediathek-Gesamtbestand, Theme, Premium-Plugins und `wp-config.php` gehören in ein **privates** Backup (z. B. UpdraftPlus → Cloud-Speicher, oder ein zweites privates GitHub-Repo).

## 5. Offen
- Test auf Strato und in Safari (bisher nur Chromium lokal)
- Testdateien `songs/preview-128.mp3`, `songs/cover-300.jpg` nur bei Bedarf für die Testseite lokal ablegen
