/**
 * Hero Controller & WebGL Smoke - Cloud AI Website
 * Preserves exact hero rotation, glitch transitions, particle typography,
 * and syncs dynamic themes with the rest of the application.
 */

// Singer image file paths (extracted lossless WebP with alpha)
const HERO_IMAGES = {
  "rihanna": "images/hero/rihanna.webp",
  "kendrick-lamar": "images/hero/kendrick-lamar.webp",
  "g-eazy": "images/hero/g-eazy.webp",
  "the-weeknd": "images/hero/the-weeknd.webp"
};

const IMG_URL = {};
for (const k in HERO_IMAGES) {
  IMG_URL[k] = HERO_IMAGES[k];
  const pre = new Image();
  pre.src = IMG_URL[k];
  if (pre.decode) pre.decode().catch(() => {});
}

// Singers dataset with coordinated themes
const SINGERS = [
  {
    name: ["RIHANNA"],
    img: "rihanna",
    smoke: [[0.78, 0.28, 0.95], [1.0, 0.68, 1.0]],
    bg: "radial-gradient(circle at 75% 30%, #c04be0 0%, #7a2fb8 35%, #2a1048 75%, #12081f 100%)",
    tracks: [
      ["WORK", "Anti", "2016"],
      ["DIAMONDS", "Unapologetic", "2012"],
      ["PON DE REPLAY", "Music of the Sun", "2005"]
    ],
    theme: {
      accent: "#c04be0",
      soft: "#ffadff",
      mid: "#2a1048",
      midLight: "#632682",
      deep: "#12081f"
    },
    /* SAMPLE content - edit before launch (rating + tagline are placeholders) */
    meta: {
      rating: 4.9,
      genres: ["Pop", "R&B", "Dance"],
      tagline: "Magnetic stage presence and pop energy.",
      bio: "Barbados-born icon who turned chart dominance into a global fashion empire.",
      listeners: "82.4M",
      topSong: "Work",
      albums: "9"
    }
  },
  {
    name: ["KENDRICK", "LAMAR"],
    img: "kendrick-lamar",
    smoke: [[0.93, 0.40, 0.12], [1.0, 0.74, 0.42]],
    bg: "radial-gradient(circle at 72% 35%, #e8662a 0%, #a8381c 35%, #3a1410 75%, #140808 100%)",
    tracks: [
      ["HUMBLE", "DAMN", "2017"],
      ["DNA", "DAMN", "2017"],
      ["SWIMMING POOLS", "Good Kid, M.A.A.D City", "2012"]
    ],
    theme: {
      accent: "#e8662a",
      soft: "#ffbd6b",
      mid: "#3a1410",
      midLight: "#7c331a",
      deep: "#140808"
    },
    /* SAMPLE content - edit before launch (rating + tagline are placeholders) */
    meta: {
      rating: 4.8,
      genres: ["Hip-Hop", "Rap", "West Coast"],
      tagline: "Precision storytelling over heavy production.",
      bio: "Compton lyricist and Pulitzer winner whose concepts reshape modern rap.",
      listeners: "58.7M",
      topSong: "HUMBLE.",
      albums: "5"
    }
  },
  {
    name: ["G-EAZY"],
    img: "g-eazy",
    smoke: [[0.20, 0.36, 0.95], [0.55, 0.74, 1.0]],
    bg: "radial-gradient(circle at 72% 35%, #25307a 0%, #151c4f 40%, #0a0f2e 75%, #05071a 100%)",
    tracks: [
      ["NO LIMIT", "The Beautiful & Damned", "2017"],
      ["GOOD LIFE", "Fate of the Furious: The Album", "2017"],
      ["ME, MYSELF & I", "When It's Dark Out", "2015"]
    ],
    theme: {
      accent: "#335cf2",
      soft: "#8cbdff",
      mid: "#0a0f2e",
      midLight: "#1a2c79",
      deep: "#05071a"
    },
    /* SAMPLE content - edit before launch (rating + tagline are placeholders) */
    meta: {
      rating: 4.7,
      genres: ["Hip-Hop", "Rap", "Bay Area"],
      tagline: "Smooth west-coast flow, vintage swagger.",
      bio: "Oakland crooner in tailored suits, blending slick bars with retro cool.",
      listeners: "31.2M",
      topSong: "No Limit",
      albums: "4"
    }
  },
  {
    name: ["THE", "WEEKND"],
    img: "the-weeknd",
    smoke: [[0.92, 0.36, 0.09], [1.0, 0.72, 0.32]],
    bg: "radial-gradient(circle at 70% 40%, #d9541b 0%, #8e2a12 38%, #2e100a 75%, #120806 100%)",
    tracks: [
      ["STARBOY", "Starboy", "2016"],
      ["I FEEL IT COMING", "Starboy", "2016"],
      ["THE HILLS", "Beauty Behind the Madness", "2015"]
    ],
    theme: {
      accent: "#d9541b",
      soft: "#ffb852",
      mid: "#2e100a",
      midLight: "#6f2a11",
      deep: "#120806"
    },
    /* SAMPLE content - edit before launch (rating + tagline are placeholders) */
    meta: {
      rating: 4.9,
      genres: ["R&B", "Synth-pop", "Pop"],
      tagline: "Cinematic late-night R&B atmosphere.",
      bio: "Toronto's dark-voiced hitmaker behind Blinding Lights and Starboy.",
      listeners: "75.6M",
      topSong: "Blinding Lights",
      albums: "6"
    }
  }
];

