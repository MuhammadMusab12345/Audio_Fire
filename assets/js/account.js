/* ==========================================================================
   ACCOUNT PAGE (account.php) - characters, sign in / sign up panels,
   password strength, card tilt, colour cycling.
   Extracted verbatim from the original "Final Account page.html".
   + hashchange support so the shared navbar links (account.php#signin and
   account.php#signup) open the right tab even when this page is already open.
   ========================================================================== */
const chars=document.getElementById('chars'),$$=s=>[...document.querySelectorAll(s)];
let mode='idle',mx=innerWidth/2,my=innerHeight/2;
const cl=v=>Math.max(-1,Math.min(1,v));
const set=o=>{for(const k in o)chars.style.setProperty('--'+k,o[k])};
function render(){
  if(mode==='idle'){
    const r=chars.getBoundingClientRect(),dx=cl((mx-(r.left+r.width/2))/320),dy=cl((my-(r.top+r.height/3))/320);
    set({fx:dx*8,fy:dy*5,px:dx*3,py:dy*3,ps:-12-dx*3,ph:0,bs:-dx*2});
  }else if(mode==='typing'){            /* leaning toward the form, looking at it */
    set({fx:10,fy:5,px:3,py:2,ps:-20,ph:26,bs:-6});
  }else{                                /* password visible: look away, straighten up */
    set({fx:-9,fy:-3,px:-3,py:-2,ps:0,ph:-34,bs:0});
  }
}
function update(){
  const a=document.activeElement,shown=$$('.panel.on input[data-pw]').some(i=>i.type==='text');
  mode=shown?'reveal':(a&&a.closest('.panel.on')&&a.matches('input,select'))?'typing':'idle';
  render();
}
addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;if(mode==='idle')render()});
document.addEventListener('focusin',update);
document.addEventListener('focusout',()=>setTimeout(update,0));
document.addEventListener('input',e=>{if(mode==='typing'){chars.style.setProperty('--ph',34);setTimeout(render,120)}});
$$('.eyebtn').forEach(b=>{
  b.addEventListener('mousedown',e=>e.preventDefault());
  b.addEventListener('click',()=>{const i=b.parentElement.querySelector('input'),s=i.type==='password';i.type=s?'text':'password';b.classList.toggle('shown',s);update()});
});
(function blink(){chars.classList.add('blink');setTimeout(()=>chars.classList.remove('blink'),140);setTimeout(blink,2800+Math.random()*2600)})();
function go(w){
  $$('.panel').forEach(p=>p.classList.toggle('on',p.id===w));
  $$('.msg').forEach(m=>{m.textContent='';m.className='msg'});
  document.title='Account | Audio Fire — '+(w==='signup'?'Sign Up':'Sign In');
  history.replaceState(null,'','#'+w);update();
}
$$('[data-go]').forEach(a=>a.addEventListener('click',e=>{e.preventDefault();go(a.dataset.go)}));
/* the Admin Login tab is only rendered when no administrator session exists,
   so the guard below keeps a stale #adminlogin hash from blanking the page */
const hasPanel=id=>!!document.getElementById(id);
if(location.hash==='#signup')go('signup');
else if(location.hash==='#adminlogin'&&hasPanel('adminlogin'))go('adminlogin');
/* navbar deep links: account.php#signin | account.php#signup */
addEventListener('hashchange',()=>{
  const h=location.hash.slice(1);
  if(h==='signup'||h==='signin')go(h);
  else if(h==='adminlogin'&&hasPanel('adminlogin'))go('adminlogin');
});
/* ==========================================================================
   REAL AUTH (server side: api/auth.php + includes/auth.php)
   * register / login over fetch - the page never reloads
   * CSRF token in the af-csrf meta tag, rotated by the server on sign in
   * password_hash() on the server, throttled login attempts
   * after sign in the guest's saved songs/artists are merged into the
     database (AFS.sync -> api/save.php?action=merge)
   ========================================================================== */
