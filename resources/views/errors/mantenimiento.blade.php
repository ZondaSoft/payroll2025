{{-- Pantalla de error 500/503 (también la de mantenimiento durante el deploy).
     El HTML original va en bloques "verbatim" para que Blade no interprete su CSS/JS;
     solo se inyectan el código de error y la URL de inicio para el botón Volver.
     La usan errors/500.blade.php y errors/503.blade.php. --}}
@verbatim
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>Estamos en mantenimiento</title>
<style>
:root{--navy:#14307a;--blue:#2563eb;--sky:#60a5fa;--orange:#f59e0b;--ink:#0f1b3d;--mute:#5b6a8c;--bg1:#eef4fc;--bg2:#cddbf2}
*{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;overflow:hidden;font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:var(--ink);
  background:radial-gradient(ellipse at 50% 35%,var(--bg1),var(--bg2));-webkit-tap-highlight-color:transparent;overscroll-behavior:none}
#c{position:fixed;inset:0;width:100%;height:100%;display:block;touch-action:none;outline:none}

/* ---------- título y subtítulo ---------- */
#hero{position:fixed;z-index:5;top:14px;left:50%;transform:translateX(-50%);width:min(700px,calc(100% - 24px));pointer-events:none;
  background:rgba(255,255,255,.9);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border-radius:20px;overflow:hidden;
  box-shadow:0 10px 34px rgba(20,48,122,.2);border:1px solid rgba(255,255,255,.95)}
#hero .hz{height:8px;background:repeating-linear-gradient(-45deg,#f59e0b 0 12px,#20283d 12px 24px)}
#hero .row{display:flex;align-items:center;gap:16px;padding:16px 22px 12px}
#hero .gear{flex:none;width:52px;height:52px;border-radius:15px;display:grid;place-items:center;
  background:linear-gradient(135deg,var(--blue),var(--navy));box-shadow:0 6px 16px rgba(37,99,235,.35)}
#hero .gear svg{animation:spin 6s linear infinite}
#hero h1{font-size:clamp(22px,4.6vw,34px);line-height:1.1;font-weight:800;letter-spacing:-.01em;color:var(--navy)}
#hero p{margin-top:5px;font-size:clamp(13px,2.4vw,16.5px);line-height:1.35;color:var(--mute)}
#hero .bar{height:6px;margin:0 22px 16px;border-radius:99px;background:#e6ecf7;position:relative;overflow:hidden}
#hero .bar i{position:absolute;top:0;bottom:0;left:0;width:0;border-radius:inherit;background:linear-gradient(90deg,var(--blue),var(--sky));transition:width .3s linear}
#hero.checking .bar i{width:34% !important;transition:none;animation:slide 1.2s ease-in-out infinite}
#hero.ok .bar i{width:100% !important;transition:width .4s ease;background:#16a34a;animation:none}
#hero .retry{padding:0 22px 14px;margin-top:-6px;font-size:13px;line-height:1.3;color:var(--mute);font-variant-numeric:tabular-nums}
#hero .retry b{color:var(--navy)}
#hero.ok .retry{color:#166534;font-weight:600}
#hero h1 .code{margin-left:.35em;font-weight:700;color:var(--blue);white-space:nowrap}
#hero .actions{display:none;padding:2px 22px 16px}
#hero .actions button{pointer-events:auto;cursor:pointer;font:inherit;font-weight:700;font-size:15px;color:#fff;border:0;border-radius:12px;padding:10px 22px;
  background:linear-gradient(135deg,var(--blue),var(--navy));box-shadow:0 6px 16px rgba(37,99,235,.35);transition:transform .15s}
#hero .actions button:hover{transform:translateY(-1px)}
#hero .actions button:focus-visible{outline:3px solid var(--sky);outline-offset:2px}
#hero.nf .bar,#hero.nf .retry{display:none}
#hero.nf .actions{display:block}
/* modo noche (lo activa el script de la escena con html.night) */
html.night #hero{background:rgba(14,24,56,.88);border-color:rgba(255,255,255,.1);box-shadow:0 10px 34px rgba(0,0,0,.45)}
html.night #hero h1{color:#fff}
html.night #hero h1 .code{color:var(--sky)}
html.night #hero p,html.night #hero .retry{color:#b4c2e2}
html.night #hero .retry b{color:#fff}
html.night #hero .bar{background:#24325f}
@keyframes spin{to{transform:rotate(360deg)}}
@keyframes slide{0%{left:-34%}100%{left:100%}}

#hint{position:fixed;left:50%;bottom:16px;transform:translateX(-50%);z-index:3;background:rgba(15,27,61,.82);color:#fff;
  font-size:13px;padding:8px 16px;border-radius:99px;pointer-events:none;transition:opacity .6s;white-space:nowrap}
#hint.off{opacity:0}
#err{position:fixed;inset:0;z-index:20;display:none;place-items:center;text-align:center;padding:30px;font-size:16px;color:var(--navy)}

/* ---------- reloj analógico (arrastrar las agujas cambia la hora de la escena) ---------- */
#reloj{position:fixed;z-index:6;top:14px;right:14px;width:156px;padding:10px 10px 9px;text-align:center;user-select:none;-webkit-user-select:none;
  background:rgba(255,255,255,.9);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border-radius:18px;
  box-shadow:0 10px 30px rgba(20,48,122,.2);border:1px solid rgba(255,255,255,.95);color:var(--navy)}
