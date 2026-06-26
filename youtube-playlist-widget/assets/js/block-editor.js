( function ( blocks, blockEditor, components, element, i18n ) {
	'use strict';

	var registerBlockType = blocks.registerBlockType;
	var createElement = element.createElement;
	var __ = i18n.__;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var MediaUpload = blockEditor.MediaUpload;
	var MediaUploadCheck = blockEditor.MediaUploadCheck;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var TextareaControl = components.TextareaControl;
	var Button = components.Button;
	var SelectControl = components.SelectControl;
	var ToggleControl = components.ToggleControl;

	var defaults = window.ypwBlockDefaults || {};

	var FONT_OPTIONS = [
		{
			label: __( 'Baloo', 'youtube-playlist-widget' ),
			value: '"Baloo 2", "Arial Rounded MT Bold", Arial, sans-serif',
		},
		{
			label: __( 'Shadow', 'youtube-playlist-widget' ),
			value: '"Shadows Into Light", "Comic Sans MS", cursive',
		},
		{
			label: __( 'System Sans', 'youtube-playlist-widget' ),
			value: 'Arial, Helvetica, sans-serif',
		},
		{
			label: __( 'Serif', 'youtube-playlist-widget' ),
			value: 'Georgia, "Times New Roman", serif',
		},
		{
			label: __( 'Use custom value below', 'youtube-playlist-widget' ),
			value: 'custom',
		},
	];

	function getPlaylistIdFromUrl( url ) {
		var parser;
		var pathMatch;

		if ( ! url ) {
			return '';
		}

		try {
			parser = new URL( url );

			if ( parser.searchParams.get( 'list' ) ) {
				return parser.searchParams.get( 'list' ).replace( /[^A-Za-z0-9_-]/g, '' );
			}

			pathMatch = parser.pathname.match( /\/playlist\/([A-Za-z0-9_-]+)/ );
			if ( pathMatch ) {
				return pathMatch[ 1 ];
			}
		} catch ( error ) {
			return '';
		}

		return '';
	}

	function getPlaylistUrl( attributes ) {
		var playlistId = attributes.playlistId || getPlaylistIdFromUrl( attributes.playlistUrl );

		if ( playlistId ) {
			return 'https://www.youtube.com/playlist?list=' + encodeURIComponent( playlistId );
		}

		return attributes.playlistUrl || 'https://www.youtube.com/';
	}

	function getStyle( attributes ) {
		return {
			'--ypw-background': attributes.backgroundColor || defaults.backgroundColor,
			'--ypw-content-background': attributes.contentColor || defaults.contentColor,
			'--ypw-title-color': attributes.titleColor || defaults.titleColor,
			'--ypw-text-color': attributes.textColor || defaults.textColor,
			'--ypw-accent-color': attributes.accentColor || defaults.accentColor,
			'--ypw-play-button-color': attributes.playButtonColor || defaults.playButtonColor,
			'--ypw-title-font-family': attributes.titleFontFamily || defaults.titleFontFamily,
			'--ypw-body-font-family': attributes.bodyFontFamily || defaults.bodyFontFamily,
			'--ypw-title-font-size': attributes.titleFontSize || defaults.titleFontSize,
			'--ypw-description-font-size': attributes.descriptionFontSize || defaults.descriptionFontSize,
		};
	}

	function previewPlayButton( maskId ) {
		return createElement(
			'span',
			{ className: 'ypw-play-button', 'aria-hidden': true },
			createElement(
				'svg',
				{ viewBox: '0 0 96 96', focusable: 'false', role: 'img', 'aria-hidden': true },
				createElement(
					'defs',
					null,
					createElement(
						'mask',
						{ id: maskId },
						createElement( 'rect', { width: '96', height: '96', fill: 'white' } ),
						createElement( 'path', { d: 'M40 31 L67 48 L40 65 Z', fill: 'black' } )
					)
				),
				createElement( 'circle', {
					cx: '48',
					cy: '48',
					r: '43',
					fill: 'currentColor',
					mask: 'url(#' + maskId + ')',
				} )
			)
		);
	}

	function WidgetPreview( props ) {
		var attributes = props.attributes;
		var maskId = 'ypw-editor-play-mask-' + props.clientId.replace( /[^A-Za-z0-9_-]/g, '' );
		var blockProps = useBlockProps( {
			className: 'ypw-widget ypw-layout-' + ( attributes.layout || 'split' ),
			style: getStyle( attributes ),
		} );
		var thumbnail = attributes.thumbnailUrl;
		var imageProps = thumbnail
			? {
					className: 'ypw-thumbnail',
					src: thumbnail,
					alt: attributes.title || __( 'Playlist thumbnail', 'youtube-playlist-widget' ),
			  }
			: null;

		if ( imageProps && 'auto' !== attributes.thumbnailLoading ) {
			imageProps.loading = attributes.thumbnailLoading;
		}

		return createElement(
			'section',
			blockProps,
			createElement(
				'a',
				{ className: 'ypw-media', href: getPlaylistUrl( attributes ), onClick: function ( event ) { event.preventDefault(); } },
				thumbnail
					? createElement( 'img', imageProps )
					: createElement( 'span', { className: 'ypw-thumbnail ypw-thumbnail-placeholder', 'aria-hidden': true } ),
				previewPlayButton( maskId )
			),
			createElement(
				'div',
				{ className: 'ypw-content' },
				attributes.title
					? createElement( 'h2', { className: 'ypw-title' }, attributes.title )
					: null,
				attributes.description
					? createElement( 'p', { className: 'ypw-description' }, attributes.description )
					: null,
				createElement( 'a', { className: 'ypw-cta', href: getPlaylistUrl( attributes ), onClick: function ( event ) { event.preventDefault(); } }, attributes.buttonText || defaults.buttonText )
			)
		);
	}

	function FontControls( props ) {
		var attributes = props.attributes;
		var setAttributes = props.setAttributes;
		var selectedTitlePreset = FONT_OPTIONS.some( function ( option ) {
			return option.value === attributes.titleFontFamily;
		} )
			? attributes.titleFontFamily
			: 'custom';
		var selectedBodyPreset = FONT_OPTIONS.some( function ( option ) {
			return option.value === attributes.bodyFontFamily;
		} )
			? attributes.bodyFontFamily
			: 'custom';

		return createElement(
			PanelBody,
			{ title: __( 'Typography', 'youtube-playlist-widget' ), initialOpen: false },
			createElement( SelectControl, {
				label: __( 'Title font preset', 'youtube-playlist-widget' ),
				value: selectedTitlePreset,
				options: FONT_OPTIONS,
				onChange: function ( value ) {
					if ( 'custom' !== value ) {
						setAttributes( { titleFontFamily: value } );
					}
				},
			} ),
			createElement( TextControl, {
				label: __( 'Title font family', 'youtube-playlist-widget' ),
				value: attributes.titleFontFamily,
				help: __( 'Example: "Baloo 2", Arial, sans-serif', 'youtube-playlist-widget' ),
				onChange: function ( value ) {
					setAttributes( { titleFontFamily: value } );
				},
			} ),
			createElement( TextControl, {
				label: __( 'Title font size', 'youtube-playlist-widget' ),
				value: attributes.titleFontSize,
				help: __( 'Accepts CSS values such as 56px, 4rem, or clamp(...).', 'youtube-playlist-widget' ),
				onChange: function ( value ) {
					setAttributes( { titleFontSize: value } );
				},
			} ),
			createElement( SelectControl, {
				label: __( 'Body font preset', 'youtube-playlist-widget' ),
				value: selectedBodyPreset,
				options: FONT_OPTIONS,
				onChange: function ( value ) {
					if ( 'custom' !== value ) {
						setAttributes( { bodyFontFamily: value } );
					}
				},
			} ),
			createElement( TextControl, {
				label: __( 'Body font family', 'youtube-playlist-widget' ),
				value: attributes.bodyFontFamily,
				onChange: function ( value ) {
					setAttributes( { bodyFontFamily: value } );
				},
			} ),
			createElement( TextControl, {
				label: __( 'Description font size', 'youtube-playlist-widget' ),
				value: attributes.descriptionFontSize,
				onChange: function ( value ) {
					setAttributes( { descriptionFontSize: value } );
				},
			} )
		);
	}

	function ColorControls( props ) {
		var attributes = props.attributes;
		var setAttributes = props.setAttributes;

		return createElement(
			PanelBody,
			{ title: __( 'Colors', 'youtube-playlist-widget' ), initialOpen: false },
			createElement( TextControl, {
				label: __( 'Outer background', 'youtube-playlist-widget' ),
				value: attributes.backgroundColor,
				onChange: function ( value ) {
					setAttributes( { backgroundColor: value } );
				},
			} ),
			createElement( TextControl, {
				label: __( 'Content background', 'youtube-playlist-widget' ),
				value: attributes.contentColor,
				onChange: function ( value ) {
					setAttributes( { contentColor: value } );
				},
			} ),
			createElement( TextControl, {
				label: __( 'Title color', 'youtube-playlist-widget' ),
				value: attributes.titleColor,
				onChange: function ( value ) {
					setAttributes( { titleColor: value } );
				},
			} ),
			createElement( TextControl, {
				label: __( 'Description color', 'youtube-playlist-widget' ),
				value: attributes.textColor,
				onChange: function ( value ) {
					setAttributes( { textColor: value } );
				},
			} ),
			createElement( TextControl, {
				label: __( 'Button/accent color', 'youtube-playlist-widget' ),
				value: attributes.accentColor,
				onChange: function ( value ) {
					setAttributes( { accentColor: value } );
				},
			} ),
			createElement( TextControl, {
				label: __( 'Play circle color', 'youtube-playlist-widget' ),
				value: attributes.playButtonColor,
				onChange: function ( value ) {
					setAttributes( { playButtonColor: value } );
				},
			} )
		);
	}

	registerBlockType( 'ypw/youtube-playlist-widget', {
		title: __( 'YouTube Playlist Widget', 'youtube-playlist-widget' ),
		description: __( 'Reusable configurable widget for linking to a YouTube playlist.', 'youtube-playlist-widget' ),
		category: 'media',
		icon: 'format-video',
		keywords: [
			__( 'youtube', 'youtube-playlist-widget' ),
			__( 'playlist', 'youtube-playlist-widget' ),
			__( 'widget', 'youtube-playlist-widget' ),
		],
		attributes: {
			title: { type: 'string', default: defaults.title },
			description: { type: 'string', default: defaults.description },
			playlistUrl: { type: 'string', default: defaults.playlistUrl },
			playlistId: { type: 'string', default: defaults.playlistId },
			thumbnailId: { type: 'number', default: defaults.thumbnailId },
			thumbnailUrl: { type: 'string', default: defaults.thumbnailUrl },
			thumbnailLoading: { type: 'string', default: defaults.thumbnailLoading },
			backgroundColor: { type: 'string', default: defaults.backgroundColor },
			contentColor: { type: 'string', default: defaults.contentColor },
			titleColor: { type: 'string', default: defaults.titleColor },
			textColor: { type: 'string', default: defaults.textColor },
			accentColor: { type: 'string', default: defaults.accentColor },
			playButtonColor: { type: 'string', default: defaults.playButtonColor },
			titleFontFamily: { type: 'string', default: defaults.titleFontFamily },
			bodyFontFamily: { type: 'string', default: defaults.bodyFontFamily },
			titleFontSize: { type: 'string', default: defaults.titleFontSize },
			descriptionFontSize: { type: 'string', default: defaults.descriptionFontSize },
			buttonText: { type: 'string', default: defaults.buttonText },
			layout: { type: 'string', default: defaults.layout },
			openInNewTab: { type: 'boolean', default: defaults.openInNewTab },
		},
		supports: {
			align: [ 'wide', 'full' ],
			html: false,
		},
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;

			return createElement(
				element.Fragment,
				null,
				createElement(
					InspectorControls,
					null,
					createElement(
						PanelBody,
						{ title: __( 'Content', 'youtube-playlist-widget' ), initialOpen: true },
						createElement( TextControl, {
							label: __( 'Title', 'youtube-playlist-widget' ),
							value: attributes.title,
							onChange: function ( value ) {
								setAttributes( { title: value } );
							},
						} ),
						createElement( TextareaControl, {
							label: __( 'Description/Text', 'youtube-playlist-widget' ),
							value: attributes.description,
							onChange: function ( value ) {
								setAttributes( { description: value } );
							},
						} ),
						createElement( TextControl, {
							label: __( 'Button text', 'youtube-playlist-widget' ),
							value: attributes.buttonText,
							onChange: function ( value ) {
								setAttributes( { buttonText: value } );
							},
						} ),
						createElement( TextControl, {
							label: __( 'YouTube playlist URL', 'youtube-playlist-widget' ),
							value: attributes.playlistUrl,
							help: __( 'Paste a playlist URL; the block will also accept a separate playlist ID.', 'youtube-playlist-widget' ),
							onChange: function ( value ) {
								var extractedPlaylistId = getPlaylistIdFromUrl( value );
								setAttributes( {
									playlistUrl: value,
									playlistId: extractedPlaylistId || attributes.playlistId,
								} );
							},
						} ),
						createElement( TextControl, {
							label: __( 'YouTube playlist ID', 'youtube-playlist-widget' ),
							value: attributes.playlistId,
							onChange: function ( value ) {
								setAttributes( { playlistId: value.replace( /[^A-Za-z0-9_-]/g, '' ) } );
							},
						} ),
						createElement( SelectControl, {
							label: __( 'Layout', 'youtube-playlist-widget' ),
							value: attributes.layout,
							options: [
								{ label: __( 'Version 6 split layout', 'youtube-playlist-widget' ), value: 'split' },
								{ label: __( 'Stacked', 'youtube-playlist-widget' ), value: 'stacked' },
							],
							onChange: function ( value ) {
								setAttributes( { layout: value } );
							},
						} ),
						createElement( ToggleControl, {
							label: __( 'Open playlist in a new tab', 'youtube-playlist-widget' ),
							checked: attributes.openInNewTab,
							onChange: function ( value ) {
								setAttributes( { openInNewTab: value } );
							},
						} )
					),
					createElement(
						PanelBody,
						{ title: __( 'Thumbnail/Image', 'youtube-playlist-widget' ), initialOpen: false },
						createElement(
							MediaUploadCheck,
							null,
							createElement( MediaUpload, {
								onSelect: function ( media ) {
									setAttributes( {
										thumbnailId: media.id,
										thumbnailUrl: media.url,
									} );
								},
								allowedTypes: [ 'image' ],
								value: attributes.thumbnailId,
								render: function ( mediaUploadProps ) {
									return createElement(
										Button,
										{ variant: 'secondary', onClick: mediaUploadProps.open },
										attributes.thumbnailUrl
											? __( 'Replace thumbnail', 'youtube-playlist-widget' )
											: __( 'Choose thumbnail', 'youtube-playlist-widget' )
									);
								},
							} )
						),
						attributes.thumbnailUrl
							? createElement(
									Button,
									{
										variant: 'link',
										isDestructive: true,
										onClick: function () {
											setAttributes( { thumbnailId: 0, thumbnailUrl: '' } );
										},
									},
									__( 'Remove thumbnail', 'youtube-playlist-widget' )
							  )
							: null,
						createElement( TextControl, {
							label: __( 'Thumbnail URL', 'youtube-playlist-widget' ),
							value: attributes.thumbnailUrl,
							onChange: function ( value ) {
								setAttributes( { thumbnailId: 0, thumbnailUrl: value } );
							},
						} ),
						createElement( SelectControl, {
							label: __( 'Thumbnail loading', 'youtube-playlist-widget' ),
							value: attributes.thumbnailLoading,
							help: __( 'Use eager only when this widget image is visible near the top of the page.', 'youtube-playlist-widget' ),
							options: [
								{ label: __( 'Auto', 'youtube-playlist-widget' ), value: 'auto' },
								{ label: __( 'Lazy', 'youtube-playlist-widget' ), value: 'lazy' },
								{ label: __( 'Eager / high priority', 'youtube-playlist-widget' ), value: 'eager' },
							],
							onChange: function ( value ) {
								setAttributes( { thumbnailLoading: value } );
							},
						} )
					),
					createElement( ColorControls, { attributes: attributes, setAttributes: setAttributes } ),
					createElement( FontControls, { attributes: attributes, setAttributes: setAttributes } )
				),
				createElement( WidgetPreview, {
					attributes: attributes,
					clientId: props.clientId,
				} )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n );
