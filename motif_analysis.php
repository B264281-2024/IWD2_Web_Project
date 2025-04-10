<?php
session_start();

// Ensure fasta data is provided
if (!isset($_POST['fasta_data']) || empty(trim($_POST['fasta_data']))) {
    die("No FASTA data provided.");
}

// Create a temp directory for this session
$session_id = session_id();
$temp_dir = __DIR__ . "/tmp/motif_$session_id";
if (!is_dir($temp_dir)) {
    mkdir($temp_dir, 0777, true);
}

// Save input to a fasta file
$fasta_input = trim($_POST['fasta_data']);
$fasta_file = "$temp_dir/input.fasta";
file_put_contents($fasta_file, $fasta_input);

// Split sequences
$sequences = preg_split('/^>/m', $fasta_input, -1, PREG_SPLIT_NO_EMPTY);
$csv_data = [["Sequence Name", "Motif", "Start", "End"]];

foreach ($sequences as $index => $seq_block) {
    $lines = explode("\n", $seq_block);
    $header = trim(array_shift($lines));
    $sequence = implode("", $lines);
    
    // Create a temporary fasa file for this sequence
    $temp_fasta = tempnam(sys_get_temp_dir(), 'seq_') . ".fasta";
    file_put_contents($temp_fasta, ">$header\n$sequence");

    // Create a temporary output file
    $temp_out = tempnam(sys_get_temp_dir(), 'motif_') . ".txt";

    // Run patmatmotifs for each individual fasta file
    $command = "patmatmotifs -sequence $temp_fasta -outfile $temp_out -auto > /dev/null 2>&1";
    exec($command, $output, $return_var);
    
    if ($return_var !== 0) {
        error_log("patmatmotifs failed on $temp_fasta with exit code $return_var.");
        continue;
    }

    // Parse results of each file
    if (file_exists($temp_out)) {
        $lines = file($temp_out);
        $motif_data = ['header' => $header];

        $current_motif = null;
        $current_start = null;
        $current_end = null;

        foreach ($lines as $line) {
            // Extract start, end, and motif details
            if (preg_match('/^Start = position (\d+)/', $line, $start_match)) {
                $current_start = $start_match[1];
            }

            if (preg_match('/^End = position (\d+)/', $line, $end_match)) {
                $current_end = $end_match[1];
            }

            if (preg_match('/^Motif = (\S+)/', $line, $motif_match)) {
                $current_motif = $motif_match[1];

                // Once we have Motif, Start, End, save the entry
                if ($current_start && $current_end && $current_motif) {
                    $csv_data[] = [
                        $motif_data['header'],
                        $current_motif,
                        $current_start,
                        $current_end,
                    ];
                    // Reset the motif data for the next motif
                    $current_motif = null;
                    $current_start = null;
                    $current_end = null;
                }
            }
        }
    } else {
        error_log("Result file $temp_out not found.");
        continue;
    }

    // Clean up temporary files
    unlink($temp_fasta);
    unlink($temp_out);
}

// Save CSV file
$output_csv = "$temp_dir/motif_results.csv";
$fp = fopen($output_csv, 'w');
fputcsv($fp, ['Sequence Header', 'Motif', 'Start', 'End']);
foreach ($csv_data as $row) {
    fputcsv($fp, $row);
}
fclose($fp);

// HTML Output
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Motif Analysis Results</title>
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

    <!-- Table with all motif data -->
    <div class="container">
        <h1>Motif Analysis Results</h1>
        <p>Results from EMBOSS patmatmotifs:</p>

        <table class="styled-table">
            <thead>
                <tr>
                    <?php foreach ($csv_data[0] as $col): ?>
                        <th><?php echo htmlspecialchars($col); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php for ($i = 1; $i < count($csv_data); $i++): ?>
                    <tr>
                        <?php foreach ($csv_data[$i] as $val): ?>
                            <td><?php echo htmlspecialchars($val); ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <!-- Directly referencing the CSV file -->
        <p><a href="tmp/motif_<?php echo $session_id; ?>/motif_results.csv" download>Download CSV</a></p>
    </div>
</body>
</html>
