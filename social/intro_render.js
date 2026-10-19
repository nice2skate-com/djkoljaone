// Intro-Video einer Seite rendern (seit 1.65.0): social/intro/intro.html Bild für Bild mit window.seek(t), dann ffmpeg.
//   node social/intro_render.js SEITE BILDORDNER AUSGANGSORDNER
// Einstellungen: social/intro/intros.json. Erzeugt intro_SEITE.mp4 (quer 1280×720) und intro_SEITE_hoch.mp4 (4:5, 864×1080)
// plus Standbilder .jpg. Schriften aus media/motion/fonts. Braucht Playwright (Chromium) und ffmpeg.
// Bild-Einträge sind Dateinamen im BILDORDNER oder Clips: {"clip": "event_events_3.mp4", "ab": 2, "unten": 0.14}
// („unten“ = Anteil, der unten abgeschnitten wird, z. B. der eingebrannte GEMA-Hinweis)
// oder freigestellte Produktfotos: {"bild": "bose_s1.webp", "groesse": "auto 62%", "schein": "70% 40%"} (Position über pos).
const {chromium} = require(require('child_process').execSync('npm root -g').toString().trim() + '/playwright');
const {execFileSync} = require('child_process');
const fs = require('fs'), path = require('path');
const [, , seite, bilder, aus] = process.argv;
const cfg = JSON.parse(fs.readFileSync(path.join(__dirname, 'intro', 'intros.json'), 'utf8'))[seite];
if (!cfg) { console.error('Unbekannte Seite: ' + seite); process.exit(2); }
const ROOT = path.join(__dirname, '..'), FPS = 30;
const CLIPS = path.join(aus, 'clips');
const FORMATE = [{suffix: '', w: 1280, h: 720, hoch: false}, {suffix: '_hoch', w: 864, h: 1080, hoch: true}];
(async () => {
  const b = await chromium.launch();
  fs.mkdirSync(aus, {recursive: true});
  fs.rmSync(CLIPS, {recursive: true, force: true});
  const bilderListe = cfg.images.map((e, k) => {
    if (typeof e === 'string') return 'img/' + e;
    if (e.bild) return {src: 'img/' + e.bild, size: e.groesse, size_hoch: e.groesse_hoch, glow: e.schein}; // freigestelltes Foto (transparent)
    const ziel = path.join(CLIPS, String(k)); fs.mkdirSync(ziel, {recursive: true});
    execFileSync('ffmpeg', ['-v', 'error', '-ss', String(e.ab || 0), '-i', path.join(bilder, e.clip), '-t', '6', '-an',
      '-vf', `crop=iw:ih*${1 - (e.unten || 0)}:0:0,fps=${FPS},scale='min(1280,iw)':-2`, '-q:v', '3', '-start_number', '0', path.join(ziel, '%05d.jpg')]);
    return {clip: 'clip/' + k + '/', n: fs.readdirSync(ziel).length};
  });
  for (const f of FORMATE) {
    const p = await b.newPage({viewport: {width: f.w, height: f.h}, deviceScaleFactor: 1});
    await p.route('http://m/**', r => {
      const rel = decodeURIComponent(new URL(r.request().url()).pathname.slice(1));
      const cand = rel.startsWith('clip/') ? path.join(CLIPS, rel.slice(5)) : rel.startsWith('img/') ? path.join(bilder, rel.slice(4)) : rel.startsWith('fonts/') ? path.join(ROOT, 'media/motion', rel) : path.join(__dirname, 'intro', rel);
      return fs.existsSync(cand) ? r.fulfill({path: cand}) : r.fulfill({status: 404, body: ''});
    });
    const conf = Object.assign({}, cfg, {hoch: f.hoch, images: bilderListe.map(e => e && e.src && f.hoch ? Object.assign({}, e, {size: e.size_hoch || e.size, glow: '50% 25%'}) : e), pos: f.hoch ? (cfg.pos_hoch || cfg.pos) : cfg.pos});
    await p.addInitScript(c => { window.INTRO = c; }, conf);
    await p.goto('http://m/intro.html', {waitUntil: 'load'});
    await p.evaluate(() => document.fonts.ready);
    await p.waitForTimeout(400);
    const tmp = path.join(aus, 'frames' + f.suffix); fs.rmSync(tmp, {recursive: true, force: true}); fs.mkdirSync(tmp);
    const N = Math.round(cfg.dur * FPS);
    for (let i = 0; i < N; i++) {
      await p.evaluate(t => window.seek(t), i / FPS);
      await p.screenshot({path: path.join(tmp, String(i).padStart(5, '0') + '.jpg'), type: 'jpeg', quality: 90});
    }
    const name = 'intro_' + seite + f.suffix;
    execFileSync('ffmpeg', ['-v', 'error', '-y', '-framerate', String(FPS), '-i', path.join(tmp, '%05d.jpg'), '-c:v', 'libx264', '-preset', 'slow', '-crf', '27',
      '-pix_fmt', 'yuv420p', '-profile:v', 'high', '-movflags', '+faststart', '-an', path.join(aus, name + '.mp4')]);
    fs.copyFileSync(path.join(tmp, String(Math.round(N * 0.35)).padStart(5, '0') + '.jpg'), path.join(aus, name + '.jpg')); // Standbild (Poster)
    fs.rmSync(tmp, {recursive: true, force: true});
    console.log(name + '.mp4', (fs.statSync(path.join(aus, name + '.mp4')).size / 1e6).toFixed(2) + ' MB');
    await p.close();
  }
  await b.close();
  fs.rmSync(CLIPS, {recursive: true, force: true});
})().catch(e => { console.error(e); process.exit(1); });
