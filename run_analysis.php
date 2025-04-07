<?php
header('Content-Type: application/json');
session_start();

require_once 'config.php';

try {
    // Ensure user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in.');
    }

    // Step 1: Retrieve FASTA sequences from the database
    $stmt = $pdo->prepare("SELECT fasta_header, fasta_sequence FROM search_queries WHERE user_id = :user_id ORDER BY id DESC LIMIT 10");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $sequences = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($sequences)) {
        throw new Exception('No FASTA sequences found for analysis.');
    }

    // Step 2: Write sequences to a temporary FASTA file
    $inputFile = '/tmp/input_sequences.fasta';
    $fileHandle = fopen($inputFile, 'w');
    
    foreach ($sequences as $sequence) {
        fwrite($fileHandle, ">" . trim($sequence['fasta_header']) . "\n" . wordwrap(trim($sequence['fasta_sequence']), 80, "\n", true) . "\n");
    }
    
    fclose($fileHandle);

    // Step 3: Define output file paths for Clustal Omega
    $alignmentFile = '/tmp/aligned_sequences.aln';
    $clustalCommand = "clustalo -i {$inputFile} -o {$alignmentFile} --force --outfmt=clustal";

    // Step 4: Generate alignment file
    $alignmentFile = '/tmp/aligned_sequences.aln';
    $clustalCommand = "clustalo -i {$inputFile} -o {$alignmentFile} --force --outfmt=clustal";
    exec($clustalCommand, $outputClustal, $returnClustal);

    if ($returnClustal !== 0) {
        throw new Exception('Failed to run Clustal Omega alignment.');
    }

    // Step 5: Run plotcon analysis and capture output
    $outputFile = '/images/plotcon_' . uniqid() . '.png';
    $plotconWindowSize = 4;
    $plotconCommand = "plotcon -sequence {$alignmentFile} -winsize {$plotconWindowSize} -graph png -goutfile \"{$outputFile}\" 2>&1"; // 2>&1 captures error output
    exec($plotconCommand, $plotconOutput, $plotconReturn);
    
    if ($plotconReturn !== 0) {
        throw new Exception('Plotcon analysis failed: ' . implode("\n", $plotconOutput));
    }

    // Step 6: Return both alignment and plotcon results
    echo json_encode([
        'success' => true,
        'alignment' => file_get_contents($alignmentFile),
        'plotcon_image' => basename($outputFile, ".1.png")
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
