(function(){
var M=window.KJO_MEDIA||{};
function one(el){
  if(el.getAttribute("data-kjo"))return;
  var m=(String(el.className).match(/kjo-m-([a-z0-9_]+)/i)||[])[1]; if(!m)return;
  el.setAttribute("data-kjo","1");
  var e=M[m.toLowerCase()]||{};
  if(e.img){el.style.backgroundImage='url("'+e.img+'")';el.style.backgroundSize="cover";el.style.backgroundPosition="center";}
  if(!e.vid)return;
  el.classList.add("kjo-vid");
  var v=document.createElement("video");
  v.setAttribute("playsinline","");v.playsInline=true;v.preload="metadata";
  if(e.img){v.poster=e.img;v.src=e.vid;}else{v.src=e.vid+"#t=0.1";}
  var b=document.createElement("button");b.type="button";b.className="kjo-pb";b.setAttribute("aria-label","Video abspielen");
  b.innerHTML='<svg class="kjo-i1" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4.5v15l13-7.5z"/></svg><svg class="kjo-i2" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 4.5h4.2v15H6zM13.8 4.5H18v15h-4.2z"/></svg>';
  el.appendChild(v);el.appendChild(b);
  function set(on){el.classList.toggle("kjo-on",on);b.setAttribute("aria-label",on?"Video anhalten":"Video abspielen");}
  function tog(ev){ev.preventDefault();ev.stopPropagation();
    if(v.paused){var o=document.querySelectorAll(".kjo-vid>video");for(var i=0;i<o.length;i++){if(o[i]!==v)o[i].pause();}
      var p=v.play();if(p&&p.catch)p.catch(function(){});}
    else v.pause();}
  b.addEventListener("click",tog);v.addEventListener("click",tog);
  v.addEventListener("play",function(){set(true);});
  function rst(){set(false);if(e.img){v.load();}else{try{v.currentTime=0.1;}catch(x){}}}
  v.addEventListener("pause",rst);
  v.addEventListener("ended",rst);
}
function init(){var l=document.querySelectorAll('[class*="kjo-m-"]');for(var i=0;i<l.length;i++)one(l[i]);}
window.KJO_MEDIA_INIT=init;
if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",init);else init();
})();
