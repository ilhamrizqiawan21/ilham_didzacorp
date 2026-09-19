import { spawn } from 'node:child_process';
import { mkdtemp, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { createServer } from 'node:net';
import { randomBytes } from 'node:crypto';

const directory = await mkdtemp(join(tmpdir(), 'lms-browser-'));
const database = join(directory, 'database.sqlite');
await writeFile(database, '');
const socket = createServer();
await new Promise(resolve => socket.listen(0, '127.0.0.1', resolve));
const port = socket.address().port;
await new Promise(resolve => socket.close(resolve));
const baseURL = `http://127.0.0.1:${port}`;
const env = {
    ...process.env, APP_ENV: 'testing', APP_URL: baseURL, ASSET_URL: baseURL,
    APP_KEY: `base64:${randomBytes(32).toString('base64')}`,
    DB_CONNECTION: 'sqlite', DB_DATABASE: database, DB_URL: '',
    CACHE_STORE: 'array', SESSION_DRIVER: 'database', SESSION_DOMAIN: '',
    SESSION_SECURE_COOKIE: 'false', SESSION_COOKIE: 'lms_browser_test',
    QUEUE_CONNECTION: 'sync', MAIL_MAILER: 'array', LOG_CHANNEL: 'stderr',
    BCRYPT_ROUNDS: '4', REQUIRE_STUDENT_PHONE: 'false', BROWSER_BASE_URL: baseURL,
};

function run(command, args) {
    return new Promise((resolve, reject) => {
        const child = spawn(command, args, { env, stdio: 'inherit' });
        child.on('error', reject);
        child.on('exit', code => code === 0 ? resolve() : reject(new Error(`${command} exited ${code}`)));
    });
}

let server;
try {
    await run('php', ['tests/Browser/seed.php']);
    server = spawn('php', ['-S', `127.0.0.1:${port}`, '../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'], { cwd: 'public', env, stdio: ['ignore', 'ignore', 'pipe'] });
    let serverErrors = '';
    server.stderr.on('data', data => { serverErrors = (serverErrors + data).slice(-8000); });
    let ready = false;
    for (let attempt = 0; attempt < 100; attempt++) {
        try {
            const response = await fetch(baseURL);
            if (response.ok) { ready = true; break; }
        } catch { /* The server may still be starting. */ }
        await new Promise(resolve => setTimeout(resolve, 100));
    }
    if (!ready) throw new Error(`Browser server did not start: ${serverErrors}`);
    await run(process.execPath, ['node_modules/@playwright/test/cli.js', 'test', ...process.argv.slice(2)]);
} finally {
    if (server && server.exitCode === null) {
        server.kill('SIGTERM');
        await new Promise(resolve => server.once('exit', resolve));
    }
    await rm(directory, { recursive: true, force: true });
}
