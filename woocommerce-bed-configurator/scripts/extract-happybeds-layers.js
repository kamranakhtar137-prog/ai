/**
 * Happy Beds layer extractor — run in DevTools Console on:
 * https://www.happybeds.co.uk/build-your-own-bed
 *
 * 1. Open the page and wait for it to fully load.
 * 2. Open DevTools → Console, paste this entire file, press Enter.
 * 3. It fetches layer filenames from Happy Beds API, downloads PNGs, and saves manifest.json.
 */
(function extractHappyBedsLayers() {
	'use strict';

	var SIZE_MAP = {
		'small-single': 'small-single-2ft-6',
		single: 'single-3ft',
		'small-double': 'small-double-4ft',
		double: 'double-4ft-6',
		king: 'king-5ft',
		'super-king': 'superking-6ft',
	};

	var SIZES = Object.keys(SIZE_MAP);
	var COLOURS = [
		'beige-velvet',
		'black-velvet',
		'graphite-velvet',
		'cream-cotton',
		'midnight-blue-cotton',
	];
	var HEADBOARDS = [
		'cornell-plain',
		'cornell-lined',
		'cornell-buttoned',
		'dudley-plain',
		'victor-plain',
		'no-headboard',
	];
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
			var storageBack = storage3.replace('4ft6', '4ft').replace('_front_', '_front_left_');
			layers.storage_back = storageBack;
		} else if (storage1 && urlStorage === '2-drawers-with-2-mini-drawers') {
			var back = storage1.replace('4ft6', '4ft').replace('_front_', '_front_left_');
			layers.storage_back = back;
		}

		if (layers.headboard === 'no_headboard.png' || params.headboard === 'no-headboard') {
			layers.headboard = 'FFFFFF-0.png';
		}

		return layers;
	}

	function comboKey(local) {
		return [local.size, local.colour, local.headboard, local.depth, local.storage, local.ds].join('|');
	}

	function fetchLayers(apiParams) {
		var q = new URLSearchParams(apiParams);
		return fetch('/ev_bespokebeds/bespoke/image?' + q.toString(), {
			credentials: 'same-origin',
			headers: { Accept: 'application/json' },
		})
			.then(function (r) {
				return r.json();
			})
			.then(function (data) {
				if (!data || data.status === 'ERROR' || !data.image_result) {
					throw new Error('API error for ' + q.toString());
				}
				return JSON.parse(data.image_result);
			});
	}

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

	function downloadText(filename, text) {
		downloadBlob(filename, new Blob([text], { type: 'application/json' }));
	}

	function fileUrl(relativePath) {
		if (!relativePath || relativePath.indexOf('FFFFFF-0') !== -1) {
			return null;
		}
		if (relativePath.indexOf('/') === 0) {
			return relativePath;
		}
		return '/media/new_configurator/' + relativePath.replace(/^\/+/, '');
	}

	var manifest = {
		source: 'https://www.happybeds.co.uk/build-your-own-bed',
		extracted_at: new Date().toISOString(),
		combinations: {},
		files: {},
	};

	var uniqueFiles = {};
	var MODE = (window.wcbcHbExtractMode || 'plugin');

	function buildQueue() {
		var list = [];
		if (MODE === 'quick') {
			SIZES.forEach(function (size) {
				list.push({
					size: size,
					colour: 'beige-velvet',
					headboard: 'cornell-plain',
					depth: '14-inch',
					storage: 'no-drawers',
					ds: 1,
				});
			});
			return list;
		}

		SIZES.forEach(function (size) {
			COLOURS.forEach(function (colour) {
				HEADBOARDS.forEach(function (headboard) {
					DEPTHS.forEach(function (depth) {
						STORAGE.forEach(function (storage) {
							list.push({
								size: size,
								colour: colour,
								headboard: headboard,
								depth: depth,
								storage: storage,
								ds: 1,
							});
							if (storage === 'ottoman') {
								list.push({
									size: size,
									colour: colour,
									headboard: headboard,
									depth: depth,
									storage: storage,
									ds: 0,
								});
							}
						});
					});
				});
			});
		});
		return list;
	}

	var queue = buildQueue();
	var total = queue.length;
	console.log('Mode:', MODE, '—', total, 'combinations to fetch');
	console.log('Quick test: wcbcHbExtractMode="quick"; then paste script again.');

	var index = 0;

	function next() {
		if (index >= queue.length) {
			return finish();
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
				manifest.combinations[comboKey(local)] = layers;
				Object.keys(layers).forEach(function (layer) {
					var rel = layers[layer];
					if (rel && rel.indexOf('FFFFFF-0') === -1) {
						uniqueFiles[rel] = fileUrl(rel);
					}
				});
				if (index % 25 === 0 || index === total) {
					console.log('Fetched ' + index + ' / ' + total);
				}
			})
			.catch(function (err) {
				console.warn('Skip', local, err.message);
			})
			.then(function () {
				return sleep(120);
			})
			.then(next);
	}

	function finish() {
		manifest.files = uniqueFiles;
		var fileList = Object.keys(uniqueFiles);
		console.log('Unique layer files:', fileList.length);
		downloadText('happybeds-manifest.json', JSON.stringify(manifest, null, 2));

		var downloaded = 0;
		function dlNext(i) {
			if (i >= fileList.length) {
				console.log('Done. Import happybeds-manifest.json + downloaded PNGs into demo-images/happybeds-layers/');
				console.log('Then run: python3 scripts/import-happybeds-layers.py');
				return Promise.resolve();
			}
			var rel = fileList[i];
			var url = uniqueFiles[rel];
			return fetch(url, { credentials: 'same-origin' })
				.then(function (r) {
					return r.blob();
				})
				.then(function (blob) {
					var safeName = rel.replace(/\//g, '__');
					downloadBlob(safeName, blob);
					downloaded++;
					if (downloaded % 20 === 0) {
						console.log('Downloaded ' + downloaded + ' / ' + fileList.length);
					}
				})
				.catch(function (e) {
					console.warn('Failed', rel, e.message);
				})
				.then(function () {
					return sleep(80);
				})
				.then(function () {
					return dlNext(i + 1);
				});
		}

		return dlNext(0);
	}

	return next();
})();
