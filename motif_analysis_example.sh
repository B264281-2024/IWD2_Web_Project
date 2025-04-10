
INPUT_FASTA="protein_sequences.fasta"
OUTPUT_FILE="example_motifs_combined.txt"

# Empty the output file first
> "$OUTPUT_FILE"

# Temp variables
seq=""
header=""

while read -r line; do
    # Header lines
    if [[ "$line" =~ ^\> ]]; then
        # If we already have a sequence from the previous entry, process it
        if [[ -n "$seq" && -n "$header" ]]; then
            # Create temp fasta
            temp_fasta=$(mktemp /tmp/seqXXXX.fasta)
            echo "$header" > "$temp_fasta"
            echo "$seq" >> "$temp_fasta"

            # Create temp output
            temp_out=$(mktemp /tmp/motifXXXX.txt)

            # Run patmatmotifs
            patmatmotifs -sequence "$temp_fasta" -outfile "$temp_out" -auto > /dev/null 2>&1

            # Append results
            echo "Results for $header" >> "$OUTPUT_FILE"
            cat "$temp_out" >> "$OUTPUT_FILE"
            echo -e "\n============================================================\n" >> "$OUTPUT_FILE"

            # Clean up
            rm "$temp_fasta" "$temp_out"
        fi

        # Start new record
        header="$line"
        seq=""
    else
        # Concatenate sequence lines, strip whitespace
        seq+=$(echo "$line" | tr -d '[:space:]')
    fi
done < "$INPUT_FASTA"

# Process the last entry
if [[ -n "$seq" && -n "$header" ]]; then
    temp_fasta=$(mktemp /tmp/seqXXXX.fasta)
    echo "$header" > "$temp_fasta"
    echo "$seq" >> "$temp_fasta"

    temp_out=$(mktemp /tmp/motifXXXX.txt)
    patmatmotifs -sequence "$temp_fasta" -outfile "$temp_out" -auto > /dev/null 2>&1

    echo "Results for $header" >> "$OUTPUT_FILE"
    cat "$temp_out" >> "$OUTPUT_FILE"
    echo -e "\n============================================================\n" >> "$OUTPUT_FILE"

    rm "$temp_fasta" "$temp_out"
fi