const DISPLAY_MS = 8000, OUT_MS = 300, IN_MS = 400;

const hero = document.getElementById("hero");
const $ = id => document.getElementById(id);

/* ---------------- Theme Synchronization & JS Lerp Fallback ---------------- */
const supportsAtProperty = window.CSS && CSS.registerProperty && (() => {
  try {
    CSS.registerProperty({ name: "--theme-check-prop", syntax: "<color>", inherits: false, initialValue: "#000" });
    return true;
  } catch (e) {
    return false;
  }
})();

function hexToRgb(hex) {
  let c = hex.replace("#", "");
  if (c.length === 3) c = c.split("").map(x => x + x).join("");
  const num = parseInt(c, 16);
  return [(num >> 16) & 255, (num >> 8) & 255, num & 255];
}

function rgbToHex([r, g, b]) {
  return "#" + [r, g, b].map(x => Math.round(x).toString(16).padStart(2, "0")).join("");
}

function lerpColor(c1, c2, t) {
  return [
    c1[0] + (c2[0] - c1[0]) * t,
    c1[1] + (c2[1] - c1[1]) * t,
    c1[2] + (c2[2] - c1[2]) * t
  ];
}

let activeTheme = null;
let themeLerpRaf = 0;

/* Applies the full singer palette (incl. the texture's lighter centre tone) */
function applyThemeVars(t) {
  const root = document.documentElement.style;
  root.setProperty("--accent", t.accent);
  root.setProperty("--accent-soft", t.soft);
  root.setProperty("--mid", t.mid);
  root.setProperty("--mid-light", t.midLight || t.mid);
  root.setProperty("--deep", t.deep);
}

