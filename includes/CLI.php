<?php
/**
 * WP-CLI functionality.
 *
 * @package SmoothGenerator\Classes
 */

namespace WC\SmoothGenerator;

use WP_CLI, WP_CLI_Command;

/**
 * WP-CLI Integration class
 */
class CLI extends WP_CLI_Command {
	/**
	 * Generate products.
	 *
	 * ## OPTIONS
	 *
	 * <amount>
	 * : The amount of products to generate
	 * ---
	 * default: 100
	 * ---
	 *
	 * ## EXAMPLES
	 * wc generate products 100
	 *
	 * @param array $args Arguments specified.
	 * @param array $assoc_args Associative arguments specified.
	 */
	public static function products( $args, $assoc_args ) {
		list( $amount ) = $args;

		$time_start = microtime( true );

		WP_CLI::line( 'Initializing...' );

		// Pre-generate images. Min 20, max 100.
		Generator\Product::seed_images( min( $amount + 19, 100 ) );

		$progress = \WP_CLI\Utils\make_progress_bar( 'Generating products', $amount );

		for ( $i = 1; $i <= $amount; $i++ ) {
			Generator\Product::generate();
			$progress->tick();
		}

		$time_end       = microtime( true );
		$execution_time = round( ( $time_end - $time_start ), 2 );
		$display_time   = $execution_time < 60 ? $execution_time . ' seconds' : human_time_diff( $time_start, $time_end );

		$progress->finish();

		WP_CLI::success( $amount . ' products generated in ' . $display_time );
	}

	/**
	 * Generate orders.
	 *
	 * ## OPTIONS
	 *
	 * <amount>
	 * : The amount of orders to generate
	 * ---
	 * default: 100
	 * ---
	 *
	 * ## EXAMPLES
	 * wc generate orders 100
	 *
	 * @param array $args Arguments specified.
	 * @param array $assoc_args Associative arguments specified.
	 */
	public static function orders( $args, $assoc_args ) {
		list( $amount ) = $args;

		$time_start = microtime( true );

		$amount = (int) $amount;
		if ( empty( $amount ) ) {
			$amount = 100;
		}

		if ( ! empty( $assoc_args['status'] ) ) {
			$status = $assoc_args['status'];
			if ( ! wc_is_order_status( 'wc-' . $status ) ) {
				WP_CLI::log( "The argument \"$status\" is not a valid order status." );
				return;
			}
		}

		if ( $amount > 0 ) {
			Generator\Order::disable_emails();
			$progress = \WP_CLI\Utils\make_progress_bar( 'Generating orders', $amount );
			for ( $i = 1; $i <= $amount; $i++ ) {
				Generator\Order::generate( true, $assoc_args );
				$progress->tick();
			}
			$progress->finish();
		}

		$time_end       = microtime( true );
		$execution_time = round( ( $time_end - $time_start ), 2 );
		$display_time   = $execution_time < 60 ? $execution_time . ' seconds' : human_time_diff( $time_start, $time_end );

		WP_CLI::success( $amount . ' orders generated in ' . $display_time );
	}

	/**
	 * Generate customers.
	 *
	 * ## OPTIONS
	 *
	 * <amount>
	 * : The amount of customers to generate
	 * ---
	 * default: 100
	 * ---
	 *
	 * ## EXAMPLES
	 * wc generate customers 100
	 *
	 * @param array $args Arguments specified.
	 * @param array $assoc_args Associative arguments specified.
	 */
	public static function customers( $args, $assoc_args ) {
		list( $amount ) = $args;

		$time_start = microtime( true );

		Generator\Customer::disable_emails();
		$progress = \WP_CLI\Utils\make_progress_bar( 'Generating customers', $amount );
		for ( $i = 1; $i <= $amount; $i++ ) {
			Generator\Customer::generate();
			$progress->tick();
		}
		$progress->finish();

		$time_end       = microtime( true );
		$execution_time = round( ( $time_end - $time_start ), 2 );
		$display_time   = $execution_time < 60 ? $execution_time . ' seconds' : human_time_diff( $time_start, $time_end );

		WP_CLI::success( $amount . ' customers generated in ' . $display_time );
	}

