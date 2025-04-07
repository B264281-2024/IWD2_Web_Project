#!/bin/bash

# Define query parameters
PROTEIN_FAMILY="glucose-6-phosphatase"
TAXON_GROUP="Aves"

# Search for matching protein sequences in NCBI
esearch -db protein -query "${PROTEIN_FAMILY} AND ${TAXON_GROUP}[Organism]" | \
efetch -format fasta > protein_sequences.fasta

echo "Protein sequences saved to protein_sequences.fasta"