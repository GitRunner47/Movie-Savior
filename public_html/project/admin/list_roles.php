<?php
require_once(__DIR__ . "/../../../lib/app.php");
require_role("Admin");

$search = trim($_GET["q"] ?? "");
$roles = [];

if (isset($_POST["role_id"])) {
    require_csrf_token(project_url("admin/list_roles.php"));

    $role_id = (int)$_POST["role_id"];

    if ($role_id !== 0) {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                "UPDATE Roles
                 SET is_active = !is_active
                 WHERE id = :id"
            );
            $stmt->execute([":id" => $role_id]);
            flash("Role status updated.", "success");
        } catch (PDOException $e) {
            error_log("Role status update failed: " . $e->getMessage());
            flash("Could not update the role status.", "danger");
        }
    }

    header("Location: " . project_url("admin/list_roles.php"));
    exit;
}

try {
    $params = [];
    $where = "";

    if ($search !== "") {
        $where = "WHERE name LIKE :search OR description LIKE :search";
        $params[":search"] = "%$search%";
    }

    $db = getDB();
    $stmt = $db->prepare(
        "SELECT id, name, description, is_active, created, modified
         FROM Roles
         $where
         ORDER BY modified DESC
         LIMIT 10"
    );
    $stmt->execute($params);
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Role list failed: " . $e->getMessage());
    flash("Could not load roles.", "danger");
}
?>
<!doctype html>
<html lang="en">
<head>
    <?php render_head("List Roles"); ?>
</head>
<body>
    <?php render_nav(); ?>
    <main class="container py-4">
        <h1>List Roles</h1>

        <form method="get">
            <?php
            render_input([
                "label" => "Search",
                "name" => "q",
                "value" => $search,
            ]);
            render_button(["text" => "Search"]);
            ?>
        </form>

        <?php
        foreach ($roles as &$role) {
            if ($role["is_active"]) {
                $role["status"] = "Active";
            } else {
                $role["status"] = "Inactive";
            }
        }

        $role_columns = [
            "name" => "Name",
            "description" => "Description",
            "status" => "Status",
        ];

        $role_actions = [[
            "label" => "Toggle Status",
            "url" => "admin/list_roles.php",
            "method" => "POST",
            "parameter" => "role_id",
            "variant" => "warning",
        ]];

        render_table(
            $roles,
            $role_columns,
            $role_actions
        );
        ?>
    </main>
    <?php render_flash_messages(); ?>
    <?php render_scripts(); ?>
</body>
</html>