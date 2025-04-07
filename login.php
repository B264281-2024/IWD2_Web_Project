<?php
session_start();

// Include database configuration
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=$db_hostname;dbname=$db_database;charset=utf8mb4", $db_username, $db_password);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forename']) && isset($_POST['surname'])) {
    $_SESSION['forename'] = $_POST['forename'];
    $_SESSION['surname'] = $_POST['surname'];

    // Insert data into the database
    $stmt = $pdo->prepare("INSERT INTO users (forename, surname) VALUES (:forename, :surname)");
    $stmt->bindParam(':forename', $_SESSION['forename']);
    $stmt->bindParam(':surname', $_SESSION['surname']);
    $stmt->execute();

    // Retrieve the last inserted ID
    $userId = $pdo->lastInsertId();
    if ($userId) {
        $_SESSION['user_id'] = $userId; // Store user ID in session
    } else {
        die("Failed to retrieve user ID.");
    }

    header('Location: index.html');
    exit();
}
?>

<html lang="en">
<head>
  <meta charset="UTF-8">
	<meta name="viewport"
	      content="width=device-width, initial-scale=1.0">
	<title>Login Page</title>
	<link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="login-container", id="loginForm">
    <h2>Login</h2>
    <form action="" method="post" id="loginForm">
      <label for="firstName">First Name:</label>
      <input type="text" id="firstName" name="forename" required><br><br>
      <label for="surName">Surname:</label>
      <input type="text" id="surName" name="surname" required><br><br>
      
      <button type="submit" id="btn" name="login" value="Login">Submit</button>
    </form>
  </div>
</body>
</html>


