<?php
// public_html/project/admin/delete_guide.php
require_once(__DIR__ . "/../../../lib/app.php");
require_role("Admin");

$return_to = safe_project_return_url(
    $_GET["return_to"] ?? "",
    [
        "movies.php",
        "my_movies.php",
        "profile.php",
        "admin/list_movies.php",
        "admin/movie_associations.php",
        "admin/unassociated_movies.php",
    ],
    "admin/list_movies.php"
);

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) {
    flash("Missing movie id.", "danger");
    header("Location: " . $return_to);
    exit;
}
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    flash("Use the movie list to delete a movie or show.", "warning");
    header("Location: " . $return_to);
    exit;
}

require_csrf_token($return_to);

try {
    $db = getDB();

    $image_ids = selectAll("SELECT image_id 
                        FROM MovieImages
                        WHERE movie_id = :id", 
                        ["id" => $id]);

    $stmt = $db->prepare("DELETE FROM MovieImages WHERE movie_id = :id");
    $stmt->execute([":id" => $id]);

    foreach($image_ids as $image_id) {
        $stmt = $db->prepare("DELETE FROM Images WHERE id = :image_id LIMIT 1");
        $stmt->execute([":image_id" => $image_id["image_id"]]);
    }

    $stmt = $db->prepare("DELETE FROM MovieServices WHERE movie_id = :id");
    $stmt->execute([":id" => $id]);

    $stmt = $db->prepare("DELETE FROM MovieCast WHERE movie_id = :id");
    $stmt->execute([":id" => $id]);

    $stmt = $db->prepare("DELETE FROM MovieCreators WHERE movie_id = :id");
    $stmt->execute([":id" => $id]);

    $stmt = $db->prepare("DELETE FROM MovieGenres WHERE movie_id = :id");
    $stmt->execute([":id" => $id]);

    $stmt = $db->prepare("DELETE FROM MOVIES WHERE id = :id LIMIT 1");
    $stmt->execute([":id" => $id]);

    if ($stmt->rowCount() === 1) {
        flash("Movie deleted.", "success");
    } else {
        flash("Movie not found.", "warning");
    }
} catch (PDOException $e) {
    error_log("Movie deletion failed: " . $e->getMessage());
    flash("The movie or show could not be deleted.", "danger");
}

header("Location: " . $return_to);
exit;