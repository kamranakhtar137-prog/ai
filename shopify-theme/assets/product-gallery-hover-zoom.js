(function (global) {
  'use strict';

  var galleries = {};

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

  function removeDuplicateLenses(gallery) {
    document.querySelectorAll('.zoom-lens.is-gallery-hover').forEach(function (lens) {
      lens.parentNode.removeChild(lens);
    });

    gallery.querySelectorAll('.zoom-lens').forEach(function (lens) {
      lens.parentNode.removeChild(lens);
    });
  }

  function createSharedLens() {
    var lens = document.createElement('div');
    lens.className = 'zoom-lens is-gallery-hover';
    lens.setAttribute('aria-hidden', 'true');

    var lensImg = document.createElement('img');
    lensImg.className = 'zoom-lens__img';
    lensImg.src = '';
    lensImg.alt = '';
    lensImg.draggable = false;
    lensImg.decoding = 'async';

    lens.appendChild(lensImg);
    document.body.appendChild(lens);

    return { lens: lens, lensImg: lensImg };
  }

  function init(productId) {
    if (global.matchMedia('(hover: none) and (pointer: coarse)').matches) {
      return;
    }

    var gallery = document.querySelector('.product-' + productId + '-gallery');
    if (!gallery) {
      return;
    }

    if (galleries[productId]) {
      return;
    }

    removeDuplicateLenses(gallery);

    var lensParts = createSharedLens();
    var lens = lensParts.lens;
    var lensImg = lensParts.lensImg;
    var lensSize = 100;
    var lensRadius = lensSize / 2;
    var zoomRatio = 2.5;
    var activeImage = null;

    function hideLens() {
      lens.classList.remove('is-active');
      activeImage = null;
    }

    function isVisible(el) {
      var rect = el.getBoundingClientRect();
      return rect.width > 0 && rect.height > 0 && rect.bottom > 0 && rect.top < global.innerHeight;
    }

    function moveLens(event) {
      var container = event.target.closest('.zoom-container');
      if (!container || !gallery.contains(container)) {
        hideLens();
        return;
      }

      var image = container.querySelector('.product-gallery__image');
      if (!image || !isVisible(image)) {
        hideLens();
        return;
      }

      activeImage = image;
      var imageRect = image.getBoundingClientRect();
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
        hideLens();
        return;
      }

      if (renderBox.width < lensSize || renderBox.height < lensSize) {
        hideLens();
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
      lens.style.left = (clampedCenterX - lensRadius) + 'px';
      lens.style.top = (clampedCenterY - lensRadius) + 'px';

      if (lensImg.getAttribute('src') !== zoomSrc) {
        lensImg.src = zoomSrc;
      }

      lensImg.style.width = magnifiedW + 'px';
      lensImg.style.height = magnifiedH + 'px';
      lensImg.style.left = (lensRadius - (lensImageX * zoomRatio)) + 'px';
      lensImg.style.top = (lensRadius - (lensImageY * zoomRatio)) + 'px';
      lens.classList.add('is-active');
    }

    gallery.addEventListener('mousemove', moveLens);
    gallery.addEventListener('mouseleave', hideLens);

    if (global.jQuery) {
      global.jQuery(gallery).on('change.flickity select.flickity', hideLens);
    }

    galleries[productId] = true;
  }

  global.ProductGalleryHoverZoom = {
    init: init,
    refresh: init
  };
})(window);
