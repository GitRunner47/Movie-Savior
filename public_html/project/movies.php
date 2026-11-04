<?php
// public_html/project/admin/list_guides.php
require_once(__DIR__ . "/../../lib/app.php");

$sort_options = [
    "modified" => "Recently Updated",
    "title" => "Title",
    "show_type" => "Show Type",
    "overview" => "Overview",
];

$allowed_sort_columns = array_keys($sort_options);
$list_config = [
    "filters" => movie_filter_rules(),
    // Each submitted sort key matches its trusted SQL column on these pages.
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
$where = "";
if (!empty($filter_query["sql"])) {
    $where = "WHERE " . $filter_query["sql"];
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

    // A removal may leave the requested page beyond the new final page.
    if ($page > $total_pages) {
        $page = $total_pages;
        $offset = pagination_offset($page, $limit);
    }

    $list_params = array_merge($params, [
        "limit" => $limit,
        "offset" => $offset,
    ]);
    $user_id = get_user_id(); // returns 0 when not logged in
    $list_params = array_merge($list_params, ["user_id" => $user_id]);

    $movies = selectAll(
        "SELECT g.id, g.title, g.show_type, g.overview,
                IF(g.api_id IS NULL, 'Manual', 'API') AS source,
                EXISTS (
                    SELECT 1
                    FROM UserMovies ug
                    WHERE ug.movie_id = g.id
                    AND ug.user_id = :user_id
                    AND ug.is_active = 1
                ) AS is_saved
         FROM MOVIES g
         $where
         ORDER BY $order_by, g.id ASC
         LIMIT :limit OFFSET :offset",
        $list_params
    );

    for($i = 0; $i < sizeof($movies); $i++) {
        if ($movies[$i]["overview"] === null || trim($movies[$i]["overview"]) === "") {
            $movies[$i]["overview"] = "No overview available.";
        }
    }
} catch (Throwable $e) {
    error_log("Admin guide list failed: " . $e->getMessage());
    flash("Movies could not be loaded.", "danger");
}

$shown_count = count($movies);

$return_to = current_project_request_url("movies.php");
?>
<!doctype html>
<html lang="en">
<head><?php render_head("Search Movies"); ?></head>
<body>
    <?php render_nav(); ?>
    <main class="container py-4">
        <h1>Search Movies or Shows</h1>
        <?php render_movie_search(
                $filters,
                $limit,
                $sort,
                $direction,
                $sort_options
        );?>
        <?php
        render_result_summary($shown_count, $matching_count);
        render_movie_grid($movies);
        $query_params = array_merge($filters, [
            "sort" => $sort,
            "direction" => $direction,
            "limit" => $limit,
        ]);
        render_pagination(
            $page,
            $total_pages,
            $query_params
        );
        ?>
    </main>
    <?php render_flash_messages(); ?>
    <?php render_scripts(); ?>
</body>
</html>