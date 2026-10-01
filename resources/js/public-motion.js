// A single calm fade-up as sections enter the viewport. Progressive: without
// JS, or with reduced motion, content renders in its final state, because the
// hidden pre-reveal state only applies once `motion-ready` is set on <html>.

export function initPublicMotion() {
    const targets = document.querySelectorAll('[data-reveal]');

    if (
        targets.length === 0
        || ! ('IntersectionObserver' in window)
        || window.matchMedia('(prefers-reduced-motion: reduce)').matches
    ) {
        return;
    }

    document.documentElement.classList.add('motion-ready');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-revealed');
                observer.unobserve(entry.target);
            }
        });
    }, { rootMargin: '0px 0px -6% 0px', threshold: 0.1 });

    targets.forEach((target) => observer.observe(target));
}
