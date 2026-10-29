(() => {
    const motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
    if (motionPreference.matches) return;

    const targets = document.querySelectorAll([
        '.dream-hero-copy',
        '.dream-hero-art',
        '.custom-heading',
        '#timeless-furniture-showcase',
        '#brand-story > div',
        '#featured-collections .product-card',
        '#featured-collections-title',
        '#sustainability-and-perspectives .col-md-4',
        '#perspectives-title',
        '#comfort-and-style h1',
        '.testimonial-card',
        '#customer-testimonials-title',
        '.home-about-collage img',
        '.home-about-copy',
        '.user-designer-card',
        '#designer-directory-title'
    ].join(','));

    if (!targets.length || !('IntersectionObserver' in window)) return;

    targets.forEach((target, index) => {
        target.setAttribute('data-storefront-reveal', '');
        target.style.setProperty('--storefront-reveal-delay', `${(index % 4) * 75}ms`);
    });

    document.documentElement.classList.add('has-storefront-motion');
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            entry.target.classList.toggle('is-storefront-visible', entry.isIntersecting);
        });
    }, { threshold: .12, rootMargin: '0px 0px -5% 0px' });

    targets.forEach(target => observer.observe(target));
})();
