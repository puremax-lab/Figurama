import './styles/app.css';

/*
 * Infinite scroll for the catalogue. The "load more" link works on its own
 * (it opens the next page); here, the next page is fetched as soon as the link
 * comes near the screen and its cards are added to the grid.
 */
const observer = new IntersectionObserver((entries) => {
    for (const entry of entries) {
        if (entry.isIntersecting) {
            loadMore(entry.target);
        }
    }
}, { rootMargin: '400px' });

async function loadMore(link) {
    observer.unobserve(link);
    link.textContent = 'Chargement…';

    try {
        const response = await fetch(link.href, { headers: { 'X-Requested-With': 'fetch' } });
        if (!response.ok) {
            throw new Error(response.statusText);
        }
        const page = new DOMParser().parseFromString(await response.text(), 'text/html');
        document.querySelector(link.dataset.target).append(...page.querySelector('[data-items]').children);

        const next = page.querySelector('[data-load-more]');
        if (next) {
            link.replaceWith(next);
            watch(next);
        } else {
            link.remove();
        }
    } catch {
        // Leave a working link: a click opens the next page normally.
        link.textContent = 'Charger la suite';
    }
}

function watch(link) {
    link.addEventListener('click', (event) => {
        event.preventDefault();
        loadMore(link);
    });
    observer.observe(link);
}

document.querySelectorAll('[data-load-more]').forEach(watch);
