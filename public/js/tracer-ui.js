/* Progressive enhancements only: forms, permissions and navigation still work server-side. */
(() => {
    let nextFieldId = 0;
    function enhanceForms(root) {
        root.querySelectorAll('label:not([for])').forEach(label => {
            if (label.querySelector('input, select, textarea')) return;
            const field = label.parentElement.querySelector('input:not([type="hidden"]), select, textarea');
            if (!field || field.matches('[type="checkbox"], [type="radio"]')) return;
            if (!field.id) {
                do { field.id = 'tracer-field-' + (++nextFieldId); }
                while (document.querySelectorAll('#' + field.id).length > 1);
            }
            label.htmlFor = field.id;
        });
        root.querySelectorAll('.alert-danger').forEach(alert => alert.setAttribute('role', 'alert'));
        root.querySelectorAll('.alert-success').forEach(alert => alert.setAttribute('role', 'status'));
        root.querySelectorAll('.modal').forEach(modal => {
            const title = modal.querySelector('.modal-title');
            if (title && !modal.hasAttribute('aria-labelledby')) {
                if (!title.id) title.id = modal.id + '-title';
                modal.setAttribute('aria-labelledby', title.id);
            }
        });
    }
    enhanceForms(document);
    // Give horizontally scrolling tables a visible mobile cue and make
    // their scroll area accessible from the keyboard.
    document.querySelectorAll('.table-responsive').forEach(table => {
        table.tabIndex = 0;
        table.setAttribute('aria-label', 'Data table. Scroll horizontally to view all columns.');
        const hint = document.createElement('p');
        hint.className = 'table-swipe-hint';
        hint.textContent = 'Swipe sideways to see all columns';
        table.before(hint);
    });
    // Wizard repeat rows are created after load; give their labels unique associations too.
    const form = document.getElementById('tracerForm');
    if (form) {
        new MutationObserver(records => {
            if (records.some(record => record.addedNodes.length)) enhanceForms(form);
        }).observe(form, { childList: true, subtree: true });
    }
})();
