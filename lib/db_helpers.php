<?php
// lib/db_helpers.php

/**
 * Validates and quotes a trusted table or column name.
 */
function db_identifier(string $name): string
{
    if ($name === "" || !preg_match("/^[A-Za-z0-9_-]+$/", $name)) {
        throw new InvalidArgumentException("Invalid database identifier");
    }

    return "`$name`";
}

/**
 * Selects the PDO parameter type for one basic database value.
 */
function db_parameter_type($value): int
{
    if (is_int($value)) {
        return PDO::PARAM_INT;
    }
    if (is_bool($value)) {
        return PDO::PARAM_BOOL;
    }
    if (is_null($value)) {
        return PDO::PARAM_NULL;
    }

    // PDO has no float parameter type, so floats and strings use PARAM_STR.
    return PDO::PARAM_STR;
}

/**
 * Confirms that one row is associative and contains only basic values.
 */
function validate_db_row(array $row): void
{
    if (!$row || array_is_list($row)) {
        throw new InvalidArgumentException("Each database row must be an associative array");
    }

    foreach ($row as $column => $value) {
        if (!is_string($column)) {
            throw new InvalidArgumentException("Database row keys must be column-name strings");
        }

        db_identifier($column);

        // Map nested API data into columns or related-table rows before inserting.
        if (is_array($value) || is_object($value) || is_resource($value)) {
            throw new InvalidArgumentException("Database row values must use basic data types");
        }
    }
}

/**
 * Inserts one validated row or many identically shaped rows.
 *
 * @param string $table_name Trusted project table name.
 * @param array $data One associative row or a list of associative rows.
 * @param array $opts Optional update_duplicate, columns_to_update, and debug settings.
 * @return array Number of affected rows and the last inserted id.
 */
// Parameters: trusted table name, one row or a list of rows, and optional insert settings.
function insert(string $table_name, array $data, array $opts = []): array
{
    db_identifier($table_name);
    if (!$data) {
        throw new InvalidArgumentException("Insert requires data");
    }

    // Normalize a single associative row into the same list shape as a bulk insert.
    $rows = array_is_list($data) ? $data : [$data];
    if (!$rows) {
        throw new InvalidArgumentException("Insert requires one row or a list of rows");
    }

    foreach ($rows as $row) {
        if (!is_array($row)) {
            throw new InvalidArgumentException("Every insert item must be an associative array");
        }
        validate_db_row($row);
    }

    $columns = array_keys($rows[0]);
    $sorted_columns = $columns;
    sort($sorted_columns);

    // Every bulk row must contain the same columns, but their array order may differ.
    foreach ($rows as $row) {
        $row_columns = array_keys($row);
        sort($row_columns);
        if ($row_columns !== $sorted_columns) {
            throw new InvalidArgumentException("Every insert row must use the same columns");
        }
    }

    // Quote each trusted column name before adding it to generated SQL.
    $quoted_columns = [];
    foreach ($columns as $column) {
        $quoted_columns[] = db_identifier($column);
    }
    $column_sql = implode(", ", $quoted_columns);

    $value_groups = [];
    $params = [];
    // Build one uniquely named placeholder group for each row.
    foreach ($rows as $index => $row) {
        $placeholders = [];
        foreach ($columns as $column_index => $column) {
            $placeholder = ":value_" . $index . "_" . $column_index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $row[$column];
        }
        $value_groups[] = "(" . implode(", ", $placeholders) . ")";
    }

    $sql = "INSERT INTO " . db_identifier($table_name)
        . " ($column_sql) VALUES " . implode(", ", $value_groups);

    if (!empty($opts["update_duplicate"])) {
        $update_columns = $opts["columns_to_update"] ?? [];
        if ($update_columns === []) {
            $update_columns = $columns;
        }
        if (!is_array($update_columns) || !array_is_list($update_columns)) {
            throw new InvalidArgumentException("Duplicate updates require a column list");
        }
        foreach ($update_columns as $column) {
            if (!is_string($column) || $column === "") {
                throw new InvalidArgumentException("Duplicate update columns must be non-empty strings");
            }
        }

        // An upsert may update only columns that were included in the INSERT.
        if (array_diff($update_columns, $columns)) {
            throw new InvalidArgumentException("Duplicate update columns must be inserted columns");
        }

        $updates = [];
        foreach ($update_columns as $column) {
            $updates[] = db_identifier($column) . " = VALUES(" . db_identifier($column) . ")";
        }
        $sql .= " ON DUPLICATE KEY UPDATE " . implode(", ", $updates);
    }

    if (!empty($opts["debug"])) {
        error_log("Generated INSERT SQL: " . $sql);
        error_log("INSERT parameter names: " . implode(", ", array_keys($params)));
    }

    $db = getDB();
    $stmt = $db->prepare($sql);
    foreach ($params as $placeholder => $value) {
        $stmt->bindValue($placeholder, $value, db_parameter_type($value));
    }
    $stmt->execute();

    return ["rowCount" => $stmt->rowCount(), "lastInsertId" => $db->lastInsertId()];
}

