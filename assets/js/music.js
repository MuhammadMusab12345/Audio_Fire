/* ==========================================================================
   MUSIC PAGE (music.php) - rotating vinyl, language filter, song grid
   Song data lives in assets/js/songs-data.js (AF_SONGS / AF_LANGS) and is
   shared with the home page "Now Playing" section.
   ========================================================================== */
(function () {
'use strict';
const T = AF_SONGS, LANGS = AF_LANGS;
const $=s=>document.querySelector(s),fmt=AF.fmt;
let cur=0,playing=false,lang="All",theme=0;
const a=AF.createAudio();a.onended=()=>go(1);
a.onplay=()=>{playing=true;ui()};a.onpause=()=>{playing=false;ui()};
const L=()=>T.map((s,i)=>lang=="All"||s.l==lang?i:-1).filter(i=>i>=0);
function setTheme(i){const c1=T[i%T.length].c,c2=T[(i+7)%T.length].c,b=$("#bg");b.style.setProperty("--c1",c1);b.style.setProperty("--c2",c2);const r=document.documentElement.style;r.setProperty("--c1",c1);r.setProperty("--c2",c2)}
setInterval(()=>setTheme(++theme),7000);
function chips(){document.querySelectorAll(".chips").forEach(c=>{c.innerHTML="";LANGS.forEach(l=>{const b=document.createElement("button");b.className="chip"+(l==lang?" on":"");b.textContent=l;b.onclick=()=>{lang=l;lists()};c.append(b)})})}
/* save hearts: every row/card carries its data (assets/js/save.js injects
   the button and talks to localStorage / the database) */
function sd(el,s){el.dataset.save="song";if(window.AFS)el.dataset.saveId=AFS.id(s.t,s.a);el.dataset.saveTitle=s.t;el.dataset.saveArtist=s.a;el.dataset.saveSrc=s.s;el.dataset.saveImg=s.i}
function row(i,n){const s=T[i],r=document.createElement("div");r.className="row"+(i==cur?" on":"");r.tabIndex=0;r.innerHTML=(n?`<i>${String(n).padStart(2,"0")}</i>`:"")+`<img src="${s.i}" alt="" loading="lazy"><div><p>${s.t}</p><small>${s.a}</small></div><em>${fmt(s.d)}</em>`;sd(r,s);r.onclick=()=>load(i,true);r.onkeydown=e=>e.key=="Enter"&&load(i,true);return r}
function lists(){chips();const l=L(),none='<p class="empty">Is language ka abhi koi song nahi hai.</p>';
 const h=$("#list");h.innerHTML=l.length?"":none;l.forEach(i=>h.append(row(i)));
 const g=$("#grid");g.innerHTML=l.length?"":none;l.forEach(i=>{const s=T[i],d=document.createElement("div");d.className="glass card"+(i==cur?" on":"");d.tabIndex=0;d.innerHTML=`<div class="cv"><img src="${s.i}" alt="" loading="lazy"><button class="pl" aria-label="Play ${s.t}">${i==cur&&playing?"⏸":"▶"}</button></div><p>${s.t}</p><small>${s.a}</small>`;sd(d,s);d.onclick=()=>i==cur?toggle():load(i,true);d.onkeydown=e=>e.key=="Enter"&&d.onclick();g.append(d)});
 const p=$("#pop");p.innerHTML=l.length?"":none;const st=Math.max(0,l.indexOf(cur));l.slice(st,st+5).forEach((i,k)=>p.append(row(i,k+1)));
 if(l.length<5&&l.length)l.slice(0,5-l.slice(st,st+5).length).filter(i=>!l.slice(st,st+5).includes(i)).forEach((i,k)=>p.append(row(i,l.slice(st,st+5).length+k+1)))}
function ui(){const s=T[cur],w=s.t.split(" ");
 $("#lab").style.backgroundImage=`url(${s.i})`;$("#cov").src=$("#bi").src=s.i;$("#ti").textContent=$("#bt").textContent=s.t;$("#ar").textContent=$("#ba").textContent=s.a;
 $("#fh").innerHTML=w.length>1?w.slice(0,-1).join(" ")+" <span class='out'>"+w.slice(-1)+"</span>":s.t;$("#ft").textContent=s.a+" · "+s.l;
 $("#bl").textContent=String(cur+1).padStart(2,"0")+". "+s.t;
 $("#pp").textContent=playing?"⏸":"▶";$("#big").textContent=playing?"Pause":"Play";
 document.querySelectorAll(".stage,.big").forEach(e=>e.classList.toggle("playing",playing));lists()}
function load(i,auto){cur=i;a.src=T[i].s;theme=i;setTheme(i);if(auto)a.play().catch(()=>{});else{playing=false;a.pause()}ui()}
function toggle(){a.src?(playing?a.pause():a.play().catch(()=>{})):load(cur,true)}
function go(d){const l=L();if(!l.length)return;const p=l.indexOf(cur);load(p<0?l[0]:l[(p+d+l.length)%l.length],true)}
setInterval(()=>{const t=a.currentTime||0,d=a.duration||T[cur].d,r=Math.min(1,t/d);$("#fill").style.width=r*100+"%";$("#ring").style.strokeDashoffset=307.9*(1-r);$("#t0").textContent=fmt(t);$("#t1").textContent=fmt(d)},250);
$("#trk").onclick=e=>{const r=e.currentTarget.getBoundingClientRect();if(a.duration)a.currentTime=(e.clientX-r.left)/r.width*a.duration};
$("#pp").onclick=$("#big").onclick=toggle;$("#nx").onclick=()=>go(1);$("#pv").onclick=()=>go(-1);
load(0,false);
})();