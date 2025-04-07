<?php
header('Content-Type: application/json');
session_start();

require_once 'config.php';

try {
    // Ensure user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User ID is not set in the session.');
    }

    // Connect to the database and fetch sequences for the logged-in user
    $stmt = $pdo->prepare("SELECT fasta_header, fasta_sequence FROM search_queries WHERE user_id = :user_id ORDER BY id DESC LIMIT 20");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $sequences = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($sequences)) {
        throw new Exception('No FASTA sequences found for the current user.');
    }

    // Return sequences as JSON
    echo json_encode([
        'success' => true,
        'sequences' => $sequences
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
