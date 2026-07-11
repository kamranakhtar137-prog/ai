/**
 * Download Happy Beds layer files to your computer (run on happybeds.co.uk).
 *
 * 1. Open https://www.happybeds.co.uk/build-your-own-bed
 * 2. Paste this script in DevTools Console
 * 3. Copy downloaded files into:
 *    wp-content/plugins/woocommerce-bed-configurator/demo-images/happybeds-cache/
 *    keeping the same folder structure (new_shadow/, legs/, bases/, etc.)
 */
(function downloadHappyBedsCache() {
	'use strict';

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

	function downloadBlob(filename, blob) {
		var a = document.createElement('a');
		a.href = URL.createObjectURL(blob);
		a.download = filename;
		a.click();
		URL.revokeObjectURL(a.href);
	}

	var index = 0;
	function next() {
		if (index >= FILES.length) {
			console.log('Done. Copy files into demo-images/happybeds-cache/ preserving folders.');
			return Promise.resolve();
		}
		var path = FILES[index++];
		var flat = path.replace(/\//g, '__');
		return fetch(CDN + path, { credentials: 'same-origin' })
			.then(function (r) {
				return r.blob();
			})
			.then(function (blob) {
				downloadBlob(flat, blob);
				console.log('Downloaded', path, 'as', flat);
			})
			.catch(function (e) {
				console.warn('Failed', path, e.message);
			})
			.then(function () {
				return sleep(120);
			})
			.then(next);
	}

	console.log('Downloading', FILES.length, 'Happy Beds layer files…');
	return next();
})();
