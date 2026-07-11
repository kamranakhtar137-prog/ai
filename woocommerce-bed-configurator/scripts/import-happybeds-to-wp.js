/**
 * Import real Happy Beds layer images into your WordPress plugin cache.
 *
 * STEP 1 — On your WordPress site (logged in as admin), open any page and run:
 *   wcbcWpSite = 'http://localhost/slick';
 *   wcbcImportNonce = 'PASTE_NONCE_FROM_WP_ADMIN';
 *
 *   Get nonce: WooCommerce → Bed Configurator (or add this to product page source:
 *   view page source and search for wcbcImportNonce in localized script — added in v1.0.13)
 *
 * STEP 2 — Open https://www.happybeds.co.uk/build-your-own-bed in another tab
 * STEP 3 — Paste this entire file into DevTools Console and press Enter
 *
 * Images are fetched same-origin from Happy Beds, then POSTed to your WordPress cache.
 */
(function importHappyBedsToWordPress() {
	'use strict';

	var WP_SITE = window.wcbcWpSite || '';
	var NONCE = window.wcbcImportNonce || '';

	if (!WP_SITE) {
		console.error('Set wcbcWpSite first, e.g. wcbcWpSite = "http://localhost/slick";');
		return;
	}

	var CDN = '/media/new_configurator/';
	var FILES = [
		'new_shadow/shadow_wrk_3ft.jpg',
		'new_shadow/shadow_wrk_4ft6.jpg',
		'new_shadow/shadow_wrk_5ft.jpg',
		'new_shadow/shadow_wrk_6ft.jpg',
		'legs/bedding_legs_3ft.png',
		'legs/bedding_legs_4ft6.png',
		'legs/bedding_legs_5ft.png',
		'legs/bedding_legs_6ft.png',
		'FFFFFF-0.png',
		'bases/velvet/bedbase_3ft_14i_30.png',
		'bases/velvet/bedbase_4ft6_14i_30.png',
		'bases/velvet/bedbase_5ft_14i_30.png',
		'bases/velvet/bedbase_6ft_14i_30.png',
		'headboards_velvet/cornell_lined_3ft_14i30.png',
		'headboards_velvet/cornell_lined_4ft6_14i30.png',
		'headboards_velvet/cornell_lined_5ft_14i30.png',
		'headboards_velvet/cornell_lined_6ft_14i30.png',
		'drawers_velvet/reference_drawer_normal_back_3ft_drawer_normal_front_14i30.png',
		'drawers_velvet/reference_drawer_normal_front_3ft_drawer_normal_front_14i30.png',
		'drawers_velvet/reference_drawer_normal_back_190_4ft6_drawer_normal_front_14i30.png',
		'drawers_velvet/reference_drawer_normal_front_190_4ft6_drawer_normal_front_14i30.png',
		'drawers_velvet/reference_drawer_normal_back_200_5ft_drawer_normal_front_14i30.png',
		'drawers_velvet/reference_drawer_normal_front_200_5ft_drawer_normal_front_14i30.png',
		'drawers_velvet/reference_drawer_normal_back_200_6ft_drawer_normal_front_14i30.png',
		'drawers_velvet/reference_drawer_normal_front_200_6ft_drawer_normal_front_14i30.png',
	];

	function sleep(ms) {
		return new Promise(function (resolve) {
			setTimeout(resolve, ms);
		});
	}

	function blobToBase64(blob) {
		return new Promise(function (resolve, reject) {
			var reader = new FileReader();
			reader.onload = function () {
				resolve(reader.result);
			};
			reader.onerror = reject;
			reader.readAsDataURL(blob);
		});
	}

	function upload(path, dataUrl) {
		return fetch(WP_SITE.replace(/\/$/, '') + '/wp-json/wcbc/v1/cache-image', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WCBC-Import-Nonce': NONCE,
			},
			body: JSON.stringify({ path: path, data: dataUrl }),
		}).then(function (r) {
			return r.json();
		});
	}

	var index = 0;
	var ok = 0;
	var fail = 0;

	function next() {
		if (index >= FILES.length) {
			console.log('Import complete. OK:', ok, 'Failed:', fail);
			console.log('Reload your product page — preview should use real Happy Beds images.');
			return Promise.resolve();
		}

		var path = FILES[index++];
		return fetch(CDN + path, { credentials: 'same-origin' })
			.then(function (r) {
				if (!r.ok) {
					throw new Error('HTTP ' + r.status);
				}
				return r.blob();
			})
			.then(blobToBase64)
			.then(function (dataUrl) {
				return upload(path, dataUrl);
			})
			.then(function (res) {
				if (res && res.ok) {
					ok++;
					console.log('Saved', path);
				} else {
					fail++;
					console.warn('Upload failed', path, res);
				}
			})
			.catch(function (err) {
				fail++;
				console.warn('Failed', path, err.message);
			})
			.then(function () {
				return sleep(100);
			})
			.then(next);
	}

	console.log('Importing', FILES.length, 'Happy Beds images to', WP_SITE);
	return next();
})();
