/* DJ KOLJA ONE – Angebots-PDF (seit 1.75.0 eigene Datei). Läuft im Angebots-Editor und im PDF-Dienst auf dem Server,
 * damit beide Wege dasselbe PDF erzeugen. Aufruf: KJO_ANG_PDF(A, K, logo) → jsPDF-Dokument. */
(function (G) {
	'use strict';
	G.KJO_ANG_PDF = function (A, K, logo) {
		A.pos = A.pos || [];
		function num(v) {
			if (v === '' || v == null) return '';
			if (typeof v === 'number') return v;
			v = String(v).replace(/[€\s]/g, '');
			if (v.indexOf(',') > -1) v = v.replace(/\./g, '').replace(',', '.');
			var n = parseFloat(v);
			return isNaN(n) ? '' : Math.round(n * 100) / 100;
		}
		function de(n, d) { return Number(n).toLocaleString('de-DE', { minimumFractionDigits: d == null ? 2 : d, maximumFractionDigits: 2 }); }
		function eur(n) { return n === '' || n == null ? '—' : de(n) + ' €'; }
		function qty(n) { return de(n, 0); }
		function isoD(d) { return d.toISOString().slice(0, 10); }
		function datum(s, wt) {
			if (!s) return '';
			var p = s.split('-'), d = new Date(Date.UTC(+p[0], p[1] - 1, +p[2]));
			return (wt ? ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'][d.getUTCDay()] + ', ' : '') + p[2] + '.' + p[1] + '.' + p[0];
		}
		/* Gleiche Regel wie kjo_vl_tarif() auf der Website: Wochenende Fr 15 – Mo 21 Uhr, sonst Tage. */
		function tarif(von, bis) {
			if (!von) return null;
			var a = new Date(von + 'T00:00:00Z'), b = new Date((bis || von) + 'T00:00:00Z');
			if (isNaN(a) || isNaN(b) || b < a) return null;
			var sat = {}, all = [], d, x;
			for (d = new Date(a); d <= b; d.setUTCDate(d.getUTCDate() + 1)) {
				var w = d.getUTCDay();
				all.push([new Date(d), w]);
				if (w === 6 || w === 0) { x = new Date(d); if (w === 0) x.setUTCDate(x.getUTCDate() - 1); sat[isoD(x)] = 1; }
			}
			var nw = Object.keys(sat).length;
			if (!nw) return { we: 0, d: Math.max(1, Math.round((b - a) / 864e5)) };
			var n = 0;
			all.forEach(function (e) {
				var w = e[1];
				if (w === 6 || w === 0) return;
				x = new Date(e[0]);
				if (w === 5) { x.setUTCDate(x.getUTCDate() + 1); if (sat[isoD(x)]) return; }
				if (w === 1) { x = new Date(e[0]); x.setUTCDate(x.getUTCDate() - 2); if (sat[isoD(x)]) return; }
				n++;
			});
			return { we: nw, d: n };
		}
		function tarifTxt(t) {
			var p = [];
			if (t.we) p.push(t.we + (t.we === 1 ? ' Wochenende' : ' Wochenenden'));
			if (t.d) p.push(t.d + (t.d === 1 ? ' Tag' : ' Tage'));
			return p.join(' + ');
		}
		function preisKat(c) {
			if (c.p == null) return '';
			if (A.typ !== 'miete') return c.p;
			var t = tarif(A.von, A.bis);
			return t ? t.we * (c.we == null ? c.p : c.we) + t.d * c.p : c.p;
		}
		function summen() {
			var s = 0, offen = 0;
			(A.pos || []).forEach(function (p) {
				if (p.o) return;
				if (p.p === '') { offen++; return; }
				s += (+p.q || 0) * p.p;
			});
			var r = num(A.rabatt) || 0;
			return { zw: Math.round(s * 100) / 100, rabatt: r, ges: Math.max(0, Math.round((s - r) * 100) / 100), offen: offen };
		}

		var QZ = [['miete', 'Miete erhalten'], ['kaution', 'Kaution erhalten'], ['zurueck', 'Kaution zurückgezahlt']];
		var C = { dunkel: [15, 12, 7], gold: [178, 157, 117], text: [30, 27, 22], grau: [111, 103, 90], linie: [226, 218, 203], hell: [244, 239, 230], weiss: [243, 241, 233] };
		function pdf(logo) {
			var J = G.jspdf.jsPDF, d = new J({ unit: 'mm', format: 'a4', compress: true }), S = K.absender;
			d.addFileToVFS('Fira-400.ttf', G.KJO_FIRA['400']); d.addFont('Fira-400.ttf', 'Fira', 'normal');
			d.addFileToVFS('Fira-600.ttf', G.KJO_FIRA['600']); d.addFont('Fira-600.ttf', 'Fira', 'bold');
			d.setProperties({ title: 'Angebot ' + A.nr + ' – DJ KOLJA ONE', author: 'DJ KOLJA ONE', creator: 'DJ KOLJA ONE' });
			var L = 18, R = 192, W = R - L, y = 0, m = A.typ === 'miete', seitenKopf = 'Angebot ' + A.nr;
			function f(w, size, col) { d.setFont('Fira', w ? 'bold' : 'normal'); d.setFontSize(size); d.setTextColor.apply(d, col || C.text); }
			function lh(size) { return size * 0.3528 * 1.38; }
			function seite() { // Folgeseite – Schrift und Farbe des laufenden Textes danach wiederherstellen (seit 1.76.2, sonst war Text nach dem Umbruch hell auf weiß)
				var fs = d.getFontSize(), fo = d.getFont(), tc = d.getTextColor();
				d.addPage();
				d.setFillColor.apply(d, C.dunkel); d.rect(0, 0, 210, 12, 'F');
				d.setFillColor.apply(d, C.gold); d.rect(0, 12, 210, 0.6, 'F');
				f(0, 8, C.weiss); d.text('DJ KOLJA ONE · ' + seitenKopf, L, 7.6);
				d.setFont(fo.fontName, fo.fontStyle); d.setFontSize(fs); d.setTextColor(tc);
				y = 22;
			}
			function platz(h) { if (y + h > 271) seite(); }
			/* Mietvertrag mit Quittung und Übergabeprotokoll (seit 1.73.0). § 1–3 aus dem Angebot, ab § 4 Text aus den Einstellungen. */
			function vertrag(s, fest) {
				var V = K.vertrag || {}, q = A.q || {};
				seitenKopf = 'Mietvertrag zum Angebot ' + A.nr;
				d.addPage();
				d.setFillColor.apply(d, C.dunkel); d.rect(0, 0, 210, 26, 'F');
				d.setFillColor.apply(d, C.gold); d.rect(0, 26, 210, 0.7, 'F');
				f(1, 8.5, C.gold); d.text('MIETVERTRAG', L, 12, { charSpace: 1.4 });
				f(0, 10.5, C.weiss); d.text('zum Angebot Nr. ' + A.nr + ' vom ' + datum(A.datum), L, 19.5);
				y = 37;
				function block(x, titel, zeilen) {
					f(1, 7.5, C.grau); d.text(titel, x, y, { charSpace: 0.8 });
					var yy = y + 5.5;
					zeilen.filter(Boolean).forEach(function (z, i) { f(i ? 0 : 1, i ? 9 : 10); d.splitTextToSize(String(z), 80).forEach(function (t) { d.text(t, x, yy); yy += 4.5; }); });
					return yy;
				}
				var y1 = block(L, 'VERMIETER', [S.marke, S.firma, S.strasse + ', ' + S.ort, 'Telefon ' + S.tel]);
				var y2 = block(110, 'MIETER', [A.name, A.firma, A.strasse, A.plzort, A.tel ? 'Telefon ' + A.tel : '', A.email]);
				y = Math.max(y1, y2) + 4;
				absatz('Grundlage ist das Angebot Nr. ' + A.nr + ' vom ' + datum(A.datum) + '. Mit der Unterschrift beider Seiten kommt der Mietvertrag zu den folgenden Bedingungen zustande.', 9.2);
				y += 3;
				var nr = 0;
				function par(titel, txt) { nr++; platz(16); f(1, 10, C.dunkel); d.text('§ ' + nr + ' ' + titel, L, y); y += 5; absatz(txt, 9.2); y += 3; }
				par('Mietgegenstand', fest.map(function (p) { return (p.e === 'pauschal' ? '' : qty(p.q) + (p.e ? ' ' + p.e : '') + '  ') + (p.t || ''); }).join('\n') + '\neinschließlich des im Angebot genannten Zubehörs.');
				par('Mietzeit und Übergabe', 'Mietbeginn: ' + (A.von ? datum(A.von, 1) : '__________') + '    Rückgabe: ' + (A.bis || A.von ? datum(A.bis || A.von, 1) : '__________') + '\nÜbergabe: ' + (A.uebergabe || '__________') + (A.ort ? '    Einsatzort: ' + A.ort : '') + '\nUhrzeiten für Übergabe und Rückgabe: ______________________');
				par('Miete und Kaution', 'Die Miete beträgt ' + eur(s.ges) + ' laut Angebot. Gemäß § 19 UStG wird keine Umsatzsteuer berechnet. Die Kaution beträgt ' + (num(A.kaution) ? eur(num(A.kaution)) : '__________ €') + '. Zahlungsweise und Fälligkeit richten sich nach den Bedingungen im Angebot.');
				if (V.storno) par('Stornierung', V.storno);
				String(V.text || '').split(/\n\s*\n/).forEach(function (b) {
					var z = b.replace(/^\s+|\s+$/g, '').split('\n');
					if (!z[0]) return;
					var t = z[0].match(/^§\s*\d+\s*(.*)$/);
					if (t) par(t[1], z.slice(1).join('\n')); else { absatz(z.join('\n'), 9.2); y += 3; }
				});
				// Quittung
				function linie(x1, x2, yy) { d.setDrawColor.apply(d, C.grau); d.setLineWidth(0.2); d.line(x1, yy, x2, yy); }
				y += 2; platz(40);
				f(1, 11, C.dunkel); d.text('Quittung', L, y); y += 6;
				var sp = [L, L + 50, L + 82, L + 108, L + 136];
				f(1, 7.5, C.grau); ['', 'Betrag', 'Datum', 'Art', 'Unterschrift Vermieter'].forEach(function (h, i) { d.text(h, sp[i], y); }); y += 6.5;
				QZ.forEach(function (z) {
					var r = q[z[0]] || {};
					f(1, 9.2); d.text(z[1], L, y);
					f(0, 9.2);
					if (r.b !== '' && r.b != null) d.text(eur(r.b), sp[1], y); else linie(sp[1], sp[2] - 4, y + 0.8);
					if (r.d) d.text(datum(r.d), sp[2], y); else linie(sp[2], sp[3] - 4, y + 0.8);
					if (r.a) d.text(r.a, sp[3], y); else linie(sp[3], sp[4] - 4, y + 0.8);
					linie(sp[4], R, y + 0.8);
					y += 8;
				});
				// Übergabeprotokoll
				y += 2; platz(52);
				f(1, 11, C.dunkel); d.text('Übergabeprotokoll', L, y); y += 6;
				function kaestchen(x, yy, t) { d.setDrawColor.apply(d, C.text); d.setLineWidth(0.25); d.rect(x, yy - 3, 3.2, 3.2); f(0, 9); d.text(t, x + 5, yy); }
				var y0 = y;
				[['Übergabe', ['vollständig und funktionsfähig übergeben', 'Einweisung erfolgt', 'Bedienungsanleitungen erhalten']], ['Rückgabe', ['vollständig zurückgegeben', 'ohne erkennbare Schäden', 'Kaution geprüft']]].forEach(function (sp2, i) {
					var x = i ? 110 : L, yy = y0;
					f(1, 9.2); d.text(sp2[0] + ' am ____________', x, yy); yy += 6;
					sp2[1].forEach(function (t) { kaestchen(x, yy, t); yy += 5.6; });
					f(0, 8.3, C.grau); d.text('Bemerkungen:', x, yy + 1); linie(x + 20, x + (i ? R - 110 : 86), yy + 1.6); linie(x, x + (i ? R - 110 : 86), yy + 8);
					y = Math.max(y, yy + 12);
				});
				// Unterschriften
				y += 6; platz(24);
				[['Ort, Datum', L, L + 50], ['Mieter', L + 58, L + 112], ['Vermieter (Kolja Tönges)', L + 120, R]].forEach(function (u) { linie(u[1], u[2], y + 10); f(0, 7.8, C.grau); d.text(u[0], u[1], y + 14); });
				y += 18;
			}
			/* Vertragsbedingungen für DJ-Angebote (seit 1.74.0): Text aus den Einstellungen, „§ n Titel“ = Überschrift, „---“ = neue Seite. */
			function bedingungen() {
				seitenKopf = 'Vertragsbedingungen zum Angebot ' + A.nr;
				d.addPage();
				d.setFillColor.apply(d, C.dunkel); d.rect(0, 0, 210, 26, 'F');
				d.setFillColor.apply(d, C.gold); d.rect(0, 26, 210, 0.7, 'F');
				f(1, 8.5, C.gold); d.text('VERTRAGSBEDINGUNGEN', L, 12, { charSpace: 1.4 });
				f(0, 10.5, C.weiss); d.text('für DJ-, Musik- und Technikleistungen · zum Angebot Nr. ' + A.nr + ' vom ' + datum(A.datum), L, 19.5);
				y = 37;
				ustText(String((K.vertrag || {}).dj || '')).replace(/\r/g, '').replace(/^[ \t]*-{3,}[ \t]*$/gm, '\n---\n').split(/\n\s*\n/).forEach(function (b) {
					var z = b.replace(/^\s+|\s+$/g, '').split('\n');
					if (!z[0]) return;
					if (/^-{3,}$/.test(z[0])) { seite(); z = z.slice(1); if (!z.length) return; }
					var t = /^§\s*\d+/.test(z[0]);
					if (t) { platz(16); f(1, 10, C.dunkel); d.text(z[0], L, y); y += 5; z = z.slice(1); }
					z.forEach(function (l) { absatz(l, 9); y += 0.5; });
					y += 1.8;
				});
			}
			/* Mit Umsatzsteuer im Angebot (seit 1.75.0) passt der § 19-Satz in den Vertragsbedingungen nicht – dann der passende Satz. */
		function ustText(t) {
			if (A.ust !== 'brutto' && A.ust !== 'netto') return t;
			return t.replace(/Gemäß § 19 UStG wird keine Umsatzsteuer berechnet\./g, A.ust === 'netto' ? 'Alle Preise verstehen sich netto zuzüglich 19 % Umsatzsteuer.' : 'Alle Preise verstehen sich inklusive 19 % Umsatzsteuer.');
		}
		function absatz(txt, size, col, w) {
				f(w, size, col);
				d.splitTextToSize(String(txt || ''), W).forEach(function (z) {
					platz(lh(size)); d.text(z, L, y);
					// Internet-Adressen im Text anklickbar machen (seit 1.76.2), Satzzeichen am Ende gehören nicht zum Link
					var re = /https?:\/\/[^\s<>"]+/g, m;
					while ((m = re.exec(z))) {
						var u = m[0].replace(/[.,;:!?)»“"]+$/, '');
						d.link(L + d.getTextWidth(z.slice(0, m.index)), y - size * 0.3528, d.getTextWidth(u), size * 0.3528 * 1.25, { url: u });
					}
					y += lh(size);
				});
			}
			// Kopf
			d.setFillColor.apply(d, C.dunkel); d.rect(0, 0, 210, 32, 'F');
			d.setFillColor.apply(d, C.gold); d.rect(0, 32, 210, 0.8, 'F');
			if (logo) { // Schriftzug als PNG in doppelter Auflösung, Claim als Vektorschrift: bleibt bei jeder Vergrößerung scharf (seit 1.72.3)
				d.addImage(logo, 'PNG', L, 10.5, 66.4, 8.5, 'logo', 'FAST');
				var cl = 'JEDES EVENT FINDET NUR EINMAL STATT.';
				f(0, 5.14, C.gold); d.text(cl, L + 10.75, 21.9, { charSpace: (44.85 - d.getTextWidth(cl)) / (cl.length - 1) });
				d.setDrawColor.apply(d, C.gold); d.setLineWidth(0.15);
				d.line(L + 0.55, 21.2, L + 9.2, 21.2); d.line(L + 57.15, 21.2, L + 65.8, 21.2);
			}
			else { f(1, 18, C.weiss); d.text('DJ KOLJA ONE', L, 19); }
			f(1, 8.5, C.gold);
			var an = 'ANGEBOT', cs = 1.4; d.text(an, R - d.getTextWidth(an) - cs * (an.length - 1), 13.5, { charSpace: cs });
			f(0, 10.5, C.weiss); d.text('Nr. ' + A.nr, R, 20, { align: 'right' });
			f(0, 8.5, [190, 182, 166]); d.text(datum(A.datum), R, 25, { align: 'right' });
			// Absender und Empfänger
			y = 44;
			f(0, 7, C.grau); d.text(S.marke + ' · ' + S.name + ' · ' + S.strasse + ' · ' + S.ort, L, y);
			d.setDrawColor.apply(d, C.linie); d.setLineWidth(0.2); d.line(L, y + 1.2, L + 85, y + 1.2);
			var ye = y + 7;
			[[A.name, 1], [A.firma, 0], [A.strasse, 0], [A.plzort, 0]].forEach(function (z) { if (!z[0]) return; f(z[1], z[1] ? 11 : 10); d.text(String(z[0]), L, ye); ye += 5.2; });
			// Eckdaten rechts
			var tf = tarif(A.von, A.bis), info = [['Angebot', A.nr], ['Datum', datum(A.datum)], ['Gültig bis', datum(A.gueltig)], ['Anlass', A.anlass]];
			if (m) info.push(['Mietzeitraum', A.von ? datum(A.von, 1) + (A.bis && A.bis !== A.von ? ' – ' + datum(A.bis, 1) : '') : ''], ['Abrechnung', tf ? tarifTxt(tf) : ''], ['Übergabe', A.uebergabe]);
			else info.push(['Termin', datum(A.von, 1)], ['Uhrzeit', A.zeit]);
			info.push([m ? 'Einsatzort' : 'Location', A.ort], ['Gäste', A.gaeste]);
			var yi = 43, xl = 124, xv = 148;
			info.forEach(function (z) {
				if (!z[1]) return;
				f(0, 7.5, C.grau); d.text(z[0], xl, yi);
				f(0, 8.8); var l = d.splitTextToSize(String(z[1]), R - xv); d.text(l, xv, yi); yi += Math.max(1, l.length) * 4.4 + 0.6;
			});
			y = Math.max(ye, yi) + 8;
			// Überschrift und Einleitung
			f(1, 17, C.dunkel); d.splitTextToSize(A.titel || 'Angebot', W).forEach(function (z) { d.text(z, L, y); y += 7.2; });
			d.setFillColor.apply(d, C.gold); d.rect(L, y - 3.4, 18, 0.7, 'F'); y += 4;
			absatz(A.gruss || 'Hallo ' + (A.name || '') + ',', 10); // eigene Anrede seit 1.75.0 (z. B. „Guten Tag …,“ aus Telegram/n8n)
			y += 1;
			absatz(A.intro, 10);
			y += 5;
			// Tabelle
			var xQ = 136, xE = 164, xG = R;
			function kopf(t) {
				platz(14);
				d.setFillColor.apply(d, C.hell); d.rect(L, y - 4.6, W, 7, 'F');
				f(1, 7.8, C.grau); d.text(t, L + 2, y); d.text('Menge', xQ, y, { align: 'right' }); d.text('Einzelpreis', xE, y, { align: 'right' }); d.text('Gesamt', xG - 2, y, { align: 'right' });
				y += 7;
			}
			function zeile(p, n) {
				f(1, 9.8); var tl = d.splitTextToSize((n ? n + '.  ' : '') + (p.t || ''), xQ - 22 - L - 4);
				f(0, 8.3); var il = p.i ? d.splitTextToSize(p.i, xQ - 22 - L - 4) : [];
				var h = tl.length * lh(9.8) + il.length * lh(8.3) + 3;
				platz(h);
				var y0 = y;
				f(1, 9.8); tl.forEach(function (z) { d.text(z, L + 2, y); y += lh(9.8); });
				f(0, 8.3, C.grau); il.forEach(function (z) { d.text(z, L + 2 + (n ? 5 : 0), y); y += lh(8.3); });
				f(0, 9.5);
				d.text(p.e === 'pauschal' && +p.q === 1 ? 'pauschal' : qty(p.q) + (p.e ? ' ' + p.e : ''), xQ, y0, { align: 'right' });
				d.text(eur(p.p), xE, y0, { align: 'right' });
				f(1, 9.5); d.text(p.p === '' ? '—' : eur((+p.q || 0) * p.p), xG - 2, y0, { align: 'right' });
				y += 1.2;
				d.setDrawColor.apply(d, C.linie); d.line(L, y - 1.8 + 1, R, y - 1.8 + 1);
				y += 3.6;
			}
			var fest = A.pos.filter(function (p) { return !p.o; }), opt = A.pos.filter(function (p) { return p.o; });
			kopf('Leistung');
			fest.forEach(function (p, i) { zeile(p, i + 1); });
			// Summen
			var s = summen();
			y += 1;
			platz(30);
			function sumZ(l, v, w) { f(w, 9.5, w ? C.text : C.grau); d.text(l, xE - 30, y); d.text(v, xG - 2, y, { align: 'right' }); y += 5.4; }
			if (s.rabatt) { sumZ('Zwischensumme', eur(s.zw)); sumZ(A.rabatt_txt ? 'Rabatt (' + A.rabatt_txt + ')' : 'Rabatt', '− ' + eur(s.rabatt)); }
			/* Umsatzsteuer (seit 1.75.0): leer = wie bisher § 19 UStG; „brutto“ = Preise inkl. 19 % MwSt.; „netto“ = Preise netto, MwSt. wird aufgeschlagen. */
			var ust = A.ust === 'brutto' || A.ust === 'netto' ? A.ust : '', brutto = s.ges, mwst = 0;
			if (ust === 'netto') {
				brutto = num(A.ust_brutto) || Math.round(s.ges * 119) / 100;
				mwst = Math.round((brutto - s.ges) * 100) / 100;
				sumZ('Summe netto', eur(s.ges)); sumZ('zzgl. 19 % MwSt.', eur(mwst));
			}
			if (ust === 'brutto') mwst = Math.round((s.ges - s.ges / 1.19) * 100) / 100;
			d.setFillColor.apply(d, C.dunkel); d.rect(xE - 33, y - 5, xG - xE + 33, 9, 'F');
			f(1, 10.5, C.gold); d.text('Gesamtbetrag', xE - 30, y + 0.9); f(1, 11.5, C.weiss); d.text(eur(brutto), xG - 2, y + 0.9, { align: 'right' });
			y += 9;
			f(0, 7.8, C.grau); d.text(ust === 'brutto' ? 'Im Gesamtbetrag sind 19 % MwSt. (' + eur(mwst) + ') enthalten.' : ust === 'netto' ? 'Alle Einzelpreise netto, zzgl. 19 % MwSt.' : 'Gemäß § 19 UStG wird keine Umsatzsteuer berechnet.', xG - 2, y, { align: 'right' }); y += 4.4;
			if (num(A.kaution)) { d.text('Kaution: ' + eur(num(A.kaution)) + ' – nicht im Gesamtbetrag enthalten', xG - 2, y, { align: 'right' }); y += 4.4; }
			y += 4;
			if (opt.length) {
				platz(20);
				f(1, 11, C.dunkel); d.text('Optional zubuchbar', L, y); y += 3;
				f(0, 8, C.grau); d.text('Nicht im Gesamtbetrag enthalten – sagen Sie einfach Bescheid, wenn Sie etwas davon möchten.', L, y + 2.5); y += 8; // seit 13.11.2026 immer Sie-Form
				kopf('Option');
				opt.forEach(function (p) { zeile(p, 0); });
				y += 2;
			}
			if (A.bed) { y += 2; platz(14); f(1, 11, C.dunkel); d.text('Bedingungen', L, y); y += 5.5; absatz(A.bed, 9.2); y += 3; }
			if (A.schluss) { y += 2; absatz(A.schluss, 10); }
			y += 5; platz(11);
			f(0, 10); d.text('Viele Grüße', L, y); y += 5.5;
			f(1, 10); d.text('Kolja · DJ KOLJA ONE', L, y);
			if (m && A.vertrag !== false) vertrag(s, fest);
			if (!m && A.vertrag !== false && (K.vertrag || {}).dj) bedingungen();
			// Fußzeile auf jeder Seite
			var n = d.getNumberOfPages();
			for (var i = 1; i <= n; i++) {
				d.setPage(i);
				d.setFillColor.apply(d, C.gold); d.rect(L, 276, W, 0.4, 'F');
				var y2 = 280.5;
				f(1, 7, C.text); d.text(S.marke, L, y2);
				f(0, 6.8, C.grau); d.text([S.firma, S.strasse + ', ' + S.ort].concat(S.ustid ? [S.ustid] : []), L, y2 + 3.4, { lineHeightFactor: 1.35 });
				d.text(['Telefon/WhatsApp ' + S.tel, S.mail, S.web], 98, y2, { lineHeightFactor: 1.35 });
				var bk = Object.keys(K.bank || {}).map(function (k) { return k + ': ' + K.bank[k]; });
				if (bk.length) d.text(bk, 146, y2, { lineHeightFactor: 1.35 });
				d.text('Seite ' + i + ' von ' + n, R, 293, { align: 'right' });
			}
			return d;
		}
		return pdf(logo);
	};
})(typeof window !== 'undefined' ? window : globalThis);
