# DJ KOLJA ONE – dj-kolja-one.de

Quellcode, Plugin und Medien der Website.

| Ordner | Inhalt |
|---|---|
| `plugin/dj-kolja-one-plattenkiste/` | WordPress-Plugin (PHP + assets) |
| `musik/`, `deck/`, `formcss/`, `media/`, `logo/` | Quellen der Pulte, Formular-CSS, Medien, Logos |
| `site/` | Generator der 20 Elementor-Seiten (aus Repo-Root starten: `python3 site/export.py`) |
| `release/make_release.sh` | baut ZIP + `plattenkiste.json` |
| `wp-content/` | (optional) Mediathek/Theme/weitere Plugins, siehe `docs/WORDPRESS-SYNC.md` |
| `docs/` | Anleitungen, `MEDIEN.md` (Medienliste) |
| `tools/` | Hilfsskripte (Medienliste) |

Plugin-Updates: `plattenkiste.json` + ZIP im Root von `main` → WordPress aktualisiert automatisch.
Deployment/Backup/Secrets: `docs/WORDPRESS-SYNC.md`.

**Das Repo ist öffentlich – keine Passwörter, `wp-config.php`, Datenbank-Dumps oder Premium-Plugins einchecken.**
