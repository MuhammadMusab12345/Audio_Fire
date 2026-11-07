/* ==========================================================================
   Latest Songs - WebGL carousel (Three.js r128 + GSAP) - Cloud AI Website
   Transparent output: the section shows the site's fixed grunge texture
   behind the cards (alpha: true, clear colour alpha 0, RGBA render target,
   lens shader writes premultiplied RGBA).

   >>> EDIT THE SONGS ARRAY JUST BELOW to change songs / links / colours. <<<
   ========================================================================== */

/* =====================================================================
   1) SONGS (image + YouTube link).
   - src: image path (e.g. "images/songs/song-1.jpg"). If src is empty (""),
     the auto-generated cover art below is used.
   - color: glow ring colour of that song (singer theme colour)
   - link: play button link
   ===================================================================== */
const SONGS = [
  { title:"Poove Sempoove", artist:"", color:"#e8c96a", link:"https://www.youtube.com/results?search_query=Poove+Sempoove+song", src:"images/songs/song-1.jpg" },
  { title:"Sahiba", artist:"Aditya Rikhari", color:"#ff3b3b", link:"https://www.youtube.com/results?search_query=Sahiba+Aditya+Rikhari", src:"images/songs/song-2.jpg" },
  { title:"Udaarian", artist:"Satinder Sartaaj", color:"#d9b36a", link:"https://www.youtube.com/results?search_query=Udaarian+Satinder+Sartaaj", src:"images/songs/song-3.jpg" },
  { title:"Rathinamo", artist:"Saurav Srisan", color:"#2ee6c8", link:"https://www.youtube.com/results?search_query=Rathinamo+Saurav+Srisan", src:"images/songs/song-4.jpg" },
  { title:"Drip Sef", artist:"Bob Trigger ft Gunna", color:"#c0392b", link:"https://www.youtube.com/results?search_query=Drip+Sef+Bob+Trigger+Gunna", src:"images/songs/song-5.jpg" },
  { title:"Sweetheart", artist:"Monsta X", color:"#ffb020", link:"https://www.youtube.com/results?search_query=Sweetheart+Monsta+X", src:"images/songs/song-6.jpg" },
  { title:"Raataan Lambiyan", artist:"Jubin Nautiyal & Asees Kaur", color:"#ff8fb0", link:"https://www.youtube.com/results?search_query=Raataan+Lambiyan+Shershaah", src:"images/songs/song-7.jpg" },
  { title:"Arz Kiya Hai", artist:"Anuv Jain", color:"#e0e0e0", link:"https://www.youtube.com/results?search_query=Arz+Kiya+Hai+Anuv Jain+Coke+Studio+Bharat", src:"images/songs/song-8.jpg" }
];
const PANEL_HEIGHT = 450, GAP = 12, ENTRY_ON = true;

/* ===================== Auto cover art (agar src khali ho) ===================== */
function makeCover(song){
  const c = document.createElement("canvas"); c.width = 600; c.height = 800;
  const g = c.getContext("2d");
  const col = song.color;
  const grad = g.createLinearGradient(0,0,600,800);
  grad.addColorStop(0, col); grad.addColorStop(.55, "#1a0b2b"); grad.addColorStop(1, "#07030c");
  g.fillStyle = grad; g.fillRect(0,0,600,800);
  // vinyl rings
  g.save(); g.translate(300,360);
  for(let r=250;r>40;r-=14){ g.beginPath(); g.arc(0,0,r,0,Math.PI*2);
    g.strokeStyle = `rgba(255,255,255,${0.03+ (r%28===0?0.05:0)})`; g.lineWidth=2; g.stroke(); }
  const rg = g.createRadialGradient(0,0,0,0,0,110);
  rg.addColorStop(0,"#fff"); rg.addColorStop(.25,col); rg.addColorStop(1,"rgba(0,0,0,0)");
  g.fillStyle = rg; g.beginPath(); g.arc(0,0,110,0,Math.PI*2); g.fill();
  g.restore();
  // glow streak
  g.save(); g.globalCompositeOperation="screen"; g.rotate(-.35);
  const sg = g.createLinearGradient(0,300,600,300);
  sg.addColorStop(0,"rgba(255,255,255,0)"); sg.addColorStop(.5,"rgba(255,255,255,.18)"); sg.addColorStop(1,"rgba(255,255,255,0)");
  g.fillStyle = sg; g.fillRect(-100,330,900,60); g.restore();
  // text
  g.fillStyle="rgba(255,255,255,.75)"; g.font="600 22px Inter, sans-serif"; g.textAlign="left";
  g.fillText(song.artist.toUpperCase(), 40, 690);
  g.fillStyle="#fff"; g.font='900 56px "Playfair Display", Georgia, serif';
  const words = song.title.split(" "); let line="", y=745;
  const lines=[]; words.forEach(w=>{ const t=line?line+" "+w:w; if(g.measureText(t).width>520){lines.push(line);line=w;} else line=t; }); lines.push(line);
  y = 745 - (lines.length-1)*58;
  lines.forEach((l,i)=>g.fillText(l,40,y+i*58));
  g.fillStyle="rgba(255,255,255,.5)"; g.font="500 18px Inter, sans-serif"; g.textAlign="right";
  return c;
}

/* ===================== Lens settings & shader ===================== */
const LENS = {
  sizeX:.565,sizeY:1,posX:.5,posY:.5,rotation:65,zoom:0,dispersion:11,blur:0,glow:4.2,whiteGlow:.24,
  novaSize:12,blueRing:0,ringRadius:.49,ringWidth:.014,shimmerFreq:12,shimmerSpeed:3.5,shimmerDepth:.12,
  rimStart:.578,rimTangential:.6,rimInward:0,rimFreq1:2,rimFreq2:1,rimLine:1.4,rimLinePos:.488,
  rimLineWidth:.003,vignette:0,vignetteSize:.3,samples:16
};
const FOCUS = {cardDuration:.7,focusDuration:.9,cardEase:"power4.out",focusEase:"power3.out",stagger:.06,dropDist:1.4,centerScale:1.18,lensFade:.85};
const ENTRY = {delay:.5,startH:80,riseDuration:1,stagger:.07,riseEase:"power3.out",fromBelow:.9,growDelay:.25,growDuration:2.15,growEase:"expo.inOut",growStagger:.085,lensBloom:1.4,lensBloomEase:"power2.inOut"};

