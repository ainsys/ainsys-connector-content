<?php

namespace Ainsys\Connector\Content\WP;

use Ainsys\Connector\Master\Hooked;
use Ainsys\Connector\Master\Logger;
use Ainsys\Connector\Master\Settings\Settings;
use Ainsys\Connector\Master\WP\Process;
use Ainsys\Connector\Master\Conditions;
use Ainsys\Connector\Woocommerce\Helper;
use Ainsys\Connector\Woocommerce\Prepare_Product_Data;
use Ainsys\Connector\Woocommerce\Prepare_Product_Variation_Data;
use Ainsys\Connector\Master\Settings\Admin_UI_Entities_Checking;

class Process_Content extends Process implements Hooked {

	protected static string $entity = 'content';


	/**
	 * Initializes WordPress hooks for plugin/components.
	 *
	 * @return void
	 */
	public function init_hooks() {

		add_filter( 'ainsys_get_entities_list', [ $this, 'add_product_entity_to_list' ], 10, 1 );

		/**
		 * Check entity connection for products
		 */
		add_filter( 'ainsys_check_connection_request', [ $this, 'check_product_entity' ], 15, 3 );
		add_filter( 'ainsys_process_create_fields_page', [ $this, 'process_create' ], 15, 3 );

		//add_action( 'woocommerce_new_product', 'on_product_save', 10, 1 );
		//add_action( 'save_post_product', [ $this, 'process_update' ], 10, 4 );
		//add_action( 'deleted_post', [ $this, 'process_delete' ], 10, 2 );

	}


	/**
	 * @param $entities_list
	 *
	 * @return mixed
	 */

	public function add_product_entity_to_list( $entities_list ) {

		$entities_list[self::$entity] = __( 'Content', AINSYS_CONNECTOR_TEXTDOMAIN );

		return $entities_list;

	}



	public function process_create( $data, $post ) {

		self::$action = 'CREATE';
		error_log( print_r( $data, 1 ) );
		return $data;
		/*if ( Conditions::has_entity_disable( self::$entity, self::$action ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check if it is a REST Request
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		// Check if it is an autosave or a revision.
		if ( wp_is_post_autosave( $id ) || wp_is_post_revision( $id ) ) {
			return;
		}

		$fields = apply_filters(
			'ainsys_process_create_fields_' . self::$entity,
			$this->prepare_data( $id ),
			$id
		);

		$this->send_data( $id, self::$entity, self::$action, $fields );*/

	}


	/**
	 * Sends updated post details to AINSYS.
	 *
	 * @param       $product_id
	 * @param       $product
	 * @param       $update
	 */
	public function process_update( $product_id, $product, $update ): void {

		self::$action = 'UPDATE';

		if ( Conditions::has_entity_disable( self::$entity, self::$action ) ) {
			return;
		}

		if ( ! $this->is_updated( $product_id, $update ) ) {
			return;
		}

		if ( get_post_type( $product_id ) !== self::$entity ) {
			return;
		}

		$fields = apply_filters(
			'ainsys_process_update_fields_' . self::$entity,
			$this->prepare_data( $product_id ),
			$product_id
		);

		$this->send_data( $product_id, self::$entity, self::$action, $fields );
	}


	/**
	 * Sends delete post details to AINSYS
	 *
	 * @param  int $post_id
	 * @param      $post
	 *
	 * @return void
	 */
	public function process_delete( int $product_id, $post ): void {

		self::$action = 'DELETE';

		if ( Conditions::has_entity_disable( self::$entity, self::$action ) ) {
			return;
		}

		$fields = apply_filters(
			'ainsys_process_delete_fields_' . self::$entity,
			$this->prepare_data( $product_id ),
			$product_id
		);

		$this->send_data( $product_id, self::$entity, self::$action, $fields );

	}


	/**
	 * Sends updated post details to AINSYS.
	 *
	 * @param       $product_id
	 * @param       $product
	 * @param       $update
	 *
	 * @return array
	 */
	public function process_checking( $product_id, $product, $update ): array {

		self::$action = 'CHECKING';

		if ( Conditions::has_entity_disable( self::$entity, self::$action ) ) {
			return [];
		}

		if ( ! $this->is_updated( $product_id,$product, $update ) ) {
			return [];
		}

		if ( get_post_type( $product_id ) !== self::$entity ) {
			return [];
		}

		$fields = apply_filters(
			'ainsys_process_update_fields_' . self::$entity,
			$this->prepare_data( $product_id ),
			$product_id
		);

		return $this->send_data( $product_id, self::$entity, self::$action, $fields );
	}


	/**
	 * @param                              $result_entity
	 * @param                              $entity
	 * @param  Admin_UI_Entities_Checking  $entities_checking
	 *
	 * @return mixed
	 */
	public function check_product_entity( $result_entity, $entity, Admin_UI_Entities_Checking $entities_checking ) {

		if ( $entity !== self::$entity ) {
			return $result_entity;
		}

		$entities_checking->make_request = false;
		$result_test                     = $this->get_product();
		$result_entity                   = Settings::get_option( 'check_connection_entity' );

		return $entities_checking->get_result_entity( $result_test, $result_entity, $entity );

	}


	/**
	 * @return array
	 *
	 * Get product data for AINSYS
	 *
	 */
	protected function get_product(): array {

		$products = wc_get_products( [
			'limit' => 50,
		] );

		if ( ! empty( $products ) ) {
			return [
				'request'  => __( 'Error: There is no data to check.', AINSYS_CONNECTOR_TEXTDOMAIN ),
				'response' => __( 'Error: There is no data to check.', AINSYS_CONNECTOR_TEXTDOMAIN ),
			];
		}

		$product    = end( $products );
		$product_id = $product->get_id();

		return $this->process_checking( $product_id, $product, true );

	}


	/**
	 * @param $product_id
	 *
	 * @return array|mixed|void
	 * Prepare product data, for send to AINSYS
	 */
	public function prepare_data( $product_id ) {

		$data = [];


		return $data;

	}

}