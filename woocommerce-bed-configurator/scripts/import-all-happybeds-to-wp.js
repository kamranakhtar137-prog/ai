/**
 * FULL import — all size/colour/headboard/depth/storage variations.
 * Run on https://www.happybeds.co.uk/build-your-own-bed
 * Copy from WordPress admin → Bed Configurator → "Full import script"
 */
(function importAllHappyBedsToWordPress() {
	'use strict';

	var config = window.wcbcImportConfig || {};
	var WP_SITE = config.site || '';
	var TOKEN = config.token || '';

	if (!WP_SITE || !TOKEN) {
		console.error('Copy the FULL script from WordPress admin (Bed Configurator tab).');
		return;
	}

	var SIZE_MAP = {
		'small-single': 'small-single-2ft-6',
		single: 'single-3ft',
		'small-double': 'small-double-4ft',
		double: 'double-4ft-6',
		king: 'king-5ft',
		'super-king': 'superking-6ft',
	};

	var SIZES = Object.keys(SIZE_MAP);
	var COLOURS = config.colours || [
		'light-silver-velvet', 'asphalt-velvet', 'graphite-velvet', 'black-velvet', 'blue-marine-velvet',
		'emerald-velvet', 'duck-egg-blue-velvet', 'pink-velvet', 'beige-velvet', 'mustard-velvet',
		'black-linen', 'charcoal-linen', 'chocolate-linen', 'cream-linen', 'duck-egg-blue-linen',
		'lime-linen', 'midnight-blue-linen', 'orchid-linen', 'plum-linen', 'red-linen',
		'slate-grey-linen', 'white-linen', 'silver-grey-linen',
	];
	var HEADBOARDS = ['cornell-plain', 'cornell-lined', 'cornell-buttoned', 'dudley-plain', 'victor-plain', 'no-headboard'];
	var DEPTHS = ['6-inch', '10-inch', '14-inch'];
	var STORAGE = ['no-drawers', 'ottoman', '2-drawers', '4-drawers', 'end-drawer'];

	function styleFromHeadboard(headboard) {
		if (headboard.indexOf('cornell') === 0) return 'CORNELL';
		if (headboard.indexOf('dudley') === 0) return 'DUDLEY';
		if (headboard.indexOf('victor') === 0) return 'VICTOR';
		return 'NOHEADBOARD';
	}

	function resolvePaths(obj, params) {
		var baseImage = obj.base_image;
		var legsImage = obj.legs_image;
		var shadowImage = obj.shadow_image;
		var headboardImage = obj.headboard_image;
		var storage1 = obj.storage1;
		var storage2 = obj.storage2;
		var storage3 = obj.storage3;
		var urlStorage = params.storage;
		var urlSize = params.size;
		var drawState = params.ds;

		if (urlStorage === 'ottoman' && drawState === 0) {
			baseImage = baseImage
				.replace('linoso', 'linoso/ottoman_open')
				.replace('suede', 'suede/ottoman_open')
				.replace('velvet', 'velvet/ottoman_open');
		}

		if (urlStorage === 'ottoman') {
			if (urlSize === 'double-4ft-6' || urlSize === "double-4ft-6''") {
				legsImage = 'hb_legs_4ft6_ottoman.png';
			} else if (urlSize === 'king-5ft' || urlSize === 'king-size-5ft') {
				legsImage = 'hb_legs_5ft_ottoman.png';
			} else if (urlSize === 'superking-6ft' || urlSize === 'super-kingsize-6ft') {
				legsImage = 'hb_legs_6ft_ottoman.png';
			}
		}

		var layers = {
			shadow: 'new_shadow/' + shadowImage,
			legs: 'legs/' + legsImage,
			storage_back: 'FFFFFF-0.png',
			base: 'bases/' + baseImage + '.png',
			headboard: headboardImage ? headboardImage + '.png' : 'FFFFFF-0.png',
			storage_1: storage1 || 'FFFFFF-0.png',
			storage_2: storage2 || 'FFFFFF-0.png',
			storage_3: storage3 || 'FFFFFF-0.png',
		};

		if (storage3 && (urlStorage === '4-drawers' || urlStorage === '2-drawers')) {
			layers.storage_back = storage3.replace('4ft6', '4ft').replace('_front_', '_front_left_');
		}

		if (layers.headboard === 'no_headboard.png' || params.headboard === 'no-headboard') {
			layers.headboard = 'FFFFFF-0.png';
		}

		return layers;
	}

	function fetchLayers(apiParams) {
		var q = new URLSearchParams(apiParams);
		return fetch('/ev_bespokebeds/bespoke/image?' + q.toString(), {
			credentials: 'same-origin',
			headers: { Accept: 'application/json' },
		})
			.then(function (r) { return r.json(); })
			.then(function (data) {
				if (!data || data.status === 'ERROR' || !data.image_result) {
					throw new Error('API error');
				}
				return JSON.parse(data.image_result);
			});
	}

	function sleep(ms) {
		return new Promise(function (resolve) { setTimeout(resolve, ms); });
	}

	function blobToBase64(blob) {
		return new Promise(function (resolve, reject) {
			var reader = new FileReader();
			reader.onload = function () { resolve(reader.result); };
			reader.onerror = reject;
			reader.readAsDataURL(blob);
		});
	}

	function upload(path, dataUrl) {
		return fetch(WP_SITE.replace(/\/$/, '') + '/wp-json/wcbc/v1/cache-image', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WCBC-Import-Token': TOKEN,
			},
			body: JSON.stringify({ path: path, data: dataUrl }),
		}).then(function (r) { return r.json(); });
	}

	function buildQueue() {
		var list = [];
		SIZES.forEach(function (size) {
			COLOURS.forEach(function (colour) {
				HEADBOARDS.forEach(function (headboard) {
					DEPTHS.forEach(function (depth) {
						STORAGE.forEach(function (storage) {
							list.push({ size: size, colour: colour, headboard: headboard, depth: depth, storage: storage, ds: 1 });
							if (storage === 'ottoman') {
								list.push({ size: size, colour: colour, headboard: headboard, depth: depth, storage: storage, ds: 0 });
							}
						});
					});
				});
			});
		});
		return list;
	}

	var uniqueFiles = {};
	var queue = buildQueue();
	var total = queue.length;
	var index = 0;

	console.log('Step 1/2: Fetching', total, 'combinations from Happy Beds API…');

	function fetchNext() {
		if (index >= queue.length) {
			return uploadAll();
		}

		var local = queue[index++];
		var apiSize = SIZE_MAP[local.size];
		var apiParams = {
			size: apiSize,
			style: styleFromHeadboard(local.headboard),
			colour: local.colour,
			base: local.depth,
			headboard: local.headboard,
			storage: local.storage,
			ds: String(local.ds),
			is_divan: '1',
		};

		return fetchLayers(apiParams)
			.then(function (obj) {
				var layers = resolvePaths(obj, {
					size: apiSize,
					storage: local.storage,
					headboard: local.headboard,
					ds: local.ds,
				});
				Object.keys(layers).forEach(function (layer) {
					var rel = layers[layer];
					if (rel && rel.indexOf('FFFFFF-0') === -1) {
						uniqueFiles[rel] = true;
					}
				});
				if (index % 50 === 0 || index === total) {
					console.log('API progress:', index, '/', total, '— unique files:', Object.keys(uniqueFiles).length);
				}
			})
			.catch(function () { /* skip failed combo */ })
			.then(function () { return sleep(100); })
			.then(fetchNext);
	}

	function uploadAll() {
		var fileList = Object.keys(uniqueFiles);
		var ok = 0;
		var fail = 0;
		var i = 0;

		console.log('Step 2/2: Uploading', fileList.length, 'unique images to', WP_SITE);

		function uploadNext() {
			if (i >= fileList.length) {
				console.log('FULL import complete. OK:', ok, 'Failed:', fail);
				console.log('Reload your product page — all colour/size/headboard variations should now update.');
				return Promise.resolve();
			}

			var path = fileList[i++];
			return fetch('/media/new_configurator/' + path, { credentials: 'same-origin' })
				.then(function (r) {
					if (!r.ok) throw new Error('HTTP ' + r.status);
					return r.blob();
				})
				.then(blobToBase64)
				.then(function (dataUrl) { return upload(path, dataUrl); })
				.then(function (res) {
					if (res && res.ok) {
						ok++;
						if (ok % 25 === 0) console.log('Uploaded', ok, '/', fileList.length);
					} else {
						fail++;
						console.warn('Upload failed', path, res);
					}
				})
				.catch(function (err) {
					fail++;
					console.warn('Failed', path, err.message);
				})
				.then(function () { return sleep(80); })
				.then(uploadNext);
		}

		return uploadNext();
	}

	return fetchNext();
})();
