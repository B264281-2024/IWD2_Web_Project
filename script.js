document.addEventListener('DOMContentLoaded', () => {
    const resultsDiv = document.getElementById('results');
    const analysisResultsDiv = document.getElementById('analysisResults');

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
                let formattedSequences = '';
                fastaData.sequences.forEach(sequence => {
                    formattedSequences += `<div class="fasta-header">>${sequence.header}</div>`;
                    formattedSequences += `<div class="fasta-sequence">${sequence.sequence}</div>`;
                });

                resultsDiv.innerHTML = `
                    <h3>FASTA Sequences:</h3>
                    <div class="sequence-container">
                        ${formattedSequences}
                    </div>
                `;

                const analysisButton = document.createElement('button');
                analysisButton.id = 'analyseBtn';
                analysisButton.textContent = 'Run Sequence Analysis';
                analysisButton.addEventListener('click', runClustalAnalysis);
                resultsDiv.appendChild(analysisButton);
            } else {
                resultsDiv.innerHTML = `<p>${fastaData.error || "No sequences found."}</p>`;
            }
        } catch (error) {
            console.error('Error:', error);
            resultsDiv.innerHTML = `<p>Error fetching sequences: ${error.message}</p>`;
        }
    }

    async function runClustalAnalysis() {
        try {
            analysisResultsDiv.innerHTML = '<p>Running analysis... <span class="loading-spinner"></span></p>';
            
            const response = await fetch('run_analysis.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });

            const analysisData = await response.json();

            if (analysisData.success) {
                // Display alignment results
                const alignmentHtml = `
                    <h3>Alignment Results:</h3>
                    <div class="result-container">
                        <pre>${analysisData.alignment}</pre>
                    </div>
                `;

                // Display Plotcon image with cache-busting
                const imageUrl = analysisData.plotcon_image.includes('://') 
                    ? analysisData.plotcon_image 
                    : `${window.location.origin}/${analysisData.plotcon_image.replace(/^\//, '')}`;
                
                const conservationHtml = `
                    <h3>Conservation Plot:</h3>
                    <div class="image-container">
                        <img src="${analysisData.plotcon_image}?t=${Date.now()}" 
                             alt="Conservation Plot" 
                             class="plot-image"
                             onerror="this.onerror=null;this.src='fallback.png'">
                    </div>
                `;

                analysisResultsDiv.innerHTML = alignmentHtml
                analysisResultsDiv.innerHTML = conservationHtml;
            } else {
                throw new Error(analysisData.error || "Analysis failed");
            }
        } catch (error) {
            console.error('Error:', error);
            analysisResultsDiv.innerHTML = `<p>Analysis error: ${error.message}</p>`;
        }
    }

    // Form submission handler
    document.getElementById('ncbiForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        resultsDiv.innerHTML = '<p>Fetching sequences...</p>';
        await fetchFastaSequences();
    });
});
