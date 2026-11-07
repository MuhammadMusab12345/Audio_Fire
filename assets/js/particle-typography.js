/**
 * Cursor-driven particle typography
 * Direct port of the supplied React component: Particle class,
 * canvas pixel-map, mouse repulsion, spring + friction physics,
 * ResizeObserver, touch support and full cleanup.
 */
class Particle {
  constructor(x, y, size, color, dispersion, returnSpd) {
    this.x = x + (Math.random() - 0.5) * 10;
    this.y = y + (Math.random() - 0.5) * 10;
    this.originX = x;
    this.originY = y;
    this.vx = (Math.random() - 0.5) * 5;
    this.vy = (Math.random() - 0.5) * 5;
    this.size = size;
    this.color = color;
    this.dispersion = dispersion;
    this.returnSpd = returnSpd;
  }

  update(mouseX, mouseY) {
    const dx = mouseX - this.x, dy = mouseY - this.y;
    const distance = Math.sqrt(dx * dx + dy * dy);
    const interactionRadius = 120;
    if (distance < interactionRadius && mouseX !== -1000 && mouseY !== -1000) {
      const fx = dx / distance, fy = dy / distance;
      const force = (interactionRadius - distance) / interactionRadius;
      this.vx -= fx * force * this.dispersion;
      this.vy -= fy * force * this.dispersion;
    }
    this.vx += (this.originX - this.x) * this.returnSpd;
    this.vy += (this.originY - this.y) * this.returnSpd;
    this.vx *= 0.85;
    this.vy *= 0.85;
    const d = Math.sqrt(Math.pow(this.x - this.originX, 2) + Math.pow(this.y - this.originY, 2));
    if (d < 1 && Math.random() > 0.95) {
      this.vx += (Math.random() - 0.5) * 0.2;
      this.vy += (Math.random() - 0.5) * 0.2;
    }
    this.x += this.vx;
    this.y += this.vy;
  }

  draw(ctx) {
    ctx.fillStyle = this.color;
    ctx.beginPath();
    ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
    ctx.fill();
  }
}

/**
 * Mounts the particle typography effect in `container`; returns a destroy() function.
 */
function mountParticleTypography(container, {
  text, fontSize = 120, fontFamily = "Inter, sans-serif", particleSize = 1.5,
  particleDensity = 6, dispersionStrength = 15, returnSpeed = 0.08, color
}) {
  const canvas = document.createElement("canvas");
  canvas.style.display = "block";
  canvas.style.width = "100%";
  canvas.style.height = "100%";
  container.style.touchAction = "none";
  container.appendChild(canvas);
  const ctx = canvas.getContext("2d", { willReadFrequently: true });

  let animationFrameId, particles = [], mouseX = -1000, mouseY = -1000, cw = 0, ch = 0, dead = false;
  let running = false, visible = true;

  const init = () => {
    cw = container.clientWidth;
    ch = container.clientHeight;
    if (!cw || !ch) return;
    const dpr = window.devicePixelRatio || 1;
    canvas.width = cw * dpr;
    canvas.height = ch * dpr;
    canvas.style.width = cw + "px";
    canvas.style.height = ch + "px";
    ctx.scale(dpr, dpr);
    const textColor = color || getComputedStyle(container).color || "#000000";
    ctx.clearRect(0, 0, cw, ch);
    ctx.fillStyle = textColor;
    const eff = Math.min(fontSize, cw * 0.15);
    ctx.font = `bold ${eff}px ${fontFamily}`;
    ctx.textAlign = "center";
    ctx.textBaseline = "middle";
    ctx.fillText(text, cw / 2, ch / 2);
    const img = ctx.getImageData(0, 0, canvas.width, canvas.height);
    particles = [];
    const step = Math.max(1, Math.floor(particleDensity * dpr));
    for (let y = 0; y < img.height; y += step) {
      for (let x = 0; x < img.width; x += step) {
        const a = img.data[(y * img.width + x) * 4 + 3] || 0;
        if (a > 128) {
          particles.push(new Particle(x / dpr, y / dpr, particleSize, textColor, dispersionStrength, returnSpeed));
        }
      }
    }
  };

  const animate = () => {
    animationFrameId = 0;
    if (dead || !running) return;
    ctx.clearRect(0, 0, cw, ch);
    for (const p of particles) {
      p.update(mouseX, mouseY);
      p.draw(ctx);
    }
    animationFrameId = requestAnimationFrame(animate);
  };

  /* Offscreen / hidden-tab pause: no physics work while nobody can see it */
  const startLoop = () => {
    if (dead || !running || !visible || document.hidden || animationFrameId) return;
    animationFrameId = requestAnimationFrame(animate);
  };
  const stopLoop = () => {
    if (animationFrameId) cancelAnimationFrame(animationFrameId);
    animationFrameId = 0;
  };

  const onMove = e => {
    const r = canvas.getBoundingClientRect();
    mouseX = e.clientX - r.left;
    mouseY = e.clientY - r.top;
  };
  const onLeave = () => {
    mouseX = -1000;
    mouseY = -1000;
  };
  const onTouch = e => {
    if (!e.touches[0]) return;
    const r = canvas.getBoundingClientRect();
    mouseX = e.touches[0].clientX - r.left;
    mouseY = e.touches[0].clientY - r.top;
  };

  const start = () => {
    if (dead) return;
    init();
    running = true;
    startLoop();
  };

  /* Pause the particle loop while the container is offscreen */
  const offscreenObserver = ('IntersectionObserver' in window)
    ? new IntersectionObserver(entries => {
        for (const entry of entries) {
          visible = entry.isIntersecting;
          if (visible) startLoop(); else stopLoop();
        }
      }, { threshold: 0 })
    : null;
  if (offscreenObserver) offscreenObserver.observe(container);

  const onVisibilityChange = () => {
    if (document.hidden) stopLoop(); else startLoop();
  };
  document.addEventListener("visibilitychange", onVisibilityChange);

  const timeoutId = setTimeout(() => {
    // wait for the web font so the pixel map uses Montserrat
    (document.fonts && document.fonts.load ? document.fonts.load(`bold 40px ${fontFamily}`) : Promise.resolve())
      .catch(() => {}).then(start);
  }, 100);

  const resizeObserver = new ResizeObserver(() => {
    if (!dead && particles.length !== undefined) init();
  });
  resizeObserver.observe(container);

  canvas.addEventListener("mousemove", onMove);
  canvas.addEventListener("mouseleave", onLeave);
  canvas.addEventListener("touchstart", onTouch);
  canvas.addEventListener("touchmove", onTouch);
  canvas.addEventListener("touchend", onLeave);

  return () => {
    dead = true;
    running = false;
    clearTimeout(timeoutId);
    resizeObserver.disconnect();
    if (offscreenObserver) offscreenObserver.disconnect();
    document.removeEventListener("visibilitychange", onVisibilityChange);
    canvas.removeEventListener("mousemove", onMove);
    canvas.removeEventListener("mouseleave", onLeave);
    canvas.removeEventListener("touchstart", onTouch);
    canvas.removeEventListener("touchmove", onTouch);
    canvas.removeEventListener("touchend", onLeave);
    stopLoop();
    canvas.remove();
  };
}

// Expose globally for both hero and artists modules
window.Particle = Particle;
window.mountParticleTypography = mountParticleTypography;
