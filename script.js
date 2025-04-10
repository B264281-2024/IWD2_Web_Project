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
                
                let fastaText = '';
                fastaData.sequences.forEach(sequence => {
                  fastaText += `>${sequence.header}\n${sequence.sequence}\n`;
                });

                // Create the download button
                const downloadBtn = document.createElement('button');
                downloadBtn.textContent = 'Download FASTA';
                downloadBtn.classList.add('download-button'); // optional for styling

                // Create a blob from the FASTA content
                const blob = new Blob([fastaText], { type: 'text/plain' });
                const url = URL.createObjectURL(blob);

                // Create a download link and trigger it
                downloadBtn.addEventListener('click', () => {
                  const a = document.createElement('a');
                  a.href = url;
                  a.download = 'sequences.fasta';
                  document.body.appendChild(a);
                  a.click();
                  document.body.removeChild(a);
                });

                resultsDiv.appendChild(downloadBtn);


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

            // Debugging: Log the raw response text
            const responseText = await response.text();  // Get raw response text to inspect any errors
            console.log("Raw Response Text:", responseText);

            // Try parsing manually to handle any errors
            const analysisData = JSON.parse(responseText);

            // Check if response is valid
            if (analysisData.success) {
                // Display alignment results
                const alignmentHtml = `
                    <h3>Alignment Results:</h3>
                    <div class="result-container">
                        <pre>${analysisData.alignment}</pre>
                    </div>
                `;

                // Handle the plotcon image
                const imageUrl = `${window.location.origin}/${analysisData.plotcon_image}`;

                
                const conservationHtml = `
                    <h3>Conservation Plot:</h3>
                    <div class="image-container">
                        <img src="${imageUrl}?t=${Date.now()}" 
                             alt="Conservation Plot" 
                             class="plot-image"
                             onerror="this.onerror=null;this.src='fallback.png'">
                    </div>
                `;

                // Combine both alignment and plotcon result
                analysisResultsDiv.innerHTML = alignmentHtml + conservationHtml;
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
        resultsDiv.innerHTML = '<p>Fetching sequences... <span class="loading-spinner"></span></p>';
        await fetchFastaSequences();
    });
});
