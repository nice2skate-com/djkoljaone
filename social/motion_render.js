// HTML-Animation (z. B. Export aus Claude Motion) Bild für Bild als Video rendern – mit gesteuerter Zeit,
// damit nichts ruckelt. Ergebnis: Einzelbilder, die der GitHub-Ablauf mit ffmpeg zu einem MP4 zusammensetzt.
//
//   node social/motion_render.js EINGANG.html AUSGANGSORDNER [sekunden|auto] [fps]
//
// Format immer hochkant 1080×1920. Dauer „auto“ = Summe der Szenen (data-dur) bzw. 30 Sekunden.
// Schriften, die neben der HTML-Datei fehlen (z. B. fonts/fira400.woff2), kommen aus media/motion/.
const {chromium} = require(require('child_process').execSync('npm root -g').toString().trim() + '/playwright');
const fs = require('fs'), path = require('path');
const [, , html, out, secsArg = 'auto', fpsArg = '30'] = process.argv;
if (!html || !out) { console.error('Aufruf: node social/motion_render.js EINGANG.html AUSGANG [sekunden|auto] [fps]'); process.exit(2); }
const W = 1080, H = 1920, FPS = +fpsArg || 30;
const DIR = path.dirname(path.resolve(html)), NAME = path.basename(html), ERSATZ = path.join(__dirname, '..', 'media', 'motion');

(async () => {
  const b = await chromium.launch();
  const p = await b.newPage({viewport: {width: W, height: H}, deviceScaleFactor: 1});
  await p.route('http://m/**', r => {
    const rel = decodeURIComponent(new URL(r.request().url()).pathname.slice(1));
    const f = [path.join(DIR, rel), path.join(ERSATZ, rel)].find(x => !rel.includes('..') && fs.existsSync(x) && fs.statSync(x).isFile());
    return f ? r.fulfill({path: f}) : r.fulfill({status: 404, body: ''});
  });
  await p.addInitScript(() => { const r = window.requestAnimationFrame.bind(window); window.requestAnimationFrame = cb => r(() => cb(performance.now())); });
  await p.addInitScript(() => { try { localStorage.setItem('fmt', 'v'); } catch (e) {} });
  const c = await p.context().newCDPSession(p);
  await p.goto('http://m/' + encodeURIComponent(NAME), {waitUntil: 'load'});
  const hatBuehne = await p.evaluate(() => !!document.querySelector('.stage'));
  await p.addStyleTag({content: hatBuehne
    ? `.head,.transport{display:none!important}body{padding:0!important;margin:0!important;overflow:hidden}.wrap{max-width:none!important;gap:0!important}.stage,.stage.vertical{width:${W}px!important;height:${H}px!important;max-width:none!important;aspect-ratio:auto!important;border-radius:0!important;box-shadow:none!important}`
    : 'html,body{margin:0!important;overflow:hidden!important}'});
  await p.evaluate(() => document.fonts.ready);
  await p.waitForTimeout(500);
  let secs = parseFloat(secsArg);
  if (!(secs > 0)) {
    const summe = await p.evaluate(() => [...document.querySelectorAll('.scene[data-dur],[data-scene-dur]')].reduce((s, e) => s + (parseFloat(e.dataset.dur || e.dataset.sceneDur) || 0), 0));
    secs = summe > 0 ? summe : 30;
  }
  secs = Math.min(secs, 90);
  const N = Math.round(secs * FPS);
  console.log(`Rendere ${NAME}: ${secs} s, ${FPS} fps, ${N} Bilder, ${hatBuehne ? 'Claude-Motion-Bühne' : 'ganze Seite'}`);
  await c.send('Emulation.setVirtualTimePolicy', {policy: 'pause'});
  await p.evaluate(() => { const r = document.getElementById('restart'); if (r) r.click(); });
  fs.mkdirSync(out, {recursive: true});
  const adv = ms => new Promise(res => { c.once('Emulation.virtualTimeBudgetExpired', res); c.send('Emulation.setVirtualTimePolicy', {policy: 'advance', budget: ms}); });
  await adv(16);
  for (let i = 0; i < N; i++) {
    await adv(1000 / FPS);
    const r = await c.send('Page.captureScreenshot', {format: 'jpeg', quality: 92});
    fs.writeFileSync(path.join(out, String(i).padStart(5, '0') + '.jpg'), Buffer.from(r.data, 'base64'));
    if (i % 300 === 0) console.log('Bild', i);
  }
  await b.close();
})().catch(e => { console.error(e); process.exit(1); });