// Add this function below insert() in lib/db_helpers.php.
/**
 * Updates allowlisted fields using one or more values as the WHERE condition.
 *
 * @param string $table_name Trusted project table name.
 * @param array $data Allowlisted fields plus the values used by the WHERE condition.
 * @param array $where_keys Array keys from $data used to build the WHERE condition.
 * @param array $opts Optional debug setting for local troubleshooting.
 * @return array Number of affected rows.
 */
// Parameters: trusted table name, SET/WHERE data, WHERE array keys, and optional settings.
function update(string $table_name, array $data, array $where_keys = ["id"], array $opts = []): array
{
    db_identifier($table_name);
    validate_db_row($data);

    if (!$where_keys || !array_is_list($where_keys)) {
        throw new InvalidArgumentException("Update requires a list of WHERE array keys");
    }
    // Confirm every requested WHERE array key is valid and exists before building SQL.
    foreach ($where_keys as $key) {
        if (!is_string($key) || $key === "") {
            throw new InvalidArgumentException("WHERE array keys must be non-empty strings");
        }
        if (!array_key_exists($key, $data)) {
            throw new InvalidArgumentException("Missing WHERE array key: " . $key);
        }
    }
    if (count(array_unique($where_keys)) !== count($where_keys)) {
        throw new InvalidArgumentException("WHERE array keys cannot contain duplicates");
    }

    $set_columns = array_values(array_diff(array_keys($data), $where_keys));
    // Prevent an UPDATE that has no fields after removing the WHERE keys.
    if (!$set_columns) {
        throw new InvalidArgumentException("No columns left to update");
    }

    $sets = [];
    $params = [];
    // Use separate placeholders for the fields being changed.
    foreach ($set_columns as $index => $column) {
        $placeholder = ":set_" . $index;
        $sets[] = db_identifier($column) . " = " . $placeholder;
        $params[$placeholder] = $data[$column];
    }

    $wheres = [];
    // Use separate placeholders for the values that select the record.
    foreach ($where_keys as $index => $column) {
        $placeholder = ":where_" . $index;
        $wheres[] = db_identifier($column) . " = " . $placeholder;
        $params[$placeholder] = $data[$column];
    }

    $sql = "UPDATE " . db_identifier($table_name)
        . " SET " . implode(", ", $sets)
        . " WHERE " . implode(" AND ", $wheres);

    if (!empty($opts["debug"])) {
        error_log("Generated UPDATE SQL: " . $sql);
        error_log("UPDATE parameter names: " . implode(", ", array_keys($params)));
    }

    $db = getDB();
    $stmt = $db->prepare($sql);
    foreach ($params as $placeholder => $value) {
        $stmt->bindValue($placeholder, $value, db_parameter_type($value));
    }
    $stmt->execute();

    return ["rowCount" => $stmt->rowCount()];
}

