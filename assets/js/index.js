import Swiper from 'swiper/bundle';

class InstagramFeedSlider {
  constructor(module = document) {
    this.slider = module.querySelector('.js-instagram-feed');
    this.pagination = module.querySelector('.js-swiper-pagination');
    this.next = module.querySelector('.js-swiper-button-next');
    this.prev = module.querySelector('.js-swiper-button-prev');
    this.init();
  }

  init() {
    this.runSlider();
  }

  runSlider() {
    new Swiper(this.slider, {
      slidesPerView: 1,
      spaceBetween: 30,
      loop: true,
      keyboard: {
        enabled: true,
      },
      autoplay: {
        delay: 2500,
        disableOnInteraction: false,
      },
      pagination: {
        el: this.pagination,
        clickable: true,
      },
      navigation: {
        nextEl: this.next,
        prevEl: this.prev,
      },
    });
  }
}

new InstagramFeedSlider();