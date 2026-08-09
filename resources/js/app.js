import './bootstrap';

/*
| Keep the sidebar out of the way on phones.
|
| Filament stores the sidebar's open/closed state in localStorage and defaults
| it to open, with no check for how wide the screen is:
|
|     Alpine.store('sidebar', { isOpen: Alpine.$persist(true).as('isOpen'), ... })
|
| On a desktop that is the right default. On a phone the sidebar is a full-height
| overlay, so the app opened onto a wall of navigation covering the entire screen
| — the treasurer had to dismiss it before they could see a single figure.
|
| Below Tailwind's `lg` breakpoint, which is where Filament switches the sidebar
| from an inline column to an overlay, we force it shut on load and whenever the
| viewport crosses down over that line. Opening it by hand still works; this only
| decides the state you arrive in.
*/
const OVERLAY_VIEWPORT = '(max-width: 1023.98px)';

function closeSidebarOnSmallScreens() {
    const sidebar = window.Alpine?.store('sidebar');

    if (! sidebar) {
        return;
    }

    if (window.matchMedia(OVERLAY_VIEWPORT).matches && sidebar.isOpen) {
        sidebar.close();
    }
}

document.addEventListener('alpine:initialized', () => {
    closeSidebarOnSmallScreens();

    // Rotating a phone into portrait, or narrowing a desktop window, drops the
    // sidebar back into overlay mode — where an open sidebar covers the page.
    window.matchMedia(OVERLAY_VIEWPORT).addEventListener('change', closeSidebarOnSmallScreens);
});

/*
| Tapping a link in the overlay should take you to the page, not leave the
| navigation sitting on top of it. The panel runs in SPA mode, so a click
| navigates without a page load and nothing would otherwise dismiss the overlay.
*/
document.addEventListener('livewire:navigated', closeSidebarOnSmallScreens);
