import './styles/app.css';

/*
 * Gentle entrance: elements marked data-reveal fade in when they reach the screen,
 * and numbers marked data-count count up to their value.
 */
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const revealer = new IntersectionObserver((entries) => {
    for (const entry of entries) {
        if (!entry.isIntersecting) {
            continue;
        }
        entry.target.classList.add('is-visible');
        entry.target.querySelectorAll('[data-count]').forEach(countUp);
        revealer.unobserve(entry.target);
    }
}, { threshold: 0.15 });

function reveal(root = document) {
    root.querySelectorAll('[data-reveal]:not(.is-visible)').forEach((element) => revealer.observe(element));
}

function countUp(element) {
    const target = Number(element.dataset.count);
    const suffix = element.dataset.suffix ?? '';
    if (reduceMotion || !target) {
        return;
    }
    const start = performance.now();
    const step = (now) => {
        const progress = Math.min(1, (now - start) / 1200);
        element.textContent = Math.round(target * (1 - Math.pow(1 - progress, 3))) + suffix;
        if (progress < 1) {
            requestAnimationFrame(step);
        }
    };
    requestAnimationFrame(step);
}

reveal();

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
        const grid = document.querySelector(link.dataset.target);
        grid.append(...page.querySelector('[data-items]').children);
        reveal(grid);

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
