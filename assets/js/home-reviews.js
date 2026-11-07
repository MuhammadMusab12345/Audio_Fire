/* ==========================================================================
   HOME PAGE - REVIEWS carousel section (#reviews)
   Moved out of index.php: identical logic and sample review data.
   ========================================================================== */
(function () {
'use strict';
/* SAMPLE reviews - replace with real reviews before launch. Fields: name, place, rating, text, optional avatar image path */
const REVIEWS=[
["Ayesha Khan","Karachi",5.0,"The hero animation is stunning. I stayed on the page just to watch the artists change.", "https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=160&h=160&q=85"],
["Daniel Brooks","London",4.5,"Clean design and very smooth. The colours shifting with each artist is a great touch.", "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=160&h=160&q=85"],
["Sofia Martins","Lisbon",5.0,"So easy to discover artists here. Everything feels premium and fast.", "https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=160&h=160&q=85"],
["Hamza Ali","Lahore",4.5,"Best looking music site I have seen this year, and it works perfectly on my phone too.", "https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=160&h=160&q=85"],
["Emily Carter","Toronto",4.0,"Beautiful layout and the cards feel really smooth. I would love even more artist details.", "https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=160&h=160&q=85"],
["Omar Farooq","Dubai",5.0,"The glass cards look incredible. You can tell a lot of care went into every section.", "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=160&h=160&q=85"],
["Lena Fischer","Berlin",4.5,"Loads quickly and the animations never feel too much. Very classy.", "https://images.unsplash.com/photo-1531123897727-8f129e1688ce?auto=format&fit=crop&w=160&h=160&q=85"],
["Bilal Ahmed","Islamabad",4.8,"I keep coming back for the featured artists section. The design is simply on another level.", "https://images.unsplash.com/photo-1507591064344-4c6ce005b128?auto=format&fit=crop&w=160&h=160&q=85"],
["Maya Thompson","Sydney",4.7,"Love the dark textured look. It feels like a real concert poster.", "https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=160&h=160&q=85"],
["Zain Malik","Karachi",4.6,"Smooth scrolling and great colours. The page changes mood with every singer.", "https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=160&h=160&q=85"],
["Chloe Dubois","Paris",4.9,"Elegant and modern. The details on the cards are beautiful.", "https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=160&h=160&q=85"],
["Arjun Mehta","Mumbai",4.4,"Really nice experience overall. The console section is a cool surprise.", "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=160&h=160&q=85"],
["Fatima Noor","Lahore",5.0,"Absolutely gorgeous design. I showed it to all my friends.", "https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=160&h=160&q=85"],
["Lucas Almeida","Sao Paulo",4.6,"Great vibe from the first second. The glitch effect is a really fun touch.", "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=160&h=160&q=85"],
["Hannah Wilson","Manchester",4.3,"Very polished. A few more filters for finding artists would make it perfect.", "https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=160&h=160&q=85"],
["Usman Tariq","Faisalabad",4.8,"Premium feel from top to bottom. The transitions are super clean.", "https://images.unsplash.com/photo-1507591064344-4c6ce005b128?auto=format&fit=crop&w=160&h=160&q=85"],
["Isabella Rossi","Milan",4.9,"The colours blend so well with the background. Feels like a luxury brand.", "https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=160&h=160&q=85"],
["Noah Anderson","Chicago",4.5,"Fast, clean and easy to browse. The artist cards are my favourite part.", "https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=160&h=160&q=85"],
["Sana Iqbal","Multan",4.7,"Beautiful website. It looks great on both my laptop and my phone."],
["Ethan Walker","Los Angeles",4.2,"Love the style. I would like a bit more info under each artist."],
["Mariam Said","Cairo",5.0,"One of the most beautiful music sites I have visited. Truly impressive."],
["Kenji Tanaka","Tokyo",4.6,"Smooth animations and a very consistent look across all sections."],
["Olivia Green","Dublin",4.8,"The theme colours changing with each artist make the whole page feel alive."],
["Ali Raza","Rawalpindi",4.5,"Simple, modern and stylish. Exactly how a music website should feel."],
["Zara Hussain","Karachi",4.9,"Gorgeous from start to finish. I could scroll through this page all day."]
];
const ANGLES=[-10,7,-6,9,-8], INTERVAL=3000;
const row=document.getElementById('rv-row'), dotsEl=document.getElementById('rv-dots'), sec=document.getElementById('reviews');
const esc=s=>String(s).replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const initials=n=>n.split(' ').map(p=>p[0]).slice(0,2).join('').toUpperCase();
const perView=()=>innerWidth>=1280?5:innerWidth>=1024?4:innerWidth>=700?2:1;
let pv=perView(), page=0, paused=false, visible=true, busy=false;
const pages=()=>Math.ceil(REVIEWS.length/pv);
const item=(p,k)=>REVIEWS[(p*pv+k)%REVIEWS.length];

function inner(r){
  const [name,place,rating,text,img]=r;
  const av=img?`<img src="${esc(img)}" alt="" style="width:100%;height:100%;border-radius:50%;object-fit:cover">`:initials(name);
  return `<div class="av" aria-hidden="true">${av}</div>
    <div class="name">${esc(name)}</div><div class="place">Fan, ${esc(place)}</div>
    <div class="stars" role="img" aria-label="${rating} out of 5">★★★★★<i style="width:${rating/5*100}%">★★★★★</i></div>
    <p class="txt">${esc(text)}</p>
    <div class="badge"><span style="color:var(--rv-s)">★</span><b>${rating.toFixed(1)}</b></div>`;
}
function build(){
  pv=perView(); row.style.setProperty('--rv-n',pv); row.innerHTML='';
  for(let k=0;k<pv;k++){
    const s=document.createElement('div'); s.className='slot'; s.style.setProperty('--rv-rot',ANGLES[k%ANGLES.length]+'deg');
    s.innerHTML=`<div class="shape"></div><article class="card" tabindex="0">${inner(item(page,k))}</article>`;
    row.appendChild(s);
  }
  dotsEl.innerHTML=''; for(let i=0;i<pages();i++){const b=document.createElement('button');b.setAttribute('aria-label','Reviews page '+(i+1));b.onclick=()=>go(i);dotsEl.appendChild(b);}
  mark();
}
const mark=()=>[...dotsEl.children].forEach((b,i)=>b.classList.toggle('on',i===page));

/* The theme tokens live on #reviews itself (never on :root) so nothing else moves */
function applyTheme(t){const r=sec.style;r.setProperty('--rv-a',t.accent);r.setProperty('--rv-s',t.soft||t.accentSoft);r.setProperty('--rv-m',t.mid);r.setProperty('--rv-d',t.deep);}
/* The hero fires: window.dispatchEvent(new CustomEvent('themechange',{detail:{accent,soft,mid,deep}})) */
addEventListener('themechange',e=>e.detail&&applyTheme(e.detail));
let demoTheme=false; /* inside the site the hero drives the theme - no demo cycling */

function go(n){
  if(busy)return; busy=true; const slots=[...row.children], reduce=matchMedia('(prefers-reduced-motion:reduce)').matches;
  slots.forEach((s,k)=>{s.style.setProperty('--rv-d1',k*60+'ms');s.classList.remove('in');s.classList.add('out');});
  setTimeout(()=>{
    page=(n+pages())%pages();
    slots.forEach((s,k)=>{s.querySelector('.card').innerHTML=inner(item(page,k));s.classList.remove('out');s.classList.add('in');});
    mark(); if(demoTheme&&window.THEMES)applyTheme(THEMES[page%THEMES.length]);
    setTimeout(()=>{slots.forEach(s=>s.classList.remove('in'));busy=false;},reduce?300:400+slots.length*60);
  },reduce?150:300+slots.length*60);
}
setInterval(()=>{if(!paused&&visible)go(page+1);},INTERVAL);
sec.addEventListener('mouseenter',()=>paused=true);sec.addEventListener('mouseleave',()=>paused=false);
sec.addEventListener('focusin',()=>paused=true);sec.addEventListener('focusout',()=>paused=false);
row.addEventListener('mousemove',e=>{const c=e.target.closest('.card');if(!c)return;const b=c.getBoundingClientRect();c.style.setProperty('--rv-mx',e.clientX-b.left+'px');c.style.setProperty('--rv-my',e.clientY-b.top+'px');});
new IntersectionObserver(([e])=>visible=e.isIntersecting).observe(sec);
let rt;addEventListener('resize',()=>{clearTimeout(rt);rt=setTimeout(()=>{if(perView()!==pv){page=0;build();}},200);});
build();
})();