#reloj svg{display:block;width:118px;height:118px;margin:0 auto;touch-action:none;cursor:default}
#reloj .aguja{cursor:grab} #reloj.arrastrando .aguja{cursor:grabbing}
#reloj .aguja .hit{stroke:transparent;stroke-width:16;stroke-linecap:round}
#reloj .dig{margin-top:4px;font-size:20px;font-weight:800;font-variant-numeric:tabular-nums;letter-spacing:.02em}
#reloj .dia{display:flex;align-items:center;justify-content:center;gap:4px;margin-top:2px;font-size:13px;font-weight:600;color:var(--mute)}
#reloj .dia button,#reloj #relojReal{font:inherit;cursor:pointer;border:0;border-radius:8px;background:#e6ecf7;color:var(--navy);padding:2px 7px}
#reloj .dia button:hover,#reloj #relojReal:hover{background:#d6e0f3}
#reloj .estado{margin-top:5px;font-size:11.5px;line-height:1.25;color:var(--mute);min-height:2.5em}
#reloj .estado b{color:var(--navy)}
#reloj #relojReal{margin-top:6px;font-size:12px;font-weight:700}
#reloj .ayuda{margin-top:4px;font-size:10.5px;color:var(--mute);opacity:.85}
html.night #reloj{background:rgba(14,24,56,.88);border-color:rgba(255,255,255,.1);color:#fff;box-shadow:0 10px 30px rgba(0,0,0,.45)}
html.night #reloj .dia,html.night #reloj .estado,html.night #reloj .ayuda{color:#b4c2e2} html.night #reloj .estado b{color:#fff}
html.night #reloj .dia button,html.night #reloj #relojReal{background:#24325f;color:#fff}
@media (max-width:1040px){ #reloj{top:auto;bottom:14px;right:10px;width:136px;padding:8px} #reloj svg{width:96px;height:96px} #reloj .dig{font-size:17px} #reloj .ayuda{display:none} }
@media (max-width:680px){ #reloj{width:118px;padding:7px} #reloj svg{width:80px;height:80px} #reloj .dig{font-size:15px} #reloj .dia{font-size:11.5px} #reloj .estado{font-size:10.5px} }

@media (max-width:680px){
  #hero{top:8px;width:calc(100% - 16px);border-radius:16px}
  #hero .row{gap:12px;padding:12px 14px 10px}
  #hero .gear{width:40px;height:40px;border-radius:12px}
  #hero .gear svg{width:21px;height:21px}
  #hero .bar{margin:0 14px 12px}
  #hero .retry{padding:0 14px 11px;font-size:12px}
  #hero .actions{padding:2px 14px 12px}
  #hero .actions button{font-size:14px;padding:9px 18px}
  #hint{font-size:12px;bottom:12px;max-width:92vw;overflow:hidden;text-overflow:ellipsis}
}
</style>
<script type="importmap">
{"imports":{
 "three":"https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.module.js",
 "three/addons/":"https://cdn.jsdelivr.net/npm/three@0.160.0/examples/jsm/"}}
</script>
</head>
<body data-mantenimiento>
<canvas id="c" tabindex="0"></canvas>

<header id="hero">
  <div class="hz"></div>
  <div class="row">
    <div class="gear"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><circle cx="12" cy="12" r="6.5"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M19.1 4.9L17 7M7 17l-2.1 2.1"/></svg></div>
    <div class="txt">
      <h1><span id="titulo">Estamos en mantenimiento</span><span id="codigo" class="code" hidden></span></h1>
      <p id="subtitulo">Espera un momento en breve el sistema estara en linea nuevamente</p>
    </div>
  </div>
  <div class="bar"><i></i></div>
  <div class="retry" id="retry" aria-live="polite">Verificando de nuevo en <b id="cd">30</b> s</div>
  <div class="actions"><button type="button" id="volver">&larr; Volver</button></div>
</header>
<div id="hint">Arrastrá para girar · pellizcá o rueda para zoom</div>
<div id="reloj" aria-label="Reloj de la obra: arrastrá las agujas para cambiar la hora">
  <svg id="relojSvg" viewBox="0 0 120 120">
    <circle cx="60" cy="60" r="56" fill="#fff" stroke="#14307a" stroke-width="4"/>
    <g id="relojMarcas"></g>
    <g font-size="13" font-weight="800" fill="#14307a" text-anchor="middle" font-family="system-ui,sans-serif">
      <text x="60" y="24">12</text><text x="99" y="65">3</text><text x="60" y="105">6</text><text x="21" y="65">9</text>
    </g>
    <g id="agujaH" class="aguja"><line x1="60" y1="60" x2="60" y2="32" stroke="#14307a" stroke-width="6" stroke-linecap="round"/><line class="hit" x1="60" y1="60" x2="60" y2="30"/></g>
    <g id="agujaM" class="aguja"><line x1="60" y1="60" x2="60" y2="17" stroke="#2563eb" stroke-width="4" stroke-linecap="round"/><line class="hit" x1="60" y1="60" x2="60" y2="15"/></g>
    <circle cx="60" cy="60" r="5" fill="#f59e0b" stroke="#14307a" stroke-width="2"/>
  </svg>
  <div class="dig" id="relojDig">--:--</div>
  <div class="dia"><button type="button" id="diaAnt" title="Día anterior" aria-label="Día anterior">&#9664;</button><span id="relojDia">---</span><button type="button" id="diaSig" title="Día siguiente" aria-label="Día siguiente">&#9654;</button></div>
  <div class="estado" id="relojEstado"></div>
  <button type="button" id="relojReal" hidden>&#8634; Hora real</button>
  <div class="ayuda">Arrastrá las agujas</div>
</div>
<div id="err">No se pudo cargar la escena 3D.<br>Revisá la conexión a internet (Three.js se descarga de un CDN) y recargá la página.</div>
<script>setTimeout(function(){ if(!window.__ok){ document.getElementById('err').style.display='grid'; } }, 8000);</script>

@endverbatim
@php
    // La pantalla de error no puede romperse: si url() falla, '/' alcanza.
    try { $urlInicio = url('/'); } catch (\Throwable $e) { $urlInicio = '/'; }
@endphp
<script>window.ERROR_CODE = {{ (int) ($codigo ?? 503) }}; window.MANTENIMIENTO = { urlInicio: @json($urlInicio) };</script>
@verbatim
<script>
/* =====================================================================
   CÓDIGO DE ERROR
   Esta página no puede "preguntarle" al servidor por qué fue llamada, así que busca el código
   en este orden (gana el primero que encuentre, siempre entre 400 y 599):
     1) window.ERROR_CODE            -> lo imprime el servidor:  <script>window.ERROR_CODE = 503<\/script>
     2) ?code=503 en la URL          -> para redirecciones (el servidor responde 302 -> /mantenimiento.html?code=503)
     3) código HTTP real de ESTA página (PerformanceNavigationTiming.responseStatus) -> funciona cuando
        el servidor sirve este archivo como ErrorDocument/error_page y conserva el estado 404/500/503
     4) la ruta, si contiene /404, /500 o /503 (ej. /errors/404.html)
   Si no encuentra nada, queda como "Estamos en mantenimiento" sin número.
   Opcional: ?next=/ruta  (misma origen) = adónde volver cuando el sistema se recupere.
   ===================================================================== */
(function(){
  var qs = new URLSearchParams(location.search);
  function valido(v){ var n = parseInt(v,10); return (n>=400 && n<=599) ? n : null; }
  var code = valido(window.ERROR_CODE) || valido(qs.get('code'));
  if(!code){
    try { var nav = performance.getEntriesByType('navigation')[0]; if(nav && nav.responseStatus) code = valido(nav.responseStatus); } catch(e){}
  }
  if(!code){ var m = location.pathname.match(/(?:^|[\/_-])(404|500|503)(?:[\/._-]|$)/); if(m) code = valido(m[1]); }
  window.__errorCode = code || null;

  var next = qs.get('next');                      /* solo rutas internas: "/algo", nunca "//otro.sitio" ni "http://..." */
  window.__next = (next && /^\/(?![\/\\])/.test(next)) ? next : null;

  var hero = document.getElementById('hero'), cod = document.getElementById('codigo');
  if(code){ cod.textContent = '(' + code + ')'; cod.hidden = false; }
  if(code === 404){
    hero.classList.add('nf');
    document.getElementById('titulo').textContent = 'Pagina no encontrada';
    document.getElementById('subtitulo').textContent = 'La direccion que buscas no existe o cambio de lugar';
  }
  document.title = document.getElementById('titulo').textContent + (code ? ' (' + code + ')' : '');

  var inicio = (window.MANTENIMIENTO && window.MANTENIMIENTO.urlInicio) || '/';
  document.getElementById('volver').addEventListener('click', function(){
    var ref = document.referrer, mismoSitio = false;
    try { mismoSitio = !!ref && new URL(ref).origin === location.origin; } catch(e){}
    if(history.length > 1){
      history.back();
      setTimeout(function(){ location.href = inicio; }, 700);   /* si el historial no llevó a ningún lado */
    } else if(mismoSitio){ location.href = ref; }
    else { location.href = inicio; }
  });
})();
</script>

<script>
/* =====================================================================
   REINTENTO AUTOMÁTICO (no corre en 404: ahí se muestra el botón Volver)
   Cada 30 s pide `urlVerificacion` (por defecto, esta misma página). Si responde bien
   y ya no es la página de mantenimiento, vuelve al sistema (`urlDestino`, o recarga).
   Se puede configurar ANTES de este script:
     <script>window.MANTENIMIENTO = { segundos:30, urlVerificacion:'/api/health', urlDestino:'/' }<\/script>
   ===================================================================== */
(function(){
  if(window.__errorCode === 404) return;
  var CFG = Object.assign({ segundos:30, urlVerificacion:window.__next, urlDestino:window.__next, timeoutMs:8000 }, window.MANTENIMIENTO||{});
  var hero=document.getElementById('hero'), bar=hero.querySelector('.bar i'), msg=document.getElementById('retry'), deadline=0, busy=false;
  function esc(n){ return '<b id="cd">'+n+'</b>'; }
  function arm(){ deadline=Date.now()+CFG.segundos*1000; busy=false; hero.classList.remove('checking'); bar.style.width='0%'; }
  function tick(){
    if(busy) return;
    var left=Math.max(0,deadline-Date.now()), secs=Math.ceil(left/1000);
    msg.innerHTML='Verificando de nuevo en '+esc(secs)+' s';
    bar.style.width=((1-left/(CFG.segundos*1000))*100).toFixed(1)+'%';
    if(left<=0) verificar();
  }
  function verificar(){
    busy=true; hero.classList.add('checking'); msg.textContent='Verificando si el sistema ya está en línea…';
    var ctl=new AbortController(), to=setTimeout(function(){ ctl.abort(); }, CFG.timeoutMs);
    fetch(CFG.urlVerificacion||location.href,{cache:'no-store',signal:ctl.signal})
      .then(function(r){ clearTimeout(to); if(!r.ok) throw new Error('HTTP '+r.status); return r.text(); })
      .then(function(t){ if(t.indexOf('data-mantenimiento')!==-1) throw new Error('sigue en mantenimiento'); volver(); })
      .catch(function(){ clearTimeout(to); msg.textContent='Todavía en mantenimiento. Reintentando…'; setTimeout(arm,1500); });
  }
  function volver(){
    hero.classList.remove('checking'); hero.classList.add('ok'); msg.textContent='¡El sistema volvió a estar en línea! Redirigiendo…';
    setTimeout(function(){ if(CFG.urlDestino) location.replace(CFG.urlDestino); else location.reload(); },1400);
  }
  arm(); setInterval(tick,250); tick();
  window.__retry={ cfg:CFG, verificar:verificar, arm:arm };
})();
</script>

<script type="module">
import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

/* ---------- paleta (la misma del pañol) ---------- */
const C = { navy:0x14307a, blue:0x2563eb, sky:0x60a5fa, orange:0xf59e0b, carton:0xd9b27c, cartonDk:0xc19a62,
            floor:0xe6ebf5, metal:0x9aa6b8, dark:0x20283d, white:0xffffff, yellow:0xf5c542, wall:0x3b6fe0,
            concrete:0xb7c0d0, brick:0xd98b5f, asphalt:0x39425c };

/* ---------- renderer / escena / cámara isométrica ---------- */
const canvas = document.getElementById('c');
let renderer;
try { renderer = new THREE.WebGLRenderer({ canvas, antialias:true, alpha:true }); }
catch(e){ document.getElementById('err').style.display='grid'; throw e; }
renderer.setPixelRatio(Math.min(window.devicePixelRatio||1, 2));
renderer.shadowMap.enabled = true;
renderer.shadowMap.type = THREE.PCFSoftShadowMap;

const scene = new THREE.Scene();
const camera = new THREE.OrthographicCamera(-10,10,10,-10,-150,260);
camera.position.set(21,18,21);
camera.lookAt(0,1,0);
camera.zoom=.72;   // calles laterales y barrio de atrás: más lejos para que entre todo

function resize(){
  const w = window.innerWidth, h = window.innerHeight, a = w/h;
  renderer.setSize(w,h,false);
  const H = Math.max(15.5, 20/a);
  const dy = H*(a<.9 ? .22 : .27);              // baja la escena para dejar lugar al título
  camera.left=-H*a; camera.right=H*a; camera.top=H+dy; camera.bottom=-H+dy;
  camera.updateProjectionMatrix();
}
window.addEventListener('resize', resize);
window.addEventListener('orientationchange', ()=>setTimeout(resize,200));
resize();

const hemi = new THREE.HemisphereLight(0xffffff, 0xb6c6e6, 0.95); scene.add(hemi);
const sun = new THREE.DirectionalLight(0xffffff, 1.5);
sun.position.set(10,26,12);
sun.castShadow = true;
const small = Math.min(window.innerWidth, window.innerHeight) < 700;
sun.shadow.mapSize.set(small?1024:2048, small?1024:2048);
Object.assign(sun.shadow.camera, { left:-34, right:34, top:30, bottom:-30, near:1, far:120 });
sun.shadow.bias = -0.0006;
scene.add(sun);

/* ---------- helpers ---------- */
const ease = t=>t*t*(3-2*t), lerp=(a,b,t)=>a+(b-a)*t, clamp=(v,a,b)=>Math.min(b,Math.max(a,v));
const std = (hex,o={}) => new THREE.MeshStandardMaterial({ color:hex, roughness:.75, metalness:.04, flatShading:true, ...o });
const matCache = {}; const M = hex => matCache[hex] || (matCache[hex]=std(hex));
const B = (w,h,d)=>new THREE.BoxGeometry(w,h,d);
const CYL = (r1,r2,h,s=8)=>new THREE.CylinderGeometry(r1,r2,h,s);
function mesh(geo,mat,x=0,y=0,z=0,shadow=true){ const m=new THREE.Mesh(geo,mat); m.position.set(x,y,z); m.castShadow=shadow; m.receiveShadow=true; return m; }
function A(parent,geo,hex,x=0,y=0,z=0,rx=0,ry=0,rz=0,shadow=true){ const m=mesh(geo,M(hex),x,y,z,shadow); m.rotation.set(rx,ry,rz); parent.add(m); return m; }
const dimMats=[];   // materiales sin iluminación (marcas del piso): se oscurecen de noche
function plane(w,d,x,z,hex,y=.012){ const mat=new THREE.MeshBasicMaterial({color:hex}); dimMats.push({mat,base:new THREE.Color(hex)}); const m=new THREE.Mesh(new THREE.PlaneGeometry(w,d), mat); m.rotation.x=-Math.PI/2; m.position.set(x,y,z); scene.add(m); return m; }
const towerLampMat=new THREE.MeshBasicMaterial({color:0xfff1b0});   // paneles de la torre de luz (brillan de noche)
const obstacles=[]; function obstacle(name,cx,cz,w,d){ obstacles.push({name,x0:cx-w/2,x1:cx+w/2,z0:cz-d/2,z1:cz+d/2}); }

/* =====================================================================
   TERRENO, CALLE Y VALLADO
   ===================================================================== */
const GX=15, GZ=11.5;
const WX=22.6, WZ0=-24.6, WZ1=13.4;   // mundo: obra + calles laterales + vereda del frente + calle de atrás y barrio
scene.add(mesh(B(WX*2,.5,WZ1-WZ0), M(C.navy), 0,-.55,(WZ0+WZ1)/2));
scene.add(mesh(B(GX*2,.4,GZ*2), std(C.floor,{flatShading:false,roughness:.95}), 0,-.2,0));
scene.add(mesh(B(32.8,.22,3.94), std(C.asphalt,{flatShading:false,roughness:.95}), 0,-.09,9.57));   // calle al frente (sobresale .04 del terreno para no solapar caras)
for(let x=-16;x<=16;x+=1.8) plane(.9,.09,x,9.5,0xffffff,.032);
plane(32.8,.07,0,7.75,0xffffff,.032); plane(32.8,.07,0,11.35,0xffffff,.032);
plane(6.6,16.5,9.1,-1.1,0xdcd3bf);       // tierra pisada (camino de camiones)
plane(4.4,4.2,13.0,-3.95,0xdcd3bf);

/* ---------- calles laterales y veredas ---------- */
const ROAD_X=18.4, SIDE_IN=15.7, SIDE_OUT=21.1, SIDEWALK_Y=.15, FRONT_WALK_Z=12.35;
const asphaltMat=std(C.asphalt,{flatShading:false,roughness:.95}), walkMat=std(0xd3d8e2,{flatShading:false,roughness:.9}), curbMat=std(0xaab2c2,{flatShading:false,roughness:.9});
function slab2(mat,w,d,x,z,top){ const h=top+.3; const m=mesh(B(w,h,d),mat,x,top-h/2,z,false); scene.add(m); return m; }
const BACK_Z=-14.9, BACK_IN_Z=-12.2, BACK_OUT_Z=-17.6;   // calle de atrás y sus veredas
[-1,1].forEach(s=>{
  slab2(asphaltMat,4.0,WZ1-WZ0,s*ROAD_X,(WZ0+WZ1)/2,.02);                                      // calle lateral
  for(let z=WZ0+.9; z<WZ1-.4; z+=1.8) if((z<7.3||z>11.9) && (z<-17.1||z>-12.7)) plane(.09,.9,s*ROAD_X,z,0xffffff,.032);   // línea discontinua (no sobre los cruces)
  slab2(walkMat,1.4,7.6+12.9,s*SIDE_IN,(7.6-12.9)/2,SIDEWALK_Y);                                 // vereda junto al vallado
  slab2(curbMat,.12,7.6+12.9,s*16.34,(7.6-12.9)/2,SIDEWALK_Y+.02);                               // cordón
  slab2(walkMat,1.4,-16.9-WZ0,s*SIDE_IN,(WZ0-16.9)/2,SIDEWALK_Y);                                // vereda del lado del barrio
  slab2(curbMat,.12,-16.9-WZ0,s*16.34,(WZ0-16.9)/2,SIDEWALK_Y+.02);
  slab2(walkMat,1.4,WZ1-WZ0,s*SIDE_OUT,(WZ0+WZ1)/2,SIDEWALK_Y);                                  // vereda de enfrente
  slab2(curbMat,.12,WZ1-WZ0,s*20.46,(WZ0+WZ1)/2,SIDEWALK_Y+.02);
});
// calle de atrás (detrás del vallado del fondo) con veredas a los dos lados
slab2(asphaltMat,32.8,4.0,0,BACK_Z,.02);
for(let x=-15.5;x<=15.5;x+=1.8) plane(.9,.09,x,BACK_Z,0xffffff,.032);
plane(32.8,.07,0,-13.05,0xffffff,.032); plane(32.8,.07,0,-16.75,0xffffff,.032);
slab2(walkMat,32.8,1.4,0,BACK_IN_Z,SIDEWALK_Y);  slab2(curbMat,32.8,.12,0,-12.84,SIDEWALK_Y+.02);
slab2(walkMat,32.8,1.4,0,BACK_OUT_Z,SIDEWALK_Y); slab2(curbMat,32.8,.12,0,-16.96,SIDEWALK_Y+.02);
slab2(std(0x7fb069,{flatShading:false,roughness:1}),32.8,-18.3-WZ0,0,(WZ0-18.3)/2,.06);         // terrenos del barrio (pasto)

/* ---------- barrio: 4 casas de estilos distintos cruzando la calle de atrás ---------- */
const CASA_Z=-21.6, PUERTA_Z=-19.55;
const WIN_OFF=new THREE.Color(0x2a3550), WIN_ON=new THREE.Color(0xffd27a);
const casas=[];
function ventana(g,mat,x,y,z,w=.8,h=.7){ const m=new THREE.Mesh(B(w,h,.05),mat); m.position.set(x,y,z); g.add(m); A(g,B(w+.12,.07,.1),0xffffff,x,y-h/2-.03,z+.02,0,0,0,false); return m; }
function puerta(g,x,z,col){ const piv=new THREE.Group(); piv.position.set(x-.35,0,z+.035); g.add(piv); A(piv,B(.7,1.45,.06),col,.35,.73,0); A(piv,B(.06,.06,.06),0xf5c542,.6,.75,.05,0,0,0,false); return piv; }
function arbol(x,z,r){ A(scene,CYL(.1,.13,1.1,6),0x7c4a1e,x,.6,z); const c=mesh(new THREE.IcosahedronGeometry(r,0),M(0x3f8f3a),x,1.1+r*.8,z); scene.add(c); }
function nuevaCasa(hx,doorLocalX,build){
  const g=new THREE.Group(); g.position.set(hx,0,CASA_Z); scene.add(g);
  const wm=new THREE.MeshBasicMaterial({color:WIN_OFF.clone()});
  const door=build(g,wm);
  A(scene,B(.8,.03,1.25),0xcbd2dc,hx+doorLocalX,.075,-18.92,0,0,0,false);                     // caminito a la puerta
  const porch=new THREE.PointLight(0xffd59a,0,5,1.6); porch.position.set(hx+doorLocalX,2.1,PUERTA_Z+.5); scene.add(porch);
  const c={ x:hx, doorX:hx+doorLocalX, door, wm, porch, k:0, doorOpen:0 }; casas.push(c); return c;
}
// 1) clásica: paredes crema, techo de tejas a dos aguas y chimenea
nuevaCasa(-12,0,(g,wm)=>{
  A(g,B(4.2,2.4,3.6),0xf3e3c3,0,1.2,0);
  const t=new THREE.CylinderGeometry(2.66,2.66,4.0,3); t.rotateX(Math.PI/2); t.rotateZ(Math.PI); t.scale(1,.42,1); g.add(mesh(t,M(0xb4472c),0,2.96,0));
  A(g,B(.42,1.1,.42),0x8b4a3c,1.25,3.3,-.6);
  ventana(g,wm,-1.3,1.4,1.81); ventana(g,wm,1.3,1.4,1.81);
  return puerta(g,0,1.8,0x7c4a1e);
});
// 2) chalet de dos plantas con techo a cuatro aguas y balcón
nuevaCasa(-4,-1.2,(g,wm)=>{
  A(g,B(4.4,2.2,3.8),0xc9d6e3,0,1.1,0); A(g,B(4.5,.1,3.9),0x64748b,0,2.25,0);
  A(g,B(3.6,2.0,3.0),0xe2e8f0,0,3.3,-.3);
  const t=new THREE.ConeGeometry(2.85,1.3,4); t.rotateY(Math.PI/4); t.scale(1,1,.86); g.add(mesh(t,M(0x4b5563),0,4.95,-.3));
  A(g,B(2.2,.1,.6),0xffffff,.6,2.36,1.5); A(g,B(2.2,.4,.05),0xffffff,.6,2.6,1.78);   // balcón
  ventana(g,wm,.9,1.25,1.91,1.2,.8); ventana(g,wm,-.6,3.35,1.21); ventana(g,wm,.9,3.35,1.21);
  return puerta(g,-1.2,1.9,0x334155);
});
// 3) moderna de techo plano, revestimiento de madera, ventanal y garage
let garajePuerta=null; const GAR_X=6.3;
nuevaCasa(4,-1.6,(g,wm)=>{
  A(g,B(3.6,2.7,3.8),0xf5f5f4,-.8,1.35,0); A(g,B(4.0,.18,4.2),0x1f2937,-.8,2.79,0);
  A(g,B(.6,2.7,.08),0xa16207,-2.3,1.35,1.91);
  ventana(g,wm,0,1.5,1.92,1.4,1.6);
  A(g,B(2.4,2.3,3.4),0xe7e5e4,2.3,1.15,-.2); A(g,B(2.6,.15,3.6),0x1f2937,2.3,2.37,-.2);   // garage
  A(g,B(2.0,1.95,.02),0x111827,2.3,1.0,1.44);                                             // interior oscuro
  garajePuerta=new THREE.Group(); garajePuerta.position.set(2.3,1.97,1.53); g.add(garajePuerta);
  A(garajePuerta,B(2.0,1.95,.06),0x9ca3af,0,-.975,0); for(let k=0;k<4;k++) A(garajePuerta,B(2.0,.03,.07),0x6b7280,0,-.35-k*.45,0,0,0,0,false);
  return puerta(g,-1.6,1.9,0x111827);
});
A(scene,B(2.2,.04,1.9),0xb6bfcc,GAR_X,.08,-19.2,0,0,0,false);                               // entrada de autos
// 4) de ladrillo con techo de una agua y galería
nuevaCasa(12,.9,(g,wm)=>{
  A(g,B(4.2,2.5,3.6),0xc0643f,0,1.25,0);
  A(g,B(4.7,.16,4.3),0x374151,0,2.85,0,-.2,0,0);
  [-1.8,1.8].forEach(x=>A(g,B(.1,2.2,.1),0xffffff,x,1.1,2.4)); A(g,B(4.2,.1,1.0),0x374151,0,2.3,2.25); A(g,B(4.2,.12,1.0),0x9a7a4c,0,.08,2.25);
  ventana(g,wm,-1.0,1.4,1.81,1.2,.8);
  return puerta(g,.9,1.8,0x14532d);
});
arbol(-8.2,-19.4,.85); arbol(-.2,-19.2,.75); arbol(8.6,-19.3,.8); arbol(-15.2,-22.5,.9); arbol(15.3,-23,.8);
slab2(walkMat,32.8,1.66,0,12.37,SIDEWALK_Y);                                                    // vereda de la calle del frente
slab2(curbMat,32.8,.12,0,11.6,SIDEWALK_Y+.02);

function fenceRun(x0,z0,x1,z1){
  const len=Math.hypot(x1-x0,z1-z0), n=Math.max(1,Math.round(len/1.25)), ux=(x1-x0)/len, uz=(z1-z0)/len, seg=len/n, ang=Math.atan2(-uz,ux);
  for(let i=0;i<n;i++){
    const g=new THREE.Group(); g.position.set(x0+ux*seg*(i+.5), 0, z0+uz*seg*(i+.5)); g.rotation.y=ang; scene.add(g);
    A(g,B(seg-.12,.7,.06), i%2?C.orange:0xffffff, 0,.5,0);
    A(g,B(seg-.12,.08,.08), C.navy, 0,.9,0);
    A(g,B(.1,1.0,.1), C.navy, -seg/2,.5,0);
  }
  A(scene,B(.1,1.0,.1),C.navy,x1,.5,z1);
}
const FZ=7.0, FX=14.6, FB=-11.0;
fenceRun(-FX,FZ, 6.0,FZ); fenceRun(8.6,FZ, 9.6,FZ); fenceRun(12.2,FZ, FX,FZ);       // frente con 2 portones (salida 6–8.6, entrada 9.6–12.2)
fenceRun(-FX,FZ,-FX,FB); fenceRun(FX,FZ,FX,FB); fenceRun(-FX,FB,FX,FB);
const gateLamps=[];
[6.0,8.6,9.6,12.2].forEach((x,i)=>{
  A(scene,B(.3,1.9,.3),C.navy,x,.95,FZ); A(scene,B(.34,.12,.34),C.orange,x,1.95,FZ);
  const l=new THREE.Mesh(new THREE.SphereGeometry(.13,10,8), new THREE.MeshBasicMaterial({color:0xffa400})); l.position.set(x,2.15,FZ); scene.add(l); gateLamps.push(l);
});
// barreras levadizas en los dos portones (abiertas de día, bajas cuando la obra está cerrada)
const barriers=[];
function barrier(hingeX,dir,len){
  const g=new THREE.Group(); g.position.set(hingeX,1.0,FZ); scene.add(g);
  A(g,B(.3,.34,.3),C.orange,0,-.05,0);                                  // caja del mecanismo
  const arm=new THREE.Group(); g.add(arm);
  const n=6, seg=len/n;
  for(let i=0;i<n;i++) A(arm,B(seg,.13,.08), i%2?0xffffff:0xdc2626, dir*(seg*(i+.5)),0,0,0,0,0,false);
  const lamp=new THREE.Mesh(new THREE.SphereGeometry(.1,8,6), new THREE.MeshBasicMaterial({color:0xff2a2a})); lamp.position.set(dir*(len-.12),.14,0); arm.add(lamp);
  const b={ g, arm, lamp, dir, k:0, target:0 }; barriers.push(b); return b;
}
barrier(12.05,-1,2.3);   // entrada (9.75–12.05)
// cartel "CERRADO" que se cuelga del portón durante el asado de los sábados
const cartelCerrado=new THREE.Group(); cartelCerrado.position.set(10.9,1.55,7.18); scene.add(cartelCerrado); cartelCerrado.visible=false;
A(cartelCerrado,B(1.7,.62,.05),0xdc2626,0,0,0); A(cartelCerrado,B(1.5,.1,.06),0xffffff,0,.12,0); A(cartelCerrado,B(1.5,.1,.06),0xffffff,0,-.12,0);
[-.6,.6].forEach(x=>A(cartelCerrado,B(.03,.5,.03),C.dark,x,.5,0));
barrier(8.45,-1,2.3);    // salida  (6.15–8.45)
// carteles de entrada / salida
function sign(x,z,hex){ A(scene,B(.08,1.4,.08),C.dark,x,.7,z); A(scene,B(.9,.5,.06),hex,x,1.55,z); A(scene,B(.7,.1,.07),0xffffff,x,1.55,z+.01); }
sign(12.6,6.4,C.blue); sign(8.95,6.4,0x16a34a);
// conos
function cone(x,z){ A(scene,CYL(.07,.22,.5,8),C.orange,x,.25,z); A(scene,CYL(.11,.14,.08,8),0xffffff,x,.3,z); A(scene,B(.5,.04,.5),C.dark,x,.02,z); }
[[13.8,6.4],[5.0,6.6],[3.9,6.6],[6.6,-7.0],[6.6,-5.8]].forEach(p=>cone(...p));

/* =====================================================================
   EDIFICIO EN CONSTRUCCIÓN
   ===================================================================== */
const BLD = { cx:-4, cz:-3, w:10.6, d:8.6 };
(function(){
  const g=new THREE.Group(); scene.add(g);
  const slab=(y,t,w,d,cx,cz)=>A(g,B(w,t,d),C.concrete,cx,y,cz);
  slab(.12,.24,BLD.w,BLD.d,BLD.cx,BLD.cz);                         // platea
  slab(3.0,.3,BLD.w,BLD.d,BLD.cx,BLD.cz);                          // losa 1
  slab(6.0,.3,8.3,BLD.d,-5.15,BLD.cz);                             // losa 2 (parcial)
  const xs=[-9,-4,1], zs=[-7,-3,1];
  xs.forEach(x=>zs.forEach(z=>{
    A(g,B(.5,2.76,.5),0xa9b4c6,x,1.62,z);                          // columnas planta baja
    A(g,B(.5,2.7,.5),0xa9b4c6,x,4.5,z);                            // columnas piso 1
    A(g,B(.5,2.5,.5),0xa9b4c6,x,7.4,z);                            // columnas piso 2 (con hierros)
    [[-.17,-.17],[.17,-.17],[-.17,.17],[.17,.17]].forEach(([a,b])=>A(g,CYL(.025,.025,.7,5),0x8a5a2b,x+a,8.9,z+b));
  }));
  // muros de ladrillo (planta baja, a medio hacer)
  A(g,B(4.1,1.2,.26),C.brick,-6.95,.84,1);          // frente (en construcción)
  A(g,B(4.4,2.7,.26),C.brick,-1.2,1.59,1);          // frente (terminado)
  A(g,B(.26,2.7,3.0),C.brick,1,1.59,-5.5);          // lateral este
  A(g,B(.26,1.2,2.0),C.brick,1,.84,0);
  A(g,B(10,2.7,.26),C.brick,-4,1.59,-7);            // fondo
  // red de seguridad naranja en el borde de la losa 2
  const net=new THREE.Mesh(B(8.3,1.1,.02), new THREE.MeshBasicMaterial({color:C.orange,transparent:true,opacity:.4,side:THREE.DoubleSide}));
  net.position.set(-5.15,6.7,1.05); g.add(net);
  // andamio (lado oeste)
  for(let z=-7;z<=1.01;z+=1.6){ A(g,B(.07,6.4,.07),C.yellow,-10.1,3.2,z); A(g,B(.07,6.4,.07),C.yellow,-9.35,3.2,z); }
  [1.5,3.0,4.5,6.0].forEach(y=>{ A(g,B(.06,.06,8.2),C.yellow,-10.1,y,-3); A(g,B(.06,.06,8.2),C.yellow,-9.35,y,-3); });
  [3.0,4.5].forEach(y=>A(g,B(.8,.07,8.0),0xb08850,-9.72,y+.04,-3));
  for(let i=0;i<5;i++){ const z=-6+i*1.6, d=A(g,B(.05,1.9,.05),C.yellow,-9.35,2.2,z+.8); d.rotation.x=(i%2?1:-1)*.75; }
  // fierros, tablas de encofrado y tubos sobre la losa 1
  A(g,B(1.6,.12,.4),0xb08850,-8,3.21,-5.5); A(g,B(1.6,.12,.4),0xb08850,-8,3.33,-5.5);
  for(let i=0;i<4;i++) A(g,CYL(.04,.04,2,5),0x8a5a2b,-2.6,3.2+i*.09,-5.9+i*.2,0,0,Math.PI/2);
})();
obstacle('edificio',-4.5,-3,11.8,8.8);

/* ---------- grúa torre ---------- */
const CR = { x:3.5, z:-3.5, H:11.5, L:12.5 };
const crane = new THREE.Group(); crane.position.set(CR.x,0,CR.z); scene.add(crane);
A(crane,B(2.8,.5,2.8),C.metal,0,.25,0);
[[1,1],[1,-1],[-1,1],[-1,-1]].forEach(([a,b])=>A(crane,B(.9,.7,.9),0xb9c4d8,a,.85,b));
[[1,1],[1,-1],[-1,1],[-1,-1]].forEach(([a,b])=>A(crane,B(.12,CR.H-.6,.12),C.yellow,a*.55,(CR.H+.6)/2,b*.55));
for(let y=1.2;y<CR.H-.1;y+=1.1){ [-.55,.55].forEach(s=>{ A(crane,B(1.1,.07,.07),C.yellow,0,y,s); A(crane,B(.07,.07,1.1),C.yellow,s,y,0); }); }
const top=new THREE.Group(); top.position.y=CR.H; crane.add(top);
A(top,CYL(.8,.8,.35,12),C.dark,0,.18,0);
A(top,B(1.0,.9,1.1),C.blue,.1,.85,.95); A(top,B(.9,.4,.04),0xbfe0ff,.1,1.0,1.5);
const jib=new THREE.Group(); jib.position.y=.5; top.add(jib);
[.35,-.35].forEach(z=>{ A(jib,B(CR.L,.1,.1),C.yellow,CR.L/2,0,z); A(jib,B(4.6,.1,.1),C.yellow,-2.3,0,z); });
A(jib,B(CR.L,.08,.08),C.yellow,CR.L/2,1.0,0);
for(let x=0;x<CR.L-1;x+=1.25){ A(jib,B(.05,1.0,.05),C.yellow,x,.5,0); const len=Math.hypot(1.25,1.0); A(jib,B(len,.04,.04),C.yellow,x+.625,.5,0,0,0,((x/1.25|0)%2?1:-1)*Math.atan2(1.0,1.25)); }
A(jib,B(1.2,1.1,1.0),0x6b7a93,-3.9,-.2,0);                           // contrapeso
A(jib,B(.14,2.6,.14),C.yellow,0,1.5,0);                              // torre pluma
A(jib,B(Math.hypot(CR.L*.85,1.8),.03,.03),C.dark,CR.L*.425,1.9,0,0,0,-Math.atan2(1.8,CR.L*.85));
A(jib,B(Math.hypot(4.4,2.5),.03,.03),C.dark,-2.2,1.55,0,0,0,Math.atan2(2.5,4.4));
const craneLamp=new THREE.Mesh(new THREE.SphereGeometry(.12,10,8), new THREE.MeshBasicMaterial({color:0xff3b3b})); craneLamp.position.set(0,2.9,0); jib.add(craneLamp);
const trolley=new THREE.Group(); jib.add(trolley);
A(trolley,B(.5,.25,.8),C.dark,0,-.05,0);
const cable=new THREE.Mesh((()=>{const g=CYL(.025,.025,1,6); g.translate(0,-.5,0); return g;})(), M(C.dark)); cable.position.y=-.12; trolley.add(cable);
const hook=new THREE.Group(); trolley.add(hook);
A(hook,B(.32,.3,.22),C.orange,0,0,0); A(hook,CYL(.05,.05,.4,6),C.dark,0,-.3,0);
function makeBlocks(){
  const g=new THREE.Group();
  A(g,B(1.5,.14,1.5),0x9a7a4c,0,.07,0);
  for(let r=0;r<2;r++) for(let i=0;i<2;i++) for(let j=0;j<2;j++) A(g,B(.7,.45,.7),(i+j+r)%2?0xaab4c6:0x99a4b8,-.37+i*.74,.14+.225+r*.46,-.37+j*.74);
  return g;
}
const loadG=makeBlocks(); loadG.position.y=-1.6; hook.add(loadG);
[-1,1].forEach(s=>A(hook,B(.03,.9,.03),C.dark,s*.3,-.7,0,0,0,-s*.55));
const pickupPal=makeBlocks(); pickupPal.position.set(2.2,0,2.2); scene.add(pickupPal);
obstacle('base grúa',CR.x,CR.z,3.0,3.0); obstacle('pallet bloques',2.2,2.2,1.7,1.7);
const thP=Math.atan2(-(2.2-CR.z),2.2-CR.x), rP=Math.hypot(2.2-CR.x,2.2-CR.z);
const thD=Math.atan2(-(-1.5-CR.z),-3.0-CR.x), rD=Math.hypot(-3.0-CR.x,-1.5-CR.z);
const SEG=[
  {d:2.5,th:thP,r:rP,y:1.7,load:0},
  {d:3.4,th:thP,r:rP,y:9.4,load:1},
  {d:5.2,th:thD,r:rD,y:9.4,load:1},
  {d:2.6,th:thD,r:rD,y:7.85,load:1},
  {d:2.0,th:thD,r:rD,y:7.85,load:2},
  {d:1.6,th:thD,r:rD,y:9.4,load:0},
  {d:4.8,th:thP,r:rP,y:9.4,load:0},
  {d:3.2,th:thP,r:rP,y:1.7,load:0,restock:true},
];
const CR_TOTAL=SEG.reduce((a,s)=>a+s.d,0); let crT=0;
function craneStep(dt){
  crT=(crT+dt)%CR_TOTAL; let t=crT,i=0; while(t>SEG[i].d){ t-=SEG[i].d; i++; }
  const s=SEG[i], p=SEG[(i+SEG.length-1)%SEG.length], k=ease(clamp(t/s.d,0,1));
  top.rotation.y=lerp(p.th,s.th,k); trolley.position.x=lerp(p.r,s.r,k);
  const hy=lerp(p.y,s.y,k)-(CR.H+.5); hook.position.y=hy; cable.scale.y=Math.max(.05,-hy-.27);
  loadG.visible=s.load>0; loadG.scale.setScalar(s.load===2?Math.max(.001,1-k):1);
  const ps = s.restock ? k : (i===0 ? 1 : 0); pickupPal.visible=ps>.01; pickupPal.scale.setScalar(Math.max(.001,ps));
  craneLamp.visible = Math.sin(T*5)>-.2;
}

/* =====================================================================
   MATERIALES, MÁQUINAS Y PROPS
   ===================================================================== */
function pile(x,z,r,h,hex){ const m=mesh(CYL(.08,r,h,9),M(hex),x,h/2,z); scene.add(m); return m; }
pile(-.8,4.6,1.3,1.3,0xe0c48e); obstacle('arena',-.8,4.6,2.8,2.8);
pile(3.8,5.0,.9,.9,0x8b97ad);   obstacle('piedra',3.8,5.0,1.9,1.9);
const dumpPile=pile(13.1,-3.95,1.05,1.0,0xd1b98a); obstacle('pila volquete',13.1,-3.95,2.3,2.3);
// pallets de ladrillos
function brickPallet(x,z){ const g=new THREE.Group(); g.position.set(x,0,z); scene.add(g); A(g,B(1.3,.14,1.3),0x9a7a4c,0,.07,0);
  for(let r=0;r<4;r++) for(let i=0;i<2;i++) A(g,B(.6,.2,1.1),C.brick,-.3+i*.6,.24+r*.21,0); }
brickPallet(-5.5,4.2); brickPallet(-4.15,4.2); obstacle('ladrillos',-4.85,4.2,2.8,1.4);
// hormigonera
const mixer=new THREE.Group(); mixer.position.set(-8,0,4.6); scene.add(mixer);
A(mixer,B(1.3,.1,1.0),C.dark,0,.3,0); [[-.5,.4],[.5,.4],[-.5,-.4],[.5,-.4]].forEach(([a,b])=>A(mixer,B(.08,.3,.08),C.dark,a,.15,b));
A(mixer,B(.12,.7,.12),C.orange,0,.7,0);
const mixTilt=new THREE.Group(); mixTilt.position.set(0,1.05,0); mixTilt.rotation.z=.5; mixer.add(mixTilt);
const mixSpin=new THREE.Group(); mixTilt.add(mixSpin);
(()=>{ const g=CYL(.55,.55,.6,12); mixSpin.add(mesh(g,M(C.orange),0,0,0)); mixSpin.add(mesh(CYL(.55,.3,.45,12),M(C.orange),0,.52,0)); mixSpin.add(mesh(CYL(.3,.55,.35,12),M(0xd97706),0,-.45,0));
  for(let i=0;i<6;i++){ const a=i/6*Math.PI*2; A(mixSpin,B(.08,.55,.08),i%2?0xffffff:C.blue,Math.cos(a)*.56,0,Math.sin(a)*.56); } })();
obstacle('hormigonera',-8,4.6,1.8,1.8);
// comedor: mesa y dos bancos para el almuerzo (detrás del edificio, junto al contenedor)
const MESA={x:-6.5,z:-9.6};
A(scene,B(3.2,.08,.9),0x9a7a4c,MESA.x,.75,MESA.z); [[-1.4,-.3],[1.4,-.3],[-1.4,.3],[1.4,.3]].forEach(([a,b])=>A(scene,B(.08,.72,.08),C.dark,MESA.x+a,.37,MESA.z+b));
[-.75,.75].forEach(dz=>{ A(scene,B(3.2,.07,.32),0x8a6a3c,MESA.x,.43,MESA.z+dz); [-1.4,1.4].forEach(a=>A(scene,B(.08,.4,.25),C.dark,MESA.x+a,.2,MESA.z+dz)); });
const lunchProps=new THREE.Group(); scene.add(lunchProps); lunchProps.visible=false;
[[-1.1,-.22],[-.4,-.22],[.4,-.22],[1.1,-.22],[-1.1,.22],[0,.22],[1.1,.22]].forEach(([a,b],i)=>{
  lunchProps.add(mesh(CYL(.13,.11,.03,10),M(0xffffff),MESA.x+a,.8,MESA.z+b));
  lunchProps.add(mesh(B(.14,.06,.1),M(0xe0b060),MESA.x+a,.83,MESA.z+b));                         // sánguche
  if(i%2===0) lunchProps.add(mesh(CYL(.04,.04,.2,8),M(0x16a34a),MESA.x+a+.2,.88,MESA.z+b*.3));     // botella
});
obstacle('comedor',MESA.x,MESA.z,3.4,2.3);
// parrilla para el asado de los sábados
const PARRI={x:-9.3,z:-10.3};
(function(){ const g=new THREE.Group(); g.position.set(PARRI.x,0,PARRI.z); scene.add(g);
  [[-.3,-.18],[.3,-.18],[-.3,.18],[.3,.18]].forEach(([a,b])=>A(g,B(.06,.8,.06),C.dark,a,.4,b));
  A(g,B(.75,.2,.45),0x2b3245,0,.86,0);                                              // batea
  for(let i=-3;i<=3;i++) A(g,B(.02,.02,.42),C.metal,i*.1,.98,0,0,0,0,false);         // parrilla
  A(g,B(.75,.35,.04),0x2b3245,0,1.13,-.22);                                          // respaldo
})();
obstacle('parrilla',PARRI.x,PARRI.z,.9,.6);
const asadoProps=new THREE.Group(); scene.add(asadoProps); asadoProps.visible=false;
const brasas=new THREE.Mesh(B(.62,.03,.36), new THREE.MeshBasicMaterial({color:0xff6a00})); brasas.position.set(PARRI.x,.97,PARRI.z); asadoProps.add(brasas);
[[-.22,-.08],[.02,.1],[.22,-.06],[-.04,-.1]].forEach(([a,b],i)=>asadoProps.add(mesh(B(.17,.04,.1+(i%2)*.05),M(i%2?0x7f1d1d:0x9a3412),PARRI.x+a,1.01,PARRI.z+b)));
[-1.05,.05,1.1].forEach(a=>{ asadoProps.add(mesh(B(.42,.03,.26),M(0x9a7a4c),MESA.x+a,.8,MESA.z)); asadoProps.add(mesh(B(.26,.05,.14),M(0x9a3412),MESA.x+a,.83,MESA.z)); });
[[-.55,.05,0x4c0519],[.6,-.05,0x4c0519],[-1.4,.1,0xb91c1c],[1.45,-.1,0xb91c1c]].forEach(([a,b,col])=>asadoProps.add(mesh(CYL(.045,.05,.3,8),M(col),MESA.x+a,.94,MESA.z+b)));
[[-.3,-.05],[.35,.08]].forEach(([a,b])=>asadoProps.add(mesh(B(.22,.07,.12),M(0xe0b060),MESA.x+a,.82,MESA.z+b)));   // pan
const humo=[]; for(let i=0;i<10;i++){ const m=new THREE.Mesh(new THREE.SphereGeometry(.12,8,6), new THREE.MeshBasicMaterial({color:0xd1d5db,transparent:true,opacity:0,depthWrite:false})); m.userData.t=i/10; asadoProps.add(m); humo.push(m); }
function asadoStep(dt){
  asadoProps.visible = shift.phase==='asado';
  if(!asadoProps.visible) return;
  humo.forEach((m,i)=>{ const t=m.userData.t=(m.userData.t+dt*.32)%1; m.position.set(PARRI.x+Math.sin(t*6+i)*.15, 1.05+t*2.4, PARRI.z+t*.35); m.scale.setScalar(.6+t*2); m.material.opacity=(1-t)*.45; });
  brasas.material.color.setHSL(.06, 1, .45+Math.sin(T*7)*.06);
}
// contenedor oficina
(function(){ const g=new THREE.Group(); g.position.set(-12.2,0,-9.6); scene.add(g);
  A(g,B(4.6,2.4,2.0),C.blue,0,1.4,0); A(g,B(4.7,.1,2.1),C.navy,0,2.65,0);
  for(let i=-2;i<=2;i++) A(g,B(.06,2.3,2.04),0x1d4fc0,i*.9,1.4,0);
  A(g,B(.9,1.7,.06),0xffffff,-1.6,1.0,1.03); A(g,B(1.0,.7,.06),0xbfe0ff,.3,1.5,1.03); A(g,B(1.0,.7,.06),0xbfe0ff,1.6,1.5,1.03);
  A(g,B(1.0,.14,.6),C.dark,-1.6,.2,1.35); })();
obstacle('contenedor',-12.2,-9.6,4.8,2.2);
// tubos, torre de luz, baño químico
for(let r=0;r<3;r++) for(let i=0;i<3-r;i++) A(scene,CYL(.14,.14,3.2,8),0xc7d0df,-13.9+(i+r*.5)*.34,.15+r*.28,-2,Math.PI/2,0,0);
obstacle('tubos',-13.8,-2,1.2,3.4);
(function(){ const g=new THREE.Group(); g.position.set(-14,0,3.5); scene.add(g); A(g,B(.2,5,.2),C.dark,0,2.5,0); A(g,B(1.2,.7,.12),C.navy,0,5.2,0,0,.5,0);
  [-.4,0,.4].forEach(x=>{ const m=new THREE.Mesh(B(.28,.28,.05), towerLampMat); m.position.set(x*Math.cos(.5),5.2,x*Math.sin(.5)+.07); m.rotation.set(0,.5,0); g.add(m); }); })();
obstacle('torre luz',-14,3.5,.9,.9);
A(scene,B(1,2.2,1),C.sky,13.6,1.1,2.9); A(scene,B(1.04,.12,1.04),C.navy,13.6,2.26,2.9); A(scene,B(.4,1.4,.04),0xffffff,13.6,1.1,3.42); obstacle('baño',13.6,2.9,1.2,1.2);

/* ---------- garita del cuidador (en la entrada) ---------- */
const GAR={x:12.6,z:5.2};
const garita=new THREE.Group(); garita.position.set(GAR.x,0,GAR.z); scene.add(garita);
const glassMat=new THREE.MeshStandardMaterial({color:0xbfe0ff,transparent:true,opacity:.28,roughness:.1,metalness:.1,depthWrite:false});
(function(){ const g=garita;
  A(g,B(1.3,.08,1.2),0x9aa6b8,0,.04,0);
  A(g,B(1.3,2.1,.08),0xe8eef8,0,1.05,-.6);                         // pared del fondo
  A(g,B(.08,.95,1.2),0xe8eef8,.65,.475,0);                         // pared este (abajo de la ventana)
  A(g,B(1.3,.95,.08),0xe8eef8,0,.475,.6);                          // pared del frente (abajo de la ventana)
  A(g,B(.08,2.1,.5),0xe8eef8,-.65,1.05,-.35);                      // pared oeste, al lado de la puerta
  A(g,B(.08,.35,.7),0xe8eef8,-.65,1.93,.25);                       // dintel
  [[.65,1.5,0,.03,1.1,1.2],[0,1.5,.6,1.3,1.1,.03]].forEach(([x,y,z,w,h,d])=>{ const m=new THREE.Mesh(B(w,h,d),glassMat); m.position.set(x,y,z); g.add(m); });
  [[.65,2.07,0,.1,.08,1.24],[0,2.07,.6,1.34,.08,.1],[.65,.97,0,.1,.06,1.24],[0,.97,.6,1.34,.06,.1]].forEach(([x,y,z,w,h,d])=>A(g,B(w,h,d),C.navy,x,y,z));
  A(g,B(1.55,.14,1.45),C.navy,0,2.18,0);                           // techo
  A(g,B(1.32,.18,.04),C.orange,0,1.95,.63);                        // franja "SEGURIDAD"
  A(g,B(.42,.05,.8),0x9a7a4c,.38,.78,0); A(g,B(.06,.73,.06),C.dark,.38,.4,0);   // mesada
  A(g,CYL(.16,.16,.06,10),C.dark,-.08,.45,.05); A(g,CYL(.04,.04,.42,6),C.dark,-.08,.21,.05);   // banqueta
})();
obstacle('garita',GAR.x,GAR.z,1.5,1.4);
const garitaLight=new THREE.PointLight(0xffd9a0,0,4,1.6); garitaLight.position.set(GAR.x,1.8,GAR.z); scene.add(garitaLight);
// pozo + montículo de tierra
plane(2.6,2.2,13.1,-8.9,0x5b4a35,.02);
const mound=pile(12.2,-6.5,.9,.8,0xb9a176);
// balizas
function beacon(x,z){ const g=new THREE.Group(); g.position.set(x,0,z); scene.add(g);
  A(g,CYL(.1,.14,2.4,8),C.navy,0,1.2,0); A(g,CYL(.28,.28,.12,10),C.orange,0,.06,0);
  const head=new THREE.Group(); head.position.y=2.55; g.add(head);
  A(head,CYL(.2,.2,.3,10),0xfff1d0,0,0,0,0,0,0,false);
  const arm=new THREE.Group(); head.add(arm);
  [.32,-.32].forEach(x=>{ const b=new THREE.Mesh(new THREE.SphereGeometry(.13,10,8), new THREE.MeshBasicMaterial({color:0xffa400})); b.position.x=x; arm.add(b); });
  return arm; }
const beacons=[beacon(-14,-10.2),beacon(14,-10.2)];

/* ---------- excavadora ---------- */
const exc=new THREE.Group(); exc.position.set(9.3,0,-8.8); scene.add(exc);
[-.8,.8].forEach(z=>{ A(exc,B(2.8,.45,.55),C.dark,0,.25,z); [-1.4,1.4].forEach(x=>A(exc,CYL(.3,.3,.55,10),0x2b3245,x,.3,z,Math.PI/2,0,0)); });
const house=new THREE.Group(); house.position.y=.55; exc.add(house);
A(house,B(1.8,.5,1.6),C.orange,0,.35,0); A(house,B(.6,.6,1.5),0x39425c,-.85,.55,0);
A(house,B(.9,.95,.8),C.orange,.2,1.05,.4); A(house,B(.7,.5,.04),0xbfe0ff,.62,1.15,.4);
const boom=new THREE.Group(); boom.position.set(.8,.65,0); house.add(boom); A(boom,B(2.3,.3,.3),C.orange,1.15,0,0);
const stick=new THREE.Group(); stick.position.set(2.3,0,0); boom.add(stick); A(stick,B(1.7,.22,.22),0xd97706,.85,0,0);
const bucket=new THREE.Group(); bucket.position.set(1.7,0,0); stick.add(bucket); A(bucket,B(.5,.4,.7),C.dark,.22,-.18,0); A(bucket,B(.12,.14,.7),0x8b97ad,.5,-.34,0);
obstacle('excavadora',9.3,-8.8,3.6,2.4);
const EK=[ {t:0,yaw:0,b:.40,s:-1.25,k:-.55},{t:1.2,yaw:0,b:.40,s:-1.25,k:-1.5},{t:2.8,yaw:0,b:.95,s:-.85,k:-1.4},
           {t:4.4,yaw:-.6,b:.95,s:-.85,k:-1.4},{t:5.6,yaw:-.6,b:.95,s:-.85,k:-.2},{t:7.2,yaw:0,b:.7,s:-1.05,k:-.4},{t:8.4,yaw:0,b:.40,s:-1.25,k:-.55} ];
let excT=0;
function excStep(dt){
  excT+=dt; const t=excT%8.4; let i=0; while(t>EK[i+1].t) i++;
  const a=EK[i], b=EK[i+1], k=ease((t-a.t)/(b.t-a.t));
  house.rotation.y=lerp(a.yaw,b.yaw,k); boom.rotation.z=lerp(a.b,b.b,k); stick.rotation.z=lerp(a.s,b.s,k); bucket.rotation.z=lerp(a.k,b.k,k);
}

/* =====================================================================
   OBREROS
   ===================================================================== */
function buildPerson(o={}){
  const { vest=C.orange, pants=0x20283d, hat=0xffffff, skin=0xf1c27d, civil=false, hair=0x3b2a1a, mujer=false } = o;   // civil: sin casco ni franja reflectiva; mujer: pelo largo y pollera
  const g=new THREE.Group(), inner=new THREE.Group(); g.add(inner);
  const mk=(geo,col)=>{ const m=new THREE.Mesh(geo,M(col)); m.castShadow=true; return m; };
  const limb=(w,h,d,col,x,y)=>{ const p=new THREE.Group(); p.position.set(x,y,0); const m=mk(B(w,h,d),col); m.position.y=-h/2; p.add(m); inner.add(p); return p; };
  const legL=limb(.13,.45,.14,pants,-.09,.45), legR=limb(.13,.45,.14,pants,.09,.45);
  const armL=limb(.1,.42,.1,vest,-.26,.93), armR=limb(.1,.42,.1,vest,.26,.93);
  const torso=mk(B(.38,.5,.24),vest); torso.position.y=.7; inner.add(torso);
  if(!civil){ const strip=mk(B(.39,.06,.25),0xeaf3ff); strip.position.y=.72; inner.add(strip); }
  const head=mk(new THREE.IcosahedronGeometry(.14,1),skin); head.position.y=1.1; inner.add(head);
  if(civil){ const hr=mk(B(.27,.09,.27),hair); hr.position.y=1.21; inner.add(hr);
    if(mujer){ const largo=mk(B(.28,.36,.09),hair); largo.position.set(0,1.02,-.11); inner.add(largo);   // pelo largo
               const pollera=mk(B(.42,.3,.3),pants); pollera.position.y=.42; inner.add(pollera); } }
  else {
    const cap=mk(new THREE.SphereGeometry(.16,8,6,0,Math.PI*2,0,Math.PI/2),hat); cap.position.y=1.13; inner.add(cap);
    const brim=mk(CYL(.2,.2,.02,10),hat); brim.position.set(0,1.13,.03); inner.add(brim);
  }
  inner.scale.setScalar(1.5);
  g.userData={legL,legR,armL,armR,torso};
  scene.add(g); return g;
}
function setWalk(p,phase,amp){ const u=p.userData,s=Math.sin(phase)*amp; u.legL.rotation.x=s*.7; u.legR.rotation.x=-s*.7; u.armL.rotation.x=-s*.6; u.armR.rotation.x=s*.6; u.torso.position.y=.7+Math.abs(s)*.02; }
function turnTo(o,target,f){ o.rotation.y += Math.atan2(Math.sin(target-o.rotation.y),Math.cos(target-o.rotation.y))*Math.min(1,f); }
function addHammer(p){ const h=new THREE.Group(); h.position.set(0,-.44,0); const hd=mesh(B(.035,.3,.035),M(0x9a7a4c),0,-.05,0); h.add(hd); h.add(mesh(B(.17,.07,.07),M(C.dark),0,-.2,0)); p.userData.armR.add(h); }

/* red de caminos de los que caminan al azar */
const NODES=[], ADJ=[], nodeAt={};
function N(x,z){ const k=x+'_'+z; if(!(k in nodeAt)){ nodeAt[k]=NODES.length; NODES.push({x,z}); ADJ.push([]); } return nodeAt[k]; }
function chain(pts){ for(let i=0;i<pts.length-1;i++){ const a=N(...pts[i]), b=N(...pts[i+1]); ADJ[a].push(b); ADJ[b].push(a); } }
chain([[1.5,6.4],[-2.8,6.4],[-8,6.4],[-12.5,6.4],[-12.5,2.2],[-12.5,-2],[-12.5,-6],[-12.5,-7.9],[-8,-7.9],[-4,-7.9],[1.5,-7.9]]);
const walkers=[
  { obj:buildPerson({vest:C.orange,hat:0xffffff}), cur:N(-12.5,2.2), nxt:-1, prev:-1, p:0, len:1, wait:0, phase:0, amp:0, speed:1.1 },
  { obj:buildPerson({vest:0xfacc15,hat:C.orange,pants:0x2b3a67}), cur:N(-4,-7.9), nxt:-1, prev:-1, p:0, len:1, wait:0, phase:2, amp:0, speed:1.0 },
];
walkers.forEach(w=>w.obj.position.set(NODES[w.cur].x,0,NODES[w.cur].z));
function walkerStep(w,dt){
  if(w.nxt<0){
    if(w.wait>0) w.wait-=dt;
    else{
      let c=ADJ[w.cur].filter(n=>n!==w.prev); if(!c.length) c=ADJ[w.cur];
      w.nxt=c[Math.floor(Math.random()*c.length)];
      const a=NODES[w.cur], b=NODES[w.nxt]; w.len=Math.hypot(b.x-a.x,b.z-a.z); w.p=0;
    }
  } else {
    w.p+=dt*w.speed/w.len;
    const a=NODES[w.cur], b=NODES[w.nxt], t=Math.min(1,w.p);
    w.obj.position.set(a.x+(b.x-a.x)*t,0,a.z+(b.z-a.z)*t);
    turnTo(w.obj,Math.atan2(b.x-a.x,b.z-a.z),dt*8);
    if(w.p>=1){ w.prev=w.cur; w.cur=w.nxt; w.nxt=-1; w.wait=Math.random()<.45?.6+Math.random()*1.8:0; }
  }
  w.amp+=((w.nxt>=0?1:0)-w.amp)*Math.min(1,dt*8); if(w.nxt>=0) w.phase+=dt*6.2;
  setWalk(w.obj,w.phase,w.amp);
}

/* carretilla: va y viene entre la pila de arena y el muro */
const barrowMan=buildPerson({vest:0xdc2626,hat:0xffffff});
const wheelbarrow=new THREE.Group(); barrowMan.add(wheelbarrow); wheelbarrow.position.set(0,0,.95);
A(wheelbarrow,B(.55,.22,.7),C.blue,0,.45,.1); A(wheelbarrow,B(.5,.14,.6),0xe0c48e,0,.58,.1);
A(wheelbarrow,CYL(.17,.17,.07,10),C.dark,0,.17,.5,0,0,Math.PI/2); [-.22,.22].forEach(x=>A(wheelbarrow,B(.04,.04,.8),C.metal,x,.42,-.4));
const BARROW={ ax:-.2, bx:-7.2, z:2.4, p:0, state:'load', wait:1.5, phase:0, amp:0, load:false, face:Math.PI/2 };
function barrowStep(dt){
  const b=BARROW, len=Math.abs(b.bx-b.ax), u=barrowMan.userData;
  if(b.state==='load' || b.state==='unload'){
    b.wait-=dt; b.amp+=(0-b.amp)*Math.min(1,dt*8); setWalk(barrowMan,b.phase,b.amp);
    u.armR.rotation.x=-.5+Math.sin(T*7)*.5; u.armL.rotation.x=-.5;                       // palea / vuelca
    if(b.wait<=0){
      if(b.state==='load'){ b.load=true; b.state='toWall'; b.face=-Math.PI/2; } else { b.load=false; b.state='toSand'; b.face=Math.PI/2; }
    }
  } else {
    b.p+=(b.state==='toWall'?1:-1)*dt*1.0/len;
    if(b.p>=1){ b.p=1; b.state='unload'; b.wait=2.2; }
    if(b.p<=0){ b.p=0; b.state='load'; b.wait=2.6; }
    b.amp+=(1-b.amp)*Math.min(1,dt*8); b.phase+=dt*6;
    setWalk(barrowMan,b.phase,b.amp); u.armR.rotation.x=-.55; u.armL.rotation.x=-.55;
  }
  barrowMan.position.set(lerp(b.ax,b.bx,b.p),0,b.z);
  turnTo(barrowMan,b.face,dt*7);
  wheelbarrow.children[1].visible=b.load;
}

/* obreros fijos */
const bricklayer=buildPerson({vest:C.sky,hat:0xffffff}); bricklayer.position.set(-6.9,0,.15); bricklayer.rotation.y=0;
const hammer1=buildPerson({vest:C.orange,hat:C.yellow}); hammer1.position.set(-6.0,3.15,-2.0); hammer1.rotation.y=Math.PI*.8; addHammer(hammer1);
const hammer2=buildPerson({vest:0xfacc15,hat:0xffffff,pants:0x2b3a67}); hammer2.position.set(-1.8,6.15,-4.6); hammer2.rotation.y=-Math.PI/2; addHammer(hammer2);
const foreman=buildPerson({vest:0xffffff,hat:C.blue,pants:0x2b3a67}); foreman.position.set(5.8,0,6.0); foreman.rotation.y=Math.PI*.35;
(function(){ const cb=new THREE.Group(); cb.position.set(0,-.44,.05); cb.add(mesh(B(.2,.28,.03),M(0x9a7a4c),0,0,0)); cb.add(mesh(B(.17,.24,.035),M(0xffffff),0,0,.01)); foreman.userData.armL.add(cb); })();
const atPost=o=>!o.userData.crew || o.userData.crew.state==='post';
function staticStep(){
  const t=T; let u;
  // albañil: coloca ladrillos
  if(atPost(bricklayer)){ u=bricklayer.userData; u.armR.rotation.x=-1.0+Math.sin(t*3.2)*.35; u.armL.rotation.x=-.7+Math.sin(t*1.6)*.15; u.torso.position.y=.7+Math.abs(Math.sin(t*3.2))*.015; }
  // martilleros
  if(atPost(hammer1)){ u=hammer1.userData; u.armR.rotation.x=-1.1+Math.sin(t*6.5)*.8; u.armL.rotation.x=-.5; u.torso.position.y=.7+Math.abs(Math.sin(t*6.5))*.02; }
  if(atPost(hammer2)){ u=hammer2.userData; u.armR.rotation.x=-1.1+Math.sin(t*6.5+1.4)*.8; u.armL.rotation.x=-.5; u.torso.position.y=.7+Math.abs(Math.sin(t*6.5+1.4))*.02; }
  // capataz: mira pasar los camiones y señala
  if(atPost(foreman)){ foreman.rotation.y=Math.PI*.35+Math.sin(t*.5)*.5; u=foreman.userData; u.armL.rotation.x=-1.0; u.armR.rotation.x=Math.sin(t*.8)>.6?-2.4:-.1; }
}

/* =====================================================================
   VEHÍCULOS
   ===================================================================== */
function makeWheel(r,w){
  const g=new THREE.Group();
  const tire=mesh(CYL(r,r,w,12),M(0x161b27)); tire.rotation.z=Math.PI/2; g.add(tire);
  const hub=mesh(CYL(r*.55,r*.55,w+.02,10),M(0x8b97ad)); hub.rotation.z=Math.PI/2; g.add(hub);
  g.add(mesh(B(w+.05,.07,.12),M(0xffffff),0,r*.36,0,false));
  return g;
}
function truckBase(cabHex){
  const g=new THREE.Group(), wheels=[];
  A(g,B(1.2,.22,3.5),C.dark,0,.6,-.1);
  [[1.1,.38,0],[-.75,.38,0],[-1.35,.38,0]].forEach(([z,r])=>[-.62,.62].forEach(x=>{ const w=makeWheel(r,.3); w.position.set(x,r,z); g.add(w); wheels.push({g:w,r}); }));
  A(g,B(1.15,.85,.95),cabHex,0,1.2,1.15); A(g,B(1.15,.12,1.0),0xffffff,0,1.68,1.15);
  A(g,B(.95,.38,.03),0xbfe0ff,0,1.34,1.64); [-1,1].forEach(s=>A(g,B(.03,.34,.5),0xbfe0ff,s*.58,1.34,1.2));
  [-.4,.4].forEach(s=>A(g,B(.25,.12,.04),0xffe9a8,s,.82,1.66)); A(g,B(1.2,.14,.12),C.dark,0,.62,1.72);
  A(g,CYL(.04,.04,1.2,6),C.metal,-.62,1.7,.65);
  return {g,wheels};
}
function dumpTruck(){
  const {g,wheels}=truckBase(C.blue);
  const piv=new THREE.Group(); piv.position.set(0,.78,-1.8); g.add(piv);
  A(piv,B(1.35,.14,2.35),0xd97706,0,.05,1.18); A(piv,B(1.35,.55,.1),C.orange,0,.38,2.3); A(piv,B(.1,.55,2.35),C.orange,-.65,.38,1.18); A(piv,B(.1,.55,2.35),C.orange,.65,.38,1.18); A(piv,B(1.35,.55,.1),C.orange,0,.38,.06);
  for(let z=.4;z<2.2;z+=.7) [-1,1].forEach(s=>A(piv,B(.06,.5,.08),0xd97706,s*.7,.38,z));
  return { g, wheels, update(v){ piv.rotation.x=-.75*v.lift; } };
}
function mixerTruck(){
  const {g,wheels}=truckBase(C.orange);
  const tilt=new THREE.Group(); tilt.position.set(0,1.55,-.45); tilt.rotation.x=.28; g.add(tilt);
  const spin=new THREE.Group(); tilt.add(spin);
  const cy=CYL(.58,.58,1.5,12); cy.rotateX(Math.PI/2); spin.add(mesh(cy,M(0xf3f4f6)));
  const rear=CYL(.28,.58,.9,12); rear.rotateX(-Math.PI/2); spin.add(mesh(rear,M(0xf3f4f6),0,0,-1.2));
  const front=CYL(.35,.58,.5,12); front.rotateX(Math.PI/2); spin.add(mesh(front,M(0xf3f4f6),0,0,1.0));
  for(let i=0;i<8;i++){ const a=i/8*Math.PI*2; const f=mesh(B(.08,.08,1.55),M(i%2?C.blue:C.orange),Math.cos(a)*.6,Math.sin(a)*.6,0); f.rotation.z=a; spin.add(f); }
  A(g,B(.3,.15,.9),C.dark,0,.95,-1.9,.5,0,0);
  return { g, wheels, update(v,dt){ spin.rotation.z += dt*(1.1+v.lift*2.4); } };
}
function flatbed(kind){
  const {g,wheels}=truckBase(C.navy);
  A(g,B(1.3,.12,2.5),0x9a7a4c,0,.78,-.6); [-1,1].forEach(s=>[-1.6,-.6,.4].forEach(z=>A(g,B(.06,.5,.06),C.dark,s*.65,1.05,z)));
  if(kind==='pipes'){ [[-.4,.95],[-.13,.95],[.14,.95],[.41,.95],[-.27,1.1],[0,1.1],[.27,1.1]].forEach(([x,y])=>{ const p=CYL(.09,.09,2.3,8); p.rotateX(Math.PI/2); g.add(mesh(p,M(0xc7d0df),x,y,-.6)); });
    [-1.2,-.1].forEach(z=>A(g,B(1.1,.03,.06),C.blue,0,1.19,z)); }
  else { [[-.32,-1.2],[.32,-1.2],[-.32,-.1],[.32,-.1]].forEach(([x,z])=>{ A(g,B(.6,.4,.8),C.carton,x,1.04,z); A(g,B(.62,.03,.14),0xe9d3a6,x,1.25,z); }); A(g,B(.6,.4,.8),C.cartonDk,0,1.45,-.65); }
  return { g, wheels, update(){} };
}
/* rutas */
function makeRoute(pts){ const curve=new THREE.CatmullRomCurve3(pts.map(p=>new THREE.Vector3(p[0],0,p[1])),false,'centripetal'); return { curve, L:curve.getLength() }; }
const siteRoute=makeRoute([[18,8.5],[12.6,8.5],[11.4,8.1],[10.9,7.0],[10.8,5.0],[10.8,0],[10.8,-2.2],[10.3,-3.45],[9.05,-3.95],[7.8,-3.45],[7.3,-2.2],[7.3,0],[7.3,4],[7.3,7.0],[7.0,7.9],[6.2,8.5],[3,8.5],[-18,8.5]]);
(function(){ // parada = punto más cercano a la cima de la vuelta
  let best=1e9,bs=0; for(let i=0;i<=800;i++){ const p=siteRoute.curve.getPointAt(i/800); const d=Math.hypot(p.x-9.05,p.z+3.95); if(d<best){best=d;bs=i/800*siteRoute.L;} } siteRoute.sStop=bs; })();
const laneRoute=makeRoute([[-18,10.4],[0,10.4],[18,10.4]]);
function makeVehicle(model,route,o){
  const v={ ...model, route, s:o.s0, s0:o.s0, p0:!!(o.stop && o.s0>route.sStop), speed:o.speed, speedNow:0, pause:0, passed:!!(o.stop && o.s0>route.sStop), delay:o.delay||0, gap:o.gap||2, pauseDur:o.pauseDur||6, lift:0, stop:!!o.stop };
  scene.add(v.g); return v;
}
const vehicles=[
  makeVehicle(dumpTruck(), siteRoute,{s0:0, speed:3.0, stop:true, pauseDur:6.5}),
  makeVehicle(mixerTruck(),siteRoute,{s0:siteRoute.L*.5, speed:3.0, stop:true, pauseDur:6.5}),
  makeVehicle(flatbed('pipes'),laneRoute,{s0:6, speed:3.4, gap:2}),
  makeVehicle(flatbed('boxes'),laneRoute,{s0:laneRoute.L*.55, speed:3.4, gap:2}),
];
// camiones que entran a la obra: 40% menos frecuentes. Cada vuelta dura L/vel + descarga + espera;
// se alarga la espera para que la vuelta completa dure 1/0.6 de lo que duraba (frecuencia × 0.6).
vehicles.filter(v=>v.route===siteRoute).forEach(v=>{ const vuelta=siteRoute.L/v.speed+v.pauseDur+2; v.gap = vuelta/(0.6*0.6) - (siteRoute.L/v.speed+v.pauseDur); });   // dos rebajas del 40%
function vehicleStep(v,dt){
  const R=v.route;
  if(v.delay>0){ if(shift.phase==='work') v.delay-=dt; v.g.visible=false; return; }   // fuera de horario no sale ningún camión nuevo
  if(v.s<.05 && v.pause<=0 && trafficPool.some(o=>o.on && inCross(o.g.position.x,o.g.position.z,Math.sign(R.curve.getPointAt(0).x)))){ v.g.visible=false; return; }   // cruce ocupado por la calle lateral
  if(v.s<.05 && v.pause<=0 && R.curve.getPointAt(0).x<0 && semLuz('FRONT')!=='G'){ v.g.visible=false; return; }                                      // semáforo en rojo en la esquina izquierda
  if(shift.phase==='asado' && (v.route!==siteRoute || v.s<siteRoute.sEntry)){ v.s=0; v.passed=false; v.pause=0; v.lift=0; v.speedNow=0; v.delay=1; v.g.visible=false; return; }   // asado: obra clausurada, no circulan camiones de la obra
  v.g.visible=true;
  if(v.pause>0){
    v.pause-=dt; v.speedNow=0; const e=v.pauseDur-v.pause;
    v.lift = e<1.6 ? ease(e/1.6) : (v.pause<1.6 ? ease(v.pause/1.6) : 1);
    if(v.pause<=0){ v.passed=true; v.lift=0; }
  } else {
    let target=v.speed;
    if(v.stop && !v.passed){ const dist=R.sStop-v.s; target=v.speed*Math.min(1,.2+dist/4.5); }
    { // frena si hay una persona (obrero que va/viene del bus o el cuidador) adelante
      const uu=clamp(v.s/R.L,0,1), tt=R.curve.getTangentAt(uu), pp=v.g.position, hit=o=>{ const dx=o.position.x-pp.x, dz=o.position.z-pp.z, f=dx*tt.x+dz*tt.z, l=Math.abs(dx*tt.z-dz*tt.x); return f>-.5 && f<3.8 && l<1.15; };
      if(crew.some(c=>(c.state==='toBus'||c.state==='fromBus') && c.obj.visible && hit(c.obj)) || (guard.visible && G.state!=='rest' && hit(guard))) target=0;
    }
    { // semáforo de la esquina izquierda (los que van hacia -x frenan antes del cruce)
      const tt=R.curve.getTangentAt(clamp(v.s/R.L,0,1)), st=semLuz('FRONT'), dist=v.g.position.x-(-16.4+2.1);
      if(tt.x<-.5 && st!=='G' && dist>-.05 && dist<5 && !(st==='Y' && dist<1.2)) target=Math.min(target, v.speed*clamp((dist-.1)/4,0,1));
    }
    { const gap=obstaculoAdelante(v.g.position,R.curve.getTangentAt(clamp(v.s/R.L,0,1)),3.6,null); if(gap<1.4) target=0; else if(gap<5) target=Math.min(target,v.speed*(gap-1.4)/3.6); }   // auto adelante
    v.speedNow+=clamp(target-v.speedNow,-4*dt,2.6*dt);
    v.s+=v.speedNow*dt;
    if(v.stop && !v.passed && v.s>=R.sStop-.03){ v.s=R.sStop; v.speedNow=0; v.pause=v.pauseDur; v.lift=0; }
    if(v.s>=R.L){ v.s=0; v.passed=false; v.speedNow=0; v.delay=v.gap; v.g.visible=false; return; }
  }
  const u=clamp(v.s/R.L,0,1), p=R.curve.getPointAt(u), t=R.curve.getTangentAt(u);
  v.g.position.set(p.x,0,p.z); v.g.rotation.y=Math.atan2(t.x,t.z);
  v.g.scale.setScalar(Math.max(.001,ease(clamp(Math.min(v.s,R.L-v.s)/2.4,0,1))));
  v.wheels.forEach(w=>{ w.g.rotation.x+=v.speedNow*dt/w.r; });
  v.update(v,dt);
}

/* =====================================================================
   HORARIO: turno de día · 13 hs almuerzo (40 min) · 18 hs salida en bus · cuidador con perro
            · 19 hs gato en los techos · 20 hs noche · 22 hs delivery de pizza al cuidador (30 min)
            · sábados hasta las 13 + asado 13 a 15 · domingos y feriados de Argentina no se trabaja
            ?fecha=2026-11-14 elige el día; el reloj de la esquina se arrastra para cambiar la hora
            · de 23 a 6: sin peatones, muy pocos autos y de vez en cuando un patrullero
            · los autos eligen al azar en cada cruce: seguir de largo o doblar (pueden dar la vuelta a la manzana)
            · el gato: de día anda por su casa (la de techo rojo); de noche por los techos de la obra y a veces la visita
            · barrio de atrás: luces según día y hora, cada hora entra el dueño de cada casa,
              el auto de la casa moderna sale del garage a las :15 y vuelve a las :40
   Usa la hora local del navegador. Para probar:  ?hora=17:58  (arranca a esa hora)  &vel=30 (acelera el reloj)
   Configurable:  window.MANTENIMIENTO = { horario:{ inicio:6, salida:18, noche:20, almuerzo:13, almuerzoMin:40, gato:19, pizza:22, pizzaMin:30 } }
   ===================================================================== */
const HOR = Object.assign({ inicio:6, salida:18, noche:20, almuerzo:13, almuerzoMin:40, gato:19, pizza:22, pizzaMin:30, asado:13, asadoFin:15 }, (window.MANTENIMIENTO && window.MANTENIMIENTO.horario) || {});
const qs0 = new URLSearchParams(location.search);
function parseHora(s){ const m=/^(\d{1,2})(?::(\d{2}))?$/.exec(s||''); return m ? (+m[1] + (+m[2]||0)/60) : null; }
const hBase=parseHora(qs0.get('hora')), VEL=Math.max(1, parseFloat(qs0.get('vel'))||1), T0=Date.now();
let hourOverride=null;
const qFecha=/^(\d{4})-(\d{2})-(\d{2})$/.exec(qs0.get('fecha')||'');   // ?fecha=2026-11-14 para probar un día puntual
let OFF=0;                                                               // corrimiento elegido con el reloj (ms)
function simNow(){
  let ms;
  if(hBase!==null || VEL!==1 || qFecha){
    const d0=new Date(T0), hoy0=new Date(d0.getFullYear(),d0.getMonth(),d0.getDate()).getTime();
    const dia = qFecha ? new Date(+qFecha[1],+qFecha[2]-1,+qFecha[3]).getTime() : hoy0;
    ms = dia + (hBase!==null ? hBase*3600000 : T0-hoy0) + (Date.now()-T0)*VEL;
  } else ms=Date.now();
  const d=new Date(ms+OFF);
  if(hourOverride!==null){ d.setHours(0,0,0,0); return new Date(d.getTime()+hourOverride*3600000); }
  return d;
}
const hourOf = d => d.getHours()+d.getMinutes()/60+d.getSeconds()/3600+d.getMilliseconds()/3600000;
function hourNow(){ return hourOf(simNow()); }

/* ---------- calendario: feriados nacionales de Argentina ----------
   Inamovibles + Carnaval y Viernes Santo (según Pascua) + trasladables (Ley 27.399:
   martes/miércoles → lunes anterior; jueves/viernes → lunes siguiente).
   Los "días no laborables con fines turísticos" salen por decreto cada año: se agregan con
   window.MANTENIMIENTO = { feriados:['2026-07-10', ...] }                                  */
const FERIADOS_EXTRA=(window.MANTENIMIENTO && window.MANTENIMIENTO.feriados) || [];
const claveDia = d => d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');
function pascua(y){
  const a=y%19,b=Math.floor(y/100),c=y%100,d=Math.floor(b/4),e=b%4,f=Math.floor((b+8)/25),g=Math.floor((b-f+1)/3),
        h=(19*a+b-d-g+15)%30,i=Math.floor(c/4),k=c%4,l=(32+2*e+2*i-h-k)%7,m=Math.floor((a+11*h+22*l)/451),
        mes=Math.floor((h+l-7*m+114)/31),dia=((h+l-7*m+114)%31)+1;
  return new Date(y,mes-1,dia);
}
const _feriados={};
function feriadosDe(y){
  if(_feriados[y]) return _feriados[y];
  const F={}, add=(d,n)=>{ F[claveDia(d)]=n; };
  [[1,1,'Año Nuevo'],[3,24,'Día de la Memoria'],[4,2,'Día de los Veteranos de Malvinas'],[5,1,'Día del Trabajador'],[5,25,'Revolución de Mayo'],
   [6,20,'Día de la Bandera'],[7,9,'Día de la Independencia'],[12,8,'Inmaculada Concepción'],[12,25,'Navidad']].forEach(([m,d,n])=>add(new Date(y,m-1,d),n));
  const p=pascua(y), pas=n=>new Date(p.getFullYear(),p.getMonth(),p.getDate()+n);
  add(pas(-48),'Carnaval'); add(pas(-47),'Carnaval'); add(pas(-2),'Viernes Santo');
  [[6,17,'Paso a la Inmortalidad de Güemes'],[8,17,'Paso a la Inmortalidad de San Martín'],[10,12,'Día del Respeto a la Diversidad Cultural'],[11,20,'Día de la Soberanía Nacional']]
    .forEach(([m,d,n])=>{ const w=new Date(y,m-1,d).getDay(), mov = w===2?-1 : w===3?-2 : w===4?4 : w===5?3 : 0; add(new Date(y,m-1,d+mov),n); });
  FERIADOS_EXTRA.forEach(k=>{ if(!F[k]) F[k]='Feriado'; });
  return (_feriados[y]=F);
}
function tipoDia(d){
  const f=feriadosDe(d.getFullYear())[claveDia(d)];
  if(f) return {tipo:'feriado', nombre:f};
  const w=d.getDay(); return {tipo: w===0?'domingo' : w===6?'sabado' : 'habil'};
}
// qué debería estar pasando en la obra en ese momento
function wantAt(d){
  const h=hourOf(d), t=tipoDia(d).tipo;
  if(t==='feriado' || t==='domingo' || h<HOR.inicio) return 'off';
  if(t==='sabado') return h<HOR.asado ? 'work' : (h<HOR.asadoFin ? 'asado' : 'off');
  if(h>=HOR.salida) return 'off';
  return (h>=HOR.almuerzo && h<HOR.almuerzo+HOR.almuerzoMin/60) ? 'lunch' : 'work';
}
const isWork = h => h>=HOR.inicio && h<HOR.salida;
const RAMP=1/3;                                       // la noche cae en 20 min (y el amanecer también)
function nightK(h){
  if(h>=HOR.noche) return clamp((h-HOR.noche)/RAMP,0,1);
  if(h<HOR.inicio) return clamp(1-(h-(HOR.inicio-RAMP))/RAMP,0,1);
  return 0;
}
const duskK = h => (h>=HOR.salida && h<HOR.noche+RAMP) ? clamp((h-HOR.salida)/(HOR.noche-HOR.salida),0,1) : (h>=HOR.noche+RAMP?1:0);

/* ---------- luces nocturnas (siempre presentes, de día con intensidad 0: así no se recompilan shaders) ---------- */
const towerSpot=new THREE.SpotLight(0xfff0c8,0,26,1.15,.9,1.1); towerSpot.position.set(-13.7,5.1,3.9); towerSpot.target.position.set(-9.5,0,5.2); scene.add(towerSpot,towerSpot.target);
const gateGlow=new THREE.PointLight(0xffc27a,0,11,1.4); gateGlow.position.set(11,2.9,6.0); scene.add(gateGlow);
const guardSpot=new THREE.SpotLight(0xfff4cf,0,15,.42,.55,1.2);

/* ---------- barrera: abre/cierra ---------- */
function barrierStep(dt,n){
  barriers.forEach(b=>{
    b.k=clamp(b.k+Math.sign(b.target-b.k)*Math.min(Math.abs(b.target-b.k),dt*.55),0,1);
    b.arm.rotation.z=-(1-ease(b.k))*1.45;
    b.lamp.visible = b.k>.5 && (n>.3 ? Math.sin(T*5)>0 : true);
  });
}

/* ---------- bus que se lleva a los obreros ---------- */
const BUS_X=11.6, BUS_Z=8.5, DOOR={x:BUS_X-.9, z:BUS_Z-.66};
function buildBus(){
  const g=new THREE.Group(), wheels=[];
  A(g,B(1.3,.2,4.3),C.dark,0,.52,0);
  A(g,B(1.36,.55,4.4),C.navy,0,.88,0);
  A(g,B(1.32,.82,4.3),C.blue,0,1.56,0);
  A(g,B(1.38,.1,4.45),0xffffff,0,2.0,0);
  A(g,B(1.38,.12,4.42),C.orange,0,1.17,0);
  [-1,1].forEach(s=>A(g,B(.03,.42,3.7),0xbfe0ff,s*.665,1.6,-.1));
  A(g,B(1.1,.5,.03),0xbfe0ff,0,1.6,2.16);
  A(g,B(.05,.9,.75),0x161b27,-.67,1.22,1.45);                       // puerta (lado de la obra)
  [-.4,.4].forEach(s=>A(g,B(.2,.12,.04),0xffe9a8,s,.8,2.21)); [-.45,.45].forEach(s=>A(g,B(.18,.1,.04),0xdc2626,s,.8,-2.21));
  A(g,B(.9,.12,.03),0xffffff,0,2.0,2.2);
  [[1.45],[-1.3]].forEach(([z])=>[-.62,.62].forEach(x=>{ const w=makeWheel(.38,.3); w.position.set(x,.38,z); g.add(w); wheels.push({g:w,r:.38}); }));
  scene.add(g); g.visible=false; g.rotation.y=-Math.PI/2; return {g,wheels};
}
const bus=Object.assign(buildBus(),{ x:18, speed:0, state:'gone', want:null, parkT:0, cool:0, departed:false });
function busStep(dt){
  const b=bus; b.cool=Math.max(0,b.cool-dt);
  if(b.state==='gone'){ b.g.visible=false; return; }
  if(b.state==='arriving'){
    b.speed=Math.min(3.4,.5+(b.x-BUS_X)*1.0); b.x-=b.speed*dt;
    if(b.x<=BUS_X+.02){ b.x=BUS_X; b.speed=0; b.state='parked'; b.parkT=0; }
  } else if(b.state==='leaving'){
    b.speed=Math.min(3.4,b.speed+2.2*dt);
    { const gap=obstaculoAdelante(b.g.position,new THREE.Vector3(-1,0,0),4.4,null); if(gap<1.4) b.speed=0; else if(gap<5) b.speed=Math.min(b.speed,3.4*(gap-1.4)/3.6); }
    { const st=semLuz('FRONT'), dist=b.x-(-14.0); if(st!=='G' && dist>-.05 && dist<5 && !(st==='Y' && dist<1.5)) b.speed=Math.min(b.speed,3.4*clamp((dist-.1)/4,0,1)); }
    b.x-=b.speed*dt;
    if(b.x<-17.6){ b.state='gone'; b.g.visible=false; return; }
  }
  b.g.visible=true; b.g.position.set(b.x,0,BUS_Z);
  b.g.scale.setScalar(Math.max(.001,ease(clamp(Math.min((18.2-b.x)/2.4,(b.x+18)/2.4),0,1))));
  b.wheels.forEach(w=>{ w.g.rotation.x+=b.speed*dt/w.r; });
}
// tramos de la ruta de camiones: para saber si la entrada/salida está libre
(function(){ let e=null,x=null; for(let i=0;i<=1600;i++){ const p=siteRoute.curve.getPointAt(i/1600), s=i/1600*siteRoute.L; if(e===null && p.x<=12.6) e=s; if(p.x<=3 && p.z>8){ x=s; break; } } siteRoute.sEntry=e; siteRoute.sOut=x; })();
const siteVeh = () => vehicles.filter(v=>v.route===siteRoute && v.g.visible && v.delay<=0);
const entryClear = () => siteVeh().every(v=>v.s > siteRoute.sEntry+4.5);       // ningún camión llegando al portón
const siteBusy   = () => siteVeh().some(v=>v.s < siteRoute.sOut);              // algún camión todavía dentro / saliendo
function blockedByVehicle(x,z){ return siteVeh().some(v=>v.g.scale.x>.5 && Math.hypot(v.g.position.x-x,v.g.position.z-z)<2.7); }

/* ---------- cuidador + perro ---------- */
const guard=buildPerson({vest:0x3b4a73,pants:0x1f2937,hat:0x1f2937}); guard.visible=false;
const flash=new THREE.Group(); flash.position.set(0,-.44,0); guard.userData.armR.add(flash);
flash.add(mesh(CYL(.04,.05,.22,8),M(0x20283d),0,-.05,0));
flash.add(new THREE.Mesh(CYL(.065,.045,.04,8), new THREE.MeshBasicMaterial({color:0xfff1b0})));  flash.children[1].position.y=-.17;
const beam=new THREE.Mesh(new THREE.ConeGeometry(.9,3.6,20,1,true), new THREE.MeshBasicMaterial({color:0xfff2b0,transparent:true,opacity:0,depthWrite:false,blending:THREE.AdditiveBlending,side:THREE.DoubleSide}));
beam.position.y=-.19-1.8; flash.add(beam);
guardSpot.position.set(0,-.2,0); flash.add(guardSpot); const spotTarget=new THREE.Object3D(); spotTarget.position.set(0,-4,0); flash.add(spotTarget); guardSpot.target=spotTarget;

function buildDog(){
  const g=new THREE.Group(), inner=new THREE.Group(); g.add(inner); inner.scale.setScalar(1.35);
  const mk=(geo,col,x,y,z,p)=>{ const m=new THREE.Mesh(geo,M(col)); m.position.set(x,y,z); m.castShadow=true; p.add(m); return m; };
  const body=new THREE.Group(); inner.add(body);
  mk(B(.3,.26,.6),0xc98d4b,0,.44,0,body); mk(B(.24,.14,.18),0xf3ead8,0,.38,.26,body);
  const head=new THREE.Group(); head.position.set(0,.6,.34); body.add(head);
  mk(B(.2,.2,.2),0xc98d4b,0,0,0,head); mk(B(.12,.1,.16),0xe4b277,0,-.04,.15,head); mk(B(.05,.04,.04),0x111827,0,0,.24,head);
  mk(B(.06,.12,.05),0x7a4a22,-.1,.12,-.02,head); mk(B(.06,.12,.05),0x7a4a22,.1,.12,-.02,head);
  const tail=new THREE.Group(); tail.position.set(0,.5,-.3); tail.rotation.x=-.5; body.add(tail); mk(B(.05,.05,.26),0xc98d4b,0,0,-.13,tail);
  const legs=[[-.1,.22],[.1,.22],[-.1,-.22],[.1,-.22]].map(([x,z])=>{ const p=new THREE.Group(); p.position.set(x,.32,z); inner.add(p); mk(B(.07,.32,.07),0xb87a3d,0,-.16,0,p); return p; });
  scene.add(g); g.visible=false; return { g, body, head, tail, legs, phase:0, sit:0 };
}
const dog=buildDog();

const REST=[11.8,5.6];
const EMERGE=[[-13.8,-8.2],[-12.5,-7.9],[-12.5,-6],[-12.5,-2],[-12.5,2.2],[-12.5,6.4],[-8,6.4],[-2.8,6.4],[1.5,6.4],[6,6.4],[11.0,6.2],REST];
const PATROL=[[11.0,6.2],[6,6.4],[1.5,6.4],[-2.8,6.4],[-8,6.4],[-12.5,6.4,1.4],[-12.5,2.2],[-12.5,-2],[-12.5,-6],[-12.5,-7.9,1.4],[-8,-7.9],[-4,-7.9],[1.5,-7.9],[6,-7.9,1.4],[6,-4],[6,0],[6,4.5,1.2],[6,5.6],REST];
const PATROL_EVERY=(window.MANTENIMIENTO&&window.MANTENIMIENTO.patrullaCadaSeg)||120;
const G={ state:'hidden', path:[], idx:0, wait:0, hold:0, speed:1.15, since:0, goHome:false, armK:0, amp:0, phase:0 };
function guardGo(path,state){ G.path=path; G.idx=0; G.wait=0; G.hold=0; G.state=state; }
function guardAppear(){ guard.visible=dog.g.visible=true; guard.position.set(EMERGE[0][0],0,EMERGE[0][1]); guard.rotation.y=0; dog.g.position.set(EMERGE[0][0]+.1,0,EMERGE[0][1]+.55); G.since=PATROL_EVERY-15; G.goHome=false; guardGo(EMERGE.slice(1),'emerge'); }
function followPath(dt){
  if(G.wait>0){ G.wait-=dt; return 'wait'; }
  const t=G.path[G.idx]; if(!t) return 'done';
  const o=guard, dx=t[0]-o.position.x, dz=t[1]-o.position.z, d=Math.hypot(dx,dz), st=G.speed*dt;
  if(d<=st){ o.position.x=t[0]; o.position.z=t[1]; G.idx++; if(t[2]) G.wait=t[2]; return 'moving'; }
  const nx=o.position.x+dx/d*st, nz=o.position.z+dz/d*st;
  if(blockedByVehicle(nx,nz) && G.hold<6){ G.hold+=dt; return 'wait'; }
  G.hold=0; o.position.x=nx; o.position.z=nz; turnTo(o,Math.atan2(dx,dz),dt*8); return 'moving';
}
let nightNow=0;
function pushOut(p,m=.25){   // saca al perro de los obstáculos (contenedor, pilas, etc.)
  for(const o of obstacles){
    if(p.x>o.x0-m&&p.x<o.x1+m&&p.z>o.z0-m&&p.z<o.z1+m){
      const dl=p.x-(o.x0-m), dr=(o.x1+m)-p.x, dt=p.z-(o.z0-m), db=(o.z1+m)-p.z, mn=Math.min(dl,dr,dt,db);
      if(mn===dl) p.x=o.x0-m; else if(mn===dr) p.x=o.x1+m; else if(mn===dt) p.z=o.z0-m; else p.z=o.z1+m;
    }
  }
}
function guardStep(dt){
  if(G.state==='hidden'){ guard.visible=dog.g.visible=false; guardSpot.intensity=0; return; }
  guard.visible=dog.g.visible=true; G.since+=dt;
  let moving=false;
  const WALKS=['emerge','patrol','home','pizzaGo','pizzaIn','pizzaOut'];
  if(WALKS.includes(G.state)){
    const r=followPath(dt); moving=(r==='moving');
    if(r==='done'){
      if(G.state==='home'){ G.state='hidden'; return; }
      if(G.state==='pizzaGo') G.state='pizzaWait';                          // espera la moto en el portón
      else if(G.state==='pizzaIn'){ G.state='pizzaEat'; G.eatFrom=hourNow(); }   // se sienta a comer en la garita
      else { const vieneDeComer=(G.state==='pizzaOut'); G.state='rest'; if(vieneDeComer) G.since=Math.min(G.since,PATROL_EVERY-20); }
    }
  } else if(G.state==='rest'){
    if(G.goHome){ guardGo(EMERGE.slice().reverse(),'home'); }
    else if(G.since>=PATROL_EVERY){ G.since=0; guardGo(PATROL.slice(),'patrol'); }
    else turnTo(guard, Math.sin(T*.4)*(nightNow>.5?.9:.5), dt*2);
  } else if(G.state==='pizzaWait'){
    turnTo(guard,0,dt*6);
  } else if(G.state==='pizzaEat'){
    guard.position.y+=(-.18-guard.position.y)*Math.min(1,dt*6); turnTo(guard,Math.PI/2,dt*8);
    const uu=guard.userData; uu.legL.rotation.x=uu.legR.rotation.x=-1.45;
    uu.armR.rotation.x=-1.0+Math.sin(T*1.6)*.5; uu.armL.rotation.x=-.75; uu.torso.position.y=.7;
    if(hourDiff(hourNow(),G.eatFrom)>=HOR.pizzaMin/60){ pizza.visible=false; guardGo([[12.0,5.4],[11.75,5.45],[REST[0],REST[1]]],'pizzaOut'); }
  }
  if(G.state!=='pizzaEat'){
    guard.position.y+=(0-guard.position.y)*Math.min(1,dt*8);
    G.amp+=((moving?1:0)-G.amp)*Math.min(1,dt*8); if(moving) G.phase+=dt*5.6;
    setWalk(guard,G.phase,G.amp);
  }
  // linterna: se prende al caer la noche (no mientras come)
  const lightOn = nightNow>.5 && G.state!=='pizzaEat';
  G.armK+=((lightOn?1:0)-G.armK)*Math.min(1,dt*3);
  const u=guard.userData; if(G.state!=='pizzaEat') u.armR.rotation.x=lerp(u.armR.rotation.x,-1.0,G.armK);
  if(G.state==='pizzaWait'||G.state==='pizzaIn') u.armL.rotation.x=-1.05;     // recibe / lleva la pizza
  guardSpot.intensity=G.armK*120; beam.material.opacity=G.armK*.24;
  // perro
  const D=dog; let tx,tz;
  if(G.state==='rest'||G.state==='pizzaEat'){ tx=REST[0]-.2; tz=REST[1]+.75; }     // echado afuera de la garita
  else if(G.state==='pizzaWait'){ tx=MEET.x-.7; tz=MEET.z-.25; }
  else { const a=guard.rotation.y; tx=guard.position.x-Math.sin(a)*.95; tz=guard.position.z-Math.cos(a)*.95; }
  const dx=tx-D.g.position.x, dz=tz-D.g.position.z, d=Math.hypot(dx,dz); let dm=false;
  if(d>.12){ const sp=Math.min(2.8,d*3.2+.2), st=Math.min(d,sp*dt); D.g.position.x+=dx/d*st; D.g.position.z+=dz/d*st; turnTo(D.g,Math.atan2(dx,dz),dt*9); dm=true; D.phase+=dt*sp*3.4; }
  D.sit+=((dm?0:1)-D.sit)*Math.min(1,dt*3);
  const sw=dm?Math.sin(D.phase)*.7:0; D.legs[0].rotation.x=sw; D.legs[3].rotation.x=sw; D.legs[1].rotation.x=-sw; D.legs[2].rotation.x=-sw;
  pushOut(D.g.position);
  D.body.position.y=-.09*D.sit; D.legs[2].rotation.x+=D.sit*-.9; D.legs[3].rotation.x+=D.sit*-.9;
  D.tail.rotation.y=Math.sin(T*(dm?14:7))*.55; D.head.rotation.y=dm?0:Math.sin(T*.8)*.5;
}


/* =====================================================================
   GATO NARANJA: fuera de horario (desde las 19 hs) camina y salta por los techos
   ===================================================================== */
function buildCat(){
  const g=new THREE.Group(), inner=new THREE.Group(); g.add(inner); inner.scale.setScalar(1.25);
  const O=0xf28c28, Dk=0xc8670f, W=0xfff1dc;
  const mk=(geo,col,x,y,z,p)=>{ const m=new THREE.Mesh(geo,M(col)); m.position.set(x,y,z); m.castShadow=true; p.add(m); return m; };
  const body=new THREE.Group(); inner.add(body);
  mk(B(.16,.15,.38),O,0,.22,0,body);
  [-.1,0,.1].forEach(z=>mk(B(.165,.03,.05),Dk,0,.3,z,body));                       // rayas
  mk(B(.12,.06,.16),W,0,.15,.08,body);                                              // panza
  const head=new THREE.Group(); head.position.set(0,.33,.22); body.add(head);
  mk(B(.15,.13,.13),O,0,0,0,head); mk(B(.08,.05,.05),W,0,-.03,.07,head);
  [-.045,.045].forEach(x=>{ const e=mk(new THREE.ConeGeometry(.035,.07,4),O,x,.09,-.01,head); e.rotation.y=Math.PI/4; });
  [-.035,.035].forEach(x=>mk(B(.025,.025,.01),0x14532d,x,.02,.066,head));            // ojos
  const tail=new THREE.Group(); tail.position.set(0,.27,-.19); body.add(tail);
  mk(B(.04,.04,.2),O,0,0,-.1,tail);
  const tail2=new THREE.Group(); tail2.position.set(0,0,-.2); tail.add(tail2); mk(B(.035,.035,.16),Dk,0,0,-.08,tail2);
  const legs=[[-.05,.13],[.05,.13],[-.05,-.13],[.05,-.13]].map(([x,z])=>{ const q=new THREE.Group(); q.position.set(x,.16,z); inner.add(q); mk(B(.04,.16,.04),O,0,-.08,0,q); return q; });
  scene.add(g); g.visible=false; return { g, body, head, tail, tail2, legs };
}
const cat=buildCat();
const CN=[ // nodos [x, altura del techo, z]
  [-13.7,2.72,-9.6],[-10.7,2.72,-9.2],                                               // 0-1 techo del contenedor
  [-8.5,3.15,-6.5],[-6.5,3.15,-6.5],[-6.5,3.15,-5.0],[-.2,3.15,-5.0],[-.2,3.15,.5],  // 2-6 losa 1
  [-9.72,4.58,-5.5],                                                                  // 7 tablón del andamio
  [-8.4,6.15,-5.5],[-5.5,6.15,-5.2],[-1.6,6.15,-5.4],[-6.5,6.15,.3],                 // 8-11 losa 2
];
const CE=[[0,1,0],[1,2,1],[2,3,0],[3,4,0],[4,5,0],[5,6,0],[2,7,1],[7,8,1],[8,9,0],[9,10,0],[9,11,0],[8,11,0],[5,10,1]];   // [a, b, ¿salto?]
const CADJ=CN.map(()=>[]); CE.forEach(([a,b,j])=>{ CADJ[a].push({n:b,j}); CADJ[b].push({n:a,j}); });
const K={ node:0, prev:-1, mode:'sit', t:0, dur:0, from:null, to:null, jump:false, wait:2, crouch:0, phase:0, sc:1, queue:[], vis:1, cross:false, zona:'obra', init:false };
// su casa (la de techo rojo): de día anda por el jardín, el árbol y el techo; a veces se cruza al chalet vecino
const CD=[ [-12.0,.08,-19.35],[-14.3,.07,-19.1],[-10.4,.07,-19.0],[-14.7,.07,-21.0],[-14.6,.07,-24.1],[-15.2,2.7,-22.5],
           [-12.0,4.1,-22.6],[-12.0,4.1,-20.3],[-8.2,.07,-18.7],[-6.6,.07,-18.9],[-12.0,.07,-24.1] ];
// 0 puerta · 1/2 frente · 3 costado · 4/10 fondo · 5 copa del árbol · 6/7 cumbrera del techo · 8/9 jardín del vecino
const CDE=[[0,1,0],[0,2,0],[1,3,0],[3,4,0],[4,10,0],[3,5,1],[5,6,1],[6,7,0],[2,8,0],[8,9,0]];
const CDADJ=CD.map(()=>[]); CDE.forEach(([a,b,j])=>{ CDADJ[a].push({n:b,j}); CDADJ[b].push({n:a,j}); });
const nocheGato = h => h>=HOR.gato || h<HOR.inicio;   // de noche anda por los techos de la obra; de día por las casas
function gatoIda(cambiaZona){        // desde el techo del contenedor hasta la puerta de su casa
  const ida=[ {to:[-13.4,1.0,-11.0],jump:true}, {to:[-13.4,.15,-12.15],jump:true}, {to:[-13.3,.15,-12.75]},
    {to:[-13.0,.02,-13.2],cross:true}, {to:[-12.5,.02,-16.6]}, {to:[-12.35,.15,-17.4]}, {to:[-12.1,.08,-18.6]}, {to:[-12.0,.08,-19.35]} ];
  if(cambiaZona) Object.assign(ida[ida.length-1],{zona:'casa',node:0});
  return ida;
}
function gatoVuelta(cambiaZona){     // desde la puerta de su casa hasta el techo del contenedor
  const v=[ {to:[-12.1,.08,-18.6]}, {to:[-12.35,.15,-17.4]}, {to:[-12.5,.02,-16.8],cross:true}, {to:[-13.0,.02,-13.2]}, {to:[-13.3,.15,-12.75]},
    {to:[-13.4,.15,-12.15]}, {to:[-13.4,1.0,-11.0],jump:true}, {to:CN[0],jump:true,node:0} ];
  if(cambiaZona) v[v.length-1].zona='obra';
  return v;
}
function caminoGrafo(N,ADJ,desde,hasta){   // pasos (con saltos) del nodo actual a otro
  const prev=new Array(N.length).fill(null); prev[desde]={n:-1}; const q=[desde];
  while(q.length){ const a=q.shift(); if(a===hasta) break; ADJ[a].forEach(e=>{ if(!prev[e.n]){ prev[e.n]={n:a,j:e.j}; q.push(e.n); } }); }
  const pasos=[]; for(let n=hasta; n!==desde && prev[n]; n=prev[n].n) pasos.unshift({to:N[n],jump:!!prev[n].j,node:n});
  return pasos;
}
// de noche, de vez en cuando: salta la cerca del fondo, cruza la calle y se mete un rato en su casa; después vuelve
function excursionGato(){ return gatoIda(false).concat([{adentro:25+Math.random()*35}], gatoVuelta(false)); }
function catStep(dt){
  const want=true;   // anda a toda hora: de noche por la obra, de día por su casa
  const zonaQuiere = nocheGato(hourNow()) ? 'obra' : 'casa';
  if(!K.init){ K.init=true; K.zona=zonaQuiere; K.node = K.zona==='obra' ? 0 : 1; const p0=(K.zona==='obra'?CN:CD)[K.node]; cat.g.position.set(p0[0],p0[1],p0[2]); }
  K.vis+=((K.mode==='adentro'?0:1)-K.vis)*Math.min(1,dt*4);
  cat.g.visible=K.vis>.02; cat.g.scale.setScalar(Math.max(.001,K.sc*K.vis));
  const u=cat, f6=Math.min(1,dt*6);
  if(K.mode==='sit'){
    K.wait-=dt;
    u.body.position.y=lerp(u.body.position.y,-.05,f6); u.body.rotation.x=0;
    u.legs[2].rotation.x=u.legs[3].rotation.x=lerp(u.legs[2].rotation.x,-1.0,f6); u.legs[0].rotation.x=u.legs[1].rotation.x=0;
    u.head.rotation.y=Math.sin(T*.9)*.6; u.head.rotation.x=Math.max(0,Math.sin(T*.7))*.35;   // mira y se lame
    u.tail.rotation.x=.9; u.tail.rotation.y=Math.sin(T*2.2)*.5; u.tail2.rotation.x=.6;
    if(K.wait<=0 && want){
      if(!K.queue.length && K.zona!==zonaQuiere && K.node>=0){                         // cambio de horario: se muda de zona caminando
        K.queue = zonaQuiere==='casa' ? caminoGrafo(CN,CADJ,K.node,0).concat(gatoIda(true))
                                      : caminoGrafo(CD,CDADJ,K.node,0).concat(gatoVuelta(true));
      }
      if(!K.queue.length && K.zona==='obra' && K.node===0 && Math.random()<.3) K.queue=excursionGato();          // de noche, a veces visita su casa
      if(!K.queue.length && K.zona==='casa' && K.node===0 && Math.random()<.3) K.queue=[{adentro:25+Math.random()*45}];   // de día entra y sale de su casa
      const p=cat.g.position;
      if(K.queue.length){
        const stp=K.queue.shift();
        if(stp.adentro){ K.mode='adentro'; K.t=0; K.dur=stp.adentro; casas[0].doorOpen=.5; return; }
        K.from=[p.x,p.y,p.z]; K.to=stp.to; K.jump=!!stp.jump; K.cross=!!stp.cross; K.t=0;
        if(stp.zona) K.zona=stp.zona;
        if(stp.node!==undefined){ K.node=stp.node; K.prev=-1; } else K.node=-1;
      } else {
      const GN = K.zona==='obra' ? CN : CD, GA = K.zona==='obra' ? CADJ : CDADJ;
      let opts=GA[K.node].filter(e=>e.n!==K.prev); if(!opts.length) opts=GA[K.node];
      if(K.zona==='casa' && Math.random()<.7) opts = opts.filter(e=>e.n!==8 && e.n!==9).length ? opts.filter(e=>e.n!==8 && e.n!==9) : opts;   // casi siempre se queda en su casa
      const e=opts[Math.floor(Math.random()*opts.length)];
      K.from=[p.x,p.y,p.z]; K.to=GN[e.n]; K.prev=K.node; K.node=e.n; K.jump=!!e.j; K.cross=false; K.t=0;
      }
      const d=Math.hypot(K.to[0]-K.from[0],K.to[2]-K.from[2]);
      K.dur = K.jump ? .55+d*.12+Math.abs(K.to[1]-K.from[1])*.08 : d/1.5;
      K.mode = K.jump ? 'crouch' : 'walk'; K.crouch=.3;
      cat.g.rotation.y=Math.atan2(K.to[0]-K.from[0],K.to[2]-K.from[2]);
      u.head.rotation.set(0,0,0);
    }
  } else if(K.mode==='adentro'){                                   // dentro de la casa de techo rojo
    K.t+=dt; if(K.t>1.2 && K.t<K.dur-1.2) casas[0].doorOpen=0;
    if(K.t>=K.dur-1.2) casas[0].doorOpen=.5;
    if(K.t>=K.dur){ casas[0].doorOpen=0; K.mode='sit'; K.wait=.4; }
  } else if(K.mode==='crouch'){
    K.crouch-=dt; u.body.position.y=lerp(u.body.position.y,-.08,Math.min(1,dt*10)); u.tail.rotation.x=.3;
    u.legs.forEach(l=>l.rotation.x=0);
    if(K.crouch<=0) K.mode='jump';
  } else {
    if(K.cross && K.t===0 && trafficPool.some(o=>o.on && Math.abs(o.g.position.z-BACK_Z)<2.6 && Math.abs(o.g.position.x-cat.g.position.x)<6)){   // mira antes de cruzar
      u.legs.forEach(l=>l.rotation.x=0); u.head.rotation.y=Math.sin(T*3)*.7; return;
    }
    K.t+=dt; const k=clamp(K.t/K.dur,0,1), f=K.from, to=K.to;
    let y=lerp(f[1],to[1],k);
    if(K.mode==='jump'){
      y += Math.sin(Math.PI*k)*Math.max(.55, Math.abs(to[1]-f[1])*.45+.45);
      u.body.rotation.x=lerp(-.35,.35,k); u.body.position.y=0;
      u.legs[0].rotation.x=u.legs[1].rotation.x=-.9; u.legs[2].rotation.x=u.legs[3].rotation.x=.9; u.tail.rotation.x=.1;
    } else {
      K.phase+=dt*11; const sw=Math.sin(K.phase)*.7;
      u.legs[0].rotation.x=sw; u.legs[3].rotation.x=sw; u.legs[1].rotation.x=-sw; u.legs[2].rotation.x=-sw;
      u.body.position.y=Math.abs(Math.sin(K.phase))*.015; u.body.rotation.x=0; u.tail.rotation.x=.7; u.tail.rotation.y=Math.sin(K.phase*.5)*.3;
    }
    cat.g.position.set(lerp(f[0],to[0],k), y, lerp(f[2],to[2],k));
    if(k>=1){ u.body.rotation.x=0; K.mode='sit'; K.wait = (K.jump ? .6+Math.random()*1.4 : (Math.random()<.4 ? 1.5+Math.random()*3 : .2)) * (K.zona==='casa' && !K.queue.length ? 2.5 : 1); }   // de día, más tranquilo
  }
}

/* =====================================================================
   DELIVERY: a las 22 hs una moto le trae pizza y coca al cuidador,
   que se sienta a comer en la garita durante 30 minutos
   ===================================================================== */
function buildMoto(col,delivery){
  const g=new THREE.Group(), wheels=[];
  [.62,-.62].forEach(z=>{ const w=makeWheel(.26,.12); w.position.set(0,.26,z); g.add(w); wheels.push({g:w,r:.26}); });
  A(g,B(.22,.28,1.0),col,0,.55,0); A(g,B(.26,.12,.5),C.dark,0,.76,-.15);                       // cuerpo y asiento
  A(g,B(.06,.5,.06),C.metal,0,.7,.55,-.35,0,0); A(g,B(.56,.05,.05),C.dark,0,.95,.62);           // horquilla y manubrio
  A(g,B(.16,.1,.06),0xffe9a8,0,.82,.68); A(g,B(.14,.08,.05),0xdc2626,0,.66,-.52);               // faro y luz trasera
  let box=null;
  if(delivery){ box=new THREE.Group(); box.position.set(0,1.05,-.5); g.add(box); A(box,B(.46,.42,.46),0xdc2626,0,0,0); A(box,B(.47,.1,.47),0xffffff,0,.05,0); }
  const rider=buildPerson({vest:delivery?0xdc2626:col,pants:0x1f2937,hat:delivery?0xdc2626:0x111827});
  g.add(rider); rider.position.set(0,.32,-.02);
  const u=rider.userData; u.legL.rotation.x=u.legR.rotation.x=-1.2; u.armL.rotation.x=u.armR.rotation.x=-1.25;
  scene.add(g); g.visible=false;
  return { g, wheels, rider, box, len:1.4 };
}
const pizza=new THREE.Group(); scene.add(pizza); pizza.visible=false;
const pzBox=new THREE.Group(); pizza.add(pzBox);
A(pzBox,B(.5,.05,.5),0xf3ead8,0,0,0);
const pzLid=new THREE.Group(); pzLid.position.set(0,.03,-.25); pzBox.add(pzLid);
A(pzLid,B(.5,.04,.5),0xf3ead8,0,0,.25); A(pzLid,B(.2,.01,.2),0xdc2626,0,.025,.25);
const pzPie=mesh(CYL(.21,.21,.03,14),M(0xf5b041),0,.04,0); pzBox.add(pzPie);
[[.08,.05],[-.06,.09],[-.05,-.08],[.1,-.07]].forEach(([x,z])=>A(pzPie,CYL(.03,.03,.02,8),0xb91c1c,x,.02,z));   // pepperoni
const coke=mesh(CYL(.045,.045,.15,10),M(0xb91c1c),.32,.075,.05); pizza.add(coke); A(coke,CYL(.046,.046,.03,10),0xffffff,0,.02,0);
const moto=buildMoto(0xdc2626,true);
const MOTO_Z=8.9, MOTO_STOP=11.2, MEET={x:11.2,z:6.55};
const PZ={ state:'idle', done:false, x:-18.5, speed:0, t:0 };
const pizzaHours = h => h>=HOR.pizza && h<HOR.pizza+.5;
function hourDiff(a,b){ return ((a-b)%24+24)%24; }   // horas que pasaron de b a a
function handPos(){ const a=guard.rotation.y; return new THREE.Vector3(guard.position.x+Math.sin(a)*.38, 1.05, guard.position.z+Math.cos(a)*.38); }
function pizzaStep(dt){
  const h=hourNow();
  if(PZ.state==='idle'){
    if(!pizzaHours(h)) PZ.done=false;
    else if(!PZ.done && shift.phase==='off' && G.state==='rest'){
      PZ.done=true; PZ.state='coming'; PZ.x=-18.5; PZ.speed=3.6; moto.rider.userData.armL.rotation.x=-1.25;
      guardGo([[11.3,5.95],[MEET.x,MEET.z]],'pizzaGo');
    }
  }
  if(PZ.state==='coming'){ PZ.speed=(PZ.x<-16.2 && semLuz('FRONT')!=='G') ? 0 : Math.min(4,.4+(MOTO_STOP-PZ.x)*.9); PZ.x+=PZ.speed*dt; if(PZ.x>=MOTO_STOP-.02){ PZ.x=MOTO_STOP; PZ.speed=0; PZ.state='parked'; } }
  else if(PZ.state==='leaving'){ PZ.speed=Math.min(4.5,PZ.speed+2.5*dt); PZ.x+=PZ.speed*dt; if(PZ.x>18.6){ PZ.state='idle'; } }
  if(PZ.state==='parked' && G.state==='pizzaWait'){ PZ.state='handoff'; PZ.t=0; pizza.visible=true; pzLid.rotation.x=0; pzPie.scale.set(1,1,1); }
  if(PZ.state==='handoff'){
    PZ.t+=dt; const k=ease(clamp(PZ.t/1.4,0,1)), b=handPos();
    pizza.position.set(lerp(MOTO_STOP,b.x,k), lerp(1.35,b.y,k)+Math.sin(Math.PI*k)*.6, lerp(MOTO_Z-.35,b.z,k)); pizza.rotation.set(0,0,0);
    moto.rider.userData.armL.rotation.x=-1.25-Math.sin(Math.PI*k)*.6;
    if(k>=1){ PZ.state='leaving'; PZ.speed=0; guardGo([[11.3,5.95],[11.75,5.45],[12.0,5.4],[GAR.x-.08,GAR.z+.05]],'pizzaIn'); }
  }
  if(G.state==='pizzaIn'){ pizza.position.copy(handPos()); pizza.rotation.set(0,guard.rotation.y,0); }
  else if(G.state==='pizzaEat'){
    pizza.position.set(GAR.x+.38,.81,GAR.z-.02); pizza.rotation.set(0,Math.PI/2,0);
    pzLid.rotation.x=lerp(pzLid.rotation.x,-1.9,Math.min(1,dt*3));
    const left=Math.max(.06,1-clamp(hourDiff(h,G.eatFrom)/(HOR.pizzaMin/60),0,1)); pzPie.scale.set(left,1,left);   // la pizza se va terminando
  }
  const vis=PZ.state!=='idle';
  moto.g.visible=vis;
  if(vis){
    moto.g.position.set(PZ.x,0,MOTO_Z); moto.g.rotation.y=Math.PI/2;
    moto.g.scale.setScalar(Math.max(.001,ease(clamp(Math.min(PZ.x+18.6,18.6-PZ.x)/2.2,0,1))));
    moto.wheels.forEach(w=>{ w.g.rotation.x+=PZ.speed*dt/w.r; });
  }
}

/* =====================================================================
   CIUDAD: autos, camionetas y motos por las calles laterales; peatones en las veredas
   ===================================================================== */
const PALETA=[0xdc2626,0x2563eb,0xf3f4f6,0x111827,0x9aa6b8,0x16a34a,0xf59e0b,0x7c3aed,0x0ea5e9];
const pick=a=>a[Math.floor(Math.random()*a.length)];
function buildCar(col){
  const g=new THREE.Group(), wheels=[];
  A(g,B(1.1,.42,2.1),col,0,.5,0); A(g,B(.96,.38,1.1),0x1e293b,0,.88,-.1); A(g,B(1.0,.06,1.0),col,0,1.1,-.1);
  [.68,-.68].forEach(z=>[-.52,.52].forEach(x=>{ const w=makeWheel(.24,.2); w.position.set(x,.24,z); g.add(w); wheels.push({g:w,r:.24}); }));
  [-.35,.35].forEach(x=>{ A(g,B(.2,.1,.04),0xffe9a8,x,.55,1.06); A(g,B(.2,.1,.04),0xdc2626,x,.55,-1.06); });
  scene.add(g); g.visible=false; return { g, wheels, len:2.2 };
}
function buildPickup(col){
  const g=new THREE.Group(), wheels=[];
  A(g,B(1.2,.5,2.6),col,0,.6,0);
  A(g,B(1.1,.5,1.1),0x1e293b,0,1.1,.35); A(g,B(1.14,.07,1.12),col,0,1.38,.35);                  // cabina
  [-1,1].forEach(s=>A(g,B(.06,.3,1.15),col,s*.57,1.0,-.7)); A(g,B(1.2,.3,.06),col,0,1.0,-1.27);   // caja
  [.85,-.85].forEach(z=>[-.58,.58].forEach(x=>{ const w=makeWheel(.3,.24); w.position.set(x,.3,z); g.add(w); wheels.push({g:w,r:.3}); }));
  [-.4,.4].forEach(x=>{ A(g,B(.22,.1,.04),0xffe9a8,x,.7,1.31); A(g,B(.2,.1,.04),0xdc2626,x,.7,-1.31); });
  scene.add(g); g.visible=false; return { g, wheels, len:2.7 };
}
const LANES=[ {x:17.4,dir:1}, {x:19.4,dir:-1}, {x:-17.4,dir:-1}, {x:-19.4,dir:1} ];   // se circula por la derecha
// madrugada (de 23 a 6): no hay peatones, pasan muy pocos autos y de vez en cuando un patrullero
const madrugada = () => { const h=hourNow(); return h>=23 || h<6; };
function buildPolicia(){
  const v=buildCar(0xf8fafc);
  [-1,1].forEach(s=>A(v.g,B(.03,.16,1.9),0x1d4ed8,s*.56,.52,0,0,0,0,false));   // franja azul
  A(v.g,B(.9,.1,.25),0x111827,0,1.18,-.1);
  const azul=new THREE.Mesh(B(.36,.12,.2),new THREE.MeshBasicMaterial({color:0x1d4ed8})); azul.position.set(-.22,1.26,-.1); v.g.add(azul);
  const rojo=new THREE.Mesh(B(.36,.12,.2),new THREE.MeshBasicMaterial({color:0xb91c1c})); rojo.position.set(.22,1.26,-.1); v.g.add(rojo);
  v.police=true; v.balizas=[azul,rojo]; return v;
}
const ZA=WZ0+.3, ZB=WZ1-.3;
const trafficPool=[buildCar(pick(PALETA)),buildCar(pick(PALETA)),buildCar(pick(PALETA)),buildCar(pick(PALETA)),
                   buildPickup(pick(PALETA)),buildPickup(pick(PALETA)),buildMoto(pick(PALETA),false),buildMoto(pick(PALETA),false),buildPolicia()];
trafficPool.forEach(v=>{ v.on=false; });
let trafficT=2;
function inCross(x,z,road){ return Math.abs(x-road*ROAD_X)<2.3 && z>7.2 && z<11.95; }   // cruce calle lateral / calle del frente
function inCrossBack(x,z,road){ return Math.abs(x-road*ROAD_X)<2.3 && z>-17.15 && z<-12.65; }   // cruce calle lateral / calle de atrás

/* ---------- semáforo en la esquina izquierda (calle del frente × lateral izquierda) ---------- */
const SEM=[['LAT','G',10],['LAT','Y',2.5],['-','R',1.5],['FRONT','G',10],['FRONT','Y',2.5],['-','R',1.5]];
const SEM_TOTAL=SEM.reduce((a,x)=>a+x[2],0); let semT=0;
function semLuz(grupo){ let t=semT%SEM_TOTAL; for(const [g,c,d] of SEM){ if(t<d) return g===grupo?c:'R'; t-=d; } return 'R'; }
const semCaras=[];
function semaforo(x,z,heads){
  const g=new THREE.Group(); g.position.set(x,SIDEWALK_Y,z); scene.add(g);
  A(g,CYL(.06,.07,2.6,8),C.dark,0,1.3,0);
  heads.forEach(([ry,grupo])=>{
    const h=new THREE.Group(); h.position.set(0,2.35,0); h.rotation.y=ry; g.add(h);
    A(h,B(.3,.86,.2),0x111827,0,0,.1);
    const luces=[0xff2a2a,0xffb000,0x22c55e].map((c,i)=>{ const m=new THREE.Mesh(new THREE.SphereGeometry(.09,10,8), new THREE.MeshBasicMaterial({color:0x222222})); m.position.set(0,.27-i*.27,.21); h.add(m);
      m.userData.on=new THREE.Color(c); m.userData.off=new THREE.Color(c).multiplyScalar(.16); return m; });
    semCaras.push({grupo,luces});
  });
}
semaforo(-16.15,7.35,[[Math.PI/2,'FRONT'],[Math.PI,'LAT']]);     // esquina de la obra
semaforo(-20.65,7.35,[[Math.PI,'LAT'],[-Math.PI/2,'FRONT']]);    // vereda de enfrente
semaforo(-16.15,11.85,[[0,'LAT']]);                              // vereda del frente
function semaforoStep(dt){
  semT+=dt;
  semCaras.forEach(c=>{ const st=semLuz(c.grupo), idx= st==='R'?0 : st==='Y'?1 : 2; c.luces.forEach((m,i)=>m.material.color.copy(i===idx?m.userData.on:m.userData.off)); });
}

/* ---------- auto de la casa moderna: sale del garage a las :15 y vuelve a las :40 ---------- */
const carCasa=buildCar(0x0ea5e9);
const rutaEntra=makeRoute([[18.6,-15.9],[GAR_X+3.6,-15.9],[GAR_X+1.2,-16.8],[GAR_X,-18.7],[GAR_X,-21.9]]);
const rutaAtras=makeRoute([[GAR_X,-21.9],[GAR_X,-18.7],[GAR_X-.7,-16.3],[GAR_X-3.0,-14.2]]);
const rutaSale=makeRoute([[GAR_X-3.0,-14.2],[GAR_X+3,-14.0],[18.6,-14.0]]);
const HC={ state:'casa', s:0, v:0, door:0, salioKey:'', llegoKey:'' };
function horaClave(d){ return claveDia(d)+'-'+d.getHours(); }
function carCasaStep(dt){
  const d=simNow(), m=d.getMinutes(), key=horaClave(d);
  if(HC.state==='casa' && m>=15 && m<40 && HC.salioKey!==key && !madrugada()){ HC.salioKey=key; HC.state='abrir'; }
  if(HC.state==='fuera' && m>=40 && HC.llegoKey!==key && !trafficPool.some(o=>o.on && inCrossBack(o.g.position.x,o.g.position.z,1))){ HC.llegoKey=key; HC.state='llegando'; HC.s=0; HC.v=3; }
  let ruta=null, atras=false, puertaAbierta=false;
  if(HC.state==='abrir'){ puertaAbierta=true; if(HC.door>.97){ HC.state='marchaAtras'; HC.s=0; HC.v=0; } }
  if(HC.state==='marchaAtras'){ ruta=rutaAtras; atras=true; puertaAbierta=HC.s<4.5; HC.v=Math.min(1.3,HC.v+1.5*dt); HC.s+=HC.v*dt;
    if(HC.s>=rutaAtras.L){ HC.state='yendo'; HC.s=0; HC.v=0; } }
  else if(HC.state==='yendo'){ ruta=rutaSale; HC.v=Math.min(3.4,HC.v+2*dt); HC.s+=HC.v*dt; if(HC.s>=rutaSale.L){ HC.state='fuera'; } }
  else if(HC.state==='llegando'){ ruta=rutaEntra; const falta=rutaEntra.L-HC.s; puertaAbierta=falta<7; HC.v=3.2*Math.min(1,.25+falta/5); HC.s+=HC.v*dt;
    if(HC.s>=rutaEntra.L){ HC.s=rutaEntra.L; HC.state='casa'; } }
  HC.door+=((puertaAbierta?1:0)-HC.door)*Math.min(1,dt*2.2);
  garajePuerta.rotation.x=-HC.door*1.45;
  carCasa.g.visible = HC.state!=='fuera';
  if(HC.state==='casa'||HC.state==='abrir'){ carCasa.g.position.set(GAR_X,0,-21.9); carCasa.g.rotation.y=Math.PI; carCasa.g.scale.setScalar(1); return; }
  if(!ruta) return;
  { const tt=ruta.curve.getTangentAt(clamp(HC.s/ruta.L,0,1)), gap=obstaculoAdelante(carCasa.g.position,tt,2.2,carCasa); if(gap<1.2){ HC.s-=HC.v*dt; HC.v=0; } }   // espera al de adelante
  const u=clamp(HC.s/ruta.L,0,1), pnt=ruta.curve.getPointAt(u), t=ruta.curve.getTangentAt(u);
  carCasa.g.position.set(pnt.x,0,pnt.z); carCasa.g.rotation.y=Math.atan2(t.x,t.z)+(atras?Math.PI:0);
  carCasa.g.scale.setScalar(Math.max(.001,ease(clamp((18.6-pnt.x)/2.2,0,1))));
  carCasa.wheels.forEach(w=>{ w.g.rotation.x+=(atras?-1:1)*HC.v*dt/w.r; });
}
{ const m0=simNow().getMinutes(); if(m0>=15 && m0<40 && !madrugada()){ HC.state='fuera'; HC.salioKey=horaClave(simNow()); } }   // al abrir la página entre :15 y :40 el auto no está

/* ---------- dueños: cada hora entra a su casa el dueño de cada una ---------- */
const DUENIOS=[8,21,34,47].map((minuto,i)=>({ casa:casas[i], minuto, key:'', state:'off', path:[], idx:0, t:0, phase:0,
  obj:(()=>{ const o=buildPerson({civil:true,mujer:i%2===0,vest:pick(PALETA),pants:pick([0x1f2937,0x334155,0x1e3a8a,0x78716c]),hair:pick([0x2b1a10,0x111827,0x7c4a1e,0xd6b370,0x9ca3af]),skin:pick([0xf1c27d,0xe0ac69,0xc68642,0x8d5524,0xffdbac])}); o.visible=false; return o; })() }));
function casaLuz(i,d){                    // luces de cada casa según el día y la hora
  const h=hourOf(d), w=d.getDay(), finde=(w===5||w===6);
  if(h>=1 && h<6) return false;                                    // de 1 a 6 todas las casas a oscuras
  const prende=18.6+i*.3, apaga=Math.min(23.1+i*.35+(finde?1.2:0), 25);   // como muy tarde, a la 1
  if(h>=prende && h<Math.min(apaga,24)) return true;
  if(apaga>24 && h<apaga-24) return true;
  if(!finde && w!==0 && h>=6.0+i*.1 && h<7.0) return true;      // se levantan temprano los días hábiles (desde las 6)
  return false;
}
function barrioStep(dt){
  const d=simNow(), m=d.getMinutes(), key=horaClave(d);
  casas.forEach((c,i)=>{
    const hh=hourOf(d), oscuro=(hh>=1 && hh<6);
    c.k+=(((casaLuz(i,d)||(c.entro>0 && !oscuro))?1:0)-c.k)*Math.min(1,dt*3); c.entro=Math.max(0,(c.entro||0)-dt);
    c.wm.color.copy(WIN_OFF).lerp(WIN_ON,c.k);
    c.porch.intensity=c.k*2.2*clamp(nightNow*2,0,1);
    c.door.rotation.y=lerp(c.door.rotation.y,-1.3*c.doorOpen,Math.min(1,dt*4));
  });
  DUENIOS.forEach(o=>{
    const c=o.casa;
    if(o.state==='off'){
      if(m>=o.minuto && o.key!==key && !madrugada()){ o.key=key; const sx=c.x<0?-16.1:16.1;
        o.path=[[c.doorX,BACK_OUT_Z],[c.doorX,PUERTA_Z]]; o.idx=0; o.state='camina'; o.obj.visible=true; o.obj.scale.setScalar(1); o.obj.position.set(sx,SIDEWALK_Y,BACK_OUT_Z); }
      return;
    }
    if(o.state==='camina'){
      const t=o.path[o.idx], p=o.obj.position, dx=t[0]-p.x, dz=t[1]-p.z, dd=Math.hypot(dx,dz), st=1.1*dt;
      if(dd<=st){ p.x=t[0]; p.z=t[1]; o.idx++; if(o.idx>=o.path.length){ o.state='entra'; o.t=0; c.doorOpen=1; } }
      else { p.x+=dx/dd*st; p.z+=dz/dd*st; turnTo(o.obj,Math.atan2(dx,dz),dt*10); }
      p.y = p.z>-18.3 ? SIDEWALK_Y : .07;
      o.phase+=dt*6.2; setWalk(o.obj,o.phase,1);
      return;
    }
    if(o.state==='entra'){
      o.t+=dt; setWalk(o.obj,0,0);
      if(o.t>.5){ o.obj.position.z-=dt*.7; o.obj.scale.setScalar(Math.max(.001,1-(o.t-.5)/.8)); }
      if(o.t>1.3){ o.obj.visible=false; o.state='off'; c.doorOpen=0; if(nightNow>.3) c.entro=60; }   // al entrar de noche prende la luz un rato
    }
  });
}
function frontObjects(){ const a=[]; vehicles.forEach(v=>{ if(v.g.visible) a.push(v.g.position); }); if(bus.g.visible) a.push(bus.g.position); if(moto.g.visible) a.push(moto.g.position); return a; }
// red de calles: dos laterales (x=±18.4) y dos transversales (frente z=9.45, atrás z=-14.95); se circula por la derecha
const V3=(x,z)=>new THREE.Vector3(x,0,z);
const CALLES_Z=[9.45,-14.95];
const laneXof=(X,dz)=> dz>0 ? X-1 : X+1, laneZof=(Z,dx)=> dx>0 ? Z+.95 : Z-.95;
const CRUCES=[ {sem:true, box:(x,z)=>inCross(x,z,-1)}, {box:(x,z)=>inCross(x,z,1)}, {box:(x,z)=>inCrossBack(x,z,-1)}, {box:(x,z)=>inCrossBack(x,z,1)} ];
// entra por una punta de una lateral; en cada cruce sigue de largo o dobla hacia la manzana;
// en las transversales, al llegar a la otra lateral dobla a izquierda o derecha. Siempre termina saliendo por una punta.
function rutaAleatoria(){
  const X=pick([-18.4,18.4]), dz=pick([1,-1]);
  const cp=new THREE.CurvePath(); let cur=V3(laneXof(X,dz), dz>0?ZA:ZB), st={axis:'z',X,dz}, scan=cur.z, giros=0;
  for(let k=0;k<14;k++){
    if(st.axis==='z'){
      const lx=laneXof(st.X,st.dz);
      const sig=CALLES_Z.filter(Z=> st.dz>0 ? Z>scan+.5 : Z<scan-.5).sort((a,b)=> st.dz>0 ? a-b : b-a)[0];
      if(sig===undefined){ cp.add(new THREE.LineCurve3(cur,V3(lx, st.dz>0?ZB:ZA))); break; }
      if(giros<4 && Math.random()<.38){
        const hd = st.X>0 ? -1 : 1, corner=V3(lx,laneZof(sig,hd)), pb=corner.clone().add(V3(0,-st.dz*2.4)), pa=corner.clone().add(V3(hd*2.4,0));
        cp.add(new THREE.LineCurve3(cur,pb)); cp.add(new THREE.QuadraticBezierCurve3(pb,corner,pa)); cur=pa; st={axis:'x',Z:sig,dx:hd}; giros++;
      } else scan=sig;
    } else {
      const X2 = st.dx>0 ? 18.4 : -18.4, dz2=pick([1,-1]), corner=V3(laneXof(X2,dz2),laneZof(st.Z,st.dx));
      const pb=corner.clone().add(V3(-st.dx*2.4,0)), pa=corner.clone().add(V3(0,dz2*2.4));
      cp.add(new THREE.LineCurve3(cur,pb)); cp.add(new THREE.QuadraticBezierCurve3(pb,corner,pa)); cur=pa; scan=st.Z; st={axis:'z',X:X2,dz:dz2}; giros++;   // scan = la transversal recién usada
    }
  }
  return cp;
}
// distancia libre hasta el vehículo de adelante (autos, auto de la casa, camiones, colectivo), o Infinity
function obstaculoAdelante(pos,tan,len,yo){
  const lista=[];
  trafficPool.forEach(o=>{ if(o!==yo && o.on) lista.push([o.g.position,o.len]); });
  if(carCasa.g.visible && yo!==carCasa) lista.push([carCasa.g.position,2.2]);
  if(yo){ vehicles.forEach(o=>{ if(o.g.visible) lista.push([o.g.position,3.6]); }); if(bus.g.visible) lista.push([bus.g.position,4.4]); }
  let best=Infinity;
  lista.forEach(([p,l])=>{ const dx=p.x-pos.x, dz=p.z-pos.z, f=dx*tan.x+dz*tan.z, lat=Math.abs(dx*tan.z-dz*tan.x); if(f>0 && lat<1.0){ const gap=f-len/2-l/2; if(gap<best) best=gap; } });
  return best;
}
function cruceOcupado(cr,yo){
  if(trafficPool.some(o=>o!==yo && o.on && cr.box(o.g.position.x,o.g.position.z))) return true;
  if(frontObjects().some(p=>cr.box(p.x,p.z))) return true;
  return carCasa.g.visible && cr.box(carCasa.g.position.x,carCasa.g.position.z);
}
const enRuta=(v,s)=>v.curve.getPointAt(clamp(s/v.L,0,1));
function trafficStep(dt){
  trafficT-=dt;
  if(trafficT<=0){
    const tarde=madrugada();
    trafficT = (tarde ? 18+Math.random()*25 : nightNow>.5 ? 6+Math.random()*10 : 2.5+Math.random()*6) / (0.7*0.8);   // 30% y luego 20% menos autos; de madrugada, muy pocos
    const libres=trafficPool.filter(v=>!v.on), poli=libres.find(v=>v.police), civiles=libres.filter(v=>!v.police);
    const v = (tarde && poli && Math.random()<.4) ? poli : (civiles.length ? pick(civiles) : null);   // el patrullero sólo de madrugada
    if(v){
      const cv=rutaAleatoria(), p0=cv.getPointAt(0);
      if(!trafficPool.some(o=>o.on && o.g.position.distanceTo(p0)<v.len+3)){
        v.on=true; v.curve=cv; v.L=cv.getLength(); v.s=0; v.speed=3+Math.random()*1.8; v.vNow=v.speed; v.g.visible=true;
      }
    }
  }
  trafficPool.forEach(v=>{
    if(!v.on) return;
    const u=clamp(v.s/v.L,0,1), pos=v.curve.getPointAt(u), tan=v.curve.getTangentAt(u);
    let target=v.speed;
    const gap=obstaculoAdelante(pos,tan,v.len,v); if(gap<1.4) target=0; else if(gap<5) target=Math.min(target,v.speed*(gap-1.4)/3.6);
    // cruces: frena antes de entrar si el semáforo no está en verde o si hay alguien adentro
    const frente=v.s+v.len/2, pf=enRuta(v,frente);
    CRUCES.forEach(cr=>{
      if(cr.box(pf.x,pf.z)) return;                                         // ya está entrando: sigue
      let dist=-1; for(let a=.5;a<=5;a+=.5){ const q=enRuta(v,frente+a); if(cr.box(q.x,q.z)){ dist=a; break; } }
      if(dist<0) return;
      let parar=false;
      if(cr.sem){ const st=semLuz(Math.abs(tan.z)>Math.abs(tan.x)?'LAT':'FRONT'); if(st!=='G' && !(st==='Y' && dist<1.0)) parar=true; }
      if(!parar && dist<2.5 && cruceOcupado(cr,v)) parar=true;
      if(parar) target=Math.min(target, v.speed*clamp((dist-.3)/4,0,1));
    });
    v.vNow=Math.max(0,v.vNow+clamp(target-v.vNow,-6*dt,2.5*dt));
    v.s+=v.vNow*dt;
    if(v.s>=v.L){ v.on=false; v.g.visible=false; return; }
    const u2=clamp(v.s/v.L,0,1), p2=v.curve.getPointAt(u2), t2=v.curve.getTangentAt(u2);
    v.g.position.set(p2.x,0,p2.z); v.g.rotation.y=Math.atan2(t2.x,t2.z);
    v.g.scale.setScalar(Math.max(.001,ease(clamp(Math.min(v.s,v.L-v.s)/2.2,0,1))));
    v.wheels.forEach(w=>{ w.g.rotation.x+=v.vNow*dt/w.r; });
    if(v.police){ const on=Math.sin(T*12)>0; v.balizas[0].material.color.setHex(on?0x3b82f6:0x0b1e4f); v.balizas[1].material.color.setHex(on?0x3b0a0a:0xef4444); }
  });
}
const SIDEWALKS=[
  {a:[-16.1,FRONT_WALK_Z], b:[16.1,FRONT_WALK_Z]},                                   // vereda de la calle del frente
  {a:[SIDE_IN,-12.6], b:[SIDE_IN,7.35]}, {a:[-SIDE_IN,-12.6], b:[-SIDE_IN,7.35]},     // veredas junto al vallado
  {a:[SIDE_IN,WZ0+.3], b:[SIDE_IN,-17.2]}, {a:[-SIDE_IN,WZ0+.3], b:[-SIDE_IN,-17.2]},   // veredas del lado del barrio
  {a:[-16.1,BACK_IN_Z], b:[16.1,BACK_IN_Z]}, {a:[-16.1,BACK_OUT_Z], b:[16.1,BACK_OUT_Z]},   // veredas de la calle de atrás
  {a:[SIDE_OUT,WZ0+.3], b:[SIDE_OUT,WZ1-.3]}, {a:[-SIDE_OUT,WZ0+.3], b:[-SIDE_OUT,WZ1-.3]},   // veredas de enfrente
];
const ROPA=[0x2563eb,0xdc2626,0x16a34a,0xf59e0b,0x7c3aed,0x0f766e,0xf3f4f6,0x334155,0xdb2777];
const peds=[];
for(let i=0;i<6;i++){   // la mitad son mujeres (salen al azar entre los libres → ~50%)
  const o=buildPerson({civil:true,mujer:i%2===1,vest:pick(ROPA),pants:pick([0x1f2937,0x334155,0x1e3a8a,0x78716c]),hair:pick([0x2b1a10,0x111827,0x7c4a1e,0xd6b370,0x9ca3af]),skin:pick([0xf1c27d,0xe0ac69,0xc68642,0x8d5524,0xffdbac])});
  o.visible=false; peds.push({obj:o,on:false,phase:Math.random()*6});
}
let pedT=1.5;
function pedStep(dt){
  pedT-=dt;
  if(pedT<=0){
    pedT = (nightNow>.5 ? 7+Math.random()*12 : 2+Math.random()*6) / (0.6*0.7*0.8);   // peatones: -40%, -30% y -20%
    const free=madrugada() ? [] : peds.filter(q=>!q.on);   // de 23 a 6 no sale nadie a caminar
    if(free.length){
      const q=pick(free), w=pick(SIDEWALKS), rev=Math.random()<.5;
      q.from=rev?w.b:w.a; q.to=rev?w.a:w.b; q.len=Math.hypot(q.to[0]-q.from[0],q.to[1]-q.from[1]); q.s=0;
      q.speed=.9+Math.random()*.45; q.lat=(Math.random()-.5)*.6; q.on=true; q.obj.visible=true;
      q.obj.rotation.y=Math.atan2(q.to[0]-q.from[0],q.to[1]-q.from[1]);
    }
  }
  peds.forEach(q=>{
    if(!q.on) return;
    q.s+=q.speed*dt; if(q.s>=q.len){ q.on=false; q.obj.visible=false; return; }
    const k=q.s/q.len, dx=(q.to[0]-q.from[0])/q.len, dz=(q.to[1]-q.from[1])/q.len;
    q.obj.position.set(lerp(q.from[0],q.to[0],k)+dz*q.lat, SIDEWALK_Y, lerp(q.from[1],q.to[1],k)-dx*q.lat);
    q.obj.scale.setScalar(Math.max(.001,ease(clamp(Math.min(q.s,q.len-q.s)/1.4,0,1))));
    q.phase+=dt*6*q.speed; setWalk(q.obj,q.phase,1);
  });
}

/* ---------- obreros: salen caminando hasta el bus / vuelven a su puesto ---------- */
const GATE_PATH=[[5.8,0,6.4],[10.7,0,6.4],[10.7,0,7.4]];
const WEST_UP=[[-12.5,0,6.4],[-8,0,6.4],[-2.8,0,6.4],[1.5,0,6.4]];
const SCAF=(y,z)=>[[-9.2,y,z],[-9.7,y,z],[-9.7,0,z],[-12.5,0,z]];
function walkerPath(w,start){
  const goal=N(1.5,6.4), prev=new Array(NODES.length).fill(-2); prev[start]=-1; const q=[start];
  while(q.length){ const a=q.shift(); if(a===goal) break; ADJ[a].forEach(b=>{ if(prev[b]===-2){ prev[b]=a; q.push(b); } }); }
  const seq=[]; for(let n=goal;n!==-1;n=prev[n]) seq.unshift([NODES[n].x,0,NODES[n].z]);
  return seq.concat(GATE_PATH);
}
const crew=[];
function addCrew(o){ Object.assign(o,{ state:'post', wait:0, hold:0, ph:0, amp:0, board:0, path:[], idx:0 }); o.obj.userData.crew=o; crew.push(o); return o; }
walkers.forEach(w=>{ w.home=w.cur; addCrew({ obj:w.obj, kind:'walker', w,
  out(arrive){ return walkerPath(w, arrive ? w.home : (w.nxt>=0 ? w.nxt : w.cur)); },
  homePos(){ return null; },
  onPost(){ w.cur=w.home; w.nxt=-1; w.prev=-1; w.wait=0; w.p=0; w.amp=0; w.obj.position.set(NODES[w.home].x,0,NODES[w.home].z); } }); });
const BARROW_DROP=.46;
addCrew({ obj:barrowMan, kind:'barrow', dropP:0,
  out(){ const x=lerp(BARROW.ax,BARROW.bx,this.dropP); return [[x,0,BARROW.z],[-2.8,0,BARROW.z],[-2.8,0,6.4],[1.5,0,6.4]].concat(GATE_PATH); },
  homePos(){ return [lerp(BARROW.ax,BARROW.bx,this.dropP),0,BARROW.z]; },
  onDepart(){ this.dropP=BARROW.p; scene.attach(wheelbarrow); },
  onPost(){ barrowMan.add(wheelbarrow); wheelbarrow.position.set(0,0,.95); wheelbarrow.rotation.set(0,0,0); BARROW.p=this.dropP; BARROW.state='load'; BARROW.wait=.3; BARROW.load=false; barrowMan.rotation.y=BARROW.face; } });
const fixedCrew=(obj,home,ry,pre)=>addCrew({ obj, kind:'fixed',
  out(){ return pre.concat(WEST_UP,GATE_PATH).filter(Boolean); }, homePos(){ return home; },
  onPost(){ obj.position.set(home[0],home[1],home[2]); obj.rotation.y=ry; } });
fixedCrew(bricklayer,[-6.9,0,.15],0,[[-9.3,0,.15],[-9.9,0,.62],[-12.5,0,.62]]);
fixedCrew(hammer1,[-6,3.15,-2],Math.PI*.8,SCAF(3.15,-1.4));
fixedCrew(hammer2,[-1.8,6.15,-4.6],-Math.PI/2,SCAF(6.15,-1.4));
addCrew({ obj:foreman, kind:'fixed', out(){ return GATE_PATH.slice(); }, homePos(){ return [5.8,0,6.0]; }, onPost(){ foreman.position.set(5.8,0,6.0); foreman.rotation.y=Math.PI*.35; } });

/* ---------- almuerzo: camino de cada obrero hasta la mesa y su asiento ---------- */
function graphPath(start,goal){
  const prev=new Array(NODES.length).fill(-2); prev[start]=-1; const q=[start];
  while(q.length){ const a=q.shift(); if(a===goal) break; ADJ[a].forEach(b=>{ if(prev[b]===-2){ prev[b]=a; q.push(b); } }); }
  const seq=[]; for(let n=goal; n>=0; n=prev[n]) seq.unshift([NODES[n].x,0,NODES[n].z]); return seq;
}
const HUB_W=[[-12.5,0,-7.9],[-8.5,0,-7.9]];
const crewOf=o=>crew.find(c=>c.obj===o);
crew.forEach(c=>{
  if(c.kind==='walker'){ const w=c.w; c.lunchWay=home=>graphPath(home ? w.home : (w.nxt>=0 ? w.nxt : w.cur), N(-8,-7.9)); }
  else if(c.kind==='barrow'){ c.lunchWay=()=>{ const x=lerp(BARROW.ax,BARROW.bx,c.dropP); return [[x,0,BARROW.z],[-12.5,0,BARROW.z]].concat(HUB_W); }; }
});
crewOf(bricklayer).lunchWay=()=>[[-9.3,0,.15],[-9.9,0,.62],[-12.5,0,.62]].concat(HUB_W);
crewOf(hammer1).lunchWay=()=>SCAF(3.15,-1.4).concat(HUB_W);
crewOf(hammer2).lunchWay=()=>SCAF(6.15,-1.4).concat(HUB_W);
crewOf(foreman).lunchWay=()=>[[6,0,6.4],[6,0,-7.9],[-4,0,-7.9]];
const SEATS=[   // banco del fondo (miran a la cámara) y banco del frente
  {x:-7.6,z:-10.35,ry:0,fondo:true},{x:-6.9,z:-10.35,ry:0,fondo:true},{x:-6.1,z:-10.35,ry:0,fondo:true},{x:-5.4,z:-10.35,ry:0,fondo:true},
  {x:-7.6,z:-8.85,ry:Math.PI},{x:-6.5,z:-8.85,ry:Math.PI},{x:-5.4,z:-8.85,ry:Math.PI} ];
crew.forEach((c,i)=>{ const st=SEATS[i]; c.seatIdx=i;
  c.seat={ ry:st.ry, via: st.fondo ? [[-8.6,0,-8.4],[-8.6,0,-10.78],[st.x,0,-10.78],[st.x,0,st.z]] : [[st.x,0,-8.2],[st.x,0,st.z]] }; });
const GRILL_SEAT={ ry:Math.PI, stand:true, via:[[-9.3,0,-8.3],[-9.3,0,-9.6]] };   // asador: parado frente a la parrilla
crew.forEach(c=>{ c.curSeat=c.seat; });
function lunchPathOut(c){ return c.lunchWay(false).concat(c.curSeat.via); }
function lunchPathBack(c){ const back=c.curSeat.via.slice().reverse().concat(c.lunchWay(true).slice().reverse()), hp=c.homePos(); return hp ? back.concat([hp]) : back; }
// de la mesa directo al colectivo (sábado, al terminar el asado)
function leaveFromTable(c){
  const via=c.curSeat.via.slice().reverse();
  if(c.obj===foreman) return via.concat([[-4,0,-7.9],[6,0,-7.9],[6,0,6.4]],GATE_PATH);
  return via.concat([[-8.5,0,-7.9],[-12.5,0,-7.9]],WEST_UP,GATE_PATH);
}

function crewMove(c,dt){
  const o=c.obj; let moving=false;
  if(c.wait>0){ c.wait-=dt; }
  else {
    const t=c.path[c.idx];
    if(t){
      const dx=t[0]-o.position.x, dy=t[1]-o.position.y, dz=t[2]-o.position.z, d=Math.hypot(dx,dy,dz), st=1.25*dt;
      if(d<=st){ o.position.set(t[0],t[1],t[2]); c.idx++; moving=true; }
      else {
        const nx=o.position.x+dx/d*st, nz=o.position.z+dz/d*st;
        if(blockedByVehicle(nx,nz) && c.hold<6){ c.hold+=dt; }
        else { c.hold=0; o.position.x=nx; o.position.y+=dy/d*st; o.position.z=nz; if(Math.hypot(dx,dz)>.02) turnTo(o,Math.atan2(dx,dz),dt*10); moving=true; }
      }
    }
  }
  c.amp+=((moving?1:0)-c.amp)*Math.min(1,dt*8); if(moving) c.ph+=dt*6.4;
  setWalk(o,c.ph,c.amp);
  return !c.path[c.idx];
}
function crewStep(dt){
  crew.forEach(c=>{
    if(c.state==='toBus'){
      if(crewMove(c,dt)){                                   // llegó a la puerta del bus
        turnTo(c.obj,0,dt*10); c.board+=dt;
        if(bus.state==='parked' && c.board>.4 && bus.cool<=0){ c.state='inBus'; c.obj.visible=false; bus.cool=.8; }
      }
    } else if(c.state==='fromBus'){
      if(crewMove(c,dt)){ c.state='post'; c.obj.visible=true; if(c.onPost) c.onPost(); }
    } else if(c.state==='toLunch'){
      if(crewMove(c,dt)) c.state='atLunch';
    } else if(c.state==='atLunch'){                       // sentado comiendo (o el asador, parado en la parrilla)
      const o=c.obj, u=o.userData, st=c.curSeat;
      if(st.stand){
        o.position.y+=(0-o.position.y)*Math.min(1,dt*6); turnTo(o,st.ry,dt*8);
        u.legL.rotation.x=u.legR.rotation.x=0; u.torso.position.y=.7;
        u.armR.rotation.x=-1.25+Math.sin(T*3.5)*.4; u.armL.rotation.x=-.5;
      } else {
        o.position.y+=(-.2-o.position.y)*Math.min(1,dt*6); turnTo(o,st.ry,dt*8);
        u.legL.rotation.x=u.legR.rotation.x=-1.45; u.torso.position.y=.7;
        u.armR.rotation.x=-1.1+Math.sin(T*2.2+c.seatIdx*1.3)*.55; u.armL.rotation.x=-.8;
      }
    } else if(c.state==='fromLunch'){
      if(crewMove(c,dt)){ c.state='post'; c.obj.position.y=0; if(c.onPost) c.onPost(); }
    } else if(c.state==='waitBus'){
      if(bus.state==='parked' && bus.cool<=0 && !crew.some(k=>k.state==='fromBus' && k.idx<2)){
        const rev=c.out(true).slice().reverse(), hp=c.homePos();
        c.path=hp ? rev.concat([hp]) : rev; c.idx=0; c.wait=0; c.hold=0; c.amp=0;
        c.obj.position.set(DOOR.x,0,7.4); c.obj.rotation.y=Math.PI; c.obj.visible=true; c.state='fromBus'; bus.cool=.9;
      }
    }
  });
}

/* ---------- máquina de estados del turno ---------- */
const shift={ phase:'work', mk:1 };
let snapNext=false;
function startLeave(){
  shift.phase='leaving';
  crew.forEach((c,i)=>{ if(c.onDepart) c.onDepart(); const enMesa=(c.state==='atLunch'||c.state==='toLunch'); c.state='toBus'; c.path=enMesa?leaveFromTable(c):c.out(false); c.idx=0; c.wait=i*.9+Math.random()*.5; c.hold=0; c.board=0; c.amp=0; });
  bus.want='arrive';
}
function endLeave(){ shift.phase='off'; guardAppear(); }
function startArrive(){
  shift.phase='arriving'; G.goHome=true;
  crew.forEach(c=>{ c.state='waitBus'; c.obj.visible=false; });
  bus.want='arrive';
}
function snapOff(){
  shift.phase='off'; shift.mk=0;
  crew.forEach(c=>{ if(c.kind==='barrow'){ c.dropP=BARROW_DROP; BARROW.p=BARROW_DROP; barrowMan.position.set(lerp(BARROW.ax,BARROW.bx,BARROW_DROP),0,BARROW.z); barrowMan.rotation.y=BARROW.face; scene.updateMatrixWorld(true); scene.attach(wheelbarrow); }
    c.state='inBus'; c.obj.visible=false; });
  vehicles.forEach(v=>{ v.s=0; v.delay=1; v.pause=0; v.lift=0; v.speedNow=0; v.passed=false; v.g.visible=false; });
  bus.state='gone'; bus.g.visible=false; bus.want=null; bus.departed=false;
  barriers.forEach(b=>{ b.k=1; b.target=1; });
  guardAppear(); G.state='rest'; G.since=PATROL_EVERY-15; guard.position.set(REST[0],0,REST[1]); guard.rotation.y=0; dog.g.position.set(REST[0]-.2,0,REST[1]+.75); G.path=[];
  PZ.state='idle'; pizza.visible=false;
}
// mode: 'lunch' (almuerzo, días hábiles) o 'asado' (sábados)
function startMeal(mode){
  shift.phase=mode;
  crew.forEach((c,i)=>{ c.curSeat=(mode==='asado' && c.obj===foreman) ? GRILL_SEAT : c.seat;
    if(c.onDepart) c.onDepart(); c.state='toLunch'; c.path=lunchPathOut(c); c.idx=0; c.wait=i*.7+Math.random()*.6; c.hold=0; c.amp=0; });
}
function startLunchBack(){
  shift.phase='lunchBack';
  crew.forEach((c,i)=>{ c.state='fromLunch'; c.path=lunchPathBack(c); c.idx=0; c.wait=i*.6+Math.random()*.5; c.hold=0; c.amp=0; });
}
function snapMeal(mode){
  snapWork();                                     // limpia camiones, colectivo, cuidador, delivery
  shift.phase=mode; shift.mk=0;
  if(mode==='asado'){ vehicles.forEach(v=>{ v.s=0; v.delay=1; v.pause=0; v.lift=0; v.speedNow=0; v.passed=false; v.g.visible=false; }); barriers.forEach(b=>{ b.k=1; b.target=1; }); }   // obra clausurada
  crew.forEach(c=>{
    c.curSeat=(mode==='asado' && c.obj===foreman) ? GRILL_SEAT : c.seat;
    if(c.kind==='barrow'){ c.dropP=BARROW_DROP; BARROW.p=BARROW_DROP; barrowMan.position.set(lerp(BARROW.ax,BARROW.bx,BARROW_DROP),0,BARROW.z); barrowMan.rotation.y=BARROW.face; scene.updateMatrixWorld(true); scene.attach(wheelbarrow); }
    const st=c.curSeat.via[c.curSeat.via.length-1]; c.obj.visible=true; c.obj.position.set(st[0],c.curSeat.stand?0:-.2,st[2]); c.obj.rotation.y=c.curSeat.ry; c.state='atLunch'; c.path=[]; c.idx=0;
  });
}
function snapWork(){
  shift.phase='work'; shift.mk=1; PZ.state='idle'; pizza.visible=false;
  crew.forEach(c=>{ c.state='post'; c.obj.visible=true; if(c.onPost) c.onPost(); });
  vehicles.forEach(v=>{ v.s=v.s0; v.passed=v.p0; v.delay=0; v.pause=0; v.lift=0; v.speedNow=0; });
  bus.state='gone'; bus.g.visible=false; bus.want=null; bus.departed=false;
  barriers.forEach(b=>{ b.k=0; b.target=0; });
  G.state='hidden'; guard.visible=dog.g.visible=false;
}

function applyLight(h){
  const n=nightK(h), d=duskK(h)*(1-n); nightNow=n;
  hemi.intensity=lerp(lerp(.95,.82,d),.3,n);
  hemi.color.set(0xffffff).lerp(cB.set(0xffd9b0),d*.55).lerp(cB.set(0x7f95d6),n);
  hemi.groundColor.set(0xb6c6e6).lerp(cB.set(0xc9a98a),d*.5).lerp(cB.set(0x1b2548),n);
  sun.intensity=lerp(lerp(1.5,1.15,d),.4,n);
  sun.color.set(0xffffff).lerp(cB.set(0xffbf80),d*.8).lerp(cB.set(0x93acff),n);
  sun.position.set(lerp(lerp(10,18,d),-8,n), lerp(lerp(26,17,d),24,n), lerp(lerp(12,9,d),-6,n));
  towerSpot.intensity=n*85; gateGlow.intensity=n*16; garitaLight.intensity=n*4;
  towerLampMat.color.set(0xfff1b0).lerp(cB.set(0xffffff),n);
  const f=lerp(1,.3,n);
  dimMats.forEach(m=>{ m.mat.color.copy(m.base).multiplyScalar(f); m.mat.color.b=Math.min(1,m.mat.color.b*lerp(1,1.35,n)); });
  const bg1=cA.set(0xeef4fc).lerp(cB.set(0xffe6cc),d).lerp(cB.set(0x1c2c5e),n).getStyle();
  const bg2=cA.set(0xcddbf2).lerp(cB.set(0xe9a883),d).lerp(cB.set(0x0a1130),n).getStyle();
  if(bg1+bg2!==applyLight.last){ applyLight.last=bg1+bg2; const r=document.documentElement; r.style.setProperty('--bg1',bg1); r.style.setProperty('--bg2',bg2); }
  document.documentElement.classList.toggle('night', n>.5);
}
const cA=new THREE.Color(), cB=new THREE.Color();
function shiftUpdate(dt){
  const now=simNow(), h=hourOf(now); applyLight(h);
  const want=wantAt(now), P=shift.phase, sn=snapNext;   // sn: salto de reloj (arrastre / pestaña dormida) → directo al estado final
  if(P==='work'){
    if(want==='off') sn?snapOff():startLeave();
    else if(want==='lunch'||want==='asado') sn?snapMeal(want):startMeal(want);
  } else if(P==='off'){
    if(want==='work'||want==='lunch') sn?snapWork():startArrive();
    else if(want==='asado') snapMeal('asado');
  } else if(P==='lunch'){
    if(want==='work') sn?snapWork():startLunchBack();
    else if(want==='off') snapOff(); else if(want==='asado') snapMeal('asado');
  } else if(P==='lunchBack'){
    if(want==='off') snapOff(); else if(want==='lunch'||want==='asado') snapMeal(want);
  } else if(P==='asado'){
    if(want==='off') sn?snapOff():startLeave();
    else if(want==='work') snapWork(); else if(want==='lunch') snapMeal('lunch');
  } else if(sn){                                      // en plena salida/llegada del colectivo y se movió el reloj
    if(want==='off') snapOff(); else if(want==='work') snapWork(); else snapMeal(want);
  }
  snapNext=false;
  if(shift.phase==='lunchBack' && crew.every(c=>c.state==='post')) shift.phase='work';
  lunchProps.visible = shift.phase==='lunch' && crew.some(c=>c.state==='atLunch');
  if(bus.want==='arrive' && bus.state==='gone' && entryClear() && !trafficPool.some(o=>o.on && inCross(o.g.position.x,o.g.position.z,1))){ bus.state='arriving'; bus.x=18.2; bus.speed=3.2; bus.want=null; }
  if(shift.phase==='leaving'){
    if(bus.state==='parked' && crew.every(c=>c.state==='inBus')){ bus.parkT+=dt; if(bus.parkT>1.6 && !siteBusy()){ bus.state='leaving'; bus.speed=0; bus.departed=true; } }
    if(bus.departed && bus.state==='gone'){ bus.departed=false; endLeave(); }
  } else if(shift.phase==='arriving'){
    if(bus.state==='parked' && crew.every(c=>c.state==='post')){ bus.parkT+=dt; if(bus.parkT>1.2){ bus.state='leaving'; bus.speed=0; bus.departed=true; } }
    if(bus.departed && bus.state==='gone'){ bus.departed=false; shift.phase='work'; }
  }
  shift.mk+=((shift.phase==='work'?1:0)-shift.mk)*Math.min(1,dt*1.4);
  const closed = (shift.phase==='off' && bus.state==='gone' && !siteBusy()) || (shift.phase==='asado' && !siteBusy());
  cartelCerrado.visible = shift.phase==='asado' && closed;
  barriers.forEach(b=>{ b.target = closed?1:0; });
  barrierStep(dt,nightNow);
}

/* =====================================================================
   CONTROLES
   ===================================================================== */
const controls=new OrbitControls(camera,canvas);
controls.target.set(0,1,-4);
controls.enableDamping=true; controls.dampingFactor=.08;
controls.minZoom=.55; controls.maxZoom=3.2; controls.zoomSpeed=.9;
controls.minPolarAngle=.45; controls.maxPolarAngle=1.35;
controls.enablePan=true; controls.screenSpacePanning=true;
controls.autoRotate=true; controls.autoRotateSpeed=.7;
controls.update();
let resumeTimer=0;
controls.addEventListener('start',()=>{ controls.autoRotate=false; clearTimeout(resumeTimer); hideHint(); });
controls.addEventListener('end',()=>{ clearTimeout(resumeTimer); resumeTimer=setTimeout(()=>{ controls.autoRotate=true; },4000); });
let hintHidden=false; function hideHint(){ if(hintHidden) return; hintHidden=true; document.getElementById('hint').classList.add('off'); }
setTimeout(hideHint,9000);
const bounds={ min:new THREE.Vector3(-12,0,-16), max:new THREE.Vector3(12,3,8) };

/* =====================================================================
   RELOJ ANALÓGICO: muestra la hora de la escena; arrastrando las agujas
   (o con ◀ ▶ el día) se corre el tiempo y la obra salta a ese momento
   ===================================================================== */
const DIAS=['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
const relojEl=document.getElementById('reloj'), relojSvg=document.getElementById('relojSvg');
const agH=document.getElementById('agujaH'), agM=document.getElementById('agujaM');
const relojDig=document.getElementById('relojDig'), relojDia=document.getElementById('relojDia'), relojEst=document.getElementById('relojEstado'), relojReal=document.getElementById('relojReal');
(function(){ let g=''; for(let i=0;i<60;i++){ const a=i/60*Math.PI*2, r1=i%5?50:46, x1=60+Math.sin(a)*r1, y1=60-Math.cos(a)*r1, x2=60+Math.sin(a)*53, y2=60-Math.cos(a)*53;
  g+='<line x1="'+x1.toFixed(1)+'" y1="'+y1.toFixed(1)+'" x2="'+x2.toFixed(1)+'" y2="'+y2.toFixed(1)+'" stroke="#14307a" stroke-width="'+(i%5?1:2.5)+'" stroke-linecap="round"/>'; }
  document.getElementById('relojMarcas').innerHTML=g; })();
const dos = n => String(n).padStart(2,'0');
function textoEstado(d){
  const td=tipoDia(d), w=wantAt(d), h=hourOf(d);
  if(td.tipo==='feriado') return 'Feriado: <b>'+td.nombre+'</b><br>no se trabaja';
  if(td.tipo==='domingo') return '<b>Domingo</b>: no se trabaja';
  if(td.tipo==='sabado'){
    if(w==='asado') return 'Sábado: <b>¡asado!</b> (13 a 15)';
    if(w==='work') return 'Sábado: se trabaja <b>hasta las 13</b>';
    return h<HOR.inicio ? 'Sábado: arranca a las 6' : 'Sábado: <b>terminó la semana</b>';
  }
  if(w==='lunch') return '<b>Almuerzo</b> (13:00 a 13:40)';
  if(w==='work') return '<b>Jornada de trabajo</b>';
  return h>=HOR.pizza && h<HOR.pizza+.5 ? 'Fuera de horario · <b>llega la pizza</b>' : 'Fuera de horario';
}
let relojUlt='';
function relojStep(){
  const d=simNow(), h=d.getHours(), m=d.getMinutes(), sec=d.getSeconds();
  agH.setAttribute('transform','rotate('+(((h%12)+m/60)*30).toFixed(2)+' 60 60)');
  agM.setAttribute('transform','rotate('+((m+sec/60)*6).toFixed(2)+' 60 60)');
  const txt=dos(h)+':'+dos(m);
  if(txt+d.getDate()!==relojUlt){
    relojUlt=txt+d.getDate();
    relojDig.textContent=txt;
    relojDia.textContent=DIAS[d.getDay()]+' '+d.getDate()+'/'+(d.getMonth()+1);
    relojEst.innerHTML=textoEstado(d);
  }
  relojReal.hidden = OFF===0;
}
let arrastre=null;
function anguloPuntero(e){ const r=relojSvg.getBoundingClientRect(); let a=Math.atan2(e.clientX-(r.left+r.width/2), -(e.clientY-(r.top+r.height/2))); if(a<0) a+=Math.PI*2; return a; }
function moverReloj(ms){ OFF+=ms; hourOverride=null; snapNext=true; }
function empezarArrastre(cual,e){ arrastre=cual; relojEl.classList.add('arrastrando'); relojSvg.setPointerCapture(e.pointerId); e.preventDefault(); e.stopPropagation(); }
agH.addEventListener('pointerdown',e=>empezarArrastre('h',e));
agM.addEventListener('pointerdown',e=>empezarArrastre('m',e));
relojSvg.addEventListener('pointermove',e=>{
  if(!arrastre) return;
  const a=anguloPuntero(e), d=simNow();
  if(arrastre==='m'){ let diff=a/(Math.PI*2)*60-(d.getMinutes()+d.getSeconds()/60); if(diff>30) diff-=60; if(diff<-30) diff+=60; moverReloj(diff*60000); }
  else { let diff=a/(Math.PI*2)*12-((d.getHours()%12)+d.getMinutes()/60); if(diff>6) diff-=12; if(diff<-6) diff+=12; moverReloj(diff*3600000); }   // pasar por las 12 cambia AM/PM
});
const soltar=()=>{ arrastre=null; relojEl.classList.remove('arrastrando'); };
relojSvg.addEventListener('pointerup',soltar); relojSvg.addEventListener('pointercancel',soltar);
document.getElementById('diaAnt').addEventListener('click',()=>moverReloj(-86400000));
document.getElementById('diaSig').addEventListener('click',()=>moverReloj(86400000));
relojReal.addEventListener('click',()=>{ OFF=0; hourOverride=null; snapNext=true; });

/* =====================================================================
   BUCLE
   ===================================================================== */
const clock=new THREE.Clock(); let T=0;
function step(dt){
  T+=dt;
  shiftUpdate(dt);
  const mk=shift.mk;                                   // 1 = máquinas en marcha, 0 = detenidas (fuera de horario)
  craneStep(dt*mk); excStep(dt*mk); staticStep();
  walkers.forEach(w=>{ if(atPost(w.obj)) walkerStep(w,dt); }); if(atPost(barrowMan)) barrowStep(dt);
  crewStep(dt); busStep(dt); guardStep(dt); pizzaStep(dt); catStep(dt); semaforoStep(dt); trafficStep(dt); pedStep(dt); asadoStep(dt); carCasaStep(dt); barrioStep(dt);
  vehicles.forEach(v=>vehicleStep(v,dt));
  mixSpin.rotation.y+=dt*2.2*mk;
  beacons.forEach((b,i)=>{ b.rotation.y+=dt*(3.5+i); });
  gateLamps.forEach((l,i)=>{ l.visible = Math.sin(T*6+(i%2)*Math.PI)>-.1; });
}
function frame(){
  const rdt=clock.getDelta(); if(rdt>2.5) snapNext=true;      // pestaña dormida: si cambió el turno, salta directo al estado final
  step(Math.min(.05,rdt));
  controls.target.clamp(bounds.min,bounds.max);
  controls.update();
  renderer.render(scene,camera);
  relojStep();
  requestAnimationFrame(frame);
}
wantAt(simNow())!=='off' ? snapWork() : snapOff();   // estado inicial según el día y la hora
snapNext=true;                                   // si arranca en el almuerzo, aparecen ya sentados
frame();

// ganchos para pruebas
window.__obra={ shift, bus, G, guard, dog, crew, barriers, PATROL, EMERGE, GATE_PATH, REST, snapOff, snapWork, setHour:h=>{ hourOverride=h; }, hour:hourNow, nightK, applyLight, step, vehicles, walkers, barrowMan, obstacles, camera, controls, siteRoute, bricklayer, hammer1, hammer2, foreman, get T(){return T;}, get crT(){return crT;}, loadG, cat:K, PZ, pizza, trafficPool, peds, startMeal, startLunchBack, snapMeal, simNow, tipoDia, wantAt, feriadosDe, setOffset:ms=>{ OFF=ms; snapNext=true; }, HC, casas, DUENIOS, semLuz, cartelCerrado, excursionGato, rutaAleatoria, apurarTransito:()=>{ trafficT=0; } };
window.__ok=true;
</script>
</body>
</html>
@endverbatim
