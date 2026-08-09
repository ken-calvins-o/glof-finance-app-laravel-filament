import { defineConfig } from 'vite';
import laravel, { refreshPaths } from 'laravel-vite-plugin';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

/**
 * Make `npm run dev` work behind Laravel Herd.
 *
 * Herd serves this project at <folder-name>.test. If the site has been secured
 * (`herd secure`), the page is served over https while Vite's dev server
 * defaults to http on localhost — and the browser blocks the mixed content, so
 * the panel loads with no styling at all and no obvious reason why.
 *
 * Herd already has a certificate for the site, so the dev server can simply
 * reuse it. If no certificate is found — the site is not secured, or this is
 * not Herd — we return nothing and Vite keeps its normal localhost defaults.
 *
 * Set VITE_DEV_HOST in .env to override the hostname if the folder name and the
 * Herd domain differ.
 */
function herdDevServer() {
    const host = process.env.VITE_DEV_HOST ?? `${path.basename(process.cwd())}.test`;

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

export default defineConfig({
    plugins: [
        laravel({
            /*
             * app.css is the Filament panel's theme (registered with
             * ->viteTheme() in AppPanelProvider), not a separate stylesheet, so
             * it has to stay in this list or the panel loses all of its styling.
             */
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: [
                ...refreshPaths,
                'app/Livewire/**',
                'app/Filament/**',
            ],
        }),
    ],
    server: herdDevServer(),
});
