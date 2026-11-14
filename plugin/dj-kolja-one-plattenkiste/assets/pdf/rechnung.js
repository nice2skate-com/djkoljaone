/* DJ KOLJA ONE – Rechnungen im Angebot (seit 1.77.0). Kasten „Rechnungen“: anlegen, PDF erzeugen, senden, bezahlt markieren, stornieren.
   Das PDF baut assets/pdf/angebot-pdf.js (Zweig A.rechnung) – gleiche Optik wie das Angebot. */
(function () {
	'use strict';
	var K = window.KJO_ANG, box = document.getElementById('kjo-re');
	if (!K || !box) return;
	var msg = document.getElementById('kjo-re-msg'), aktiv = null;
	function meld(t, err) { if (msg) { msg.textContent = t; msg.style.color = err ? '#b32d2e' : ''; } }
	function $(id) { return document.getElementById(id); }
	function post(action, data) {
		var fd = new FormData();
		fd.append('action', action); fd.append('nonce', K.nonce); fd.append('id', K.id);
		Object.keys(data || {}).forEach(function (k) { fd.append(k, data[k]); });
		return fetch(K.ajax, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) {
			if (!j || !j.success) throw new Error((j && j.data && j.data.message) || 'Fehler – bitte Seite neu laden.');
			return j.data;
		});
	}
	function num(v) {
		v = String(v || '').replace(/[€\s]/g, '');
		if (v.indexOf(',') > -1) v = v.replace(/\./g, '').replace(',', '.');
		var n = parseFloat(v); return isNaN(n) ? 0 : Math.round(n * 100) / 100;
	}
	function eur(n) { return Number(n).toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €'; }
	/* PDF aus den Rechnungsdaten bauen und hochladen */
	function pdfHoch(re, daten) {
		meld('PDF wird erzeugt …');
		return (window.kjoAngLogo ? window.kjoAngLogo() : Promise.resolve(null)).then(function (logo) {
			var doc = window.KJO_ANG_PDF(daten, K, logo);
			return post('kjo_re_pdf', { re: re, pdf: doc.output('datauristring') });
		});
	}
	function neuLaden(t) { meld(t || 'Gespeichert – Seite wird aktualisiert …'); setTimeout(function () { window.location.hash = 'kjo_re_box'; window.location.reload(); }, 600); }
	function fehler(e) { meld(e.message, true); }

	/* Neue Rechnung */
	var neu = box.querySelector('.kjo-re-neu');
	if (neu) {
		var typ = function () { var r = neu.querySelector('input[name=kjo_re_typ]:checked'); return r ? r.value : ''; };
		var gesamt = function () { var A = window.kjoAngDaten ? window.kjoAngDaten() : {}, s = 0; (A.pos || []).forEach(function (p) { if (!p.o && p.p !== '') s += (+p.q || 0) * p.p; }); return Math.max(0, s - num(A.rabatt)); };
		var zeig = function () {
			neu.querySelector('.kjo-re-anz').hidden = typ() !== 'anzahlung';
			$('kjo-re-ges').textContent = 'Gesamtbetrag laut Angebot: ' + eur(gesamt());
		};
		neu.addEventListener('change', function (e) {
			if (e.target.name === 'kjo_re_typ') zeig();
			if (e.target.id === 'kjo-re-kunde') $('kjo-re-ziel').value = e.target.value === 'firma' ? neu.dataset.zielFirma : neu.dataset.zielPrivat;
			if (e.target.id === 'kjo-re-schon') $('kjo-re-schon-w').hidden = !e.target.checked;
		});
		neu.addEventListener('click', function (e) {
			var b = e.target.closest('[data-re-proz]');
			if (b) $('kjo-re-betrag').value = (Math.round(gesamt() * b.dataset.reProz) / 100).toFixed(2).replace('.', ',');
		});
		zeig();
		$('kjo-re-erstellen').addEventListener('click', function () {
			var t = typ(), b = this;
			if (!t) { meld('Bitte die Art der Rechnung wählen.', true); return; }
			if (t === 'anzahlung' && !num($('kjo-re-betrag').value)) { meld('Bitte den Betrag der Anzahlung eintragen.', true); return; }
			var schon = $('kjo-re-schon').checked;
			if (!confirm('Rechnung jetzt mit fortlaufender Nummer erstellen? Danach ist sie fest – Korrektur nur noch per Storno.')) return;
			b.disabled = true; meld('Rechnung wird angelegt …');
			var A = window.kjoAngDaten ? window.kjoAngDaten() : null;
			post('kjo_re_neu', {
				data: A ? JSON.stringify(A) : '', typ: t, datum: $('kjo-re-datum').value, ziel: $('kjo-re-ziel').value, leistung: $('kjo-re-leistung').value,
				betrag: t === 'anzahlung' ? $('kjo-re-betrag').value : '', bezahlt: schon ? $('kjo-re-schon-art').value : '', bezahlt_am: schon ? $('kjo-re-schon-am').value : ''
			}).then(function (r) { return pdfHoch(r.re, r.data); }).then(function () { neuLaden('Rechnung erstellt – Seite wird aktualisiert …'); })
				.catch(function (e) { b.disabled = false; fehler(e); });
		});
	}

	/* Aktionen je Rechnung */
	var mailBox = $('kjo-re-mail'), bezBox = $('kjo-re-bez');
	function zu() { if (mailBox) mailBox.hidden = true; if (bezBox) bezBox.hidden = true; }
	box.addEventListener('click', function (e) {
		if (e.target.closest('[data-re-zu]')) { zu(); return; }
		var b = e.target.closest('[data-re-a]');
		if (!b) return;
		var tr = b.closest('tr[data-re]'), re = tr.dataset.re, a = b.dataset.reA, M = JSON.parse(tr.dataset.mail || '{}');
		aktiv = { re: re, M: M, tr: tr };
		if (a === 'pdf') {
			b.disabled = true;
			post('kjo_re_daten', { re: re }).then(function (r) { return pdfHoch(re, r.data); }).then(function () { neuLaden('PDF erzeugt.'); }).catch(function (x) { b.disabled = false; fehler(x); });
		} else if (a === 'mail') {
			zu();
			$('kjo-re-mail-to').value = M.to || ''; $('kjo-re-mail-sub').value = M.sub || ''; $('kjo-re-mail-txt').value = M.txt || '';
			mailBox.hidden = false; $('kjo-re-mail-to').focus();
		} else if (a === 'wa') {
			if (!tr.querySelector('a.button')) { meld('Bitte zuerst das PDF erzeugen.', true); return; }
			var t = String(M.tel || '').replace(/[^\d+]/g, '');
			if (t.indexOf('+') === 0) t = t.slice(1); else if (t.indexOf('00') === 0) t = t.slice(2); else if (t.indexOf('0') === 0) t = '49' + t.slice(1);
			window.open('https://wa.me/' + (t.length > 6 ? t : '') + '?text=' + encodeURIComponent(M.wa || ''), '_blank', 'noopener');
			post('kjo_re_wa', { re: re }).then(function () { meld('WhatsApp geöffnet – Nachricht dort absenden.'); }).catch(fehler);
		} else if (a === 'bezahlt') {
			zu(); bezBox.hidden = false;
		} else if (a === 'offen') {
			if (!confirm('Zahlung zurücknehmen – Rechnung wieder offen?')) return;
			post('kjo_re_bezahlt', { re: re, zurueck: 1 }).then(function () { neuLaden(); }).catch(fehler);
		} else if (a === 'storno') {
			if (!confirm('Rechnung stornieren? Es entsteht eine Stornorechnung mit eigener Nummer, die Rechnung bleibt als „storniert“ erhalten.')) return;
			b.disabled = true; meld('Stornorechnung wird angelegt …');
			post('kjo_re_storno', { re: re }).then(function (r) { return pdfHoch(r.re, r.data); }).then(function () { neuLaden('Storniert – Seite wird aktualisiert …'); }).catch(function (x) { b.disabled = false; fehler(x); });
		}
	});
	if ($('kjo-re-mail-send')) $('kjo-re-mail-send').addEventListener('click', function () {
		var b = this, to = $('kjo-re-mail-to').value.trim();
		if (!aktiv) return;
		if (!/^\S+@\S+\.\S+$/.test(to)) { meld('Bitte eine gültige E-Mail-Adresse eintragen.', true); return; }
		if (!aktiv.tr.querySelector('a.button')) { meld('Bitte zuerst das PDF erzeugen.', true); return; }
		b.disabled = true; meld('E-Mail wird gesendet …');
		post('kjo_re_mail', { re: aktiv.re, to: to, sub: $('kjo-re-mail-sub').value, txt: $('kjo-re-mail-txt').value })
			.then(function (r) { neuLaden(r.message); }).catch(function (x) { b.disabled = false; fehler(x); });
	});
	if ($('kjo-re-bez-ok')) $('kjo-re-bez-ok').addEventListener('click', function () {
		if (!aktiv) return;
		this.disabled = true;
		post('kjo_re_bezahlt', { re: aktiv.re, am: $('kjo-re-bez-am').value, art: $('kjo-re-bez-art').value }).then(function () { neuLaden(); }).catch(fehler);
	});
})();
