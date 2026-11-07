
(function(){
  const pages=document.getElementById('pages');
  const cards=[...document.querySelectorAll('.card')];
  const prev=document.getElementById('prev'), next=document.getElementById('next');
  const mobile=()=>matchMedia('(max-width:700px)').matches;
  const canHover=matchMedia('(hover:hover)').matches;
  const PAGES=pages.querySelectorAll('.page').length;
  let timer;

  /* open one card (within its own screen on desktop, across the strip on mobile) */
  function tint(c){ if(c&&c.dataset.tint) document.documentElement.style.setProperty('--tint',c.dataset.tint); }
  function setActive(c,scroll){
    tint(c);
    if(c.classList.contains('active'))return;
    const group=mobile()?cards:[...c.parentElement.querySelectorAll('.card')];
    group.forEach(x=>x.classList.toggle('active',x===c));
    if(scroll&&mobile()){           // keep the opened card centred on phones
      const cw=innerWidth*.30, ew=innerWidth*.72, i=cards.indexOf(c);
      const t=i*cw+ew/2-innerWidth/2, max=(cards.length-1)*cw+ew-innerWidth;
      pages.scrollTo({left:Math.max(0,Math.min(max,t)),behavior:'smooth'});
    }
  }
  cards.forEach(c=>{
    if(canHover){ c.tabIndex=0;
      c.addEventListener('mouseenter',()=>{clearTimeout(timer);timer=setTimeout(()=>setActive(c),60)});
      c.addEventListener('mouseleave',()=>clearTimeout(timer));
    }
    c.addEventListener('click',()=>setActive(c,true));
    c.addEventListener('focus',()=>setActive(c));
  });

  /* screens (5 artists each) */
  const cur=()=>Math.round(pages.scrollLeft/pages.clientWidth);
  const go=i=>pages.scrollTo({left:Math.max(0,Math.min(PAGES-1,i))*pages.clientWidth,behavior:'smooth'});
  const sync=()=>{ if(mobile()){prev.classList.add('hide');next.classList.add('hide');return}
    const i=cur(); tint(pages.querySelectorAll('.page')[i]&&pages.querySelectorAll('.page')[i].querySelector('.card.active')); prev.classList.toggle('hide',i<=0); next.classList.toggle('hide',i>=PAGES-1); };
  pages.addEventListener('scroll',()=>requestAnimationFrame(sync),{passive:true});
  addEventListener('resize',sync);
  prev.onclick=()=>go(cur()-1); next.onclick=()=>go(cur()+1);
  addEventListener('keydown',e=>{ if(mobile()||window.__csView)return; if(e.key==='ArrowRight')go(cur()+1); if(e.key==='ArrowLeft')go(cur()-1); });


  sync();
  if(mobile())tint(cards[0]);
})();

/* ===== Glass carousel: details close -> slide -> image grows -> details open ===== */
(function(){
  const stage=document.getElementById('stage'), cs=document.getElementById('cs');
  const slides=[...stage.querySelectorAll('.slide')], N=slides.length, half=Math.floor(N/2);
  const bgs=[...cs.querySelectorAll('.cs-bg img')], count=document.getElementById('count'), bar=document.getElementById('bar');
  const wait=ms=>new Promise(r=>setTimeout(r,ms));
  const mobile=()=>matchMedia('(max-width:700px)').matches;
  let a=0, busy=false, hover=false, seen=false, bgi=0;

  function layout(){
    slides.forEach((s,i)=>{
      const signed=((i-a+N+half)%N)-half;               // -13 .. +13 around the active slide
      s.dataset.d=Math.min(Math.abs(signed),3);
      s.style.order=signed+N;
    });
    count.textContent=String(a+1).padStart(2,'0')+' / '+String(N).padStart(2,'0');
    bar.style.width=((a+1)/N*100)+'%';
    const next=bgs[bgi^=1], prev=bgs[bgi^1];               // blurred colour behind the glass follows the photo
    next.src=slides[a].querySelector('img').src; next.classList.add('on'); prev.classList.remove('on');
  }
  async function go(n){
    n=((n%N)+N)%N;
    if(busy||n===a)return; busy=true;
    slides[a].classList.remove('open');                    // 1. details card closes
    await wait(560);
    a=n; layout();                                         // 2. photo shrinks / next photo grows, row slides
    await wait(880);
    slides[a].classList.add('open');                       // 3. new details card opens
    await wait(950);
    busy=false;
  }
  slides.forEach((s,i)=>s.addEventListener('click',e=>{ if(i!==a){e.preventDefault();go(i)} }));
  stage.querySelectorAll('.btn').forEach(b=>b.addEventListener('click',e=>e.preventDefault()));
  document.getElementById('cprev').onclick=()=>go(a-1);
  document.getElementById('cnext').onclick=()=>go(a+1);

  /* swipe */
  let sx=null;
  stage.addEventListener('pointerdown',e=>{sx=e.clientX});
  addEventListener('pointerup',e=>{ if(sx===null)return; const dx=e.clientX-sx; sx=null; if(Math.abs(dx)>60 && e.target.closest&&e.target.closest('#stage')) go(a+(dx<0?1:-1)); });

  /* keyboard + autoplay only while this section is on screen */
  new IntersectionObserver(([en])=>{ seen=en.isIntersecting; window.__csView=en.intersectionRatio>.45; },{threshold:[0,.45,.7]}).observe(cs);
  addEventListener('keydown',e=>{ if(!window.__csView)return; if(e.key==='ArrowRight')go(a+1); if(e.key==='ArrowLeft')go(a-1); });
  stage.addEventListener('mouseenter',()=>hover=true); stage.addEventListener('mouseleave',()=>hover=false);
  setInterval(()=>{ if(seen&&!hover&&!busy&&!document.hidden) go(a+1); },5600);

  layout();
  setTimeout(()=>slides[a].classList.add('open'),500);
})();