const meta=n=>{const m=document.querySelector('meta[name="'+n+'"]');return m?m.content:''};
const csrfToken=()=>meta('af-csrf');
function setCsrf(t){const m=document.querySelector('meta[name="af-csrf"]');if(m&&t)m.setAttribute('content',t)}
function busy(f,on){f.querySelectorAll('.btn').forEach(b=>{b.classList.toggle('af-busy',on);b.disabled=on});f.querySelectorAll('input,select').forEach(i=>i.disabled=on)}
async function postAuth(action,payload){
  const r=await fetch('api/auth.php?action='+action,{method:'POST',
    headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken()},
    credentials:'same-origin',body:JSON.stringify(payload)});
  return r.json();
}
function done(name,email){
  /* sign in success: refresh AFS (merges guest saves into the database) */
  if(window.AFS){AFS.sync&&AFS.sync();}
  document.getElementById('siName').textContent=name;
  document.getElementById('siEmail').textContent=(email?email+' · ':'')+'Your saves are stored in your account now.';
  go('signedin');
  if(window.AFToast)AFToast.show('Signed in as '+name,'ok');
}
const fSignin=document.getElementById('formSignin'),fSignup=document.getElementById('formSignup');
if(fSignin)fSignin.addEventListener('submit',async e=>{
  e.preventDefault();const m=fSignin.querySelector('.msg'),v=n=>(fSignin.elements[n]||{}).value||'';m.className='msg';
  if(!v('email').trim()||!v('password'))return m.textContent='Please fill in all fields.';
  if(v('password').length<6)return m.textContent='Password must be at least 6 characters.';
  busy(fSignin,true);
  try{
    const d=await postAuth('login',{email:v('email').trim(),password:v('password')});
    busy(fSignin,false);
    if(!d.ok)return m.textContent=d.error||'Could not sign in.';
    setCsrf(d.csrf);
    m.className='msg ok';m.textContent=d.message||'Welcome back!';
    done(d.user&&d.user.name?d.user.name:'friend',d.user&&d.user.email?d.user.email:'');
  }catch(err){busy(fSignin,false);m.textContent='Network problem - please try again.'}
});
if(fSignup)fSignup.addEventListener('submit',async e=>{
  e.preventDefault();const m=fSignup.querySelector('.msg'),v=n=>(fSignup.elements[n]||{}).value||'';m.className='msg';
  const bad=[...fSignup.querySelectorAll('input:not([type=checkbox])')].some(i=>!i.value.trim());
  if(bad)return m.textContent='Please fill in all fields.';
  if(!/^\S+@\S+\.\S+$/.test(v('email')))return m.textContent='Enter a valid email address.';
  if(v('password').length<6)return m.textContent='Password must be at least 6 characters.';
  if(v('password')!==v('confirm'))return m.textContent='Passwords do not match.';
  if(fSignup.elements.terms&&!fSignup.elements.terms.checked)return m.textContent='Please accept the Terms to continue.';
  busy(fSignup,true);
  try{
    const d=await postAuth('register',{name:v('name').trim(),username:v('username').trim(),email:v('email').trim(),
      phone:v('phone').trim(),address:v('address').trim(),password:v('password'),confirm:v('confirm')});
    busy(fSignup,false);
    if(!d.ok)return m.textContent=d.error||'Could not create the account.';
    setCsrf(d.csrf);
    m.className='msg ok';m.textContent=d.message||'Account created.';
    done(d.user&&d.user.name?d.user.name:v('name').trim(),d.user&&d.user.email?d.user.email:v('email').trim());
  }catch(err){busy(fSignup,false);m.textContent='Network problem - please try again.'}
});
/* signed-in state on load (session exists) + logout */
if(meta('af-user')){
  document.getElementById('siName').textContent=meta('af-user');
  document.getElementById('siEmail').textContent='Signed in. Your saves are stored in your account.';
  document.getElementById('signedin').hidden=false;
  $$('.panel').forEach(p=>p.classList.toggle('on',p.id==='signedin'));
}
const lo=document.getElementById('siLogout');
if(lo)lo.addEventListener('click',async()=>{
  lo.classList.add('af-busy');
  try{await postAuth('logout',{});}catch(err){}
  location.reload();
});

/* smoke colours: change one by one */
const pal=['#8b5cf6','#ec4899','#f97316','#06b6d4','#22c55e','#3b82f6','#eab308'];let ci=0;
function smoke(){const r=document.documentElement.style;r.setProperty('--s1',pal[ci%7]);r.setProperty('--s2',pal[(ci+2)%7]);r.setProperty('--s3',pal[(ci+4)%7]);ci++}
smoke();setInterval(smoke,6000);
/* 3D card tilt */
const card=document.querySelector('.card');
addEventListener('mousemove',e=>{card.style.setProperty('--ry',((e.clientX/innerWidth-.5)*6).toFixed(2));card.style.setProperty('--rx',((.5-e.clientY/innerHeight)*5).toFixed(2))});
/* floating music notes */
const nt=document.getElementById('notes');
for(let i=0;i<9;i++){const n=document.createElement('span');n.textContent=['♪','♫','♬'][i%3];n.style.cssText=`left:${8+Math.random()*84}%;font-size:${16+Math.random()*20}px;animation-duration:${9+Math.random()*8}s;animation-delay:${-Math.random()*14}s`;nt.appendChild(n)}
/* password strength */
const mb=$$('#meter i'),ml=document.querySelector('#meter span');
document.querySelector('#signup input[name=password]').addEventListener('input',e=>{const v=e.target.value;
const sc=!v?0:Math.min(4,(v.length>=6)+(v.length>=10)+(/[A-Z]/.test(v)&&/\d/.test(v))+/[^\w\s]/.test(v));
const col=['#f43f5e','#f43f5e','#f97316','#eab308','#22c55e'][sc];
mb.forEach((b,i)=>{b.classList.toggle('on',i<sc);b.style.setProperty('--c',col)});
ml.textContent=['Password strength','Weak','Okay','Good','Strong'][sc]});
/* characters react to submit */
function react(c){chars.classList.remove('shake','happy');void chars.offsetWidth;chars.classList.add(c);setTimeout(()=>chars.classList.remove(c),900)}
$$('form').forEach(f=>f.addEventListener('submit',()=>setTimeout(()=>react(f.querySelector('.msg').classList.contains('ok')?'happy':'shake'),0)));
update();
