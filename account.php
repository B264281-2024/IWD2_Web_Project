<?php
session_start();
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user search history from MySQL
$stmt = $pdo->prepare("SELECT protein_family, taxonomic_group, query_date, fasta_header FROM search_queries WHERE user_id = :user_id ORDER BY query_date DESC");
$stmt->execute(['user_id' => $user_id]);
$queries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Account</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <!-- Navigation Bar -->
  <nav class="navbar">
    <div class="navbar__container">
      <a href="index.html" id="navbar__logo">User-Defined Protein Search</a>
      <ul class="navbar__menu">
        <li class="navbar__item"><a href="example.html" class="navbar__links">Example Data</a></li>
        <li class="navbar__item"><a href="help.html" class="navbar__links">Help</a></li>
        <li class="navbar__item"><a href="credits.html" class="navbar__links">Statement of Credits</a></li>
        <li class="navbar__btn"><a href="account.php" class="button">Account</a></li>
      </ul>
    </div>
  </nav>

  <!-- table of all fasta sequences searched for by this user -->
  <div class="content">
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['forename'] . ' ' . $_SESSION['surname']); ?>!</h2><br>
    
    <h3>Your Previous Queries:</h3><br>

    <?php if (count($queries) > 0): ?>
      <table>
        <thead>
          <tr>
            <th>Protein Family</th>
            <th>Taxonomic Group</th>
            <th>Query Date</th>
            <th>FASTA Header</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($queries as $query): ?>
            <tr>
              <td><?php echo htmlspecialchars($query['protein_family']); ?></td>
              <td><?php echo htmlspecialchars($query['taxonomic_group']); ?></td>
              <td><?php echo htmlspecialchars($query['query_date']); ?></td>
              <td><?php echo htmlspecialchars($query['fasta_header']); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>You have not submitted any queries yet.</p>
    <?php endif; ?>
  </div>
</body>
</html>
