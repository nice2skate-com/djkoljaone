# Angebote aus Telegram / n8n (Plattenkiste seit 1.75.0)

Stand: 13.11.2026 – Version 1.75.0 wurde außerhalb dieses Repos gebaut und hier unverändert übernommen.

## Ablauf

1. Im Telegram-Bot werden die Angaben zum DJ-Angebot gesammelt (inkl. km, Änderungen).
2. Tippt Kolja auf **„Senden“**, ruft n8n die Plattenkiste auf:
   - `POST /wp-json/kjo/v1/auto/angebot` mit `{ref, data}` → legt das DJ-Angebot an (oder aktualisiert es bei gleicher `ref`, solange es noch nicht versendet ist). Nummer, Datum, Gültigkeit, Bedingungen und Schlusstext kommen aus Angebote → Einstellungen. Antwort: `id`, `nr`, `data`, `cfg` (Logo, Absender, Bank, Vertragstexte), `edit`.
   - Der **PDF-Dienst** baut daraus das PDF (`POST /render` bzw. `/render.json` mit `{data, cfg}`).
   - `POST /wp-json/kjo/v1/auto/angebot/{id}/pdf` mit `{pdf}` (Base64) → PDF wird im geschützten Ordner abgelegt, Antwort mit Kunden-Link und Dateiname.
   - n8n schickt die E-Mail mit PDF an den Kunden.
   - `POST /wp-json/kjo/v1/auto/angebot/{id}/versendet` mit `{to}` → Status „Versendet“, Verlauf, DJ-Anfrage mitgezogen.
   - `GET /wp-json/kjo/v1/auto/ping` → Verbindungstest (gibt die Plugin-Version zurück).
3. Das Angebot steht danach ganz normal unter **Angebote** (Nummer, PDF-Link, Verlauf, Öffnen-Hinweis). Eine DJ-Anfrage aus dem Website-Formular mit gleicher E-Mail (letzte 60 Tage) wird verknüpft und auf „Angebot“ gesetzt.

Felder, die n8n setzen darf: `titel, name, firma, strasse, plzort, email, tel, anlass, von, zeit, ort, gaeste, gruss, sie, intro, schluss, pos, rabatt, rabatt_txt, ust, ust_brutto`. Pflicht: `name` und mindestens eine Position.

## Zugang

- Schlüssel unter **Angebote → Automatik** erzeugen (wird nur einmal angezeigt, gespeichert wird nur der SHA-256-Fingerabdruck). Neuer Schlüssel macht den alten ungültig; „Schlüssel löschen“ schaltet die Automatik aus.
- In n8n als Zugangsdaten „Plattenkiste Automatik“, Kopfzeile `X-KJO-Key`. **Nie in den Chat oder per E-Mail.**
- Die Schnittstelle darf nur: Angebote anlegen/aktualisieren (nur über die Automatik entstandene), PDF ablegen, Status „Versendet“ setzen.

## PDF-Dienst (Server)

- Datei: `plugin/dj-kolja-one-plattenkiste/assets/pdf/kjo-pdf-server.js` (Node, ohne Abhängigkeiten).
- Lädt `jspdf.umd.min.js`, `fira400.js`, `fira600.js`, `angebot-pdf.js` von der Website (`ASSET_BASE`, Standard: Plugin-Ordner auf dj-kolja-one.de) und hält sie 10 Minuten im Speicher → ein Plugin-Update wirkt automatisch.
- Läuft nur im internen Docker-Netz (kein veröffentlichter Port). Umgebung: `PORT` (3000), `ASSET_BASE`, `ALLOW_HTTP` nur für Tests. `GET /health` → `ok`.
- Logo nur von derselben Domain wie `ASSET_BASE`.
- Das PDF entsteht mit genau demselben Code wie im Editor: `assets/pdf/angebot-pdf.js` (`KJO_ANG_PDF(A, K, logo)`), der Editor (`angebot.js`) nutzt ihn ebenfalls.

## Neu im Angebot (auch im Editor nutzbar)

- **Eigene Anrede** (`gruss`) und **Sie-Form** (`sie`).
- **Umsatzsteuer** (`ust`): leer = wie bisher „Gemäß § 19 UStG wird keine Umsatzsteuer berechnet“; `brutto` = Preise inkl. 19 % MwSt. (MwSt.-Anteil wird ausgewiesen); `netto` = Preise zzgl. 19 % MwSt., Gesamtbetrag brutto (`ust_brutto` überschreibt ihn bei Bedarf). Das PDF passt den § 19-Satz in den Vertragsbedingungen (§ 6) automatisch an.
- Achtung: Solange Kleinunternehmerregelung (§ 19 UStG) gilt, `ust` leer lassen – ausgewiesene Umsatzsteuer wird sonst nach § 14c UStG geschuldet.

## Getestet (13.11.2026)

- Editor im Browser: DJ-Angebot mit Vertragsbedingungen (4 Seiten) und Mietangebot mit Mietvertrag (2 Seiten) fehlerfrei.
- PDF-Dienst lokal: `/render` liefert gültiges PDF (4 Seiten), Sie-Form und „inkl. 19 % MwSt.“ inkl. angepasstem § 6.
