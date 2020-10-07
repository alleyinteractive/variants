# Variants
Framework to allow developers to easily create A/B tests. There are two core assumptions this framework makes:

1. Only one test can be active at a time (two or more test cannot be active at a time)
1. A test only has a control and variant (no multivariant testing)

## Hello World

In this example we will be adding a new test to change the color of post titles. First we need to add our test via the `variants_get_tests` filter.

```php
add_filter(
	'variants_get_tests',
	function () {
		return [
			'post_title_color' => [
				'name'             => 'Post Title Color',
				'settings'         => [ // Depends on Fieldmanager.
					'color' => new \Fieldmanager_ColorPicker(
						[
							'label' => __( 'Color' ),
						]
					),
				],
				'display_callback' => function ( $active_variant_id, $settings ) {
					// No color.
					if ( empty( $settings['color'] ) ) {
						return null;
					}

					return the_title( '<h1 class="entry-title" style="color:' . esc_attr( $settings['color'] ) . ';">', '</h1>', false );
				},
			],
		];
	}
);
```

Once the test is registered we can use the `display_variant_test` helper function to render the test on the frontend. This function will take care of all the logic to show the control HTML or the variant HTML based on what group the user is assigned.

```php
// Original code
the_title( '<h1 class="entry-title">', '</h1>' );

// New code that renders a test.
\Variants\display_variant_test( the_title( '<h1 class="entry-title">', '</h1>', false ), 'post_title_color' );
```

The `display_variant_test` funciton takes in the original HTML string as the first argument and the test slug as the second. If there is an active test matching the slug provided, then a test will be rendered otherwise just the original HTML will be dislayed.

## Sending Data to Google Analytics

Variants is agnostic about how data is collected and reported, but usually most setups will involve sending data to Google Analytics in the form of custom dimensions on the main pageview event. Data about what group the user is assigned to and the active test name are stored in Local Storage. This means you can get the data to send to Google Analytics very easily:

```js
const variantType = window.localStorage.getItem('variant-type');
const variantGroup = window.localStorage.getItem('variant-group');
```
