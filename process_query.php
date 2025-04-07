<?php
header('Content-Type: application/json');
session_start();

require_once 'config.php';

try {
    // Ensure user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in.');
    }

    // Get input data
    $data = json_decode(file_get_contents('php://input'), true);
    $taxonomicGroup = $data['taxonomic_group'] ?? '';
    $proteinFamily = $data['protein_family'] ?? '';

    if (empty($taxonomicGroup) || empty($proteinFamily)) {
        throw new Exception('Both taxonomic group and protein family are required.');
    }

    // Step 1: ESearch for taxid
    $esearchUrlTaxid = "https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?" .
                       "db=taxonomy&term={$taxonomicGroup}&retmode=json&api_key=e3b163e9a875fcd8a2f8410c2021dcc0a608";

    $esearchResponseTaxid = file_get_contents($esearchUrlTaxid);
    if ($esearchResponseTaxid === false) {
        throw new Exception('Failed to connect to NCBI API for taxonomy search.');
    }

    $esearchDataTaxid = json_decode($esearchResponseTaxid, true);
    $taxID = $esearchDataTaxid['esearchresult']['idlist'][0] ?? null;

    if (!$taxID) {
        throw new Exception("No taxonomy ID found for '{$taxonomicGroup}'.");
    }

    // Step 2: ESearch for protein IDs using taxID and proteinFamily
    $esearchUrlProtein = "https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?" .
                         "db=protein&term={$proteinFamily}+AND+txid{$taxID}[Organism]&retmode=json&retmax=100&api_key=e3b163e9a875fcd8a2f8410c2021dcc0a608";

    $esearchResponseProtein = file_get_contents($esearchUrlProtein);
    if ($esearchResponseProtein === false) {
        throw new Exception('Failed to connect to NCBI API for protein search.');
    }

    $esearchDataProtein = json_decode($esearchResponseProtein, true);
    $proteinIDs = $esearchDataProtein['esearchresult']['idlist'] ?? [];

    if (empty($proteinIDs)) {
        throw new Exception("No protein IDs found for '{$proteinFamily}' and taxonomy ID '{$taxID}'.");
    }

    // Step 3: EFetch FASTA sequences using protein IDs
    $ids = implode(",", $proteinIDs);
    $efetchUrlFasta = "https://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?" .
                      "db=protein&id={$ids}&rettype=fasta&retmode=text&retmax=100&api_key=e3b163e9a875fcd8a2f8410c2021dcc0a608";

    $fastaResponse = file_get_contents($efetchUrlFasta);
    if (!$fastaResponse) {
        throw new Exception('No FASTA data retrieved from NCBI.');
    }

    // Parse FASTA and save to DB
    $sequences = [];
    
    // Split FASTA entries by "\n>"
    $fastaEntries = explode("\n>", trim($fastaResponse));
    
    foreach ($fastaEntries as $entry) {
        // Separate header and sequence
        $lines = explode("\n", trim($entry));
        $header = str_replace('>', '', array_shift($lines)); // Remove leading ">"
        $sequence = implode('', array_map('trim', $lines)); // Concatenate multi-line sequences

        // Save to database
        try {
            $stmt = $pdo->prepare("INSERT INTO search_queries (user_id, protein_family, taxonomic_group, fasta_header, fasta_sequence) VALUES (:user_id, :protein_family, :taxonomic_group, :header, :sequence)");
            $stmt->execute([
                ':user_id' => $_SESSION['user_id'],
                ':protein_family' => $proteinFamily,
                ':taxonomic_group' => $taxID,
                ':header' => $header,
                ':sequence' => $sequence
            ]);

            // Collect data for immediate response
            $sequences[] = [
                'header' => $header,
                'sequence' => wordwrap($sequence, 80, "\n", true) // Format for display
            ];
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    // Return parsed sequences for direct display
    echo json_encode([
        'success' => true,
        'sequences' => $sequences
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
