<?php
// UCID: kvv, Date: 7/17/26
// lib/project_api.php
// lib/app.php imports api_helper before this wrapper.

/**
 * Converts Alpha Vantage JSON keys and values into a cleaner project-friendly shape.
 *
 * @param array $api_record One entity from a Streaming Availability API response.
 * @return array Record with normalized field names and numeric values.
 */
function transform_streaming_availability_record(array $api_record): array
{
    $transformed_record = [];

    foreach ($api_record as $json_key => $value) {
        $field_name = $json_key;
        $field_name = str_replace(" ", "_", $field_name);

        if (is_string($value)) {
            $value = str_replace("%", "", $value);
        }
        if (is_numeric($value)) {
            if (strpos((string)$value, ".") !== false) {
                $value = (float)$value;
            } else {
                $value = (int)$value;
            }
        }

        $transformed_record[$field_name] = $value;
    }

    return $transformed_record;
}

function search_shows(string $title, string $show_type, array &$errors): array
{
    $result = null;
    if ($show_type === "both") {
        $result = api_get(
            "https://streaming-availability.p.rapidapi.com/shows/search/title",
            ["country" => "US", "title" => $title],
            ["key_name" => "PROJECT_API_KEY", "host_name" => "PROJECT_API_HOST",
            "key_header" => "x-rapidapi-key", "host_header" => "x-rapidapi-host"]
        );
    } else if ($show_type === "show") {
        $result = api_get(
            "https://streaming-availability.p.rapidapi.com/shows/search/title",
            ["country" => "US", "show_type" => "series", "title" => $title],
            ["key_name" => "PROJECT_API_KEY", "host_name" => "PROJECT_API_HOST",
            "key_header" => "x-rapidapi-key", "host_header" => "x-rapidapi-host"]
        );
    }
    else {
        $result = api_get(
            "https://streaming-availability.p.rapidapi.com/shows/search/title",
            ["country" => "US", "show_type" => "movie", "title" => $title],
            ["key_name" => "PROJECT_API_KEY", "host_name" => "PROJECT_API_HOST",
            "key_header" => "x-rapidapi-key", "host_header" => "x-rapidapi-host"]
        );
    }

    $json_key = null;
    $decoded = decode_api_response($result, $json_key, $errors);
    if ($decoded == null) {
        return [];
    }
    $matches_json = $decoded;

    $shows = [];
    foreach ($matches_json as $match_json) {
        if (!is_array($match_json)) {
            continue;
        }

        $show = transform_streaming_availability_record($match_json);
        if (empty($show["title"]) || empty($show["id"])) {
            continue;
        }

        $streamingOptions = $show["streamingOptions"];

        $shows[] = [
            "title" => $show["title"],
            "overview" => $show["overview"] ?? "",
            "show_type" => $show["showType"] ?? "",
            "genres" => $show["genres"] ?? [],
            "creators" => $show["creators"] ?? [],
            "cast" => $show["cast"] ?? [],
            "images" => $show["imageSet"] ?? [],
            "services" => $streamingOptions["us"] ?? [],
            "is_api" => 1,
            "api_id" => $show["id"]
        ];
    }

    return $shows;
}