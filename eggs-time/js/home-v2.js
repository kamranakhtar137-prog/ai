(function ($) {
    'use strict';

    var DESKTOP_BREAKPOINT = 1200;
    var MOBILE_SLIDES_TO_SHOW = 3;
    /* Choose Your Egg World + Best Sellers only — 2 cards on mobile */
    var EGG_CAROUSEL_MOBILE_SLIDES = 2;

    function getSliderConfig($wrap, prevLabel, nextLabel, arrowClass, options) {
        var settings = $.extend({
            slidesToShow: 3,
            slidesToScroll: 1,
            responsive: [
                {
                    breakpoint: 992,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: MOBILE_SLIDES_TO_SHOW,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 576,
                    settings: {
                        slidesToShow: MOBILE_SLIDES_TO_SHOW,
                        slidesToScroll: 1
                    }
                }
            ]
        }, options || {});

        return {
            slidesToShow: settings.slidesToShow,
            slidesToScroll: settings.slidesToScroll,
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
            adaptiveHeight: !!settings.adaptiveHeight,
            prevArrow: '<button class="slick-prev ' + arrowClass + '" aria-label="' + prevLabel + '" type="button"></button>',
            nextArrow: '<button class="slick-next ' + arrowClass + '" aria-label="' + nextLabel + '" type="button"></button>',
            responsive: settings.responsive
        };
    }

    function getParentTrustCertsSliderConfig($wrap, prevLabel, nextLabel, arrowClass) {
        return getSliderConfig($wrap, prevLabel, nextLabel, arrowClass, {
            slidesToShow: 4,
            slidesToScroll: 1,
            responsive: [
                {
                    breakpoint: 992,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: MOBILE_SLIDES_TO_SHOW,
                        slidesToScroll: 1
                    }
                }
            ]
        });
    }

    function getParentTrustMessagesSliderConfig($wrap, prevLabel, nextLabel, arrowClass) {
        return getSliderConfig($wrap, prevLabel, nextLabel, arrowClass, {
            slidesToShow: 3,
            slidesToScroll: 1,
            adaptiveHeight: true,
            responsive: [
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: MOBILE_SLIDES_TO_SHOW,
                        slidesToScroll: 1
                    }
                }
            ]
        });
    }

    function buildEggCarouselArrows(arrowClass, prevLabel, nextLabel) {
        return {
            prevArrow: '<button class="slick-prev et-home__egg-carousel-arrow et-home__egg-carousel-arrow--prev ' + arrowClass + ' ' + arrowClass + '--prev" aria-label="' + prevLabel + '" type="button"></button>',
            nextArrow: '<button class="slick-next et-home__egg-carousel-arrow et-home__egg-carousel-arrow--next ' + arrowClass + ' ' + arrowClass + '--next" aria-label="' + nextLabel + '" type="button"></button>'
        };
    }

    function prepareEggWorldSliderForLoop($el) {
        if ($el.data('et-loop-prepared') || 'fixed' === $el.data('etCarouselMode')) {
            return;
        }

        var $items = $el.children('li');
        var count = $items.length;

        if (count > 1) {
            $items.clone().appendTo($el);
        }

        $el.data('et-loop-prepared', true);
    }

    function withInfiniteResponsiveSettings(responsive) {
        return $.map(responsive || [], function (breakpoint) {
            if (typeof breakpoint.settings.infinite === 'boolean') {
                return breakpoint;
            }

            breakpoint.settings = $.extend({}, breakpoint.settings, { infinite: true });
            return breakpoint;
        });
    }

    function getEggWorldSliderConfig($wrap, prevLabel, nextLabel, arrowClass, options) {
        var settings = $.extend({
            slidesToShow: 6,
            slidesToScroll: 1,
            responsive: [
                {
                    breakpoint: 1199,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 991,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: MOBILE_SLIDES_TO_SHOW,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 576,
                    settings: {
                        slidesToShow: MOBILE_SLIDES_TO_SHOW,
                        slidesToScroll: 1
                    }
                }
            ]
        }, options || {});

        var arrows = buildEggCarouselArrows(arrowClass, prevLabel, nextLabel);

        return {
            slidesToShow: settings.slidesToShow,
            slidesToScroll: settings.slidesToScroll,
            initialSlide: typeof settings.initialSlide === 'number' ? settings.initialSlide : 0,
            arrows: true,
            appendArrows: $wrap,
            autoplay: false,
            infinite: typeof settings.infinite === 'boolean' ? settings.infinite : true,
            swipe: true,
            swipeToSlide: true,
            draggable: true,
            touchMove: true,
            touchThreshold: 8,
            edgeFriction: 0.05,
            speed: 350,
            waitForAnimate: false,
            prevArrow: arrows.prevArrow,
            nextArrow: arrows.nextArrow,
            responsive: withInfiniteResponsiveSettings(settings.responsive)
        };
    }

    function getCharactersSliderConfig($wrap, prevLabel, nextLabel, arrowClass) {
        return getEggWorldSliderConfig($wrap, prevLabel, nextLabel, arrowClass, {
            slidesToShow: 6,
            slidesToScroll: 1,
            responsive: [
                {
                    breakpoint: 1199,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: EGG_CAROUSEL_MOBILE_SLIDES,
                        slidesToScroll: 1
                    }
                }
            ]
        });
    }

    function getBestSellersSliderConfig($wrap, prevLabel, nextLabel, arrowClass) {
        return getEggWorldSliderConfig($wrap, prevLabel, nextLabel, arrowClass, {
            slidesToShow: 6,
            slidesToScroll: 1,
            responsive: [
                {
                    breakpoint: 1199,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: EGG_CAROUSEL_MOBILE_SLIDES,
                        slidesToScroll: 1
                    }
                }
            ]
        });
    }

    function initEggWorldSlider(selector, wrapClass, prevLabel, nextLabel, arrowClass, configBuilder, resizeEvent) {
        var $sliders = $(selector);
        var buildConfig = configBuilder || getEggWorldSliderConfig;
        var resizeTimer;

        if (!$sliders.length) {
            return;
        }

        function initOne($el) {
            var $wrap = $el.closest(wrapClass);

            if (typeof $.fn.slick !== 'function') {
                return false;
            }

            if ($el.hasClass('slick-initialized')) {
                $el.slick('setPosition');
                return true;
            }

            prepareEggWorldSliderForLoop($el);
            $wrap.addClass('is-slider-active');
            $el.slick(buildConfig($wrap, prevLabel, nextLabel, arrowClass));
            $el.find('img').attr('draggable', 'false');
            $el.on('dragstart', 'img', function (event) {
                event.preventDefault();
            });
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
            $(window).on('load.etHomeEggSlider', function () {
                if (refresh()) {
                    $(window).off('load.etHomeEggSlider');
                }
            });
        }

        if (resizeEvent) {
            $(window).on(resizeEvent, function () {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(refresh, 150);
            });
        }
    }

    function toggleResponsiveSlider($slider, wrapClass, prevLabel, nextLabel, arrowClass, configBuilder) {
        var $wrap = $slider.closest(wrapClass);
        var buildConfig = configBuilder || getSliderConfig;

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

        $slider.slick(buildConfig($wrap, prevLabel, nextLabel, arrowClass));
    }

    function bindResponsiveSlider(selector, wrapClass, prevLabel, nextLabel, arrowClass, resizeEvent, configBuilder) {
        var $sliders = $(selector);
        var resizeTimer;

        if (!$sliders.length || typeof $.fn.slick !== 'function') {
            return;
        }

        function refresh() {
            $sliders.each(function () {
                toggleResponsiveSlider($(this), wrapClass, prevLabel, nextLabel, arrowClass, configBuilder);
            });
        }

        refresh();

        $(window).on(resizeEvent, function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(refresh, 150);
        });
    }

    function getGamesExtraSliderConfig($wrap, prevLabel, nextLabel, arrowClass) {
        return getEggWorldSliderConfig($wrap, prevLabel, nextLabel, arrowClass, {
            slidesToShow: 3,
            slidesToScroll: 1,
            infinite: true,
            responsive: [
                {
                    breakpoint: 1199,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 991,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: MOBILE_SLIDES_TO_SHOW,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 576,
                    settings: {
                        slidesToShow: MOBILE_SLIDES_TO_SHOW,
                        slidesToScroll: 1
                    }
                }
            ]
        });
    }

    function getProductsSliderConfig($wrap, prevLabel, nextLabel, arrowClass) {
        var $panel = $wrap.closest('.et-home__products-panel');
        var config = getEggWorldSliderConfig($wrap, prevLabel, nextLabel, arrowClass, {
            slidesToShow: 6,
            slidesToScroll: 1,
            responsive: [
                {
                    breakpoint: 1400,
                    settings: {
                        slidesToShow: 5,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 1199,
                    settings: {
                        slidesToShow: 4,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 991,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 576,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 1
                    }
                }
            ]
        });

        config.dots = true;
        config.appendDots = $panel.find('.et-home__products-dots');
        config.autoplay = false;

        return config;
    }

    $(document).ready(function () {
        initEggWorldSlider(
            '.et-home__best-sellers-slider',
            '.et-home__best-sellers-slider-wrap',
            'Previous best sellers',
            'Next best sellers',
            'et-home__best-sellers-arrow',
            getBestSellersSliderConfig,
            'resize.etHomeBestSellersSlider'
        );

        initEggWorldSlider(
            '.et-home__characters-slider',
            '.et-home__characters-slider-wrap',
            'Previous characters',
            'Next characters',
            'et-home__characters-arrow',
            getCharactersSliderConfig,
            'resize.etHomeCharactersSlider'
        );

        function getStoriesSliderConfig($wrap, prevLabel, nextLabel, arrowClass) {
            return {
                slidesToShow: 3,
                slidesToScroll: 1,
                arrows: true,
                appendArrows: $wrap,
                autoplay: false,
                pauseOnHover: true,
                pauseOnFocus: true,
                infinite: true,
                swipe: true,
                draggable: true,
                touchMove: true,
                adaptiveHeight: false,
                prevArrow: '<button class="slick-prev ' + arrowClass + '" aria-label="' + prevLabel + '" type="button"></button>',
                nextArrow: '<button class="slick-next ' + arrowClass + '" aria-label="' + nextLabel + '" type="button"></button>',
                responsive: [
                    {
                        breakpoint: 992,
                        settings: {
                            slidesToShow: 2,
                            slidesToScroll: 1
                        }
                    },
                    {
                        breakpoint: 768,
                        settings: {
                            slidesToShow: 2,
                            slidesToScroll: 1
                        }
                    }
                ]
            };
        }

        (function initStoriesSlider() {
            var MOBILE_MAX = 768;
            var $sliders = $('.et-home__stories-slider');
            var resizeTimer;

            if (!$sliders.length || typeof $.fn.slick !== 'function') {
                return;
            }

            function toggle($slider) {
                var $wrap = $slider.closest('.et-home__stories-slider-wrap');
                var $section = $slider.closest('.et-home__stories');

                if (window.innerWidth >= DESKTOP_BREAKPOINT) {
                    $wrap.removeClass('is-slider-active');
                    $section.removeClass('et-home__stories--mobile-card');

                    if ($slider.hasClass('slick-initialized')) {
                        $slider.slick('unslick');
                    }

                    return;
                }

                $section.toggleClass('et-home__stories--mobile-card', window.innerWidth <= MOBILE_MAX);
                $wrap.addClass('is-slider-active');

                if ($slider.hasClass('slick-initialized')) {
                    $slider.slick('setPosition');
                    return;
                }

                $slider.slick(getStoriesSliderConfig(
                    $wrap,
                    'Previous stories',
                    'Next stories',
                    'et-home__stories-arrow'
                ));
            }

            function refresh() {
                $sliders.each(function () {
                    toggle($(this));
                });
            }

            refresh();

            $(window).on('resize.etHomeStoriesSlider', function () {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(refresh, 150);
            });
        }());

        initEggWorldSlider(
            '.et-home__products-slider',
            '.et-home__products-slider-wrap',
            'Previous products',
            'Next products',
            'et-home__products-arrow',
            getProductsSliderConfig,
            'resize.etHomeProductsSlider'
        );

        bindResponsiveSlider(
            '.et-home__parent-trust-certs-slider',
            '.et-home__parent-trust-certs-slider-wrap',
            'Previous certifications',
            'Next certifications',
            'et-home__parent-trust-arrow',
            'resize.etHomeParentTrustCertsSlider',
            getParentTrustCertsSliderConfig
        );

        initEggWorldSlider(
            '.et-home__games-extra-slider',
            '.et-home__games-extra-slider-wrap',
            'Previous games',
            'Next games',
            'et-home__games-extra-arrow',
            getGamesExtraSliderConfig,
            'resize.etHomeGamesExtraSlider'
        );

        function getFunEggGameCardsSliderConfig($wrap, prevLabel, nextLabel, arrowClass) {
            var count = parseInt($wrap.find('.et-home__fun-egg-games-cards-slider').data('etGameCount'), 10) || 2;
            var slidesToShow = Math.min(count, 2);

            return getEggWorldSliderConfig($wrap, prevLabel, nextLabel, arrowClass, {
                slidesToShow: slidesToShow,
                slidesToScroll: 1,
                infinite: count > slidesToShow,
                responsive: [
                    {
                        breakpoint: 767,
                        settings: {
                            slidesToShow: slidesToShow,
                            slidesToScroll: 1,
                            infinite: count > slidesToShow
                        }
                    }
                ]
            });
        }

        /* Fun in Every Egg game cards: 2-up slider when more than 2 games. */
        (function initFunEggGameCardsSlider() {
            var $sliders = $('.et-home__fun-egg-games-cards-slider');
            var resizeTimer;

            if (!$sliders.length || typeof $.fn.slick !== 'function') {
                return;
            }

            function toggle($slider) {
                var $wrap = $slider.closest('.et-home__fun-egg-games-cards-slider-wrap');
                var count = parseInt($slider.data('etGameCount'), 10) || 0;

                if (count <= 2) {
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

                prepareEggWorldSliderForLoop($slider);
                $slider.slick(
                    getFunEggGameCardsSliderConfig(
                        $wrap,
                        'Previous games',
                        'Next games',
                        'et-home__fun-egg-games-cards-arrow'
                    )
                );
                $slider.find('img').attr('draggable', 'false');
                $slider.on('dragstart', 'img', function (event) {
                    event.preventDefault();
                });
            }

            function refresh() {
                $sliders.each(function () {
                    toggle($(this));
                });
            }

            refresh();

            $(window).on('resize.etHomeFunEggGameCardsSlider', function () {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(refresh, 150);
            });
        })();

        /* Fun Egg app game cards: static grid on desktop/tablet; slider on mobile. */
        (function initFunEggAppGamesMobileSlider() {
            var MOBILE_MAX = 767;
            var $sliders = $('.et-home__fun-egg-app-games-slider');
            var resizeTimer;

            if (!$sliders.length || typeof $.fn.slick !== 'function') {
                return;
            }

            function toggle($slider) {
                var $wrap = $slider.closest('.et-home__fun-egg-app-games-slider-wrap');

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

                $slider.slick({
                    slidesToShow: MOBILE_SLIDES_TO_SHOW,
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
                    prevArrow: '<button class="slick-prev et-home__fun-egg-app-arrow" aria-label="Previous app games" type="button"></button>',
                    nextArrow: '<button class="slick-next et-home__fun-egg-app-arrow" aria-label="Next app games" type="button"></button>'
                });
            }

            function refresh() {
                $sliders.each(function () {
                    toggle($(this));
                });
            }

            refresh();

            $(window).on('resize.etHomeFunEggAppGamesSlider', function () {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(refresh, 150);
            });
        })();

        /* Community social cards: static grid on desktop/tablet; slider on mobile. */
        (function initSocialCardsMobileSlider() {
            var MOBILE_MAX = 767;
            var $sliders = $('.et-home__social-slider');
            var resizeTimer;

            if (!$sliders.length || typeof $.fn.slick !== 'function') {
                return;
            }

            function toggle($slider) {
                var $wrap = $slider.closest('.et-home__social-slider-wrap');

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

                $slider.slick({
                    slidesToShow: 1,
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
                    prevArrow: '<button class="slick-prev et-home__social-arrow" aria-label="Previous social cards" type="button"></button>',
                    nextArrow: '<button class="slick-next et-home__social-arrow" aria-label="Next social cards" type="button"></button>'
                });
            }

            function refresh() {
                $sliders.each(function () {
                    toggle($(this));
                });
            }

            refresh();

            $(window).on('resize.etHomeSocialCardsSlider', function () {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(refresh, 150);
            });
        })();

        /* Distributor brand logos: CSS scroll carousel on mobile (no slick — avoids overflow). */
        (function initDistributorBrandsScrollCarousel() {
            var MOBILE_MAX = 767;
            var $wraps = $('.et-home__distributor-showcase-slider-wrap');

            if (!$wraps.length) {
                return;
            }

            function ensureArrows($wrap) {
                if ($wrap.find('.et-home__distributor-arrow').length) {
                    return;
                }

                $wrap.prepend(
                    '<button type="button" class="et-home__distributor-arrow et-home__distributor-arrow--prev" aria-label="Previous brands"></button>' +
                    '<button type="button" class="et-home__distributor-arrow et-home__distributor-arrow--next" aria-label="Next brands"></button>'
                );
            }

            function bindWrap($wrap) {
                var $track = $wrap.find('.et-home__distributor-showcase-grid');
                if (!$track.length || $wrap.data('et-distributor-bound')) {
                    return;
                }

                ensureArrows($wrap);
                $wrap.data('et-distributor-bound', true);

                function scrollByCard(direction) {
                    var card = $track.find('.et-home__distributor-showcase-item').get(0);
                    var amount = card ? card.getBoundingClientRect().width + 12 : $track.innerWidth() * 0.8;
                    $track.get(0).scrollBy({ left: direction * amount, behavior: 'smooth' });
                }

                $wrap.on('click.etDistributorScroll', '.et-home__distributor-arrow--prev', function () {
                    scrollByCard(-1);
                });

                $wrap.on('click.etDistributorScroll', '.et-home__distributor-arrow--next', function () {
                    scrollByCard(1);
                });
            }

            function syncMode() {
                var isMobile = window.innerWidth <= MOBILE_MAX;

                $wraps.each(function () {
                    var $wrap = $(this);
                    var $track = $wrap.find('.et-home__distributor-showcase-grid');

                    if ($track.hasClass('slick-initialized') && typeof $.fn.slick === 'function') {
                        $track.slick('unslick');
                    }

                    $wrap.toggleClass('is-scroll-carousel', isMobile);
                    if (isMobile) {
                        bindWrap($wrap);
                    }
                });
            }

            syncMode();
            $(window).on('resize.etHomeDistributorBrandsScroll', function () {
                window.clearTimeout(window.__etDistributorScrollTimer);
                window.__etDistributorScrollTimer = window.setTimeout(syncMode, 150);
            });
        })();

        /* Partnership CTA cards: grid on desktop; slider on mobile. */
        (function initCtaCardsMobileSlider() {
            var MOBILE_MAX = 767;
            var $sliders = $('.et-home__cta-cards-slider');
            var resizeTimer;

            if (!$sliders.length || typeof $.fn.slick !== 'function') {
                return;
            }

            function toggle($slider) {
                var $wrap = $slider.closest('.et-home__cta-cards-slider-wrap');

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

                $slider.slick({
                    slidesToShow: 1,
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
                    prevArrow: '<button class="slick-prev et-home__cta-arrow" aria-label="Previous partnership cards" type="button"></button>',
                    nextArrow: '<button class="slick-next et-home__cta-arrow" aria-label="Next partnership cards" type="button"></button>'
                });
            }

            function refresh() {
                $sliders.each(function () {
                    toggle($(this));
                });
            }

            refresh();

            $(window).on('resize.etHomeCtaCardsSlider', function () {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(refresh, 150);
            });
        })();

        initHeroVideo();
    });

    function initHeroVideoWrap(videoWrap) {
        var video = videoWrap.querySelector('.et-home__hero-video');
        var playToggle = videoWrap.querySelector('.et-home__hero-video-play');
        var soundToggle = videoWrap.querySelector('.et-home__hero-video-sound');

        if (!video) {
            return;
        }

        function updatePlayButtonLabel() {
            if (!playToggle) {
                return;
            }

            playToggle.setAttribute(
                'aria-label',
                video.paused ? 'Play video' : 'Pause video'
            );
        }

        function setPlayingState(isPlaying) {
            videoWrap.classList.toggle('is-playing', isPlaying);
            updatePlayButtonLabel();
        }

        function markVideoReady() {
            videoWrap.classList.remove('is-loading');
        }

        function markVideoLoading() {
            videoWrap.classList.add('is-loading');
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
                    video.play().catch(function () {});
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

        markVideoLoading();

        video.addEventListener('loadeddata', markVideoReady);
        video.addEventListener('canplay', markVideoReady);

        if (video.readyState >= 2) {
            markVideoReady();
        }

        if (soundToggle) {
            soundToggle.addEventListener('click', function (event) {
                event.stopPropagation();
                setSoundState(!soundToggle.classList.contains('is-unmuted'));
            });
        }
    }

    function initHeroVideo() {
        var hero = document.querySelector('.et-home__hero--split-video');
        if (!hero) {
            return;
        }

        hero.querySelectorAll('.et-home__hero-video-wrap').forEach(function (videoWrap) {
            initHeroVideoWrap(videoWrap);
        });
    }
})(jQuery);
