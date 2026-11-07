/* ==========================================================================
   SHARED UI (assets/js/site.js) - loaded on EVERY page (includes/scripts.php)
   * toast notifications          window.AFToast.show(text, 'ok' | 'err' | '')
   * back to top button           #af-top (appears after a little scroll)
   * broken image fallback        one capture listener swaps failed <img>
     for a neutral vinyl placeholder (keeps the layout, no console spam)
   Everything here is additive - it never touches page specific behaviour.
   ========================================================================== */
(function () {
  'use strict';

  /* ---------- toasts ---------------------------------------------------- */
  const TOAST_ICONS = {
    '':    '♪',
    'ok':  '✓',
    'err': '!'
  };
  function toast(text, kind) {
    kind = kind || '';
    let box = document.getElementById('af-toasts');
    if (!box) {
      box = document.createElement('div');
      box.id = 'af-toasts';
      box.className = 'af-toasts';
      box.setAttribute('role', 'status');      // announced by screen readers
      box.setAttribute('aria-live', 'polite');
      document.body.appendChild(box);
    }
    const t = document.createElement('div');
    t.className = 'af-toast' + (kind ? ' af-toast--' + kind : '');
    t.innerHTML = '<i aria-hidden="true">' + (TOAST_ICONS[kind] || '♪') + '</i><span></span>';
    t.querySelector('span').textContent = text;   // safe: no HTML from strings
    box.appendChild(t);
    /* enter -> hold -> leave */
    requestAnimationFrame(() => t.classList.add('in'));
    setTimeout(() => {
      t.classList.remove('in');
      setTimeout(() => t.remove(), 300);
    }, 3200);
    /* tap anywhere on it to dismiss early */
    t.addEventListener('click', () => t.remove());
  }
  window.AFToast = { show: toast };

  /* ---------- back to top ----------------------------------------------- */
  const topBtn = document.createElement('button');
  topBtn.type = 'button';
  topBtn.id = 'af-top';
  topBtn.className = 'af-top';
  topBtn.setAttribute('aria-label', 'Back to top');
  topBtn.title = 'Back to top';
  topBtn.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 15 6-6 6 6" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
  document.body.appendChild(topBtn);
  let ticking = false;
  addEventListener('scroll', () => {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(() => {
      topBtn.classList.toggle('show', scrollY > 480);
      ticking = false;
    });
  }, { passive: true });
  topBtn.addEventListener('click', () => {
    /* smooth when the user allows motion, instant jump otherwise */
    const behavior = matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';
    scrollTo({ top: 0, behavior: behavior });
    /* move focus back to the top for keyboard users */
    const brand = document.querySelector('.brand-logo');
    if (brand) brand.focus({ preventScroll: true });
  });

  /* ---------- broken <img> fallback ------------------------------------- */
  /* A tiny vinyl disc as a data URI - no extra request, works offline. */
  const PLACEHOLDER = 'data:image/svg+xml,' + encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120">' +
    '<rect width="120" height="120" fill="#16101f"/>' +
    '<circle cx="60" cy="60" r="40" fill="#0b0710" stroke="rgba(255,255,255,.16)"/>' +
    '<circle cx="60" cy="60" r="26" fill="none" stroke="rgba(255,255,255,.08)"/>' +
    '<circle cx="60" cy="60" r="10" fill="#c04be0"/>' +
    '<circle cx="60" cy="60" r="2.5" fill="#0b0710"/></svg>'
  );
  addEventListener('error', function (ev) {
    const img = ev.target;
    if (!img || img.tagName !== 'IMG' || img.dataset.afFallback) return;
    img.dataset.afFallback = '1';              // never loop on the placeholder itself
    img.src = PLACEHOLDER;
  }, true);                                     // capture: also catches lazy images
})();
