/* DJ KOLJA ONE – Angebots-Editor und PDF (seit 1.56.0). Daten: verstecktes Feld #kjo-ang-json, Einstellungen: KJO_ANG. */
(function () {
	'use strict';
	var K = window.KJO_ANG, root = document.getElementById('kjo-ang'), hid = document.getElementById('kjo-ang-json');
	if (!K || !root || !hid) return;
	var A = JSON.parse(hid.value || '{}'), hasPdf = !!K.pdf, dirty = !K.aktuell, link = K.link || '';
	var KAT = {};
	(K.katalog || []).forEach(function (c) { KAT[c.k] = c; });

	/* ---------- Hilfen ---------- */
	function $(s, c) { return (c || root).querySelector(s); }
	function esc(s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
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
	function sync() {
		hid.value = JSON.stringify(A);
		dirty = true;
		var s = summen(), el = document.getElementById('kjo-ang-sum');
		if (el) el.innerHTML = '<strong>Gesamt: ' + eur(s.ges) + '</strong>' + (s.offen ? '<br><span style="color:#b32d2e">' + s.offen + ' Position' + (s.offen > 1 ? 'en' : '') + ' ohne Preis</span>' : '');
		var t = $('.kjo-ang-tarif');
		if (t) { var tf = tarif(A.von, A.bis); t.textContent = A.typ === 'miete' && tf ? 'Mietzeitraum: ' + tarifTxt(tf) : ''; }
		root.querySelectorAll('tr[data-i]').forEach(function (tr) {
			var p = A.pos[+tr.dataset.i];
			tr.querySelector('.kjo-ang-ges').textContent = p.p === '' ? '—' : eur((+p.q || 0) * p.p) + (p.o ? ' (opt.)' : '');
			tr.classList.toggle('kjo-ang-leer', p.p === '');
		});
	}

	/* ---------- Formular ---------- */
	function feld(k, label, typ, cls) {
		return '<label class="kjo-ang-f ' + (cls || '') + '"><span>' + label + '</span><input data-k="' + k + '" type="' + (typ || 'text') + '" value="' + esc(A[k]) + '"></label>';
	}
	function area(k, label, rows) {
		return '<label class="kjo-ang-f kjo-ang-w"><span>' + label + '</span><textarea data-k="' + k + '" rows="' + (rows || 3) + '">' + esc(A[k]) + '</textarea></label>';
	}
	function render() {
		var m = A.typ === 'miete', opts = '<option value="">Aus Katalog hinzufügen …</option>', grp = { p: 'Pakete', g: 'Geräte' };
		['p', 'g'].forEach(function (pre) {
			var l = (K.katalog || []).filter(function (c) { return c.k.charAt(0) === pre; });
			if (!l.length) return;
			opts += '<optgroup label="' + grp[pre] + '">' + l.map(function (c) { return '<option value="' + c.k + '">' + esc(c.t) + (c.p == null ? '' : ' (' + de(c.p) + ' €/Tag)') + '</option>'; }).join('') + '</optgroup>';
		});
		root.innerHTML =
			'<div class="kjo-ang-g">' +
			'<label class="kjo-ang-f"><span>Art</span><select data-k="typ">' + Object.keys(K.typen).map(function (t) { return '<option value="' + t + '"' + (A.typ === t ? ' selected' : '') + '>' + K.typen[t] + '</option>'; }).join('') + '</select></label>' +
			feld('nr', 'Angebots-Nr.') + feld('datum', 'Datum', 'date') + feld('gueltig', 'Gültig bis', 'date') + '</div>' +
			'<div class="kjo-ang-g">' + feld('titel', 'Überschrift im PDF', 'text', 'kjo-ang-w') + '</div>' +
			'<h3>Kunde</h3><div class="kjo-ang-g">' + feld('name', 'Name *') + feld('firma', 'Firma (optional)') + feld('strasse', 'Straße') + feld('plzort', 'PLZ und Ort') + feld('email', 'E-Mail', 'email') + feld('tel', 'Telefon / WhatsApp', 'tel') + '</div>' +
			'<h3>' + (m ? 'Miete' : 'Event') + '</h3><div class="kjo-ang-g">' + feld('anlass', 'Anlass') + feld('von', m ? 'Abholung / Beginn' : 'Datum', 'date') + (m ? feld('bis', 'Rückgabe', 'date') : feld('zeit', 'Uhrzeit (z. B. 18:00 – 02:00)')) +
			feld('ort', m ? 'Einsatzort' : 'Ort / Location') + feld('gaeste', 'Gäste') + (m ? feld('uebergabe', 'Übergabe') : '') + '</div><p class="kjo-ang-tarif description"></p>' +
			'<h3>Einleitung</h3>' + area('intro', 'Steht im PDF nach „Hallo ' + esc(A.name || '…') + ',“', 3) +
			'<h3>Positionen</h3><table class="widefat kjo-ang-t"><thead><tr><th>Leistung</th><th>Menge</th><th>Einheit</th><th>Einzelpreis €</th><th>Gesamt</th><th title="Optional zubuchbar, zählt nicht zur Summe">Opt.</th><th></th></tr></thead><tbody>' +
			(A.pos || []).map(function (p, i) {
				return '<tr data-i="' + i + '"><td><input data-p="t" value="' + esc(p.t) + '" placeholder="Leistung"><textarea data-p="i" rows="1" placeholder="Beschreibung (optional)">' + esc(p.i) + '</textarea></td>' +
					'<td><input data-p="q" value="' + esc(String(p.q).replace('.', ',')) + '" inputmode="decimal" class="kjo-ang-n"></td><td><input data-p="e" value="' + esc(p.e) + '" class="kjo-ang-e"></td>' +
					'<td><input data-p="p" value="' + (p.p === '' ? '' : esc(String(p.p).replace('.', ','))) + '" inputmode="decimal" class="kjo-ang-n" placeholder="Preis"></td><td class="kjo-ang-ges"></td>' +
					'<td><input type="checkbox" data-p="o"' + (p.o ? ' checked' : '') + '></td><td class="kjo-ang-btns"><button type="button" class="button-link" data-a="up" aria-label="nach oben">↑</button><button type="button" class="button-link" data-a="down" aria-label="nach unten">↓</button><button type="button" class="button-link kjo-ang-del" data-a="del" aria-label="entfernen">✕</button></td></tr>';
			}).join('') +
			'</tbody></table><p class="kjo-ang-add"><button type="button" class="button" data-a="neu">+ Position</button> <select class="kjo-ang-kat">' + opts + '</select> ' +
			(m ? '<button type="button" class="button" data-a="preise" title="Katalogpreise für den Mietzeitraum neu berechnen">Mietpreise neu berechnen</button>' : '<button type="button" class="button" data-a="vorlage">+ DJ-Positionen aus Vorlage</button>') + '</p>' +
			(m && (K.lieferung || K.aufbau) ? '<p class="description">Deine Preisliste: ' + esc([K.lieferung, K.aufbau].filter(Boolean).join(' · ')) + '</p>' : '') +
			'<div class="kjo-ang-g">' + feld('rabatt', 'Rabatt in €') + feld('rabatt_txt', 'Rabatt-Text (z. B. Stammkunde)') + feld('kaution', 'Kaution in € (nicht in der Summe)') + '</div>' +
			'<h3>Bedingungen und Schluss</h3>' + area('bed', 'Bedingungen (Zahlung, Reservierung, Kaution …)', 4) + area('schluss', 'Schlusstext', 2);
		sync();
	}

	root.addEventListener('input', function (e) {
		var t = e.target, tr = t.closest('tr[data-i]');
		if (t.dataset.k) {
			A[t.dataset.k] = (t.dataset.k === 'rabatt' || t.dataset.k === 'kaution') ? num(t.value) : t.value;
		} else if (tr && t.dataset.p) {
			var p = A.pos[+tr.dataset.i], k = t.dataset.p;
			p[k] = k === 'o' ? t.checked : (k === 'p' ? num(t.value) : (k === 'q' ? (num(t.value) === '' ? 0 : num(t.value)) : t.value));
		}
		sync();
	});
	root.addEventListener('change', function (e) {
		var t = e.target;
		if (t.dataset.k === 'typ') {
			var alt = A.typ === 'miete' ? 'dj' : 'miete';
			A.typ = t.value;
			if (!A.intro || A.intro === K.texte['intro_' + alt]) A.intro = K.texte['intro_' + A.typ];
			if (!A.bed || A.bed === K.texte['bed_' + alt]) A.bed = K.texte['bed_' + A.typ];
			if (/^Angebot (DJ|Mietequipment)/.test(A.titel || '')) A.titel = A.typ === 'miete' ? 'Angebot Mietequipment' : 'Angebot DJ' + (A.anlass ? ' – ' + A.anlass : '');
			render();
		} else if (t.classList.contains('kjo-ang-kat') && t.value) {
			var c = KAT[t.value];
			A.pos.push({ t: c.t, i: c.i || '', q: 1, e: 'Stk.', p: preisKat(c), o: false, k: c.k });
			if (A.typ === 'miete' && c.ka != null) A.kaution = (num(A.kaution) || 0) + c.ka;
			render();
		} else if (t.dataset.p === 'o') {
			sync();
		} else if (t.dataset.k === 'von' || t.dataset.k === 'bis') {
			sync();
		}
	});
	root.addEventListener('click', function (e) {
		var b = e.target.closest('button[data-a]');
		if (!b) return;
		var tr = b.closest('tr[data-i]'), i = tr ? +tr.dataset.i : -1, a = b.dataset.a;
		if (a === 'neu') A.pos.push({ t: '', i: '', q: 1, e: '', p: '', o: false, k: '' });
		else if (a === 'del') {
			if (!confirm('Position entfernen?')) return;
			var w = A.pos.splice(i, 1)[0], c = w.k && KAT[w.k];
			if (A.typ === 'miete' && c && c.ka != null && num(A.kaution) !== '') A.kaution = Math.max(0, num(A.kaution) - c.ka * (+w.q || 0)) || '';
		}
		else if (a === 'up' && i > 0) A.pos.splice(i - 1, 0, A.pos.splice(i, 1)[0]);
		else if (a === 'down' && i < A.pos.length - 1) A.pos.splice(i + 1, 0, A.pos.splice(i, 1)[0]);
		else if (a === 'vorlage') A.pos = A.pos.concat(JSON.parse(JSON.stringify(K.vorlage || [])));
		else if (a === 'preise') {
			A.pos.forEach(function (p) { if (p.k && KAT[p.k]) p.p = preisKat(KAT[p.k]); });
		} else return;
		render();
		if (a === 'neu') { var l = root.querySelectorAll('tr[data-i] input[data-p=t]'); if (l.length) l[l.length - 1].focus(); }
	});

	/* ---------- PDF ---------- */
	var C = { dunkel: [15, 12, 7], gold: [178, 157, 117], text: [30, 27, 22], grau: [111, 103, 90], linie: [226, 218, 203], hell: [244, 239, 230], weiss: [243, 241, 233] };
	var logoData = null;
	function ladeLogo() {
		if (logoData) return Promise.resolve(logoData);
		return fetch(K.logo).then(function (r) { return r.blob(); }).then(function (b) {
			return new Promise(function (res) { var f = new FileReader(); f.onload = function () { logoData = f.result; res(logoData); }; f.readAsDataURL(b); });
		}).catch(function () { return null; });
	}
	function pdf(logo) {
		var J = window.jspdf.jsPDF, d = new J({ unit: 'mm', format: 'a4', compress: true }), S = K.absender;
		d.addFileToVFS('Fira-400.ttf', window.KJO_FIRA['400']); d.addFont('Fira-400.ttf', 'Fira', 'normal');
		d.addFileToVFS('Fira-600.ttf', window.KJO_FIRA['600']); d.addFont('Fira-600.ttf', 'Fira', 'bold');
		d.setProperties({ title: 'Angebot ' + A.nr + ' – DJ KOLJA ONE', author: 'DJ KOLJA ONE', creator: 'DJ KOLJA ONE' });
		var L = 18, R = 192, W = R - L, y = 0, m = A.typ === 'miete';
		function f(w, size, col) { d.setFont('Fira', w ? 'bold' : 'normal'); d.setFontSize(size); d.setTextColor.apply(d, col || C.text); }
		function lh(size) { return size * 0.3528 * 1.38; }
		function seite() { // Folgeseite
			d.addPage();
			d.setFillColor.apply(d, C.dunkel); d.rect(0, 0, 210, 12, 'F');
			d.setFillColor.apply(d, C.gold); d.rect(0, 12, 210, 0.6, 'F');
			f(0, 8, C.weiss); d.text('DJ KOLJA ONE · Angebot ' + A.nr, L, 7.6);
			y = 22;
		}
		function platz(h) { if (y + h > 271) seite(); }
		function absatz(txt, size, col, w) {
			f(w, size, col);
			d.splitTextToSize(String(txt || ''), W).forEach(function (z) { platz(lh(size)); d.text(z, L, y); y += lh(size); });
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
		absatz('Hallo ' + (A.name || '') + ',', 10);
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
		d.setFillColor.apply(d, C.dunkel); d.rect(xE - 33, y - 5, xG - xE + 33, 9, 'F');
		f(1, 10.5, C.gold); d.text('Gesamtbetrag', xE - 30, y + 0.9); f(1, 11.5, C.weiss); d.text(eur(s.ges), xG - 2, y + 0.9, { align: 'right' });
		y += 9;
		f(0, 7.8, C.grau); d.text('Gemäß § 19 UStG wird keine Umsatzsteuer berechnet.', xG - 2, y, { align: 'right' }); y += 4.4;
		if (num(A.kaution)) { d.text('Kaution: ' + eur(num(A.kaution)) + ' – nicht im Gesamtbetrag enthalten', xG - 2, y, { align: 'right' }); y += 4.4; }
		y += 4;
		if (opt.length) {
			platz(20);
			f(1, 11, C.dunkel); d.text('Optional zubuchbar', L, y); y += 3;
			f(0, 8, C.grau); d.text('Nicht im Gesamtbetrag enthalten – sagt einfach Bescheid, wenn ihr etwas davon möchtet.', L, y + 2.5); y += 8;
			kopf('Option');
			opt.forEach(function (p) { zeile(p, 0); });
			y += 2;
		}
		if (A.bed) { y += 2; platz(14); f(1, 11, C.dunkel); d.text('Bedingungen', L, y); y += 5.5; absatz(A.bed, 9.2); y += 3; }
		if (A.schluss) { y += 2; absatz(A.schluss, 10); }
		y += 5; platz(11);
		f(0, 10); d.text('Viele Grüße', L, y); y += 5.5;
		f(1, 10); d.text('Kolja · DJ KOLJA ONE', L, y);
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

	/* ---------- Speichern, Hochladen, Versand ---------- */
	var msg = document.getElementById('kjo-ang-msg');
	function meld(t, err) { if (msg) { msg.textContent = t; msg.style.color = err ? '#b32d2e' : ''; } }
	function post(action, data) {
		var fd = new FormData();
		fd.append('action', action); fd.append('nonce', K.nonce); fd.append('id', K.id);
		Object.keys(data || {}).forEach(function (k) { fd.append(k, data[k]); });
		return fetch(K.ajax, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) {
			if (!j || !j.success) throw new Error((j && j.data && j.data.message) || 'Fehler beim Speichern.');
			return j.data;
		});
	}
	function pruefen() {
		if (!A.name) return 'Bitte den Namen des Kunden eintragen.';
		if (!A.pos.length) return 'Bitte mindestens eine Position eintragen.';
		var s = summen();
		if (s.offen && !confirm(s.offen + ' Position(en) ohne Preis – trotzdem so ins PDF übernehmen (Preis erscheint als „—“)?')) return '';
		return null;
	}
	function erzeugen() {
		var p = pruefen();
		if (p !== null) { if (p) meld(p, true); return Promise.reject(new Error(p || 'abgebrochen')); }
		meld('PDF wird erzeugt …');
		return post('kjo_ang_save', { data: JSON.stringify(A) }).then(function (r) {
			A = r.data; hid.value = JSON.stringify(A);
			var nr = $('[data-k=nr]'); if (nr) nr.value = A.nr;
			return ladeLogo();
		}).then(function (logo) {
			return post('kjo_ang_pdf', { pdf: pdf(logo).output('datauristring') });
		}).then(function (r) {
			link = r.link; hasPdf = true; dirty = false;
			document.getElementById('kjo-ang-pdflink').innerHTML = '<a href="' + esc(link) + '" target="_blank" rel="noopener">PDF ansehen</a>';
			meld('PDF gespeichert.');
			return link;
		});
	}
	function aktuell() { return hasPdf && !dirty ? Promise.resolve(link) : erzeugen(); }
	function platzhalter(s) { return String(s || '').replace(/\{name\}/g, A.name || '').replace(/\{nr\}/g, A.nr || '').replace(/\{link\}/g, link || ''); }

	document.getElementById('kjo-ang-pdf').addEventListener('click', function () {
		erzeugen().then(function (l) { window.open(l, '_blank', 'noopener'); }).catch(function (e) { if (e.message !== 'abgebrochen') meld(e.message, true); });
	});
	document.getElementById('kjo-ang-mail-open').addEventListener('click', function () {
		var box = document.getElementById('kjo-ang-mail');
		box.hidden = !box.hidden;
		if (!box.hidden) {
			document.getElementById('kjo-ang-mail-to').value = A.email || '';
			document.getElementById('kjo-ang-mail-sub').value = platzhalter(K.mail.sub);
			document.getElementById('kjo-ang-mail-txt').value = K.mail.txt.replace(/\{name\}/g, A.name || '').replace(/\{nr\}/g, A.nr || '');
		}
	});
	document.getElementById('kjo-ang-mail-send').addEventListener('click', function () {
		var b = this, to = document.getElementById('kjo-ang-mail-to').value.trim();
		if (!/^\S+@\S+\.\S+$/.test(to)) { meld('Bitte eine gültige E-Mail-Adresse eintragen.', true); return; }
		b.disabled = true;
		aktuell().then(function () {
			meld('E-Mail wird gesendet …');
			return post('kjo_ang_mail', { to: to, sub: platzhalter(document.getElementById('kjo-ang-mail-sub').value), txt: platzhalter(document.getElementById('kjo-ang-mail-txt').value) });
		}).then(function (r) {
			meld(r.message); document.getElementById('kjo-ang-mail').hidden = true;
			var st = document.getElementById('kjo-ang-status'); if (st) st.value = 'versendet';
		}).catch(function (e) { if (e.message !== 'abgebrochen') meld(e.message, true); }).then(function () { b.disabled = false; });
	});
	document.getElementById('kjo-ang-wa').addEventListener('click', function () {
		var w = window.open('', '_blank'); // sofort öffnen, sonst blockiert der Browser das Fenster
		aktuell().then(function () {
			var t = String(A.tel || '').replace(/[^\d+]/g, '');
			if (t.indexOf('+') === 0) t = t.slice(1); else if (t.indexOf('00') === 0) t = t.slice(2); else if (t.indexOf('0') === 0) t = '49' + t.slice(1);
			var u = 'https://wa.me/' + (t.length > 6 ? t : '') + '?text=' + encodeURIComponent(platzhalter(K.mail.wa));
			if (w) w.location.href = u; else window.location.href = u;
			return post('kjo_ang_wa', {});
		}).then(function () {
			meld('WhatsApp geöffnet – Nachricht dort absenden. Status: versendet.');
			var st = document.getElementById('kjo-ang-status'); if (st) st.value = 'versendet';
		}).catch(function (e) { if (w) w.close(); if (e.message !== 'abgebrochen') meld(e.message, true); });
	});
	/* Beim normalen Speichern („Aktualisieren“) gehen die Daten über das versteckte Feld mit. */
	var form = document.getElementById('post');
	if (form) form.addEventListener('submit', function () { hid.value = JSON.stringify(A); });

	A.pos = A.pos || [];
	render();
	dirty = !K.aktuell;
	window.kjoAngPdf = function () { return ladeLogo().then(pdf); }; // für Tests
})();
