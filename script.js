document.addEventListener('DOMContentLoaded', () => {
    const resultsDiv = document.getElementById('results');
    const analysisResultsDiv = document.getElementById('analysisResults');
    let fastaText = ''; // Declare globally to use in motif analysis

    // Function to fetch fasta sequences through process_query.php which has esearch and efetch functionality
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
                fastaText = ''; // Reset before rebuilding

                fastaData.sequences.forEach(sequence => {
                    formattedSequences += `<div class="fasta-header">>${sequence.header}</div>`;
                    formattedSequences += `<div class="fasta-sequence">${sequence.sequence}</div>`;
                    fastaText += `>${sequence.header}\n${sequence.sequence}\n`;
                });

                resultsDiv.innerHTML = `
                    <h3>FASTA Sequences:</h3>
                    <div class="sequence-container">
                        ${formattedSequences}
                    </div>
                `;

                // Download Button for downloading Fasta data
                const downloadBtn = document.createElement('button');
                downloadBtn.textContent = 'Download FASTA';
                downloadBtn.classList.add('download-button');

                const blob = new Blob([fastaText], { type: 'text/plain' });
                const url = URL.createObjectURL(blob);

                downloadBtn.addEventListener('click', () => {
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'sequences.fasta';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                });

                resultsDiv.appendChild(downloadBtn);

                // Clustal Analysis Button for alignments
                const analysisButton = document.createElement('button');
                analysisButton.id = 'analyseBtn';
                analysisButton.textContent = 'Run Sequence Analysis';
                analysisButton.addEventListener('click', runClustalAnalysis);
                resultsDiv.appendChild(analysisButton);

                // Motif Analysis Button for posting to motif_analysis.php
                const motifButton = document.createElement('button');
                motifButton.id = 'motifBtn';
                motifButton.textContent = 'Run Motif Analysis';
                motifButton.addEventListener('click', () => {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'motif_analysis.php';

                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'fasta_data';
                    input.value = fastaText;
                    form.appendChild(input);

                    document.body.appendChild(form);
                    form.submit();
                });
                resultsDiv.appendChild(motifButton);

            } else {
                resultsDiv.innerHTML = `<p>${fastaData.error || "No sequences found."}</p>`;
            }
        } catch (error) {
            console.error('Error:', error);
            resultsDiv.innerHTML = `<p>Error fetching sequences: ${error.message}</p>`;
        }
    }

    // Function to run alignment via run_analysis.php script and displaying data
    async function runClustalAnalysis() {
        try {
            analysisResultsDiv.innerHTML = '<p>Running analysis... <span class="loading-spinner"></span></p>';

            const response = await fetch('run_analysis.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });

            const responseText = await response.text();
            console.log("Raw Response Text:", responseText);
            const analysisData = JSON.parse(responseText);

            if (analysisData.success) {
                const alignmentHtml = `
                    <h3>Alignment Results:</h3>
                    <div class="result-container">
                        <pre>${analysisData.alignment}</pre>
                    </div>
                `;

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

                analysisResultsDiv.innerHTML = alignmentHtml + conservationHtml;
            } else {
                throw new Error(analysisData.error || "Analysis failed");
            }
        } catch (error) {
            console.error('Error:', error);
            analysisResultsDiv.innerHTML = `<p>Analysis error: ${error.message}</p>`;
        }
    }

    document.getElementById('ncbiForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        resultsDiv.innerHTML = '<p>Fetching sequences... <span class="loading-spinner"></span></p>';
        await fetchFastaSequences();
    });
});