	/**
	 * Generate coupons.
	 *
	 * ## OPTIONS
	 *
	 * <amount>
	 * : The amount of coupons to generate
	 * ---
	 * default: 100
	 * ---
	 *
	 * ## EXAMPLES
	 * wc generate coupons 100
	 *
	 * @param array $args Arguments specified.
	 * @param array $assoc_args Associative arguments specified.
	 */
	public static function coupons( $args, $assoc_args ) {
		list( $amount ) = $args;

		$time_start = microtime( true );

		$amount = (int) $amount;
		if ( empty( $amount ) ) {
			$amount = 10;
		}

		$min = 5;
		$max = 100;
		if ( ! empty( $assoc_args['min'] ) ) {
			$min = $assoc_args['min'];
		}
		if ( ! empty( $assoc_args['max'] ) ) {
			$max = $assoc_args['max'];
		}

		if ( $amount > 0 ) {
			$progress = \WP_CLI\Utils\make_progress_bar( 'Generating coupons', $amount );
			for ( $i = 1; $i <= $amount; $i++ ) {
				Generator\Coupon::generate( true, $min, $max );
				$progress->tick();
			}
			$progress->finish();
		}

		$time_end       = microtime( true );
		$execution_time = round( ( $time_end - $time_start ), 2 );
		$display_time   = $execution_time < 60 ? $execution_time . ' seconds' : human_time_diff( $time_start, $time_end );

		WP_CLI::success( $amount . ' coupons generated in ' . $display_time );
	}

	/**
	 * Generate terms for the Product Category taxonomy.
	 *
	 * @param array $args Arguments specified.
	 * @param array $assoc_args Associative arguments specified.
	 */
	public static function terms( $args, $assoc_args ) {
		list( $taxonomy, $amount ) = $args;
		$amount = absint( $amount );

		$time_start = microtime( true );

		$progress = \WP_CLI\Utils\make_progress_bar( 'Generating terms', $amount );

		add_action(
			'smoothgenerator_term_generated',
			function () use ( $progress ) {
				$progress->tick();
			}
		);

		$result = Generator\Term::batch( $amount, $taxonomy, $assoc_args );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result );
		}

		$progress->finish();

		$generated      = count( $result );
		$time_end       = microtime( true );
		$execution_time = round( ( $time_end - $time_start ), 2 );
		$display_time   = $execution_time < 60 ? $execution_time . ' seconds' : human_time_diff( $time_start, $time_end );

		WP_CLI::success( $generated . ' terms generated in ' . $display_time );
	}
}

WP_CLI::add_command( 'wc generate products', array( 'WC\SmoothGenerator\CLI', 'products' ) );
WP_CLI::add_command( 'wc generate orders', array( 'WC\SmoothGenerator\CLI', 'orders' ), array(
	'synopsis' => array(
		array(
			'name'     => 'amount',
			'type'     => 'positional',
			'optional' => true,
			'default'  => 100,
		),
		array(
			'name'     => 'date-start',
			'type'     => 'assoc',
			'optional' => true,
		),
		array(
			'name'     => 'date-end',
			'type'     => 'assoc',
			'optional' => true,
		),
		array(
			'name'     => 'status',
			'type'     => 'assoc',
			'optional' => true,
		),
		array(
			'name'     => 'coupons',
			'type'     => 'assoc',
			'optional' => true,
		),
	),
) );
WP_CLI::add_command( 'wc generate customers', array( 'WC\SmoothGenerator\CLI', 'customers' ) );

WP_CLI::add_command( 'wc generate coupons', array( 'WC\SmoothGenerator\CLI', 'coupons' ), array(
	'synopsis' => array(
		array(
			'name'     => 'amount',
			'type'     => 'positional',
			'optional' => true,
			'default'  => 10,
		),
		array(
			'name'     => 'min',
			'optional' => true,
			'type'     => 'assoc',
			'default'  => 5,
		),
		array(
			'name'     => 'max',
			'optional' => true,
			'type'     => 'assoc',
			'default'  => 100,
		),
	),
) );

WP_CLI::add_command( 'wc generate terms', array( 'WC\SmoothGenerator\CLI', 'terms' ), array(
	'shortdesc' => 'Generate product categories.',
	'synopsis'  => array(
		array(
			'name'        => 'taxonomy',
			'type'        => 'positional',
			'description' => 'The taxonomy to generate the terms for.',
			'options'     => array( 'product_cat', 'product_tag' ),
		),
		array(
			'name'        => 'amount',
			'type'        => 'positional',
			'description' => 'The number of terms to generate. Max value 100.',
			'optional'    => true,
			'default'     => 10,
		),
		array(
			'name'        => 'max_depth',
			'type'        => 'assoc',
			'description' => 'The maximum number of hierarchy levels for the terms. A value of 1 means all categories will be top-level. Max value 5. Only applies to taxonomies that are hierarchical.',
			'optional'    => true,
			'options'     => array( 1, 2, 3, 4, 5 ),
			'default'     => 1,
		),
		array(
			'name'        => 'parent',
			'type'        => 'assoc',
			'description' => 'Specify an existing term ID as the parent for the new terms. Only applies to taxonomies that are hierarchical.',
			'optional'    => true,
			'default'     => 0,
		),
	),
	'longdesc' => "## EXAMPLES\n\nwc generate terms product_tag 10\n\nwc generate terms product_cat 50 --max_depth=3",
) );
