<?php
// partials/scripts.php
// Usage: render_scripts() loads this partial before the closing </body> tag.
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
    crossorigin="anonymous"></script>
<script src="<?php echo htmlspecialchars(project_url("helpers.js")); ?>"></script>