const LENS_VERTEX = `varying vec2 vUv; void main(){ vUv = uv; gl_Position = vec4(position.xy,0.0,1.0); }`;

/* Transparent version: uTex is sampled as PREMULTIPLIED RGBA, alpha is
   accumulated with the colour in the dispersion loop, and main() writes
   premultiplied vec4(rgb, a) with rgb <= a. Empty space stays alpha 0. */
const LENS_FRAGMENT = `
#define PI 3.14159265
precision highp float;
varying vec2 vUv;
uniform sampler2D uTex; uniform vec2 uRes; uniform vec2 uCenter;
uniform float uSizeX,uSizeY,uAspect,uZoom,uDispersion,uBlur,uGlow,uWhiteGlow,uNovaSize,uBlueRing,uRingRadius,uRingWidth;
uniform float uShimmer,uShimmerFreq,uShimmerSpeed,uShimmerDepth,uTime,uRimStart,uRimTangential,uRimInward,uRimFreq1,uRimFreq2;
uniform vec3 uBlueColor;
uniform float uRimLine,uRimLinePos,uRimLineWidth,uVignette,uVignetteSize,uShape,uSquareRound,uRotation;
uniform int uSamples;
const int MAX_SAMPLES = 16;
float sdRoundBox(vec2 p, vec2 b, float r){ vec2 q = abs(p)-b+r; return min(max(q.x,q.y),0.0)+length(max(q,0.0))-r; }
vec4 discLens(vec2 center, float aspectCorrect, out float outA){
  vec2 p = vUv - center; p.x *= aspectCorrect;
  float ca = cos(uRotation), sa = sin(uRotation);
  p = mat2(ca,-sa,sa,ca)*p;
  vec2 halfSize = vec2(uSizeX,uSizeY);
  float dist = length(p/halfSize);
  outA = 0.0;
  float maskND;
  if(uShape>0.5){ float corner=min(uSizeX,uSizeY)*clamp(uSquareRound,0.0,1.0); float sd=sdRoundBox(p,halfSize,corner); maskND=1.0+sd/min(uSizeX,uSizeY); }
  else { maskND = dist; }
  if(maskND>1.0) return vec4(0.0);
  float shapeND = clamp(maskND,0.0,1.0);
  float nd = clamp(dist,0.0,1.0);
  vec2 offset = vUv-center;
  vec2 radialDir = normalize(offset+1e-6);
  vec2 tangentDir = vec2(-radialDir.y,radialDir.x);
  float angle = atan(p.y,p.x);
  float pull = uZoom*0.30*(nd*nd);
  float rimStrength = smoothstep(uRimStart,1.0,nd);
  float fluidWave = sin(angle*uRimFreq1)*0.55+sin(angle*uRimFreq2)*0.25;
  float rScreen = (uSizeX+uSizeY)*0.5;
  vec2 rimOff = tangentDir*fluidWave*rimStrength*rScreen*uRimTangential;
  vec2 rimPull = -radialDir*rimStrength*rScreen*uRimInward;
  vec2 baseUV = center+offset*(1.0-pull)+rimOff+rimPull;
  float rimMask = smoothstep(0.55,1.0,nd);
  vec2 dispDir = offset*uDispersion*0.004*rimMask;
  int N = uSamples; if(N<2) N=2; if(N>MAX_SAMPLES) N=MAX_SAMPLES;
  vec4 col = vec4(0.0); vec4 caW = vec4(0.0);
  for(int i=0;i<MAX_SAMPLES;i++){
    if(i>=N) break;
    float t = float(i)/float(N-1);
    vec2 sUV = baseUV+dispDir*(t-0.5);
    vec4 s = texture2D(uTex,sUV);
    vec3 w = vec3(exp(-pow((t-0.0)/0.38,2.0)),exp(-pow((t-0.5)/0.38,2.0)),exp(-pow((t-1.0)/0.38,2.0)));
    float wa = (w.x+w.y+w.z)*0.3333333;
    col.rgb += s.rgb*w; caW.rgb += w;
    col.a   += s.a*wa;   caW.a   += wa;
  }
  col.rgb /= max(caW.rgb,vec3(0.001));
  col.a   /= max(caW.a,0.001);
  col.rgb *= mix(0.91,1.0,smoothstep(0.0,0.38,shapeND));
  float glow = 0.0;
  float r2 = shapeND*shapeND*0.25;
  float gs = max(uNovaSize*uGlow*0.003,0.004);
  float nova = exp(-r2/gs)+exp(-r2/(gs*7.0))*0.18;
  nova *= uWhiteGlow*(uGlow/17.0)*1.15;
  col.rgb += vec3(nova);
  glow = max(glow,nova);
  float dC = shapeND*0.5;
  float tR = clamp(uRingRadius,0.1,0.49);
  float rW = max(uRingWidth,0.003);
  float ring = exp(-pow((dC-tR)/rW,2.0));
  ring *= uBlueRing*(uGlow/17.0)*1.8;
  if(uShimmer>0.5) ring *= sin(angle*uShimmerFreq+uTime*uShimmerSpeed)*uShimmerDepth+(1.0-uShimmerDepth);
  float ringAura = exp(-pow((dC-tR)/(rW*6.0),2.0))*0.28*uBlueRing*(uGlow/17.0);
  col.rgb += uBlueColor*(ring+ringAura);
  glow = max(glow,(ring+ringAura)*max(uBlueColor.r,max(uBlueColor.g,uBlueColor.b)));
  float rim = exp(-pow((dC-uRimLinePos)/max(uRimLineWidth,0.0001),2.0))*uRimLine;
  col.rgb += vec3(rim);
  glow = max(glow,rim);
  col.a = max(col.a,glow);
  col.rgb = min(col.rgb,vec3(col.a));
  outA = smoothstep(1.0,0.93,maskND);
  return col;
}
void main(){
  vec4 base = texture2D(uTex,vUv);
  float mask = 0.0;
  vec4 c = discLens(uCenter,uAspect,mask);
  vec3 rgb = mix(base.rgb,c.rgb,mask);
  float a = max(base.a,c.a*mask);
  a = clamp(a,0.0,1.0);
  rgb = min(rgb,vec3(a));
  gl_FragColor = vec4(rgb,a);
}`;

