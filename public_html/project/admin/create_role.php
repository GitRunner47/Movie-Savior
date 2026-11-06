<?php
require_once(__DIR__ . "/../../../lib/app.php");
require_role("Admin");

$errors = [];
$name = "";
$description = "";

if (isset($_POST["name"], $_POST["description"])) {
    require_csrf_token(project_url("admin/create_role.php")); // New line.

    // Keep the existing validation, INSERT, feedback, and redirect here.
    $name = trim($_POST["name"]);
    $description = trim($_POST["description"]);

    if (strlen($name) < 2 || strlen($name) > 30) {
        $errors[] = "Role name must be 2-30 characters.";
    }

    if (!preg_match('/^[A-Za-z][A-Za-z0-9 ._-]*$/', $name)) {
        $errors[] = "Role name must start with a letter and use only letters, numbers, spaces, periods, underscores, or hyphens.";
    }

    if (strlen($description) > 255) {
        $errors[] = "Description must be 255 characters or less.";
    }

    if (empty($errors)) {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                "INSERT INTO Roles (name, description)
                 VALUES (:name, :description)"
            );
            $stmt->execute([
                ":name" => $name,
                ":description" => $description,
            ]);

            flash("Role created.", "success");
            header("Location: " . project_url("admin/list_roles.php"));
            exit;
        } catch (PDOException $e) {
            $errorInfo = $e->errorInfo ?? [];

            if (($errorInfo[0] ?? "") === "23000" && ($errorInfo[1] ?? 0) === 1062) {
                $errors[] = "That role name already exists.";
            } else {
                error_log("Create role failed: " . $e->getMessage());
                $errors[] = "Could not create the role.";
            }
        }
    }

    flash_errors($errors);
}
?>
<!doctype html>
<html lang="en">
<head>
    <?php render_head("Create Role"); ?>
</head>
<body>
    <?php render_nav(); ?>
    <main class="container py-4">
        <h1>Create Role</h1>

        <form method="post">
            <?php
            render_csrf_input();

            render_input([
                "name" => "name",
                "label" => "Role Name",
                "value" => $name,
                "attributes" => [
                    "required" => true,
                    "minlength" => 2,
                    "maxlength" => 30,
                ],
            ]);
            render_input([
                "type" => "textarea",
                "name" => "description",
                "value" => $description,
                "attributes" => [
                    "maxlength" => 255,
                    "rows" => 3,
                ],
            ]);
            render_button(["text" => "Create Role"]);
            ?>
        </form>
    </main>
    <?php render_flash_messages(); ?>
    <?php render_scripts(); ?>
</body>
</html>