/* global Swiper */
/**
 * Initialises Swiper.js for .wpk-slider-swiper elements.
 *
 * Translates the same data-config JSON used by the vanilla runtime into
 * Swiper constructor options, so the same per-slider configuration drives
 * both render modes.
 *
 * Loaded only when a slider on the current page has use_swiper: true
 * (enqueued conditionally by SliderRenderer::render()).
 *
 * @since 1.1.0
 */
( function ( global ) {
	'use strict';

	var reducedMotion = global.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	/**
	 * Maps our flat config object to Swiper constructor options.
	 *
	 * @param {Object} cfg  Parsed data-config JSON.
	 * @param {Element} el  The slider container.
	 * @returns {Object}
	 */
	function buildSwiperOptions( cfg, el ) {
		var effect = cfg.effect || 'slide';

		// Effects that require exactly 1 slide per view.
		var singleSlideEffects = [ 'cube', 'flip', 'coverflow', 'cards' ];
		var forceSingle = singleSlideEffects.indexOf( effect ) !== -1;

		var options = {
			effect: effect,
			speed: cfg.speed || 500,
			loop: cfg.loop !== false,
			direction: cfg.direction === 'vertical' ? 'vertical' : 'horizontal',
			slidesPerView: forceSingle ? 1 : ( cfg.slidesPerView || 1 ),
			spaceBetween: forceSingle ? 0 : ( cfg.spaceBetween || 0 ),
			centeredSlides: forceSingle ? true : !! cfg.centeredSlides,
			grabCursor: true,
			watchSlidesProgress: true,
			keyboard: { enabled: !! cfg.keyboard },
			allowTouchMove: cfg.swipe !== false,
		};

		if ( cfg.autoplay && ! reducedMotion ) {
			options.autoplay = {
				delay: cfg.autoplayDelay || 4000,
				pauseOnMouseEnter: !! cfg.autoplayPauseOnHover,
				disableOnInteraction: false,
			};
		}

		if ( cfg.navigation ) {
			options.navigation = {
				prevEl: el.querySelector( '.wpk-slider-prev' ),
				nextEl: el.querySelector( '.wpk-slider-next' ),
			};
		}

		if ( cfg.pagination && cfg.pagination !== 'none' && cfg.pagination !== 'thumbstrip' ) {
			// Swiper uses 'progressbar' not 'progress'.
			var paginationType = cfg.pagination === 'progress' ? 'progressbar' : cfg.pagination;
			options.pagination = {
				el: el.querySelector( '.wpk-slider-pagination' ),
				type: paginationType,
				clickable: 'bullets' === paginationType,
			};
		}

		if ( cfg.pagination === 'thumbstrip' ) {
			var outer = el.closest( '.wpk-slider-outer' );
			if ( outer ) {
				var thumbstrip = outer.querySelector( '.wpk-slider-thumbstrip' );
				if ( thumbstrip ) {
					options.thumbs = {
						swiper: {
							el: thumbstrip,
							slidesPerView: 'auto',
							watchSlidesProgress: true,
							slideClass: 'wpk-thumb',
							slideActiveClass: 'wpk-thumb-active',
						},
					};
				}
			}
		}

		if ( cfg.freeMode ) {
			options.freeMode = { enabled: true };
		}

		if ( cfg.autoHeight ) {
			options.autoHeight = true;
		}

		// Per-effect module options.
		if ( effect === 'coverflow' ) {
			options.coverflowEffect = {
				rotate: 50,
				stretch: 0,
				depth: 100,
				modifier: 1,
				slideShadows: true,
			};
		}

		if ( effect === 'cube' ) {
			options.cubeEffect = {
				shadow: true,
				slideShadows: true,
				shadowOffset: 20,
				shadowScale: 0.94,
			};
		}

		if ( effect === 'flip' ) {
			options.flipEffect = {
				slideShadows: true,
			};
		}

		if ( effect === 'cards' ) {
			options.cardsEffect = {
				slideShadows: true,
			};
		}

		return options;
	}

	/**
	 * Initialises all un-initialised .wpk-slider-swiper elements.
	 */
	function initAll() {
		document.querySelectorAll( '.wpk-slider-swiper:not(.wpk-swiper-initialized)' ).forEach( function ( el ) {
			var rawCfg = {};
			try { rawCfg = JSON.parse( el.dataset.config || '{}' ); } catch ( e ) { /* noop */ }

			var swiper = new Swiper( el, buildSwiperOptions( rawCfg, el ) );
			el.classList.add( 'wpk-swiper-initialized' );

			// Sync ARIA hidden attributes on slide change (Swiper doesn't do this by default).
			swiper.on( 'slideChange', function () {
				el.querySelectorAll( '.wpk-slide' ).forEach( function ( s, i ) {
					var active = i === swiper.realIndex;
					s.setAttribute( 'aria-hidden', active ? 'false' : 'true' );
				} );
			} );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initAll );
	} else {
		initAll();
	}

	global.WPKSliderSwiper = { init: initAll };
}( window ) );
