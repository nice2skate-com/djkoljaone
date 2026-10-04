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

/* Tonart-Erkennung: Float32Array (mono) + Abtastrate -> Camelot-Code ("8A", "5B" …) oder "" */
function kjmKey(x,sr){
  var D=Math.max(1,Math.round(sr/11025)),fs=sr/D,n=Math.floor(x.length/D),i,j,k;
  if(n<fs*10)return "";
  var maxN=Math.floor(fs*90),off=0;if(n>maxN){off=Math.floor((n-maxN)/2);n=maxN;}
  // auf ~11 kHz herunterrechnen (Mittelwert über 2·D Werte als einfacher Tiefpass)
  var y=new Float32Array(n);
  for(i=0;i<n;i++){var s=0,b=(i+off)*D-Math.floor(D/2),c=0;for(j=0;j<2*D;j++){var q=b+j;if(q>=0&&q<x.length){s+=x[q];c++;}}y[i]=c?s/c:0;}
  var N=8192,hop=4096,L=13,cs=new Float64Array(N/2),sn=new Float64Array(N/2),win=new Float64Array(N);
  for(i=0;i<N/2;i++){cs[i]=Math.cos(2*Math.PI*i/N);sn[i]=-Math.sin(2*Math.PI*i/N);}
  for(i=0;i<N;i++)win[i]=0.5-0.5*Math.cos(2*Math.PI*i/(N-1));
  var rev=new Uint16Array(N);for(i=0;i<N;i++){var r=0;for(j=0;j<L;j++)if(i&(1<<j))r|=1<<(L-1-j);rev[i]=r;}
  var re=new Float64Array(N),im=new Float64Array(N);
  function fft(){
    for(var a=0;a<N;a++){var t=rev[a];if(t>a){var u=re[a];re[a]=re[t];re[t]=u;u=im[a];im[a]=im[t];im[t]=u;}}
    for(var len=2;len<=N;len<<=1){var half=len>>1,step=N/len;
      for(var s0=0;s0<N;s0+=len)for(var m=0;m<half;m++){var wr=cs[m*step],wi=sn[m*step],p=s0+m,qq=p+half,
        xr=re[qq]*wr-im[qq]*wi,xi=re[qq]*wi+im[qq]*wr;re[qq]=re[p]-xr;im[qq]=im[p]-xi;re[p]+=xr;im[p]+=xi;}}
  }
  var f0=fs/N,k0=Math.ceil(60/f0),k1=Math.min(N/2-2,Math.floor(2000/f0)),mag=new Float64Array(N/2),peaks=[];
  for(var st=0;st+N<=n;st+=hop){
    var en=0;for(i=0;i<N;i++){var v=y[st+i]*win[i];re[i]=v;im[i]=0;en+=v*v;}
    if(en/N<1e-7)continue; // Stille
    fft();var mx=0;
    for(k=k0-1;k<=k1+1;k++){mag[k]=Math.sqrt(re[k]*re[k]+im[k]*im[k]);if(k>=k0&&k<=k1&&mag[k]>mx)mx=mag[k];}
    if(mx<=0)continue;
    var fr=[];
    for(k=k0;k<=k1;k++){var m0=mag[k];
      if(m0>mag[k-1]&&m0>=mag[k+1]&&m0>mx*0.05){
        // parabolische Feinschätzung der Frequenz
        var a1=Math.log(mag[k-1]+1e-12),b1=Math.log(m0+1e-12),c1=Math.log(mag[k+1]+1e-12),dl=0.5*(a1-c1)/(a1-2*b1+c1);
        if(!(Math.abs(dl)<=0.5))dl=0;
        var fq=(k+dl)*f0;fr.push([12*Math.log(fq/440)/Math.LN2+69,Math.pow(m0/mx,0.7)]);}}
    peaks.push(fr);
  }
  if(peaks.length<8)return "";
  // Stimmung (Abweichung von 440 Hz) als Kreismittel der Halbton-Brüche
  var sx=0,sy=0;peaks.forEach(function(fr){fr.forEach(function(p){var dv=p[0]-Math.round(p[0]);sx+=p[1]*Math.cos(2*Math.PI*dv);sy+=p[1]*Math.sin(2*Math.PI*dv);});});
  var tune=Math.atan2(sy,sx)/(2*Math.PI),chroma=new Float64Array(12);
  peaks.forEach(function(fr){var fc=new Float64Array(12),tot=0;
    fr.forEach(function(p){var pc=((Math.round(p[0]-tune)%12)+12)%12;fc[pc]+=p[1];tot+=p[1];});
    if(tot>0)for(var z=0;z<12;z++)chroma[z]+=fc[z]/tot;});
  // Tonart-Profile (Temperley) – Korrelation mit allen 24 Tonarten
  var MAJ=[5.0,2.0,3.5,2.0,4.5,4.0,2.0,4.5,2.0,3.5,1.5,4.0],MIN=[5.0,2.0,3.5,4.5,2.0,4.0,2.0,4.5,3.5,2.0,1.5,4.0];
  function corr(pr,rot){var ma=0,mb=0,z;for(z=0;z<12;z++){ma+=chroma[z];mb+=pr[z];}ma/=12;mb/=12;
    var nu=0,da=0,db=0;for(z=0;z<12;z++){var ca=chroma[(z+rot)%12]-ma,cb=pr[z]-mb;nu+=ca*cb;da+=ca*ca;db+=cb*cb;}
    return da>0&&db>0?nu/Math.sqrt(da*db):-1;}
  var best=-2,bpc=0,bmin=false;
  for(var t=0;t<12;t++){var cM=corr(MAJ,t),cm=corr(MIN,t);if(cM>best){best=cM;bpc=t;bmin=false;}if(cm>best){best=cm;bpc=t;bmin=true;}}
  if(best<0.3)return "";
  // Camelot: Dur-Grundton -> Nummer (C=8B, G=9B …), Moll = parallele Durtonart + 3 Halbtöne -> gleiche Nummer mit A
  var maj=bmin?(bpc+3)%12:bpc,num=((maj*7)%12+7)%12+1;
  return num+(bmin?"A":"B");
}
/*WAVE-START*/
/* Wellenform (3 Bänder, alle 50 ms) + Beatgrid: Float32Array (mono) + Abtastrate + BPM -> {dt,n,d(base64),first,bpm,bar,dur} oder null */
function kjmWave(x,sr,bpm){
  var dt=0.05,H=Math.max(1,Math.round(sr*dt)),n=Math.floor(x.length/H),i,j;if(n<40)return null;
  var a1=Math.exp(-2*Math.PI*140/sr),a2=Math.exp(-2*Math.PI*2500/sr),p1=0,q1=0,p2=0,q2=0;
  var lo=new Float32Array(n),mi=new Float32Array(n),hi=new Float32Array(n);
  var H5=Math.max(1,Math.round(sr*0.005)),n5=Math.floor(x.length/H5),eb=new Float32Array(n5),c5=0,s5=0,k5=0;
  for(i=0;i<n;i++){var sl=0,sm=0,sh=0,base=i*H;
    for(j=0;j<H;j++){var v=x[base+j];p1+=(1-a1)*(v-p1);q1+=(1-a1)*(p1-q1);p2+=(1-a2)*(v-p2);q2+=(1-a2)*(p2-q2);
      var l=q1,m=q2-q1,h=v-q2;sl+=l*l;sm+=m*m;sh+=h*h;s5+=l*l;if(++c5===H5){if(k5<n5)eb[k5++]=s5/H5;s5=0;c5=0;}}
    lo[i]=Math.sqrt(sl/H);mi[i]=Math.sqrt(sm/H);hi[i]=Math.sqrt(sh/H);}
  // Bänder angleichen (Höhen/Mitten sind energieärmer) und Lautstärke stauchen
  var a=new Float32Array(n),b=new Float32Array(n),c=new Float32Array(n),mx=new Float32Array(n);
  for(i=0;i<n;i++){a[i]=Math.pow(lo[i],0.55);b[i]=Math.pow(mi[i]*1.5,0.55);c[i]=Math.pow(hi[i]*3.5,0.55);mx[i]=Math.max(a[i],b[i],c[i]);}
  var srt=Array.prototype.slice.call(mx).sort(function(p,q){return p-q;}),ref=srt[Math.floor(n*0.98)]||1e-6,out=new Uint8Array(n*3);
  for(i=0;i<n;i++){out[i*3]=Math.min(255,Math.round(255*a[i]/ref));out[i*3+1]=Math.min(255,Math.round(255*b[i]/ref));out[i*3+2]=Math.min(255,Math.round(255*c[i]/ref));}
  var bin="";for(i=0;i<out.length;i+=8192)bin+=String.fromCharCode.apply(null,out.subarray(i,i+8192));
  var res={dt:dt,n:n,d:btoa(bin),dur:Math.round(x.length/sr*100)/100,first:0,bpm:bpm||0,bar:0};
  // Beatgrid: Anschlag-Hüllkurve des Basses (5 ms), dann Tempo (fein) und Phase suchen
  if(bpm>=60&&bpm<=200&&n5>400){
    var s=new Float32Array(n5),env=new Float32Array(n5);for(i=0;i<n5;i++)s[i]=Math.sqrt(eb[i]);
    var s2=new Float32Array(n5);for(i=3;i<n5-3;i++)s2[i]=(s[i-3]+2*s[i-2]+3*s[i-1]+4*s[i]+3*s[i+1]+2*s[i+2]+s[i+3])/16;s=s2; // Bass-Schwebungen glätten
    for(i=1;i<n5;i++){var d=s[i]-s[i-1];env[i]=d>0?d:0;}
    var step=H5/sr,best=-1,bb=bpm,bo=0,cand,pk,off,bt,sc; // Zeitschritt der Hüllkurve (~5 ms, exakt H5/sr)
    for(cand=bpm-0.7;cand<=bpm+0.7001;cand+=0.02){pk=60/cand/step; // Beat-Abstand in Hüllkurven-Schritten
      for(off=0;off<pk;off+=1){sc=0;for(bt=off;bt<n5-1;bt+=pk){var ix=Math.round(bt);sc+=env[ix]+0.5*(env[ix-1>0?ix-1:0]+env[ix+1]);}
        if(sc>best){best=sc;bb=cand;bo=off;}}}
    var pk2=60/bb/step,ph=[0,0,0,0],cnt=[0,0,0,0],k=0;
    for(bt=bo;bt<n5-1;bt+=pk2,k++){var q=Math.round(bt);ph[k%4]+=s[q];cnt[k%4]++;}
    var bar=0,bv=-1;for(i=0;i<4;i++){var av=cnt[i]?ph[i]/cnt[i]:0;if(av>bv){bv=av;bar=i;}}
    res.first=Math.round(bo*step*1000)/1000;res.bpm=Math.round(bb*100)/100;res.bar=bar;
  }
  return res;
}
/*WAVE-END*/
if(typeof module!=="undefined")module.exports={kjmBpm:kjmBpm,kjmKey:kjmKey,kjmWave:kjmWave};
