<?php
/**
 * Generates slider HTML output.
 *
 * @package WPKoumbit\Slider
 */

namespace WPKoumbit\Slider\Frontend;

use WPKoumbit\Slider\Admin\EditScreen;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a slider's HTML from post meta.
 *
 * @since 1.0.0
 */
class SliderRenderer {

	/**
	 * Renders the slider or returns an empty string when no slides exist.
	 *
	 * @param int $slider_id  The wpk_slider post ID.
	 * @return string         HTML output.
	 */
	public function render( int $slider_id ): string {
		$post = get_post( $slider_id );
		if ( ! $post || 'wpk_slider' !== $post->post_type || 'publish' !== $post->post_status ) {
			return '';
		}

		$slides = json_decode( get_post_meta( $slider_id, '_wpk_slider_slides', true ) ?: '[]', true );
		if ( ! is_array( $slides ) || empty( $slides ) ) {
			return '';
		}

		$cfg_raw = get_post_meta( $slider_id, '_wpk_slider_config', true ) ?: '{}';
		$cfg     = array_merge( EditScreen::config_defaults(), json_decode( $cfg_raw, true ) ?: array() );

		$direction = 'vertical' === $cfg['direction'] ? 'vertical' : 'horizontal';
		$cfg_json  = wp_json_encode(
			array(
				'effect'               => $cfg['effect'],
				'speed'                => (int) $cfg['speed'],
				'loop'                 => (bool) $cfg['loop'],
				'autoplay'             => (bool) $cfg['autoplay'],
				'autoplayDelay'        => (int) $cfg['autoplay_delay'],
				'autoplayPauseOnHover' => (bool) $cfg['autoplay_pause_on_hover'],
				'navigation'           => (bool) $cfg['navigation'],
				'pagination'           => $cfg['pagination'],
				'keyboard'             => (bool) $cfg['keyboard'],
				'swipe'                => (bool) $cfg['swipe'],
				'slidesPerView'        => (int) $cfg['slides_per_view'],
				'spaceBetween'         => (int) $cfg['space_between'],
				'autoHeight'           => (bool) $cfg['auto_height'],
				'centeredSlides'       => (bool) $cfg['centered_slides'],
				'freeMode'             => (bool) $cfg['free_mode'],
				'lazy'                 => (bool) $cfg['lazy'],
				'direction'            => $direction,
			)
		);

		$total = count( $slides );
		ob_start();
		?>
		<div
			class="wpk-slider wpk-slider--<?php echo esc_attr( $direction ); ?>"
			id="wpk-slider-<?php echo esc_attr( (string) $slider_id ); ?>"
			role="region"
			aria-roledescription="carousel"
			aria-label="<?php echo esc_attr( get_the_title( $slider_id ) ); ?>"
			style="--wpk-slider-height:<?php echo esc_attr( $cfg['height'] ); ?>;"
			data-config="<?php echo esc_attr( $cfg_json ); ?>"
		>
			<div class="wpk-slider-track" aria-live="off">
				<?php foreach ( $slides as $index => $slide ) : ?>
					<?php
					$slide = wp_parse_args(
						(array) $slide,
						array(
							'image_url'       => '',
							'image_alt'       => '',
							'title'           => '',
							'subtitle'        => '',
							'content'         => '',
							'button_text'     => '',
							'button_url'      => '',
							'button_target'   => '_self',
							'button_style'    => 'primary',
							'overlay_opacity' => $cfg['overlay_opacity'],
							'overlay_color'   => $cfg['overlay_color'],
							'text_align'      => 'center',
							'custom_class'    => '',
						)
					);

					$slide_class = 'wpk-slide wpk-text-' . sanitize_html_class( $slide['text_align'] );
					if ( ! empty( $slide['custom_class'] ) ) {
						$slide_class .= ' ' . sanitize_html_class( $slide['custom_class'] );
					}

					$has_image   = ! empty( $slide['image_url'] );
					$bg_style    = $has_image ? ' style="background-image:url(' . esc_url( $slide['image_url'] ) . ');"' : '';
					$pos         = $index + 1;
					?>
					<div
						class="<?php echo esc_attr( $slide_class ); ?>"
						role="group"
						aria-roledescription="slide"
						aria-label="<?php printf( /* translators: 1: slide number, 2: total count */ esc_attr__( 'Slide %1$d of %2$d', 'wp-koumbit-slider' ), $pos, $total ); ?>"
						<?php echo $bg_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — bg_style is already escaped above ?>
					>
						<?php if ( (float) $slide['overlay_opacity'] > 0 ) : ?>
							<div
								class="wpk-slide-overlay"
								aria-hidden="true"
								style="background:<?php echo esc_attr( $slide['overlay_color'] ); ?>;opacity:<?php echo esc_attr( (string) (float) $slide['overlay_opacity'] ); ?>;"
							></div>
						<?php endif; ?>

						<div class="wpk-slide-content">
							<?php if ( ! empty( $slide['title'] ) ) : ?>
								<h2 class="wpk-slide-title"><?php echo esc_html( $slide['title'] ); ?></h2>
							<?php endif; ?>

							<?php if ( ! empty( $slide['subtitle'] ) ) : ?>
								<p class="wpk-slide-subtitle"><?php echo esc_html( $slide['subtitle'] ); ?></p>
							<?php endif; ?>

							<?php if ( ! empty( $slide['content'] ) ) : ?>
								<div class="wpk-slide-body"><?php echo wp_kses_post( $slide['content'] ); ?></div>
							<?php endif; ?>

							<?php if ( ! empty( $slide['button_text'] ) && ! empty( $slide['button_url'] ) ) : ?>
								<a
									class="wpk-btn wpk-btn-<?php echo esc_attr( $slide['button_style'] ); ?>"
									href="<?php echo esc_url( $slide['button_url'] ); ?>"
									target="<?php echo '_blank' === $slide['button_target'] ? '_blank' : '_self'; ?>"
									<?php echo '_blank' === $slide['button_target'] ? 'rel="noopener noreferrer"' : ''; ?>
								>
									<?php echo esc_html( $slide['button_text'] ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( $cfg['navigation'] ) : ?>
				<button class="wpk-slider-prev" aria-label="<?php esc_attr_e( 'Previous slide', 'wp-koumbit-slider' ); ?>" aria-controls="wpk-slider-<?php echo esc_attr( (string) $slider_id ); ?>">
					<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="24" height="24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
				</button>
				<button class="wpk-slider-next" aria-label="<?php esc_attr_e( 'Next slide', 'wp-koumbit-slider' ); ?>" aria-controls="wpk-slider-<?php echo esc_attr( (string) $slider_id ); ?>">
					<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="24" height="24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
				</button>
			<?php endif; ?>

			<?php if ( 'none' !== $cfg['pagination'] ) : ?>
				<div
					class="wpk-slider-pagination wpk-pagination-<?php echo esc_attr( $cfg['pagination'] ); ?>"
					role="tablist"
					aria-label="<?php esc_attr_e( 'Slide navigation', 'wp-koumbit-slider' ); ?>"
				></div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean() ?: '';
	}
}
