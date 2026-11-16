/* DJ KOLJA ONE – Auftragsbestätigung im Angebot (seit 1.85.0). Kasten „Auftragsbestätigung“: anlegen/neu fassen, PDF, E-Mail, WhatsApp.
   Das PDF baut assets/pdf/angebot-pdf.js (Zweig A.ab) – gleiche Optik wie Angebot und Rechnung. */
(function () {
	'use strict';
	var K = window.KJO_ANG, box = document.getElementById('kjo-ab');
	if (!K || !box) return;
	var msg = document.getElementById('kjo-ab-msg'), M = {};
	try { M = JSON.parse(box.dataset.mail || '{}'); } catch (e) { M = {}; }
	function $(id) { return document.getElementById(id); }
	function meld(t, err) { if (msg) { msg.textContent = t; msg.style.color = err ? '#b32d2e' : ''; } }
	function post(action, data) {
		var fd = new FormData();
		fd.append('action', action); fd.append('nonce', K.nonce); fd.append('id', K.id);
		Object.keys(data || {}).forEach(function (k) { fd.append(k, data[k]); });
		return fetch(K.ajax, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) {
			if (!j || !j.success) throw new Error((j && j.data && j.data.message) || 'Fehler – bitte Seite neu laden.');
			return j.data;
		});
	}
	function neuLaden(t) { meld(t || 'Gespeichert – Seite wird aktualisiert …'); setTimeout(function () { window.location.hash = 'kjo_ab_box'; window.location.reload(); }, 600); }
	if ($('kjo-ab-neu')) $('kjo-ab-neu').addEventListener('click', function () {
		var b = this, A = window.kjoAngDaten ? window.kjoAngDaten() : null;
		b.disabled = true; meld('Auftragsbestätigung wird angelegt …');
		post('kjo_ab_neu', { data: A ? JSON.stringify(A) : '', datum: $('kjo-ab-datum').value, text: $('kjo-ab-text').value, zahlung: $('kjo-ab-zahlung').value, schluss: $('kjo-ab-schluss').value })
			.then(function (r) {
				meld('PDF wird erzeugt …');
				return (window.kjoAngLogo ? window.kjoAngLogo() : Promise.resolve(null)).then(function (logo) {
					return post('kjo_ab_pdf', { pdf: window.KJO_ANG_PDF(r.data, K, logo).output('datauristring') });
				});
			})
			.then(function () { neuLaden('Auftragsbestätigung erstellt – Seite wird aktualisiert …'); })
			.catch(function (e) { b.disabled = false; meld(e.message, true); });
	});
	box.addEventListener('click', function (e) {
		var b = e.target.closest('[data-ab]');
		if (!b) return;
		var a = b.dataset.ab, mb = $('kjo-ab-mail');
		if (a === 'zu') { mb.hidden = true; return; }
		if (a === 'mail') {
			$('kjo-ab-mail-to').value = M.to || ''; $('kjo-ab-mail-sub').value = M.sub || ''; $('kjo-ab-mail-txt').value = M.txt || '';
			mb.hidden = false; $('kjo-ab-mail-to').focus();
		} else if (a === 'wa') {
			if (!box.querySelector('a.button')) { meld('Bitte zuerst das PDF erzeugen.', true); return; }
			var t = String(M.tel || '').replace(/[^\d+]/g, '');
			if (t.indexOf('+') === 0) t = t.slice(1); else if (t.indexOf('00') === 0) t = t.slice(2); else if (t.indexOf('0') === 0) t = '49' + t.slice(1);
			window.open(window.kjoWaUrl ? window.kjoWaUrl(t, M.wa || '') : 'https://wa.me/' + (t.length > 6 ? t : '') + '?text=' + encodeURIComponent(M.wa || ''), K.wa_kanal === 'app' ? '_blank' : 'kjo-whatsapp');
			post('kjo_ab_wa', {}).then(function () { meld('WhatsApp geöffnet – Nachricht dort absenden.'); }).catch(function (x) { meld(x.message, true); });
		}
	});
	if ($('kjo-ab-mail-send')) $('kjo-ab-mail-send').addEventListener('click', function () {
		var b = this, to = $('kjo-ab-mail-to').value.trim();
		if (!/^\S+@\S+\.\S+$/.test(to)) { meld('Bitte eine gültige E-Mail-Adresse eintragen.', true); return; }
		b.disabled = true; meld('E-Mail wird gesendet …');
		post('kjo_ab_mail', { to: to, sub: $('kjo-ab-mail-sub').value, txt: $('kjo-ab-mail-txt').value })
			.then(function (r) { neuLaden(r.message); }).catch(function (x) { b.disabled = false; meld(x.message, true); });
	});
})();