function syncTheme(theme) {
  if (!activeTheme) {
    activeTheme = { ...theme };
    applyThemeVars(theme);
    window.dispatchEvent(new CustomEvent("themechange", { detail: theme }));
    return;
  }

  // If @property is supported, CSS transition: 1s ease handles interpolation automatically
  if (supportsAtProperty) {
    applyThemeVars(theme);
    window.dispatchEvent(new CustomEvent("themechange", { detail: theme }));
    activeTheme = { ...theme };
    return;
  }

  // JS lerp fallback for browsers without CSS @property
  cancelAnimationFrame(themeLerpRaf);
  const startTheme = { ...activeTheme };
  const targetTheme = { ...theme };
  const keys = ["accent", "soft", "mid", "midLight", "deep"];
  const startRGB = {}, targetRGB = {};
  keys.forEach(k => {
    startRGB[k] = hexToRgb(startTheme[k] || "#000000");
    targetRGB[k] = hexToRgb(targetTheme[k] || targetTheme.mid || "#000000");
  });
  const startTime = performance.now();
  const duration = 1000;

  function step(now) {
    const elapsed = now - startTime;
    const p = Math.min(1, elapsed / duration);
    const ease = p < 0.5 ? 2 * p * p : 1 - Math.pow(-2 * p + 2, 2) / 2;

    applyThemeVars({
      accent: rgbToHex(lerpColor(startRGB.accent, targetRGB.accent, ease)),
      soft: rgbToHex(lerpColor(startRGB.soft, targetRGB.soft, ease)),
      mid: rgbToHex(lerpColor(startRGB.mid, targetRGB.mid, ease)),
      midLight: rgbToHex(lerpColor(startRGB.midLight, targetRGB.midLight, ease)),
      deep: rgbToHex(lerpColor(startRGB.deep, targetRGB.deep, ease))
    });

    if (p < 1) {
      themeLerpRaf = requestAnimationFrame(step);
    } else {
      activeTheme = { ...targetTheme };
    }
  }

  themeLerpRaf = requestAnimationFrame(step);
  window.dispatchEvent(new CustomEvent("themechange", { detail: theme }));
}

/* ---------------- Cinematic smoke (WebGL, behind the portrait) ----------------
   Domain-warped fractal noise = soft volumetric smoke. Colour is lerped
   between singer themes so it flows into the new colour at each swap.
   Copied from the reference hero file: the canvas is an ABSOLUTE layer inside
   .hero (overflow:hidden keeps it out of every other section), sized to the
   hero box and masked to an ellipse around the portrait. */
