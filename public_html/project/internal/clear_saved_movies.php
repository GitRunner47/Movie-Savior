<?php
// public_html/project/internal/clear_saved_guides.php
require_once(__DIR__ . "/../../../lib/app.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    flash("That saved-movie action is not available.", "warning");
    header("Location: " . project_url("my_movies.php"));
    exit;
}

if (!is_logged_in()) {
    flash("Log in to update saved movies or shows.", "warning");
    header("Location: " . project_url("login.php"));
    exit;
}

require_csrf_token(project_url("my_movies.php")); // New line.

try {
    $db = getDB();
    $stmt = $db->prepare(
        "UPDATE UserMovies
         SET is_active = 0
         WHERE user_id = :user_id AND is_active = 1"
    );
    $stmt->execute([":user_id" => get_user_id()]);
    flash("All saved movies and shows were removed.", "success");
} catch (Throwable $e) {
    error_log("Clear saved movies failed: " . $e->getMessage());
    flash("Saved movies or shows could not be cleared.", "danger");
}

header("Location: " . project_url("my_movies.php"));
exit;