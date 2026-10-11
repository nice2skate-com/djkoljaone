# Backup-Strategie dj-kolja-one.de

Stand: 11.10.2026. Ziel: Die komplette Seite lässt sich nach jedem Fehler (Update, Hack, versehentliches Löschen, Strato-Ausfall) innerhalb weniger Stunden wiederherstellen.

## Was muss gesichert werden?

| Teil | Wo liegt es? | Schon gesichert? |
|---|---|---|
| Plugin „Plattenkiste“ (Pult, Verleih, Kalender, Formular, Design) | GitHub-Repo `nice2skate-com/djkoljaone` | ✅ automatisch (jede Änderung) |
| Seitenvorlagen und Texte (`site/`, `seiten/*.json`) | GitHub-Repo | ✅ automatisch |
| **Datenbank**: Geräte-Daten, Preise, Pakete, Kalender-Termine, DJ-Anfragen, Mietanfragen, Songs/Bewertungen, Einstellungen (Complianz, Site Kit, Elementor), Einwilligungs-Protokolle | Strato MySQL | ⚠️ nur Strato (14 Tage) |
| **Mediathek**: Fotos (Originale!), Videos, Musik, PDFs | Strato Webspace `wp-content/uploads` | ⚠️ nur Strato (14 Tage) |
| Andere Plugins und Theme | Strato Webspace | lassen sich neu installieren |
| GA4, Google Ads, Google-Profil, Clarity | bei Google bzw. Microsoft | liegt dort, kein Backup nötig |

## Die 3 Ebenen (3-2-1-Regel: 3 Kopien, 2 Orte, 1 außer Haus)

1. **Strato BackupControl** (ist schon da, kostet nichts)
   - Webspace: tägliche Sicherung, 14 Tage (Kundenlogin → Sicherheit → BackupControl)
   - Datenbank: tägliche Sicherung, 14 Tage (Kundenlogin → Datenbankverwaltung → Backups anzeigen)
   - Einmal prüfen, ob beides im Paket aktiv ist.
   - Grenze: Liegt beim selben Anbieter und reicht nur 14 Tage zurück. Wer einen Fehler erst nach 3 Wochen bemerkt, hat kein Backup mehr.

2. **UpdraftPlus (kostenlos) → Google Drive** (einrichten)
   - Plugins → Installieren → „UpdraftPlus“ → Einstellungen → Speicherort **Google Drive**
   - Zeitplan: **Datenbank täglich**, 14 Sicherungen behalten; **Dateien wöchentlich**, 8 Sicherungen behalten
   - Einschließen: Uploads, Plugins, Themes, Andere (Standard)
   - Ergebnis: rund 2 Monate Verlauf, außerhalb von Strato

3. **GitHub** (läuft schon)
   - Code und Seitenvorlagen mit komplettem Verlauf

## Feste Gewohnheiten

- **Vor jedem größeren Update** (WordPress, Elementor, Complianz) in UpdraftPlus auf „Jetzt sichern“ klicken.
- **Einmal im Quartal:** eine Sicherung testweise wiederherstellen. Am einfachsten auf einer Strato-Subdomain (z. B. `test.dj-kolja-one.de`) mit „UpdraftPlus → Wiederherstellen“. Ein Backup gilt erst als gut, wenn eine Wiederherstellung einmal geklappt hat.
- **Einmal im Jahr:** die neueste Sicherung aus Google Drive zusätzlich auf eine externe Festplatte laden.

## Zugänge absichern (gehört dazu)

- Zwei-Faktor-Anmeldung für das Strato-Konto, das Google-Konto und den WordPress-Admin (z. B. Plugin „Two Factor“).
- Diese Zugangsdaten an einem sicheren Ort aufbewahren (Passwort-Manager): Strato, WordPress-Admin, Google, GitHub, Domain.

## Notfall: Wiederherstellen

| Problem | Schnellster Weg |
|---|---|
| Seite nach einem Plugin-Update kaputt | Plugin per FTP/Strato umbenennen bzw. vorherige Version einspielen; Plattenkiste: ältere ZIP aus GitHub |
| Inhalt oder Gerät versehentlich gelöscht (unter 14 Tagen) | Strato BackupControl, nur Datenbank |
| Hack oder Totalausfall | UpdraftPlus-Sicherung aus Google Drive auf frischem WordPress wiederherstellen |
| Wechsel von Strato | UpdraftPlus-Sicherung beim neuen Hoster wiederherstellen, dann DNS umstellen |
