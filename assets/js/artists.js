/**
 * Showcase (Console + Featured Artists) Controller - Cloud AI Website
 * Scroll reveals (fade + rise, staggered, once only), subtle cursor-follow
 * spotlight inside the glass cards, the interactive fanned card stack and
 * the back-to-top control. Only transform/opacity are animated.
 */

(function () {
  'use strict';

  const isReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const isTouch = !window.matchMedia('(hover: hover) and (pointer: fine)').matches;

  // =========================================================================
  // 1. SCROLL REVEAL (Headings & Artist Cards) - fade + slight rise, once
  // =========================================================================
  function initScrollReveals() {
    const headings = [...document.querySelectorAll('[data-reveal]')];
    const cards = [...document.querySelectorAll('.artists .card')];
    const targets = [...headings, ...cards];
    if (!targets.length) return;

    if (isReduced || !('IntersectionObserver' in window)) {
      targets.forEach(el => el.classList.add('is-revealed'));
      return;
    }

    const observer = new IntersectionObserver((entries, obs) => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        const el = entry.target;
        el.classList.add('is-revealed');
        obs.unobserve(el);
        // Drop the stagger delay once revealed so hover stays instant
        if (el.style.transitionDelay) {
          setTimeout(() => el.style.removeProperty('transition-delay'), 1000);
        }
      });
    }, {
      rootMargin: '0px 0px -60px 0px',
      threshold: 0.12
    });

    // Stagger artist cards left-to-right within a row
    cards.forEach((el, i) => {
      el.style.transitionDelay = `${(i % 3) * 0.12}s`;
    });

    targets.forEach(el => observer.observe(el));

    // Safety net: anything already inside the viewport reveals immediately
    // (covers direct #anchor jumps and engines that delay the first IO pass)
    requestAnimationFrame(() => {
      targets.forEach(el => {
        const r = el.getBoundingClientRect();
        if (r.top < window.innerHeight && r.bottom > 0) {
          el.classList.add('is-revealed');
        }
      });
    });
  }

  // =========================================================================
  // 2. CURSOR-FOLLOW SPOTLIGHT INSIDE CARDS (subtle, no tilt, no magnetic)
  // =========================================================================
  function initCardSpotlights() {
    if (isTouch || isReduced) return;

    const cards = document.querySelectorAll('.artists .card, .ocs-card');

    cards.forEach(card => {
      let rect = null;
      let rafId = null;

      card.addEventListener('mouseenter', () => {
        rect = card.getBoundingClientRect();
      });

      card.addEventListener('mousemove', e => {
        if (!rect) rect = card.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        if (rafId) cancelAnimationFrame(rafId);
        rafId = requestAnimationFrame(() => {
          card.style.setProperty('--mouse-x', `${x}px`);
          card.style.setProperty('--mouse-y', `${y}px`);
        });
      }, { passive: true });

      card.addEventListener('mouseleave', () => {
        if (rafId) cancelAnimationFrame(rafId);
        rect = null;
      });
    });
  }

  // =========================================================================
  // 3. CONSOLE SECTION: INTERACTIVE FANNED CARD STACK
  // =========================================================================
  function initConsoleStack() {
    const stage = document.getElementById('stage');
    if (!stage) return;

    const cards = [...stage.querySelectorAll('.ocs-card')];
    const count = cards.length;
    if (!count) return;

    const mid = (count - 1) / 2;
    const home = Math.round(mid);
    let open = false;
    let active = home;

    cards.forEach((el, i) => {
      const d = i - mid;
      el.style.setProperty('--d', d);
      el.style.setProperty('--abs', Math.abs(d));
    });

    function renderStack() {
      stage.dataset.open = open;
      cards.forEach((el, i) => {
        const isActive = open && i === active;
        el.dataset.active = isActive;
        el.style.zIndex = isActive ? count + 2 : count - Math.round(Math.abs(i - mid));
        const arrow = el.querySelector('a.ocs-arrow');
        if (arrow) arrow.tabIndex = open ? 0 : -1;
      });
    }

    const setOpen = v => {
      open = v;
      if (!v) active = home;
      renderStack();
    };

    // Desktop hover: pointerenter fans the stack out.
    // Sticky-open: once opened it stays open for this page view (the old
    // pointerleave collapse was removed); a refresh resets it to hover-open.
    stage.addEventListener('pointerenter', e => {
      if (e.pointerType === 'mouse') setOpen(true);
    });

    cards.forEach((el, i) => {
      el.addEventListener('pointerenter', e => {
        if (e.pointerType === 'mouse' && open) {
          active = i;
          renderStack();
        }
      });
      el.addEventListener('focus', () => {
        open = true;
        active = i;
        renderStack();
      });
      el.addEventListener('click', () => {
        if (open) {
          active = i;
          renderStack();
        } else {
          setOpen(true);
        }
      });
    });

    // Keyboard: Escape still collapses (a11y); mouse users get sticky-open.
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && open) setOpen(false);
    });

    // Touch tap outside collapses (mouse outside-clicks keep it open)
    document.addEventListener('pointerdown', e => {
      if (open && e.pointerType !== 'mouse' && !stage.contains(e.target)) setOpen(false);
    });

    renderStack();
  }

  // =========================================================================
  // 4. SMOOTH BACK-TO-TOP
  // =========================================================================
  function initBackToTop() {
    const btn = document.getElementById('backToTop');
    if (!btn) return;
    btn.addEventListener('click', e => {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: isReduced ? 'auto' : 'smooth' });
    });
  }

  // =========================================================================
  // BOOTSTRAP WHEN DOM IS READY
  // =========================================================================
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  function init() {
    initScrollReveals();
    initCardSpotlights();
    initConsoleStack();
    initBackToTop();
  }
})();
