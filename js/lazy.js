document.addEventListener('DOMContentLoaded', function () {
    if (!('IntersectionObserver' in window)) {
        document.querySelectorAll('img.lazy').forEach(loadImage);
        return;
    }

    const io = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                loadImage(entry.target, observer);
            }
        });
    }, {
        rootMargin: '0px 0px 200px 0px'
    });

    document.querySelectorAll('img.lazy').forEach(img => io.observe(img));

    function loadImage(img, observer) {
        if (img.dataset.src) img.src = img.dataset.src;
        if (img.dataset.srcset) img.srcset = img.dataset.srcset;

        img.addEventListener('load', function onLoad() {
            img.classList.add('loaded'); // remove blur
            img.removeEventListener('load', onLoad);
        });

        if (observer) observer.unobserve(img);
    }
});
