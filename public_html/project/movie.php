<?php
require_once(__DIR__ . "/../../lib/app.php");
$return_to = safe_project_return_url(
    $_GET["return_to"] ?? "",
    [
        "guides.php",
        "my_guides.php",
        "profile.php",
        "admin/list_guides.php",
        "admin/guide_associations.php",
        "admin/unassociated_guides.php",
    ],
    "guides.php"
);

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) {
    flash("Missing movie id.", "danger");
    header("Location: " . project_url("movies.php"));
    exit;
}
$user_id = get_user_id(); // returns 0 when not logged in

$movie = NULL;
$genres = [];
$creators = [];
$cast = [];
$services = [];
$images = [];
try {
    $movie = select(
        "SELECT g.id, g.api_id, g.title, g.show_type, g.overview,
         IF(g.is_api = 0, 'Manual', 'API') AS source, g.created, g.modified,
         EXISTS (
            SELECT 1
            FROM UserMovies ug
            WHERE ug.movie_id = g.id
                AND ug.user_id = :user_id
                AND ug.is_active = 1
            ) AS is_saved
         FROM MOVIES g
         WHERE g.id = :id
         LIMIT 1",
        ["id" => $id, "user_id" => $user_id]
    );
    $movieGenreId = selectAll(
        "SELECT genre_id
        FROM MovieGenres
        WHERE movie_id = :movie_id",
        ["movie_id" => $id]
    );
    foreach ($movieGenreId as $genre_id) {
        $movieGenre = select(
            "SELECT genre
            FROM Genre
            WHERE id = :genre_id
            LIMIT 1",
            [":genre_id" => $genre_id["genre_id"]]
        );
        $genres[] = $movieGenre["genre"];
    }

    $movieCreatorId = selectAll(
        "SELECT creator_id
        FROM MovieCreators
        WHERE movie_id = :movie_id",
        ["movie_id" => $id]
    );
    foreach ($movieCreatorId as $creator_id) {
        $movieCreator = select(
            "SELECT creator
            FROM Creators
            WHERE id = :creator_id
            LIMIT 1",
            [":creator_id" => $creator_id["creator_id"]]
        );
        $creators[] = $movieCreator["creator"];
    }

    $movieCastId = selectAll(
        "SELECT member_id
        FROM MovieCast
        WHERE movie_id = :movie_id",
        ["movie_id" => $id]
    );
    foreach ($movieCastId as $member_id) {
        $movieCastMember = select(
            "SELECT member
            FROM Cast
            WHERE id = :member_id
            LIMIT 1",
            [":member_id" => $member_id["member_id"]]
        );
        $cast[] = $movieCastMember["member"];
    }

    $movieServiceId = selectAll(
        "SELECT service_id
        FROM MovieServices
        WHERE movie_id = :movie_id",
        ["movie_id" => $id]
    );
    foreach ($movieServiceId as $service_id) {
        $movieService = select(
            "SELECT service
            FROM Services
            WHERE id = :service_id
            LIMIT 1",
            [":service_id" => $service_id["service_id"]]
        );
        $services[] = $movieService["service"];
    }

    $movieImageId = selectAll(
        "SELECT image_id
        FROM MovieImages
        WHERE movie_id = :movie_id",
        ["movie_id" => $id]
    );
    foreach ($movieImageId as $image_id) {
        $movieImages = selectAll(
            "SELECT ImageType, ImageAddr
            FROM Images
            WHERE id = :image_id",
            [":image_id" => $image_id["image_id"]]
        );
        foreach ($movieImages as $image) {
            $images[$image['ImageType']] = $image['ImageAddr'];
        }
    }

    if (isset($images["Horizontal"])) {
        $images["Horizontal"] = sc_nullable_url($images["Horizontal"] ?? null);
    }
    if (isset($images["Vertical"])) {
        $images["Vertical"] = sc_nullable_url($images["Vertical"] ?? null);
    }

    if (!empty($genres)) {
        $movie["genres"] = $genres;
    }
    if (!empty($creators)) {
        $movie["creators"] = $creators;
    }
    if (!empty($cast)) {
        $movie["cast"] = $cast;
    }
    if (!empty($services)) {
        $movie["services"] = $services;
    }
    if (!empty($images)) {
        $movie["images"] = $images;
    }

} catch (Throwable $e) {
    error_log("Movie lookup failed: " . $e->getMessage());
    flash("The movie or show could not be loaded.", "danger");
    header("Location: " . project_url("movies.php"));
    exit;
}
if ($movie === null) {
    flash("Movie or show not found.", "warning");
    header("Location: " . project_url("movies.php"));
    exit;
}
?>
<!doctype html>
<html lang="en">
<head><?php render_head($movie["title"]); ?></head>
<body>
    <?php render_nav(); ?>
    <main class="container py-4">
                <?php
        $card_options = [
            "show_detail_view" => true,
            "return_to" => $return_to,
        ];
        render_movie_card($movie, $card_options);
        ?>
    </main>
    <?php render_flash_messages(); ?>
    <?php render_scripts(); ?>
</body>
</html>