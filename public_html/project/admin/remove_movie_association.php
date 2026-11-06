<?php
// public_html/project/admin/remove_guide_association.php
/**
 * Accepts one user_id and movie_id pair from the Admin Movie Associations
 * page. Soft-removes only that relationship.
 */
require_once(__DIR__ . "/../../../lib/app.php");
require_role("Admin");

$default_url = project_url("admin/movie_associations.php");
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . $default_url);
    exit;
}

$return_url = $default_url;
$requested_return = $_GET["return_to"] ?? "";
if (
    is_string($requested_return)
    && !str_contains($requested_return, "\r")
    && !str_contains($requested_return, "\n")
    && (
        $requested_return === $default_url
        || str_starts_with($requested_return, $default_url . "?")
    )
) {
    $return_url = $requested_return;
}

require_csrf_token($return_url);

$user_id = filter_input(INPUT_POST, "user_id", FILTER_VALIDATE_INT);
$movie_id = filter_input(INPUT_POST, "movie_id", FILTER_VALIDATE_INT);
if (!$user_id || !$movie_id) {
    flash("Choose a valid user and movie relationship.", "warning");
    header("Location: " . $return_url);
    exit;
}

// Remove movie association
try {
    update(
        "UserMovies",
        [
            "user_id" => $user_id,
            "movie_id" => $movie_id,
            "is_active" => 0,
        ],
        ["user_id", "movie_id"]
    );
    flash("Relationship removed.", "success");
} catch (Throwable $e) {
    error_log("Admin relationship removal failed: " . $e->getMessage());
    flash("The relationship could not be removed.", "danger");
}

header("Location: " . $return_url);
exit;