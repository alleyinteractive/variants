<?php
/**
 * Base file for all helper functions.
 *
 * @package Variants
 */

namespace Variants;

/**
 * Gets the active AB test of a given type.
 *
 * @param  string $type AB test type.
 * @return null|array  Null if no test is found or an array of the test data.
 */
function get_active_ab_test( $type ) {
	// No type.
	if ( empty( $type ) ) {
		return null;
	}

	// Never return a test for AMP or feeds.
	if (
		( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() )
		|| is_feed()
	) {
		return null;
	}

	// Get the latest AB test.
	$latest_variant_id = \Variants\get_latest_ab_test_id();

	// No test found.
	if ( empty( $latest_variant_id ) ) {
		return null;
	}

	// Get the test meta data.
	$meta = get_post_meta( $latest_variant_id, 'variant-settings', true );

	// Active Ab test is not of this type.
	if ( empty( $meta['type'] ) || $type !== $meta['type'] ) {
		return null;
	}

	return $latest_variant_id;
}

/**
 * Returns the latest AB Test post ID.
 *
 * @return int The latest AB Test post ID or 0 if none exist.
 */
function get_latest_ab_test_id() {
	$cache_key  = 'variant_latest_id';
	$variant_id = get_transient( $cache_key );

	// We have a cache hit, so lets use that.
	if ( false !== $variant_id ) {
		return (int) $variant_id;
	}

	$variant_query = new \WP_Query(
		[
			'post_type'      => 'variant',
			'posts_per_page' => 1,
		]
	);

	if ( ! empty( $variant_query->posts[0] ) && $variant_query->posts[0] instanceof \WP_Post ) {
		$variant_id = $variant_query->posts[0]->ID;
	}

	// Save this to the cache.
	set_transient( $cache_key, $variant_id, DAY_IN_SECONDS );

	return (int) $variant_id;
}

/**
 * Clear the ab test cache when an ab test is saved.
 *
 * @param int $post_id The current post ID.
 */
function clear_get_latest_variant_id_cache( $post_id ) {
	delete_transient( 'variant_latest_id' );
}
add_action( 'save_post_variant', __NAMESPACE__ . '\clear_get_latest_variant_id_cache' );
