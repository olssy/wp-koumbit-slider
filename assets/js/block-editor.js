/* global wp */
/**
 * Block editor component for the wpk-slider/slider block.
 *
 * Uses window.wp.* globals — no build step required.
 * Renders a <select> in the block editor so authors pick a slider by title.
 * The actual slider output is server-rendered via the render_callback.
 */
( function () {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var useSelect = wp.data.useSelect;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var Placeholder = wp.components.Placeholder;
	var Spinner = wp.components.Spinner;

	wp.blocks.registerBlockType( 'wpk-slider/slider', {
		edit: function EditSliderBlock( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			var sliders = useSelect( function ( select ) {
				return select( 'core' ).getEntityRecords( 'postType', 'wpk_slider', {
					per_page: 100,
					status: 'publish',
					_fields: 'id,title',
				} );
			}, [] );

			var options = [ { label: __( '— Select a slider —', 'wp-koumbit-slider' ), value: 0 } ];
			if ( sliders ) {
				sliders.forEach( function ( s ) {
					options.push( { label: s.title.rendered || s.title.raw || '#' + s.id, value: s.id } );
				} );
			}

			var sliderId = attributes.sliderId || 0;

			var inspector = el(
				InspectorControls,
				null,
				el(
					PanelBody,
					{ title: __( 'Slider', 'wp-koumbit-slider' ), initialOpen: true },
					el( SelectControl, {
						label: __( 'Select slider', 'wp-koumbit-slider' ),
						value: sliderId,
						options: options,
						onChange: function ( val ) {
							setAttributes( { sliderId: parseInt( val, 10 ) || 0 } );
						},
					} )
				)
			);

			var preview;
			if ( ! sliders ) {
				preview = el( Spinner );
			} else if ( sliderId === 0 ) {
				preview = el(
					Placeholder,
					{
						icon: 'images-alt2',
						label: __( 'Koumbit Slider', 'wp-koumbit-slider' ),
						instructions: __( 'Select a slider from the sidebar panel.', 'wp-koumbit-slider' ),
					}
				);
			} else {
				var found = sliders.find( function ( s ) { return s.id === sliderId; } );
				var name = found ? ( found.title.rendered || found.title.raw || '#' + sliderId ) : '#' + sliderId;
				preview = el(
					'div',
					{ className: 'wpk-block-preview' },
					el( 'span', { className: 'dashicons dashicons-images-alt2' } ),
					' ' + __( 'Slider: ', 'wp-koumbit-slider' ) + name
				);
			}

			return el(
				'div',
				blockProps,
				inspector,
				preview
			);
		},

		save: function () {
			// Server-side rendered — no static save.
			return null;
		},
	} );
}() );
