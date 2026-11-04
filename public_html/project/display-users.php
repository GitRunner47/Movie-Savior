<?php
require_once(__DIR__ . "/../../lib/db.php");
/*
$db = getDB();

$q = trim($_POST["email"] ?? "");

try {
    $stmt = $db->prepare(
        "SELECT id, email
        FROM Users
        WHERE email LIKE ?
        ORDER BY email
        LIMIT 10"
    );
    $params = [$q];
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $users = [];
    $pageError = "Database is unavailable right now.";
}
?>

<?php if (count($users) === 0): ?>
  <p>No users yet.</p>
<?php else: ?>
  <ul>
    <?php foreach ($users as $user): ?>
      <li>
        <!-- Escape database values before showing them in HTML. -->
        <?php echo htmlspecialchars($user["email"]); ?>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search emails</title>
</head>
<body>
    <h1>Search emails</h1>
    <form method="post">
        <p id="form-message"></p>

        <label for="email">Email</label>
        <input id="email" name="email" type="email"
            autocomplete="email"
            value="<?php echo htmlspecialchars($email); ?>">

        <button type="submit">Search</button>
    </form>
</body>
</html>*/