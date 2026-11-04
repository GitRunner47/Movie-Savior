<?php
// partials/result_summary.php
// Usage: render_result_summary($shown_count, $matching_count);
?>
<p class="text-body-secondary" aria-live="polite">
    Showing <?php echo (int) ($shown_count ?? 0); ?> of
    <?php echo (int) $matching_count; ?> matching results.
</p>