const LENS_FX_KEYS = ["uDispersion","uBlueRing","uRimLine","uZoom","uRimTangential","uRimInward"];
const REPEATS = 4, CLICK_SLOP = 6, TOUCH_CLICK_SLOP = 12, FLICK_IDLE_MS = 90;
const songsReduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
const pad = n => String(n).padStart(2,"0");

/* ===================== UI elements ===================== */
const stage = document.getElementById("songs-stage"), mount = document.getElementById("songs-mount");
const cursorEl = document.getElementById("cursor"), titleEl = document.getElementById("song-title");
const counterEl = document.getElementById("counter"), closeBtn = document.getElementById("close");
const infoEl = document.getElementById("info"), infoTitle = document.getElementById("info-title");
const infoMeta = document.getElementById("info-meta"), infoLink = document.getElementById("info-link");
const infoSave = document.getElementById("info-save");   // save heart (assets/js/save.js)

/* keep the save heart in sync with the carousel's current song */
function syncInfoSave(){
  if (!infoSave) return;
  const s = SONGS[active];
  const on = !!(window.AFS && AFS.has("song", AFS.id(s.title, s.artist)));
  infoSave.classList.toggle("on", on);
  infoSave.setAttribute("aria-pressed", String(on));
  infoSave.setAttribute("aria-label", (on ? "Remove " : "Save ") + s.title);
}
if (infoSave) {
  infoSave.addEventListener("click", e => {
    e.preventDefault(); e.stopPropagation();          // never open the card
    const s = SONGS[active];
    if (window.AFS) AFS.toggle("song", {
      id: AFS.id(s.title, s.artist), title: s.title, artist: s.artist,
      img: s.src, href: s.link                        // no mp3 here: "Open" goes to the video link
    });
    syncInfoSave();
  });
  document.addEventListener("af:saves", syncInfoSave); // saves from anywhere update it
}

let engine = null, active = 0, focused = false, entryDone = !ENTRY_ON || songsReduced, revealPlayed = false;

function setTitle(i){
  const s = SONGS[i];
  titleEl.innerHTML = s.artist ? `${s.title} <span>— ${s.artist}</span>` : s.title;
  counterEl.textContent = `${pad(i+1)}/${pad(SONGS.length)}`;
  infoTitle.textContent = s.title;
  infoMeta.textContent = s.artist || '';
  infoLink.href = s.link; infoLink.target = '_blank'; infoLink.rel = 'noopener noreferrer';
  /* flashing NEW chip on the newest additions (first three songs) */
  const newChip = document.getElementById("info-new");
  if (newChip) newChip.hidden = i >= 3;
  syncInfoSave();
}
function onActive(i){
  active = i; setTitle(i);
  if(engine) engine.setColor(SONGS[i].color);
}
function onFocus(open){
  focused = open;
  closeBtn.style.opacity = open ? 1 : 0; closeBtn.style.pointerEvents = open ? "auto" : "none";
  infoEl.classList.toggle("show", open);
  gsap.to(counterEl,{autoAlpha: open?0:1,duration:.4});
  gsap.to(titleEl,{y: open ? -window.innerHeight*0.02 : 0,duration:.4});
}
function onEntryDone(done){
  entryDone = done;
  if(!done){ gsap.set([titleEl,counterEl],{autoAlpha:0}); return; }
  if(!revealPlayed){
    revealPlayed = true;
    gsap.fromTo(titleEl,{autoAlpha:0},{autoAlpha:1,duration:songsReduced?0:1.6,ease:"power2.out"});
    gsap.fromTo(counterEl,{autoAlpha:0},{autoAlpha:1,duration:songsReduced?0:1.6,delay:songsReduced?0:.18,ease:"power2.out"});
  }
}
gsap.set([titleEl,counterEl],{xPercent:-50}); /* centre */
closeBtn.addEventListener("click",()=>engine && engine.closeFocus());
stage.addEventListener("keydown",e=>{
  if(!engine) return;
  if(e.key==="ArrowRight"){e.preventDefault();engine.next();}
  else if(e.key==="ArrowLeft"){e.preventDefault();engine.previous();}
  else if(e.key==="Escape"){e.preventDefault();engine.closeFocus();}
});

