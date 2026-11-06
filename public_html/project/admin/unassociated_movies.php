<?php
// unassociated_movies page lists all unassociated movies
// for admins only
require_once(__DIR__ . "/../../../lib/app.php");
require_role("Admin");

// Start with the unchanged list_movies.php filter and sort configuration.
$sort_options = [
    "modified" => "Recently Updated",
    "title" => "Title",
    "show_type" => "Show Type",
    "overview" => "Overview",
];
$allowed_sort_columns = array_keys($sort_options);
$list_config = [
    "filters" => movie_filter_rules(),
    "sort_columns" => $allowed_sort_columns,
];
$list_state = build_list_query_state($_GET, $list_config);
$filters = $list_state["filters"];
$sort = $list_state["sort"];
$direction = $list_state["direction"];
$order_by = $list_state["order_by"];
$limit = $list_state["limit"];

$pagination_state = build_pagination_query_state($_GET, $limit);
$page = $pagination_state["page"];
$offset = $pagination_state["offset"];

$filter_query = build_movie_filter_query($filters);
$where = "WHERE NOT EXISTS (
    SELECT 1
    FROM UserMovies ug
    WHERE ug.movie_id = MOVIES.id
      AND ug.is_active = 1
)";
if (!empty($filter_query["sql"])) {
    $where .= " AND " . $filter_query["sql"];
}
$params = $filter_query["params"];

$matching_count = 0;
$total_pages = 1;
$movies = [];
try {
    $count_row = select(
        "SELECT COUNT(*) AS total
         FROM MOVIES
         $where
         LIMIT 1",
        $params
    );
    $matching_count = (int) ($count_row["total"] ?? 0);
    $total_pages = pagination_total_pages($matching_count, $limit);
    if ($page > $total_pages) {
        $page = $total_pages;
        $offset = pagination_offset($page, $limit);
    }

    $list_params = array_merge($params, [
        "limit" => $limit,
        "offset" => $offset,
    ]);
    $movies = selectAll(
        "SELECT id, title, show_type, overview,
                IF(api_id IS NULL, 'Manual', 'API') AS source
         FROM MOVIES
         $where
         ORDER BY $order_by, id ASC
         LIMIT :limit OFFSET :offset",
        $list_params
    );
    for($i = 0; $i < sizeof($movies); $i++) {
        if ($movies[$i]["overview"] === null || trim($movies[$i]["overview"]) === "") {
            $movies[$i]["overview"] = "No overview available.";
        }
    }
} catch (Throwable $e) {
    error_log("Unassociated movie list failed: " . $e->getMessage());
    flash("Unassociated movies and shows could not be loaded.", "danger");
}
$shown_count = count($movies);

// Keep the existing list_guides.php columns and remove only the Delete action.
$movie_columns = [
    "title" => "Title",
    "show_type" => "Show Type",
    "overview" => "Overview",
    "source" => "Source",
];
$return_to = current_project_request_url("admin/unassociated_movies.php");
$movie_actions = [
    [
        "label" => "View",
        "url" => "movie.php",
        "query_parameters" => ["return_to" => $return_to],
        "variant" => "primary",
    ],
    [
        "label" => "Edit",
        "url" => "admin/edit_movie.php",
        "query_parameters" => ["return_to" => $return_to],
        "variant" => "warning",
    ],
];
?>
<!doctype html>
<html lang="en">

<head><?php render_head("Unassociated Movies and Shows"); ?></head>

<body>
    <?php render_nav(); ?>
    <main class="container py-4">
        <h1>Unassociated Movies and Shows</h1>
        <?php render_movie_search(
            $filters,
            $limit,
            $sort,
            $direction,
            $sort_options
        ); ?>
        <?php render_result_summary($shown_count, $matching_count); ?>
        <?php render_table(
            $movies,
            $movie_columns,
            $movie_actions,
            "No unassociated movies or shows matched the selected filters."
        ); ?>
        <?php
        $query_params = array_merge($filters, [
            "sort" => $sort,
            "direction" => $direction,
            "limit" => $limit,
        ]);
        render_pagination($page, $total_pages, $query_params);
        ?>
    </main>
    <?php render_flash_messages(); ?>
    <?php render_scripts(); ?>
</body>
</html>