<?php
// assign_movies page that allows user-entity associations to be added or removed
// for admins only
require_once(__DIR__ . "/../../../lib/app.php");
require_role("Admin");

$db = getDB();
$usernameSearch = trim((string) ($_GET["username"] ?? $_POST["username"] ?? ""));
$movieSearch = trim((string) ($_GET["movie"] ?? $_POST["movie"] ?? ""));
$users = [];
$movies = [];

$returnParams = array_filter(
    ["username" => $usernameSearch, "movie" => $movieSearch],
    fn($value) => $value !== ""
);
$redirect = "admin/assign_movies.php";
if (!empty($returnParams)) {
    $redirect .= "?" . http_build_query($returnParams);
}

if (isset($_POST["action"]) && $_POST["action"] === "toggle_associations") {
    require_csrf_token(project_url($redirect)); // New line.

    // UCID: kvv, Date: 8/9/26
    // selected pair processing
    $submittedUserIds = $_POST["user_ids"] ?? [];
    $submittedMovieIds = $_POST["movie_ids"] ?? [];
    if (!is_array($submittedUserIds) || !is_array($submittedMovieIds)) {
        $submittedUserIds = [];
        $submittedMovieIds = [];
    }

    $userIds = array_unique(array_filter(
        array_map("intval", $submittedUserIds),
        fn($id) => $id > 0
    ));
    $movieIds = array_unique(array_filter(
        array_map("intval", $submittedMovieIds),
        fn($id) => $id > 0
    ));

    $userIds = array_slice(array_values($userIds), 0, 25);
    $movieIds = array_slice(array_values($movieIds), 0, 25);

    if (empty($userIds) || empty($movieIds)) {
        flash("Select at least one user and one movie or show.", "warning");
    } else {
        // toggles relationship status in the relationship table
        try {
            $stmt = $db->prepare(
                "INSERT INTO UserMovies (user_id, movie_id, is_active)
                 VALUES (:user_id, :movie_id, 1)
                 ON DUPLICATE KEY UPDATE
                    is_active = IF(is_active = 1, 0, 1)"
            );
            $toggledCount = 0;
            $failedCount = 0;

            foreach ($userIds as $userId) {
                foreach ($movieIds as $movieId) {
                    try {
                        $stmt->execute([
                            ":user_id" => $userId,
                            ":movie_id" => $movieId,
                        ]);
                        $toggledCount++;
                    } catch (PDOException $e) {
                        $failedCount++;
                        error_log(
                            "Movie association toggle failed for user $userId"
                                . " and movie $movieId: " . $e->getMessage()
                        );
                    }
                }
            }

            if ($toggledCount > 0) {
                flash("$toggledCount selected user-movie pair(s) were toggled.", "success");
            }

            if ($failedCount > 0) {
                flash("$failedCount selected pair(s) could not be updated.", "warning");
            }
        } catch (PDOException $e) {
            error_log("Movie association setup failed: " . $e->getMessage());
            flash("Could not update movie associations.", "danger");
        }
    }

    header("Location: " . project_url($redirect));
    exit;
}

try {
    // Searches for the user
    if (!empty($usernameSearch)) {
        $users = selectAll(
            "SELECT u.id, u.username,
                    GROUP_CONCAT(
                        g.title ORDER BY g.title SEPARATOR '|||'
                    ) AS associated_movie_titles
             FROM Users u
             LEFT JOIN UserMovies ug
                ON ug.user_id = u.id
                AND ug.is_active = 1
             LEFT JOIN MOVIES g ON g.id = ug.movie_id
             WHERE u.username LIKE :username
             GROUP BY u.id, u.username
             ORDER BY u.username ASC
             LIMIT 25",
            ["username" => "%$usernameSearch%"]
        );
    }

    // Searches for similarly named movies
    if (!empty($movieSearch)) {
        $movies = selectAll(
            "SELECT id, title, show_type, overview
             FROM MOVIES
             WHERE title LIKE :title
             ORDER BY title ASC
             LIMIT 25",
            ["title" => "%$movieSearch%"]
        );
    }
} catch (Throwable $e) {
    error_log("Association search failed: " . $e->getMessage());
    flash("Association choices could not be loaded.", "danger");
}
?>
<!doctype html>
<html lang="en">

