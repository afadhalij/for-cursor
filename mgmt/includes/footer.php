        </main>
    </div><!-- .app-main -->
</div><!-- .app-shell -->

<div id="toast-area"></div>

<!-- jQuery (DataTables dependency) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap 5 bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<!-- QR -->
<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>

<!-- App-wide helpers -->
<script src="<?= h(APP_BASE) ?>/assets/js/helpers.js?v=2"></script>

<?php if (!empty($EXTRA_JS) && is_array($EXTRA_JS)): ?>
    <?php foreach ($EXTRA_JS as $src): ?>
        <script src="<?= h(APP_BASE) ?>/assets/js/<?= h($src) ?>?v=2"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
