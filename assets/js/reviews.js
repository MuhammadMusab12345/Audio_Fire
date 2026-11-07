/* ==========================================================================
   REVIEWS PAGE (reviews.php) - review cards, rating form, colour cycling,
   floating notes. Extracted verbatim from the original "Final reviews.html".
   ========================================================================== */
const KEY='cloudai_reviews_v2';
const av=n=>'https://i.pravatar.cc/160?img='+n;
const seed=[
 {n:'Ayesha Noor',r:'Music Lover',s:5,t:'The songs sound great and the whole site feels smooth. The glass design is beautiful.',i:'Nothing, it\'s great',p:av(47)},
 {n:'Hamza Ali',r:'Listener',s:4,t:'Songs play one after another without any problem. Lovely collection overall.',i:'More songs',p:av(12)},
 {n:'Sara Khan',r:'Student',s:5,t:'Love the animations and the colours that keep changing. Feels very modern.',i:'Player',p:av(32)},
 {n:'Bilal Ahmed',r:'Designer',s:3,t:'Good start. I would like a playlist option and a search bar.',i:'Design, Speed',p:av(15)}
];
let data=[];try{data=JSON.parse(localStorage.getItem(KEY))||[]}catch(e){}
if(!data.length)data=seed;
let rating=0,photo='',chosen=new Set();
const $=id=>document.getElementById(id),grid=$('grid');

