================================================================================
CLOUD AI WEBSITE - SETUP & DEPLOYMENT GUIDE
================================================================================

1. LOCAL TESTING / XAMPP SETUP:
   - Copy the "cloud-ai-website" folder into your XAMPP web root:
     C:\xampp\htdocs\cloud-ai-website\
   - Start Apache from the XAMPP Control Panel.
   - In your browser, open: http://localhost/cloud-ai-website/
   - Alternatively, test via CLI: run "php -S localhost:8000" inside the folder
     and open http://localhost:8000 in your browser.

2. CPANEL / HOSTING (public_html) SETUP:
   - Upload "cloud-ai-website.zip" to your cPanel File Manager into "public_html/".
   - Right-click the zip and select "Extract" (or extract contents into public_html).
   - Verify that index.php and the assets/ and images/ folders reside in your
     desired directory. Open your domain in any browser to verify.

================================================================================
ARCHITECTURE & ENHANCEMENTS SUMMARY:
================================================================================
- Exact Hero preservation: 100% fidelity on particle typography physics, WebGL
  volumetric smoke, glitch animation, 8s singer loop, and glassmorphism navbar.
- Lossless WebP extraction: All 10 embedded base64 images extracted directly to
  disk preserving exact alpha channels and zero quality loss.
- ONE continuous page: A single fixed texture layer (#bg-layer, z-index: -1)
  sits behind the whole page (singer gradient -> grayscale grunge texture in
  soft-light -> vignette -> film grain). Every section and wrapper is fully
  transparent - no borders, dividers, per-section backgrounds or shadows, so
  the page reads as one uninterrupted surface.
- Seamless hero blend: the hero's own background layers fade out over the last
  160px with an alpha mask (not a colour gradient), so the hero dissolves into
  the fixed texture with no line or colour jump at any scroll position.
- ONE merged showcase section (id="showcase"): the interactive console card
  stack sits on top, a small 48-64px gap, then the Featured Artists grid
  (id="artists", linked from the navbar "Work" item). Card geometry unchanged
  (3 / 2 / 1 columns, heads rising above the glass, images never cropped).
- Section headings are visually hidden (<h2 class="sr-only">) for accessibility
  only; no visible title text and no extra padding where titles used to be.
- Dynamic Theme Synchronization: singer palette (--accent, --accent-soft,
  --mid, --mid-light, --deep) glides (~1s, CSS @property with a JS lerp
  fallback) across the texture, chips, stars, console highlights and card glows.
- Compact per-singer hero info block: 5 inline-SVG stars (partially filled),
  genre chips, short tagline and an "Explore artists" CTA - data lives in the
  SINGERS array in assets/js/hero.js (ratings/taglines are SAMPLE content:
  edit before launch).
- Lighting: one gentle top glow in --accent-soft plus a single faint slow beam
  (static under prefers-reduced-motion). No cursor glow, no multiple beams.
- Motion: cards/console panels lift 4-6px with an accent border, subtle
  cursor-follow spotlight, photo scale 1.03, nav/button underline sweep; no 3D
  tilt, no magnetic effect; reveals are fade+rise, staggered, once only; only
  transform/opacity animate; offscreen work pauses via IntersectionObserver.
- Full Accessibility & SEO: Semantic HTML5 structure, visually hidden <h2>s,
  lazy image loading, focus-visible keyboard navigation, and prefers-reduced-motion.
