<script>
(function () {
    document.querySelectorAll('[data-realtime-name-search]').forEach(function (form) {
        const input = form.querySelector('input[name="search"]');
        const target = document.querySelector(form.dataset.resultsTarget);
        const fallbackButton = form.querySelector('[data-search-submit]');
        const status = form.querySelector('[data-search-status]');

        if (!input || !target) return;

        // The button remains useful when JavaScript is unavailable. Once
        // this live-search handler is active, typing is all that is needed.
        fallbackButton?.classList.add('d-none');

        let timer = null;
        let activeRequest = null;
        let revision = 0;

        function buildUrl() {
            const url = new URL(form.action, window.location.origin);
            const formData = new FormData(form);

            formData.forEach(function (value, key) {
                const normalized = String(value).trim();
                if (normalized !== '') url.searchParams.append(key, normalized);
            });

            // A changed search always starts at the first result page.
            url.searchParams.delete('page');

            return url;
        }

        async function refreshResults(url = buildUrl(), version = revision) {

            activeRequest?.abort();
            const request = new AbortController();
            activeRequest = request;

            input.setAttribute('aria-busy', 'true');
            target.style.opacity = '.55';
            if (status) status.textContent = 'Searching...';

            try {
                const response = await fetch(url, {
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    signal: request.signal,
                });

                if (!response.ok) throw new Error('Search request failed.');

                const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                const incomingResults = page.querySelector(form.dataset.resultsTarget);
                if (!incomingResults) throw new Error('Search results were not found.');
                if (version !== revision) return;

                target.innerHTML = incomingResults.innerHTML;
                window.history.replaceState({}, '', url);
                target.querySelectorAll('.table-responsive').forEach(function (table) {
                    table.tabIndex = 0;
                    table.setAttribute('aria-label', 'Data table. Scroll horizontally to view all columns.');
                });
                if (status) status.textContent = target.querySelector('[data-result-summary]')?.textContent || 'Results updated.';
            } catch (error) {
                if (error.name !== 'AbortError' && status) {
                    status.textContent = 'Unable to update results. Press Enter to retry.';
                }
            } finally {
                if (activeRequest === request) {
                    activeRequest = null;
                    input.removeAttribute('aria-busy');
                    target.style.opacity = '';
                }
            }
        }

        input.addEventListener('input', function () {
            revision++;
            activeRequest?.abort();
            window.clearTimeout(timer);
            timer = window.setTimeout(refreshResults, 250);
        });

        form.querySelectorAll('select').forEach(function (select) {
            select.addEventListener('change', function () {
                revision++;
                window.clearTimeout(timer);
                refreshResults();
            });
        });

        target.addEventListener('click', function (event) {
            const link = event.target.closest('.pagination a');
            if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            revision++;
            window.clearTimeout(timer);
            refreshResults(new URL(link.href));
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            window.clearTimeout(timer);
            refreshResults();
        });
    });
})();
</script>
