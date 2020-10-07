<?php
/**
 * Plugin Name: Variants
 * Description: Framework for developers to easily create A/B Tests.
 * Author: Alley
 * Version: 0.1.0
 * Author URI: https://alley.co
 *
 * @package Variants
 */

namespace Variants;

// This plugin depends on Fieldmanager.
if (
	function_exists( '\is_plugin_active' )
	&& ! \is_plugin_active( 'wordpress-fieldmanager/fieldmanager.php' )
) {
	add_action(
		'admin_notices',
		function () {
			?>
			<div class="notice notice-error">
				<p><?php esc_html_e( 'Please install and activate Fieldmanager to use the Variants plugin.', 'variants' ); ?></p>
			</div>
			<?php
		}
	);

	return;
}

/**
 * Gets all variants.
 *
 * @return array An array of variants.
 */
function get_variants() {
	/**
	 * Filters all variants registered.
	 *
	 * @var array $variants {
	 *     @type string   $name The test name.
	 *     @type array    $settings The test variant settings.
	 *     @type callback $display_callback The display callback.
	 * }
	 */
	return apply_filters( 'variants_get_tests', [] );
}

/**
 * Displays a variant test given the control HTML and then variant slug.
 *
 * @param  string $control_html The control HTML.
 * @param  string $slug         The variant slug to display.
 */
function display_variant_test( $control_html, $slug ) {
	$variants = get_variants();

	// No variant.
	if ( empty( $variants[ $slug ] ) ) {
		echo wp_kses_post( $control_html );
		return;
	}

	$active_variant_id = get_active_ab_test( $slug );

	// No active variant.
	if ( empty( $active_variant_id ) ) {
		echo wp_kses_post( $control_html );
		return;
	}

	$variant_settings = get_post_meta( $active_variant_id, 'variant-settings', true );

	// No valid callback.
	if ( ! is_callable( $variants[ $slug ]['display_callback'] ) ) {
		echo wp_kses_post( $control_html );
		return;
	}

	$variant_html = \call_user_func( $variants[ $slug ]['display_callback'], $active_variant_id, $variant_settings );

	// No variant data.
	if ( is_null( $variant_html ) ) {
		echo wp_kses_post( $control_html );
		return;
	}

	echo sprintf(
		'<variant-test
			data-type="%1$s"
			control="%2$s"
			variant="%3$s"
			traffic-percentage="%4$d"
		>
		%5$s
		</variant-test>',
		esc_attr( $slug ),
		esc_attr( wp_json_encode( $control_html, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ),
		esc_attr( wp_json_encode( $variant_html, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ),
		esc_attr( min( 100, max( 0, $variant_settings['traffic_percentage'] ?? 50 ) ) ),
		wp_kses_post( $control_html )
	);
}

/**
 * Enqueue scripts.
 */
function enqueue_scripts() {
	wp_enqueue_script(
		'variants-custom-html-element',
		plugin_dir_url( __FILE__ ) . '/client/src/js/variant.js',
		[],
		'1.0.0',
		true
	);

	wp_localize_script( 'variants-custom-html-element', 'variantsActiveTest', (int) get_latest_ab_test_id() );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_scripts' );

// Helpers.
require_once dirname( __FILE__ ) . '/helpers.php';

// Post Type.
require_once dirname( __FILE__ ) . '/classes/class-variant-post-type.php';

// Unpublish Variant Tests.
require_once dirname( __FILE__ ) . '/classes/class-unpublish-variant-test.php';
