<?php
/**
 * Handles logic for creating the Variant post type.
 *
 * @package Variants
 */

namespace Variants;

/**
 * Class to create the Variant post type.
 */
class Variant_Post_Type {

	/**
	 * Name of the custom post type.
	 *
	 * @var string
	 */
	public $name = 'variant';

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Create the post type.
		add_action( 'init', [ $this, 'create_post_type' ] );
		add_filter( 'post_updated_messages', [ $this, 'set_post_updated_messages' ] );

		// Add fields.
		add_action( 'fm_post_' . $this->name, [ $this, 'add_fields' ] );

		// Add custom post status for completed tests.
		add_action( 'init', [ $this, 'add_custom_post_status' ] );
	}

	/**
	 * Adds custom post status.
	 */
	public function add_custom_post_status() {
		register_post_status(
			'complete',
			array(
				'label'                     => _x( 'Complete', 'post', 'variants' ),
				'public'                    => false,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* Translators: 1. Number of items */
				'label_count'               => _n_noop( 'Complete (%s)', 'Complete (%s)', 'variants' ),
			)
		);
	}

	/**
	 * Add fields for creating Variants.
	 */
	public function add_fields() {
		$variants = get_variants();

		$fm = new \Fieldmanager_DatePicker(
			[
				'name'        => 'end_date',
				'use_time'    => true,
				'label'       => __( 'End Date', 'variants' ),
				'description' => __( 'When the test should stop running.', 'variants' ),
			]
		);
		$fm->add_meta_box( __( 'End Date', 'variants' ), [ $this->name ], 'side', 'high' );

		$variant_settings = [];

		// Defaults.
		$variant_settings['type'] = new \Fieldmanager_Select(
			[
				'label'       => __( 'Type', 'variants' ),
				'first_empty' => true,
				'options'     => array_map(
					function( $value ) {
						return $value['name'];
					},
					$variants
				),
			]
		);

		$variant_settings['traffic_percentage'] = new \Fieldmanager_TextField(
			[
				'label'         => __( 'Traffic Percentage', 'variants' ),
				'description'   => __( 'Percentage of traffic shown this variant. (0% - 100%)', 'variants' ),
				'input_type'    => 'number',
				'default_value' => 50,
				'attributes'    => [
					'min' => '0',
					'max' => '100',
				],
			]
		);

		foreach ( $variants as $slug => $variant ) {
			if ( ! empty( $variant['settings'] ) && is_array( $variant['settings'] ) ) {
				// Set the display_if value.
				foreach ( $variant['settings'] as $name => $fm ) {
					$fm->display_if = [
						'src'   => 'type',
						'value' => $slug,
					];

					$variant['settings'][ $name ] = $fm;
				}

				$variant_settings = array_merge( $variant_settings, $variant['settings'] );
			}
		}

		$fm = new \Fieldmanager_Group(
			[
				'name'          => 'variant-settings',
				'add_to_prefix' => false,
				'children'      => $variant_settings,
			]
		);
		$fm->add_meta_box( __( 'Settings', 'variants' ), [ $this->name ], 'normal', 'high' );
	}

	/**
	 * Creates the post type.
	 */
	public function create_post_type() {
		register_post_type(
			$this->name,
			[
				'labels'        => [
					'name'                     => __( 'Variants', 'variants' ),
					'singular_name'            => __( 'Variant', 'variants' ),
					'add_new'                  => __( 'Add New Variant', 'variants' ),
					'add_new_item'             => __( 'Add New Variant', 'variants' ),
					'edit_item'                => __( 'Edit Variant', 'variants' ),
					'new_item'                 => __( 'New Variant', 'variants' ),
					'view_item'                => __( 'View Variant', 'variants' ),
					'view_items'               => __( 'View Variants', 'variants' ),
					'search_items'             => __( 'Search Variants', 'variants' ),
					'not_found'                => __( 'No Variants found', 'variants' ),
					'not_found_in_trash'       => __( 'No Variants found in Trash', 'variants' ),
					'parent_item_colon'        => __( 'Parent Variant:', 'variants' ),
					'all_items'                => __( 'All Variants', 'variants' ),
					'Variants'                 => __( 'Variant Variants', 'variants' ),
					'attributes'               => __( 'Variant Attributes', 'variants' ),
					'insert_into_item'         => __( 'Insert into Variant', 'variants' ),
					'uploaded_to_this_item'    => __( 'Uploaded to this Variant', 'variants' ),
					'featured_image'           => __( 'Featured Image', 'variants' ),
					'set_featured_image'       => __( 'Set featured image', 'variants' ),
					'remove_featured_image'    => __( 'Remove featured image', 'variants' ),
					'use_featured_image'       => __( 'Use as featured image', 'variants' ),
					'filter_items_list'        => __( 'Filter Variants list', 'variants' ),
					'items_list_navigation'    => __( 'Variants list navigation', 'variants' ),
					'items_list'               => __( 'Variants list', 'variants' ),
					'item_published'           => __( 'Variant published.', 'variants' ),
					'item_published_privately' => __( 'Variant published privately.', 'variants' ),
					'item_reverted_to_draft'   => __( 'Variant reverted to draft.', 'variants' ),
					'item_scheduled'           => __( 'Variant scheduled.', 'variants' ),
					'item_updated'             => __( 'Variant updated.', 'variants' ),
					'menu_name'                => __( 'Variants', 'variants' ),
				],
				'public'        => false,
				'show_ui'       => true,
				'menu_icon'     => 'dashicons-chart-line',
				'menu_position' => 80,
				'supports'      => [ 'title', 'revisions' ],
			]
		);
	}

	/**
	 * Set post type updated messages.
	 *
	 * The messages are as follows:
	 *
	 *   1 => "Post updated. {View Post}"
	 *   2 => "Custom field updated."
	 *   3 => "Custom field deleted."
	 *   4 => "Post updated."
	 *   5 => "Post restored to revision from [date]."
	 *   6 => "Post published. {View post}"
	 *   7 => "Post saved."
	 *   8 => "Post submitted. {Preview post}"
	 *   9 => "Post scheduled for: [date]. {Preview post}"
	 *  10 => "Post draft updated. {Preview post}"
	 *
	 * (Via https://github.com/johnbillion/extended-cpts.)
	 *
	 * @param array $messages An associative array of post updated messages with post type as keys.
	 * @return array Updated array of post updated messages.
	 */
	public function set_post_updated_messages( $messages ) {
		global $post;

		$preview_url    = get_preview_post_link( $post );
		$permalink      = get_permalink( $post );
		$scheduled_date = date_i18n( 'M j, Y @ H:i', strtotime( $post->post_date ) );

		$preview_post_link_html   = '';
		$scheduled_post_link_html = '';
		$view_post_link_html      = '';

		if ( is_post_type_viewable( $this->name ) ) {
			// Preview-post link.
			$preview_post_link_html = sprintf(
				' <a target="_blank" href="%1$s">%2$s</a>',
				esc_url( $preview_url ),
				__( 'Preview Variant', 'variants' )
			);

			// Scheduled post preview link.
			$scheduled_post_link_html = sprintf(
				' <a target="_blank" href="%1$s">%2$s</a>',
				esc_url( $permalink ),
				__( 'Preview Variant', 'variants' )
			);

			// View-post link.
			$view_post_link_html = sprintf(
				' <a href="%1$s">%2$s</a>',
				esc_url( $permalink ),
				__( 'View Variant', 'variants' )
			);
		}

		$messages[ $this->name ] = [
			1  => __( 'Variant updated.', 'variants' ) . $view_post_link_html,
			2  => __( 'Custom field updated.', 'variants' ),
			3  => __( 'Custom field updated.', 'variants' ),
			4  => __( 'Variant updated.', 'variants' ),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Variant restored to revision from %s.', 'variants' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			6  => __( 'Variant published.', 'variants' ) . $view_post_link_html,
			7  => __( 'Variant saved.', 'variants' ),
			8  => __( 'Variant submitted.', 'variants' ) . $preview_post_link_html,
			/* translators: %s: date on which the Variant is currently scheduled to be published */
			9  => sprintf( __( 'Variant scheduled for: %s.', 'variants' ), '<strong>' . $scheduled_date . '</strong>' ) . $scheduled_post_link_html,
			10 => __( 'Variant draft updated.', 'variants' ) . $preview_post_link_html,
		];

		return $messages;
	}
}

// Create the post type.
$variants_variant_post_type = new Variant_Post_Type();
