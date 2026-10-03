=== DJ KOLJA ONE Plattenkiste ===
Songs aus der Mediathek für das DJ-Pult auf der Seite „Meine Musik“.

1. MP3 hochladen (Medien → Datei hinzufügen).
2. In den Datei-Details „Plattenkiste: Genre“ ausfüllen (oder Genre im MP3 hinterlegen).
3. BPM und Tonart (Camelot, z. B. 8A) werden beim Hochladen automatisch erkannt, sobald die Mediathek im Browser geöffnet ist – Korrektur in den Datei-Details möglich. Optional: Labelfarbe, Reihenfolge. Beschriftung = kleine Info-Zeile unter dem Titel.
4. Video: MP4 mit gleichem Dateinamen hochladen (song.mp3 + song.mp4) – wird automatisch verknüpft.
5. Übersicht: Medien → Plattenkiste.

Seit 1.4.0: Navigation bleibt beim Scrollen oben stehen, Anrufen + WhatsApp als Icons rechts im Menü, Cookie-Banner im DJ-KOLJA-ONE-Design, Link „Cookie-Richtlinie“ in der Fußzeile.

Seit 1.4.1: Keine blauen Markierungen mehr beim Ziehen am Crossfader. Kein 128-kbps-Hinweis mehr: Songs laufen in der hochgeladenen Qualität.

Seit 1.5.0: Seiten ohne Elementor (z. B. Cookie-Richtlinie) erscheinen im DJ-KOLJA-ONE-Design mit gleicher Kopf- und Fußzeile.

Seit 1.12.4: Automix in Safari: Das Tempo gleitet nach dem Mix nur noch in wenigen groben Schritten (erst nach 10 s) zurück, statt zehnmal pro Sekunde; die Datei des nächsten Titels wird erst 4 s nach dem Mix geladen (die Anzeige wechselt sofort). Unter dem Automix gibt es einen Link „Diagnose“ mit einem Protokoll der Medien-Ereignisse (play, pause, waiting, error …) – zum Eingrenzen von Problemen.

Seit 1.12.3: Weiße Artefakte über dem Controller beseitigt: der bewegte Controller trägt keinen CSS-/SVG-Filter mehr (Schatten statisch, Leuchten über Farbe und Strichstärke). Der Abstand zwischen Text und Pult wird aus den Layout-Positionen gemessen, nicht aus animierten Bildschirmpositionen.

Seit 1.12.3 (Automix): Automix: Der nächste Titel erscheint sofort auf dem freien Deck (nicht erst kurz vor dem Mix). Safari/iPhone: Das zweite Deck wird beim Tippen auf „+“ bzw. „Automix starten“ für das automatische Abspielen freigeschaltet; blockiert der Browser den Start trotzdem, erscheint der Knopf „Song freigeben“ – ein Tipp genügt.

Seit 1.12.2: Automix steht jetzt rechts neben der Plattenkiste, die Titel untereinander (auf dem Handy als einklappbare Liste). Die Platten ziehen weniger weit heraus und überdecken die Genre-Überschriften nicht mehr. Der Abstand zwischen Einleitungstext und DJ-Pult wird gemessen und ausgeglichen. Nach dem Deploy leert die Seite ihren Zwischenspeicher automatisch.

Seit 1.12.2 (Zwischenspeicher): Nach jedem Update werden Elementor- und gängige Cache-Plugin-Zwischenspeicher einmalig geleert (sobald jemand das Dashboard öffnet), damit neue Pult-Versionen sofort erscheinen; Knopf „Zwischenspeicher leeren“ unter Medien → Plattenkiste. Die Lücke auf „Meine Musik“ wird jetzt unabhängig vom Seitenaufbau entfernt.

Seit 1.12.1: Klang und Laufruhe des Players verbessert: größerer Audio-Puffer (weniger Aussetzer), Limiter gegen Übersteuern beim Mixen, exakte Wiedergabegeschwindigkeit ohne Umrechnung, Filter in Neutralstellung ohne Höhenverlust; die Anzeige schreibt rund 94 % weniger Änderungen in die Seite und lässt dem Ton mehr Rechenzeit. Auf „Meine Musik“ ist die schwarze Lücke zwischen Einleitungstext und DJ-Pult entfernt.

Seit 1.12.0: Tempo-Regler an beiden Decks (±16 %), SYNC-Knopf (gleicht das Tempo an das andere Deck an, auch halbes/doppeltes Tempo) und KEY-Knopf (Key-Lock: Tonart bleibt beim Tempo-Ändern gleich). Automix: Mit dem + auf den Platten legst du Songs in eine Playlist; „Automix starten“ lädt sie nacheinander auf die Decks und mixt sie mit Tempo-Angleich, Überblendung und Bass-Tausch. Sobald du an Crossfader, Fadern, Tempo, Sync oder einer Platte eingreifst, übernimmst du manuell (die Decks laufen weiter); „Automix fortsetzen“ übergibt wieder. Auf der Seite „Meine Musik“ entfallen die beiden Hero-Buttons. Auf dem Handy erscheint beim Scrollen ein Menü-Knopf (Hamburger) für die Unterseiten.

Seit 1.11.0: Tonart-Erkennung (Camelot-System). Die Tonart wird beim Upload automatisch zusammen mit der BPM erkannt, am Deck und auf der Platte angezeigt; der Mixer zeigt, ob die beiden Songs harmonisch zusammenpassen (z. B. „8A + 9A · PASST“). In der Plattenkiste tragen die Platten Tonart und BPM, passende Songs sind grün/gelb markiert, der Knopf „Passt zu …“ blendet unpassende aus. Bestehende Songs werden einmalig automatisch nachanalysiert.
