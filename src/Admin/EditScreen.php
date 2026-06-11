<?php
/**
 * Slider edit screen: slide manager and settings meta boxes.
 *
 * @package WPKoumbit\Slider
 */

namespace WPKoumbit\Slider\Admin;

use WPKoumbit\Slider\PostType\SliderPostType;

defined( 'ABSPATH' ) || exit;

/**
 * Adds two meta boxes to the wpk_slider edit screen:
 *   1. Slides — drag-and-drop (up/down) slide manager with full per-slide config
 *   2. Settings — progressive-disclosure slider configuration grouped by category
 *
 * Data is stored as JSON in two post-meta keys:
 *   _wpk_slider_slides  — JSON array of slide objects
 *   _wpk_slider_config  — JSON object of slider configuration
 *
 * Saved via the standard save_post hook (no full-page-reload inside the plugin UI;
 * the WP post-edit save is a native WP flow, not a plugin page reload).
 *
 * @since 1.0.0
 */
class EditScreen {

	public function init(): void {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_action( 'save_post_' . SliderPostType::POST_TYPE, array( $this, 'save' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	public function register_meta_boxes(): void {
		add_meta_box(
			'wpk-slider-slides',
			esc_html__( 'Slides', 'wp-koumbit-slider' ),
			array( $this, 'render_slides_box' ),
			SliderPostType::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'wpk-slider-config',
			esc_html__( 'Slider Settings', 'wp-koumbit-slider' ),
			array( $this, 'render_config_box' ),
			SliderPostType::POST_TYPE,
			'normal',
			'default'
		);

		// Remove the default "Publish" box submit area padding for cleaner layout.
		add_meta_box(
			'wpk-slider-shortcode',
			esc_html__( 'Shortcode', 'wp-koumbit-slider' ),
			array( $this, 'render_shortcode_box' ),
			SliderPostType::POST_TYPE,
			'side',
			'high'
		);
	}

	/**
	 * Renders the slide manager meta box.
	 *
	 * The JS in admin-editor.js takes over and renders the interactive UI from
	 * the JSON stored in the hidden textarea.
	 *
	 * @param \WP_Post $post
	 */
	public function render_slides_box( \WP_Post $post ): void {
		wp_nonce_field( 'wpk-slider-save-' . $post->ID, 'wpk_slider_nonce' );

		$raw_slides = get_post_meta( $post->ID, '_wpk_slider_slides', true );
		$slides     = $raw_slides ?: '[]';
		?>
		<div id="wpk-slider-editor" data-post-id="<?php echo esc_attr( (string) $post->ID ); ?>">

			<div id="wpk-slides-list" aria-label="<?php esc_attr_e( 'Slide list', 'wp-koumbit-slider' ); ?>" role="list">
				<p class="wpk-slides-empty description" style="display:none;">
					<?php esc_html_e( 'No slides yet. Click "Add Slide" to get started.', 'wp-koumbit-slider' ); ?>
				</p>
			</div>

			<button type="button" id="wpk-add-slide" class="button button-secondary" style="margin-top:12px;">
				+ <?php esc_html_e( 'Add Slide', 'wp-koumbit-slider' ); ?>
			</button>

			<textarea
				id="wpk-slides-json"
				name="wpk_slider_slides_json"
				style="display:none;"
				aria-hidden="true"
			><?php echo esc_textarea( $slides ); ?></textarea>

		</div>
		<?php
	}

	/**
	 * Renders the settings meta box.
	 *
	 * Settings are grouped into sections with progressive disclosure:
	 * Layout is expanded by default; all other sections are collapsed.
	 *
	 * @param \WP_Post $post
	 */
	public function render_config_box( \WP_Post $post ): void {
		$raw_config = get_post_meta( $post->ID, '_wpk_slider_config', true );
		$cfg        = json_decode( $raw_config ?: '{}', true ) ?: array();
		$d          = self::config_defaults();

		$get = static fn( string $key ) => $cfg[ $key ] ?? $d[ $key ];
		?>
		<div id="wpk-slider-config-form">

			<?php $this->render_section( __( 'Layout', 'wp-koumbit-slider' ), true, function () use ( $get ): void { ?>
				<table class="form-table wpk-config-table" role="presentation">
					<tr>
						<th scope="row"><label for="wpk-cfg-height"><?php esc_html_e( 'Height', 'wp-koumbit-slider' ); ?></label></th>
						<td>
							<input type="text" id="wpk-cfg-height" name="wpk_slider_config[height]" value="<?php echo esc_attr( $get( 'height' ) ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'CSS value: 500px, 60vh, auto', 'wp-koumbit-slider' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wpk-cfg-spv"><?php esc_html_e( 'Slides per view', 'wp-koumbit-slider' ); ?></label></th>
						<td>
							<select id="wpk-cfg-spv" name="wpk_slider_config[slides_per_view]">
								<?php foreach ( array( 1, 2, 3, 4 ) as $n ) : ?>
									<option value="<?php echo esc_attr( (string) $n ); ?>" <?php selected( (int) $get( 'slides_per_view' ), $n ); ?>><?php echo esc_html( (string) $n ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wpk-cfg-gap"><?php esc_html_e( 'Gap between slides', 'wp-koumbit-slider' ); ?></label></th>
						<td>
							<input type="number" id="wpk-cfg-gap" name="wpk_slider_config[space_between]" value="<?php echo esc_attr( (string) (int) $get( 'space_between' ) ); ?>" min="0" max="200" style="width:80px;" /> px
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wpk-cfg-direction"><?php esc_html_e( 'Direction', 'wp-koumbit-slider' ); ?></label></th>
						<td>
							<select id="wpk-cfg-direction" name="wpk_slider_config[direction]">
								<option value="horizontal" <?php selected( $get( 'direction' ), 'horizontal' ); ?>><?php esc_html_e( 'Horizontal', 'wp-koumbit-slider' ); ?></option>
								<option value="vertical" <?php selected( $get( 'direction' ), 'vertical' ); ?>><?php esc_html_e( 'Vertical', 'wp-koumbit-slider' ); ?></option>
							</select>
						</td>
					</tr>
				</table>
			<?php } );

			$this->render_section( __( 'Transitions', 'wp-koumbit-slider' ), false, function () use ( $get ): void { ?>
				<table class="form-table wpk-config-table" role="presentation">
					<tr>
						<th scope="row"><label for="wpk-cfg-effect"><?php esc_html_e( 'Effect', 'wp-koumbit-slider' ); ?></label></th>
						<td>
							<select id="wpk-cfg-effect" name="wpk_slider_config[effect]">
								<option value="slide" <?php selected( $get( 'effect' ), 'slide' ); ?>><?php esc_html_e( 'Slide', 'wp-koumbit-slider' ); ?></option>
								<option value="fade" <?php selected( $get( 'effect' ), 'fade' ); ?>><?php esc_html_e( 'Fade', 'wp-koumbit-slider' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wpk-cfg-speed"><?php esc_html_e( 'Transition speed', 'wp-koumbit-slider' ); ?></label></th>
						<td>
							<input type="number" id="wpk-cfg-speed" name="wpk_slider_config[speed]" value="<?php echo esc_attr( (string) (int) $get( 'speed' ) ); ?>" min="100" max="3000" step="50" style="width:90px;" /> ms
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Loop', 'wp-koumbit-slider' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="wpk_slider_config[loop]" value="1" <?php checked( $get( 'loop' ) ); ?> />
								<?php esc_html_e( 'Loop back to first slide after the last', 'wp-koumbit-slider' ); ?>
							</label>
						</td>
					</tr>
				</table>
			<?php } );

			$this->render_section( __( 'Autoplay', 'wp-koumbit-slider' ), false, function () use ( $get ): void { ?>
				<table class="form-table wpk-config-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Autoplay', 'wp-koumbit-slider' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="wpk_slider_config[autoplay]" value="1" <?php checked( $get( 'autoplay' ) ); ?> />
								<?php esc_html_e( 'Advance slides automatically', 'wp-koumbit-slider' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wpk-cfg-delay"><?php esc_html_e( 'Delay', 'wp-koumbit-slider' ); ?></label></th>
						<td>
							<input type="number" id="wpk-cfg-delay" name="wpk_slider_config[autoplay_delay]" value="<?php echo esc_attr( (string) (int) $get( 'autoplay_delay' ) ); ?>" min="500" max="30000" step="500" style="width:100px;" /> ms
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Pause on hover', 'wp-koumbit-slider' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="wpk_slider_config[autoplay_pause_on_hover]" value="1" <?php checked( $get( 'autoplay_pause_on_hover' ) ); ?> />
								<?php esc_html_e( 'Pause autoplay when the cursor is over the slider', 'wp-koumbit-slider' ); ?>
							</label>
						</td>
					</tr>
				</table>
			<?php } );

			$this->render_section( __( 'Controls', 'wp-koumbit-slider' ), false, function () use ( $get ): void { ?>
				<table class="form-table wpk-config-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Navigation arrows', 'wp-koumbit-slider' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="wpk_slider_config[navigation]" value="1" <?php checked( $get( 'navigation' ) ); ?> />
								<?php esc_html_e( 'Show prev/next arrow buttons', 'wp-koumbit-slider' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wpk-cfg-pagination"><?php esc_html_e( 'Pagination', 'wp-koumbit-slider' ); ?></label></th>
						<td>
							<select id="wpk-cfg-pagination" name="wpk_slider_config[pagination]">
								<option value="bullets"   <?php selected( $get( 'pagination' ), 'bullets' );   ?>><?php esc_html_e( 'Bullet dots', 'wp-koumbit-slider' ); ?></option>
								<option value="fraction"  <?php selected( $get( 'pagination' ), 'fraction' );  ?>><?php esc_html_e( 'Fraction (1 / 4)', 'wp-koumbit-slider' ); ?></option>
								<option value="progress"  <?php selected( $get( 'pagination' ), 'progress' );  ?>><?php esc_html_e( 'Progress bar', 'wp-koumbit-slider' ); ?></option>
								<option value="thumbstrip" <?php selected( $get( 'pagination' ), 'thumbstrip' ); ?>><?php esc_html_e( 'Thumbnail strip', 'wp-koumbit-slider' ); ?></option>
								<option value="none"      <?php selected( $get( 'pagination' ), 'none' );      ?>><?php esc_html_e( 'None', 'wp-koumbit-slider' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Keyboard navigation', 'wp-koumbit-slider' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="wpk_slider_config[keyboard]" value="1" <?php checked( $get( 'keyboard' ) ); ?> />
								<?php esc_html_e( 'Allow left/right arrow keys to navigate slides', 'wp-koumbit-slider' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Touch/swipe', 'wp-koumbit-slider' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="wpk_slider_config[swipe]" value="1" <?php checked( $get( 'swipe' ) ); ?> />
								<?php esc_html_e( 'Enable touch swipe on mobile', 'wp-koumbit-slider' ); ?>
							</label>
						</td>
					</tr>
				</table>
			<?php } );

			$this->render_section( __( 'Advanced', 'wp-koumbit-slider' ), false, function () use ( $get ): void { ?>
				<table class="form-table wpk-config-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Auto height', 'wp-koumbit-slider' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="wpk_slider_config[auto_height]" value="1" <?php checked( $get( 'auto_height' ) ); ?> />
								<?php esc_html_e( 'Height adjusts to the tallest visible slide', 'wp-koumbit-slider' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Center active slide', 'wp-koumbit-slider' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="wpk_slider_config[centered_slides]" value="1" <?php checked( $get( 'centered_slides' ) ); ?> />
								<?php esc_html_e( 'Active slide is always centered (useful with slides_per_view > 1)', 'wp-koumbit-slider' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Free drag mode', 'wp-koumbit-slider' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="wpk_slider_config[free_mode]" value="1" <?php checked( $get( 'free_mode' ) ); ?> />
								<?php esc_html_e( "Slides don't snap to positions — free-scrolling carousel", 'wp-koumbit-slider' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Lazy load images', 'wp-koumbit-slider' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="wpk_slider_config[lazy]" value="1" <?php checked( $get( 'lazy' ) ); ?> />
								<?php esc_html_e( 'Load slide images only when they are about to be visible', 'wp-koumbit-slider' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wpk-cfg-overlay-color"><?php esc_html_e( 'Default overlay colour', 'wp-koumbit-slider' ); ?></label></th>
						<td>
							<input type="color" id="wpk-cfg-overlay-color" name="wpk_slider_config[overlay_color]" value="<?php echo esc_attr( $get( 'overlay_color' ) ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wpk-cfg-overlay-opacity"><?php esc_html_e( 'Default overlay opacity', 'wp-koumbit-slider' ); ?></label></th>
						<td>
							<input type="range" id="wpk-cfg-overlay-opacity" name="wpk_slider_config[overlay_opacity]" value="<?php echo esc_attr( (string) (float) $get( 'overlay_opacity' ) ); ?>" min="0" max="1" step="0.05" style="width:200px;" />
							<output for="wpk-cfg-overlay-opacity"><?php echo esc_html( (string) (float) $get( 'overlay_opacity' ) ); ?></output>
						</td>
					</tr>
				</table>
			<?php } );

			$this->render_section( __( 'Swiper Library', 'wp-koumbit-slider' ), false, function () use ( $get ): void { ?>
				<p class="description" style="padding:0 0 8px;">
					<?php esc_html_e( 'Enables Swiper.js for this slider, which adds 3D and advanced effects. Swiper is loaded from a CDN. Not needed for standard slide or fade effects.', 'wp-koumbit-slider' ); ?>
				</p>
				<table class="form-table wpk-config-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Swiper', 'wp-koumbit-slider' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="wpk_slider_config[use_swiper]" value="1" <?php checked( $get( 'use_swiper' ) ); ?> />
								<?php esc_html_e( 'Use Swiper.js for this slider', 'wp-koumbit-slider' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wpk-cfg-swiper-effect"><?php esc_html_e( 'Swiper effect', 'wp-koumbit-slider' ); ?></label></th>
						<td>
							<select id="wpk-cfg-swiper-effect" name="wpk_slider_config[swiper_effect]">
								<option value="slide"      <?php selected( $get( 'swiper_effect' ), 'slide' );      ?>><?php esc_html_e( 'Slide', 'wp-koumbit-slider' ); ?></option>
								<option value="fade"       <?php selected( $get( 'swiper_effect' ), 'fade' );       ?>><?php esc_html_e( 'Fade', 'wp-koumbit-slider' ); ?></option>
								<option value="cube"       <?php selected( $get( 'swiper_effect' ), 'cube' );       ?>><?php esc_html_e( 'Cube (3D)', 'wp-koumbit-slider' ); ?></option>
								<option value="flip"       <?php selected( $get( 'swiper_effect' ), 'flip' );       ?>><?php esc_html_e( 'Flip (3D)', 'wp-koumbit-slider' ); ?></option>
								<option value="coverflow"  <?php selected( $get( 'swiper_effect' ), 'coverflow' );  ?>><?php esc_html_e( 'Coverflow', 'wp-koumbit-slider' ); ?></option>
								<option value="cards"      <?php selected( $get( 'swiper_effect' ), 'cards' );      ?>><?php esc_html_e( 'Cards', 'wp-koumbit-slider' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Cube, flip, coverflow, and cards automatically set slides-per-view to 1.', 'wp-koumbit-slider' ); ?></p>
						</td>
					</tr>
				</table>
			<?php } );
			?>

			<textarea name="wpk_slider_config_json" id="wpk-config-json" style="display:none;" aria-hidden="true"></textarea>

		</div>
		<?php
	}

	/**
	 * Renders the shortcode helper box in the sidebar.
	 *
	 * @param \WP_Post $post
	 */
	public function render_shortcode_box( \WP_Post $post ): void {
		if ( 'auto-draft' === $post->post_status ) {
			echo '<p class="description">' . esc_html__( 'Save the slider first to get the shortcode.', 'wp-koumbit-slider' ) . '</p>';
			return;
		}
		?>
		<p><?php esc_html_e( 'Copy this shortcode to embed the slider anywhere:', 'wp-koumbit-slider' ); ?></p>
		<code style="display:block;padding:8px;background:#f6f7f7;border:1px solid #c3c4c7;user-select:all;">
			[wpk_slider id="<?php echo esc_html( (string) $post->ID ); ?>"]
		</code>
		<p class="description" style="margin-top:8px;">
			<?php esc_html_e( 'Also available as a block and a widget.', 'wp-koumbit-slider' ); ?>
		</p>
		<?php
	}

	/**
	 * Saves slider meta on post save.
	 *
	 * @param int $post_id
	 */
	public function save( int $post_id ): void {
		if ( ! isset( $_POST['wpk_slider_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpk_slider_nonce'] ) ), 'wpk-slider-save-' . $post_id )
		) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Slides JSON — validate as array.
		$raw_slides = sanitize_textarea_field( wp_unslash( $_POST['wpk_slider_slides_json'] ?? '' ) );
		$decoded    = json_decode( $raw_slides, true );
		if ( is_array( $decoded ) ) {
			update_post_meta( $post_id, '_wpk_slider_slides', wp_slash( $raw_slides ) );
		}

		// Config — merge submitted fields with defaults, then store as JSON.
		$raw_config = isset( $_POST['wpk_slider_config'] ) && is_array( $_POST['wpk_slider_config'] )
			? $_POST['wpk_slider_config'] // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			: array();

		$config = $this->sanitize_config( wp_unslash( $raw_config ) );
		update_post_meta( $post_id, '_wpk_slider_config', wp_slash( wp_json_encode( $config ) ) );
	}

	/**
	 * Enqueues the admin editor assets only on the wpk_slider edit screen.
	 *
	 * @param string $hook
	 */
	public function enqueue( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || SliderPostType::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_script(
			'wpk-slider-admin',
			WPK_SLIDER_URL . 'assets/js/admin-editor.js',
			array( 'wp-i18n' ),
			WPK_SLIDER_VERSION,
			true
		);

		wp_set_script_translations( 'wpk-slider-admin', 'wp-koumbit-slider', WPK_SLIDER_PATH . 'languages' );

		wp_localize_script(
			'wpk-slider-admin',
			'wpkSliderAdmin',
			array(
				'mediaTitle'  => esc_html__( 'Select Slide Image', 'wp-koumbit-slider' ),
				'mediaButton' => esc_html__( 'Use this image', 'wp-koumbit-slider' ),
				'removeLabel' => esc_html__( 'Remove', 'wp-koumbit-slider' ),
				'editLabel'   => esc_html__( 'Edit slide', 'wp-koumbit-slider' ),
				'moveUpLabel' => esc_html__( 'Move up', 'wp-koumbit-slider' ),
				'moveDnLabel' => esc_html__( 'Move down', 'wp-koumbit-slider' ),
			)
		);

		wp_enqueue_style(
			'wpk-slider-admin',
			WPK_SLIDER_URL . 'assets/css/admin.css',
			array(),
			WPK_SLIDER_VERSION
		);
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Renders a collapsible settings section.
	 *
	 * @param string   $title    Section heading.
	 * @param bool     $open     Whether the section starts expanded.
	 * @param callable $content  Callback that echoes the section's content.
	 */
	private function render_section( string $title, bool $open, callable $content ): void {
		$id = 'wpk-section-' . sanitize_key( $title );
		?>
		<div class="wpk-config-section <?php echo $open ? 'wpk-section-open' : ''; ?>">
			<button type="button" class="wpk-section-toggle" aria-expanded="<?php echo $open ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $id ); ?>">
				<span class="wpk-section-icon" aria-hidden="true"></span>
				<?php echo esc_html( $title ); ?>
			</button>
			<div id="<?php echo esc_attr( $id ); ?>" class="wpk-section-body" <?php echo $open ? '' : 'hidden'; ?>>
				<?php $content(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Sanitizes and casts all config fields.
	 *
	 * @param array<string,mixed> $raw
	 * @return array<string,mixed>
	 */
	private function sanitize_config( array $raw ): array {
		$d = self::config_defaults();

		return array(
			'height'                  => sanitize_text_field( $raw['height'] ?? $d['height'] ),
			'effect'                  => in_array( $raw['effect'] ?? '', array( 'slide', 'fade' ), true ) ? $raw['effect'] : $d['effect'],
			'speed'                   => max( 100, min( 5000, (int) ( $raw['speed'] ?? $d['speed'] ) ) ),
			'loop'                    => ! empty( $raw['loop'] ) && '1' === $raw['loop'],
			'autoplay'                => ! empty( $raw['autoplay'] ) && '1' === $raw['autoplay'],
			'autoplay_delay'          => max( 500, min( 30000, (int) ( $raw['autoplay_delay'] ?? $d['autoplay_delay'] ) ) ),
			'autoplay_pause_on_hover' => ! empty( $raw['autoplay_pause_on_hover'] ) && '1' === $raw['autoplay_pause_on_hover'],
			'navigation'              => ! empty( $raw['navigation'] ) && '1' === $raw['navigation'],
			'pagination'              => in_array( $raw['pagination'] ?? '', array( 'bullets', 'fraction', 'progress', 'thumbstrip', 'none' ), true ) ? $raw['pagination'] : $d['pagination'],
			'keyboard'                => ! empty( $raw['keyboard'] ) && '1' === $raw['keyboard'],
			'swipe'                   => ! empty( $raw['swipe'] ) && '1' === $raw['swipe'],
			'slides_per_view'         => max( 1, min( 6, (int) ( $raw['slides_per_view'] ?? $d['slides_per_view'] ) ) ),
			'space_between'           => max( 0, min( 200, (int) ( $raw['space_between'] ?? $d['space_between'] ) ) ),
			'auto_height'             => ! empty( $raw['auto_height'] ) && '1' === $raw['auto_height'],
			'centered_slides'         => ! empty( $raw['centered_slides'] ) && '1' === $raw['centered_slides'],
			'free_mode'               => ! empty( $raw['free_mode'] ) && '1' === $raw['free_mode'],
			'lazy'                    => ! empty( $raw['lazy'] ) && '1' === $raw['lazy'],
			'direction'               => in_array( $raw['direction'] ?? '', array( 'horizontal', 'vertical' ), true ) ? $raw['direction'] : $d['direction'],
			'overlay_color'           => sanitize_hex_color( $raw['overlay_color'] ?? $d['overlay_color'] ) ?? $d['overlay_color'],
			'overlay_opacity'         => max( 0.0, min( 1.0, (float) ( $raw['overlay_opacity'] ?? $d['overlay_opacity'] ) ) ),
			'use_swiper'              => ! empty( $raw['use_swiper'] ) && '1' === $raw['use_swiper'],
			'swiper_effect'           => in_array( $raw['swiper_effect'] ?? '', array( 'slide', 'fade', 'cube', 'flip', 'coverflow', 'cards' ), true ) ? $raw['swiper_effect'] : $d['swiper_effect'],
		);
	}

	/**
	 * Returns the default slider configuration values.
	 *
	 * @return array<string,mixed>
	 */
	public static function config_defaults(): array {
		return array(
			'height'                  => '500px',
			'effect'                  => 'slide',
			'speed'                   => 500,
			'loop'                    => true,
			'autoplay'                => false,
			'autoplay_delay'          => 4000,
			'autoplay_pause_on_hover' => true,
			'navigation'              => true,
			'pagination'              => 'bullets',
			'keyboard'                => true,
			'swipe'                   => true,
			'slides_per_view'         => 1,
			'space_between'           => 0,
			'auto_height'             => false,
			'centered_slides'         => false,
			'free_mode'               => false,
			'lazy'                    => false,
			'direction'               => 'horizontal',
			'overlay_color'           => '#000000',
			'overlay_opacity'         => 0.0,
			'use_swiper'              => false,
			'swiper_effect'           => 'slide',
		);
	}
}