/* ===================== ALBUMS SECTION + SHARED OVERLAY ===================== */
(function () {
  var ov = document.getElementById('alb-ov');
  if (!ov) return;
  var grid = document.getElementById('alb-ov-g');
  var nameEl = ov.querySelector('.alb-ov-n');
  var lastBtn = null;
  var PLAYLIST = [];
  var PLACEHOLDER = 'data:image/svg+xml;utf8,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" fill="#241c30"/><circle cx="50" cy="50" r="16" fill="none" stroke="#8a7fb0" stroke-width="4"/><circle cx="50" cy="50" r="5" fill="#8a7fb0"/></svg>');
  var IC_PLAY = '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>';
  var IC_PAUSE = '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M6 5h4v14H6zm8 0h4v14h-4z"/></svg>';

  /* the Songs-page style video player (markup lives in album.php) */
  var plOv = document.getElementById('alb-pl');
  var plVideo = document.getElementById('alb-video');
  var plTitle = document.getElementById('alb-pl-t');
  var plArtist = document.getElementById('alb-pl-a');
  var playing = -1;

  /* albums table data, keyed by singer name (window.AF_ALBUMS from album.php) */
  function albums() {
    try { return (typeof AF_ALBUMS !== 'undefined' && AF_ALBUMS) ? AF_ALBUMS : {}; } catch (e) { return {}; }
  }
  function attr(s) {
    return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
  function playerOpen() {
    return !!plOv && plOv.classList.contains('on');
  }
  function openPlayer(i) {
    var s = PLAYLIST[i];
    if (!s || !s.s || !plOv || !plVideo) return;
    plTitle.textContent = s.t || '';
    plArtist.textContent = s.a || '';
    plVideo.src = s.s;
    plOv.classList.add('on');
    playing = i;
    /* never let two players talk at once (hero / page audio) */
    document.dispatchEvent(new CustomEvent('mh:pause'));
    plVideo.play().catch(function () {});
    var x = plOv.querySelector('.alb-pl-x');
    if (x) x.focus();
    syncIcons();
  }
  function closePlayer() {
    if (!playerOpen()) return;
    try { plVideo.pause(); plVideo.removeAttribute('src'); plVideo.load(); } catch (e) {}
    plOv.classList.remove('on');
    playing = -1;
    syncIcons();
  }
  function syncIcons() {
    var btns = grid.querySelectorAll('.pl'), i;
    for (i = 0; i < btns.length; i++) {
      var idx = +btns[i].getAttribute('data-i');
      var on = playerOpen() && playing === idx && !plVideo.paused;
      btns[i].innerHTML = on ? IC_PAUSE : IC_PLAY;
      btns[i].setAttribute('aria-label', (on ? 'Pause ' : 'Play ') + btns[i].getAttribute('data-t'));
      var card = btns[i].closest('.alb-s');
      if (card) card.classList.toggle('on', !!on);
    }
  }
  if (plVideo) {
    plVideo.addEventListener('play', syncIcons);
    plVideo.addEventListener('pause', syncIcons);
    plVideo.addEventListener('ended', syncIcons);
  }
  function render(name) {
    var als = albums()[name] || [], flat = [], html = '', i, k;
    for (i = 0; i < als.length; i++) {
      var ss = als[i].songs || [];
      for (k = 0; k < ss.length; k++) flat.push(ss[k]);
    }
    PLAYLIST = flat;
    var idx = 0;
    for (i = 0; i < als.length; i++) {
      var al = als[i], n = (al.songs || []).length;
      var cov = al.cover || PLACEHOLDER;
      html += '<article class="alb-s alb-al">' +
        '<div class="cv"><img src="' + cov + '" alt="' + attr(al.title) + '" loading="lazy" decoding="async" onerror="this.onerror=null;this.src=\'' + PLACEHOLDER + '\'">' +
        (n ? '<button type="button" class="pl" data-i="' + idx + '" data-t="' + attr(al.title) + '" aria-label="Play ' + attr(al.title) + '">' + IC_PLAY + '</button>' : '') +
        '</div>' +
        '<p title="' + attr(al.title) + '">' + al.title + '</p>' +
        '<small>' + (al.year ? al.year + ' &middot; ' : '') + al.tracks + ' track' + (al.tracks === 1 ? '' : 's') + '</small></article>';
      for (k = 0; k < n; k++) {
        var s = flat[idx];
        html += '<article class="alb-s" data-i="' + idx + '">' +
          '<div class="cv"><img src="' + s.i + '" alt="' + attr(s.t) + '" loading="lazy" decoding="async" onerror="this.onerror=null;this.src=\'' + PLACEHOLDER + '\'">' +
          '<button type="button" class="pl" data-i="' + idx + '" data-t="' + attr(s.t) + '" aria-label="Play ' + attr(s.t) + '">' + IC_PLAY + '</button></div>' +
          '<p title="' + attr(s.t) + '">' + s.t + '</p><small>' + s.a + '</small></article>';
        idx++;
      }
    }
    grid.innerHTML = html || '<p class="alb-ov-empty">Koi song available nahi hai.</p>';
    syncIcons();
  }
  function focusables() {
    return ov.querySelectorAll('button, [href], input, [tabindex]:not([tabindex="-1"])');
  }
  /* open one singer's albums: used by the cards AND by the carousel's
     "Album" buttons (same function, so both always behave alike) */
  function openByName(name, btn) {
    lastBtn = btn || null;
    nameEl.textContent = name || '';
    render(nameEl.textContent);
    ov.hidden = false;
    document.body.classList.add('alb-lock');
    ov.querySelector('.alb-ov-x').focus();
    document.addEventListener('keydown', onKey, true);
  }
  function open(btn) {
    var card = btn.closest('.alb-card');
    openByName(card ? (card.getAttribute('data-n') || '') : '', btn);
  }
  function close() {
    if (ov.hidden) return;
    closePlayer();
    ov.hidden = true;
    document.body.classList.remove('alb-lock');
    document.removeEventListener('keydown', onKey, true);
    if (lastBtn) lastBtn.focus();
  }
  function onKey(e) {
    if (ov.hidden) return;
    if (e.key === 'Escape') {
      e.preventDefault();
      if (playerOpen()) { closePlayer(); } else { close(); }
      return;
    }
    if (playerOpen()) return;            // the player keeps its own focus
    if (e.key === 'Tab') {
      var f = focusables();
      if (!f.length) return;
      var first = f[0], last = f[f.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
      else if (!ov.contains(document.activeElement)) { e.preventDefault(); first.focus(); }
    }
  }
  document.addEventListener('click', function (e) {
    if (e.target.closest && e.target.closest('[data-pl-close]')) { closePlayer(); return; }
    var btn = e.target.closest && e.target.closest('.alb-card .alb-btn');
    if (btn) { e.preventDefault(); open(btn); return; }
    if (e.target.closest && e.target.closest('[data-alb-close]')) { close(); return; }
    if (e.target === ov) { close(); return; }
    var pl = e.target.closest && e.target.closest('.alb-s .pl');
    if (pl) { e.preventDefault(); openPlayer(+pl.getAttribute('data-i')); return; }
  });

  /* the carousel's "Album" buttons open the very same overlay (an artist
     with no album data simply shows the empty state) */
  var csBtns = document.querySelectorAll('#stage .slide .detail .btn');
  for (var ci = 0; ci < csBtns.length; ci++) {
    csBtns[ci].addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var dc = this.closest('.dc');
      var h3 = dc ? dc.querySelector('h3') : null;
      openByName(h3 ? h3.textContent.trim() : '', this);
    });
  }

  window.addEventListener('pagehide', close);
})();
