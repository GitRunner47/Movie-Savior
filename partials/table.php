<?php
// partials/table.php

/**
 * Usage:
 * $columns = ["username" => "Username", "email" => "Email"];
 * $actions = [
 *     [
 *         "label" => "View",
 *         "url" => "user.php",
 *         "row_key" => "id",
 *         "parameter" => "id",
 *         "method" => "GET",
 *         "variant" => "primary",
 *     ],
 *     [
 *         "label" => "Edit",
 *         "url" => "admin/edit_user.php",
 *         "row_key" => "id",
 *         "parameter" => "id",
 *         "method" => "GET",
 *         "variant" => "secondary",
 *     ],
 *     [
 *         "label" => "Delete",
 *         "url" => "admin/delete_user.php",
 *         "row_key" => "id",
 *         "parameter" => "id",
 *         "method" => "POST",
 *         "include_parameter_in_url" => true,
 *         "query_parameters" => ["return_to" => "admin/list_users.php"],
 *         "variant" => "danger",
 *     ],
 * ];
 *
 * render_table(
 *     $rows,
 *     $columns,
 *     $actions
 * );
 *
 * View and Edit render as links; Delete renders as a POST form.
 */
if (!isset($columns)) {
    error_log('Missing $columns definition');
    $columns = [];
}
if (!isset($empty_message)) {
    $empty_message = "No records to show";
}
if (!isset($rows)) {
    error_log('Missing $rows definition');
    $rows = [];
}

$column_count = count($columns);
if (!empty($actions)) {
    $column_count++;
}
?>
<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <?php foreach ($columns as $label): ?>
                    <th scope="col"><?php echo htmlspecialchars((string) $label); ?></th>
                <?php endforeach; ?>
                <?php if (!empty($actions)): ?>
                    <th scope="col">Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="<?php echo $column_count; ?>">
                        <?php echo htmlspecialchars($empty_message); ?>
                    </td>
                </tr>
            <?php endif; ?>

            <?php foreach ($rows as $row): ?>
                <tr>
                    <?php foreach ($columns as $key => $label): ?>
                        <td><?php echo htmlspecialchars((string) ($row[$key] ?? "")); ?></td>
                    <?php endforeach; ?>

                    <?php if (!empty($actions)): ?>
                        <td class="d-flex flex-wrap gap-2">
                            <?php foreach ($actions as $action): ?>
                                <?php
                                // Existing actions still default to one id parameter from the row's id field.
                                $row_parameters = $action["row_parameters"] ?? [
                                    (string) ($action["parameter"] ?? "id")
                                    => (string) ($action["row_key"] ?? "id"),
                                ];
                                if (!is_array($row_parameters) || empty($row_parameters)) {
                                    continue;
                                }

                                // A multi-column relationship action can map each request name to a row key.
                                $row_values = [];
                                foreach ($row_parameters as $parameter_name => $row_key) {
                                    if (
                                        !is_string($parameter_name)
                                        || $parameter_name === ""
                                        || !is_string($row_key)
                                        || !array_key_exists($row_key, $row)
                                    ) {
                                        $row_values = [];
                                        break;
                                    }
                                    $row_values[$parameter_name] = $row[$row_key];
                                }

                                if (empty($row_values) || !isset($action["url"])) {
                                    continue;
                                }

                                $method = strtoupper((string) ($action["method"] ?? "GET"));
                                $label = (string) ($action["label"] ?? "Open");
                                $variant = (string) ($action["variant"] ?? "secondary");
                                $include_parameter_in_url = (bool) (
                                    $action["include_parameter_in_url"] ?? false
                                );
                                $query_parameters = $action["query_parameters"] ?? [];
                                if (!is_array($query_parameters)) {
                                    $query_parameters = [];
                                }
                                $url = project_url((string) $action["url"]);
                                ?>

                                <?php if ($method === "POST"): ?>
                                    <?php
                                    if ($include_parameter_in_url) {
                                        $query_parameters = array_merge($query_parameters, $row_values);
                                    }
                                    $form_url = $url;
                                    if (!empty($query_parameters)) {
                                        $form_url .= "?" . http_build_query($query_parameters);
                                    }
                                    ?>
                                    <form method="post" action="<?php echo htmlspecialchars($form_url); ?>">
                                        <?php render_csrf_input(); // New line. ?>

                                        <?php if (!$include_parameter_in_url): ?>
                                            <?php foreach ($row_values as $parameter_name => $row_value): ?>
                                                <?php render_input([
                                                    "type" => "hidden",
                                                    "name" => $parameter_name,
                                                    "value" => $row_value,
                                                ]); ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        <?php render_button([
                                            "text" => $label,
                                            "variant" => $variant,
                                            "attributes" => ["class" => "btn-sm"],
                                        ]); ?>
                                    </form>
                                <?php else: ?>
                                    <?php
                                    $query_parameters = array_merge($query_parameters, $row_values);
                                    $href = $url . "?" . http_build_query($query_parameters);
                                    ?>
                                    <a class="btn btn-<?php echo htmlspecialchars($variant); ?> btn-sm"
                                        href="<?php echo htmlspecialchars($href); ?>">
                                        <?php echo htmlspecialchars($label); ?>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>