#!/bin/bash

#query for the example data
PROTEIN_FAMILY="glucose-6-phosphatase"
TAXON_GROUP="Aves"

#esearch and efetch to return all the data required for the example. This was only run once so that the example data was saved into the Website folder as protein_sequences.fasta
esearch -db protein -query "${PROTEIN_FAMILY} AND ${TAXON_GROUP}[Organism]" | \
efetch -format fasta > protein_sequences.fasta

echo "Protein sequences saved to protein_sequences.fasta"