const Smoke = (() => {
  const canvas = document.getElementById("smoke");
  if (!canvas) return { setTheme() {}, destroy() {}, setVisible() {} };
  const gl = canvas.getContext("webgl", { alpha: true, premultipliedAlpha: true, antialias: false });
  if (!gl) {
    canvas.style.display = "none";
    return { setTheme() {}, destroy() {}, setVisible() {} };
  }

  const VS = "attribute vec2 p;void main(){gl_Position=vec4(p,0.,1.);}";
  const FS = `
  #ifdef GL_FRAGMENT_PRECISION_HIGH
  precision highp float;
  #else
  precision mediump float;
  #endif
  uniform vec2 uRes; uniform float uTime; uniform vec3 uC1; uniform vec3 uC2;
  uniform vec2 uCenter; uniform vec2 uRad;
  float hash(vec2 p){ p=fract(p*vec2(123.34,456.21)); p+=dot(p,p+45.32); return fract(p.x*p.y); }
  float noise(vec2 p){ vec2 i=floor(p),f=fract(p); f=f*f*(3.-2.*f);
    return mix(mix(hash(i),hash(i+vec2(1.,0.)),f.x),mix(hash(i+vec2(0.,1.)),hash(i+vec2(1.,1.)),f.x),f.y); }
  float fbm(vec2 p){ float v=0.,a=.5; mat2 m=mat2(1.6,1.2,-1.2,1.6);
    for(int i=0;i<5;i++){ v+=a*noise(p); p=m*p; a*=.5; } return v; }
  void main(){
    vec2 uv=gl_FragCoord.xy/uRes; float asp=uRes.x/uRes.y;
    vec2 p=vec2(uv.x*asp,uv.y); float t=uTime;
    vec2 q=vec2(fbm(p*1.3+vec2(0.,-t*.04)), fbm(p*1.3+vec2(5.2,1.3)+vec2(t*.02,-t*.03)));
    vec2 r=vec2(fbm(p*1.5+3.*q+vec2(1.7,9.2)+.10*t), fbm(p*1.5+3.*q+vec2(8.3,2.8)-.08*t));
    float f=fbm(p*1.4+3.2*r);                       /* main smoke body   */
    float f2=fbm(p*.7+q*2.-vec2(0.,t*.02));         /* slower, larger depth layer */
    float dens=mix(smoothstep(.2,.85,f),f2,.4);
    vec2 d=(uv-uCenter)/uRad;                       /* elliptical mask around the portrait */
    float m=1.-smoothstep(.2,1.,length(d));
    m*=1.-smoothstep(.76,.94,uv.y);                 /* fade out toward the navbar */
    float a=clamp(dens*m*1.75,0.,1.);
    float lit=smoothstep(.45,.88,f)*.7+pow(f2,2.)*.3;   /* atmospheric highlights */
    vec3 col=mix(uC1,uC2,lit)*(.7+.7*dens);
    gl_FragColor=vec4(col*a,a);
  }`;

  const sh = (type, src) => {
    const o = gl.createShader(type);
    gl.shaderSource(o, src);
    gl.compileShader(o);
    return o;
  };
  const prog = gl.createProgram();
  gl.attachShader(prog, sh(gl.VERTEX_SHADER, VS));
  gl.attachShader(prog, sh(gl.FRAGMENT_SHADER, FS));
  gl.linkProgram(prog);
  if (!gl.getProgramParameter(prog, gl.LINK_STATUS)) {
    console.warn("Smoke shader failed:", gl.getProgramInfoLog(prog));
    canvas.style.display = "none";
    return { setTheme() {}, destroy() {}, setVisible() {} };
  }
  gl.useProgram(prog);

  const buf = gl.createBuffer();
  gl.bindBuffer(gl.ARRAY_BUFFER, buf);
  gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 1, -1, -1, 1, 1, 1]), gl.STATIC_DRAW);
  const loc = gl.getAttribLocation(prog, "p");
  gl.enableVertexAttribArray(loc);
  gl.vertexAttribPointer(loc, 2, gl.FLOAT, false, 0, 0);

  const U = n => gl.getUniformLocation(prog, n);
  const uRes = U("uRes"), uTime = U("uTime"), uC1 = U("uC1"), uC2 = U("uC2"), uCenter = U("uCenter"), uRad = U("uRad");

  const SCALE = 0.45; // render at reduced resolution: smoke is soft, GPU stays cool
  /* prefers-reduced-motion: the smoke keeps moving, only slower (never frozen) */
  const reducedMQ = window.matchMedia ? window.matchMedia("(prefers-reduced-motion: reduce)") : null;
  const driftScale = () => (reducedMQ && reducedMQ.matches ? 0.4 : 1);
  let cur = null, target = null, time = 0, last = 0, raf = 0, lastDraw = 0, isVisible = true;

  /* Sized to the HERO box (not the viewport) and masked to the portrait
     ellipse, exactly like the reference file. */
  function layout() {
    if (!hero) return;
    const hr = hero.getBoundingClientRect(), pr = $("portrait").getBoundingClientRect();
    canvas.width = Math.max(2, Math.round(hr.width * SCALE));
    canvas.height = Math.max(2, Math.round(hr.height * SCALE));
    gl.viewport(0, 0, canvas.width, canvas.height);
    gl.uniform2f(uRes, canvas.width, canvas.height);
    gl.uniform2f(uCenter, (pr.left + pr.width * 0.5 - hr.left) / hr.width, 1 - (pr.top + pr.height * 0.45 - hr.top) / hr.height);
    gl.uniform2f(uRad, pr.width * 0.72 / hr.width, pr.height * 0.7 / hr.height);
  }

  function frame(now) {
    raf = requestAnimationFrame(frame);
    if (document.hidden || !isVisible || now - lastDraw < 33) return; // ~30 fps limit
    const dt = Math.min(0.1, (now - (last || now)) / 1000);
    last = now;
    lastDraw = now;
    time += dt * driftScale(); // the smoke always drifts; uTime is what makes it move
    const k = 1 - Math.exp(-dt / 0.4); // smooth colour glide (~1 s)
    for (let i = 0; i < 2; i++) {
      for (let j = 0; j < 3; j++) {
        cur[i][j] += (target[i][j] - cur[i][j]) * k;
      }
    }
    gl.uniform1f(uTime, time);
    gl.uniform3fv(uC1, cur[0]);
    gl.uniform3fv(uC2, cur[1]);
    gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
  }

  const ro = new ResizeObserver(layout);
  if (hero) ro.observe(hero);
  layout();

  return {
    setTheme(c) {
      target = c.map(a => a.slice());
      if (!cur) {
        cur = c.map(a => a.slice());
        raf = requestAnimationFrame(frame);
      }
    },
    setVisible(v) {
      isVisible = v;
      if (isVisible && !raf) {
        raf = requestAnimationFrame(frame);
      }
    },
    destroy() {
      cancelAnimationFrame(raf);
      ro.disconnect();
    }
  };
})();

