/* DJ KOLJA ONE – PDF-Dienst für Angebote (seit Plattenkiste 1.75.0).
 * Baut das Angebots-PDF mit genau dem Code der Website (assets/pdf/angebot-pdf.js, jsPDF, Fira Sans).
 * Die Dateien werden von der Website geladen und 10 Minuten zwischengespeichert – ein Plugin-Update wirkt also automatisch.
 * Nur im internen Docker-Netz erreichbar (kein veröffentlichter Port). POST /render {data, cfg} → application/pdf, POST /render.json → {pdf: Base64, bytes}. */
'use strict';
const http = require('http'), vm = require('vm');
const BASE = (process.env.ASSET_BASE || 'https://dj-kolja-one.de/wp-content/plugins/dj-kolja-one-plattenkiste/assets/pdf/').replace(/\/?$/, '/');
const HOST = new URL(BASE).host, TTL = 10 * 60 * 1000, PORT = +process.env.PORT || 3000;
const DATEIEN = ['jspdf.umd.min.js', 'fira400.js', 'fira600.js', 'angebot-pdf.js'];
let cache = null;

async function holen(url, binaer) {
	const r = await fetch(url + (url.includes('?') ? '&' : '?') + 't=' + Date.now(), { redirect: 'error' });
	if (!r.ok) throw new Error('Laden fehlgeschlagen (' + r.status + '): ' + url);
	return binaer ? Buffer.from(await r.arrayBuffer()) : r.text();
}
async function assets(logoUrl) {
	if (cache && Date.now() - cache.zeit < TTL && cache.logoUrl === logoUrl) return cache;
	const code = {};
	for (const f of DATEIEN) code[f] = await holen(BASE + f);
	let logo = null;
	if (logoUrl) {
		const u = new URL(logoUrl);
		if (u.host !== HOST || (u.protocol !== 'https:' && !process.env.ALLOW_HTTP)) throw new Error('Logo nur von ' + HOST);
		logo = 'data:image/png;base64,' + (await holen(u.href, true)).toString('base64');
	}
	cache = { zeit: Date.now(), logoUrl, code, logo };
	return cache;
}
function render(a, data, cfg) {
	const sb = { console, atob, btoa, TextEncoder, TextDecoder, Uint8Array, ArrayBuffer, setTimeout, clearTimeout };
	sb.window = sb; sb.self = sb; sb.navigator = { userAgent: 'Node' };
	vm.createContext(sb);
	for (const f of DATEIEN) vm.runInContext(a.code[f], sb, { filename: f, timeout: 20000 });
	if (typeof sb.KJO_ANG_PDF !== 'function' || !sb.jspdf) throw new Error('PDF-Code der Website nicht gefunden (Plugin ab 1.75.0 nötig).');
	const doc = sb.KJO_ANG_PDF(JSON.parse(JSON.stringify(data)), cfg, a.logo);
	return Buffer.from(doc.output('arraybuffer'));
}
http.createServer((req, res) => {
	const fehler = (code, msg) => { res.writeHead(code, { 'Content-Type': 'application/json' }); res.end(JSON.stringify({ fehler: msg })); };
	if (req.method === 'GET' && req.url === '/health') { res.writeHead(200); return res.end('ok'); }
	if (req.method !== 'POST' || (req.url !== '/render' && req.url !== '/render.json')) return fehler(404, 'nicht gefunden');
	let body = '';
	req.on('data', c => { body += c; if (body.length > 2e6) req.destroy(); });
	req.on('end', async () => {
		try {
			const j = JSON.parse(body || '{}');
			if (!j.data || !j.cfg || !j.cfg.absender) return fehler(400, 'data und cfg fehlen');
			const pdf = render(await assets(j.cfg.logo || ''), j.data, j.cfg);
			if (req.url === '/render.json') {
				res.writeHead(200, { 'Content-Type': 'application/json' });
				return res.end(JSON.stringify({ pdf: pdf.toString('base64'), bytes: pdf.length }));
			}
			res.writeHead(200, { 'Content-Type': 'application/pdf', 'Content-Length': pdf.length });
			res.end(pdf);
		} catch (e) { console.error(e); fehler(500, e.message); }
	});
}).listen(PORT, () => console.log('kjo-pdf läuft auf Port ' + PORT + ', Quelle ' + BASE));
