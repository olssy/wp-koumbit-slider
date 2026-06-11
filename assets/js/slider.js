/**
 * Vanilla JS slider runtime for wp-koumbit-slider.
 *
 * Reads configuration from data-config JSON on each .wpk-slider element.
 * Skips .wpk-slider-swiper elements — those are handled by swiper-init.js.
 *
 * v1.1 additions:
 *   - Lazy image loading via IntersectionObserver (lazy: true config)
 *   - Thumbnail strip pagination (pagination: 'thumbstrip')
 *   - Per-slide speed/easing overrides (data-speed, data-easing attributes)
 *
 * @since 1.0.0
 */
( function ( global ) {
	'use strict';

	var reducedMotion = global.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

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
		// Swiper mode — handled by swiper-init.js.
		if ( el.classList.contains( 'wpk-slider-swiper' ) ) return;

		var rawCfg = {};
		try { rawCfg = JSON.parse( el.dataset.config || '{}' ); } catch ( e ) { /* noop */ }

		var cfg = Object.assign( {}, DEFAULTS, rawCfg );
		var slides = Array.prototype.slice.call( el.querySelectorAll( '.wpk-slide' ) );
		var track = el.querySelector( '.wpk-slider-track' );
		var prevBtn = el.querySelector( '.wpk-slider-prev' );
		var nextBtn = el.querySelector( '.wpk-slider-next' );
		var paginationEl = el.querySelector( '.wpk-slider-pagination' );

		// Thumbstrip lives outside the slider in a sibling .wpk-slider-thumbstrip element.
		var thumbstripEl = null;
		if ( 'thumbstrip' === cfg.pagination ) {
			var outer = el.closest( '.wpk-slider-outer' );
			if ( outer ) {
				thumbstripEl = outer.querySelector( '.wpk-slider-thumbstrip' );
			}
		}

		if ( slides.length < 1 ) return;

		var current = 0;
		var total = slides.length;
		var autoplayTimer = null;
		var isAnimating = false;
		var isHorizontal = 'vertical' !== cfg.direction;
		var pointerStart = null;
		var pointerStartTime = 0;

		// -------------------------------------------------------------------------
		// Init
		// -------------------------------------------------------------------------
		function init() {
			el.classList.add( 'wpk-slider-ready' );

			if ( 'fade' === cfg.effect ) {
				initFadeLayout();
			} else {
				initSlideLayout();
			}

			if ( cfg.lazy ) initLazy();

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

		// -------------------------------------------------------------------------
		// Layout initialisation
		// -------------------------------------------------------------------------
		function initFadeLayout() {
			slides.forEach( function ( s, i ) {
				s.style.position = 'absolute';
				s.style.inset = '0';
				s.style.opacity = i === 0 ? '1' : '0';
				s.style.zIndex = i === 0 ? '1' : '0';
				if ( ! reducedMotion ) {
					s.style.transition = 'opacity ' + cfg.speed + 'ms ease';
				}
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

			applyTranslate( 0, false, cfg.speed, 'ease' );
		}

		// -------------------------------------------------------------------------
		// Lazy image loading via IntersectionObserver
		// -------------------------------------------------------------------------
		function initLazy() {
			// Always load the first 2 slides immediately so there's no blank first frame.
			slides.slice( 0, Math.min( 2, total ) ).forEach( loadSlide );

			if ( ! ( 'IntersectionObserver' in global ) ) {
				// Fallback: load all slides at once.
				slides.forEach( loadSlide );
				return;
			}

			// rootMargin pre-loads slides ~1 viewport ahead of the scroll position.
			var margin = isHorizontal ? '0px 100% 0px 100%' : '100% 0px 100% 0px';

			var observer = new IntersectionObserver( function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						loadSlide( entry.target );
						observer.unobserve( entry.target );
					}
				} );
			}, { rootMargin: margin } );

			slides.forEach( function ( s ) {
				if ( s.dataset.bg ) {
					observer.observe( s );
				}
			} );
		}

		/**
		 * Swaps a slide's data-bg into an actual background-image style.
		 *
		 * @param {HTMLElement} slideEl
		 */
		function loadSlide( slideEl ) {
			var bg = slideEl.dataset.bg;
			if ( ! bg ) return;
			slideEl.style.backgroundImage = 'url(' + bg + ')';
			slideEl.classList.add( 'wpk-slide-loaded' );
			delete slideEl.dataset.bg;
		}

		/**
		 * Pre-loads the slides adjacent to the current index.
		 * Called after every navigation to stay ahead of the user.
		 *
		 * @param {number} index
		 */
		function preloadAdjacent( index ) {
			[ index - 1, index, index + 1 ].forEach( function ( i ) {
				var idx = ( ( i % total ) + total ) % total;
				loadSlide( slides[ idx ] );
			} );
		}

		// -------------------------------------------------------------------------
		// Navigation
		// -------------------------------------------------------------------------
		function goTo( index, animate ) {
			if ( isAnimating && animate !== false ) return;

			var clamped = clamp( index );
			if ( clamped === current && animate !== false ) return;

			current = clamped;

			// Pre-load surrounding slides when lazy is active.
			if ( cfg.lazy ) preloadAdjacent( current );

			// Per-slide timing overrides.
			var slideSpeed = parseInt( slides[ current ].dataset.speed, 10 ) || cfg.speed;
			var slideEasing = slides[ current ].dataset.easing || 'ease';

			if ( 'fade' === cfg.effect ) {
				animateFade( animate !== false, slideSpeed, slideEasing );
			} else {
				applyTranslate( current, animate !== false, slideSpeed, slideEasing );
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
		function applyTranslate( index, animate, speed, easing ) {
			var spv = cfg.slidesPerView;
			var gap = cfg.spaceBetween;
			var pct = ( 100 / spv ) * index;
			var gapOffset = gap * index;
			var translateVal = 'calc(-' + pct + '% - ' + gapOffset + 'px)';

			if ( animate && ! reducedMotion ) {
				isAnimating = true;
				track.style.transition = 'transform ' + speed + 'ms ' + easing;
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

		function animateFade( animate, speed, easing ) {
			slides.forEach( function ( s, i ) {
				if ( animate && ! reducedMotion ) {
					s.style.transition = 'opacity ' + speed + 'ms ' + easing;
				} else {
					s.style.transition = 'none';
				}
				s.style.opacity = i === current ? '1' : '0';
				s.style.zIndex  = i === current ? '1' : '0';
			} );
		}

		function onTransitionEnd() {
			isAnimating = false;
		}

		// -------------------------------------------------------------------------
		// Pagination
		// -------------------------------------------------------------------------
		function buildPagination() {
			if ( thumbstripEl ) {
				buildThumbstrip();
				return;
			}
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

		function buildThumbstrip() {
			if ( ! thumbstripEl ) return;

			// Wire click handlers onto the pre-rendered PHP thumbnail buttons.
			var thumbBtns = thumbstripEl.querySelectorAll( '.wpk-thumb' );
			thumbBtns.forEach( function ( btn ) {
				var idx = parseInt( btn.dataset.index, 10 );
				btn.addEventListener( 'click', function () { goTo( idx ); } );
			} );
		}

		function updatePagination() {
			if ( thumbstripEl ) {
				updateThumbstrip();
				return;
			}
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

		function updateThumbstrip() {
			if ( ! thumbstripEl ) return;
			var thumbBtns = thumbstripEl.querySelectorAll( '.wpk-thumb' );
			thumbBtns.forEach( function ( btn, i ) {
				var active = i === current;
				btn.classList.toggle( 'wpk-thumb-active', active );
				btn.setAttribute( 'aria-selected', active ? 'true' : 'false' );
			} );
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
			autoplayTimer = global.setInterval( function () { next(); }, cfg.autoplayDelay );
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
				pointerStart = null;

				if ( dt > 500 ) return;
				var delta = isHorizontal ? dx : dy;
				if ( Math.abs( delta ) < 40 ) return;
				if ( delta < 0 ) next(); else prev();
			} );

			el.addEventListener( 'pointercancel', function () { pointerStart = null; } );
		}

		init();
	}

	// -------------------------------------------------------------------------
	// Boot
	// -------------------------------------------------------------------------
	function initAll() {
		document.querySelectorAll( '.wpk-slider:not(.wpk-slider-ready):not(.wpk-slider-swiper)' ).forEach( createSlider );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initAll );
	} else {
		initAll();
	}

	global.WPKSlider = { init: createSlider };
}( window ) );