/* ---------------- Hero Controller ---------------- */
const mctx = document.createElement("canvas").getContext("2d");

/* Singer name uses the site heading face (Playfair Display 900) */
const NAME_FONT = "HeadingSerif, 'Playfair Display', Georgia, serif";
const textWidth = (line, fs) => {
  mctx.font = `900 ${fs}px ${NAME_FONT}`;
  return mctx.measureText(line).width;
};

let index = 0, destroyers = [];

/* Large names: ~88px on wide desktops, never below 36px. Also capped by the
   viewport HEIGHT so a two-line name (KENDRICK LAMAR) + the glass card always
   fit below the fixed navbar instead of sliding under it. */
const nameSize = () => Math.round(Math.max(36, Math.min(88, window.innerWidth * 0.062, window.innerHeight * 0.105)));

/* ---------------- Compact per-singer info block (rating / chips / tagline) ---- */
const STAR_PATH = "M12 2.3l2.94.9 6.5.95-4.7 4.6 1.11 6.45L12 17.12 6.15 20.2l1.11-6.45-4.7-4.6 6.5-.95L12 2.3z";

function escText(text) {
  return String(text).replace(/[&<>"']/g, ch => (
    { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[ch] || ch
  ));
}

/* 5 inline SVG stars, each partially filled from the value (e.g. 4.9) */
function starsHTML(value) {
  let html = "";
  for (let i = 0; i < 5; i++) {
    const p = Math.min(1, Math.max(0, value - i));
    html += `<span class="hm-star">`
      + `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="${STAR_PATH}" fill="rgba(255,255,255,.2)"/></svg>`
      + `<span class="hm-star-fill" style="--p:${p}">`
      + `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="${STAR_PATH}" style="fill:var(--accent)"/></svg>`
      + `</span></span>`;
  }
  return html;
}

function renderHeroMeta(meta) {
  const data = meta || {};
  const rating = Number(data.rating) || 0;
  $("hmStars").innerHTML = starsHTML(rating);
  $("hmScore").textContent = rating ? rating.toFixed(1) : "";
  $("hmRating").setAttribute("aria-label", `Rated ${rating ? rating.toFixed(1) : "0"} out of 5`);
  $("hmChips").innerHTML = (Array.isArray(data.genres) ? data.genres : [])
    .map(g => `<li>${escText(g)}</li>`).join("");
  $("hmTagline").textContent = data.tagline || "";
  $("hmBio").textContent = data.bio || "";
  $("hmListeners").textContent = data.listeners || "";
  $("hmTopSong").textContent = data.topSong || "";
  $("hmAlbums").textContent = data.albums || "";
}

function render() {
  const s = SINGERS[index], fs = nameSize();
  $("bg").style.background = s.bg;
  Smoke.setTheme(s.smoke);
  $("h1").textContent = s.name.join(" ");

  // Sync theme colors with root and dispatch themechange event
  if (s.theme) {
    syncTheme(s.theme);
  }

  // Portrait
  const portrait = $("portrait");
  portrait.classList.remove("is-missing");
  for (const id of ["pImg", "pR", "pC"]) {
    $(id).src = IMG_URL[s.img];
  }
  $("pImg").alt = s.name.join(" ");
  $("missingTxt").innerHTML = "IMAGE FAILED TO LOAD<br>" + s.img;

  // Name (particle text, one canvas per line)
  destroyers.forEach(d => d());
  destroyers = [];
  const names = $("names");
  names.innerHTML = "";
  for (const line of s.name) {
    const textW = textWidth(line, fs);
    const w = Math.max(textW + 40, fs / 0.15); // component caps font at 15% of width
    const box = document.createElement("div");
    box.className = "name-line";
    box.style.cssText = `width:${w}px;height:${Math.round(fs * 1.18)}px;margin-left:${-(w - textW) / 2}px;color:#fff`;
    names.appendChild(box);
    destroyers.push(mountParticleTypography(box, {
      text: line,
      fontSize: fs,
      fontFamily: NAME_FONT,
      particleSize: 1.5,
      particleDensity: 3,
      dispersionStrength: 15,
      returnSpeed: 0.08,
      color: "#ffffff"
    }));
  }

  // Tracks
  $("tracks").innerHTML = s.tracks.map(t =>
    `<li><b>${t[0]}</b><span class="a">${t[1]}</span><br><span class="y">${t[2]}</span></li>`).join("");

  // Compact info block (rating, genre chips, tagline) - sample data in SINGERS
  renderHeroMeta(s.meta);
}

$("pImg").addEventListener("error", () => $("portrait").classList.add("is-missing"));

/* 8-second loop: glitch-out (last 300 ms) -> swap -> glitch-in (400 ms).
   Runs unconditionally: the swap glitch is a core part of the hero, so it
   must not be gated behind prefers-reduced-motion (that gate is what froze
   the smoke and silenced the glitch entirely). */
const timers = [];
const later = (fn, ms) => timers.push(setTimeout(fn, ms));

function cycle() {
  later(() => hero.dataset.phase = "out", DISPLAY_MS - OUT_MS);
  later(() => {
    index = (index + 1) % SINGERS.length;
    render();
    hero.dataset.phase = "in";
    cycle();
  }, DISPLAY_MS);
  later(() => hero.dataset.phase = "idle", DISPLAY_MS + IN_MS);
}

// Pause the smoke loop when the hero is out of viewport (reference file)
if ("IntersectionObserver" in window && hero) {
  const heroObserver = new IntersectionObserver((entries) => {
    for (const entry of entries) {
      Smoke.setVisible(entry.isIntersecting);
    }
  }, { threshold: 0.05 });
  heroObserver.observe(hero);
}

let lastFs = nameSize(), rzT;
window.addEventListener("resize", () => {
  clearTimeout(rzT);
  rzT = setTimeout(() => {
    const f = nameSize();
    if (Math.abs(f - lastFs) >= 2) {
      lastFs = f;
      render();
    }
  }, 150);
});

window.addEventListener("pagehide", () => {
  timers.forEach(clearTimeout);
  destroyers.forEach(d => d());
  Smoke.destroy();
});

// Initial boot
render();
cycle();

// Re-measure names once the heading face is available so alignment is exact
if (document.fonts && document.fonts.load) {
  Promise.all([
    document.fonts.load(`900 40px HeadingSerif`).catch(() => {}),
    document.fonts.load(`bold 40px Montserrat`).catch(() => {})
  ]).then(() => render()).catch(() => {});
}
