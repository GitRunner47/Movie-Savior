<?php
// File: lib/duplicate_user_details.php
function duplicate_user_detail(PDOException $e, array &$errors): void
{
    $errorInfo = $e->errorInfo ?? [];

    if (($errorInfo[1] ?? null) !== 1062) {
        error_log("Unhandled user duplicate error: " . var_export($errorInfo, true));
        $errors[] = "Account details could not be saved. Please try again.";
        return;
    }

    $details = $errorInfo[2] ?? "";
    if (preg_match('/(?:Users\.)?(email|username)\b/i', $details, $matches)) {
        $errors[] = "The chosen " . $matches[1] . " is not available.";
        return;
    }

    error_log("Could not identify duplicate user field: " . var_export($errorInfo, true));
    $errors[] = "That email or username is already in use.";
}
?>