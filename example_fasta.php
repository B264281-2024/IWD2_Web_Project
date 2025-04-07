<?php
require 'config.php'; //establish PDO connection

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    //call all data from example_data table
    $stmt = $pdo->query("SELECT header, sequence FROM example_data");
    $fastaData = "";

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $fastaData .= ">" . htmlspecialchars($row['header']) . "\n" . htmlspecialchars($row['sequence']) . "\n";
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FASTA Data</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>FASTA Data</h2>
    <pre id="fasta-output"><?php echo nl2br($fastaData); ?></pre>
</body>
</html>
