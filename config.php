<?php
$db_hostname = '127.0.0.1';
$db_database = 's2760053_ICA';
$db_username = 's2760053';
$db_password = 'Paramount_666';

try {
    $pdo = new PDO("mysql:host=$db_hostname;dbname=$db_database", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>