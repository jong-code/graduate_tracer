const { test } = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const source = fs.readFileSync('resources/views/partials/realtime-name-search.blade.php', 'utf8')
    .replace(/^<script>\s*/, '').replace(/\s*<\/script>\s*$/, '');

function setup() {
    const events = () => ({ handlers: {}, addEventListener(name, fn) { this.handlers[name] = fn; } });
    const input = { ...events(), value: '', setAttribute() {}, removeAttribute() {} };
    const select = { ...events(), value: 'user' };
    const status = { textContent: '' };
    const button = { hidden: false, classList: { add() { button.hidden = true; } } };
    const target = { ...events(), innerHTML: 'initial', style: {},
        querySelector() { return { textContent: '1 accounts found.' }; }, querySelectorAll() { return []; } };
    const form = { ...events(), action: 'http://localhost/admin/users', dataset: { resultsTarget: '#results' },
        querySelector(selector) { return selector.startsWith('input') ? input : selector.includes('submit') ? button : status; },
        querySelectorAll() { return [select]; } };
    const requests = [];
    const timers = new Map();
    let nextTimer = 0;
    const context = {
        document: { querySelectorAll: () => [form], querySelector: () => target },
        window: { location: { origin: 'http://localhost' }, history: { replaceState(...args) { context.lastUrl = args[2]; } },
            setTimeout(fn) { const id = ++nextTimer; timers.set(id, fn); return id; }, clearTimeout(id) { timers.delete(id); } },
        URL, AbortController,
        FormData: class { forEach(fn) { fn(input.value, 'search'); fn(select.value, 'role'); } },
        DOMParser: class { parseFromString(html) { return { querySelector: () => ({ innerHTML: html }) }; } },
        fetch(url, options) { return new Promise(resolve => requests.push({ url, options, resolve })); },
    };
    vm.runInNewContext(source, context);
    return { input, select, status, button, target, form, requests, context,
        async tick() { const callbacks = [...timers.values()]; timers.clear(); await Promise.all(callbacks.map(fn => fn())); },
        runTimer() { const callbacks = [...timers.values()]; timers.clear(); callbacks.forEach(fn => fn()); } };
}
async function answer(request, html = 'latest') {
    request.resolve({ ok: true, text: async () => html });
    await new Promise(resolve => setImmediate(resolve));
}

test('typing is debounced and preserves other filters in the request', async () => {
    const ui = setup();
    ui.input.value = ' J '; ui.input.handlers.input();
    ui.input.value = ' Joseph '; ui.input.handlers.input();
    assert.equal(ui.requests.length, 0);
    ui.runTimer();
    assert.equal(ui.requests.length, 1);
    assert.equal(ui.requests[0].url.searchParams.get('search'), 'Joseph');
    assert.equal(ui.requests[0].url.searchParams.get('role'), 'user');
    assert.equal(ui.requests[0].url.searchParams.has('page'), false);
    await answer(ui.requests[0]);
    assert.equal(ui.target.innerHTML, 'latest');
    assert.equal(ui.status.textContent, '1 accounts found.');
    assert.equal(ui.button.hidden, true);
});

test('a late response cannot overwrite results after another keystroke', async () => {
    const ui = setup();
    ui.input.value = 'Jo'; ui.input.handlers.input(); ui.runTimer();
    ui.input.value = 'Joseph'; ui.input.handlers.input();
    assert.equal(ui.requests[0].options.signal.aborted, true);
    await answer(ui.requests[0], 'stale');
    assert.equal(ui.target.innerHTML, 'initial');
    ui.runTimer();
    await answer(ui.requests[1], 'fresh');
    assert.equal(ui.target.innerHTML, 'fresh');
});

test('pagination updates only results and clearing search retains the role', async () => {
    const ui = setup();
    let prevented = false;
    ui.target.handlers.click({ target: { closest: () => ({ href: 'http://localhost/admin/users?search=Joseph&role=user&page=2' }) },
        preventDefault() { prevented = true; } });
    assert.equal(prevented, true);
    assert.equal(ui.requests[0].url.searchParams.get('page'), '2');
    await answer(ui.requests[0]);
    ui.input.value = ''; ui.input.handlers.input(); ui.runTimer();
    assert.equal(ui.requests[1].url.searchParams.has('search'), false);
    assert.equal(ui.requests[1].url.searchParams.get('role'), 'user');
    await answer(ui.requests[1]);
});

test('changing a dropdown refreshes immediately and failed requests keep results', async () => {
    const ui = setup();
    ui.select.value = 'faculty'; ui.select.handlers.change();
    assert.equal(ui.requests.length, 1);
    assert.equal(ui.requests[0].url.searchParams.get('role'), 'faculty');
    ui.requests[0].resolve({ ok: false });
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(ui.target.innerHTML, 'initial');
    assert.match(ui.status.textContent, /Press Enter to retry/);
    assert.equal(ui.target.style.opacity, '');
});
