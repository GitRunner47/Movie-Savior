<?php
// public_html/project/internal/toggle_saved_movie.php
/**
 * Usage contract:
 * - Accepts POST requests from the Save/Remove form in partials/movie_card.php.
 * - Requires a logged-in user.
 * - Expects movie_id as a positive integer and new_is_saved as 0 or 1.
 * - Accepts an optional local return_to path and query string for an approved page.
 * - Updates only the current session user's relationship, then flashes and redirects.
 */
require_once(__DIR__ . "/../../../lib/app.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . project_url("movies.php"));
    exit;
}

if (!is_logged_in()) {
    flash("Log in before saving a movie or show.", "warning");
    header("Location: " . project_url("login.php"));
    exit;
}

$return_url = safe_project_return_url(
    $_POST["return_to"] ?? "",
    ["movies.php", "my_movies.php", "movie.php", "profile.php"],
    "movies.php"
);

require_csrf_token($return_url);

$movie_id = filter_input(INPUT_POST, "movie_id", FILTER_VALIDATE_INT);
if (!$movie_id) {
    flash("Choose a valid movie or show.", "warning");
    header("Location: " . $return_url);
    exit;
}

$new_is_saved = filter_input(
    INPUT_POST,
    "new_is_saved",
    FILTER_VALIDATE_INT
);
if (!in_array($new_is_saved, [0, 1], true)) {
    flash("Choose a valid saved-movie action.", "warning");
    header("Location: " . $return_url);
    exit;
}

$user_id = get_user_id();

try {
    // UCID: kvv, Date: 8/9/26
    // This feature adds relationship links between users and entities while checking
    // for duplicate actions.
    $query = "SELECT * FROM UserMovies 
              WHERE user_id = :user_id
              AND movie_id = :movie_id LIMIT 1";
    $duplicate = select($query, ["user_id" => $user_id, "movie_id" => $movie_id]);

    if (!empty($duplicate) && $duplicate["is_active"] === $new_is_saved) {
        flash("Duplicate action requested.", "warning");
    } else {
        insert(
            "UserMovies",
            [
                "user_id" => $user_id,
                "movie_id" => $movie_id,
                "is_active" => $new_is_saved,
            ],
            [
                "update_duplicate" => true,
                "columns_to_update" => ["is_active"],
            ]
        );

        if ($new_is_saved === 1) {
            flash("Movie or show saved.", "success");
        } else {
            flash("Movie or show removed from your saved list.", "success");
        }
    }
} catch (Throwable $e) {
    error_log("Saved movie toggle failed: " . $e->getMessage());
    flash("The saved movie or show could not be updated.", "danger");
}

header("Location: " . $return_url);
exit;