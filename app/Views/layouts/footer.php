<?php if (\App\Core\Auth::check()): ?>
    </main>
    <footer class="footer-pro ui-footer modern-footer">
      <span>&copy; <?= date('Y') ?> COLVATEL S.A.S</span>
      <strong>ColvaContratos</strong>
    </footer>
  </section>
<?php else: ?>
  </main>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js?v=20260528_professional"></script>
<script src="assets/js/ui-modern.js?v=20260528_professional"></script>
</body>
</html>