<head>
    <?php render_head("Assign Movies or Shows"); ?>
</head>

<body>
    <?php render_nav(); ?>
    <main class="container py-4">
        <h1>Assign Movies or Shows</h1>
        <form method="get" class="row g-3 align-items-end mb-4">
            <div class="col-md-5">
                <?php render_input([
                    "label" => "Username Search",
                    "name" => "username",
                    "value" => $usernameSearch,
                ]); ?>
            </div>
            <div class="col-md-5">
                <?php render_input([
                    "label" => "Movie or Show Title Search",
                    "name" => "movie",
                    "value" => $movieSearch,
                ]); ?>
            </div>
            <div class="col-md-2">
                <?php render_button(["text" => "Search", "type" => "submit"]); ?>
            </div>
        </form>
        <form id="toggleForm" method="post">
            <?php
            render_csrf_input();
            render_input([
                "type" => "hidden",
                "name" => "action",
                "value" => "toggle_associations",
            ]);
            render_input([
                "type" => "hidden",
                "name" => "username",
                "value" => $usernameSearch,
            ]);
            render_input([
                "type" => "hidden",
                "name" => "movie",
                "value" => $movieSearch,
            ]);
            ?>
        </form>

        <div class="row g-4">
            <fieldset class="col-md-6">
                <legend class="h2">Users</legend>
                <?php if (empty($users)): ?>
                    <p>No users match this search.</p>
                <?php endif; ?>
                <?php foreach ($users as $user): ?>
                    <?php
                    // GROUP_CONCAT() uses ||| so each title can become one list item.
                    $associatedTitles = array_filter(explode(
                        "|||",
                        (string) ($user["associated_movie_titles"] ?? "")
                    ));
                    ?>
                    <div class="list-group-item mb-3 border rounded">
                        <?php render_input([
                            "type" => "checkbox",
                            "id" => "user_" . (int) $user["id"],
                            "name" => "user_ids[]",
                            "value" => (int) $user["id"],
                            "label" => (string) $user["username"],
                            "wrapperClass" => "mb-2",
                            "attributes" => ["form" => "toggleForm"],
                        ]); ?>

                        <div class="bg-body-tertiary border rounded p-2 overflow-auto"
                            style="max-height: 8rem;">
                            <p class="small fw-semibold mb-1">Currently Associated</p>
                            <?php if (empty($associatedTitles)): ?>
                                <p class="small text-body-secondary mb-0">No active movies</p>
                            <?php else: ?>
                                <ul class="small mb-0 ps-3">
                                    <?php foreach ($associatedTitles as $title): ?>
                                        <li><?php echo htmlspecialchars($title); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </fieldset>

            <fieldset class="col-md-6">
                <legend class="h2">Movies and Shows</legend>
                <?php if (empty($movies)): ?>
                    <p>No movies or shows match this search.</p>
                <?php endif; ?>
                <?php foreach ($movies as $movie): ?>
                    <?php render_input([
                        "type" => "checkbox",
                        "id" => "movie_" . (int) $movie["id"],
                        "name" => "movie_ids[]",
                        "value" => (int) $movie["id"],
                        "label" => (string) $movie["title"],
                        "wrapperClass" => "mb-2",
                        "attributes" => ["form" => "toggleForm"],
                    ]); ?>
                <?php endforeach; ?>
            </fieldset>
        </div>

        <?php if (!empty($users) && !empty($movies)): ?>
            <?php render_button([
                "text" => "Toggle Selected Associations",
                "variant" => "warning",
                "attributes" => ["form" => "toggleForm"],
            ]); ?>
        <?php endif; ?>
    </main>
    <?php render_flash_messages(); ?>
    <?php render_scripts(); ?>

</body>
</html>