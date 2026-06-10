/**
 * Vanilla JS slider runtime for wp-koumbit-slider.
 *
 * Reads configuration from data-config JSON on each .wpk-slider element
 * and builds the interactive carousel entirely without external libraries.
 *
 * Supports:
 *   - slide and fade effects
 *   - autoplay with pause-on-hover
 *   - prev/next navigation buttons
 *   - bullet, fraction, progress-bar, and no pagination
 *   - keyboard arrow navigation
 *   - Pointer Events API touch/swipe
 *   - ARIA roles and live-region updates
 *   - prefers-reduced-motion
 *
 * Initialises all .wpk-slider elements on DOMContentLoaded and exposes
 * window.WPKSlider.init(el) for dynamic insertion.
 */
( function ( global ) {
	'use strict';

	var reducedMotion = global.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	// -------------------------------------------------------------------------
	// Defaults
	// -------------------------------------------------------------------------
	var DEFAULTS = {
		effect: 'slide',
		speed: 500,
		loop: true,
		autoplay: false,
		autoplayDelay: 4000,
		autoplayPauseOnHover: true,
		navigation: true,
		pagination: 'bullets',
		keyboard: true,
		swipe: true,
		slidesPerView: 1,
		spaceBetween: 0,
		autoHeight: false,
		centeredSlides: false,
		freeMode: false,
		lazy: false,
		direction: 'horizontal',
	};

	// -------------------------------------------------------------------------
	// Slider factory
	// -------------------------------------------------------------------------
	function createSlider( el ) {
		var rawCfg = {};
		try { rawCfg = JSON.parse( el.dataset.config || '{}' ); } catch ( e ) { /* noop */ }

		var cfg = Object.assign( {}, DEFAULTS, rawCfg );
		var slides = Array.prototype.slice.call( el.querySelectorAll( '.wpk-slide' ) );
		var track = el.querySelector( '.wpk-slider-track' );
		var prevBtn = el.querySelector( '.wpk-slider-prev' );
		var nextBtn = el.querySelector( '.wpk-slider-next' );
		var paginationEl = el.querySelector( '.wpk-slider-pagination' );

		if ( slides.length < 1 ) return;

		var current = 0;
		var total = slides.length;
		var autoplayTimer = null;
		var isAnimating = false;

		var isHorizontal = 'vertical' !== cfg.direction;

		// Pointer-swipe state.
		var pointerStart = null;
		var pointerStartTime = 0;

		// -------------------------------------------------------------------------
		// Initialise layout
		// -------------------------------------------------------------------------
		function init() {
			el.classList.add( 'wpk-slider-ready' );

			if ( 'fade' === cfg.effect ) {
				initFadeLayout();
			} else {
				initSlideLayout();
			}

			buildPagination();
			updateA11y();
			updateControls();

			if ( cfg.autoplay && ! reducedMotion ) startAutoplay();
			if ( cfg.keyboard ) attachKeyboard();
			if ( cfg.swipe ) attachSwipe();
			if ( cfg.autoplayPauseOnHover && cfg.autoplay ) attachHoverPause();

			if ( prevBtn ) prevBtn.addEventListener( 'click', prev );
			if ( nextBtn ) nextBtn.addEventListener( 'click', next );
		}

		function initFadeLayout() {
			slides.forEach( function ( s, i ) {
				s.style.position = 'absolute';
				s.style.inset = '0';
				s.style.opacity = i === 0 ? '1' : '0';
				s.style.transition = reducedMotion ? 'none' : 'opacity ' + cfg.speed + 'ms ease';
				s.style.zIndex = i === 0 ? '1' : '0';
			} );
			track.style.position = 'relative';
		}

		function initSlideLayout() {
			var spv = cfg.slidesPerView;
			var gap = cfg.spaceBetween;
			var pct = 100 / spv;

			track.style.display = 'flex';
			track.style.flexDirection = isHorizontal ? 'row' : 'column';
			if ( ! reducedMotion ) {
				track.style.transition = 'transform ' + cfg.speed + 'ms ease';
			}

			slides.forEach( function ( s ) {
				if ( isHorizontal ) {
					s.style.minWidth = 'calc(' + pct + '% - ' + ( gap * ( spv - 1 ) / spv ) + 'px)';
					s.style.marginRight = gap + 'px';
				} else {
					s.style.minHeight = 'calc(' + pct + '% - ' + ( gap * ( spv - 1 ) / spv ) + 'px)';
					s.style.marginBottom = gap + 'px';
				}
			} );

			applyTranslate( 0, false );
		}

		// -------------------------------------------------------------------------
		// Navigation
		// -------------------------------------------------------------------------
		function goTo( index, animate ) {
			if ( isAnimating && animate !== false ) return;

			var clamped = clamp( index );
			if ( clamped === current && animate !== false ) return;

			current = clamped;

			if ( 'fade' === cfg.effect ) {
				animateFade( animate );
			} else {
				applyTranslate( current, animate !== false );
			}

			updatePagination();
			updateA11y();
			updateControls();
		}

		function prev() {
			var target = current - 1;
			if ( target < 0 ) target = cfg.loop ? total - 1 : 0;
			goTo( target );
		}

		function next() {
			var target = current + 1;
			if ( target >= total ) target = cfg.loop ? 0 : total - 1;
			goTo( target );
		}

		function clamp( index ) {
			if ( cfg.loop ) return ( ( index % total ) + total ) % total;
			return Math.max( 0, Math.min( total - 1, index ) );
		}

		// -------------------------------------------------------------------------
		// Animation
		// -------------------------------------------------------------------------
		function applyTranslate( index, animate ) {
			var spv = cfg.slidesPerView;
			var gap = cfg.spaceBetween;
			var pct = ( 100 / spv ) * index;
			var gapOffset = gap * index;
			var translateVal = 'calc(-' + pct + '% - ' + gapOffset + 'px)';

			if ( animate && ! reducedMotion ) {
				isAnimating = true;
				track.style.transition = 'transform ' + cfg.speed + 'ms ease';
				track.addEventListener( 'transitionend', onTransitionEnd, { once: true } );
			} else {
				track.style.transition = 'none';
			}

			if ( isHorizontal ) {
				track.style.transform = 'translateX(' + translateVal + ')';
			} else {
				track.style.transform = 'translateY(' + translateVal + ')';
			}
		}

		function animateFade( animate ) {
			slides.forEach( function ( s, i ) {
				if ( animate && ! reducedMotion ) {
					s.style.transition = 'opacity ' + cfg.speed + 'ms ease';
				} else {
					s.style.transition = 'none';
				}
				s.style.opacity = i === current ? '1' : '0';
				s.style.zIndex = i === current ? '1' : '0';
			} );
		}

		function onTransitionEnd() {
			isAnimating = false;
		}

		// -------------------------------------------------------------------------
		// Pagination
		// -------------------------------------------------------------------------
		function buildPagination() {
			if ( ! paginationEl ) return;

			if ( 'bullets' === cfg.pagination ) {
				slides.forEach( function ( _, i ) {
					var btn = document.createElement( 'button' );
					btn.type = 'button';
					btn.className = 'wpk-pagination-bullet';
					btn.setAttribute( 'role', 'tab' );
					btn.setAttribute( 'aria-label', 'Slide ' + ( i + 1 ) );
					btn.setAttribute( 'aria-controls', el.id );
					btn.addEventListener( 'click', function () { goTo( i ); } );
					paginationEl.appendChild( btn );
				} );
			} else if ( 'fraction' === cfg.pagination ) {
				paginationEl.innerHTML = '<span class="wpk-fraction-current">1</span> / <span class="wpk-fraction-total">' + total + '</span>';
			} else if ( 'progress' === cfg.pagination ) {
				var bar = document.createElement( 'div' );
				bar.className = 'wpk-progress-bar';
				paginationEl.appendChild( bar );
			}

			updatePagination();
		}

		function updatePagination() {
			if ( ! paginationEl ) return;

			if ( 'bullets' === cfg.pagination ) {
				var bullets = paginationEl.querySelectorAll( '.wpk-pagination-bullet' );
				bullets.forEach( function ( b, i ) {
					var active = i === current;
					b.classList.toggle( 'wpk-bullet-active', active );
					b.setAttribute( 'aria-selected', active ? 'true' : 'false' );
				} );
			} else if ( 'fraction' === cfg.pagination ) {
				var cur = paginationEl.querySelector( '.wpk-fraction-current' );
				if ( cur ) cur.textContent = String( current + 1 );
			} else if ( 'progress' === cfg.pagination ) {
				var bar = paginationEl.querySelector( '.wpk-progress-bar' );
				if ( bar ) {
					var pct = ( ( current + 1 ) / total ) * 100;
					if ( isHorizontal ) {
						bar.style.transform = 'scaleX(' + ( pct / 100 ) + ')';
					} else {
						bar.style.transform = 'scaleY(' + ( pct / 100 ) + ')';
					}
				}
			}
		}

		// -------------------------------------------------------------------------
		// ARIA
		// -------------------------------------------------------------------------
		function updateA11y() {
			slides.forEach( function ( s, i ) {
				var hidden = i !== current;
				s.setAttribute( 'aria-hidden', hidden ? 'true' : 'false' );
				var focusable = s.querySelectorAll( 'a, button, input, select, textarea, [tabindex]' );
				focusable.forEach( function ( f ) {
					if ( hidden ) {
						f.setAttribute( 'tabindex', '-1' );
					} else {
						f.removeAttribute( 'tabindex' );
					}
				} );
			} );

			// Announce to screen readers.
			el.setAttribute( 'aria-label', el.getAttribute( 'aria-label' ) || '' );
		}

		function updateControls() {
			if ( prevBtn ) {
				prevBtn.disabled = ! cfg.loop && current === 0;
			}
			if ( nextBtn ) {
				nextBtn.disabled = ! cfg.loop && current === total - 1;
			}
		}

		// -------------------------------------------------------------------------
		// Autoplay
		// -------------------------------------------------------------------------
		function startAutoplay() {
			stopAutoplay();
			autoplayTimer = global.setInterval( function () {
				next();
			}, cfg.autoplayDelay );
		}

		function stopAutoplay() {
			if ( autoplayTimer ) {
				global.clearInterval( autoplayTimer );
				autoplayTimer = null;
			}
		}

		function attachHoverPause() {
			el.addEventListener( 'mouseenter', stopAutoplay );
			el.addEventListener( 'mouseleave', function () {
				if ( ! reducedMotion ) startAutoplay();
			} );
			el.addEventListener( 'focusin', stopAutoplay );
			el.addEventListener( 'focusout', function () {
				if ( ! reducedMotion ) startAutoplay();
			} );
		}

		// -------------------------------------------------------------------------
		// Keyboard
		// -------------------------------------------------------------------------
		function attachKeyboard() {
			el.addEventListener( 'keydown', function ( e ) {
				var prevKey = isHorizontal ? 'ArrowLeft' : 'ArrowUp';
				var nextKey = isHorizontal ? 'ArrowRight' : 'ArrowDown';
				if ( e.key === prevKey ) { e.preventDefault(); prev(); }
				if ( e.key === nextKey ) { e.preventDefault(); next(); }
			} );
			el.setAttribute( 'tabindex', '0' );
		}

		// -------------------------------------------------------------------------
		// Touch / pointer swipe
		// -------------------------------------------------------------------------
		function attachSwipe() {
			el.addEventListener( 'pointerdown', function ( e ) {
				if ( 1 !== e.buttons ) return;
				pointerStart = { x: e.clientX, y: e.clientY };
				pointerStartTime = Date.now();
				el.setPointerCapture( e.pointerId );
			} );

			el.addEventListener( 'pointerup', function ( e ) {
				if ( ! pointerStart ) return;

				var dx = e.clientX - pointerStart.x;
				var dy = e.clientY - pointerStart.y;
				var dt = Date.now() - pointerStartTime;
				var threshold = 40;
				var maxDuration = 500;

				pointerStart = null;

				if ( dt > maxDuration ) return;

				var delta = isHorizontal ? dx : dy;

				if ( Math.abs( delta ) < threshold ) return;
				if ( delta < 0 ) next(); else prev();
			} );

			el.addEventListener( 'pointercancel', function () {
				pointerStart = null;
			} );
		}

		init();
	}

	// -------------------------------------------------------------------------
	// Boot
	// -------------------------------------------------------------------------
	function initAll() {
		document.querySelectorAll( '.wpk-slider:not(.wpk-slider-ready)' ).forEach( createSlider );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initAll );
	} else {
		initAll();
	}

	global.WPKSlider = { init: createSlider };
}( window ) );