/* ===================== Carousel engine ===================== */
function createCarousel(){
  const entryOn = ENTRY_ON && !songsReduced;
  let W = Math.max(1,mount.clientWidth), H = Math.max(1,mount.clientHeight);
  const panelHFor = () => Math.max(120, Math.min(PANEL_HEIGHT, Math.round(H*0.52)));
  let PANEL_H = panelHFor();
  const EASE = songsReduced?.28:.09, SNAP_EASE = songsReduced?.22:.05, WHEEL = 1.4, DRAG = 1.6, TOUCH_DRAG = 1, TOUCH_EASE = .22;
  const FRICTION = .865, SNAP_IDLE_MS = 120, SHRINK_MAX = 60, SHRINK_ATTACK = .25, SHRINK_DECAY = .06;
  const ASPECT = 896/1200;

  let renderer;
  try { renderer = new THREE.WebGLRenderer({antialias:true,alpha:true,premultipliedAlpha:true}); } catch(e){ return null; }
  const dpr = Math.min(window.devicePixelRatio||1,2);
  renderer.setPixelRatio(dpr); renderer.setSize(W,H);
  /* alpha 0 for BOTH passes (card pass -> render target, lens pass -> canvas) */
  renderer.setClearColor(0x000000,0);
  const el = renderer.domElement;
  Object.assign(el.style,{display:"block",width:"100%",height:"100%",touchAction:"none",userSelect:"none",background:"transparent"});
  mount.appendChild(el);

  const scene = new THREE.Scene();
  const camera = new THREE.OrthographicCamera(-W/2,W/2,H/2,-H/2,-100,100); camera.position.z = 10;

  const loader = new THREE.TextureLoader(); loader.setCrossOrigin("anonymous");
  const sources = SONGS.map(song=>{
    const s = {tex:null,aspect:ASPECT};
    const done = tex=>{
      tex.minFilter = THREE.LinearMipmapLinearFilter; tex.magFilter = THREE.LinearFilter;
      tex.generateMipmaps = true; tex.anisotropy = renderer.capabilities.getMaxAnisotropy();
      s.tex = tex; recomputeTotal();
      if(!userInteracted){ scroll = centerForIndex(0); target = scroll; }
    };
    /* setTimeout zaroori hai: done() baad me declare hone wale variables use karta hai */
    if(song.src){ loader.load(song.src, done, undefined, ()=>done(new THREE.CanvasTexture(makeCover(song)))); }
    else setTimeout(()=>done(new THREE.CanvasTexture(makeCover(song))),0);
    return s;
  });

  const slotWidth = i => sources[i].aspect*PANEL_H+GAP;
  let offsets = [], totalWidth = 0;
  function recomputeTotal(){ offsets=[]; let acc=0; for(let i=0;i<sources.length;i++){offsets.push(acc);acc+=slotWidth(i);} totalWidth=acc; }
  recomputeTotal();
  function centerForIndex(idx){
    const N=sources.length, loop=Math.floor(idx/N), s=((idx%N)+N)%N;
    return offsets[s]+slotWidth(s)/2-GAP/2+loop*totalWidth;
  }
  function nearestIndex(v){
    if(!totalWidth) return 0; const N=sources.length; let best=0,bd=Infinity;
    for(let i=0;i<N;i++){ const c=offsets[i]+slotWidth(i)/2-GAP/2, k=Math.round((v-c)/totalWidth), d=Math.abs(c+k*totalWidth-v); if(d<bd){bd=d;best=i+k*N;} }
    return best;
  }
  function centerIndex(v){
    if(!totalWidth) return 0; let bi=0,bd=Infinity;
    for(let i=0;i<sources.length;i++){ const c=offsets[i]+slotWidth(i)/2-GAP/2, k=Math.round((v-c)/totalWidth), d=Math.abs(c+k*totalWidth-v); if(d<bd){bd=d;bi=i;} }
    return bi;
  }

  let lastCenter = -1;
  const pool = [];
  const LABEL_GAP = 16, LABEL_RATIO = 160/1024;
  const labelTex = SONGS.map(sg=>{
    const c=document.createElement("canvas"); c.width=1024; c.height=160; const g=c.getContext("2d");
    g.textAlign="center"; g.textBaseline="middle"; g.fillStyle="#fff";
    let size=66; g.font=`700 ${size}px "Playfair Display", Georgia, serif`;
    while(g.measureText(sg.title).width>940 && size>30){ size-=4; g.font=`700 ${size}px "Playfair Display", Georgia, serif`; }
    g.fillText(sg.title,512,84);
    const t=new THREE.CanvasTexture(c); t.minFilter=THREE.LinearFilter; t.generateMipmaps=false; return t;
  });
  for(let r=0;r<REPEATS;r++) for(let i=0;i<sources.length;i++){
    const mat = new THREE.MeshBasicMaterial({color:0xdddddd,transparent:true});
    const mesh = new THREE.Mesh(new THREE.PlaneGeometry(1,1,1,1),mat);
    mesh.visible=false; scene.add(mesh);
    const lmat=new THREE.MeshBasicMaterial({map:labelTex[i],transparent:true});
    const label=new THREE.Mesh(new THREE.PlaneGeometry(1,1),lmat); label.visible=false; scene.add(label);
    pool.push({mesh,mat,label,srcIndex:i,bound:false});
  }

  let scroll = centerForIndex(0), target = scroll, userInteracted=false, velocity=0, prevScroll=0, scrollEnergy=0;
  let pendingFocus=null, lastInput=performance.now(), snapped=false;

  /* Offscreen card pass: RGBA, cleared to alpha 0 (premultiplied) */
  const rt = new THREE.WebGLRenderTarget(W*dpr,H*dpr,{
    format: THREE.RGBAFormat, minFilter: THREE.LinearFilter, magFilter: THREE.LinearFilter,
    generateMipmaps: false, depthBuffer: false, stencilBuffer: false
  });
  rt.texture.format = THREE.RGBAFormat;
  const lensScene = new THREE.Scene(), lensCam = new THREE.OrthographicCamera(-1,1,1,-1,0,1);
  const U = {
    uTex:{value:rt.texture}, uRes:{value:new THREE.Vector2(W*dpr,H*dpr)}, uCenter:{value:new THREE.Vector2(.5,.5)},
    uSizeX:{value:LENS.sizeX}, uSizeY:{value:LENS.sizeY}, uShape:{value:0}, uSquareRound:{value:0}, uRotation:{value:0},
    uAspect:{value:W/H}, uZoom:{value:LENS.zoom}, uDispersion:{value:LENS.dispersion}, uBlur:{value:LENS.blur},
    uGlow:{value:LENS.glow}, uWhiteGlow:{value:LENS.whiteGlow}, uNovaSize:{value:LENS.novaSize}, uBlueRing:{value:LENS.blueRing},
    uRingRadius:{value:LENS.ringRadius}, uRingWidth:{value:LENS.ringWidth}, uShimmer:{value:songsReduced?0:1},
    uShimmerFreq:{value:LENS.shimmerFreq}, uShimmerSpeed:{value:LENS.shimmerSpeed}, uShimmerDepth:{value:LENS.shimmerDepth},
    uTime:{value:0}, uRimStart:{value:LENS.rimStart}, uRimTangential:{value:LENS.rimTangential}, uRimInward:{value:LENS.rimInward},
    uRimFreq1:{value:LENS.rimFreq1}, uRimFreq2:{value:LENS.rimFreq2}, uBlueColor:{value:new THREE.Color(SONGS[0].color)},
    uRimLine:{value:LENS.rimLine}, uRimLinePos:{value:LENS.rimLinePos}, uRimLineWidth:{value:LENS.rimLineWidth},
    uVignette:{value:LENS.vignette}, uVignetteSize:{value:LENS.vignetteSize}, uSamples:{value:LENS.samples}
  };
  const lensMat = new THREE.ShaderMaterial({uniforms:U,vertexShader:LENS_VERTEX,fragmentShader:LENS_FRAGMENT});
  const lensQuad = new THREE.Mesh(new THREE.PlaneGeometry(2,2),lensMat); lensScene.add(lensQuad);

  const total = REPEATS*sources.length;
  const fs = {active:false,srcIndex:-1,poolIdx:-1,lensFx:entryOn?0:1,anim:null};
  const drop = new Array(total).fill(0);
  let focusScale = 1;
  const lastCenterX = new Array(total);
  const pEntry = new Array(total).fill(entryOn?0:1);
  const growArr = new Array(total).fill(entryOn?0:1);
  let entryActive = entryOn, entrySettled = false, entryAnim = null;
  const fxFull = {}; LENS_FX_KEYS.forEach(k=>fxFull[k]=U[k].value);

  let panelRects = [], centeredPanel = null;

  function layout(){
    panelRects = []; centeredPanel = null; let cd = Infinity;
    const half = W/2, buffer = PANEL_H;
    pool.forEach((p,poolIdx)=>{
      const rep = Math.floor(poolIdx/sources.length), i = p.srcIndex, src = sources[i];
      const slotC = offsets[i]+slotWidth(i)/2-GAP/2;
      let x = slotC-scroll; x = ((x%totalWidth)+totalWidth)%totalWidth;
      x += (rep-Math.floor(REPEATS/2))*totalWidth;
      if(x>half+totalWidth) x -= totalWidth*REPEATS;
      const centerX = x, inEntry = entryActive||entrySettled;
      p.label.visible=false;
      if(!inEntry && (centerX<-half-buffer || centerX>half+buffer)){ p.mesh.visible=false; p.label.visible=false; lastCenterX[poolIdx]=undefined; return; }
      lastCenterX[poolIdx] = centerX;
      const shrink = 1-.25*scrollEnergy, h = PANEL_H*shrink, wPx = src.aspect*PANEL_H*shrink;
      if(src.tex && !p.bound){ p.mat.map=src.tex; p.mat.color.set(0xffffff); p.mat.needsUpdate=true; p.bound=true; }
      /* no texture yet -> never draw the placeholder plane (would be a grey rect) */
      const ready = !!src.tex;
      let y=0; const isF = fs.active && fs.poolIdx===poolIdx, d = drop[poolIdx]||0;
      let drawW=wPx, drawH=h;
      if(isF){ drawW=wPx*focusScale; drawH=h*focusScale; } else if(d>0){ y=-d*H*FOCUS.dropDist; }
      p.mesh.visible = ready; p.label.visible = ready && !isF;
      let finalX=centerX, finalY=y, finalW=drawW, finalH=drawH;
      if(inEntry){
        const pe = pEntry[poolIdx]||0, g = growArr[poolIdx]||0;
        const curH = ENTRY.startH+(drawH-ENTRY.startH)*g;
        finalH=curH; finalW=curH*src.aspect;
        const cSrc = centerIndex(scroll); let di=i-cSrc; const N=sources.length;
        if(di>N/2) di-=N; if(di<-N/2) di+=N;
        const midRep = Math.floor(REPEATS/2);
        if(rep!==midRep){ p.mesh.visible=false; p.label.visible=false; lastCenterX[poolIdx]=undefined; return; }
        const slotH = s=>{ const gg=growArr[midRep*N+s]||0; return ENTRY.startH+(PANEL_H-ENTRY.startH)*gg; };
        let off=0;
        if(di>0){ for(let k=0;k<di;k++){ const sa=(((cSrc+k)%N)+N)%N, sb=(((cSrc+k+1)%N)+N)%N; off+=(sources[sa].aspect*slotH(sa)+sources[sb].aspect*slotH(sb))/2+GAP; } }
        else if(di<0){ for(let k=0;k<-di;k++){ const sa=(((cSrc-k)%N)+N)%N, sb=(((cSrc-k-1)%N)+N)%N; off-=(sources[sa].aspect*slotH(sa)+sources[sb].aspect*slotH(sb))/2+GAP; } }
        finalX = off;
        if(finalX<-half-buffer || finalX>half+buffer){ p.mesh.visible=false; p.label.visible=false; lastCenterX[poolIdx]=undefined; return; }
        const below = -H*ENTRY.fromBelow; finalY = below+(y-below)*pe;
      }
      p.mesh.position.set(finalX,finalY,0); p.mesh.scale.set(finalW,finalH,1);
      const lw=finalW*1.04, lh=lw*LABEL_RATIO;
      p.label.position.set(finalX, finalY-finalH/2-LABEL_GAP*(finalH/PANEL_H)-lh/2, 0); p.label.scale.set(lw,lh,1);
      const sx = centerX+W/2, sy = H/2-y;
      panelRects.push({left:sx-drawW/2,right:sx+drawW/2,top:sy-drawH/2,bottom:sy+drawH/2,poolIdx,srcIndex:i,centerX});
      if(Math.abs(centerX)<cd){ cd=Math.abs(centerX); centeredPanel={srcIndex:i,centerX,wPx,h,poolIdx}; }
    });
  }

  const panelAtPointer = (px,py)=>{ for(const r of panelRects) if(px>=r.left&&px<=r.right&&py>=r.top&&py<=r.bottom) return r; return null; };
  const localPoint = e=>{ const r=el.getBoundingClientRect(); return {x:e.clientX-r.left,y:e.clientY-r.top}; };

  let dragging=false, dragPointerId=null, dragLastX=0, dragDist=0, dragVel=0, dragMoveT=0, suppressClick=false, dragPointerType="mouse";
  let lastPX=NaN, lastPY=NaN, pointerInside=false, lastPType="mouse";

  gsap.set(cursorEl,{xPercent:20,yPercent:30,scale:0,autoAlpha:0});
  const moveX = gsap.quickTo(cursorEl,"x",{duration:.5,ease:"power3.out"});
  const moveY = gsap.quickTo(cursorEl,"y",{duration:.5,ease:"power3.out"});

  let overPanel=false, hoverPanel=false, cursorNow="";
  const setCursor = v=>{ if(v===cursorNow) return; cursorNow=v; el.style.cursor=v; };
  function updateCursor(){
    if(fs.active||entryActive||entrySettled) return setCursor("");
    if(dragging) return setCursor("grabbing");
    return setCursor(hoverPanel?"grab":"");
  }
  function setView(on){
    if(entryActive||entrySettled) on=false; if(dragging) on=false;
    if(on===overPanel){ updateCursor(); return; }
    overPanel=on; updateCursor();
    gsap.killTweensOf(cursorEl,"scale,autoAlpha,opacity,visibility");
    gsap.to(cursorEl,{scale:on?1:0,autoAlpha:on?1:0,duration:on?.35:.25,ease:on?"power3.out":"power3.in"});
  }
  function setHover(on){ hoverPanel=on; setView(on); }
  function refreshHover(){
    if(!pointerInside||lastPType!=="mouse"||!Number.isFinite(lastPX)) return;
    if(fs.active){ setHover(false); return; }
    setHover(panelAtPointer(lastPX,lastPY)!==null);
  }
  const inputLocked = ()=>fs.active||entryActive||entrySettled;

  function onWheel(e){ e.preventDefault(); if(inputLocked()) return; userInteracted=true; pendingFocus=null; target+=(e.deltaY||e.deltaX)*WHEEL; lastInput=performance.now(); snapped=false; }
  function onPointerDown(e){
    suppressClick=false; if(inputLocked()||dragging) return; if(e.button!==0&&e.pointerType==="mouse") return;
    dragging=true; dragPointerId=e.pointerId; dragPointerType=e.pointerType||"mouse";
    try{ el.setPointerCapture(e.pointerId); }catch(_){}
    const p=localPoint(e); dragLastX=p.x; lastPX=p.x; lastPY=p.y; dragDist=0; dragVel=0; dragMoveT=performance.now();
    setView(false); velocity=0; pendingFocus=null; userInteracted=true; snapped=false; lastInput=dragMoveT;
  }
  function onPointerMove(e){
    const p=localPoint(e);
    if(dragging && e.pointerId===dragPointerId){
      const sens = dragPointerType==="mouse"?DRAG:TOUCH_DRAG, dx=p.x-dragLastX;
      dragLastX=p.x; dragDist+=Math.abs(dx); target-=dx*sens; dragVel=dragVel*.6+-dx*sens*.4;
      dragMoveT=performance.now(); lastInput=dragMoveT; snapped=false;
    }
    lastPX=p.x; lastPY=p.y; lastPType=e.pointerType||"mouse"; pointerInside=true;
    if(e.pointerType!=="mouse") return;
    moveX(p.x); moveY(p.y);
    if(fs.active){ setHover(false); return; }
    setHover(panelAtPointer(p.x,p.y)!==null);
  }
  function onPointerUp(e){
    if(!dragging) return; if(e && dragPointerId!==null && e.pointerId!==dragPointerId) return;
    dragging=false;
    if(dragPointerId!==null){ try{ el.releasePointerCapture(dragPointerId); }catch(_){} dragPointerId=null; }
    velocity = performance.now()-dragMoveT>FLICK_IDLE_MS?0:dragVel; dragVel=0; lastInput=performance.now(); snapped=false;
    suppressClick = dragDist>(dragPointerType==="mouse"?CLICK_SLOP:TOUCH_CLICK_SLOP);
    if(dragPointerType==="mouse") setHover(panelAtPointer(lastPX,lastPY)!==null); else updateCursor();
  }
  function onClick(e){
    if(suppressClick){ suppressClick=false; return; } if(inputLocked()) return;
    const p=localPoint(e), hit=panelAtPointer(p.x,p.y); if(!hit) return;
    if(centeredPanel && hit.poolIdx===centeredPanel.poolIdx){ pendingFocus=null; openFocus(); return; }
    userInteracted=true; velocity=0; target=centerForIndex(nearestIndex(scroll+hit.centerX)); snapped=true;
    pendingFocus={srcIndex:hit.srcIndex}; setView(false);
  }

  function rankList(list, desc){
    let rank=0, prev=-1;
    return list.map(o=>{ if(prev>=0 && (desc? prev-o.dist : o.dist-prev)>1) rank++; prev=o.dist; return {idx:o.idx,rank}; });
  }
  function openFocus(){
    if(fs.active||!centeredPanel) return;
    const src = sources[centeredPanel.srcIndex]; if(!src||!src.tex) return;
    fs.active=true; fs.srcIndex=centeredPanel.srcIndex; const fp=centeredPanel.poolIdx; fs.poolIdx=fp;
    target = centerForIndex(nearestIndex(scroll));
    const fx = lastCenterX[fp]||0;
    const others = pool.map((_,idx)=>({idx,x:lastCenterX[idx]})).filter(o=>o.idx!==fp&&o.x!==undefined)
      .map(o=>({idx:o.idx,dist:Math.abs(o.x-fx)})).sort((a,b)=>a.dist-b.dist);
    const ranked = rankList(others,false);
    LENS_FX_KEYS.forEach(k=>fxFull[k]=U[k].value);
    if(fs.anim) fs.anim.kill();
    const sp={v:focusScale}, tl=gsap.timeline();
    tl.to(fs,{lensFx:0,duration:FOCUS.lensFade,ease:"power3.out"},0);
    tl.to(sp,{v:FOCUS.centerScale,duration:FOCUS.focusDuration,ease:FOCUS.focusEase,onUpdate(){focusScale=sp.v;}},0);
    ranked.forEach(o=>tl.to(drop,{[o.idx]:1,duration:FOCUS.cardDuration,ease:FOCUS.cardEase},o.rank*FOCUS.stagger));
    fs.anim=tl; setView(false); onFocus(true);
  }
  function closeFocus(){
    if(!fs.active) return; if(fs.anim) fs.anim.kill();
    const fx = lastCenterX[fs.poolIdx]||0;
    const others = pool.map((_,idx)=>({idx,x:lastCenterX[idx]})).filter(o=>o.x!==undefined&&(drop[o.idx]||0)>0)
      .map(o=>({idx:o.idx,dist:Math.abs(o.x-fx)})).sort((a,b)=>b.dist-a.dist);
    const ranked = rankList(others,true);
    onFocus(false);
    const sp={v:focusScale};
    const tl=gsap.timeline({onComplete(){ fs.active=false; fs.srcIndex=-1; updateCursor(); }});
    tl.to(fs,{lensFx:1,duration:FOCUS.lensFade*.8,ease:"power3.inOut"},0);
    tl.to(sp,{v:1,duration:FOCUS.focusDuration*.85,ease:FOCUS.focusEase,onUpdate(){focusScale=sp.v;}},0);
    ranked.forEach(o=>tl.to(drop,{[o.idx]:0,duration:FOCUS.cardDuration*.85,ease:FOCUS.cardEase},o.rank*FOCUS.stagger*.7));
    fs.anim=tl;
  }

  function playEntry(){
    if(!entryOn){ onEntryDone(true); return; }
    pEntry.fill(0); growArr.fill(0); entryActive=true; entrySettled=false; onEntryDone(false);
    fs.lensFx=0; target=centerForIndex(nearestIndex(scroll)); scroll=target; velocity=0; snapped=true;
    layout();
    const vis=[]; for(let k=0;k<lastCenterX.length;k++) if(lastCenterX[k]!==undefined) vis.push(k);
    const tl=gsap.timeline({delay:ENTRY.delay}), spread=ENTRY.stagger*Math.max(vis.length-1,1); let lastRiseEnd=0;
    vis.forEach(idx=>{ const t0=Math.random()*spread; lastRiseEnd=Math.max(lastRiseEnd,t0+ENTRY.riseDuration);
      tl.to(pEntry,{[idx]:1,duration:ENTRY.riseDuration,ease:ENTRY.riseEase},t0); });
    tl.call(()=>{ entryActive=false; entrySettled=true; },[],lastRiseEnd);
    const cSrc=centerIndex(scroll), Ng=sources.length, midRep=Math.floor(REPEATS/2), gl=[]; let maxRank=0;
    for(let k=0;k<lastCenterX.length;k++){
      if(lastCenterX[k]===undefined||Math.floor(k/Ng)!==midRep) continue;
      let di=(k%Ng)-cSrc; if(di>Ng/2) di-=Ng; if(di<-Ng/2) di+=Ng;
      const r=Math.abs(di); maxRank=Math.max(maxRank,r); gl.push({idx:k,rank:r});
    }
    const growStart=lastRiseEnd+ENTRY.growDelay; let growEnd=growStart;
    tl.to(fs,{lensFx:1,duration:ENTRY.lensBloom,ease:ENTRY.lensBloomEase},growStart);
    gl.forEach(v=>{ const t0=growStart+(maxRank-v.rank)*ENTRY.growStagger; growEnd=Math.max(growEnd,t0+ENTRY.growDuration);
      tl.to(growArr,{[v.idx]:1,duration:ENTRY.growDuration,ease:ENTRY.growEase},t0); });
    tl.call(()=>{ entrySettled=false; growArr.fill(1); onEntryDone(true); updateCursor(); },[],growEnd);
    entryAnim=tl;
  }

  function step(dir){ if(inputLocked()) return; userInteracted=true; velocity=0; pendingFocus=null;
    target=centerForIndex(nearestIndex(scroll)+dir); snapped=true; lastInput=performance.now(); }

  el.addEventListener("wheel",onWheel,{passive:false});
  el.addEventListener("pointerdown",onPointerDown);
  el.addEventListener("pointermove",onPointerMove);
  el.addEventListener("pointerup",onPointerUp);
  el.addEventListener("pointercancel",onPointerUp);
  el.addEventListener("pointerenter",e=>{pointerInside=true;lastPType=e.pointerType||"mouse";});
  el.addEventListener("pointerleave",()=>{pointerInside=false;setHover(false);});
  el.addEventListener("click",onClick);

  let raf=0, running=true, visible=false, entryArmed=true;
  function tick(){
    if(!running) return;
    if(!visible||document.hidden){ raf=0; return; }
    if(!dragging){
      target+=velocity; velocity*=FRICTION; if(Math.abs(velocity)<.05) velocity=0;
      if(!snapped && !fs.active && performance.now()-lastInput>SNAP_IDLE_MS){ target=centerForIndex(nearestIndex(scroll)); snapped=true; }
    }
    const follow = dragging&&dragPointerType!=="mouse" ? TOUCH_EASE : (snapped&&!pendingFocus ? SNAP_EASE : EASE);
    scroll += (target-scroll)*follow;
    const ci=centerIndex(scroll); if(ci!==lastCenter){ lastCenter=ci; onActive(ci); }
    const rawSpeed=scroll-prevScroll; prevScroll=scroll;
    const norm=Math.min(1,Math.abs(rawSpeed)/Math.max(1,SHRINK_MAX));
    scrollEnergy += (norm-scrollEnergy)*(norm>scrollEnergy?SHRINK_ATTACK:SHRINK_DECAY);
    layout(); refreshHover();
    if(pendingFocus && !fs.active && Math.abs(target-scroll)<.5){
      const pf=pendingFocus; pendingFocus=null;
      if(centeredPanel && centeredPanel.srcIndex===pf.srcIndex) openFocus();
    }
    U.uCenter.value.set(LENS.posX,LENS.posY); U.uAspect.value=W/H; U.uTime.value=performance.now()*.001;
    U.uRotation.value=LENS.rotation*Math.PI/180;
    LENS_FX_KEYS.forEach(k=>U[k].value=fxFull[k]*fs.lensFx);
    renderer.setRenderTarget(rt); renderer.render(scene,camera);
    renderer.setRenderTarget(null); renderer.render(lensScene,lensCam);
    raf=requestAnimationFrame(tick);
  }
  const startLoop=()=>{ if(!running||raf) return; raf=requestAnimationFrame(tick); };
  /* Intro animation plays ONLY once the section is actually on screen -
     page load / tab switch / scrolling elsewhere never triggers it. */
  function maybeStartEntry(){
    if(!entryArmed || !visible || document.hidden) return;
    entryArmed = false;
    if(entryOn) playEntry(); else onEntryDone(true);
  }
  /* Intro timeline is paused whenever the section (or the tab) is off screen */
  function syncEntryPause(){
    if(!entryAnim) return;
    if(!visible || document.hidden){ if(entryAnim.isActive()) entryAnim.pause(); }
    else if(entryAnim.paused() && entryAnim.progress() < 1) entryAnim.play();
  }
  startLoop();

  function onResize(){
    W=Math.max(1,mount.clientWidth); H=Math.max(1,mount.clientHeight); PANEL_H=panelHFor(); recomputeTotal();
    renderer.setSize(W,H); camera.left=-W/2; camera.right=W/2; camera.top=H/2; camera.bottom=-H/2; camera.updateProjectionMatrix();
    const ratio=Math.min(window.devicePixelRatio||1,2); renderer.setPixelRatio(ratio);
    rt.setSize(W*ratio,H*ratio); U.uRes.value.set(W*ratio,H*ratio);
    if(!userInteracted){ scroll=centerForIndex(0); target=scroll; }
  }
  new ResizeObserver(onResize).observe(mount);
  new IntersectionObserver(([en])=>{ const b=en&&en.boundingClientRect; if(!b||(b.width===0&&b.height===0)) return;
    visible=en.isIntersecting; if(visible){ startLoop(); maybeStartEntry(); } syncEntryPause(); }).observe(mount);
  document.addEventListener("visibilitychange",()=>{ if(!document.hidden){ startLoop(); maybeStartEntry(); } syncEntryPause(); });

  return {
    closeFocus, next:()=>step(1), previous:()=>step(-1),
    setColor(hex){ const c=new THREE.Color(hex); gsap.to(U.uBlueColor.value,{r:c.r,g:c.g,b:c.b,duration:.6,ease:"power2.out"}); }
  };
}