// Add these functions below update() in lib/db_helpers.php.
/**
 * Runs a prepared SELECT query and returns every row as an associative array.
 *
 * @param string $query Complete SELECT query written by the project page.
 * @param array $params Named or positional values to bind.
 * @param array $opts Optional debug setting for local troubleshooting.
 * @return array List of matching associative rows.
 */
// Parameters: complete query, values to bind, and optional settings.
function selectAll(string $query, array $params = [], array $opts = []): array
{
    if (trim($query) === "") {
        throw new InvalidArgumentException("Select requires a query");
    }

    if (!empty($opts["debug"])) {
        error_log("SELECT SQL: " . $query);
    }

    $db = getDB();
    $stmt = $db->prepare($query);
    $parameter_names = [];

    if (array_is_list($params)) {
        // Positional placeholders use 1-based positions with PDO.
        foreach ($params as $index => $value) {
            $position = $index + 1;
            $stmt->bindValue($position, $value, db_parameter_type($value));
            $parameter_names[] = "position $position";
        }
    } else {
        // Named parameters may be passed with or without the leading colon.
        foreach ($params as $name => $value) {
            if (!is_string($name) || $name === "") {
                throw new InvalidArgumentException("Named SELECT parameters must use string keys");
            }

            $placeholder = $name;
            if (!str_starts_with($placeholder, ":")) {
                $placeholder = ":" . $placeholder;
            }

            $stmt->bindValue($placeholder, $value, db_parameter_type($value));
            $parameter_names[] = $placeholder;
        }
    }

    if (!empty($opts["debug"])) {
        $names = "(none)";
        if ($parameter_names) {
            $names = implode(", ", $parameter_names);
        }
        error_log("SELECT parameter names: " . $names);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Runs a prepared SELECT query and returns its first associative row.
 *
 * @param string $query Complete SELECT query containing LIMIT 1.
 * @param array $params Named or positional values to bind.
 * @param array $opts Optional debug setting for local troubleshooting.
 * @return array|null First matching row, or null when no row matches.
 */
// Parameters: complete query, values to bind, and optional settings.
function select(string $query, array $params = [], array $opts = []): ?array
{
    // Keep single-row queries efficient and reserve multi-row queries for selectAll().
    if (!preg_match("/\\bLIMIT\\s+1\\b/i", $query)) {
        throw new InvalidArgumentException(
            "select() queries must include LIMIT 1; use selectAll() for multiple rows"
        );
    }

    $rows = selectAll($query, $params, $opts);
    if (!$rows) {
        return null;
    }

    return $rows[0];
}

// lib/db_helpers.php
// Keep the existing connection, CRUD, and select helpers above this section.

/**
 * Validates and quotes a column with an optional table or alias prefix.
 */
function db_qualified_identifier(string $column, string $prefix = ""): string
{
    $quoted_column = db_identifier($column);
    if ($prefix === "") {
        return $quoted_column;
    }

    $table_or_alias = rtrim($prefix, ".");
    return db_identifier($table_or_alias) . "." . $quoted_column;
}

/**
 * Validates a requested sort and returns trusted SQL for ORDER BY.
 *
 * @param string $requested_sort Submitted sort key.
 * @param string $requested_direction Submitted asc or desc direction.
 * @param array $sort_columns Trusted SQL columns in one of two formats:
 * ["title", "modified"] when the request keys match the SQL columns, or
 * ["saved" => "ug.modified"] when a request key needs a column override.
 * @return array Validated sort key, direction, and SQL fragment.
 */
function build_sort_query(
    string $requested_sort,
    string $requested_direction,
    array $sort_columns
): array {
    // A simple list uses each trusted column as both the request key and SQL.
    $sort_column_map = $sort_columns;
    if (array_is_list($sort_columns)) {
        $sort_column_map = [];
        foreach ($sort_columns as $column) {
            $sort_column_map[$column] = $column;
        }
    }

    if (!array_key_exists("modified", $sort_column_map)) {
        throw new InvalidArgumentException(
            "Sortable columns must include modified"
        );
    }

    $sort = $requested_sort;
    if (!array_key_exists($sort, $sort_column_map)) {
        $sort = "modified";
    }

    $direction = strtolower($requested_direction);
    if (!in_array($direction, ["asc", "desc"], true)) {
        $direction = "desc";
    }

    return [
        "sort" => $sort,
        "direction" => $direction,
        "sql" => $sort_column_map[$sort] . " " . strtoupper($direction),
    ];
}

/**
 * Reads and validates the filters, sorting, and limit for any list page.
 *
 * Filter names in a regular array accept any string. A key mapped to an
 * array accepts only those values. Empty filter values are always allowed.
 *
 * @param array $query Query-string values, normally $_GET.
 * @param array $config Trusted filters and sortable columns.
 * @return array Validated filters, sort state, ORDER BY SQL, and limit.
 */
function build_list_query_state(array $query, array $config): array
{
    $filters = [];
    foreach (($config["filters"] ?? []) as $name => $rule) {
        if (is_int($name)) {
            // A list entry, such as "title", accepts any string value.
            $name = $rule;
            $allowed_values = [];
        } else {
            // An associative entry accepts only the values listed in its rule.
            $allowed_values = $rule;
        }
        if (!is_string($name) || $name === "" || !is_array($allowed_values)) {
            throw new InvalidArgumentException("Invalid list filter configuration");
        }

        $value = "";
        if (isset($query[$name]) && is_string($query[$name])) {
            $value = trim($query[$name]);
        }
        if (
            $value !== ""
            && $allowed_values !== []
            && !in_array($value, $allowed_values, true)
        ) {
            $value = "";
        }
        $filters[$name] = $value;
    }

    $sort_columns = $config["sort_columns"] ?? [];
    $requested_sort = "modified";
    if (isset($query["sort"]) && is_string($query["sort"])) {
        $requested_sort = $query["sort"];
    }
    $requested_direction = "desc";
    if (isset($query["direction"]) && is_string($query["direction"])) {
        $requested_direction = $query["direction"];
    }
    $sort_state = build_sort_query(
        $requested_sort,
        $requested_direction,
        $sort_columns
    );

    $limit = 10;
    if (isset($query["limit"]) && is_string($query["limit"])) {
        $requested_limit = filter_var(
            $query["limit"],
            FILTER_VALIDATE_INT,
            ["options" => [
                "min_range" => 1,
                "max_range" => 100,
            ]]
        );
        if ($requested_limit !== false) {
            $limit = $requested_limit;
        }
    }

    return [
        "filters" => $filters,
        "sort" => $sort_state["sort"],
        "direction" => $sort_state["direction"],
        "order_by" => $sort_state["sql"],
        "limit" => $limit,
    ];
}

/** Returns the filters supported by the movie lists. */
function movie_filter_rules(): array
{
    return [
        "title",
        "show_type" => ["movie", "series", "both"]
    ];
}

/**
 * Builds movie conditions and PDO parameters.
 *
 * @param array $filters Validated movie filter values.
 * @param string $column_prefix Trusted table or alias prefix, including the
 * trailing period, such as "g.".
 * @return array SQL joined by AND and its named parameters.
 */
function build_movie_filter_query(
    array $filters,
    string $column_prefix = ""
): array {
    $conditions = [];
    $params = [];

    foreach (["title"] as $name) {
        if (!empty($filters[$name])) {
            $column = db_qualified_identifier($name, $column_prefix);
            $conditions[] = $column . " LIKE :" . $name;
            $params[$name] = "%" . $filters[$name] . "%";
        }
    }

    foreach (["show_type"] as $name) {
        if (!empty($filters[$name])) {
            $column = db_qualified_identifier($name, $column_prefix);
            if ($name === "show_type" && $filters[$name] === "both") {
                continue;
            }
            $conditions[] = "LOWER(" . $column . ") = :" . $name;
            $params[$name] = strtolower($filters[$name]);
        }
    }

    return [
        "sql" => implode(" AND ", $conditions),
        "params" => $params,
    ];
}