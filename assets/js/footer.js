/* ==========================================================================
   AUDIO FIRE - SHARED FOOTER
   glass light follows the cursor + water-ripple WebGL canvas over the big name
   Respects prefers-reduced-motion (no waves are spawned while reduced)
   ========================================================================== */
(function () {
  const NAME = 'Audio Fire';
  const OPT = { distortionStrength: .075, waveCount: 100, waveSize: 60, waveFadeMultiplier: .95, waveGrowth: .155, waveSpawnThreshold: .1 };
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

  const footer = document.getElementById('footer');
  if (!footer) return;

  /* ---- glass light follows cursor ---- */
  footer.addEventListener('pointermove', e => {
    const r = footer.getBoundingClientRect();
    footer.style.setProperty('--mx', (e.clientX - r.left) + 'px');
    footer.style.setProperty('--my', (e.clientY - r.top) + 'px');
  });

  /* ---- water-ripple (vanilla WebGL) over the big name ---- */
  const cv = document.getElementById('ripple');
  const gl = cv.getContext('webgl', { premultipliedAlpha: true, alpha: true });
  if (!gl) return;
  const txt = document.createElement('canvas'), tctx = txt.getContext('2d');
  const map = document.createElement('canvas'), mctx = map.getContext('2d');
  let W = 0, H = 0, dpr = 1, MS = 3, waves = [], last = null, running = false, dirty = true;

  const vs = 'attribute vec2 p;varying vec2 v;void main(){v=p*.5+.5;gl_Position=vec4(p,0.,1.);}';
  const fs = `precision mediump float;varying vec2 v;uniform sampler2D t,m;uniform vec2 px;uniform float s;
  void main(){
    float hx=texture2D(m,v+vec2(px.x,0.)).r-texture2D(m,v-vec2(px.x,0.)).r;
    float hy=texture2D(m,v+vec2(0.,px.y)).r-texture2D(m,v-vec2(0.,px.y)).r;
    vec2 g=vec2(hx,hy);
    vec4 c=texture2D(t,v+g*s*2.2);
    float sp=clamp(length(g)*1.5,0.,1.)*.22;
    gl_FragColor=vec4(c.rgb+vec3(sp),clamp(c.a+sp,0.,1.));
  }`;
  const sh = (type, src) => { const o = gl.createShader(type); gl.shaderSource(o, src); gl.compileShader(o); return o; };
  const prog = gl.createProgram();
  gl.attachShader(prog, sh(gl.VERTEX_SHADER, vs));
  gl.attachShader(prog, sh(gl.FRAGMENT_SHADER, fs));
  gl.linkProgram(prog);
  gl.useProgram(prog);
  const buf = gl.createBuffer();
  gl.bindBuffer(gl.ARRAY_BUFFER, buf);
  gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 1, -1, -1, 1, 1, 1]), gl.STATIC_DRAW);
  const loc = gl.getAttribLocation(prog, 'p');
  gl.enableVertexAttribArray(loc);
  gl.vertexAttribPointer(loc, 2, gl.FLOAT, false, 0, 0);
  const mkTex = u => {
    const t = gl.createTexture();
    gl.activeTexture(gl.TEXTURE0 + u);
    gl.bindTexture(gl.TEXTURE_2D, t);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.LINEAR);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.LINEAR);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE);
    return t;
  };
  mkTex(0); mkTex(1);
  gl.uniform1i(gl.getUniformLocation(prog, 't'), 0);
  gl.uniform1i(gl.getUniformLocation(prog, 'm'), 1);
  gl.uniform1f(gl.getUniformLocation(prog, 's'), OPT.distortionStrength);
  const uPx = gl.getUniformLocation(prog, 'px');
  gl.pixelStorei(gl.UNPACK_FLIP_Y_WEBGL, true);
  gl.enable(gl.BLEND);
  gl.blendFunc(gl.ONE, gl.ONE_MINUS_SRC_ALPHA);

  function drawText() {
    tctx.clearRect(0, 0, txt.width, txt.height);
    const fam = 'Syne,"Arial Black",sans-serif';
    tctx.font = `800 100px ${fam}`;
    const w100 = tctx.measureText(NAME).width;
    const fsz = (txt.width - 48 * dpr) / w100 * 100;
    tctx.font = `800 ${fsz}px ${fam}`;
    tctx.textAlign = 'center';
    tctx.textBaseline = 'alphabetic';
    const base = txt.height + fsz * .1;
    const g = tctx.createLinearGradient(0, base - fsz * .78, 0, txt.height);
    g.addColorStop(0, 'rgba(255,255,255,.42)');
    g.addColorStop(1, 'rgba(255,255,255,.04)');
    tctx.fillStyle = g;
    tctx.fillText(NAME, txt.width / 2, base);
    const hpx = fsz * .78 * .86 / dpr;
    footer.style.setProperty('--wm-h', Math.round(hpx) + 'px');
    gl.activeTexture(gl.TEXTURE0);
    gl.pixelStorei(gl.UNPACK_PREMULTIPLY_ALPHA_WEBGL, true);
    gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA, gl.UNSIGNED_BYTE, txt);
  }
  function resize() {
    const r = footer.getBoundingClientRect();
    if (!r.width) return;
    dpr = Math.min(devicePixelRatio || 1, 1.5);
    W = r.width; H = r.height;
    cv.width = txt.width = Math.round(W * dpr);
    cv.height = txt.height = Math.round(H * dpr);
    map.width = Math.ceil(W / MS);
    map.height = Math.ceil(H / MS);
    gl.viewport(0, 0, cv.width, cv.height);
    gl.uniform2f(uPx, 1 / map.width, 1 / map.height);
    drawText();
    dirty = true;
    kick();
  }
  function drawMap() {
    mctx.globalCompositeOperation = 'source-over';
    mctx.fillStyle = '#000';
    mctx.fillRect(0, 0, map.width, map.height);
    mctx.globalCompositeOperation = 'lighter';
    for (const w of waves) {
      const rad = OPT.waveSize * (1 + w.age * OPT.waveGrowth) / MS, x = w.x / MS, y = w.y / MS;
      const g = mctx.createRadialGradient(x, y, 0, x, y, rad);
      g.addColorStop(0, 'rgba(255,255,255,0)');
      g.addColorStop(.55, `rgba(255,255,255,${w.a})`);
      g.addColorStop(1, 'rgba(255,255,255,0)');
      mctx.fillStyle = g;
      mctx.fillRect(x - rad, y - rad, rad * 2, rad * 2);
    }
    gl.activeTexture(gl.TEXTURE1);
    gl.pixelStorei(gl.UNPACK_PREMULTIPLY_ALPHA_WEBGL, false);
    gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA, gl.UNSIGNED_BYTE, map);
  }
  function frame() {
    for (const w of waves) { w.age++; w.a *= OPT.waveFadeMultiplier; }
    waves = waves.filter(w => w.a > .015);
    if (waves.length || dirty) {
      drawMap();
      gl.clearColor(0, 0, 0, 0);
      gl.clear(gl.COLOR_BUFFER_BIT);
      gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
      dirty = !!waves.length;
      requestAnimationFrame(frame);
    } else running = false;
  }
  function kick() { if (!running) { running = true; requestAnimationFrame(frame); } }

  footer.addEventListener('pointermove', e => {
    if (reduce) return;
    const r = footer.getBoundingClientRect(), x = e.clientX - r.left, y = e.clientY - r.top;
    if (last && Math.hypot(x - last.x, y - last.y) < OPT.waveSpawnThreshold * 140) return;
    last = { x, y };
    waves.push({ x, y, age: 0, a: .55 });
    if (waves.length > OPT.waveCount) waves.shift();
    kick();
  });
  footer.addEventListener('pointerleave', () => { last = null; });
  new ResizeObserver(resize).observe(footer);
  if (document.fonts && document.fonts.load) {
    document.fonts.load('800 100px Syne').finally(() => { if (W) { drawText(); dirty = true; kick(); } });
  }
  resize();
  window.__ripple = { kick };

  /* "Saved Songs" in the footer opens the same panel as the navbar icon */
  const savedLink = document.getElementById('footerSaved');
  if (savedLink) {
    savedLink.addEventListener('click', e => {
      e.preventDefault();
      const toggle = document.getElementById('savedToggle');
      if (toggle) toggle.click();
    });
  }
})();
