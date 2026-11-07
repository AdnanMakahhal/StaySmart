(() => {
  const slider = document.querySelector('.reviews-slider');
  if (!slider || typeof Swiper === 'undefined') return;
  const controls = document.querySelector('.reviews-slider-controls');
  controls.hidden = false;
  new Swiper(slider, {
    slidesPerView: 1,
    spaceBetween: 20,
    speed: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 400,
    watchOverflow: true,
    keyboard: { enabled: true, onlyInViewport: true },
    a11y: { enabled: true, prevSlideMessage: 'Previous guest review', nextSlideMessage: 'Next guest review' },
    navigation: { prevEl: '.reviews-prev', nextEl: '.reviews-next' },
    pagination: { el: '.reviews-pagination', clickable: true },
    breakpoints: { 640: { slidesPerView: 2, spaceBetween: 20 }, 1050: { slidesPerView: 3, spaceBetween: 24 } },
  });
})();
