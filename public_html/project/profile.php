<?php
require_once(__DIR__ . "/../../lib/app.php");

$return_to = safe_project_return_url(
    $_GET["return_to"] ?? "",
    ["movies.php", "admin/movie_associations.php"],
    "movies.php"
);

$current_user_id = get_user_id();
$user_id = (int) ($_GET["id"] ?? $current_user_id);

if ($user_id <= 0) {
    if (!is_logged_in()) {
        flash("Please log in first.", "warning");
        header("Location: " . project_url("login.php"));
        exit;
    }

    flash("Choose a valid profile.", "warning");
    header("Location: " . $return_to);
    exit;
}

$is_me = is_logged_in() && $user_id === $current_user_id;
$is_edit = $is_me && isset($_GET["edit"]);

try {
    // This query only takes get values as parameters and only selects public
    // information to be displayed on the public profile. No private information
    // is leaked.
    $user = select(
        "SELECT u.id, u.username, u.created,
                COUNT(ug.id) AS saved_movie_count
         FROM Users u
         LEFT JOIN UserMovies ug ON ug.user_id = u.id
                                AND ug.is_active = 1
         WHERE u.id = :user_id
         GROUP BY u.id, u.username, u.created
         LIMIT 1",
        ["user_id" => $user_id]
    );

    if ($user === null) {
        if ($is_me) {
            unset($_SESSION["user"]);
            flash("Please log in again.", "warning");
            header("Location: " . project_url("login.php"));
            exit;
        }

        flash("Profile not found.", "warning");
        header("Location: " . $return_to);
        exit;
    }

    // Load only the private fields needed to render the owner's edit forms.
    $currentUser = null;
    if ($is_edit) {
        $currentUser = select(
            "SELECT id AS user_id, email, username
             FROM Users
             WHERE id = :user_id
             LIMIT 1",
            ["user_id" => $current_user_id]
        );
    }

    $recent_movies = [];
    if (!$is_edit) {
        $recent_movies = selectAll(
            "SELECT g.id, g.title, g.show_type, g.overview,
                    ug.modified AS saved_on,
                    EXISTS (
                        SELECT 1
                        FROM UserMovies viewer_ug
                        WHERE viewer_ug.movie_id = g.id
                          AND viewer_ug.user_id = :viewer_id
                          AND viewer_ug.is_active = 1
                    ) AS is_saved
             FROM UserMovies ug
             JOIN MOVIES g ON g.id = ug.movie_id
             WHERE ug.user_id = :user_id
               AND ug.is_active = 1
             ORDER BY ug.modified DESC, g.id ASC
             LIMIT 5",
            [
                "user_id" => $user_id,
                "viewer_id" => $current_user_id,
            ]
        );
    }
} catch (Throwable $e) {
    error_log("Profile lookup failed: " . $e->getMessage());
    flash("The profile could not be loaded.", "danger");
    header("Location: " . $return_to);
    exit;
}

$db = getDB();
$errors = [];

if (
    $is_edit
    && isset($_POST["action"], $_POST["username"], $_POST["email"])
    && $_POST["action"] === "details"
) {
    require_csrf_token(project_url("profile.php") . "?edit"); // New line.

    // Keep the existing details validation, UPDATE, and feedback here.
    $username = trim($_POST["username"]);
    $email = sanitize_email($_POST["email"]);

    validate_username($username, $errors);
    validate_email($email, $errors);

    if (empty($errors)) {
        try {
            $stmt = $db->prepare(
                "UPDATE Users
                 SET username = :username, email = :email
                 WHERE id = :user_id
                 LIMIT 1"
            );
            $stmt->execute([
                ":username" => $username,
                ":email" => $email,
                ":user_id" => $current_user_id,
            ]);

            $_SESSION["user"]["username"] = $username;
            $_SESSION["user"]["email"] = $email;
            flash("Profile updated.", "success");
            header("Location: " . project_url("profile.php"));
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === "23000") {
                duplicate_user_detail($e, $errors);
            } else {
                error_log("Profile update failed: " . $e->getMessage());
                $errors[] = "Profile update failed. Please try again.";
            }
        }
    }

    if (!empty($errors)) {
        flash_errors($errors);
        header("Location: " . project_url("profile.php") . "?edit");
        exit;
    }
}

