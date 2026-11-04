<?php
// partials/guide_search.php
// Usage: Call render_guide_search($filters, $limit) from either guide list page.
?>
<form class="row g-3 align-items-end mb-4" method="get">
    <div class="col-md-6 col-xl-4">
        <?php render_input([
            "label" => "Title",
            "name" => "title",
            "value" => $filters["title"] ?? "",
            "attributes" => ["maxlength" => 100],
        ]); ?>
    </div>
    <div class="col-md-4 col-xl-2">
        <?php render_input([
            "label" => "Show Type",
            "name" => "show_type",
            "type" => "select",
            "value" => $filters["show_type"] ?? "both",
            "options" => [
                "both" => "Both",
                "movie" => "Movie",
                "series" => "Series",
            ],
        ]); ?>
    </div>
    <?php if (array_key_exists("username", $filters ?? [])): ?>
        <div class="col-md-6 col-xl-3">
            <?php render_input([
                "label" => "Username",
                "name" => "username",
                "value" => $filters["username"] ?? "",
                "attributes" => ["maxlength" => 60],
            ]); ?>
        </div>
    <?php endif; ?>
    <div class="col-md-4 col-xl-3">
        <?php render_input([
            "label" => "Sort By",
            "name" => "sort",
            "type" => "select",
            "value" => $sort ?? "",
            "options" => $sort_options ?? [],
        ]); ?>
    </div>
    <div class="col-md-4 col-xl-2">
        <?php render_input([
            "label" => "Direction",
            "name" => "direction",
            "type" => "select",
            "value" => $direction ?? "",
            "options" => [
                "asc" => "Ascending",
                "desc" => "Descending",
            ],
        ]); ?>
    </div>
    <div class="col-md-3 col-xl-2">
        <?php render_input([
            "label" => "Limit",
            "name" => "limit",
            "type" => "number",
            "value" => $limit ?? "",
            "attributes" => ["min" => 1, "max" => 100, "required" => true],
        ]); ?>
    </div>
    <div class="col-md-3 col-xl-2">
        <?php render_button([
            "text" => "Search",
            "attributes" => ["class" => "w-100"],
        ]); ?>
    </div>
    <div class="col-md-3 col-xl-2">
       
        <a href="?" class="btn btn-secondary">Reset</a>
    </div>
</form>