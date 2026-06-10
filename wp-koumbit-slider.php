<?php
/**
 * Plugin Name:       WP Koumbit Slider
 * Plugin URI:        https://github.com/olssy/wp-koumbit-slider
 * Description:       Beautiful, accessible sliders for any WordPress site — simple by default, fully configurable for any client need.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Koumbit
 * Author URI:        https://koumbit.org
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-koumbit-slider
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'WPK_SLIDER_VERSION', '1.0.0' );
define( 'WPK_SLIDER_FILE', __FILE__ );
define( 'WPK_SLIDER_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPK_SLIDER_URL', plugin_dir_url( __FILE__ ) );
define( 'WPK_SLIDER_BASENAME', plugin_basename( __FILE__ ) );

spl_autoload_register(
	// phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.classFound
	function ( string $class ): void {
		$prefix = 'WPKoumbit\\Slider\\';
		$len    = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}
		$relative = str_replace( '\\', DIRECTORY_SEPARATOR, substr( $class, $len ) );
		$file     = WPK_SLIDER_PATH . 'src' . DIRECTORY_SEPARATOR . $relative . '.php';
		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

use WPKoumbit\Slider\Activator;
use WPKoumbit\Slider\Admin\AdminMenu;
use WPKoumbit\Slider\Admin\EditScreen;
use WPKoumbit\Slider\Frontend\Block;
use WPKoumbit\Slider\Frontend\FrontendAssets;
use WPKoumbit\Slider\Frontend\Shortcode;
use WPKoumbit\Slider\Frontend\Widget;
use WPKoumbit\Slider\I18n;
use WPKoumbit\Slider\PostType\SliderPostType;

/**
 * Plugin bootstrap.
 *
 * @since 1.0.0
 */
final class WPK_Slider_Plugin {

	/** @var WPK_Slider_Plugin|null */
	private static ?WPK_Slider_Plugin $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/**
	 * Wires all hooks and services.
	 *
	 * @since 1.0.0
	 */
	public function init(): void {
		( new I18n() )->init();
		( new SliderPostType() )->init();
		( new Shortcode() )->init();
		( new Block() )->init();
		( new FrontendAssets() )->init();

		add_action( 'widgets_init', static function (): void {
			register_widget( Widget::class );
		} );

		if ( is_admin() ) {
			( new AdminMenu() )->init();
			( new EditScreen() )->init();
		}
	}
}

register_activation_hook( __FILE__, array( Activator::class, 'activate' ) );

add_action( 'plugins_loaded', array( WPK_Slider_Plugin::instance(), 'init' ), 10 );
