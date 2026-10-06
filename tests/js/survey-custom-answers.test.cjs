const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const view = fs.readFileSync('resources/views/tracer/dashboard.blade.php', 'utf8');
const source = view.slice(view.indexOf('function createSearchableSingleSelect('), view.indexOf('function searchByName('));

function element() {
    const classes = new Set();
    return { value: '', textContent: '', handlers: {}, children: [],
        classList: { add: name => classes.add(name), remove: name => classes.delete(name),
            contains: name => classes.has(name), toggle(name, enabled) { enabled ? classes.add(name) : classes.delete(name); } },
        setAttribute() {}, addEventListener(name, callback) { this.handlers[name] = callback; },
        appendChild(child) { this.children.push(child); }, querySelectorAll() { return []; },
        setCustomValidity(message) { this.validityMessage = message; },
    };
}

for (const field of ['Present Occupation', 'Major line of business']) {
    test(field + ' accepts a typed answer and clears a previous catalog id', () => {
        const input = element(); input.required = true;
        const hidden = element();
        const listbox = element();
        const message = element();
        const context = { document: { addEventListener() {}, createElement: element }, setTimeout: callback => callback() };
        vm.runInNewContext(source, context);
        const combo = context.createSearchableSingleSelect(input, hidden, listbox, message, () => []);
        combo.setValue('catalog-id', 'Selected name');
        input.value = 'A custom answer not in the catalog';
        input.handlers.input();
        assert.equal(hidden.value, '');
        assert.equal(input.validityMessage, '');
        input.handlers.blur();
        assert.equal(input.value, 'A custom answer not in the catalog');
        assert.equal(message.textContent, '');
        assert.equal(input.validityMessage, '');
    });
}
