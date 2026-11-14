# Rechnungen (Plattenkiste seit 1.77.0)

Stand: 14.11.2026. Keine Steuerberatung – vor dem ersten Einsatz einmal mit dem Steuerberater abstimmen.

## So funktioniert es

- **Wo:** Im Angebot unten der Kasten **„Rechnungen“**. Liste aller Rechnungen unter **Angebote → Rechnungen** (mit Summe offen/überfällig/bezahlt).
- **Arten:**
  - **Komplette Rechnung** – alle festen Positionen des Angebots (optionale nicht), Rabatt wie im Angebot.
  - **Anzahlungsrechnung** – Betrag in € oder per 25/30/50 %-Knopf; mehrere Anzahlungen möglich, zusammen nie über dem Gesamtbetrag.
  - **Schlussrechnung** – alle Positionen, darunter „abzüglich Anzahlung (Rechnung … vom …)“ je Anzahlungsrechnung, Restbetrag.
  - **Stornorechnung** – entsteht über „stornieren“: eigene Nummer, negativer Betrag, die alte Rechnung bleibt als „storniert“ erhalten.
- **Nummern:** `2026-137`, `2026-138` … fortlaufend und lückenlos, auch Stornos bekommen eine Nummer. Jedes neue Jahr startet bei 001 (`2027-001`). Startwert unter Angebote → Einstellungen → Rechnungen.
- **Zahlungsziel:** 15 Tage privat, 30 Tage geschäftlich. Geschäftlich = im Angebot ist eine Firma eingetragen; im Kasten umschaltbar, Tage änderbar.
- **Bereits bezahlt:** Haken „Bereits bezahlt“ (bar oder Überweisung, Datum) beim Erstellen → die Rechnung zeigt „Den Betrag … habe ich am … in bar erhalten. Vielen Dank!“ (dient als Quittung).
- **Später bezahlt:** Knopf „Bezahlt …“ → Datum und Art. Das ändert die Rechnung (PDF) nicht, nur den Status.
- **Versand:** E-Mail (PDF im Anhang, Kopie an dich) oder WhatsApp (Link zum PDF). Texte unter Angebote → Einstellungen → Rechnungen, Platzhalter `{name} {nr} {art} {betrag} {faellig} {link} {zahlung}`.
- **Status:**
  - Angebot: „Rechnung gestellt“ sobald eine Rechnung existiert, „Rechnung bezahlt“ wenn die komplette bzw. Schlussrechnung und alle Anzahlungen bezahlt sind.
  - DJ-Anfrage (Pipeline): ebenso „Rechnung gestellt“ / „Rechnung bezahlt“; Wiedervorlage = Fälligkeit der ältesten offenen Rechnung.
- **Hochzeit (ihr/euch, seit 1.80.0):** Steht im Angebot „Anrede: ihr / euch“ (automatisch, wenn der Anlass „Hochzeit“ enthält), laufen Rechnungstext, Zahlungshinweis, E-Mail und WhatsApp in ihr-Form. Texte unter Angebote → Einstellungen → „Hochzeit: Texte in ihr/euch-Form“.
- **Mietrechnung (seit 1.80.0):** Beim Erstellen einer Rechnung zu einem Mietangebot gibt es das Übergabe- und Rückgabeprotokoll: Datum, Häkchen (vollständig übergeben, Einweisung, Anleitungen, vollständig zurück, ohne Schäden, Kaution geprüft) und Bemerkungen – die Haken setzt du selbst, sie erscheinen angekreuzt im PDF. „Unterschriftszeilen drucken“ ist standardmäßig aus. Im Mietangebot selbst lassen sich die Unterschriftszeilen des Mietvertrags über „Unterschriftszeilen drucken“ abschalten.
- **Feste Kopie:** Eine Rechnung speichert die Angebotsdaten zum Zeitpunkt der Erstellung. Spätere Änderungen am Angebot ändern sie nicht. Korrektur nur per Storno + neue Rechnung, nie überschreiben oder löschen (Löschen ist gesperrt).
- **Ablage:** PDFs in `wp-content/uploads/kjo-rechnungen/` (von außen gesperrt), Kunden-Link über zufälligen Schlüssel. Bei „PDF neu“ bleibt das alte PDF liegen.

## Beratung (Kurzfassung)

1. **Pflichtangaben als Kleinunternehmer** (§ 14 UStG, § 34a UStDV) sind im PDF enthalten: Name und Anschrift von dir und dem Kunden, USt-IdNr. (Fußzeile), Rechnungsdatum, fortlaufende Nummer, Leistung und Leistungsdatum, Betrag, Hinweis „Gemäß § 19 UStG wird keine Umsatzsteuer berechnet“. → **Anschrift des Kunden** muss im Angebot vollständig sein (der Kasten warnt).
2. **Keine Umsatzsteuer ausweisen**, solange § 19 gilt – sonst wird sie nach § 14c UStG geschuldet. Das Feld „Umsatzsteuer“ im Angebot leer lassen.
3. **Anzahlung oder komplett?** Für Hochzeiten/Firmenfeiern mit Termin weit voraus: Anzahlungsrechnung bei Buchung (üblich 20–30 %), Schlussrechnung nach dem Event. Für kleine Aufträge oder Barzahlung am Abend: komplette Rechnung, ggf. „bereits bar bezahlt“.
4. **Barzahlung:** Immer mit Rechnung „bar erhalten“ dokumentieren (Kassenbeleg für die Buchhaltung).
5. **E-Rechnung:** Seit 2025 müssen Firmen E-Rechnungen empfangen können. Als Kleinunternehmer musst du selbst keine E-Rechnung (XRechnung/ZUGFeRD) ausstellen – ein PDF reicht.
6. **Aufbewahrung:** Rechnungen 8 Jahre (seit 2025). Die PDFs liegen auf dem Server – sie gehören in die Datensicherung (UpdraftPlus: Uploads mit sichern) und gehen zusätzlich an dich per Kopie (Bcc).
7. **Nummernkreis:** Jährlicher Neustart ist zulässig, weil die Jahreszahl die Nummer eindeutig macht. Nie eine Nummer doppelt vergeben, keine Lücken durch Löschen – deshalb Storno statt Löschen.
8. **Mahnung:** Überfällige Rechnungen stehen rot in der Liste und als Wiedervorlage in der Pipeline. Eine Zahlungserinnerung schickst du vorerst per E-Mail/WhatsApp selbst.

## Offen / Ideen

- CSV-Export der Rechnungen pro Jahr für den Steuerberater.
- Zahlungserinnerung (1. Mahnung) als Vorlage mit einem Klick.
- Rechnung auch aus Telegram/n8n anstoßen (Schnittstelle wie bei Angeboten).