/* ===================== Start (font load hone ke baad covers banein) ===================== */
function showFallback(){
  if(mount) mount.style.display = "none";
  const f = document.getElementById("fallback");
  if(f) f.style.display = "flex";
}
(async function init(){
  try{ await Promise.all([
    document.fonts.load('900 56px "Playfair Display"'),
    document.fonts.load('700 66px "Playfair Display"')
  ]); }catch(e){}
  setTitle(0);
  if(typeof THREE === "undefined" || typeof gsap === "undefined"){ showFallback(); return; }
  try{ engine = createCarousel(); }catch(e){ engine = null; if(window.console) console.warn("Songs carousel:", e); }
  if(!engine) showFallback();
})();

/* =====================================================================
   IN-PAGE PLAYBACK for the carousel (Music-page style)
   - "Play Song" now plays the mp3 inside the page via AF.createAudio()
     (same single-audio player as music.php / Now Playing).
   - Matches the carousel song to AF_SONGS by title -> artist -> fuzzy;
     if there is no match it plays a random AF_SONGS track.
   - Click again toggles pause; label follows play/pause state.
   - Keeps the YouTube href as fallback when songs-data is unavailable.
   ===================================================================== */
(function () {
  if (!infoLink) return;
  let a = null;
  const norm = s => (s || "").toLowerCase().replace(/[^a-z0-9]+/g, " ").trim();
  const list = () => (typeof AF_SONGS !== "undefined" && AF_SONGS) ? AF_SONGS : [];
  function matchIdx(s) {
    const L = list();
    if (!L.length || !s) return -1;
    const t = norm(s.title), ar = norm(s.artist);
    let i = t ? L.findIndex(x => norm(x.t) === t) : -1;
    if (i < 0 && ar) i = L.findIndex(x => norm(x.a) === ar);
    if (i < 0 && t) i = L.findIndex(x => norm(x.t).includes(t) || t.includes(norm(x.t)));
    return i;
  }
  function ensureAudio() {
    if (a || !window.AF || !AF.createAudio) return a;
    a = AF.createAudio();
    window.__songsAudio = a;
    a.addEventListener("play", sync);
    a.addEventListener("pause", sync);
    a.addEventListener("ended", sync);
    return a;
  }
  function sync() {
    const i = matchIdx(SONGS[active]);
    const mine = !!(a && String(active) === a.dataset.for);
    const on = !!(a && !a.paused && ((i >= 0 && String(i) === a.dataset.i) || mine));
    infoLink.textContent = on ? "\u23F8 Pause" : "\u25B6 Play Song";
    infoLink.setAttribute("aria-label", on ? "Pause song" : "Play song");
  }
  infoLink.addEventListener("click", function (e) {
    const L = list();
    if (!L.length) return;                    /* no song data -> keep old link */
    e.preventDefault();
    let i = matchIdx(SONGS[active]);
    if (i < 0) i = Math.floor(Math.random() * L.length);   /* random fallback */
    const el = ensureAudio();
    if (!el || !L[i]) return;
    if (String(i) === el.dataset.i && el.src) {
      el.dataset.for = String(active);
      if (el.paused) el.play().catch(function () {}); else el.pause();
      return;
    }
    el.dataset.i = String(i);
    el.dataset.for = String(active);
    el.src = L[i].s;
    el.play().catch(function () {});
  });
  const _onActive = onActive;
  onActive = function (i) { _onActive(i); sync(); };
  sync();
})();
