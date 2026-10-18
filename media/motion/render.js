const {chromium}=require(require('child_process').execSync('npm root -g').toString().trim()+'/playwright');
const fs=require('fs'),path=require('path');
const [,,fmt,secs,fps,out,page='motion.html']=process.argv;const V=fmt==='v';const W=V?1080:1920,H=V?1920:1080;const FPS=+fps,N=Math.round(+secs*FPS);
const SRC=__dirname+'/';
(async()=>{const b=await chromium.launch();const p=await b.newPage({viewport:{width:W,height:H},deviceScaleFactor:1});
await p.route('http://m/**',r=>{const f=r.request().url().replace('http://m/','');r.fulfill({path:SRC+f})});
await p.addInitScript(()=>{const r=window.requestAnimationFrame.bind(window);window.requestAnimationFrame=cb=>r(()=>cb(performance.now()))});
await p.addInitScript(v=>{try{localStorage.setItem('fmt',v?'v':'h')}catch(e){}},V);
const c=await p.context().newCDPSession(p);
await p.goto('http://m/'+page,{waitUntil:'load'});
await p.addStyleTag({content:`.head,.transport{display:none!important}body{padding:0!important;margin:0!important;overflow:hidden}.wrap{max-width:none!important;gap:0!important}.stage,.stage.vertical{width:${W}px!important;height:${H}px!important;max-width:none!important;aspect-ratio:auto!important;border-radius:0!important;box-shadow:none!important}`});
await p.evaluate(()=>document.fonts.ready);await p.waitForTimeout(500);
await c.send('Emulation.setVirtualTimePolicy',{policy:'pause'});
await p.evaluate(()=>document.getElementById('restart').click());
fs.mkdirSync(out,{recursive:true});
const adv=ms=>new Promise(res=>{c.once('Emulation.virtualTimeBudgetExpired',res);c.send('Emulation.setVirtualTimePolicy',{policy:'advance',budget:ms})});
await adv(16);
for(let i=0;i<N;i++){await adv(1000/FPS);const r=await c.send('Page.captureScreenshot',{format:'jpeg',quality:92});fs.writeFileSync(path.join(out,String(i).padStart(5,'0')+'.jpg'),Buffer.from(r.data,'base64'));if(i%300===0)console.log('frame',i)}
await b.close()})();
