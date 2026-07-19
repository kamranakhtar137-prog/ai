(function ($) {
    'use strict';

    var DESKTOP_BREAKPOINT = 1200;
    var MOBILE_MAX = 767;

    /* Duplicate slides so infinite + autoplay work with few cards. */
    function prepareProductsSliderForLoop($el) {
        if ($el.data('et-loop-prepared')) {
            return;
        }

        var $items = $el.children('li');

        /* Enough slides for infinite without cloning (e.g. 10 brand logos). */
        if ($items.length > 1 && $items.length < 8) {
            $items.clone().appendTo($el);
        }

        $el.data('et-loop-prepared', true);
    }

    function getBrandsSliderConfig($wrap, prevLabel, nextLabel, arrowClass) {
        return {
            slidesToShow: 5,
            slidesToScroll: 1,
            arrows: true,
            appendArrows: $wrap,
            autoplay: true,
            autoplaySpeed: 4500,
            pauseOnHover: true,
            pauseOnFocus: true,
            infinite: true,
            swipe: true,
            swipeToSlide: true,
            draggable: true,
            touchMove: true,
            prevArrow: '<button class="slick-prev ' + arrowClass + '" aria-label="' + prevLabel + '" type="button"></button>',
            nextArrow: '<button class="slick-next ' + arrowClass + '" aria-label="' + nextLabel + '" type="button"></button>',
            responsive: [
                {
                    breakpoint: 1024,
                    settings: {
                        slidesToShow: 4,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1,
                        arrows: true
                    }
                }
            ]
        };
    }

    function getSliderConfig($wrap, prevLabel, nextLabel, arrowClass) {
        return {
            slidesToShow: 3,
            slidesToScroll: 1,
            arrows: true,
            appendArrows: $wrap,
            autoplay: true,
            autoplaySpeed: 4500,
            pauseOnHover: true,
            pauseOnFocus: true,
            infinite: true,
            swipe: true,
            draggable: true,
            touchMove: true,
            prevArrow: '<button class="slick-prev ' + arrowClass + '" aria-label="' + prevLabel + '" type="button"></button>',
            nextArrow: '<button class="slick-next ' + arrowClass + '" aria-label="' + nextLabel + '" type="button"></button>',
            responsive: [
                {
                    breakpoint: 992,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 576,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1
                    }
                }
            ]
        };
    }

    function getProductsSliderConfig($wrap, prevLabel, nextLabel, arrowClass) {
        return {
            slidesToShow: 5,
            slidesToScroll: 1,
            arrows: true,
            appendArrows: $wrap,
            autoplay: true,
            autoplaySpeed: 4500,
            pauseOnHover: true,
            pauseOnFocus: true,
            infinite: true,
            swipe: true,
            swipeToSlide: true,
            draggable: true,
            touchMove: true,
            speed: 350,
            prevArrow: '<button class="slick-prev ' + arrowClass + '" aria-label="' + prevLabel + '" type="button"></button>',
            nextArrow: '<button class="slick-next ' + arrowClass + '" aria-label="' + nextLabel + '" type="button"></button>',
            responsive: [
                {
                    breakpoint: 1024,
                    settings: {
                        slidesToShow: 4,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1,
                        arrows: true
                    }
                }
            ]
        };
    }

    function toggleResponsiveSlider($slider, wrapClass, prevLabel, nextLabel, arrowClass) {
        var $wrap = $slider.closest(wrapClass);

        if (window.innerWidth >= DESKTOP_BREAKPOINT) {
            $wrap.removeClass('is-slider-active');

            if ($slider.hasClass('slick-initialized')) {
                $slider.slick('unslick');
            }

            return;
        }

        $wrap.addClass('is-slider-active');

        if ($slider.hasClass('slick-initialized')) {
            $slider.slick('setPosition');
            return;
        }

        $slider.slick(getSliderConfig($wrap, prevLabel, nextLabel, arrowClass));
    }

    function bindResponsiveSlider(selector, wrapClass, prevLabel, nextLabel, arrowClass, resizeEvent) {
        var $sliders = $(selector);
        var resizeTimer;

        if (!$sliders.length || typeof $.fn.slick !== 'function') {
            return;
        }

        function refresh() {
            $sliders.each(function () {
                toggleResponsiveSlider($(this), wrapClass, prevLabel, nextLabel, arrowClass);
            });
        }

        refresh();

        $(window).on(resizeEvent, function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(refresh, 150);
        });
    }

    function initAlwaysOnSlider(selector, wrapClass, prevLabel, nextLabel, arrowClass, configBuilder, resizeEvent) {
        var $sliders = $(selector);
        var resizeTimer;

        if (!$sliders.length || typeof $.fn.slick !== 'function') {
            return;
        }

        function initOne($el) {
            var $wrap = $el.closest(wrapClass);

            if ($el.hasClass('slick-initialized')) {
                $el.slick('setPosition');
                return true;
            }

            prepareProductsSliderForLoop($el);
            $wrap.addClass('is-slider-active');
            $el.slick(configBuilder($wrap, prevLabel, nextLabel, arrowClass));
            $el.slick('slickPlay');
            return true;
        }

        function refresh() {
            var initialized = true;

            $sliders.each(function () {
                if (!initOne($(this))) {
                    initialized = false;
                }
            });

            return initialized;
        }

        if (!refresh()) {
            $(window).on('load.etLicenseProductsSlider', function () {
                if (refresh()) {
                    $(window).off('load.etLicenseProductsSlider');
                }
            });
        }

        $(window).on(resizeEvent, function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(refresh, 150);
        });
    }

    function initLicenseHeroVideo() {
        var hero = document.querySelector('.et-license__hero-wrap .et-home__hero--split-video');
        if (!hero) {
            return;
        }

        hero.querySelectorAll('.et-home__hero-video-wrap').forEach(function (videoWrap) {
            if (videoWrap.getAttribute('data-et-video-ready') === '1') {
                return;
            }

            var video = videoWrap.querySelector('.et-home__hero-video');
            var playToggle = videoWrap.querySelector('.et-home__hero-video-play');
            var soundToggle = videoWrap.querySelector('.et-home__hero-video-sound');

            if (!video) {
                return;
            }

            videoWrap.setAttribute('data-et-video-ready', '1');

            function updatePlayButtonLabel() {
                if (!playToggle) {
                    return;
                }
                playToggle.setAttribute('aria-label', video.paused ? 'Play video' : 'Pause video');
            }

            function setPlayingState(isPlaying) {
                videoWrap.classList.toggle('is-playing', isPlaying);
                updatePlayButtonLabel();
            }

            function setSoundState(isUnmuted) {
                if (!soundToggle) {
                    return;
                }
                video.muted = !isUnmuted;
                soundToggle.classList.toggle('is-unmuted', isUnmuted);
                soundToggle.setAttribute('aria-pressed', isUnmuted ? 'true' : 'false');
                soundToggle.setAttribute('aria-label', isUnmuted ? 'Turn sound off' : 'Turn sound on');
                if (isUnmuted) {
                    video.play().catch(function () {});
                }
            }

            video.pause();
            setPlayingState(false);

            video.addEventListener('play', function () {
                setPlayingState(true);
            });

            video.addEventListener('pause', function () {
                setPlayingState(false);
            });

            if (playToggle) {
                playToggle.addEventListener('click', function (event) {
                    event.stopPropagation();
                    if (video.paused) {
                        video.muted = true;
                        setPlayingState(true);
                        video.play().catch(function () {
                            setPlayingState(false);
                        });
                        return;
                    }
                    video.pause();
                });
            }

            videoWrap.addEventListener('click', function (event) {
                if (
                    event.target.closest('.et-home__hero-video-sound') ||
                    event.target.closest('.et-home__hero-video-play')
                ) {
                    return;
                }
                if (!video.paused) {
                    video.pause();
                }
            });

            if (soundToggle) {
                soundToggle.addEventListener('click', function (event) {
                    event.stopPropagation();
                    setSoundState(!soundToggle.classList.contains('is-unmuted'));
                });
            }
        });
    }

    $(document).ready(function () {
        initLicenseHeroVideo();

        /* Brands: always a carousel — 5 on desktop/laptop, 2 on mobile. */
        (function initBrandsSlider() {
            var $sliders = $('.et-license__brands-slider');
            var resizeTimer;

            if (!$sliders.length || typeof $.fn.slick !== 'function') {
                return;
            }

            function init($slider) {
                var $wrap = $slider.closest('.et-license__brands-slider-wrap');

                $wrap.addClass('is-slider-active');

                if ($slider.hasClass('slick-initialized')) {
                    $slider.slick('setPosition');
                    return;
                }

                prepareProductsSliderForLoop($slider);
                $slider.slick(
                    getBrandsSliderConfig(
                        $wrap,
                        'Previous brands',
                        'Next brands',
                        'et-license__slider-arrow'
                    )
                );
                $slider.slick('slickPlay');
            }

            function refresh() {
                $sliders.each(function () {
                    init($(this));
                });
            }

            refresh();

            $(window).on('resize.etLicenseBrandsSlider', function () {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(refresh, 150);
            });
        })();

        /* Products: always a carousel — 5 on desktop/laptop, 2 on mobile. */
        (function initProductsSlider() {
            var $sliders = $('.et-license__products-slider');
            var resizeTimer;

            if (!$sliders.length || typeof $.fn.slick !== 'function') {
                return;
            }

            function init($slider) {
                var $wrap = $slider.closest('.et-license__products-slider-wrap');

                $wrap.addClass('is-slider-active');

                if ($slider.hasClass('slick-initialized')) {
                    $slider.slick('setPosition');
                    return;
                }

                prepareProductsSliderForLoop($slider);
                $slider.slick(
                    getProductsSliderConfig(
                        $wrap,
                        'Previous products',
                        'Next products',
                        'et-license__slider-arrow'
                    )
                );
                $slider.slick('slickPlay');
            }

            function refresh() {
                $sliders.each(function () {
                    init($(this));
                });
            }

            refresh();

            $(window).on('resize.etLicenseProductsSlider', function () {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(refresh, 150);
            });
        })();

        /* Categories: static grid on tablet/desktop; 2-up carousel on mobile only. */
        (function initCategoriesMobileSlider() {
            var $sliders = $('.et-license__categories-slider');
            var resizeTimer;

            if (!$sliders.length || typeof $.fn.slick !== 'function') {
                return;
            }

            function toggle($slider) {
                var $wrap = $slider.closest('.et-license__categories-slider-wrap');

                if (window.innerWidth > MOBILE_MAX) {
                    $wrap.removeClass('is-slider-active');

                    if ($slider.hasClass('slick-initialized')) {
                        $slider.slick('unslick');
                    }

                    return;
                }

                $wrap.addClass('is-slider-active');

                if ($slider.hasClass('slick-initialized')) {
                    $slider.slick('setPosition');
                    return;
                }

                prepareProductsSliderForLoop($slider);
                $slider.slick({
                    slidesToShow: 2,
                    slidesToScroll: 1,
                    arrows: true,
                    appendArrows: $wrap,
                    autoplay: true,
                    autoplaySpeed: 4500,
                    pauseOnHover: true,
                    pauseOnFocus: true,
                    infinite: true,
                    swipe: true,
                    swipeToSlide: true,
                    draggable: true,
                    touchMove: true,
                    speed: 350,
                    prevArrow: '<button class="slick-prev et-license__slider-arrow" aria-label="Previous categories" type="button"></button>',
                    nextArrow: '<button class="slick-next et-license__slider-arrow" aria-label="Next categories" type="button"></button>'
                });
                $slider.slick('slickPlay');
            }

            function refresh() {
                $sliders.each(function () {
                    toggle($(this));
                });
            }

            refresh();

            $(window).on('resize.etLicenseCategoriesSlider', function () {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(refresh, 150);
            });
        })();

        /* CTA cast: 3-up on desktop, 2-up on mobile. */
        (function initCtaCastSlider() {
            var $sliders = $('.et-license__cta-cast-slider');
            var resizeTimer;

            if (!$sliders.length || typeof $.fn.slick !== 'function') {
                return;
            }

            function init($slider) {
                var $wrap = $slider.closest('.et-license__cta-cast-slider-wrap');

                $wrap.addClass('is-slider-active');

                if ($slider.hasClass('slick-initialized')) {
                    $slider.slick('setPosition');
                    return;
                }

                prepareProductsSliderForLoop($slider);
                $slider.slick({
                    slidesToShow: 3,
                    slidesToScroll: 1,
                    arrows: true,
                    appendArrows: $wrap,
                    autoplay: true,
                    autoplaySpeed: 4500,
                    pauseOnHover: true,
                    pauseOnFocus: true,
                    infinite: true,
                    swipe: true,
                    swipeToSlide: true,
                    draggable: true,
                    touchMove: true,
                    speed: 350,
                    prevArrow: '<button class="slick-prev et-license__slider-arrow" aria-label="Previous characters" type="button"></button>',
                    nextArrow: '<button class="slick-next et-license__slider-arrow" aria-label="Next characters" type="button"></button>',
                    responsive: [
                        {
                            breakpoint: 768,
                            settings: {
                                slidesToShow: 2,
                                slidesToScroll: 1
                            }
                        }
                    ]
                });
                $slider.slick('slickPlay');
            }

            function refresh() {
                $sliders.each(function () {
                    init($(this));
                });
            }

            refresh();

            $(window).on('resize.etLicenseCtaCastSlider', function () {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(refresh, 150);
            });
        })();
    });
})(jQuery);