if (
    $is_edit
    && isset($_POST["action"], $_POST["current_password"], $_POST["new_password"], $_POST["confirm_password"])
    && $_POST["action"] === "password"
) {
    require_csrf_token(project_url("profile.php") . "?edit"); // New line.

    // Keep the existing password checks, UPDATE, and feedback here.
    $currentPassword = $_POST["current_password"];
    $newPassword = $_POST["new_password"];
    $confirmPassword = $_POST["confirm_password"];

    $currentPasswordIsValid = validate_password($currentPassword, $errors);

    validate_password($newPassword, $errors);
    validate_passwords_match($newPassword, $confirmPassword, $errors);

    if ($currentPasswordIsValid) {
        try {
            $passwordUser = select(
                "SELECT password_hash
                 FROM Users
                 WHERE id = :user_id
                 LIMIT 1",
                ["user_id" => $current_user_id]
            );

            if (
                $passwordUser === null
                || !password_verify($currentPassword, $passwordUser["password_hash"])
            ) {
                $errors[] = "Current password is incorrect.";
            }
        } catch (Throwable $e) {
            error_log("Password verification failed: " . $e->getMessage());
            $errors[] = "Current password could not be verified.";
        }
    }

    if (empty($errors)) {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $db->prepare(
            "UPDATE Users SET password_hash = :hash WHERE id = :user_id LIMIT 1"
        );
        $stmt->execute([":hash" => $hash, ":user_id" => $current_user_id]);
        flash("Password updated.", "success");
        header("Location: " . project_url("profile.php"));
        exit;
    }

    if (!empty($errors)) {
        flash_errors($errors);
        header("Location: " . project_url("profile.php") . "?edit");
        exit;
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <?php render_head("Profile"); ?>
</head>

<body>
    <?php render_nav(); ?>
    <main class="container py-4">
        <h1>Profile</h1>

        <?php if ($is_me): ?>
            <a class="btn btn-secondary mb-3"
                href="<?php echo $is_edit ? "?" : "?edit"; ?>">
                <?php echo $is_edit ? "View Profile" : "Edit Profile"; ?>
            </a>
        <?php endif; ?>

        <?php if ($is_edit): ?>
            <form method="post" action="?edit" onsubmit="return validate(this);">
                <?php
                render_csrf_input(); // New line.
                render_input(["type" => "hidden", "name" => "action", "value" => "details"]);
                render_input([
                    "name" => "username",
                    "value" => $currentUser["username"],
                    "attributes" => [
                        "required" => true,
                        "minlength" => 3,
                        "maxlength" => 30,
                        "pattern" => "[a-z0-9_\\-]{3,30}",
                        "title" => "Must be lowercase alphanumeric and can use underscore or hyphens",
                        "autocomplete" => "username",
                    ],
                ]);
                render_input([
                    "type" => "email",
                    "name" => "email",
                    "value" => $currentUser["email"],
                    "attributes" => ["required" => true, "autocomplete" => "email"],
                ]);
                render_button(["text" => "Update Profile"]);
                ?>
            </form>

            <form method="post" action="?edit" onsubmit="return validate(this);">
                <?php
                render_csrf_input(); // New line.
                render_input(["type" => "hidden", "name" => "action", "value" => "password"]);
                render_input([
                    "type" => "password",
                    "name" => "current_password",
                    "label" => "Current Password",
                    "attributes" => [
                        "required" => true,
                        "minlength" => 8,
                        "autocomplete" => "current-password",
                    ],
                ]);
                render_input([
                    "type" => "password",
                    "name" => "new_password",
                    "label" => "New Password",
                    "attributes" => [
                        "required" => true,
                        "minlength" => 8,
                        "autocomplete" => "new-password",
                    ],
                ]);
                render_input([
                    "type" => "password",
                    "name" => "confirm_password",
                    "label" => "Confirm New Password",
                    "attributes" => [
                        "required" => true,
                        "minlength" => 8,
                        "autocomplete" => "new-password",
                    ],
                ]);
                render_button(["text" => "Change Password", "variant" => "warning"]);
                ?>
            </form>
        <?php else: ?>
            <h2><?php echo htmlspecialchars((string) $user["username"]); ?></h2>
            <dl class="row">
                <dt class="col-sm-3">Member Since</dt>
                <dd class="col-sm-9">
                    <?php echo htmlspecialchars(date("F j, Y", strtotime($user["created"]))); ?>
                </dd>
                <dt class="col-sm-3">Saved Movies</dt>
                <dd class="col-sm-9"><?php echo (int) $user["saved_movie_count"]; ?></dd>
            </dl>

            <h2>Recently Saved Movies and Shows</h2>
            <?php
            $card_options = ["show_saved_on" => true];
            $empty_message = "This user has not saved any movies or shows.";
            render_movie_grid($recent_movies, $card_options, $empty_message);
            ?>

            <?php if (!$is_me): ?>
                <a class="btn btn-secondary mt-3"
                    href="<?php echo htmlspecialchars($return_to); ?>">Back</a>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <?php if ($is_edit): ?>
        <script>
            function validate(form) {
                const errors = [];
                const action = form.elements["action"].value;

                if (action === "details") {
                    validate_username(form.username, errors);
                    validate_email(form.email, errors);
                } else if (action === "password") {
                    validate_password(form.current_password, errors);
                    validate_password(form.new_password, errors);
                    validate_passwords_match(form.new_password, form.confirm_password, errors);
                }

                return show_validation_errors(errors);
            }
        </script>
    <?php endif; ?>

    <?php render_flash_messages(); ?>
    <?php render_scripts(); ?>
</body>
</html>