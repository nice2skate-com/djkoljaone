# Kommentar → WhatsApp (wie ManyChat) – seit 1.71.0

Wer unter einem Reel ein Stichwort kommentiert (z. B. **DJ**), bekommt automatisch:

1. eine öffentliche Antwort unter dem Kommentar („Check deine DMs 📩“),
2. eine **Instagram-DM** mit einem WhatsApp-Link, in dem „DJ“ schon eingetippt ist,
3. nach dem Absenden auf WhatsApp sofort eine **WhatsApp-Antwort** mit deinem Link und, wenn hinterlegt, die
   **MP3 oder PDF direkt als Datei im Chat** (z. B. aus Google Drive).

Alles läuft über die Website (Plugin-Datei `kjo-autodm.php`), ohne ManyChat oder einen anderen Fremddienst.
Einstellungen: WordPress → **Social → Kommentar → WhatsApp**.

> **Warum der Umweg über die DM?** Instagram gibt die Telefonnummern von Leuten, die kommentieren, nicht heraus.
> Man kann also niemandem direkt eine WhatsApp schicken: Die Person muss dir zuerst schreiben. ManyChat macht es genauso.

Du nutzt dafür die Meta-App, die du schon für **Social → Verbindung** eingerichtet hast (siehe `social/README.md`).
Dauer: etwa 1 Stunde, danach einige Tage Warten auf die Freigabe durch Meta (Schritt 7).

---

## 1. Instagram: Zugriff auf Nachrichten erlauben (2 Min.)
Instagram-App → Profil → ☰ → *Nachrichten und Story-Antworten* → *Nachrichtensteuerung* → *Verbundene Tools* →
**„Zugriff auf Nachrichten erlauben“** einschalten. Ohne diesen Schalter lehnt Instagram die DMs ab.

## 2. Seitenschlüssel um zwei Rechte erweitern (10 Min.)
Wie in `social/README.md`, Schritt 3, nur mit zwei zusätzlichen Rechten:

1. <https://developers.facebook.com/tools/explorer> → deine App → **User Token** → unter *Permissions* zusätzlich zu den bisherigen:
   - `instagram_manage_messages` (DMs schicken)
   - `pages_manage_metadata` (Website bei der Seite für Meldungen anmelden)
2. **Generate Access Token** → Seite DJ KOLJA ONE und Instagram auswählen → erlauben.
3. Verlängern (*Access Token Tool → Extend Access Token*) → im Explorer `me/accounts?fields=id,name,access_token` abfragen.
4. Den neuen **access_token** von DJ KOLJA ONE in WordPress → **Social → Verbindung** eintragen → Speichern → *Verbindung prüfen*.
   Für die GitHub-Abläufe auch das Secret `META_PAGE_TOKEN` aktualisieren.

## 3. App-Geheimcode in WordPress eintragen (2 Min.)
1. developers.facebook.com → deine App → **App-Einstellungen → Allgemein** → *App-Geheimcode* → *Anzeigen* → kopieren.
2. WordPress → **Social → Kommentar → WhatsApp** → *App-Geheimcode* einfügen → **Speichern**.
   (Damit prüft die Website, dass Meldungen wirklich von Meta kommen. Ohne ihn nimmt sie keine an.)

Oben auf dieser Seite stehen jetzt **Callback-URL** und **Verifizierungstoken**. Beides brauchst du in Schritt 4 und 5.

## 4. Instagram-Kommentare an die Website melden lassen (10 Min.)
1. In deiner App links: **Produkt hinzufügen → Webhooks** → *Einrichten* (falls noch nicht vorhanden).
2. Oben im Auswahlfeld **Instagram** wählen → **Dieses Objekt abonnieren**:
   - *Callback-URL* und *Verifizierungstoken* aus WordPress kopieren → **Bestätigen und speichern**
3. In der Feldliste bei **comments** auf **Abonnieren** klicken.
4. Zurück in WordPress → **Verbindung prüfen & Seite anmelden**. Erwartet:
   ✔ Instagram verbunden mit @… · ✔ Seiten-Abo · (WhatsApp folgt in Schritt 5)

## 5. WhatsApp einrichten (30 Min.)