function render(){
 grid.innerHTML='';
 data.forEach((d,k)=>{
  const c=document.createElement('div');c.className='card';
  c.innerHTML=`<div class="tab"><strong></strong><small></small></div><img class="avatar" alt="">
  <div class="body"><div class="stars">${'★'.repeat(d.s)}<span class="off">${'★'.repeat(5-d.s)}</span></div><p class="txt"></p>${d.i?'<div class="tip"><b>Improve:</b> <span></span></div>':''}</div>`;
  c.querySelector('strong').textContent=d.n;
  c.querySelector('small').textContent=d.r||'Listener';
  const im=c.querySelector('.avatar');im.src=d.p||av(1+(k*7)%70);
  im.onerror=()=>{im.onerror=null;im.src='data:image/svg+xml,'+encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80"><rect width="80" height="80" fill="#5b34f0"/><text x="40" y="54" font-size="38" text-anchor="middle" fill="#fff" font-family="Arial">'+d.n.charAt(0).toUpperCase()+'</text></svg>')};
  c.querySelector('.txt').textContent=d.t;
  if(d.i)c.querySelector('.tip span').textContent=d.i;
  grid.appendChild(c);setTimeout(()=>c.classList.add('show'),110*k);
 });
 $('count').textContent=data.length;
 $('avg').textContent=(data.reduce((a,b)=>a+b.s,0)/data.length).toFixed(1);
}
render();

const stars=[...document.querySelectorAll('#pick span')];
stars.forEach((s,i)=>s.onclick=()=>{rating=i+1;stars.forEach((x,j)=>x.classList.toggle('on',j<rating))});
document.querySelectorAll('.chip').forEach(c=>c.onclick=()=>{c.classList.toggle('on');c.classList.contains('on')?chosen.add(c.textContent):chosen.delete(c.textContent)});

// random preview photo until user picks one
const prev=$('prev');prev.src=av(1+Math.floor(Math.random()*70));
$('upbtn').onclick=()=>$('file').click();
$('file').onchange=e=>{
 const f=e.target.files[0];if(!f)return;
 const r=new FileReader();r.onload=()=>{const im=new Image();im.onload=()=>{
  const cv=document.createElement('canvas');cv.width=cv.height=160;const x=cv.getContext('2d');
  const m=Math.min(im.width,im.height);x.drawImage(im,(im.width-m)/2,(im.height-m)/2,m,m,0,0,160,160);
  photo=cv.toDataURL('image/jpeg',.8);prev.src=photo};im.src=r.result};r.readAsDataURL(f)};

$('f').onsubmit=async e=>{
 e.preventDefault();
 const n=$('name').value.trim(),t=$('text').value.trim(),m=$('msg');m.className='msg';
 if(!n||!t||!rating){m.textContent='Please add your name, stars and a review.';return}
 const imp=[...chosen].concat($('tip').value.trim()?[$('tip').value.trim()]:[]).join(', ');
 const item={n,r:$('role').value.trim(),s:rating,t,i:imp,p:photo||prev.src};
 const localAdd=txt=>{                       // fallback: review stays in this browser
  data.unshift(item);
  try{localStorage.setItem(KEY,JSON.stringify(data))}catch(x){}
  resetForm();render();m.textContent=txt;m.classList.add('ok');grid.scrollIntoView({behavior:'smooth'});
 };
 const btn=$('f').querySelector('.btn[type=submit]');btn.classList.add('af-busy');btn.disabled=true;
 try{
  const r=await fetch('api/reviews.php?action=add',{method:'POST',
   headers:{'Content-Type':'application/json','X-CSRF-Token':(document.querySelector('meta[name=af-csrf]')||{content:''}).content},
   credentials:'same-origin',
   body:JSON.stringify({action:'add',name:n,role:item.r,rating:rating,comment:t,improve:imp,avatar:item.p,
     ts:($('ts')||{value:0}).value,af_company:($('af_company')||{value:''}).value})});
  const d=await r.json().catch(()=>({}));
  btn.classList.remove('af-busy');btn.disabled=false;
  if(r.status===401){m.textContent='Please sign in to post a review, or it stays in this browser.';localAdd('Thank you! Your review was saved in this browser.');return}
  if(d.ok){
   data.unshift({n:d.review.name,r:d.review.role,s:+d.review.rating,t:d.review.comment,i:d.review.improve||'',p:d.review.avatar||''});
   try{localStorage.setItem(KEY,JSON.stringify(data))}catch(x){}
   resetForm();render();m.textContent=d.message||'Thank you! Your review is live.';m.classList.add('ok');grid.scrollIntoView({behavior:'smooth'});
  }else if(r.status===503||r.status===0){
   localAdd('Saving reviews is offline - your review was added in this browser.');
  }else{
   m.textContent=d.error||'Could not save the review - please check the fields.';
  }
 }catch(err){
  btn.classList.remove('af-busy');btn.disabled=false;
  localAdd('No connection - your review was added in this browser.');
 }
};
function resetForm(){
 $('f').reset();rating=0;photo='';chosen.clear();
 stars.forEach(x=>x.classList.remove('on'));document.querySelectorAll('.chip').forEach(c=>c.classList.remove('on'));
 prev.src=av(1+Math.floor(Math.random()*70));
 const ts=$('ts');if(ts)ts.value=Math.floor(Date.now()/1000);   // fresh time gate
}

/* ---- server reviews: merge the stored ones on top of the demo list ----- */
fetch('api/reviews.php?action=list',{credentials:'same-origin'})
 .then(r=>r.json())
 .then(d=>{
  if(!d||!d.ok||!d.reviews||!d.reviews.length)return;
  const fromServer=d.reviews.map(r=>({n:r.name,r:r.role||'Listener',s:+r.rating,t:r.comment,i:r.improve||'',p:r.avatar||''}));
  const seen={};const merged=[];
  fromServer.concat(data).forEach(x=>{const k=x.n+'|'+x.t;if(!seen[k]){seen[k]=1;merged.push(x)}});
  data=merged;try{localStorage.setItem(KEY,JSON.stringify(data))}catch(x){}
  render();
 })
 .catch(()=>{});

// colour cycling, same idea as the account page
const pal=[['#8b5cf6','#ec4899','#06b6d4'],['#f43f5e','#f59e0b','#8b5cf6'],['#10b981','#06b6d4','#6366f1'],['#3b82f6','#ec4899','#f59e0b']];
let pi=0;setInterval(()=>{pi=(pi+1)%pal.length;const r=document.documentElement.style;r.setProperty('--s1',pal[pi][0]);r.setProperty('--s2',pal[pi][1]);r.setProperty('--s3',pal[pi][2])},6000);
// floating music notes
for(let i=0;i<14;i++){const s=document.createElement('span');s.textContent=['♪','♫','♩'][i%3];s.style.left=Math.random()*100+'%';s.style.fontSize=14+Math.random()*20+'px';s.style.animationDuration=12+Math.random()*14+'s';s.style.animationDelay=-Math.random()*20+'s';$('notes').appendChild(s)}
