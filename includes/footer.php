    </main><!-- /.page-wrapper -->
  </div><!-- /.main-content -->
</div><!-- /.app-layout -->

<!-- ─── Global JS ─────────────────────────────────────────────── -->
<script>
  // Expose APP_BASE to JS
  window.APP_BASE = document.querySelector('meta[name="app-base"]')?.content || '';
</script>
<script src="<?= APP_BASE ?>/assets/js/main.js"></script>

<?php if (isset($extraJs)): ?>
  <?php foreach ((array) $extraJs as $js): ?>
  <script src="<?= APP_BASE . e($js) ?>"></script>
  <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
