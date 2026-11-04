<?php
// partials/button.php
/**
 * Usage:
 * render_button([
 *     "text" => "Save",
 *     "type" => "submit",
 *     "variant" => "primary",
 *     "attributes" => ["name" => "action", "value" => "save"],
 * ]);
 */
$text = (string) ($button["text"] ?? "Submit");
$type = (string) ($button["type"] ?? "submit");
$variant = (string) ($button["variant"] ?? "primary");

if (!in_array($type, ["button", "submit", "reset"], true)) {
    $type = "button";
}

$allowed_variants = ["primary", "secondary", "success", "warning", "danger", "info", "light", "dark"];
if (!in_array($variant, $allowed_variants, true)) {
    $variant = "secondary";
}

$attributes = $button["attributes"] ?? [];
$attributes["type"] = $type;
$attributes["class"] = "btn btn-$variant " . ($attributes["class"] ?? "");
?>
<button <?php echo render_html_attributes($attributes); ?>>
    <?php echo htmlspecialchars($text); ?>
</button>