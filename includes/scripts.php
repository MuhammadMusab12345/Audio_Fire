<?php
/* ==========================================================================
   SHARED scripts - included at the very end of every page (before </body>)
   --------------------------------------------------------------------------
   main.js      shared site script (single audio element per page + helpers)
   site.js      shared UI: toasts, back-to-top, image fallback
   save.js      save system (AFS): heart buttons, badge, localStorage + DB sync
   navbar.js    glass navbar: active link, search, mobile sidebar, saved songs
   footer.js    footer glass light + water ripple canvas

   Set $pageScripts (array) BEFORE requiring this file to add page specific
   JS files, they are loaded last (assets/js/music.php ...).
   ========================================================================== */

if (!isset($pageScripts)) $pageScripts = array();
?>
  <!-- Production Scripts -->
  <script src="assets/js/main.js"></script>
  <script src="assets/js/site.js"></script>
  <script src="assets/js/save.js"></script>
  <script src="assets/js/navbar.js"></script>
  <script src="assets/js/footer.js"></script>
<?php foreach ($pageScripts as $jsFile): ?>
  <script src="<?= htmlspecialchars($jsFile) ?>"></script>
<?php endforeach; ?>
</body>
</html>
