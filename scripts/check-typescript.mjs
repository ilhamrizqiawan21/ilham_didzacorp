import { readdir, readFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { join } from 'node:path';
import { parse } from '@vue/compiler-sfc';

const root = fileURLToPath(new URL('../resources/js/', import.meta.url));
const violations = [];

async function check(directory) {
    for (const entry of await readdir(directory, { withFileTypes: true })) {
        const path = join(directory, entry.name);
        if (entry.isDirectory()) {
            await check(path);
        } else if (/\.[cm]?jsx?$/.test(entry.name)) {
            violations.push(`${path}: JavaScript must be migrated to TypeScript`);
        } else if (entry.name.endsWith('.vue')) {
            const { descriptor, errors } = parse(await readFile(path, 'utf8'), { filename: path });
            for (const error of errors) violations.push(`${path}: ${String(error)}`);
            for (const script of [descriptor.script, descriptor.scriptSetup].filter(Boolean)) {
                if (!['ts', 'tsx'].includes(script.lang)) {
                    violations.push(`${path}: Vue scripts must declare lang="ts"`);
                }
            }
        }
    }
}

await check(root);
if (violations.length) {
    console.error(violations.join('\n'));
    process.exitCode = 1;
} else {
    console.log('TypeScript coverage: all frontend scripts are typed.');
}
