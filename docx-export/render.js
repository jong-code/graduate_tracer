/**
 * Fills the GTS docxtemplater template with survey data.
 *
 * Usage: node render.js <template.docx> <data.json> <output.docx>
 *
 * Laravel (GtsDocxExportService) builds <data.json> from a survey's
 * relations and shells out to this script. Kept as a small, separate
 * Node process because docxtemplater is a JS-only library - there is
 * no first-party PHP equivalent that understands this template syntax
 * ({tag}, {#loop}...{/loop}, {^inverted}...{/inverted}).
 */
const fs = require('fs');
const path = require('path');
const PizZip = require('pizzip');
const Docxtemplater = require('docxtemplater');

function fail(message) {
    console.error(message);
    process.exit(1);
}

const [, , templatePath, dataPath, outputPath] = process.argv;

if (!templatePath || !dataPath || !outputPath) {
    fail('Usage: node render.js <template.docx> <data.json> <output.docx>');
}
if (!fs.existsSync(templatePath)) {
    fail(`Template not found: ${templatePath}`);
}
if (!fs.existsSync(dataPath)) {
    fail(`Data file not found: ${dataPath}`);
}

let data;
try {
    data = JSON.parse(fs.readFileSync(dataPath, 'utf8'));
} catch (err) {
    fail(`Could not parse data JSON: ${err.message}`);
}

// The template uses dotted tags (e.g. {#check.residence_city}) to keep the
// ~130 checkbox flags under one `check` object instead of polluting the
// top-level scope. Docxtemplater's default parser treats "check.foo" as a
// single literal tag name, so without this custom parser every dotted tag
// silently resolves to nothing. This parser walks the dots as a real path.
function dottedPathParser(tag) {
    return {
        get(scope) {
            if (tag === '.') {
                return scope;
            }
            return tag.split('.').reduce(
                (value, part) => (value === null || value === undefined ? undefined : value[part]),
                scope
            );
        },
    };
}

try {
    const content = fs.readFileSync(templatePath, 'binary');
    const zip = new PizZip(content);
    const doc = new Docxtemplater(zip, {
        paragraphLoop: true,
        linebreaks: true,
        parser: dottedPathParser,
        nullGetter: () => '', // missing tags render as blank rather than "undefined"
    });

    doc.render(data);

    const buffer = doc.getZip().generate({ type: 'nodebuffer' });
    fs.mkdirSync(path.dirname(outputPath), { recursive: true });
    fs.writeFileSync(outputPath, buffer);
    console.log(`OK: wrote ${outputPath}`);
} catch (err) {
    // docxtemplater errors carry a `properties.errors` array with the
    // specific unmatched/malformed tags - surface those, not just the
    // generic "Multi error" message, so mismatches are easy to fix.
    if (err.properties && err.properties.errors) {
        const details = err.properties.errors
            .map((e) => e.properties && e.properties.explanation ? e.properties.explanation : e.message)
            .join('; ');
        fail(`Template render error: ${details}`);
    }
    fail(`Template render error: ${err.message}`);
}
