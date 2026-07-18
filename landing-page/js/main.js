(function () {
	'use strict';

	/* ─── Slider ─── */
	function initSliders() {
		document.querySelectorAll('[data-slider]').forEach(function (slider) {
			var track = slider.querySelector('.slider__track');
			var prevBtn = slider.querySelector('[data-slider-prev]');
			var nextBtn = slider.querySelector('[data-slider-next]');
			if (!track) return;

			function getScrollAmount() {
				var slide = track.querySelector('.slider__slide');
				if (!slide) return 200;
				var gap = parseInt(getComputedStyle(track).gap, 10) || 16;
				return slide.offsetWidth + gap;
			}

			function updateButtons() {
				if (!prevBtn || !nextBtn) return;
				prevBtn.disabled = track.scrollLeft <= 0;
				nextBtn.disabled = track.scrollLeft >= track.scrollWidth - track.clientWidth - 1;
			}

			if (prevBtn) {
				prevBtn.addEventListener('click', function () {
					track.scrollBy({ left: -getScrollAmount(), behavior: 'smooth' });
				});
			}

			if (nextBtn) {
				nextBtn.addEventListener('click', function () {
					track.scrollBy({ left: getScrollAmount(), behavior: 'smooth' });
				});
			}

			track.addEventListener('scroll', updateButtons, { passive: true });
			updateButtons();
		});
	}

	/* ─── Accordion ─── */
	function initAccordion() {
		document.querySelectorAll('[data-accordion]').forEach(function (accordion) {
			accordion.querySelectorAll('.accordion__trigger').forEach(function (trigger) {
				trigger.addEventListener('click', function () {
					var item = trigger.closest('.accordion__item');
					var isOpen = item.classList.contains('is-open');

					accordion.querySelectorAll('.accordion__item').forEach(function (el) {
						el.classList.remove('is-open');
						el.querySelector('.accordion__trigger').setAttribute('aria-expanded', 'false');
					});

					if (!isOpen) {
						item.classList.add('is-open');
						trigger.setAttribute('aria-expanded', 'true');
					}
				});
			});
		});
	}

	/* ─── Mobile nav toggle ─── */
	function initMobileNav() {
		var menuBtn = document.querySelector('.header__menu-btn');
		var nav = document.querySelector('.header__nav');
		if (!menuBtn || !nav) return;

		menuBtn.addEventListener('click', function () {
			var expanded = menuBtn.getAttribute('aria-expanded') === 'true';
			menuBtn.setAttribute('aria-expanded', String(!expanded));
			nav.style.display = expanded ? '' : 'flex';
			if (!expanded) {
				nav.style.flexDirection = 'column';
				nav.style.position = 'absolute';
				nav.style.top = '64px';
				nav.style.left = '0';
				nav.style.right = '0';
				nav.style.padding = '16px var(--side-padding)';
				nav.style.background = 'rgba(255,255,255,0.98)';
				nav.style.borderBottom = '1px solid var(--color-border)';
				nav.style.gap = '16px';
			}
		});
	}

	/* ─── Equal card heights within sections ─── */
	function equalizeCardHeights() {
		document.querySelectorAll('.products__grid, .info-cards, .licensing__grid').forEach(function (grid) {
			var cards = grid.children;
			if (!cards.length) return;

			Array.from(cards).forEach(function (card) {
				card.style.minHeight = '';
			});

			if (window.innerWidth >= 768) {
				var maxHeight = 0;
				Array.from(cards).forEach(function (card) {
					maxHeight = Math.max(maxHeight, card.offsetHeight);
				});
				Array.from(cards).forEach(function (card) {
					card.style.minHeight = maxHeight + 'px';
				});
			}
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		initSliders();
		initAccordion();
		initMobileNav();
		equalizeCardHeights();
	});

	var resizeTimer;
	window.addEventListener('resize', function () {
		clearTimeout(resizeTimer);
		resizeTimer = setTimeout(equalizeCardHeights, 150);
	});
})();
