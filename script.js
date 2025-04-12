document.addEventListener('DOMContentLoaded', () => {
    // Form Elements
    const form = document.getElementById('code-form');
    const codeInput = document.getElementById('code-input');
    const submitButton = document.getElementById('submit-btn');
    const resultsOutput = document.getElementById('results-output');
    const loadingIndicator = document.getElementById('loading-indicator');
    const defaultResultsMessage = '<p>Results will slither here after analysis...</p>';

    // Dropdown Elements
    const dropdownBtn = document.getElementById('dropdown-btn');
    const dropdownMenu = document.getElementById('dropdown-menu');
    const dropdownCloseBtn = document.getElementById('dropdown-close-btn');

    // Initial State
    loadingIndicator.style.display = 'none';
    resultsOutput.innerHTML = defaultResultsMessage;
    dropdownMenu.classList.remove('active'); // Ensure menu is closed initially

    // --- Dropdown Logic ---
    function toggleDropdown() {
        dropdownMenu.classList.toggle('active');
    }

    if (dropdownBtn && dropdownMenu && dropdownCloseBtn) {
        dropdownBtn.addEventListener('click', (event) => {
            event.stopPropagation(); // Prevent click from closing menu immediately
            toggleDropdown();
        });

        dropdownCloseBtn.addEventListener('click', (event) => {
            event.preventDefault(); // Prevent potential navigation
            toggleDropdown();
        });

        // Close dropdown if clicking outside of it
        document.addEventListener('click', (event) => {
            if (dropdownMenu.classList.contains('active') && !dropdownBtn.contains(event.target) && !dropdownMenu.contains(event.target)) {
                toggleDropdown();
            }
        });
    } else {
        console.error("Dropdown elements not found!");
    }

    // --- Form Submission Logic ---
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        console.log("[JS] Submit event triggered.");

        const code = codeInput.value.trim();
        if (!code) {
            resultsOutput.innerHTML = '<p style="color: var(--red);">Please shed some Python (or other) code to analyze.</p>'; // Use theme color var
            console.log("[JS] Validation failed: No code.");
            return;
        }

        // Collapse Textarea
        codeInput.classList.add('collapsed');
        console.log("[JS] Textarea collapsed.");

        // Show Loader & Disable Button (Updated Text)
        console.log("[JS] Setting loading state...");
        resultsOutput.innerHTML = '';
        loadingIndicator.style.display = 'flex';
        submitButton.disabled = true;
        submitButton.textContent = 'hissing, please wait...'; // New loading text

        try {
            console.log("[JS] Starting fetch to api.php...");
            const response = await fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code: code }),
            });
            console.log(`[JS] Fetch response received. Status: ${response.status}`);

            let responseBodyText = await response.text();
            console.log("[JS] Response body read as text.");

            if (!response.ok) {
                let errorMsg = `HTTP error! Status: ${response.status}`;
                 console.error("[JS] HTTP error detected.");
                try {
                    const errorData = JSON.parse(responseBodyText);
                    if (errorData && errorData.error) {
                        errorMsg = `Error: ${errorData.error}`;
                    } else { errorMsg += `: ${responseBodyText.substring(0, 150)}`; }
                     console.log("[JS] Parsed error JSON (if applicable).");
                } catch (e) {
                    errorMsg += `: ${responseBodyText.substring(0, 150)}`;
                     console.log("[JS] Failed to parse error response as JSON.");
                }
                throw new Error(errorMsg);
            }

            console.log("[JS] Attempting to parse successful response as JSON...");
            const results = JSON.parse(responseBodyText);
            console.log("[JS] JSON parsed successfully.");

            if (results.error) {
                console.error("[JS] Backend returned an error object in JSON:", results.error);
                 throw new Error(`Analysis Error: ${results.error}`);
            }

            console.log("[JS] Hiding loader before displayResults.");
            loadingIndicator.style.display = 'none';
            console.log("[JS] Calling displayResults...");
            displayResults(results);
            console.log("[JS] displayResults finished.");

        } catch (error) {
            console.error('[JS] Caught error in fetch/processing block:', error);
            console.log("[JS] Hiding loader in catch block.");
            loadingIndicator.style.display = 'none';
            // Use theme color var for error display
            resultsOutput.innerHTML = `<p style="color: var(--red);"><strong>Analysis failed:</strong> ${escapeHtml(error.message) || 'Unknown venom encountered.'}</p>`;
        } finally {
            console.log("[JS] Entering finally block.");
            // Re-enable Button (Updated Text)
            submitButton.disabled = false;
            submitButton.textContent = 'shake it before you play with it'; // New button text
            console.log("[JS] Button re-enabled.");

            // Double check loader is hidden
             if (loadingIndicator.style.display !== 'none') {
                console.warn("[JS] Spinner was not hidden in try/catch, forcing hide in finally.");
                loadingIndicator.style.display = 'none';
             } else {
                console.log("[JS] Spinner check complete: already hidden.");
             }
            // Restore default message only if results area is totally empty
             if (resultsOutput.innerHTML.trim() === '') {
                 console.log("[JS] Results output empty, restoring default message.");
                 resultsOutput.innerHTML = defaultResultsMessage;
             }
             console.log("[JS] Finally block finished.");
        }
    });

    // --- Textarea Expand Logic ---
    codeInput.addEventListener('focus', () => {
        if (codeInput.classList.contains('collapsed')) {
            codeInput.classList.remove('collapsed');
            console.log("[JS] Textarea expanded on focus.");
        }
    });


    // --- Display Results Function ---
    function displayResults(results) {
        console.log("[JS] displayResults called with data:", results);
        if (!results || results.length === 0) {
            resultsOutput.innerHTML = '<p>No slippery imports detected in the provided code.</p>';
            return;
        }

        let tableHTML = `
            <table>
                <thead>
                    <tr>
                        <th>Link</th>
                        <th>Status</th>
                        <th>Package Name</th>
                        <th>Handler(s)</th> <!-- Renamed Author -->
                        <th>First Seen</th> <!-- Renamed Created -->
                        <th>Risk Level</th> <!-- Renamed Safety -->
                    </tr>
                </thead>
                <tbody>
        `;

        results.forEach(pkg => {
            const createdDate = pkg.created ? formatDate(pkg.created) : 'N/A';
            const author = pkg.author ? escapeHtml(pkg.author) : 'N/A';
            const statusClass = getStatusClass(pkg.status);
            const isBuiltin = pkg.status === 'Built-in';
            const rowClass = isBuiltin ? 'row-builtin' : '';

            const safetyText = escapeHtml(pkg.safety || 'Unknown');
            const safetyClass = getSafetyClass(pkg.safety, isBuiltin);

            let linkIconHTML = '';
            const linkUrl = pkg.libraries_io_url || pkg.link_url;
            const linkTitle = pkg.libraries_io_url ? "Visit Libraries.io page" : pkg.link_url ? "Visit package registry page" : "";

            // Updated Emoji: Use Earth Globe
            const globeEmoji = '🌍';

            if (linkUrl) {
                linkIconHTML = `<a href="${escapeHtml(linkUrl)}" target="_blank" rel="noopener noreferrer" class="package-link-icon" title="${linkTitle}">${globeEmoji}</a>`;
            } else {
                linkIconHTML = `<span class="package-link-icon no-link" title="No link available">${globeEmoji}</span>`;
            }

            tableHTML += `
                <tr class="${rowClass}">
                    <td>${linkIconHTML}</td>
                    <td class="${statusClass}">${escapeHtml(pkg.status)}</td>
                    <td>${escapeHtml(pkg.name)}</td>
                    <td>${author}</td>
                    <td>${createdDate}</td>
                    <td class="${safetyClass}">${safetyText}</td>
                </tr>
            `;

            if (pkg.status === 'Error' && pkg.error) {
                tableHTML += `
                    <tr class="error-details ${rowClass}">
                        <td colspan="6">
                            <em>↳ Error: ${escapeHtml(pkg.error)}</em>
                        </td>
                    </tr>
                `;
            }
        });

        tableHTML += `
                </tbody>
            </table>
        `;

        resultsOutput.innerHTML = tableHTML;
    }

    // --- Helper Functions (Keep formatDate, getStatusClass, getSafetyClass, escapeHtml as before) ---
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return 'Invalid Date';
            return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) || date.toISOString().split('T')[0];
        } catch (e) {
            console.warn("[JS] Date formatting error for:", dateString, e);
            return 'Invalid Date';
        }
    }

     function getStatusClass(status) {
        switch (status?.toLowerCase()) {
            case 'found': return 'status-found';
            case 'not found': return 'status-not-found';
            case 'error': return 'status-error';
            case 'built-in': return 'status-builtin';
            case 'unknown': return 'status-unknown';
            default: return '';
        }
    }

    function getSafetyClass(safety, isBuiltin = false) {
        if (isBuiltin || safety?.toLowerCase().includes('built-in')) {
             return 'safety-builtin';
        }
        const safetyLower = safety?.toLowerCase() || '';
        if (safetyLower.includes('high potential risk')) { return 'safety-high-risk'; }
        if (safetyLower.includes('potential risk')) { return 'safety-potential-risk'; }
        if (safetyLower.includes('use caution')) { return 'safety-use-caution'; }
        if (safetyLower === 'likely safe (moderately established)' || safetyLower === 'likely safe (established)') { return 'safety-likely-safe'; }
        if (safetyLower === 'likely safe (built-in)') { return 'safety-builtin'; }
        return 'safety-unknown';
    }

    function escapeHtml(unsafe) {
        if (unsafe === null || typeof unsafe === 'undefined') return '';
        return unsafe.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
     }

});