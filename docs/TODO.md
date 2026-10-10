# Offene Punkte

## A. Google Ads – vor dem Start (geparkt)

**Pflicht**
- [ ] GA4: Stern bei `generate_lead`, `click_whatsapp`, `click_phone`, `click_email` (Admin → Data display → Events → „Recent events“). **Keinen** Stern bei `contact_link_click`, `click`, `form_start`, `submit_lead_form` (doppelte Zählung).
- [ ] Google-Ads-Konto anlegen (Expertenmodus, keine Smart-Kampagne; Rechnungsdaten Artificial Sentiments).
- [ ] GA4 ↔ Google Ads verknüpfen (GA4 Admin → Product links → Google Ads links); personalisierte Werbung aus, Auto-Tagging an.
- [ ] Conversions aus GA4 importieren: `generate_lead` Wert 100 €, die anderen 50 €, alle primär, Zählung „Eine“.
- [ ] **USt-IdNr.** ins Impressum und in die strukturierten Daten, sobald die Nummer vorliegt.
- [x] Cookie-Banner (Complianz), Consent Mode, Clarity blockiert, Datenschutz mit Google Ads.
- [x] Conversion-Events auf der Seite (live geprüft 09.10.2026).

**Empfohlen**
- [ ] Google Unternehmensprofil anlegen: Name nur „DJ KOLJA ONE“, Kategorie DJ-Service, ohne Ladengeschäft (Adresse verborgen), Einzugsgebiet = Ortsseiten, Website-Link mit `?utm_source=google&utm_medium=organic&utm_campaign=gbp`, Bestätigung (Video/Post/Telefon).
- [ ] Profil-Beschreibung (750 Zeichen) und Leistungstexte – Claude schreibt Entwurf.
- [ ] Profil-Link in Webseite und Schema (`sameAs`) eintragen.
- [ ] 3–5 echte Google-Bewertungen sammeln (WhatsApp-Vorlage von Claude); danach Bewertungen auf der Seite durch echte ersetzen.
- [ ] Kampagnenplan „Hochzeits-DJ“ (Keywords, ausschließende Keywords, Anzeigentexte, Region, 10–15 €/Tag für 4 Wochen) – Claude schreibt Entwurf.

**Nach dem Start**
- [ ] Nach 1–2 Tagen: Conversion-Status „Aktiv“ prüfen.
- [ ] Wöchentlich Suchbegriffe prüfen und unpassende ausschließen.

## B. Equipment-Seite & Verleih (neu)

**Entscheidungen (Kolja)**
- [x] Shop-Variante wählen: Mietanfrage-Formular / WooCommerce + Mietplugin / externe Mietsoftware.
- [x] Equipmentliste geliefert (10.10.2026); PA = Pronomic C-215 MA (2×) + C-118SA (2×).
- [ ] Offene Modelle: Bose-Sub, Funkmikrofon, Moving Heads, KLS, ALGAM, UV, Wash, Nebel, Laufschrift; PAR-Abgleich 4 vs. 6.
- [ ] Was davon wird vermietet, was nur bei DJ-Buchungen eingesetzt?
- [ ] Übergabe: nur Abholung in Fellheim / Lieferung / Lieferung + Aufbau (jeweils Preis bzw. km-Pauschale).
- [ ] Zielgruppe: Privat, Vereine, Firmen?
- [ ] Mietpreise je Gerät und Paket (1 Tag / Wochenende), Kaution je Paket.
- [ ] Zahlungsweg: Überweisung vorab, bar bei Abholung, PayPal?

**Versicherung & Recht (Kolja, mit Beratung)**
- [ ] Bestehende Equipment-/Elektronikversicherung prüfen: deckt sie **Vermietung an Dritte** und Diebstahl beim Mieter? Wenn nein: Angebot für Veranstaltungstechnik-Versicherung mit Vermietung einholen.
- [ ] Betriebshaftpflicht: Vermietung von Veranstaltungstechnik mitversichert (Personen-/Sachschäden durch die Technik)?
- [ ] Gewerbeanmeldung um „Vermietung von Veranstaltungstechnik“ ergänzen (falls nicht abgedeckt); Kleinunternehmergrenze im Blick behalten.
- [ ] Elektrische Prüfung (DGUV V3) der Mietgeräte durchführen lassen und dokumentieren.
- [ ] Mietvertrag, Mietbedingungen und Übergabeprotokoll (Entwurf Claude) anwaltlich prüfen lassen.
- [ ] Klären, ob bei Online-Vertragsschluss ein Widerrufsrecht für Verbraucher besteht; Empfehlung bis dahin: Vertrag bei Abholung vor Ort unterschreiben.

**Inhalte (Claude)**
- [ ] Datenblätter recherchieren: Maße, Gewicht, Leistung (W), max. Schalldruck (dB), Anschlüsse, Stromanschluss, empfohlene Gästezahl, Transport (passt in Kombi?).
- [ ] Produktbeschreibungen in eigenen Worten (keine Herstellertexte kopieren).
- [ ] Party-Pakete S / M / L (Gästezahl, Raumgröße, Inhalt, Preis, Kaution) zur Freigabe.
- [x] Seitentexte „Mein Equipment“ und „Technik mieten“ zur Freigabe.
- [ ] Vorlagen: Mietvertrag, Mietbedingungen, Übergabe-/Rückgabeprotokoll, Kurzanleitung je Paket.
- [ ] FAQ Verleih (Kaution, Schaden, Diebstahl, Stornierung, Lieferung, Strom, Lautstärke/Nachbarn).

**Bilder & Videos (Kolja)**
- [ ] Eigene Produktfotos: einheitlicher dunkler Hintergrund, Front/Rückseite/Anschlüsse, je Paket ein Aufbau-Foto; kurze Videos (Aufbau, Klang ohne GEMA-Musik).
- [ ] Dateinamen nach Schema `verleih_<geraet>_1.jpg`, `paket_s_1.jpg` usw. (Liste von Claude).
- [ ] Optional: Plugin für Mediathek-Ordner (z. B. FileBird Lite) – nur zur Übersicht, nicht nötig für die Seite.

**Technik (Claude)**
- [x] Unterseiten `equipment` und `technik-mieten` in `site/pages.py` anlegen, Navigation und interne Links ergänzen.
- [x] Verwaltung „Plattenkiste → Equipment“: Felder je Gerät inkl. **Bedienungsanleitung (DE)** (Link oder Mediathek-PDF, öffentlich) und interner Inventardaten.
- [x] Button „Link defekt?“ an der Anleitung: E-Mail an anfrage@dj-kolja-one.de (Gerät, Link, Seite), Spam-Schutz, Hinweis „gemeldet“ in der Verwaltung.
- [x] Spalte **Wetterfestigkeit** je Gerät (nur innen / trocken überdacht / spritzwassergeschützt / wetterfest), auch im Browser angezeigt.
- [x] Preise, Kaution, Wochenendmiete, Sorglos-Option nur anzeigen, wenn in der Verwaltung eingetragen; sonst Ersatztext ohne Preis.
- [x] Mietanfrage-Formular (Geräte/Paket, Datum von–bis, Abholung/Lieferung, Gästezahl) mit Ereignis `generate_lead` (Parameter `type: rental`).
- [ ] Schema: `Product`/`Offer` bzw. `Service` für Verleih; SEO-Titel „Musikanlage mieten Memmingen, Allgäu & Schwaben“.
- [ ] Datenschutz ergänzen, falls neue Dienste (Zahlungsanbieter, Mietsoftware) dazukommen.
