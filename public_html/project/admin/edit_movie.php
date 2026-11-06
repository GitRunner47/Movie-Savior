<?php
// public_html/project/admin/edit_guide.php
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
        "movie.php",
    ],
    "admin/list_movies.php"
);

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) {
    flash("Missing movie id.", "danger");
    header("Location: " . project_url("admin/list_movies.php"));
    exit;
}

$errors = [];
$movie = null;
if (isset($_POST["action"]) && $_POST["action"] === "update_movie") {
    require_csrf_token($return_to); // New line.

    $title = "";
    $show_type = "movie";
    $overview = "";

    if (isset($_POST["title"]) && is_string($_POST["title"])) {
        $title = trim($_POST["title"]);
    }
    if (isset($_POST["show_type"]) && is_string($_POST["show_type"])) {
        $show_type = $_POST["show_type"];
    }
    if (isset($_POST["overview"]) && is_string($_POST["overview"])) {
        $overview = trim($_POST["overview"]);
    }

    if ($title === "" || strlen($title) > 100) {
        $errors[] = "Title is required and must be 150 characters or less.";
    }
    if (strlen($overview) > 500000) {
        $errors[] = "Overview must be 500000 characters or less.";
    }
    $valid_show_types = ["movie", "series"];
    if (!in_array($show_type, $valid_show_types, true)) {
        $errors[] = "Choose a valid show type.";
    }
 
    if (empty($errors)) {
        try {
            update("MOVIES", [
                "id" => $id,
                "title" => $title,
                "show_type" => $show_type,
                "overview" => $overview
            ]);

            flash("Movie or show updated.", "success");
            header("Location: " . project_url("admin/edit_movie.php?id=$id"));
            exit;
        } catch (Throwable $e) {
            error_log("Movie update failed: " . $e->getMessage());
            $errors[] = "The guide could not be updated.";
        }
    }

    // Keep safe submitted values visible after validation errors.
    $movie = compact(
        "id", "title", "show_type", "overview"
    );
}

try {
    $movie = select(
        "SELECT id, title, overview, show_type
         FROM MOVIES WHERE id = :id LIMIT 1",
        ["id" => $id]
    );

} catch (Throwable $e) {
    error_log("Movie lookup failed: " . $e->getMessage());
    flash("The movie could not be loaded.", "danger");
    header("Location: " . project_url("admin/list_movies.php"));
    exit;
}
if ($movie === null) {
    flash("Movie not found.", "warning");
    header("Location: " . project_url("admin/list_movies.php"));
    exit;
}

flash_errors($errors);
?>
<!doctype html>
<html lang="en">
<head>
    <?php render_head("Edit Movie or Show"); ?>
</head>
<body>
    <?php render_nav(); ?>
    <main class="container py-4">
        <h1>Edit Movie or Show</h1>
        <form method="post">
    <?php
    render_csrf_input();
    render_input([
        "name" => "title",
        "value" => $movie["title"],
        "attributes" => ["required" => true, "maxlength" => 100],
    ]);
    render_input([
        "label" => "Show Type",
        "type" => "select",
        "name" => "show_type",
        "value" => $movie["show_type"],
        "options" => ["movie" => "Movie", "series" => "Show"],
    ]);
    render_input([
        "type" => "textarea",
        "name" => "overview",
        "value" => $movie["overview"],
        "attributes" => ["rows" => 5],
    ]);
    render_button([
        "text" => "Update Movie or Show",
        "variant" => "warning",
        "attributes" => ["name" => "action", "value" => "update_movie"],
    ]);
    ?>
        </form>
    </main>
    <?php render_flash_messages(); ?>
    <?php render_scripts(); ?>
</body>
</html>