<?php
function render_nav()
{
    require(__DIR__ . "/../partials/nav.php");
}
function render_flash_messages()
{
    require(__DIR__ . "/../partials/flash.php");
}
/**
 * Renders the shared document metadata and styles for one page.
 *
 * @param string $title Text displayed in the browser tab.
 */
function render_head(string $title): void
{
    require(__DIR__ . "/../partials/head.php");
}

function render_scripts(): void
{
    require(__DIR__ . "/../partials/scripts.php");
}

/**
 * Builds escaped HTML attributes from a small configuration array.
 *
 * @param array $attributes Attribute names and their values.
 * @return string Escaped attributes ready for an HTML element.
 */
function render_html_attributes(array $attributes): string
{
    $parts = [];

    foreach ($attributes as $name => $value) {
        if (!is_string($name) || !preg_match("/^[A-Za-z_:][A-Za-z0-9:_.-]*$/", $name)) {
            continue;
        }

        if ($value === false || $value === null) {
            continue;
        }

        $safe_name = htmlspecialchars($name);
        if ($value === true) {
            $parts[] = $safe_name;
            continue;
        }

        $safe_value = htmlspecialchars((string) $value);
        $parts[] = "$safe_name=\"$safe_value\"";
    }

    return implode(" ", $parts);
}

/**
 * Renders one form field from its configuration.
 *
 * @param array $field Label, name, type, value, and HTML attributes.
 */
function render_input(array $field): void
{
    require(__DIR__ . "/../partials/input.php");
}

// Add this function immediately after render_input().

/** Renders the current session's CSRF token as a hidden form field. */
function render_csrf_input(): void
{
    render_input([
        "type" => "hidden",
        "name" => "csrf_token",
        "value" => csrf_token(),
    ]);
}

// Keep render_button(), render_table(), and the other render helpers below.

/**
 * Renders one button from its configuration.
 *
 * @param array $button Text, type, variant, and HTML attributes.
 */
function render_button(array $button): void
{
    require(__DIR__ . "/../partials/button.php");
}

/**
 * Renders rows as a table with optional authorized actions.
 *
 * @param array $rows Database or API rows to display.
 * @param array $columns Row keys mapped to visible column headings.
 * @param array $actions Allowed links or forms for each row.
 * @param string $empty_message Message displayed when there are no rows.
 */
function render_table(
    array $rows,
    array $columns,
    array $actions = [],
    string $empty_message = "No records found."
): void {
    require(__DIR__ . "/../partials/table.php");
}

// In lib/render_functions.php, replace the existing two-argument
// render_guide_search() function. Keep the closing PHP tag below it.

/**
 * Renders the shared guide filters, limit, and sorting controls.
 *
 * @param array $filters Validated guide filter values.
 * @param int $limit Valid maximum number of rows to display.
 * @param string $sort Validated sort key.
 * @param string $direction Validated sort direction.
 * @param array $sort_options Labels for the allowed sort keys.
 */
function render_guide_search(
    array $filters,
    int $limit,
    string $sort,
    string $direction,
    array $sort_options
): void {
    require(__DIR__ . "/../partials/guide_search.php");
}

// lib/render_functions.php
// Keep every existing helper, including the updated render_guide_search(), above.
// Append these three helpers immediately before the closing PHP tag.

/** Renders the current row count compared with all filtered matches. */
function render_result_summary(int $shown_count, int $matching_count): void
{
    require(__DIR__ . "/../partials/result_summary.php");
}

/**
 * Renders one StarCraft guide as either a list card or detail card.
 *
 * @param array $guide Guide data selected from the database.
 * @param array $options Supports show_detail_view and show_saved_on.
 */
function render_guide_card(array $guide, array $options = []): void
{
    require(__DIR__ . "/../partials/guide_card.php");
}

/**
 * Renders the reference project's StarCraft guide-card grid.
 *
 * @param array $guides Guide rows to display.
 * @param array $card_options Options passed to every guide card.
 * @param string $empty_message Message displayed when no guides match.
 */
function render_grid(
    array $guides,
    array $card_options = [],
    string $empty_message = "No guides matched the selected filters."
): void {
    require(__DIR__ . "/../partials/guide_grid.php");
}

// Keep the existing closing PHP tag below this section.

function render_movie_search(
    array $filters,
    int $limit,
    string $sort,
    string $direction,
    array $sort_options
): void
{
    require(__DIR__ . "/../partials/movie_search.php");
}

/**
 * Renders one movie entry as either a list card or detail card.
 *
 * @param array $movie Movie data selected from the database.
 * @param array $options Supports show_detail_view and show_saved_on.
 */
function render_movie_card(array $movie, array $options = []): void
{
    require(__DIR__ . "/../partials/movie_card.php");
}

/**
 * Renders project's movie-card grid.
 *
 * @param array $movies Movie rows to display.
 * @param array $card_options Options passed to every guide card.
 * @param string $empty_message Message displayed when no guides match.
 */
function render_movie_grid(
    array $movies,
    array $card_options = [],
    string $empty_message = "No movies matched the selected filters."
): void {
    require(__DIR__ . "/../partials/movie_grid.php");
}

// lib/render_functions.php
// Append this helper before the existing closing PHP tag.

/**
 * Renders page controls while preserving the active list values.
 *
 * @param int $page Current page number.
 * @param int $total_pages Number of available pages.
 * @param array $query_params Active filters, sort choices, and limit.
 */
function render_pagination(
    int $page,
    int $total_pages,
    array $query_params = []
): void {
    require(__DIR__ . "/../partials/pagination.php");
}
?>