import { defineConfig, loadEnv } from 'vite';
import laravel, { refreshPaths } from 'laravel-vite-plugin';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

/**
 * The hostname Herd serves this project on.
 *
 * Taken from APP_URL so the domain is configured in exactly one place. Change
 * it there and both Laravel and the dev server follow; there is no second copy
 * here to fall out of step.
 *
 * VITE_DEV_HOST overrides it for the rare case where the dev server needs a
 * different host from the app itself. Failing both, we guess from the folder
 * name, which is what Herd uses for a parked directory.
 */
function devServerHost(env) {
    if (env.VITE_DEV_HOST) {
        return env.VITE_DEV_HOST;
    }

    try {
        return new URL(env.APP_URL).hostname;
    } catch {
        return `${path.basename(process.cwd())}.test`;
    }
}

/**
 * Make `npm run dev` work behind Laravel Herd.
 *
 * If the site has been secured (`herd secure`), it is served over https while
 * Vite's dev server defaults to http on localhost — and the browser blocks the
 * mixed content, so the panel loads with no styling at all and no obvious
 * reason why.
 *
 * Herd already holds a certificate for the site, so the dev server just reuses
 * it. If no certificate is found — the site is not secured, or this is not Herd
 * — we return nothing and Vite keeps its normal localhost defaults.
 */
function herdDevServer(env) {
    const host = devServerHost(env);

    const certificateDirectories = [
        // macOS
        path.join(os.homedir(), 'Library', 'Application Support', 'Herd', 'config', 'valet', 'Certificates'),
        // Windows
        path.join(os.homedir(), '.config', 'herd', 'config', 'valet', 'Certificates'),
    ];

    for (const directory of certificateDirectories) {
        const key = path.join(directory, `${host}.key`);
        const certificate = path.join(directory, `${host}.crt`);

        if (fs.existsSync(key) && fs.existsSync(certificate)) {
            return {
                host,
                hmr: { host },
                https: {
                    key: fs.readFileSync(key),
                    cert: fs.readFileSync(certificate),
                },
            };
        }
    }

    return undefined;
}

export default defineConfig(({ mode }) => {
    // The empty prefix loads every variable, not just the VITE_-prefixed ones,
    // so APP_URL is readable here. Nothing from this is exposed to the browser.
    const env = loadEnv(mode, process.cwd(), '');

    return {
        plugins: [
            laravel({
                /*
                 * app.css is the Filament panel's theme (registered with
                 * ->viteTheme() in AppPanelProvider), not a separate
                 * stylesheet, so it has to stay in this list or the panel loses
                 * all of its styling.
                 */
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: [
                    ...refreshPaths,
                    'app/Livewire/**',
                    'app/Filament/**',
                ],
            }),
        ],
        server: herdDevServer(env),
    };
});
