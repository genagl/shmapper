/**
 * Leyka Blocks
 */

( function( blocks, editor, blockEditor, element, components, data, i18n, serverSideRender ) {

	const ServerSideRender = serverSideRender;

	const el = element.createElement;

	const { TextControl, TextareaControl, SelectControl, PanelBody, ToggleControl, BaseControl, Button, ButtonGroup, Disabled, Notice, ExternalLink, Dashicon, ToolbarGroup, ToolbarButton, __experimentalUnitControl, __experimentalBoxControl } = components;

	const { registerBlockType } = blocks;

	const { InspectorControls, BlockControls } = blockEditor;

	const { select } = data;

	const { Fragment } = element;

	const { __ } = i18n;

	// Register Block Shmapper Map
	function registerBlockShmapperMap(){

		let icon = el( 'svg',
			{
				width: 24,
				height: 24,
				fill: "none",
			},
			el( 'path',
				{
					d: "M11.9998 1.32628e-05C14.0556 -0.00337215 16.0699 0.641419 17.8061 1.85887C22.3183 5.02111 22.6507 11.5524 19.0553 16.3276C17.0034 19.0518 14.745 21.5776 12.3048 23.8776C12.2108 23.9646 12.0936 24.0052 11.9783 23.9995C11.9292 23.9972 11.8805 23.9862 11.8341 23.9669C11.7845 23.9466 11.7373 23.917 11.6949 23.8776C9.25447 21.5781 6.99607 19.0526 4.94444 16.3286C1.34985 11.5524 1.68121 5.02111 6.19372 1.85887C7.92989 0.641419 9.9439 -0.00337215 11.9998 1.32628e-05ZM8.60184 13.5198C8.66069 13.4521 8.73305 13.4026 8.81177 13.3716C8.99335 13.3003 9.20832 13.3279 9.36738 13.4615C10.8557 14.7128 13.1443 14.7128 14.6327 13.4615C14.8606 13.2701 15.2034 13.2961 15.3982 13.5198C15.593 13.7438 15.5665 14.0805 15.3388 14.2719C13.4439 15.8649 10.5562 15.8649 8.66123 14.2719C8.51596 14.1498 8.45261 13.9685 8.47623 13.795C8.48551 13.7279 8.50777 13.662 8.54354 13.6011C8.55999 13.5727 8.57959 13.5456 8.60184 13.5198ZM9.55717 6.4C9.55717 6.10549 9.31407 5.86667 9.01431 5.86667C8.71449 5.86667 8.47145 6.10549 8.47145 6.4V8H6.84287C6.66264 8 6.50304 8.08619 6.40419 8.21904C6.33872 8.30731 6.30002 8.41589 6.30002 8.53333C6.30002 8.82789 6.54305 9.06666 6.84287 9.06666H8.47145V10.6667C8.47145 10.8484 8.56395 11.0091 8.70547 11.1055C8.79325 11.1651 8.89955 11.2 9.01431 11.2C9.12353 11.2 9.22504 11.1683 9.31011 11.1138C9.4588 11.0188 9.55717 10.8539 9.55717 10.6667V9.06666H11.1857C11.3816 9.06666 11.5534 8.96485 11.6488 8.812C11.6994 8.73072 11.7286 8.63541 11.7286 8.53333C11.7286 8.23883 11.4855 8 11.1857 8H9.55717V6.4ZM15.5286 10.1333C16.428 10.1333 17.1572 9.41696 17.1572 8.53333C17.1572 7.64976 16.428 6.93333 15.5286 6.93333C14.6293 6.93333 13.9 7.64976 13.9 8.53333C13.9 9.41696 14.6293 10.1333 15.5286 10.1333Z",
					'fill-rule': "evenodd"
				}
			),
		);
		
		var getMaps = shmBlock.getMaps;
		var getMapKeys = shmBlock.getMapKeys;
		var defaultMapId = 0;
		if ( getMaps.length ) {
			defaultMapId = getMaps[0].ID;
		}

		let blockAttributes = {
			className: {
				type: 'string',
			},
			anchor: {
				type: 'string',
			},
			align: {
				type: 'string',
				default: '',
			},
			mapId: {
				type: 'string',
				default: defaultMapId,
			},
			preview: {
				type: 'boolean',
				default: false,
			},
			mapType: {
				type: 'string',
				default: 'classic',
			},
			minHeight: {
				type: 'string',
				default: '450px',
			},
			isForm: {
				type: 'boolean',
				default: false,
			},
			formWidth: {
				type: 'string',
				default: '',
			},
			formAlign: {
				type: 'string',
				default: 'left',
			},
			formSpacing: {
				type: 'object',
				default: {},
			},
		}

		// Register Block Type shmapper/map.
		registerBlockType( 'shmapper/map', {
			title: __( 'ShMapper Map', 'shmapper-by-teplitsa' ),
			description: __( 'Display customizable map block with markers.', 'shmapper-by-teplitsa' ),
			icon: {
				foreground: '#408Bfd',
				src: icon,
			},
			category: 'shmapper',
			keywords: [ 'shmapper', 'map', 'form' ],
			attributes: blockAttributes,

			supports: {
				align: [ 'wide', 'full' ],
				html: false,
				anchor: true,
			},

			example: {
				attributes: {
					'preview' : true,
				},
			},

			edit: function( props ) {

				const { attributes, className, setAttributes } = props;

				var maps = [];
				var currentMap = false;
				var currentMapId = 0;
				var currentMapTitle = '';

				if ( getMaps ) {
					getMaps.map((map) => {
						var mapTitle = map.post_title;
						var mapId = map.ID;
						if ( ! mapTitle ) {
							mapTitle = mapId;
						}
						maps.push( { label: mapTitle, value: mapId } );

						if ( mapId == attributes.mapId ) {
							currentMap = map;
							currentMapId = mapId;
							currentMapTitle = ': ' + mapTitle;
						}
						
					} )
				}

				var paddingBoxControl = function() {
					if ( ! attributes.isForm ) {
						return;
					}
					if ( attributes.mapType == 'classic' ) {
						return;
					}
					return el( BaseControl, null,
						el( __experimentalBoxControl, {
							label: __( 'Form External Spacing', 'shmapper-by-teplitsa' ),
							values: attributes.formSpacing,
							units: [
								{ value: 'px', label: 'px'},
							],
							onChange: ( val ) => {
								setAttributes( { formSpacing: val } );
							},
						}),
					)
				}

				var formShowControl = function( disabled = false ) {
					var labelSufix = '';
					if ( disabled ) {
						attributes.isForm = false;
						var labelSufix = ' (' + __( 'Not available', 'shmapper-by-teplitsa' ) + ')';
					}
					return el( BaseControl, null,
						el( ToggleControl,
							{
								label: __('Show Form', 'shmapper-by-teplitsa') + labelSufix,
								disabled: disabled,
								onChange: ( val ) => {
									setAttributes( { isForm: val } );
								},
								checked: attributes.isForm,
							}
						)
					)
				}

				var formAlignControl = function() {
					if ( ! attributes.isForm ) {
						return;
					}
					if ( attributes.mapType == 'classic' ) {
						return;
					}
					return el( BaseControl, null,
						el( SelectControl,
							{
								label: __( 'Form Align', 'shmapper-by-teplitsa' ),
								options : [
									{
										value: 'left',
										label: __( 'Left', 'shmapper-by-teplitsa' )
									},
									{
										value: 'center',
										label: __( 'Center', 'shmapper-by-teplitsa' ),
									},
									{
										value: 'right',
										label: __( 'Right', 'shmapper-by-teplitsa' ),
									}
								],

								value: attributes.formAlign,
								onChange: ( val ) => {
									setAttributes( { formAlign: val } );
								},
							},
						)
					)
				}

				var formWidthControl = function(){
					if ( ! attributes.isForm ) {
						return;
					}
					return el( BaseControl, null,
						el( __experimentalUnitControl,
							{
								label: __('Form Max Width', 'shmapper-by-teplitsa'),
								value: attributes.formWidth,
								onChange: ( val ) => {
									setAttributes( { formWidth: val } );
								},
								labelPosition: 'side',
								units: [
									{
										value: "px",
										label: "px",
									},
									{
										value: '%',
										label: '%',
									},
								]
							}
						),
					)
				}

				var formControls = function(){

					if ( ! currentMap['is_form'] ) {

						return el( Fragment, null,

							formShowControl( true ),

							el( 'div',
								{
									className: 'shm-editor-block__notice'
								},

								el( Notice,
									{
										status: 'warning',
										isDismissible: false,
									},
									__( 'The form is disabled, to enable it, go to the map editing page.', 'shmapper-by-teplitsa' ),
									el ( 'br' ),
									el( ExternalLink ,
										{
											href: 'post.php?post=' + currentMapId + '&action=edit#form_fields',
										},
										__( 'Edit map', 'shmapper-by-teplitsa' ),
									),
									'.',
								)
							)
						);

					} else {

						return el( Fragment, null,

							formShowControl(),

							formAlignControl(),

							formWidthControl(),

							paddingBoxControl(),

							// 	},
							// 	'8. Mobile First.'
							// ),
						)
					}
					
				}

				return (
					el( Fragment, null,

						el( BlockControls,
							{ key: 'controls' },
							el( ToolbarGroup,
								null,
								el( ToolbarButton, {
									icon: 'edit',
									label: __( 'Edit map', 'shmapper-by-teplitsa' ) + currentMapTitle,
									href: 'post.php?post=' + currentMapId + '&action=edit',
									target: '_blank'
								})
							),
						),

						el( InspectorControls, null,

							el( 'div',
								{
									className: 'shm-editor-block__create-new-link'
								},

								el( ExternalLink ,
									{
										href: 'edit.php?post_type=shm_map',
									},
									__( 'Configure or create a new map', 'shmapper-by-teplitsa' ),
								),
								'.',
							),

							el( 'div',
								{
									className: 'shm-editor-block__notice'
								},

								el( Notice,
									{
										status: 'warning',
										isDismissible: false,
									},
									__( 'In edit mode, the preview of the interactive map is not available.', 'shmapper-by-teplitsa' ),
									el( 'br' ),
									el( ExternalLink ,
										{
											href: select('core/editor').getEditedPostPreviewLink(),
										},
										__( 'Use the preview page for this', 'shmapper-by-teplitsa' ),
									),
									'.',
								),
							),

							el( PanelBody,
								{
									title: __( 'Map Options', 'shmapper-by-teplitsa' ),
									initialOpen: true,
								},

								el ( BaseControl, null,
									el( SelectControl,
										{
											label: __( 'Select Map', 'shmapper-by-teplitsa' ),
											options : maps,
											value: attributes.mapId,
											onChange: ( val ) => {
												setAttributes( { mapId: val } );
											},
										},
									),
								),

								el( SelectControl,
									{
										label: __( 'Map type', 'shmapper-by-teplitsa' ),
										options : [
											{
												value: 'classic',
												label: __( 'Classic', 'shmapper-by-teplitsa' )
											},
											{
												value: 'fullscreen',
												label: __( 'Full Screen', 'shmapper-by-teplitsa' ),
											}
										],

										value: attributes.mapType,
										onChange: ( val ) => {
											setAttributes( { mapType: val } );
											if ( ! attributes.align && val == 'fullscreen' ) {
												if ( val == 'fullscreen' ) {
													setAttributes( { align: 'full' } );
												}
											}
										},
									},
								),

								el( __experimentalUnitControl,
									{
										label: __('Map Min Height', 'shmapper-by-teplitsa'),
										shmapper-by-teplitsa
										shmapper-by-teplitsa
										value: attributes.minHeight,
										onChange: ( val ) => {
											setAttributes( { minHeight: val } );
										},
										labelPosition: 'side',
										units: [
											{
												value: "px",
												label: "px",
											},
											{
												value: 'vh',
												label: 'vh'
											},
										]
									}
								),

							),

							el( PanelBody,
								{
									title: __( 'Form for submitting markers', 'shmapper-by-teplitsa' ),
									initialOpen: true,
								},

								formControls(),

							),

							el( PanelBody,
								{
									title: __( 'Map elements', 'shmapper-by-teplitsa' ),
									initialOpen: true,
								},

								el( Fragment,
									null,
									getMapKeys.map((item) => {
										var key = item.key;
										var value = __('Disabled', 'shmapper-by-teplitsa');
										var className = 'is-disabled';
										if (currentMap[key]) {
											if ( key == 'is_scroll_zoom' || key == 'is_drag' ) {
												value = __('Disabled', 'shmapper-by-teplitsa');
												className = 'is-disabled';
											} else {
												value = __('Enabled', 'shmapper-by-teplitsa');
												className = 'is-enabled';
											}
										} else {
											if ( key == 'is_scroll_zoom' || key == 'is_drag' ) {
												value = __('Enabled', 'shmapper-by-teplitsa');
												className = 'is-enabled';
											}
										}
										return el( 'p',
											{
												className: 'shm-components-map-elements',
											},
											el( 'span', null, 
												item.label,
											),
											': ',
											el( 'span',
												{
													className: className,
												},
												value
											),
											
										)
									} ),
									el( Button,
										{
											icon: 'edit',
											label: __( 'Edit map', 'shmapper-by-teplitsa' ) + currentMapTitle,
											href: 'post.php?post=' + currentMapId + '&action=edit',
											target: '_blank',
											isSecondary: true,
										},
										__( 'Edit this map', 'shmapper-by-teplitsa' )
									)
								),

							),

						),

						el( Disabled, null,
							el( ServerSideRender,
								{
									block: 'shmapper/map',
									attributes: attributes,
									urlQueryArgs: { isEditor: true }
								}
							),
						)

					)
				);
			},

		} );

	}

	registerBlockShmapperMap();

}(
	window.wp.blocks,
	window.wp.editor,
	window.wp.blockEditor,
	window.wp.element,
	window.wp.components,
	window.wp.data,
	window.wp.i18n,
	window.wp.serverSideRender,
) );
