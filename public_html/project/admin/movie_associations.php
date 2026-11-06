<?php
// Movie associations list all user-entity associations for all users
// Only visible by admin users
require_once(__DIR__ . "/../../../lib/app.php");
require_role("Admin");

$sort_options = [
    "modified" => "Date Saved",
    "username" => "Username",
    "title" => "Title",
    "show_type" => "Show Type",
    "overview" => "Overview",
];
$sort_column_map = [
    "modified" => "ug.modified",
    "username" => "u.username",
    "title" => "g.title",
    "show_type" => "g.show_type",
    "overview" => "g.overview",
];

$association_filter_rules = movie_filter_rules();
array_unshift($association_filter_rules, "username");

$list_config = [
    "filters" => $association_filter_rules,
    "sort_columns" => $sort_column_map,
];

// shared list-state

$list_state = build_list_query_state($_GET, $list_config);
$filters = $list_state["filters"];
$sort = $list_state["sort"];
$direction = $list_state["direction"];
$order_by = $list_state["order_by"];
$limit = $list_state["limit"];

$pagination_state = build_pagination_query_state($_GET, $limit);
$page = $pagination_state["page"];
$offset = $pagination_state["offset"];

$from = "FROM UserMovies ug
    JOIN Users u ON u.id = ug.user_id
    JOIN MOVIES g ON g.id = ug.movie_id";

$where = "WHERE ug.is_active = 1";

$filter_query = build_movie_filter_query($filters, "g.");
if (!empty($filter_query["sql"])) {
    $where .= " AND " . $filter_query["sql"];
}
$params = $filter_query["params"];

$params = $filter_query["params"];
if (!empty($filters["username"])) {
    $where .= " AND u.username LIKE :username";
    $params["username"] = "%" . $filters["username"] . "%";
}

$matching_count = 0;
$total_pages = 1;
$relationships  = [];

try {
    // The count uses the same filters but no ORDER BY or LIMIT.
    $count_row = select(
        "SELECT COUNT(*) AS total
         $from
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
    $relationships = selectAll(
        "SELECT ug.modified AS saved_on,
                u.id AS user_id, u.username,
                g.id AS movie_id, g.title, g.show_type, g.overview,
                IF(g.api_id IS NULL, 'Manual', 'API') AS source
         $from
         $where
         ORDER BY $order_by, ug.user_id ASC, ug.movie_id ASC
         LIMIT :limit OFFSET :offset",
        $list_params
    );

    for($i = 0; $i < sizeof($relationships); $i++) {
        if ($relationships[$i]["overview"] === null || trim($relationships[$i]["overview"]) === "") {
            $relationships[$i]["overview"] = "No overview available.";
        }
    }

} catch (Throwable $e) {
    error_log("Movie association list failed: " . $e->getMessage());
    flash("Movie associations could not be loaded.", "danger");
}

$shown_count = count($relationships);

$relationship_columns = [
    "title" => "Title",
    "show_type" => "Show Type",
    "overview" => "Overview",
    "source" => "Source",
    "username" => "User",
    "saved_on" => "Saved",
];

$return_to = current_project_request_url("admin/movie_associations.php");
$relationship_actions = [
    [
        "label" => "View",
        "url" => "movie.php",
        "row_key" => "movie_id",
        "parameter" => "id",
        "query_parameters" => ["return_to" => $return_to],
        "variant" => "primary",
    ],
    [
        "label" => "View User",
        "url" => "profile.php",
        "row_key" => "user_id",
        "parameter" => "id",
        "query_parameters" => ["return_to" => $return_to],
        "variant" => "secondary",
    ],
    [
        "label" => "Remove",
        "url" => "admin/remove_movie_association.php",
        "method" => "POST",
        "row_parameters" => [
            "user_id" => "user_id",
            "movie_id" => "movie_id",
        ],
        "query_parameters" => ["return_to" => $return_to],
        "variant" => "danger",
    ],
];

?>
<!doctype html>
<html lang="en">

<head><?php render_head("Manage Movies and Shows"); ?></head>

<body>
    <?php render_nav(); ?>
    <main class="container py-4">
        <h1>Manage Movies and Shows</h1>
        <?php render_movie_search(
            $filters,
            $limit,
            $sort,
            $direction,
            $sort_options
        ); ?>
        <?php
        render_result_summary($shown_count, $matching_count);
        render_table(
            $relationships,
            $relationship_columns,
            $relationship_actions,
            "No relationships matched these filters."
        );
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