**Telefonnummer:** Die WhatsApp Cloud API braucht eine Nummer, die **nicht** in der normalen WhatsApp-App läuft.
Deine Nummer auf der Website (0172 7273707) bleibt, wie sie ist. Für den Bot eignet sich z. B. eine günstige
Prepaid-SIM, die eine SMS empfangen kann.
*Alternative:* Meta bietet eine „Koexistenz“ an: eine Nummer aus der **WhatsApp-Business-App** zusätzlich mit der API
verbinden (beim Hinzufügen der Nummer „bestehende WhatsApp Business App verbinden“, per QR-Code). Ob das angeboten
wird, hängt vom Konto ab.

1. In deiner App: **Produkt hinzufügen → WhatsApp** → *Einrichten* → Business-Portfolio DJ KOLJA ONE wählen.
2. **Nummer hinzufügen:** <https://business.facebook.com/wa/manage/phone-numbers/> → *Telefonnummer hinzufügen* →
   Anzeigename „DJ KOLJA ONE“ → Nummer per SMS bestätigen. Den Anzeigenamen muss Meta kurz freigeben.
   Falls Meta danach fragt, eine Zahlungsmethode hinterlegen. Antworten auf Nachrichten, die dir jemand schreibt, sind kostenlos.
3. App → **WhatsApp → API-Einrichtung** → oben die neue Nummer wählen → **Telefonnummer-ID** kopieren.
4. **Dauerhafter Schlüssel:** <https://business.facebook.com/settings> → *Nutzer → Systemnutzer* → *Hinzufügen*
   (Name „website“, Rolle **Admin**) → *Assets zuweisen*: deine App und dein WhatsApp-Konto, jeweils **volle Kontrolle**
   → **Neuen Token generieren** → App wählen → Ablauf **Nie** → Rechte `whatsapp_business_messaging` und
   `whatsapp_business_management` → kopieren.
5. WordPress → **Social → Kommentar → WhatsApp**: *WhatsApp-Zugriffsschlüssel*, *Telefonnummer-ID* und
   *WhatsApp-Nummer des Bots* (z. B. `0170 1234567`, wird automatisch zu `49170…`) → **Speichern**.
6. App → **WhatsApp → Konfiguration → Webhook → Bearbeiten**: dieselbe *Callback-URL* und denselben
   *Verifizierungstoken* → *Bestätigen und speichern* → *Webhook-Felder verwalten* → **messages** abonnieren.
7. WordPress → **Verbindung prüfen & Seite anmelden** → jetzt auch ✔ WhatsApp verbunden.

