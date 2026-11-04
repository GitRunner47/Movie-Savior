<?php
require_once(__DIR__ . "/../../lib/app.php");

if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <?php render_head("Dashboard"); ?>
</head>
<body>
    <?php render_nav(); ?>
    <main class="container py-4">
    <h1>Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars(get_user_username()); ?></p>
    </main>
     <!-- Last PHP inside <body> so it captures messages queued during this request. -->
    <?php render_flash_messages(); ?>
<?php render_scripts(); ?>
</body>
</html>