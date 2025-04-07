document.addEventListener('DOMContentLoaded', () => {
    const resultsDiv = document.getElementById('results');
    const analysisResultsDiv = document.getElementById('analysisResults');

    /**
     * Fetch FASTA sequences from the database via PHP
     */
    async function fetchFastaSequences() {
        try {
            const response = await fetch('process_query.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    taxonomic_group: document.getElementById('taxonomic_group').value.trim(),
                    protein_family: document.getElementById('protein_family').value.trim()
                })
            });

            const fastaData = await response.json();

            if (fastaData.success && Array.isArray(fastaData.sequences) && fastaData.sequences.length > 0) {
                // Display FASTA sequences
                let formattedSequences = '';
                fastaData.sequences.forEach(sequence => {
                    formattedSequences += `<div style="font-weight:bold; margin-top:10px;">>${sequence.header}</div>`;
                    formattedSequences += `<div style="white-space:pre-wrap;">${sequence.sequence}</div>`;
                });

                resultsDiv.innerHTML = `
                    <h3>FASTA Sequences:</h3>
                    <div style="max-height:400px; overflow-y:auto; border:1px solid #ccc; padding:10px;">
                        ${formattedSequences}
                    </div>
                `;

                // Add "Analyse Sequences" button dynamically
                const analysisButton = document.createElement('button');
                analysisButton.id = 'analyseBtn';
                analysisButton.textContent = 'Run Sequence Alignment';
                analysisButton.style.marginTop = '20px';
                analysisButton.addEventListener('click', async () => {
                    await runClustalAnalysis();
                });

                resultsDiv.appendChild(analysisButton);
            } else {
                resultsDiv.innerHTML = `<p>${fastaData.error || "No sequences found."}</p>`;
            }
        } catch (error) {
            console.error('Error fetching FASTA sequences:', error);
            resultsDiv.innerHTML = `<p>An error occurred while fetching FASTA sequences.</p>`;
        }
    }

    /**
     * Run Clustal Omega alignment on retrieved FASTA sequences
     */
    async function runClustalAnalysis() {
        try {
            const response = await fetch('run_analysis.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });

            const analysisData = await response.json();

            if (analysisData.success) {
                // Format and display alignment results in a styled div
                const formattedAlignment = `
                    <h3>Alignment Results:</h3>
                    <div style="max-height:400px; overflow-y:auto; border:1px solid #ccc; padding:10px;">
                        <pre style="white-space:pre-wrap;">${analysisData.alignment}</pre>
                    </div>
                `;
                
                analysisResultsDiv.innerHTML = '<p>Running sequence alignment (this could take some time)...</p>';
                analysisResultsDiv.innerHTML = formattedAlignment;
            } else {
                console.error(analysisData.error || "Error during Clustal Omega analysis.");
                analysisResultsDiv.innerHTML = `<p>${analysisData.error || "An error occurred during the analysis."}</p>`;
            }
        } catch (error) {
            console.error('Error running Clustal Omega:', error);
            analysisResultsDiv.innerHTML = `<p>An error occurred while processing your request.</p>`;
        }
    }

    // Attach event listener to form submission
    document.getElementById('ncbiForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        const taxonomicGroupInput = document.getElementById('taxonomic_group');
        const proteinFamilyInput = document.getElementById('protein_family');

        if (!taxonomicGroupInput.value.trim() || !proteinFamilyInput.value.trim()) {
            resultsDiv.innerHTML = '<p>Please provide both Taxonomic Group and Protein Family.</p>';
            return;
        }

        // Fetch and display FASTA sequences
        resultsDiv.innerHTML = '<p>Fetching FASTA sequences...</p>';
        await fetchFastaSequences();
    });
});