## 6. Texte einstellen, testen, einschalten (10 Min.)
1. Unter **Stichwörter** die Texte anpassen.
   - **Datei (MP3/PDF) aus Google Drive:** In Google Drive die Datei → *Teilen* → *Allgemeiner Zugriff*:
     **„Jeder, der über den Link verfügt“** (Betrachter) → *Link kopieren* → in WordPress ins Feld *Datei* einfügen,
     daneben einen Dateinamen wie `Sommer-Mix-2026.mp3` eintragen. Der Link steht dann an der Stelle `{datei}` in der
     WhatsApp-Antwort, und die Datei kommt zusätzlich direkt in den Chat.
     Bis 100 MB klappt das Anhängen. Größere Dateien (bei Google ab etwa 100 MB wegen des Virenscan-Hinweises) kommen nur als Link.
   - Ohne Datei führt `{datei}` zu Meine Musik (mit UTM, in Google Analytics als Quelle „instagram“ / Medium „whatsapp“).
   - Platzhalter: `{name}`, `{datei}`, `{whatsapp}` (Link mit eingetipptem Stichwort), `{website}` (https://dj-kolja-one.de/)
   - Weiteres Stichwort, z. B. `MIX` oder `HOCHZEIT`: einfach im leeren Feld „Neues Stichwort“ anlegen → Speichern.
2. **Ausprobieren:** einen Beispiel-Kommentar eingeben → *Testen*. Zeigt die DM und die WhatsApp-Antwort, sendet nichts.
3. **Automatische Antworten einschalten** anhaken → Speichern.
4. Echter Test: Mit einem **zweiten** Instagram-Konto „DJ“ unter ein Reel schreiben. Eigene Kommentare werden absichtlich ignoriert.
   Solange die App im Entwicklungsmodus ist (Schritt 7), muss dieses Konto in der App eine Rolle haben:
   App → *App-Rollen → Rollen* → als **Tester** hinzufügen; die Person nimmt die Einladung auf developers.facebook.com an.
5. Unten auf der Seite erscheinen **Zahlen** (Kommentare, DMs, WhatsApp-Antworten) und der **Verlauf** mit Fehlermeldungen.

## 7. Freigabe durch Meta: damit es für alle funktioniert
Im Entwicklungsmodus reagiert der Bot nur auf Personen mit einer Rolle in der App. Für alle Follower:

1. **App-Einstellungen → Allgemein**: Datenschutzrichtlinie `https://dj-kolja-one.de/datenschutz/`,
   Datenlöschung: dieselbe Adresse, App-Symbol (1024×1024, z. B. Logo), Kategorie → Speichern.
   (Die Datenschutzerklärung hat seit 1.71.0 unter „6. WhatsApp“ einen Absatz zu den automatischen Antworten. Er wird
   beim ersten Öffnen des Dashboards nach dem Update automatisch eingespielt. Bitte einmal lesen.)
2. **Unternehmensverifizierung**: business.facebook.com → *Einstellungen → Sicherheitscenter* → *Verifizierung starten*
   (Gewerbe-Nachweis bzw. Dokument mit Name und Adresse; die USt-IdNr. hast du schon). Dauert einige Tage.
3. **App-Review → Berechtigungen und Funktionen** → **Erweiterten Zugriff** beantragen für
   `instagram_basic`, `instagram_manage_comments`, `instagram_manage_messages`, `pages_manage_metadata`,
   `pages_show_list`, `whatsapp_business_messaging`.
   Je Recht ein Satz, z. B.: *„Wenn jemand unter unseren Reels das Stichwort ‚DJ‘ kommentiert, antworten wir
   automatisch mit einer privaten Nachricht, die einen Link zu unserem WhatsApp enthält. Dort erhält die Person auf
   Nachfrage einen Link zu unseren Mixen auf dj-kolja-one.de.“* Dazu eine Handy-Bildschirmaufnahme:
   Kommentar → Antwort + DM → WhatsApp → automatische Antwort.
4. Nach der Freigabe: oben in der App **App-Modus → Live**. Social-Posten und Song-Kommentar laufen danach unverändert weiter.

Lehnt Meta ab, steht in der Begründung, was fehlt. Nachbessern und neu einreichen ist normal.

---

## Wenn etwas nicht klappt

| Problem | Lösung |
|---|---|
| Webhook lässt sich bei Meta nicht speichern | `https://dj-kolja-one.de/wp-json/kjo/v1/meta-webhook` im Browser öffnen: „forbidden“ ist richtig. Eine Fehlerseite deutet auf ein Sicherheits-Plugin, das die REST-API sperrt; dann `/wp-json/kjo/v1/` freigeben. Token genau kopieren. |
| Kommentar, aber nichts passiert, auch kein Verlauf | Schalter „einschalten“ an? App-Geheimcode richtig? *comments* abonniert, Seite angemeldet (Schritt 4)? Kommentar von einem Tester bzw. App live? |
| Verlauf: Instagram-DM ✘ „…permission…“ | Seitenschlüssel ohne `instagram_manage_messages`: Schritt 2 wiederholen. |
| Verlauf: Instagram-DM ✘ sonst | Schalter aus Schritt 1 an? Private Antworten gehen nur einmal pro Kommentar und nur 7 Tage lang. |
| WhatsApp: keine Antwort | *messages* abonniert? Steht im Verlauf ✘, steht dort der Grund. Die Nachricht muss das Stichwort enthalten. |
| Verlauf: WhatsApp-Datei ✘ / Datei kommt nicht an | Drive-Freigabe auf „Jeder, der über den Link verfügt“? Datei über 100 MB? Der Link in der Nachricht davor funktioniert trotzdem. |
| DM-Link öffnet deine normale Nummer | *WhatsApp-Nummer des Bots* ist leer. Dann gibt es auch keine automatische WhatsApp-Antwort. |

**Gut zu wissen**
- Dieselbe Person kann mehrmals kommentieren und bekommt dann mehrere DMs (Instagram erlaubt eine pro Kommentar).
- Ob jemand dir folgt, liefert die Schnittstelle bei Kommentaren nicht. Der Bot antwortet jedem mit Stichwort.
- Der Seitenschlüssel und der Systemnutzer-Schlüssel laufen nicht ab. Ändert sich dein Facebook-Passwort, Schritt 2 wiederholen.
- Zum Pausieren: Haken bei „einschalten“ entfernen.
