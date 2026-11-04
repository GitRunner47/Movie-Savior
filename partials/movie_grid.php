<?php
// partials/guide_grid.php
// Usage: render_grid($guides, $card_options, $empty_message);
$card_options = $card_options ?? [];
?>
<?php if (empty($movies)): ?>
    <p><?php echo htmlspecialchars($empty_message); ?></p>
<?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
        <?php foreach ($movies as $movie): ?>
            <div class="col">
                <?php render_movie_card($movie, $card_options); ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>