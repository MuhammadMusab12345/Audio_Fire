<?php
/* ==========================================================================
   CONTACT PAGE (contact.php)
   Converted from the original self contained "contact (1).html" - same
   design and behaviour, now on the shared head / navbar / footer.
   ========================================================================== */
$currentPage     = 'contact';
$pageTitle       = 'Contact | Audio Fire';
$metaDescription = 'Get in touch with Audio Fire - email, support, phone, WhatsApp, location and the contact form for songs, feedback and collaboration.';
$pageStyles      = array('assets/css/contact.css');
$pageScripts     = array('assets/js/contact.js');
require __DIR__ . '/includes/head.php';
?>
<?php require __DIR__ . '/includes/navbar.php'; ?>

<div class="ct-page">
<svg width="0" height="0" style="position:absolute"><filter id="wisp"><feTurbulence type="fractalNoise" baseFrequency=".008 .012" numOctaves="2" seed="4"><animate attributeName="baseFrequency" dur="40s" values=".008 .012;.012 .008;.008 .012" repeatCount="indefinite"/></feTurbulence><feDisplacementMap in="SourceGraphic" scale="140"/></filter></svg>
<div class="smoke"><b></b><b></b><b></b><b></b></div><div class="notes" id="notes"></div>

<main class="shell">
 <div class="brand">Audio Fire<span class="eq"><i></i><i></i><i></i><i></i></span></div>
 <div class="mark">CONTACT</div>

 <div class="cols">
  <section>
   <div class="badge"><i><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16v.01"/></svg></i>Contact</div>
   <h1><span>Get in</span><span class="o">touch</span></h1>
   <p class="sub">Questions about songs, feedback or collaboration? Reach us any way you like and we will reply soon.</p>
   <div class="list" id="list">
    <a class="item" href="mailto:hello@cloudai.com"><span class="ic"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="M3 8l9 6 9-6"/></svg></span><div><strong>Email us</strong><small>hello@cloudai.com</small></div><span class="go"><svg viewBox="0 0 24 24"><path d="M7 17L17 7M8 7h9v9"/></svg></span></a>
    <a class="item" href="mailto:support@cloudai.com"><span class="ic"><svg viewBox="0 0 24 24"><path d="M4 13v-1a8 8 0 0 1 16 0v1"/><rect x="3" y="13" width="4" height="6" rx="2"/><rect x="17" y="13" width="4" height="6" rx="2"/></svg></span><div><strong>Support</strong><small>support@cloudai.com</small></div><span class="go"><svg viewBox="0 0 24 24"><path d="M7 17L17 7M8 7h9v9"/></svg></span></a>
    <a class="item" href="tel:+923001234567"><span class="ic"><svg viewBox="0 0 24 24"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/></svg></span><div><strong>Call us</strong><small>+92 300 123 4567</small></div><span class="go"><svg viewBox="0 0 24 24"><path d="M7 17L17 7M8 7h9v9"/></svg></span></a>
    <a class="item" href="https://wa.me/923217654321" target="_blank" rel="noopener noreferrer"><span class="ic"><svg viewBox="0 0 24 24"><path d="M3 21l1.6-4.7A8.5 8.5 0 1 1 8 19.5L3 21z"/><path d="M9 9.5c.5 2 2.5 4 4.5 4.5l1.3-1.3-2-1-.8.8c-.8-.4-1.5-1.1-1.9-1.9l.8-.8-1-2z"/></svg></span><div><strong>WhatsApp</strong><small>+92 321 765 4321</small></div><span class="go"><svg viewBox="0 0 24 24"><path d="M7 17L17 7M8 7h9v9"/></svg></span></a>
    <a class="item" href="https://www.google.com/maps/search/?api=1&query=123+Music+Avenue" target="_blank" rel="noopener noreferrer"><span class="ic"><svg viewBox="0 0 24 24"><path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg></span><div><strong>Our location</strong><small>123 Music Avenue, Sound City</small></div><span class="go"><svg viewBox="0 0 24 24"><path d="M7 17L17 7M8 7h9v9"/></svg></span></a>
   </div>
  </section>

  <section class="formbox">
   <form id="f" novalidate>
   <!-- bot trap + form render time (checked again on the server - api/contact.php) -->
   <input type="text" id="af_company" name="af_company" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0">
   <input type="hidden" id="ts" value="<?= time() ?>">
    <input id="name" placeholder="Name" maxlength="40">
    <input id="email" type="email" placeholder="Email" maxlength="60">
    <textarea id="msgbox" placeholder="Message" maxlength="600"></textarea>
    <div class="msg" id="msg"></div>
    <button class="send" type="submit">Submit</button>
   </form>
  </section>
 </div>

 <section class="follow">
  <h3>Follow us</h3><p>Stay connected on your favourite platform</p>
  <div class="socials">
   <a class="soc" href="https://www.facebook.com/" target="_blank" rel="noopener noreferrer" style="--c:#1877f2;animation-delay:.1s" aria-label="Facebook"><svg viewBox="0 0 24 24"><path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8z"/></svg></a>
   <a class="soc" href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer" style="--c:#e1306c;animation-delay:.18s" aria-label="Instagram"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".6"/></svg></a>
   <a class="soc" href="https://www.tiktok.com/" target="_blank" rel="noopener noreferrer" style="--c:#14b8c4;animation-delay:.26s" aria-label="TikTok"><svg viewBox="0 0 24 24"><path d="M14 3v11.5a3.5 3.5 0 1 1-3.5-3.5"/><path d="M14 3c.3 2.6 2 4.4 5 4.6"/></svg></a>
   <a class="soc" href="https://mail.google.com/mail/?view=cm&to=hello@cloudai.com" target="_blank" rel="noopener noreferrer" style="--c:#ea4335;animation-delay:.34s" aria-label="Gmail"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg></a>
   <a class="soc" href="https://www.youtube.com/" target="_blank" rel="noopener noreferrer" style="--c:#ff0000;animation-delay:.42s" aria-label="YouTube"><svg viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="4"/><path d="M10 9l5 3-5 3z"/></svg></a>
   <a class="soc" href="https://wa.me/923217654321" target="_blank" rel="noopener noreferrer" style="--c:#25d366;animation-delay:.5s" aria-label="WhatsApp"><svg viewBox="0 0 24 24"><path d="M3 21l1.6-4.7A8.5 8.5 0 1 1 8 19.5L3 21z"/><path d="M9 9.5c.5 2 2.5 4 4.5 4.5l1.3-1.3-2-1-.8.8c-.8-.4-1.5-1.1-1.9-1.9l.8-.8-1-2z"/></svg></a>
   <a class="soc" href="https://x.com/" target="_blank" rel="noopener noreferrer" style="--c:#555;animation-delay:.58s" aria-label="X"><svg viewBox="0 0 24 24"><path d="M4 4l16 16M20 4L4 20"/></svg></a>
   <a class="soc" href="https://www.linkedin.com/" target="_blank" rel="noopener noreferrer" style="--c:#0a66c2;animation-delay:.66s" aria-label="LinkedIn"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M8 11v6M8 7.5v.01M12 17v-6M12 13a3 3 0 0 1 6 0v4"/></svg></a>
   <a class="soc" href="https://open.spotify.com/" target="_blank" rel="noopener noreferrer" style="--c:#1db954;animation-delay:.74s" aria-label="Spotify"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M7 9.5c3.5-1 7-.7 10 1M7.5 12.5c3-.8 6-.5 8.5 1M8 15.3c2.5-.6 5-.4 7 .7"/></svg></a>
  </div>
 </section>
</main>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
<?php require __DIR__ . '/includes/scripts.php'; ?>
