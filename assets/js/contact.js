/* ==========================================================================
   CONTACT PAGE (contact.php) - contact list reveal, form check,
   colour cycling, floating notes. Extracted from the original
   self contained "contact (1).html".
   ========================================================================== */
(function () {
  const $ = id => document.getElementById(id);
  const page = document.querySelector('.ct-page');
  if (!page) return;

  /* contact items slide in one after the other */
  page.querySelectorAll('.item').forEach((c, i) => setTimeout(() => c.classList.add('show'), 150 * i + 200));

  /* ---- form (client check + server side save - api/contact.php) ---- */
  const val = id => { const el = $(id); return el ? el.value.trim() : ''; };
  const form = $('f');
  if (form) {
    form.onsubmit = async e => {
      e.preventDefault();
      const m = $('msg');
      m.className = 'msg';
      const n = val('name'), em = val('email'), t = val('msgbox');
      if (!n || !/^\S+@\S+\.\S+$/.test(em) || !t) {
        m.textContent = 'Please enter your name, a valid email and a message.';
        return;
      }
      const btn = form.querySelector('button[type="submit"], .btn');
      if (btn) { btn.classList.add('af-busy'); btn.disabled = true; }
      try {
        const r = await fetch('api/contact.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': (document.querySelector('meta[name=af-csrf]') || { content: '' }).content
          },
          credentials: 'same-origin',
          body: JSON.stringify({
            name: n, email: em, subject: '', message: t,
            ts: val('ts'), af_company: val('af_company')
          })
        });
        const d = await r.json().catch(() => ({}));
        if (btn) { btn.classList.remove('af-busy'); btn.disabled = false; }
        if (d.ok) {
          form.reset();
          const ts = $('ts'); if (ts) ts.value = Math.floor(Date.now() / 1000);
          m.textContent = d.message || 'Thank you! Your message has been sent.';
          m.classList.add('ok');
        } else {
          m.textContent = d.error || 'Could not send the message - please try again.';
        }
      } catch (err) {
        if (btn) { btn.classList.remove('af-busy'); btn.disabled = false; }
        m.textContent = 'No connection - please try again in a moment.';
      }
    };
  }

  /* ---- slow palette cycling (navbar / badge / accents follow it) ---- */
  const pal = [
    ['#8b5cf6', '#ec4899', '#06b6d4'],
    ['#f43f5e', '#f59e0b', '#8b5cf6'],
    ['#10b981', '#06b6d4', '#6366f1'],
    ['#3b82f6', '#ec4899', '#f59e0b']
  ];
  let pi = 0;
  setInterval(() => {
    pi = (pi + 1) % pal.length;
    const r = document.documentElement.style;
    r.setProperty('--s1', pal[pi][0]);
    r.setProperty('--s2', pal[pi][1]);
    r.setProperty('--s3', pal[pi][2]);
  }, 6000);

  /* ---- floating music notes ---- */
  const notes = $('notes');
  if (notes) {
    for (let i = 0; i < 14; i++) {
      const s = document.createElement('span');
      s.textContent = ['♪', '♫', '♩'][i % 3];
      s.style.left = Math.random() * 100 + '%';
      s.style.fontSize = 14 + Math.random() * 20 + 'px';
      s.style.animationDuration = 12 + Math.random() * 14 + 's';
      s.style.animationDelay = -Math.random() * 20 + 's';
      notes.appendChild(s);
    }
  }
})();
