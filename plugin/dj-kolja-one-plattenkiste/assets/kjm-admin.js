/* Admin: Song laden, BPM + Tonart im Browser erkennen und speichern. Benötigt kjm-bpm.js */
window.kjmAdmin=(function(){
  var ac=null;
  function analyse(url,known){
    return fetch(url,{credentials:"same-origin"}).then(function(r){if(!r.ok)throw new Error("Datei nicht lesbar ("+r.status+")");return r.arrayBuffer()})
    .then(function(b){ac=ac||new (window.AudioContext||window.webkitAudioContext)();return new Promise(function(ok,no){ac.decodeAudioData(b,ok,no)})})
    .then(function(buf){var n=buf.length,m=new Float32Array(n),c=buf.numberOfChannels;
      for(var k=0;k<c;k++){var d=buf.getChannelData(k);for(var i=0;i<n;i++)m[i]+=d[i]/c}
      var bpm=(known&&known.bpm)||kjmBpm(m,buf.sampleRate)||0,key=(known&&known.key)||kjmKey(m,buf.sampleRate)||"",wave=null;try{wave=kjmWave(m,buf.sampleRate,bpm)}catch(e){}
      return {bpm:bpm,key:key,wave:wave}});
  }
  function save(AJ,N,id,res){
    var f=new FormData();f.append("action","kjm_save_bpm");f.append("_wpnonce",N);f.append("id",id);
    if(res.bpm)f.append("bpm",res.bpm);if(res.key)f.append("key",res.key);if(res.wave)f.append("wave",JSON.stringify(res.wave));
    return fetch(AJ,{method:"POST",credentials:"same-origin",body:f}).then(function(r){return r.json()})
    .then(function(j){if(!j||!j.success)throw new Error("Speichern fehlgeschlagen");return j.data});
  }
  return {analyse:analyse,save:save};
})();
