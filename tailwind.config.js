import defaultTheme from 'tailwindcss/defaultTheme';
import preset from './vendor/filament/support/tailwind.config.preset';

/** @type {import('tailwindcss').Config} */
export default {
    presets: [preset],
    content: [
        './app/**/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './storage/framework/views/*.php',
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',

        // Filament core (panels, forms, tables, notifications, widgets, ...).
        './vendor/filament/**/*.blade.php',

        // Filament plugins render their own markup, so their views have to be
        // scanned too — otherwise their utility classes get purged out of the
        // custom theme and the plugin UI silently loses its styling.
        './vendor/awcodes/filament-quick-create/resources/**/*.blade.php',
        './vendor/awcodes/shout/resources/**/*.blade.php',
        './vendor/charrafimed/global-search-modal/resources/**/*.blade.php',
        './vendor/hugomyb/filament-media-action/resources/**/*.blade.php',
        './vendor/joaopaulolndev/filament-pdf-viewer/resources/**/*.blade.php',
        './vendor/leandrocfe/filament-apex-charts/resources/**/*.blade.php',
        './vendor/mokhosh/filament-kanban/resources/**/*.blade.php',
        './vendor/pxlrbt/filament-excel/resources/**/*.blade.php',
        './vendor/pxlrbt/filament-spotlight/resources/**/*.blade.php',
        './vendor/torgodly/html2media/resources/**/*.blade.php',
        './vendor/webbingbrasil/filament-advancedfilter/resources/**/*.blade.php',
        './vendor/wire-elements/spotlight/resources/**/*.blade.php',
        './vendor/ysfkaya/filament-phone-input/resources/**/*.blade.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                // Match the font the panel actually loads. The config used to
                // name Figtree, which nothing anywhere fetched, so every
                // `font-sans` element quietly fell through to the system font
                // while Filament's own chrome rendered in Inter.
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    plugins: [],
};
