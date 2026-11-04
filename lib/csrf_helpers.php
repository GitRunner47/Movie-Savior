<?php
// lib/csrf_helpers.php
// Usage:
// - Call csrf_token() when rendering a state-changing form.
// - Call require_csrf_token($redirect_url) before processing its POST data.

/** Returns the current session's CSRF token, creating it when needed. */
function csrf_token(): string
{
    $token = $_SESSION["csrf_token"] ?? null;
    if (!is_string($token) || $token === "") {
        $token = bin2hex(random_bytes(32));
        $_SESSION["csrf_token"] = $token;
    }

    return $token;
}

/** Stops an invalid POST and redirects with friendly feedback. */
function require_csrf_token(string $redirect_url): void
{
    $submitted_token = $_POST["csrf_token"] ?? null;
    $session_token = $_SESSION["csrf_token"] ?? null;
    if (
        is_string($submitted_token)
        && $submitted_token !== ""
        && is_string($session_token)
        && $session_token !== ""
        && hash_equals($session_token, $submitted_token)
    ) {
        return;
    }

    $script_name = $_SERVER["SCRIPT_NAME"] ?? "unknown script";
    error_log("CSRF validation failed for " . (string) $script_name);
    flash("The form expired or was invalid. Please try again.", "danger");
    header("Location: " . $redirect_url);
    exit;
}

/** Replaces the current token after an authentication boundary. */
function csrf_rotate_token(): void
{
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}