/**
 * CORE import — Bed Legs, Headboard, Storage Back, Base for every size × colour × base depth.
 * Fixed: Cornell Lined headboard, 2 drawers same side (for storage back).
 * Run on https://www.happybeds.co.uk/build-your-own-bed
 */
(function importCoreLayersToWordPress() {
	'use strict';

	var config = window.wcbcImportConfig || {};
	var WP_SITE = config.site || '';
	var TOKEN = config.token || '';
	var hb = config.happyBeds || {};

	if (!WP_SITE || !TOKEN) {
		console.error('Copy the CORE import script from WordPress admin (Bed Configurator tab).');
		return;
	}

	var SIZES = ['small-single', 'single', 'small-double', 'double', 'king', 'super-king'];
	var COLOURS = config.colours || Object.keys(hb.colourMeta || {});
	var DEPTHS = ['6-inch', '10-inch', '14-inch'];
	var HEADBOARD = 'cornell-lined';
	var STORAGE = '2-drawers-same-side';

	var sizeCodes = hb.sizeCodes || {
		'small-single': '3ft',
		single: '3ft',
		'small-double': '4ft6',
		double: '4ft6',
		king: '5ft',
		'super-king': '6ft',
	};
	var colourMeta = hb.colourMeta || {};
	var headboardMap = hb.headboards || {};
	var fabricPathMap = hb.fabricPaths || {};

	function fabricPaths(hbFabric) {
		return fabricPathMap[hbFabric] || fabricPathMap.velvet || {
			base: 'velvet',
			headboard: 'headboards_velvet',
			drawer: 'drawers_velvet',
		};
	}

	function drawerRefForSize(sizeCode, drawerCode) {
		if (sizeCode === '3ft') return null;
		if (sizeCode === '5ft' || sizeCode === '6ft') return '200';
		return drawerCode;
	}

	function drawerBackPath(folder, sizeCode, drawerRef, suffix) {
		if (drawerRef === null) {
			return folder + '/reference_drawer_normal_back_' + sizeCode + '_drawer_normal_front_' + suffix + '.png';
		}
		return folder + '/reference_drawer_normal_back_' + drawerRef + '_' + sizeCode + '_drawer_normal_front_' + suffix + '.png';
	}

	function drawerFrontPath(folder, sizeCode, drawerRef, suffix) {
		if (drawerRef === null) {
			return folder + '/reference_drawer_normal_front_' + sizeCode + '_drawer_normal_front_' + suffix + '.png';
		}
		return folder + '/reference_drawer_normal_front_' + drawerRef + '_' + sizeCode + '_drawer_normal_front_' + suffix + '.png';
	}

	function storageBackFromFront(storageFrontRelative) {
		if (!storageFrontRelative) return null;
		return storageFrontRelative.replace(/4ft6/g, '4ft').replace(/_front_/g, '_front_left_');
	}

	function addPath(set, rel) {
		if (!rel || rel.indexOf('FFFFFF-0') !== -1) return;
		set[rel.replace(/^\//, '')] = true;
	}

	function buildCorePaths(selections) {
		var paths = [];
		var size = selections.size;
		var colour = selections.colour;
		var baseDepth = selections.base_depth;

		var sizeCode = sizeCodes[size] || '4ft6';
		var depthCode = baseDepth.replace('-inch', 'i');
		var meta = colourMeta[colour] || colourMeta['beige-velvet'] || { hb_fabric: 'velvet', code: '30', drawer: '190' };
		var hbStyle = headboardMap[HEADBOARD];
		var suffix = depthCode + meta.code;
		var pathsFabric = fabricPaths(meta.hb_fabric || meta.fabric);
		var drawerFolder = pathsFabric.drawer;
		var hbFolder = pathsFabric.headboard;
		var drawerRef = drawerRefForSize(sizeCode, meta.drawer);

		// Bed Legs — varies by size
		paths.push('legs/bedding_legs_' + sizeCode + '.png');

		// Bed Base — size × depth × colour
		paths.push('bases/' + pathsFabric.base + '/bedbase_' + sizeCode + '_' + depthCode + '_' + meta.code + '.png');

		// Bed Headboard — style × size × depth × colour
		if (hbStyle) {
			paths.push(hbFolder + '/' + hbStyle + '_' + sizeCode + '_' + suffix + '.png');
		}

		// Bed Storage Back — size × depth × colour (from 2-drawer front path)
		var frontRel = drawerFrontPath(drawerFolder, sizeCode, drawerRef, suffix);
		paths.push(drawerBackPath(drawerFolder, sizeCode, drawerRef, suffix));
		paths.push(frontRel);
		var backRel = storageBackFromFront(frontRel);
		if (backRel) paths.push(backRel);

		return paths;
	}

	function collectUniquePaths() {
		var unique = {};
		SIZES.forEach(function (size) {
			COLOURS.forEach(function (colour) {
				DEPTHS.forEach(function (depth) {
					buildCorePaths({
						size: size,
						colour: colour,
						base_depth: depth,
					}).forEach(function (rel) {
						addPath(unique, rel);
					});
				});
			});
		});
		// Shadow used under all previews
		['3ft', '4ft6', '5ft', '6ft'].forEach(function (code) {
			addPath(unique, 'new_shadow/shadow_wrk_' + code + '.jpg');
		});
		addPath(unique, 'FFFFFF-0.png');
		return Object.keys(unique);
	}

	function sleep(ms) {
		return new Promise(function (resolve) {
			setTimeout(resolve, ms);
		});
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

	var CDN = 'https://www.happybeds.co.uk/media/new_configurator/';
	var FILES = collectUniquePaths();
	var index = 0;
	var ok = 0;
	var fail = 0;

	function next() {
		if (index >= FILES.length) {
			console.log('Core import complete. OK:', ok, 'Failed:', fail, 'Total:', FILES.length);
			console.log('Layers: Bed Legs, Bed Headboard, Bed Storage Back, Bed Base — all size × colour × base depth.');
			return Promise.resolve();
		}

		var path = FILES[index++];
		return fetch(CDN + path, { credentials: 'omit', mode: 'cors' })
			.then(function (r) {
				if (!r.ok) throw new Error('HTTP ' + r.status);
				return r.blob();
			})
			.then(blobToBase64)
			.then(function (dataUrl) { return upload(path, dataUrl); })
			.then(function (res) {
				if (res && res.ok) {
					ok++;
					if (ok % 50 === 0) console.log('Saved', ok, '/', FILES.length);
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
			.then(next);
	}

	console.log('Core import: downloading', FILES.length, 'unique CDN images (4 layers × size × colour × depth)…');
	return next();
})();
