<?php
/**
 * Builds a URL rooted at /project.
 *
 * @param string $path Path relative to public_html/project, or a root-relative web path.
 * @return string URL that can be used in href, action, or Location headers.
 */
function project_url(string $path = ""): string
{
    $path = trim($path);

    // If no path is passed, return the project folder itself.
    if ($path === "") {
        return "/project";
    }

    // If the path already starts at the web root, use it as-is.
    if ($path[0] === "/") {
        return $path;
    }

    return "/project/$path";
}

// lib/url_helpers.php
// Add these helpers below the existing project_url() function.

/**
 * Returns this page's local project URL, including its query string.
 */
function current_project_request_url(string $fallback_path): string
{
    $request_uri = $_SERVER["REQUEST_URI"] ?? "";
    $project_root = rtrim(project_url(), "/");

    if (
        is_string($request_uri)
        && $request_uri !== ""
        && !str_contains($request_uri, "\r")
        && !str_contains($request_uri, "\n")
        && (
            $request_uri === $project_root
            || str_starts_with($request_uri, $project_root . "/")
        )
    ) {
        return $request_uri;
    }

    return project_url($fallback_path);
}

/**
 * Accepts a return URL only when it matches an approved local project page.
 * An approved page may keep its query string.
 */
function safe_project_return_url(
    mixed $requested_return,
    array $allowed_paths,
    string $fallback_path
): string {
    $fallback_url = project_url($fallback_path);
    if (
        !is_string($requested_return)
        || $requested_return === ""
        || str_contains($requested_return, "\r")
        || str_contains($requested_return, "\n")
    ) {
        return $fallback_url;
    }

    foreach ($allowed_paths as $allowed_path) {
        $allowed_url = project_url((string) $allowed_path);
        if (
            $requested_return === $allowed_url
            || str_starts_with($requested_return, $allowed_url . "?")
        ) {
            return $requested_return;
        }
    }

    return $fallback_url;
}
?>