<?php
// File: public_html/project/index.php
require_once(__DIR__ . "/../../lib/app.php");
?>
<!doctype html>
<html lang="en">
<head>
    <?php render_head("Home"); ?>
</head>
<body>
    <?php render_nav(); ?>
    <main class="container py-4">
        <div class="jumbotron">
            <h1 class="display-4">Welcome to Krish's project</h1>
            <p class="lead">This is for the Summer semester of IT202 2026.</p>
        </div>
    </main>
    <?php render_flash_messages(); ?>
    <?php render_scripts(); ?>
</body>

</html>
