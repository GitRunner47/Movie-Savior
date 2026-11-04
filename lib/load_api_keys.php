<?php
// lib/load_api_keys.php
// String array of environment keys to look up.

$env_keys = [ 
    "PROJECT_API_KEY",
    "PROJECT_API_HOST",
];
$ini_path = __DIR__ . "/.env";
$ini = [];

if (is_file($ini_path)) {
    $loaded_ini = parse_ini_file($ini_path, false, INI_SCANNER_RAW);

    if (is_array($loaded_ini)) {
        $ini = $loaded_ini;
    }
}

$API_KEYS = [];
foreach ($env_keys as $key) {
    $value = "";

    if (isset($ini[$key])) {
        // Load the local lib/.env value during development.
        $value = $ini[$key];
    } else {
        // Load the Render environment value for qa or prod.
        $environment_value = getenv($key);

        if ($environment_value !== false) {
            $value = $environment_value;
        }
    }

    $API_KEYS[$key] = $value;

    if ($value === "") {
        error_log("Failed to load API setting: $key");
    }
}