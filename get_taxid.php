<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$taxonomicGroup = $data['taxonomic_group'] ?? '';

if (!$taxonomicGroup) {
    echo json_encode(['success' => false, 'error' => 'Taxonomic group is required']);
    exit;
}

// Query NCBI Taxonomy database using ESearch
$esearchUrl = "https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?" .
              "db=taxonomy&term={$taxonomicGroup}&retmode=json&api_key=e3b163e9a875fcd8a2f8410c2021dcc0a608";

$esearchResponse = file_get_contents($esearchUrl);

if ($esearchResponse === false) {
    echo json_encode(['success' => false, 'error' => 'Failed to connect to NCBI API']);
    exit;
}

$esearchData = json_decode($esearchResponse, true);

if (isset($esearchData['esearchresult']['idlist'][0])) {
    $txid = $esearchData['esearchresult']['idlist'][0];
    echo json_encode(['success' => true, 'txid' => $txid]);
} else {
    echo json_encode(['success' => false, 'error' => "No taxonomy ID found for '{$taxonomicGroup}'"]);
}
?>
