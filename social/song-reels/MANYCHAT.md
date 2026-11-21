# Kommentar-Reel mit ManyChat: „Kommentiere SUMMER“ → Song per DM

Ablauf für Zuschauer: Reel sehen → **SUMMER** kommentieren → ManyChat antwortet öffentlich auf den Kommentar
und schickt eine Direktnachricht mit dem Button **Song herunterladen** → Google Drive öffnet die MP3.

Reel bauen: `python3 social/song-reels/kommentar.py 5169 --wort SUMMER` (siehe `README.md`).

Die Menüs bei Instagram, ManyChat und Google ändern sich gelegentlich. Wenn ein Punkt anders heißt,
nach dem sinngemäßen Eintrag suchen.

## 1. Song auf Google Drive freigeben

1. Google Drive öffnen → **Neu** → **Datei hochladen** → die MP3 in bester Qualität (z. B. 320 kbit/s) wählen.
   Dateiname so, wie Fans ihn sehen sollen: `DJ KOLJA ONE - Got the Summer Feeling.mp3`.
2. Rechtsklick auf die Datei → **Freigeben** → bei *Allgemeiner Zugriff* **Jeder mit dem Link** einstellen,
   Rolle **Betrachter** → **Link kopieren**.
3. Optional direkter Download statt Vorschau: aus dem Link
   `https://drive.google.com/file/d/DATEI-ID/view?usp=sharing` wird
   `https://drive.google.com/uc?export=download&id=DATEI-ID`.
   Am Handy ist der normale Freigabelink oft angenehmer (Song lässt sich vorher anhören, Download-Pfeil oben).
4. Link einmal im privaten Browserfenster testen: Er muss ohne Google-Anmeldung funktionieren.

## 2. Instagram für ManyChat vorbereiten (einmalig)

1. Instagram muss ein **Business- oder Creator-Konto** sein, verknüpft mit der Facebook-Seite DJ KOLJA ONE
   (siehe `social/README.md`).
2. Instagram-App → **Einstellungen** → **Nachrichten und Story-Antworten** → **Nachrichtensteuerung**
   → **Verbundene Tools** → **Zugriff auf Nachrichten erlauben** aktivieren. Ohne diesen Schalter darf ManyChat keine DMs schicken.
3. Auf manychat.com mit Facebook anmelden, **Instagram** als Kanal wählen und das Konto @djkoljaone verbinden.

## 3. Automation in ManyChat anlegen

1. **Automation** → **+ Neue Automation** → Vorlage **Auto-DM von Kommentaren** (bzw. „Comment → DM“) oder leer starten.
2. Auslöser (**Trigger**): *Nutzer kommentiert deinen Post oder Reel*
   - **Bestimmter Post oder Reel**: nach dem Posten das Summer-Reel auswählen
     (vor dem Posten geht auch *nächster Post oder Reel*).
   - **Schlüsselwort**: `SUMMER`, Regel *enthält*. Weitere Varianten ergänzen: `summer`, `Summer`, `SUMMER!`.
3. **Öffentliche Antwort auf den Kommentar** aktivieren, mit mehreren Varianten, damit es nicht nach Bot aussieht:
   - „Schau in deine Nachrichten ☀️“
   - „Ist unterwegs, check deine DMs 🎧“
   - „Gern! Der Song wartet in deinem Postfach.“
4. **Direktnachricht** (Text + Button):
   > Hey, danke fürs Kommentieren! ☀️ Hier ist „Got the Summer Feeling“ – komplett und gratis.
   > Viel Spaß damit, und sag mir gern, wie er dir gefällt.

   Button **Song herunterladen** → *URL öffnen* → Drive-Link aus Schritt 1.
5. Optional zweite Nachricht nach ca. 1 Minute:
   > Alle meine Songs und das DJ-Pult zum Selbermixen: https://dj-kolja-one.de/meine-musik/
6. Optional, je nach ManyChat-Tarif: „Erst folgen, dann Download“. Das bringt Follower, kostet aber einen Teil der Downloads.
7. Automation **aktivieren**.

## 4. Testen, bevor viele es sehen

- Mit einem zweiten Instagram-Konto (nicht dem eigenen) `SUMMER` unter das Reel schreiben.
- Prüfen: öffentliche Antwort erscheint, DM kommt an, Button öffnet die MP3 ohne Anmeldung.
- Kommt keine DM: Schalter aus Schritt 2.2 prüfen und ob die Automation **aktiv** ist.

## 5. Text zum Reel (Caption)

```
Gratis-Song für deinen Sommer ☀️
Kommentiere SUMMER und ich schick dir „Got the Summer Feeling“ komplett als Download per DM.

Eigene Produktion · Disco-Hits · 119 BPM
Alle Songs und mein DJ-Pult: Link in der Bio.

#djkoljaone #eigeneproduktion #gratissong #disco #nudisco #sommerhit #djmemmingen #allgäu
```

Tipp: Den ersten eigenen Kommentar mit „Kommentiere SUMMER für den Song 👇“ oben anheften.

## Gut zu wissen

- Instagram erlaubt Antworten per DM auf einen Kommentar nur innerhalb von 7 Tagen. ManyChat beachtet das automatisch.
- Wer per DM antwortet, landet als Kontakt in ManyChat. Diese Kontakte kannst du später für neue Songs anschreiben
  (im Rahmen der Instagram-Regeln, d. h. innerhalb von 24 Stunden nach ihrer letzten Nachricht).
- Datenschutz: In der Datenschutzerklärung von dj-kolja-one.de ManyChat als Dienst erwähnen, sobald du es nutzt.
