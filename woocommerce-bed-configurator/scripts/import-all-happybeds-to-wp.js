/**
 * FULL import — builds CDN paths locally (no Happy Beds API) then uploads to WordPress.
 * Run on https://www.happybeds.co.uk/build-your-own-bed
 * Copy from WordPress admin → Bed Configurator → "Full import script"
 */
(function importAllHappyBedsToWordPress() {
	'use strict';

	var config = window.wcbcImportConfig || {};
	var WP_SITE = config.site || '';
	var TOKEN = config.token || '';
	var hb = config.happyBeds || {};

	if (!WP_SITE || !TOKEN) {
		console.error('Copy the FULL script from WordPress admin (Bed Configurator tab).');
		return;
	}

	var SIZES = ['small-single', 'single', 'small-double', 'double', 'king', 'super-king'];
	var COLOURS = config.colours || Object.keys(hb.colourMeta || {});
	var HEADBOARDS = ['cornell-plain', 'cornell-lined', 'cornell-buttoned', 'dudley-plain', 'victor-plain', 'no-headboard'];
	var DEPTHS = ['6-inch', '10-inch', '14-inch'];
	var STORAGE = ['no-drawers', 'ottoman', '2-drawers', '2-drawers-same-side', '4-drawers', 'end-drawer'];

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

	function addPath(set, rel) {
		if (!rel || rel.indexOf('FFFFFF-0') !== -1) return;
		set[rel.replace(/^\//, '')] = true;
	}

	function normalizeStorage(storage) {
		var map = {
			'2-drawers-same-side': '2-drawers',
			'2-drawers-with-end-drawer': '2-drawers',
			'2-drawers-with-2-mini-drawers': '2-drawers',
			'end-drawer-with-2-mini-drawers': 'end-drawer',
		};
		return map[storage] || storage;
	}

	function storageBackFromFront(storageFrontRelative) {
		if (!storageFrontRelative) return null;
		return storageFrontRelative.replace(/4ft6/g, '4ft').replace(/_front_/g, '_front_left_');
	}

	function buildRelativePaths(selections) {
		var paths = [];
		var size = selections.size;
		var colour = selections.colour;
		var headboard = selections.headboard;
		var baseDepth = selections.base_depth;
		var storage = normalizeStorage(selections.storage);

		var sizeCode = sizeCodes[size] || '4ft6';
		var depthCode = baseDepth.replace('-inch', 'i');
		var meta = colourMeta[colour] || colourMeta['beige-velvet'] || { hb_fabric: 'velvet', code: '30', drawer: '190' };
		var hbStyle = headboardMap[headboard];
		var suffix = depthCode + meta.code;
		var pathsFabric = fabricPaths(meta.hb_fabric || meta.fabric);
		var drawerFolder = pathsFabric.drawer;
		var hbFolder = pathsFabric.headboard;
		var drawerRef = drawerRefForSize(sizeCode, meta.drawer);

		paths.push('new_shadow/shadow_wrk_' + sizeCode + '.jpg');
		paths.push('legs/bedding_legs_' + sizeCode + '.png');
		paths.push('bases/' + pathsFabric.base + '/bedbase_' + sizeCode + '_' + depthCode + '_' + meta.code + '.png');

		if (storage === 'ottoman') {
			paths.push('bases/' + pathsFabric.base + '/ottoman_open/bedbase_' + sizeCode + '_' + depthCode + '_' + meta.code + '.png');
			if (sizeCode === '4ft6') paths.push('legs/hb_legs_4ft6_ottoman.png');
			if (sizeCode === '5ft') paths.push('legs/hb_legs_5ft_ottoman.png');
			if (sizeCode === '6ft') paths.push('legs/hb_legs_6ft_ottoman.png');
		}

		if (hbStyle) {
			paths.push(hbFolder + '/' + hbStyle + '_' + sizeCode + '_' + suffix + '.png');
		}

		if (storage === '2-drawers' || storage === 'end-drawer') {
			var frontRel = drawerFrontPath(drawerFolder, sizeCode, drawerRef, suffix);
			paths.push(drawerBackPath(drawerFolder, sizeCode, drawerRef, suffix));
			paths.push(frontRel);
			var backRel = storageBackFromFront(frontRel);
			if (backRel) paths.push(backRel);
		} else if (storage === '4-drawers') {
			var ref = drawerRef === null ? sizeCode : drawerRef;
			var storage3Rel;
			paths.push(drawerBackPath(drawerFolder, sizeCode, drawerRef, suffix));
			paths.push(drawerFrontPath(drawerFolder, sizeCode, drawerRef, suffix));
			if (drawerRef === null) {
				paths.push(drawerFolder + '/reference_drawer_jumbo_front_' + sizeCode + '_drawer_jumbo_front_' + suffix + '.png');
				storage3Rel = drawerFolder + '/reference_drawer_jumbo_back_' + sizeCode + '_drawer_jumbo_front_' + suffix + '.png';
				paths.push(storage3Rel);
			} else {
				paths.push(drawerFolder + '/reference_drawer_jumbo_front_' + ref + '_' + sizeCode + '_drawer_jumbo_front_' + suffix + '.png');
				storage3Rel = drawerFolder + '/reference_drawer_jumbo_back_' + ref + '_' + sizeCode + '_drawer_jumbo_front_' + suffix + '.png';
				paths.push(storage3Rel);
			}
			backRel = storageBackFromFront(storage3Rel);
			if (backRel) paths.push(backRel);
		}

		return paths;
	}

	function collectUniquePaths() {
		var unique = {};
		SIZES.forEach(function (size) {
			COLOURS.forEach(function (colour) {
				HEADBOARDS.forEach(function (headboard) {
					DEPTHS.forEach(function (depth) {
						STORAGE.forEach(function (storage) {
							buildRelativePaths({
								size: size,
								colour: colour,
								headboard: headboard,
								base_depth: depth,
								storage: storage,
							}).forEach(function (rel) {
								addPath(unique, rel);
							});
						});
					});
				});
			});
		});
		addPath(unique, 'FFFFFF-0.png');
		return Object.keys(unique);
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
		var base = WP_SITE.replace(/\/$/, '') + '/wp-json/wcbc/v1/cache-image';
		var url = base + '?wcbc_import_token=' + encodeURIComponent(TOKEN);
		return fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WCBC-Import-Token': TOKEN,
			},
			body: JSON.stringify({ path: path, data: dataUrl, token: TOKEN }),
		}).then(function (r) { return r.json(); });
	}

	var fileList = collectUniquePaths();
	var ok = 0;
	var fail = 0;
	var skip = 0;
	var i = 0;

	console.log('Full import: downloading', fileList.length, 'unique CDN images (no API calls)…');
	console.log('Upload target:', WP_SITE);

	function downloadNext() {
		if (i >= fileList.length) {
			console.log('FULL import complete. OK:', ok, 'Skipped (404):', skip, 'Failed:', fail);
			console.log('Reload your product page — colour/size/headboard variations should now update.');
			return Promise.resolve();
		}

		var path = fileList[i++];
		return fetch('/media/new_configurator/' + path, { credentials: 'same-origin' })
			.then(function (r) {
				if (r.status === 404) {
					skip++;
					return null;
				}
				if (!r.ok) throw new Error('HTTP ' + r.status);
				return r.blob();
			})
			.then(function (blob) {
				if (!blob) return null;
				return blobToBase64(blob).then(function (dataUrl) {
					return upload(path, dataUrl);
				});
			})
			.then(function (res) {
				if (res === null) return;
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
			.then(function () { return sleep(60); })
			.then(downloadNext);
	}

	return downloadNext();
})();
