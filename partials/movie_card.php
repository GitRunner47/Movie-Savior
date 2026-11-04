<?php
// partials/movie_card.php
// List usage: render_movie_card($movie);
// Single-page usage: render_movie_card($movie, ["show_detail_view" => true]);
$options = $options ?? [];
$movie = $movie ?? [];
$show_detail_view = (bool) ($options["show_detail_view"] ?? false);
$show_saved_on = (bool) ($options["show_saved_on"] ?? false);
$has_relationship_state = array_key_exists("is_saved", $movie);


$allowed_list_paths = [
    "movies.php",
    "my_movies.php",
    "profile.php",
    "admin/list_movies.php",
    "admin/movie_associations.php",
    "admin/unassociated_movies.php",
];
$current_url = current_project_request_url("movies.php");
$return_to = safe_project_return_url(
    $options["return_to"] ?? "",
    $allowed_list_paths,
    "movies.php"
);

$action_return_url = $current_url;
$delete_return_url = $show_detail_view ? $return_to : $current_url;
$movie_url = project_url("movie.php") . "?" . http_build_query([
    "id" => $movie["id"],
    "return_to" => $current_url,
]);
$edit_url = project_url("admin/edit_movie.php") . "?" . http_build_query([
    "id" => $movie["id"],
    "return_to" => $action_return_url,
]);
$delete_url = project_url("admin/delete_movie.php") . "?" . http_build_query([
    "id" => $movie["id"],
    "return_to" => $delete_return_url,
]);
?>
<article class="card h-100">
    <div class="card-body d-flex flex-column">
        <?php if ($show_detail_view): ?>
            <h1 class="card-title">
                <?php echo htmlspecialchars((string) $movie["title"]); ?>
            </h1>
        <?php else: ?>
            <h2 class="card-title h5">
                <?php echo htmlspecialchars((string) $movie["title"]); ?>
            </h2>
        <?php endif; ?>
        <p class="text-body-secondary">
            <?php echo htmlspecialchars((string) $movie["show_type"]); ?>
        </p>

        <?php if ($show_detail_view): ?>
            <dl class="row">
                <dt class="col-sm-3">Genres</dt>
                <?php if (isset($movie["genres"])): ?>
                <dd class="col-sm-9"><?php echo htmlspecialchars((string) implode(", ", $movie["genres"])); ?></dd>
                <?php else: ?>
                <dd class="col-sm-9">None</dd>                        
                <?php endif; ?>
                <dt class="col-sm-3">Creators</dt>
                <?php if (isset($movie["creators"])): ?>
                <dd class="col-sm-9"><?php echo htmlspecialchars((string) implode(", ", $movie["creators"])); ?></dd>
                <?php else: ?>
                <dd class="col-sm-9">None</dd>
                <?php endif; ?>
                <dt class="col-sm-3">Cast</dt>
                <?php if (isset($movie["cast"])): ?>
                <dd class="col-sm-9"><?php echo htmlspecialchars((string) implode(", ", $movie["cast"])); ?></dd>
                <?php else: ?>
                <dd class="col-sm-9">None</dd>
                <?php endif; ?>
                <dt class="col-sm-3">Availability in the US</dt>
                <?php if (isset($movie["services"])): ?>
                <dd class="col-sm-9"><?php echo htmlspecialchars((string) implode(", ", $movie["services"])); ?></dd>
                <?php else: ?>
                <dd class="col-sm-9">None</dd>
                <?php endif; ?>
            </dl>

            <?php if (isset($movie["images"]) && $movie["images"]["Vertical"] !== null): ?>
                <dl>
                    <img src=<?php echo (string) htmlspecialchars($movie["images"]["Vertical"]) ?> alt="Image could not be displayed">
                </dl>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($show_detail_view): ?>
            <p class="lead fs-6">
                Overview
            </p>
            <?php if (!empty($movie["overview"])): ?>
                <p class="card-text">
                    <?php echo nl2br(htmlspecialchars((string) $movie["overview"])); ?>
                </p>
            <?php else: ?>
                <p class="card-text">
                    No overview available.
                </p>
            <?php endif; ?>
        <?php else: ?>
            <?php if (!empty($movie["overview"])): ?>
                <p class="lead fs-6">
                    Overview
                </p>
                <p class="card-text">
                    <?php echo nl2br(htmlspecialchars((string) $movie["overview"])); ?>
                </p>
            <?php else: ?>
                <p class="card-text">
                    No overview available.
                </p>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($show_saved_on && !empty($movie["saved_on"])): ?>
            <p class="small text-body-secondary">
                Saved <?php echo htmlspecialchars((string) $movie["saved_on"]); ?>
            </p>
        <?php endif; ?>

         <div class="d-flex flex-wrap gap-2 mt-auto">
            <?php if (!$show_detail_view): ?>
                <a class="btn btn-primary"
                    href="<?php echo htmlspecialchars($movie_url); ?>">View</a>
            <?php endif; ?>
            <?php if (is_logged_in() && $has_relationship_state): ?>
                <form method="post"
                    action="<?php echo project_url("internal/toggle_saved_movie.php"); ?>">
                    <?php
                    render_csrf_input(); // New line.
                    render_input([
                        "type" => "hidden",
                        "name" => "movie_id",
                        "value" => (int) $movie["id"],
                    ]);
                    render_input([
                        "type" => "hidden",
                        "name" => "return_to",
                        "value" => $action_return_url,
                    ]);

                    $is_saved = (int) ($movie["is_saved"] ?? 0);
                    $new_is_saved = $is_saved === 1 ? 0 : 1;
                    render_input([
                        "type" => "hidden",
                        "name" => "new_is_saved",
                        "value" => $new_is_saved,
                    ]);

                    $button_text = $movie["show_type"] === "series" ? "Save Show" : "Save Movie";
                    if ($is_saved === 1) {
                        $button_text = $movie["show_type"] === "series" ? "Remove Show" : " Remove Movie";
                    }
                    render_button([
                        "text" => $button_text,
                        "variant" => "primary",
                    ]);
                    ?>
                </form>
            <?php endif; ?>
            <?php if (has_role("Admin")): ?>
                <a class="btn btn-warning"
                    href="<?php echo project_url($edit_url); ?>">
                    Edit
                </a>
                <form method="post"
                    action="<?php echo project_url($delete_url); ?>">
                    <?php 
                    render_csrf_input(); // New line.
                    render_button([
                        "text" => "Delete",
                        "variant" => "danger",
                    ]); ?>
                </form>
            <?php endif; ?>
            <?php if ($show_detail_view): ?>
                <a class="btn btn-secondary"
                    href="<?php echo project_url($return_to); ?>">Back To Movies</a>
            <?php endif; ?>
        </div>
    </div>
</article>
