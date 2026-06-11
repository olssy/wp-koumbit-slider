/* global wpkSliderAdmin, wp */
/**
 * Slide manager UI for the wpk_slider edit screen.
 *
 * Reads the JSON from #wpk-slides-json on load, renders the interactive
 * slide list, and writes back to #wpk-slides-json on every change so the
 * standard save_post hook picks it up.
 *
 * Dependencies: wp-i18n (localized via wpkSliderAdmin)
 */
( function () {
	'use strict';

	let slides = [];
	let mediaFrame = null;
	let editingIndex = -1;

	// -------------------------------------------------------------------------
	// Boot
	// -------------------------------------------------------------------------
	document.addEventListener( 'DOMContentLoaded', function () {
		const textarea = document.getElementById( 'wpk-slides-json' );
		if ( ! textarea ) return;

		try {
			slides = JSON.parse( textarea.value || '[]' );
			if ( ! Array.isArray( slides ) ) slides = [];
		} catch ( e ) {
			slides = [];
		}

		initSectionToggles();
		initConfigSync();
		renderAll();

		document.getElementById( 'wpk-add-slide' ).addEventListener( 'click', addSlide );
	} );

	// -------------------------------------------------------------------------
	// Section toggles (progressive disclosure)
	// -------------------------------------------------------------------------
	function initSectionToggles() {
		document.querySelectorAll( '.wpk-section-toggle' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				const body = document.getElementById( btn.getAttribute( 'aria-controls' ) );
				const open = 'true' === btn.getAttribute( 'aria-expanded' );
				btn.setAttribute( 'aria-expanded', open ? 'false' : 'true' );
				if ( open ) {
					body.setAttribute( 'hidden', '' );
					btn.closest( '.wpk-config-section' ).classList.remove( 'wpk-section-open' );
				} else {
					body.removeAttribute( 'hidden' );
					btn.closest( '.wpk-config-section' ).classList.add( 'wpk-section-open' );
				}
			} );
		} );
	}

	// -------------------------------------------------------------------------
	// Config form → hidden JSON sync
	// -------------------------------------------------------------------------
	function initConfigSync() {
		const configForm = document.getElementById( 'wpk-slider-config-form' );
		const configJson = document.getElementById( 'wpk-config-json' );
		if ( ! configForm || ! configJson ) return;

		// Sync range output display.
		const opacityRange = document.getElementById( 'wpk-cfg-overlay-opacity' );
		if ( opacityRange ) {
			const output = opacityRange.nextElementSibling;
			opacityRange.addEventListener( 'input', function () {
				if ( output ) output.value = opacityRange.value;
			} );
		}
	}

	// -------------------------------------------------------------------------
	// Rendering
	// -------------------------------------------------------------------------
	function renderAll() {
		const list = document.getElementById( 'wpk-slides-list' );
		const empty = list.querySelector( '.wpk-slides-empty' );

		list.querySelectorAll( '.wpk-slide-row' ).forEach( function ( el ) { el.remove(); } );

		if ( slides.length === 0 ) {
			if ( empty ) empty.style.display = '';
			syncHidden();
			return;
		}

		if ( empty ) empty.style.display = 'none';
		slides.forEach( function ( slide, i ) {
			list.appendChild( buildRow( slide, i ) );
		} );

		syncHidden();
	}

	function buildRow( slide, index ) {
		const row = document.createElement( 'div' );
		row.className = 'wpk-slide-row';
		row.setAttribute( 'role', 'listitem' );
		row.dataset.index = String( index );

		const thumb = slide.image_url
			? '<img src="' + escAttr( slide.image_url ) + '" alt="" class="wpk-slide-thumb" />'
			: '<span class="wpk-slide-thumb wpk-thumb-placeholder">&#128247;</span>';

		const title = slide.title || '(' + ( index + 1 ) + ')';

		row.innerHTML = [
			'<div class="wpk-slide-preview">',
			thumb,
			'<span class="wpk-slide-label">' + escHtml( title ) + '</span>',
			'</div>',
			'<div class="wpk-slide-actions">',
			makeBtn( 'wpk-slide-move-up', wpkSliderAdmin.moveUpLabel, index === 0 ),
			makeBtn( 'wpk-slide-move-dn', wpkSliderAdmin.moveDnLabel, index === slides.length - 1 ),
			makeBtn( 'wpk-slide-edit', wpkSliderAdmin.editLabel, false ),
			makeBtn( 'wpk-slide-remove', wpkSliderAdmin.removeLabel, false, 'button-link-delete' ),
			'</div>',
			'<div class="wpk-slide-edit-panel" hidden></div>',
		].join( '' );

		row.querySelector( '.wpk-slide-move-up' ).addEventListener( 'click', function () { moveSlide( index, -1 ); } );
		row.querySelector( '.wpk-slide-move-dn' ).addEventListener( 'click', function () { moveSlide( index, 1 ); } );
		row.querySelector( '.wpk-slide-edit' ).addEventListener( 'click', function () { openEditPanel( row, index ); } );
		row.querySelector( '.wpk-slide-remove' ).addEventListener( 'click', function () { removeSlide( index ); } );

		return row;
	}

	function makeBtn( cls, label, disabled, extra ) {
		const dis = disabled ? ' disabled' : '';
		const extraCls = extra ? ' ' + extra : '';
		return '<button type="button" class="button' + extraCls + ' ' + cls + '"' + dis + '>' + escHtml( label ) + '</button>';
	}

	// -------------------------------------------------------------------------
	// Edit panel (inline form per slide)
	// -------------------------------------------------------------------------
	function openEditPanel( row, index ) {
		const panel = row.querySelector( '.wpk-slide-edit-panel' );
		if ( ! panel.hasAttribute( 'hidden' ) ) {
			panel.setAttribute( 'hidden', '' );
			panel.innerHTML = '';
			return;
		}

		const slide = slides[ index ];
		const thumbSrc = slide.image_url ? escAttr( slide.image_url ) : '';

		panel.innerHTML = buildEditForm( slide, index, thumbSrc );
		panel.removeAttribute( 'hidden' );

		const selectBtn = panel.querySelector( '.wpk-media-select' );
		const removeImgBtn = panel.querySelector( '.wpk-media-remove' );
		const preview = panel.querySelector( '.wpk-img-preview' );
		const imgIdInput = panel.querySelector( '[name="slide_image_id"]' );
		const imgUrlInput = panel.querySelector( '[name="slide_image_url"]' );
		const imgAltInput = panel.querySelector( '[name="slide_image_alt"]' );

		selectBtn.addEventListener( 'click', function () {
			openMediaPicker( function ( attachment ) {
				imgIdInput.value = attachment.id;
				imgUrlInput.value = attachment.url;
				imgAltInput.value = attachment.alt || attachment.title || '';
				preview.src = attachment.url;
				preview.style.display = 'block';
			} );
		} );

		removeImgBtn.addEventListener( 'click', function () {
			imgIdInput.value = '';
			imgUrlInput.value = '';
			imgAltInput.value = '';
			preview.src = '';
			preview.style.display = 'none';
		} );

		panel.querySelector( '.wpk-slide-save' ).addEventListener( 'click', function () {
			saveSlide( panel, index );
		} );

		panel.querySelector( '.wpk-slide-cancel' ).addEventListener( 'click', function () {
			panel.setAttribute( 'hidden', '' );
			panel.innerHTML = '';
		} );
	}

	function buildEditForm( slide, index, thumbSrc ) {
		return [
			'<div class="wpk-edit-form">',

			// Image
			'<div class="wpk-field-group">',
			'<label>' + escHtml( 'Slide image' ) + '</label>',
			'<div class="wpk-img-picker">',
			thumbSrc
				? '<img src="' + thumbSrc + '" alt="" class="wpk-img-preview" style="max-width:200px;display:block;margin-bottom:8px;" />'
				: '<img src="" alt="" class="wpk-img-preview" style="max-width:200px;display:none;margin-bottom:8px;" />',
			'<button type="button" class="button wpk-media-select">Select image</button> ',
			'<button type="button" class="button-link wpk-media-remove">Remove</button>',
			'</div>',
			'<input type="hidden" name="slide_image_id" value="' + escAttr( String( slide.image_id || '' ) ) + '" />',
			'<input type="hidden" name="slide_image_url" value="' + escAttr( slide.image_url || '' ) + '" />',
			'<input type="hidden" name="slide_image_alt" value="' + escAttr( slide.image_alt || '' ) + '" />',
			'</div>',

			// Content
			field( 'text', 'slide_title', 'Title', slide.title || '' ),
			field( 'text', 'slide_subtitle', 'Subtitle', slide.subtitle || '' ),
			field( 'textarea', 'slide_content', 'Body text (HTML allowed)', slide.content || '' ),

			// Button
			'<fieldset class="wpk-field-group"><legend>Call-to-action button</legend>',
			field( 'text', 'slide_button_text', 'Button label', slide.button_text || '' ),
			field( 'url', 'slide_button_url', 'Button URL', slide.button_url || '' ),
			selectField( 'slide_button_target', 'Open in', [ [ '_self', 'Same tab' ], [ '_blank', 'New tab' ] ], slide.button_target || '_self' ),
			selectField( 'slide_button_style', 'Style', [ [ 'primary', 'Primary' ], [ 'secondary', 'Secondary' ], [ 'outline', 'Outline' ], [ 'ghost', 'Ghost' ] ], slide.button_style || 'primary' ),
			'</fieldset>',

			// Appearance
			'<fieldset class="wpk-field-group"><legend>Appearance</legend>',
			selectField( 'slide_text_align', 'Text alignment', [ [ 'center', 'Centre' ], [ 'left', 'Left' ], [ 'right', 'Right' ] ], slide.text_align || 'center' ),
			field( 'color', 'slide_overlay_color', 'Overlay colour', slide.overlay_color || '#000000' ),
			rangeField( 'slide_overlay_opacity', 'Overlay opacity', slide.overlay_opacity != null ? slide.overlay_opacity : 0, '0', '1', '0.05' ),
			field( 'text', 'slide_custom_class', 'Extra CSS class', slide.custom_class || '' ),
			'</fieldset>',

			'<fieldset class="wpk-field-group"><legend>Transition timing (overrides slider default)</legend>',
			field( 'number', 'slide_custom_speed', 'Speed (0 = use slider default)', slide.custom_speed || 0 ),
			selectField( 'slide_custom_easing', 'Easing', [
				[ '', 'Default (ease)' ],
				[ 'ease', 'Ease' ],
				[ 'ease-in', 'Ease in' ],
				[ 'ease-out', 'Ease out' ],
				[ 'ease-in-out', 'Ease in-out' ],
				[ 'linear', 'Linear' ],
			], slide.custom_easing || '' ),
			'</fieldset>',

			// Actions
			'<div class="wpk-edit-actions">',
			'<button type="button" class="button button-primary wpk-slide-save">Save slide</button> ',
			'<button type="button" class="button wpk-slide-cancel">Cancel</button>',
			'</div>',

			'</div>',
		].join( '' );
	}

	function field( type, name, label, value ) {
		if ( 'textarea' === type ) {
			return '<div class="wpk-field-group"><label>' + escHtml( label ) + '</label>'
				+ '<textarea name="' + escAttr( name ) + '" class="widefat" rows="3">' + escHtml( value ) + '</textarea></div>';
		}
		return '<div class="wpk-field-group"><label>' + escHtml( label ) + '</label>'
			+ '<input type="' + escAttr( type ) + '" name="' + escAttr( name ) + '" value="' + escAttr( value ) + '" class="widefat" /></div>';
	}

	function selectField( name, label, options, current ) {
		const opts = options.map( function ( o ) {
			const sel = o[ 0 ] === current ? ' selected' : '';
			return '<option value="' + escAttr( o[ 0 ] ) + '"' + sel + '>' + escHtml( o[ 1 ] ) + '</option>';
		} ).join( '' );
		return '<div class="wpk-field-group"><label>' + escHtml( label ) + '</label><select name="' + escAttr( name ) + '">' + opts + '</select></div>';
	}

	function rangeField( name, label, value, min, max, step ) {
		return '<div class="wpk-field-group"><label>' + escHtml( label ) + '</label>'
			+ '<input type="range" name="' + escAttr( name ) + '" value="' + escAttr( String( value ) ) + '" min="' + min + '" max="' + max + '" step="' + step + '" style="width:200px;">'
			+ '<output>' + escHtml( String( value ) ) + '</output></div>';
	}

	// -------------------------------------------------------------------------
	// Slide mutations
	// -------------------------------------------------------------------------
	function addSlide() {
		slides.push( {
			id: Date.now(),
			image_id: 0,
			image_url: '',
			image_alt: '',
			title: '',
			subtitle: '',
			content: '',
			button_text: '',
			button_url: '',
			button_target: '_self',
			button_style: 'primary',
			overlay_opacity: 0,
			overlay_color: '#000000',
			text_align: 'center',
			custom_class: '',
			custom_speed: 0,
			custom_easing: '',
		} );
		renderAll();

		// Auto-open the edit panel for the newly added slide.
		const rows = document.querySelectorAll( '.wpk-slide-row' );
		const last = rows[ rows.length - 1 ];
		if ( last ) {
			openEditPanel( last, slides.length - 1 );
			last.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
		}
	}

	function removeSlide( index ) {
		slides.splice( index, 1 );
		renderAll();
	}

	function moveSlide( index, direction ) {
		const target = index + direction;
		if ( target < 0 || target >= slides.length ) return;
		const tmp = slides[ index ];
		slides[ index ] = slides[ target ];
		slides[ target ] = tmp;
		renderAll();
	}

	function saveSlide( panel, index ) {
		const get = function ( name ) {
			const el = panel.querySelector( '[name="' + name + '"]' );
			return el ? el.value : '';
		};

		slides[ index ] = {
			id: slides[ index ].id || Date.now(),
			image_id: parseInt( get( 'slide_image_id' ), 10 ) || 0,
			image_url: get( 'slide_image_url' ),
			image_alt: get( 'slide_image_alt' ),
			title: get( 'slide_title' ),
			subtitle: get( 'slide_subtitle' ),
			content: get( 'slide_content' ),
			button_text: get( 'slide_button_text' ),
			button_url: get( 'slide_button_url' ),
			button_target: get( 'slide_button_target' ),
			button_style: get( 'slide_button_style' ),
			overlay_opacity: parseFloat( get( 'slide_overlay_opacity' ) ) || 0,
			overlay_color: get( 'slide_overlay_color' ),
			text_align: get( 'slide_text_align' ),
			custom_class: get( 'slide_custom_class' ),
			custom_speed: parseInt( get( 'slide_custom_speed' ), 10 ) || 0,
			custom_easing: get( 'slide_custom_easing' ) || '',
		};

		renderAll();
	}

	function syncHidden() {
		const textarea = document.getElementById( 'wpk-slides-json' );
		if ( textarea ) {
			textarea.value = JSON.stringify( slides );
		}
	}

	// -------------------------------------------------------------------------
	// WP Media picker
	// -------------------------------------------------------------------------
	function openMediaPicker( callback ) {
		if ( mediaFrame ) {
			mediaFrame.open();
			return;
		}

		mediaFrame = wp.media( {
			title: wpkSliderAdmin.mediaTitle,
			button: { text: wpkSliderAdmin.mediaButton },
			multiple: false,
			library: { type: 'image' },
		} );

		mediaFrame.on( 'select', function () {
			const attachment = mediaFrame.state().get( 'selection' ).first().toJSON();
			// Prefer medium_large size for slide backgrounds.
			const sizes = attachment.sizes || {};
			const src = ( sizes.large || sizes.medium_large || sizes.medium || sizes.full || attachment ).url;
			callback( { id: attachment.id, url: src, alt: attachment.alt, title: attachment.title } );
		} );

		mediaFrame.open();
	}

	// -------------------------------------------------------------------------
	// Utils
	// -------------------------------------------------------------------------
	function escHtml( str ) {
		return String( str )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#039;' );
	}

	function escAttr( str ) {
		return escHtml( str );
	}
}() );
