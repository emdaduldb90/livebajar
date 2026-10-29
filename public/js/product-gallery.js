(() => {
    'use strict';

    document.querySelectorAll('[data-product-gallery]').forEach((gallery) => {
        const mainImage = gallery.querySelector('[data-gallery-main-image]');
        const thumbnails = gallery.querySelectorAll('[data-gallery-thumbnail]');

        if (!(mainImage instanceof HTMLImageElement) || thumbnails.length === 0) {
            return;
        }

        thumbnails.forEach((thumbnail) => {
            thumbnail.addEventListener('click', () => {
                const imageSource = thumbnail.dataset.galleryImageSrc;
                const imageAlt = thumbnail.dataset.galleryImageAlt;

                if (!imageSource || mainImage.getAttribute('src') === imageSource) {
                    return;
                }

                mainImage.classList.add('is-changing');
                mainImage.src = imageSource;
                mainImage.alt = imageAlt || '';

                thumbnails.forEach((item) => {
                    const isSelected = item === thumbnail;
                    item.classList.toggle('is-active', isSelected);
                    item.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
                });

                const revealImage = () => mainImage.classList.remove('is-changing');
                mainImage.addEventListener('load', revealImage, { once: true });
                mainImage.addEventListener('error', revealImage, { once: true });
            });
        });
    });
})();
