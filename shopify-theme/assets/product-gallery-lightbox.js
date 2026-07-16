(function (global) {
  'use strict';

  var instances = {};

  function getRenderedImageBox(img) {
    var rect = img.getBoundingClientRect();
    var nw = img.naturalWidth;
    var nh = img.naturalHeight;

    if (!nw || !nh) {
      return { offsetX: 0, offsetY: 0, width: rect.width, height: rect.height };
    }

    var imageRatio = nw / nh;
    var boxRatio = rect.width / rect.height;
    var renderWidth;
    var renderHeight;
    var offsetX = 0;
    var offsetY = 0;

    if (imageRatio > boxRatio) {
      renderWidth = rect.width;
      renderHeight = rect.width / imageRatio;
      offsetY = (rect.height - renderHeight) / 2;
    } else {
      renderHeight = rect.height;
      renderWidth = rect.height * imageRatio;
      offsetX = (rect.width - renderWidth) / 2;
    }

    return { offsetX: offsetX, offsetY: offsetY, width: renderWidth, height: renderHeight };
  }

  function init(productId) {
    if (instances[productId]) {
      return instances[productId];
    }

    var lightbox = document.querySelector('.product-lightbox--' + productId);
    if (!lightbox) {
      return null;
    }

    var triggers = Array.prototype.slice.call(
      document.querySelectorAll('.product-' + productId + '-gallery .product-lightbox-trigger')
    );

    if (!triggers.length) {
      return null;
    }

    var stage = lightbox.querySelector('.product-lightbox__stage');
    var content = lightbox.querySelector('.product-lightbox__content');
    var image = lightbox.querySelector('.product-lightbox__image');
    var lens = lightbox.querySelector('.product-lightbox__lens');
    var lensImg = lightbox.querySelector('.product-lightbox__lens-img');
    var hint = lightbox.querySelector('.product-lightbox__hint');
    var zoomLevel = lightbox.querySelector('.product-lightbox__zoom-level');
    var closeBtn = lightbox.querySelector('.product-lightbox__close');
    var prevBtn = lightbox.querySelector('.product-lightbox__prev');
    var nextBtn = lightbox.querySelector('.product-lightbox__next');

    var isTouch = global.matchMedia('(hover: none) and (pointer: coarse)').matches;
    var currentIndex = 0;
    var scale = 1;
    var translateX = 0;
    var translateY = 0;
    var lensSize = isTouch ? 160 : 100;
    var lensRadius = lensSize / 2;
    var lensRatio = isTouch ? 3 : 2.5;
    var tapLevels = isTouch ? [1, 5, 10, 20, 35, 50] : [1, 2, 4, 8, 10];
    var hintTimer;
    var moveThreshold = 6;
    var bound = false;

    var touchState = {
      mode: null,
      startX: 0,
      startY: 0,
      lastX: 0,
      lastY: 0,
      startDistance: 0,
      startScale: 1,
      moved: false
    };

    var mouseState = {
      dragging: false,
      moved: false,
      startX: 0,
      startY: 0,
      lastTranslateX: 0,
      lastTranslateY: 0
    };

    function getTriggerData(index) {
      var trigger = triggers[index];
      return {
        src: trigger.getAttribute('data-zoom-src') || trigger.getAttribute('href'),
        alt: trigger.getAttribute('data-zoom-alt') || ''
      };
    }

    function formatZoom(value) {
      return (Math.round(value * 10) / 10) + '×';
    }

    function showHint(text, persistent) {
      hint.textContent = text;
      hint.classList.add('is-visible');
      clearTimeout(hintTimer);

      if (!persistent) {
        hintTimer = setTimeout(function () {
          hint.classList.remove('is-visible');
        }, 3500);
      }
    }

    function hideHint() {
      clearTimeout(hintTimer);
      hint.classList.remove('is-visible');
    }

    function updateZoomLabel() {
      if (scale <= 1.05) {
        zoomLevel.classList.remove('is-visible');
        zoomLevel.textContent = '';
        content.classList.remove('is-pannable', 'is-dragging');
        if (isTouch) {
          showHint('Slide finger to magnify · Tap to zoom · Pinch to zoom', true);
        }
        return;
      }

      if (!isTouch) {
        content.classList.add('is-pannable');
      }

      zoomLevel.textContent = 'Zoom: ' + formatZoom(scale) + ' · Drag or move mouse to explore';
      zoomLevel.classList.add('is-visible');
    }

    function getPanLimits() {
      var rect = image.getBoundingClientRect();
      var stageRect = stage.getBoundingClientRect();

      return {
        maxX: Math.max(0, (rect.width - stageRect.width) / 2),
        maxY: Math.max(0, (rect.height - stageRect.height) / 2)
      };
    }

    function clampTranslate() {
      var limits = getPanLimits();
      translateX = Math.min(limits.maxX, Math.max(-limits.maxX, translateX));
      translateY = Math.min(limits.maxY, Math.max(-limits.maxY, translateY));
    }

    function panToFollowCursor(clientX, clientY) {
      var limits = getPanLimits();
      if (limits.maxX === 0 && limits.maxY === 0) {
        return;
      }

      var stageRect = stage.getBoundingClientRect();
      var normX = Math.min(1, Math.max(0, (clientX - stageRect.left) / stageRect.width));
      var normY = Math.min(1, Math.max(0, (clientY - stageRect.top) / stageRect.height));

      translateX = limits.maxX * (1 - (2 * normX));
      translateY = limits.maxY * (1 - (2 * normY));
      image.style.transform = 'translate(' + translateX + 'px, ' + translateY + 'px) scale(' + scale + ')';
    }

    function hideLens() {
      lens.classList.remove('is-active');
    }

    function applyTransform() {
      clampTranslate();
      image.style.transform = 'translate(' + translateX + 'px, ' + translateY + 'px) scale(' + scale + ')';
      updateZoomLabel();
      if (scale > 1.05) {
        hideLens();
      }
    }

    function resetTransform() {
      scale = 1;
      translateX = 0;
      translateY = 0;
      applyTransform();
    }

    function getNextTapLevel(current) {
      for (var i = 0; i < tapLevels.length - 1; i++) {
        if (current < tapLevels[i + 1] - 0.2) {
          return tapLevels[i + 1];
        }
      }
      return 1;
    }

    function zoomAtPoint(nextScale, clientX, clientY) {
      if (nextScale <= 1.05) {
        resetTransform();
        return;
      }

      var rect = image.getBoundingClientRect();
      var offsetX = clientX - (rect.left + rect.width / 2);
      var offsetY = clientY - (rect.top + rect.height / 2);
      var factor = 1 - nextScale / (scale || 1);

      scale = nextScale;
      translateX += offsetX * factor;
      translateY += offsetY * factor;
      applyTransform();
    }

    function positionMagnifierLens(clientX, clientY, renderBox, imageRect, imageSrc) {
      var imgLeftEdge = imageRect.left + renderBox.offsetX;
      var imgTopEdge = imageRect.top + renderBox.offsetY;
      var imgRightEdge = imgLeftEdge + renderBox.width;
      var imgBottomEdge = imgTopEdge + renderBox.height;
      var pointerX = clientX - imageRect.left;
      var pointerY = clientY - imageRect.top;
      var imageX = pointerX - renderBox.offsetX;
      var imageY = pointerY - renderBox.offsetY;

      if (imageX < 0 || imageY < 0 || imageX > renderBox.width || imageY > renderBox.height) {
        return false;
      }

      if (renderBox.width < lensSize || renderBox.height < lensSize) {
        return false;
      }

      var centerX = imgLeftEdge + imageX;
      var centerY = imgTopEdge + imageY;
      var clampedCenterX = Math.min(imgRightEdge - lensRadius, Math.max(imgLeftEdge + lensRadius, centerX));
      var clampedCenterY = Math.min(imgBottomEdge - lensRadius, Math.max(imgTopEdge + lensRadius, centerY));
      var lensImageX = clampedCenterX - imgLeftEdge;
      var lensImageY = clampedCenterY - imgTopEdge;
      var magnifiedW = renderBox.width * lensRatio;
      var magnifiedH = renderBox.height * lensRatio;

      lens.style.width = lensSize + 'px';
      lens.style.height = lensSize + 'px';
      lens.style.left = (clampedCenterX - lensRadius) + 'px';
      lens.style.top = (clampedCenterY - lensRadius) + 'px';

      if (lensImg.getAttribute('src') !== imageSrc) {
        lensImg.src = imageSrc;
      }

      lensImg.style.width = magnifiedW + 'px';
      lensImg.style.height = magnifiedH + 'px';
      lensImg.style.left = (lensRadius - (lensImageX * lensRatio)) + 'px';
      lensImg.style.top = (lensRadius - (lensImageY * lensRatio)) + 'px';
      return true;
    }

    function updateLens(clientX, clientY) {
      if (scale > 1.05 || !image.src || !image.complete) {
        lens.classList.remove('is-active');
        return;
      }

      var imageRect = image.getBoundingClientRect();
      var renderBox = getRenderedImageBox(image);
      if (positionMagnifierLens(clientX, clientY, renderBox, imageRect, image.src)) {
        lens.classList.add('is-active');
      } else {
        lens.classList.remove('is-active');
      }
    }

    function onDesktopPanMove(event) {
      if (!mouseState.dragging) {
        return;
      }

      var dx = event.clientX - mouseState.startX;
      var dy = event.clientY - mouseState.startY;

      if (Math.abs(dx) > moveThreshold || Math.abs(dy) > moveThreshold) {
        mouseState.moved = true;
      }

      translateX = mouseState.lastTranslateX + dx;
      translateY = mouseState.lastTranslateY + dy;
      clampTranslate();
      image.style.transform = 'translate(' + translateX + 'px, ' + translateY + 'px) scale(' + scale + ')';
    }

    function endDesktopPan() {
      if (!mouseState.dragging) {
        return;
      }

      mouseState.dragging = false;
      content.classList.remove('is-dragging');
      document.removeEventListener('mousemove', onDesktopPanMove);
      document.removeEventListener('mouseup', endDesktopPan);
    }

    function startDesktopPan(event) {
      if (isTouch || scale <= 1.05 || event.button !== 0) {
        return;
      }

      mouseState.dragging = true;
      mouseState.moved = false;
      mouseState.startX = event.clientX;
      mouseState.startY = event.clientY;
      mouseState.lastTranslateX = translateX;
      mouseState.lastTranslateY = translateY;
      content.classList.add('is-dragging');
      document.addEventListener('mousemove', onDesktopPanMove);
      document.addEventListener('mouseup', endDesktopPan);
      event.preventDefault();
    }

    function loadImage(index) {
      currentIndex = index;
      var data = getTriggerData(index);
      resetTransform();
      image.alt = data.alt;
      hideLens();

      image.onload = function () {
        updateZoomLabel();
      };

      image.src = data.src;
      lensImg.src = data.src;

      prevBtn.style.display = triggers.length > 1 ? '' : 'none';
      nextBtn.style.display = triggers.length > 1 ? '' : 'none';
    }

    function open(index) {
      bindEvents();
      loadImage(index);
      lightbox.classList.add('is-open');
      lightbox.setAttribute('aria-hidden', 'false');
      document.documentElement.style.overflow = 'hidden';
      document.body.style.overflow = 'hidden';

      if (isTouch) {
        showHint('Slide finger to magnify · Tap to zoom · Pinch to zoom', true);
      } else {
        showHint('Move mouse to magnify · Click to zoom · Scroll to zoom · Drag when zoomed', false);
      }
    }

    function closeLightbox() {
      endDesktopPan();
      lightbox.classList.remove('is-open');
      lightbox.setAttribute('aria-hidden', 'true');
      document.documentElement.style.overflow = '';
      document.body.style.overflow = '';
      image.src = '';
      hideLens();
      hideHint();
      zoomLevel.classList.remove('is-visible');
      content.classList.remove('is-pannable', 'is-dragging');
      resetTransform();
    }

    function showPrev() {
      var nextIndex = currentIndex - 1;
      if (nextIndex < 0) {
        nextIndex = triggers.length - 1;
      }
      loadImage(nextIndex);
    }

    function showNext() {
      var nextIndex = currentIndex + 1;
      if (nextIndex >= triggers.length) {
        nextIndex = 0;
      }
      loadImage(nextIndex);
    }

    function getTouchDistance(touches) {
      var dx = touches[0].clientX - touches[1].clientX;
      var dy = touches[0].clientY - touches[1].clientY;
      return Math.sqrt(dx * dx + dy * dy);
    }

    function onTouchStart(event) {
      if (event.touches.length === 2) {
        touchState.mode = 'pinch';
        touchState.startDistance = getTouchDistance(event.touches);
        touchState.startScale = scale;
        touchState.moved = true;
        hideLens();
        event.preventDefault();
        return;
      }

      if (event.touches.length === 1) {
        touchState.startX = event.touches[0].clientX;
        touchState.startY = event.touches[0].clientY;
        touchState.lastX = translateX;
        touchState.lastY = translateY;
        touchState.moved = false;

        if (scale > 1.05) {
          touchState.mode = 'pan';
        } else {
          touchState.mode = 'lens';
          updateLens(event.touches[0].clientX, event.touches[0].clientY);
        }
      }
    }

    function onTouchMove(event) {
      if (touchState.mode === 'pinch' && event.touches.length === 2) {
        var distance = getTouchDistance(event.touches);
        var nextScale = touchState.startScale * (distance / touchState.startDistance);
        var midX = (event.touches[0].clientX + event.touches[1].clientX) / 2;
        var midY = (event.touches[0].clientY + event.touches[1].clientY) / 2;
        var clampedScale = Math.min(50, Math.max(1, nextScale));

        if (clampedScale <= 1.05) {
          resetTransform();
        } else {
          var previousScale = scale;
          scale = clampedScale;

          if (previousScale <= 1.05) {
            var rect = image.getBoundingClientRect();
            translateX = (midX - (rect.left + rect.width / 2)) * (1 - clampedScale);
            translateY = (midY - (rect.top + rect.height / 2)) * (1 - clampedScale);
          } else {
            var factor = 1 - clampedScale / previousScale;
            var imageRect = image.getBoundingClientRect();
            translateX += (midX - (imageRect.left + imageRect.width / 2)) * factor;
            translateY += (midY - (imageRect.top + imageRect.height / 2)) * factor;
          }

          applyTransform();
        }

        touchState.moved = true;
        event.preventDefault();
        return;
      }

      if (event.touches.length !== 1) {
        return;
      }

      var touch = event.touches[0];
      var dx = touch.clientX - touchState.startX;
      var dy = touch.clientY - touchState.startY;

      if (Math.abs(dx) > moveThreshold || Math.abs(dy) > moveThreshold) {
        touchState.moved = true;
      }

      if (touchState.mode === 'lens' && scale <= 1.05) {
        updateLens(touch.clientX, touch.clientY);
        event.preventDefault();
        return;
      }

      if (touchState.mode === 'pan' && scale > 1.05) {
        translateX = touchState.lastX + dx;
        translateY = touchState.lastY + dy;
        applyTransform();
        event.preventDefault();
      }
    }

    function onTouchEnd(event) {
      if (touchState.mode === 'lens' && !touchState.moved && event.changedTouches.length) {
        var touch = event.changedTouches[0];
        zoomAtPoint(getNextTapLevel(scale), touch.clientX, touch.clientY);
      } else if (touchState.mode === 'pan' && !touchState.moved && event.changedTouches.length) {
        var tapTouch = event.changedTouches[0];
        zoomAtPoint(getNextTapLevel(scale), tapTouch.clientX, tapTouch.clientY);
      }

      if (event.touches.length === 0) {
        touchState.mode = null;
        if (scale <= 1.05) {
          hideLens();
        }
      }
    }

    function onKeydown(event) {
      if (!lightbox.classList.contains('is-open')) {
        return;
      }

      if (event.key === 'Escape') {
        closeLightbox();
      } else if (event.key === 'ArrowLeft') {
        showPrev();
      } else if (event.key === 'ArrowRight') {
        showNext();
      }
    }

    function bindEvents() {
      if (bound) {
        return;
      }
      bound = true;

      closeBtn.addEventListener('click', closeLightbox);
      prevBtn.addEventListener('click', showPrev);
      nextBtn.addEventListener('click', showNext);

      content.addEventListener('mousemove', function (event) {
        if (isTouch) {
          return;
        }

        if (scale > 1.05) {
          if (!mouseState.dragging) {
            panToFollowCursor(event.clientX, event.clientY);
          }
          return;
        }

        updateLens(event.clientX, event.clientY);
      });

      content.addEventListener('mouseleave', function () {
        if (scale <= 1.05) {
          hideLens();
        }
      });

      content.addEventListener('mousedown', startDesktopPan);

      content.addEventListener('click', function (event) {
        if (isTouch) {
          return;
        }

        if (mouseState.moved) {
          mouseState.moved = false;
          return;
        }

        zoomAtPoint(getNextTapLevel(scale), event.clientX, event.clientY);
      });

      content.addEventListener('wheel', function (event) {
        if (isTouch) {
          return;
        }

        event.preventDefault();
        var delta = event.deltaY < 0 ? 0.35 : -0.35;
        var nextScale = Math.min(10, Math.max(1, scale + delta));

        if (nextScale <= 1.05) {
          resetTransform();
          return;
        }

        zoomAtPoint(nextScale, event.clientX, event.clientY);
      }, { passive: false });

      content.addEventListener('touchstart', onTouchStart, { passive: false });
      content.addEventListener('touchmove', onTouchMove, { passive: false });
      content.addEventListener('touchend', onTouchEnd);
      content.addEventListener('touchcancel', onTouchEnd);

      stage.addEventListener('click', function (event) {
        if (event.target === stage) {
          closeLightbox();
        }
      });

      document.addEventListener('keydown', onKeydown);
    }

    var api = { open: open, close: closeLightbox };
    instances[productId] = api;
    return api;
  }

  global.ProductGalleryLightbox = {
    init: init,
    open: function (productId, index) {
      var api = init(productId);
      if (api) {
        api.open(index);
      }
    }
  };
})(window);
