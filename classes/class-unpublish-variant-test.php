<?php
/**
 * Handles logic for unpublishing a variant test if an end date is specified.
 *
 * @package Variants
 */

namespace Variants;

/**
 * Class to handle unpublishing a variant test.
 */
class Unpublish_Variant_Test {

	/**
	 * Cron job event name.
	 *
	 * @var string
	 */
	private $event_name = 'variant_unpublish_test';

	/**
	 * Add actions and filters.
	 */
	public function add_actions_filters() {
		// Schedule/unschedule events when the end date is modified.
		add_action( 'updated_postmeta', [ $this, 'trigger_single_cron_event' ], 10, 4 );

		// Run the cron event to unpublish variant test.
		add_action( $this->event_name, [ $this, 'cron_unpublish_variant_test_callback' ] );
	}

	/**
	 * Unpublish a variant test.
	 *
	 * @param int $post_id The post ID.
	 */
	public function cron_unpublish_variant_test_callback( $post_id ) {
		// No post ID provided.
		if ( empty( $post_id ) ) {
			return;
		}

		$variant = get_post( $post_id );

		// Invalid post object.
		if ( ! $variant instanceof \WP_Post ) {
			return;
		}

		// Transition variant to complete.
		wp_update_post(
			[
				'ID'          => $variant->ID,
				'post_status' => 'complete',
			]
		);
	}

	/**
	 * Schedules/unschedules a single cron event to unpublish a variant test.
	 *
	 * @param int    $meta_id    ID of updated metadata entry.
	 * @param int    $object_id  Post ID.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Metadata value. This will be a PHP-serialized string representation of the value
	 *                           if the value is an array, an object, or itself a PHP-serialized string.
	 */
	public function trigger_single_cron_event( $meta_id, $object_id, $meta_key, $meta_value ) {
		// Sanity checks.
		if (
			'variant' !== get_post_type( $object_id )
			|| 'end_date' !== $meta_key
		) {
			return;
		}

		// Create the event args based on the post ID.
		$args = [
			'post_id' => $object_id,
		];

		// Check if a cron event is already scheduled.
		$next_event_timestamp = wp_next_scheduled( $this->event_name, $args );

		// Remove any existing event.
		if ( false !== $next_event_timestamp ) {
			wp_unschedule_event( $next_event_timestamp, $this->event_name, $args );
		}

		// Get the timezone offset.
		$timezone     = wp_timezone();
		$utc_datetime = new \DateTime( 'now', new \DateTimeZone( 'UTC' ) );

		// Schedule the single cron event.
		wp_schedule_single_event(
			(int) $meta_value - (int) $timezone->getOffset( $utc_datetime ), // Unix timestamp with the site offset applied.
			$this->event_name,
			$args
		);
	}
}

$variants_unpublish_tests = new Unpublish_Variant_Test();
$variants_unpublish_tests->add_actions_filters();
