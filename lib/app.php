<?php
// lib/app.php
// Replace the original session_start(); at the top of this file with this block.
$is_https = !empty($_SERVER["HTTPS"])
    && $_SERVER["HTTPS"] !== "off";

// Cookie settings must be configured before the session begins.
session_set_cookie_params([
    "lifetime" => 0,
    "path" => "/project",
    "secure" => $is_https,
    "httponly" => true,
    "samesite" => "Lax",
]);
session_start();

// The existing import list continues here in its original order.
require_once(__DIR__ . "/db.php");
require_once(__DIR__ . "/db_helpers.php");
require_once(__DIR__ . "/pagination_helpers.php");

// Add this new import immediately before the existing render_functions import.
// render_csrf_input() will depend on csrf_token().
require_once(__DIR__ . "/csrf_helpers.php");
require_once(__DIR__ . "/render_functions.php");

// Keep the existing URL, validation, user, flash, role, and API imports below
// in their current order.

// url_helpers.php must load before helpers or partials that call project_url().
require_once(__DIR__ . "/url_helpers.php");
require_once(__DIR__ . "/validations.php");
// Keep user_helpers.php before role_helpers.php.
// has_role() depends on is_logged_in().
require_once(__DIR__ . "/user_helpers.php");
require_once(__DIR__ . "/flash_messages.php");
require_once(__DIR__ . "/duplicate_user_details.php");
// require_role() depends on flash() and project_url().
require_once(__DIR__ . "/role_helpers.php");
// api_helper.php loads load_api_keys.php before declaring API functions.
require_once(__DIR__ . "/api_helper.php");
require_once(__DIR__ . "/project_api.php");
?>