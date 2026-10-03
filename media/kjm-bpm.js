/* BPM-Erkennung: Float32Array (mono) + Abtastrate -> BPM (ganzzahlig) oder 0 */
function kjmBpm(x,sr){
  var fps=200,H=Math.max(1,Math.round(sr/fps));fps=sr/H;
  var n=Math.floor(x.length/H);if(n<fps*8)return 0;
  // höchstens 100 Sekunden aus der Mitte
  var maxN=Math.floor(fps*100),off=0;if(n>maxN){off=Math.floor((n-maxN)/2);n=maxN;}
  var env=new Float32Array(n),i,j,lp=0,a=Math.exp(-2*Math.PI*180/sr),prev=0;
  // Bass-Hüllkurve (Tiefpass ~180 Hz) + Gesamtenergie
  var eb=new Float32Array(n),ef=new Float32Array(n);
  for(i=0;i<n;i++){var sb=0,sf=0,base=(i+off)*H;for(j=0;j<H;j++){var v=x[base+j];lp=(1-a)*v+a*lp;sb+=lp*lp;sf+=v*v;}eb[i]=Math.log(1+1000*sb/H);ef[i]=Math.log(1+1000*sf/H);}
  for(i=1;i<n;i++){var d=(eb[i]-eb[i-1])+0.5*(ef[i]-ef[i-1]);env[i]=d>0?d:0;}
  // Mittelwert abziehen
  var m=0;for(i=0;i<n;i++)m+=env[i];m/=n;for(i=0;i<n;i++)env[i]-=m;
  var maxLag=Math.ceil(fps*60/50*4)+2,ac=new Float32Array(maxLag+1);
  for(var L=1;L<=maxLag&&L<n;L++){var s=0;for(i=0;i+L<n;i++)s+=env[i]*env[i+L];ac[L]=s/(n-L);}
  function at(l){var f=Math.floor(l),r=l-f;if(f+1>maxLag)return 0;return ac[f]*(1-r)+ac[f+1]*r;}
  var best=0,bs=-1e9;
  for(var b=60;b<=200;b+=0.25){var l=fps*60/b,sc=at(l)+0.6*at(2*l)+0.35*at(4*l)+0.2*at(3*l);
    var w=Math.exp(-0.5*Math.pow(Math.log(b/118)/Math.LN2/0.75,2));sc*=(0.35+0.65*w);
    if(sc>bs){bs=sc;best=b;}}
  if(bs<=0)return 0;
  // Feinabstimmung um das Maximum
  var fb=best,fs=-1e9;for(var c=best-0.5;c<=best+0.5;c+=0.02){var l2=fps*60/c,s2=at(l2)+0.6*at(2*l2)+0.35*at(4*l2)+0.2*at(3*l2);if(s2>fs){fs=s2;fb=c;}}
  while(fb<70)fb*=2;while(fb>180)fb/=2;
  return Math.round(fb);
}
if(typeof module!=="undefined")module.exports=kjmBpm;
