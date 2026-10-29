(() => {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const revealItems = [...document.querySelectorAll('[data-scroll-reveal]')];
    if (revealItems.length && !reduceMotion && 'IntersectionObserver' in window) {
        document.documentElement.classList.add('has-page-reveal');
        const revealObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => entry.target.classList.toggle('is-revealed', entry.isIntersecting));
        }, {threshold:.08, rootMargin:'0px 0px -7% 0px'});
        revealItems.forEach(item => revealObserver.observe(item));
    }

    const viewport = document.querySelector('[data-recommendation-viewport]');
    const track = document.querySelector('[data-recommendation-track]');
    if (!viewport || !track) return;
    const originals = [...track.children];
    const itemCount = originals.length;
    if (!itemCount) return;

    originals.forEach(item => {
        const clone = item.cloneNode(true);
        clone.setAttribute('aria-hidden', 'true');
        clone.inert = true;
        clone.querySelectorAll('a,button,input,select,textarea,[tabindex]').forEach(control => control.setAttribute('tabindex', '-1'));
        track.append(clone);
    });

    const carousel = viewport.closest('.product-recommendations');
    const previousButton = carousel.querySelector('[data-recommendation-previous]');
    const nextButton = carousel.querySelector('[data-recommendation-next]');
    const toggleButton = carousel.querySelector('[data-recommendation-toggle]');
    let index = 0;
    let itemStep = 0;
    let userPaused = reduceMotion;
    let carouselVisible = false;
    let transitioning = false;

    const measure = () => {
        const item = track.querySelector('.recommendation-product');
        if (!item) return;
        itemStep = item.getBoundingClientRect().width + parseFloat(getComputedStyle(track).columnGap || getComputedStyle(track).gap || 0);
        track.style.transform = `translate3d(${-index * itemStep}px, 0, 0)`;
    };
    const setTransition = enabled => { track.classList.toggle('is-transitioning', enabled); };
    const updateToggle = () => {
        if (!toggleButton) return;
        const label = userPaused ? 'Resume automatic recommendations' : 'Pause automatic recommendations';
        toggleButton.setAttribute('aria-label', label);
        toggleButton.innerHTML = `<i class="bi ${userPaused ? 'bi-play-fill' : 'bi-pause-fill'}"></i><span>${userPaused ? 'Play' : 'Pause'}</span>`;
    };
    const canAutoPlay = () => !userPaused && carouselVisible && !document.hidden;
    const moveNext = () => {
        if (transitioning) return;
        transitioning = true;
        setTransition(true);
        index += 1;
        track.style.transform = `translate3d(${-index * itemStep}px, 0, 0)`;
    };
    const movePrevious = () => {
        if (transitioning) return;
        if (index === 0) {
            setTransition(false);
            index = itemCount;
            track.style.transform = `translate3d(${-index * itemStep}px, 0, 0)`;
            track.getBoundingClientRect();
            requestAnimationFrame(() => {
                setTransition(true);
                index -= 1;
                track.style.transform = `translate3d(${-index * itemStep}px, 0, 0)`;
                transitioning = true;
            });
            return;
        }
        transitioning = true;
        setTransition(true);
        index -= 1;
        track.style.transform = `translate3d(${-index * itemStep}px, 0, 0)`;
    };

    track.addEventListener('transitionend', event => {
        if (event.propertyName !== 'transform') return;
        transitioning = false;
        if (index === itemCount) {
            setTransition(false);
            index = 0;
            track.style.transform = 'translate3d(0, 0, 0)';
            track.getBoundingClientRect();
            setTransition(true);
        }
    });
    previousButton?.addEventListener('click', movePrevious);
    nextButton?.addEventListener('click', moveNext);
    toggleButton?.addEventListener('click', () => { userPaused = !userPaused; updateToggle(); });
    if ('IntersectionObserver' in window) {
        new IntersectionObserver(entries => { carouselVisible = entries[0].isIntersecting; }, {threshold:.1}).observe(viewport);
    } else {
        carouselVisible = true;
    }
    window.addEventListener('resize', measure, {passive:true});
    window.setInterval(() => { if (canAutoPlay()) moveNext(); }, 5000);
    updateToggle();
    measure();
})();
