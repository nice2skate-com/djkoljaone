// CSS-Animationen Bild für Bild rendern: alle Animationen anhalten und auf die exakte Zeit setzen.
const {chromium} = require(require('child_process').execSync('npm root -g').toString().trim() + '/playwright');
const fs=require('fs'),path=require('path');
const [,,html,out,secs,fps='30']=process.argv; const N=Math.round(+secs*+fps);
(async()=>{const b=await chromium.launch();const p=await b.newPage({viewport:{width:1080,height:1920},deviceScaleFactor:1});
await p.goto('file://'+path.resolve(html),{waitUntil:'load'});await p.evaluate(()=>document.fonts.ready);await p.waitForTimeout(300);
fs.mkdirSync(out,{recursive:true});
for(let i=0;i<N;i++){const t=i*1000/+fps;
 await p.evaluate(t=>document.getAnimations().forEach(a=>{a.pause();a.currentTime=t}),t);
 const r=await p.screenshot({type:'jpeg',quality:92});fs.writeFileSync(path.join(out,String(i).padStart(5,'0')+'.jpg'),r);
 if(i%150===0)console.log('Bild',i)}
await b.close()})().catch(e=>{console.error(e);process.exit(1)});
