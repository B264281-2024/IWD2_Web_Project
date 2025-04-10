import tempfile
import subprocess
import os

input_fasta = "short_seqs.fasta"
combined_output_file = "example_motifs_combined.txt"

# Read the raw fasta and split on '>'
with open(input_fasta, "r") as f:
    content = f.read()

# Clean and split
entries = content.strip().split(">")
entries = [entry.strip() for entry in entries if entry.strip()]  # Remove empty

with open(combined_output_file, "w") as combined_out:
    combined_out.write("Motif scan results (patmatmotifs)\n\n")

    for i, entry in enumerate(entries):
        lines = entry.splitlines()
        header = lines[0]
        sequence = "".join(lines[1:]).replace(" ", "").replace("\n", "")

        # Write to temporary fasta file
        with tempfile.NamedTemporaryFile(mode="w", delete=False, suffix=".fasta") as temp_fasta:
            temp_fasta_path = temp_fasta.name
            temp_fasta.write(f">{header}\n{sequence}\n")

        # Temporary output file
        with tempfile.NamedTemporaryFile(mode="r", delete=False, suffix=".txt") as temp_out:
            temp_out_path = temp_out.name

        # Run patmatmotifs
        subprocess.run([
            "patmatmotifs",
            "-sequence", temp_fasta_path,
            "-outfile", temp_out_path,
            "-auto"
        ], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)

        # Append results
        with open(temp_out_path, "r") as result_file:
            combined_out.write(f"Results for sequence {header}\n")
            combined_out.write(result_file.read())
            combined_out.write("\n" + "="*60 + "\n\n")

        # Clean up
        os.remove(temp_fasta_path)
        os.remove(temp_out_path)
