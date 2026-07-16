(function (global) {
  'use strict';

  var initialized = {};

  function getRenderedImageBox(image) {
    var rect = image.getBoundingClientRect();
    var naturalWidth = image.naturalWidth;
    var naturalHeight = image.naturalHeight;

    if (!naturalWidth || !naturalHeight) {
      return { offsetX: 0, offsetY: 0, width: rect.width, height: rect.height };
    }

    var imageRatio = naturalWidth / naturalHeight;
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

  function getZoomImageSrc(image) {
    return image.getAttribute('data-zoom-src') || image.currentSrc || image.src;
  }

  function initContainer(container) {
    if (container.getAttribute('data-hover-zoom-ready') === 'true') {
      return;
    }

    var image = container.querySelector('.product-gallery__image');
    var lens = container.querySelector('.zoom-lens');
    var lensImg = container.querySelector('.zoom-lens__img');

    if (!image || !lens || !lensImg) {
      return;
    }

    container.setAttribute('data-hover-zoom-ready', 'true');

    var lensSize = 100;
    var lensRadius = lensSize / 2;
    var zoomRatio = 2.5;

    function moveLens(event) {
      var imageRect = image.getBoundingClientRect();
      var containerRect = container.getBoundingClientRect();
      var renderBox = getRenderedImageBox(image);
      var imgLeftEdge = imageRect.left + renderBox.offsetX;
      var imgTopEdge = imageRect.top + renderBox.offsetY;
      var imgRightEdge = imgLeftEdge + renderBox.width;
      var imgBottomEdge = imgTopEdge + renderBox.height;
      var pointerX = event.clientX - imageRect.left;
      var pointerY = event.clientY - imageRect.top;
      var imageX = pointerX - renderBox.offsetX;
      var imageY = pointerY - renderBox.offsetY;

      if (imageX < 0 || imageY < 0 || imageX > renderBox.width || imageY > renderBox.height) {
        lens.classList.remove('is-active');
        return;
      }

      if (renderBox.width < lensSize || renderBox.height < lensSize) {
        lens.classList.remove('is-active');
        return;
      }

      var centerX = imgLeftEdge + imageX;
      var centerY = imgTopEdge + imageY;
      var clampedCenterX = Math.min(imgRightEdge - lensRadius, Math.max(imgLeftEdge + lensRadius, centerX));
      var clampedCenterY = Math.min(imgBottomEdge - lensRadius, Math.max(imgTopEdge + lensRadius, centerY));
      var lensImageX = clampedCenterX - imgLeftEdge;
      var lensImageY = clampedCenterY - imgTopEdge;
      var magnifiedW = renderBox.width * zoomRatio;
      var magnifiedH = renderBox.height * zoomRatio;
      var zoomSrc = getZoomImageSrc(image);

      lens.style.width = lensSize + 'px';
      lens.style.height = lensSize + 'px';
      lens.style.left = (clampedCenterX - containerRect.left - lensRadius) + 'px';
      lens.style.top = (clampedCenterY - containerRect.top - lensRadius) + 'px';

      if (lensImg.getAttribute('src') !== zoomSrc) {
        lensImg.src = zoomSrc;
      }

      lensImg.style.width = magnifiedW + 'px';
      lensImg.style.height = magnifiedH + 'px';
      lensImg.style.left = (lensRadius - (lensImageX * zoomRatio)) + 'px';
      lensImg.style.top = (lensRadius - (lensImageY * zoomRatio)) + 'px';
      lens.classList.add('is-active');
    }

    container.addEventListener('mouseleave', function () {
      lens.classList.remove('is-active');
    });
    container.addEventListener('mousemove', moveLens);

    if (!image.complete) {
      image.addEventListener('load', function () {
        lensImg.src = getZoomImageSrc(image);
      });
    } else {
      lensImg.src = getZoomImageSrc(image);
    }
  }

  function init(productId) {
    if (initialized[productId]) {
      return;
    }

    if (global.matchMedia('(hover: none) and (pointer: coarse)').matches) {
      return;
    }

    var gallery = document.querySelector('.product-' + productId + '-gallery');
    if (!gallery) {
      return;
    }

    initialized[productId] = true;
    gallery.querySelectorAll('.zoom-container').forEach(initContainer);
  }

  global.ProductGalleryHoverZoom = { init: init };
})(window);
