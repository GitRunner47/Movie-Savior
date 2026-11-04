<?php
require_once(__DIR__ . "/../../lib/app.php");

$errors = [];
$identifier = "";
$user = false;

if (isset($_POST["identifier"], $_POST["password"])) {
    require_csrf_token(project_url("login.php"));

    // Keep the existing validation, lookup, and password check here.
    $identifier = trim($_POST["identifier"]);
    $password = $_POST["password"];
    $isEmailLogin = str_contains($identifier, "@");

    if ($identifier === "") {
        $errors[] = "Enter your email or username.";
    } elseif ($isEmailLogin) {
        $identifier = sanitize_email($identifier);
        validate_email($identifier, $errors);
    } else {
        $identifier = strtolower($identifier);
        validate_username($identifier, $errors);
    }

    validate_password($password, $errors);

    if (empty($errors)) {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                "SELECT id AS user_id, email, username, password_hash
                FROM Users
                    WHERE username = :identifier
                    OR email = :identifier
                LIMIT 1"
            );
            $stmt->execute([":identifier" => $identifier]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Login query failed: " . $e->getMessage());
            $errors[] = "Login failed. Please try again.";
        }
    }

    if (
        empty($errors)
        && (!$user || !password_verify($password, $user["password_hash"]))
    ) {
        $errors[] = "Invalid email or password.";
    }

    if (empty($errors)) {
        session_regenerate_id(true);
        $user["user_id"] = (int) $user["user_id"];
        unset($user["password_hash"]);
        $user["roles"] = get_user_roles($user["user_id"]);
        $_SESSION["user"] = $user;
        csrf_rotate_token();

        // Add flash feedback before the existing redirect.
        flash("Welcome back.", "success");
        header("Location: dashboard.php");
        exit;
    }
    // Any validation, lookup, or password errors collected above show on the same form.
    flash_errors($errors);
    // Keep validation failures on this request so sticky form values remain.
    header("Location: login.php");
    exit;
}

$message = implode("<br>", array_map("htmlspecialchars", $errors));
?>
<!doctype html>
<html lang="en">

<head>
    <?php render_head("Login"); ?>
</head>

<body>
    <?php render_nav(); ?>
    <main class="container py-4">
        <h1 class=login>Login</h1>
        <p id="message"><?php echo $message; ?></p>
        <form method="post" action="login.php" onsubmit="return validate(this)">
            <?php
            render_csrf_input(); // New line immediately after the opening form tag.
            // Keep the existing identifier, password, and Login button below.
            
            render_input([
                "name" => "identifier",
                "label" => "Email or Username",
                "value" => $identifier,
                "attributes" => [
                    "class='col-6'",
                    "required" => true,
                    "autocomplete" => "username",
                    "pattern" => "(?:[a-z0-9_\\-]{3,30}|[^@\\s]+@[^@\\s]+\\.[^@\\s]+)",
                    "title" => "Enter a username or email address",
                ],
            ]);

            render_input([
                "type" => "password",
                "name" => "password",
                "label" => "Password",
                "attributes" => [
                    "class='col-6'",
                    "required" => true,
                    "minlength" => 8,
                    "autocomplete" => "current-password",
                ],
            ]);
            render_button(["text" => "Login"]);
            ?>
        </form>
    </main>
    <script>
        function validate(form) {
            const errors = [];
            const identifier = form.identifier.value.trim();
            // Keep this client-side shape aligned with the server-side email check.
            // PHP validation is still the final authority after the form posts.
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const usernamePattern = /^[a-z0-9_-]{3,30}$/;

            if (!identifier) {
                errors.push("Enter your email or username.");
            } else if (identifier.includes("@")) {
                if (!emailPattern.test(identifier)) {
                    errors.push("Enter a valid email address.");
                }
            } else if (!usernamePattern.test(identifier)) {
                errors.push("Use 3-30 lowercase letters, numbers, underscores, or hyphens.");
            }

            validate_password(form.password, errors);

            return show_validation_errors(errors);
        }
    </script>
    <!-- Last PHP inside <body> so it captures messages queued during this request. -->
    <?php render_flash_messages(); ?>
    <?php render_scripts(); ?>
</body>

</html>