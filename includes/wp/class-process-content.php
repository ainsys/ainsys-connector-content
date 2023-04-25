<?php

namespace Ainsys\Connector\Content\WP;

use Ainsys\Connector\Master\Helper;
use Ainsys\Connector\Master\Hooked;
use Ainsys\Connector\Master\Settings\Settings;
use Ainsys\Connector\Master\WP\Process;
use Ainsys\Connector\Master\Conditions;

use Ainsys\Connector\Master\Settings\Admin_UI_Entities_Checking;

class Process_Content extends Process implements Hooked {

	protected static string $entity = 'content';

	protected static array $posts_type;


	/**
	 * Initializes WordPress hooks for plugin/components.
	 *
	 * @return void
	 */
	public function init_hooks() {

		self::$posts_type = apply_filters( 'ainsys_process_content_post_type', [ 'post', 'page' ] );

		add_filter( 'ainsys_get_entities_list', [ $this, 'entity_to_list' ], 10, 1 );
		add_filter( 'ainsys_check_connection_request', [ $this, 'check_entity' ], 15, 3 );

		foreach ( self::$posts_type as $post_type ) {
			add_filter( "ainsys_process_create_$post_type", [ $this, 'process_create' ], 15, 3 );
			add_filter( "ainsys_process_update_$post_type", [ $this, 'process_update' ], 15, 1 );
		}

	}


	/**
	 * @param $post_id
	 *
	 * @return void
	 */
	public function process_create( $post_id ): void {

		self::$action = 'CREATE';

		if ( Conditions::has_entity_disable( self::$entity, self::$action ) ) {
			return;
		}

		if ( ! $this->has_entity_data( $post_id ) ) {
			return;
		}

		$fields = apply_filters(
			'ainsys_process_create_fields_' . self::$entity,
			$this->prepare_data( $post_id ),
			$post_id
		);

		$this->send_data( $post_id, self::$entity, self::$action, $fields );

	}


	/**
	 * Sends updated post details to AINSYS.
	 *
	 * @param $post_id
	 */
	public function process_update( $post_id ): void {

		self::$action = 'UPDATE';

		if ( Conditions::has_entity_disable( self::$entity, self::$action ) ) {
			return;
		}

		if ( ! $this->has_entity_data( $post_id ) ) {
			return;
		}

		$fields = apply_filters(
			'ainsys_process_update_fields_' . self::$entity,
			$this->prepare_data( $post_id ),
			$post_id
		);

		$this->send_data( $post_id, self::$entity, self::$action, $fields );
	}


	/**
	 * Sends updated post details to AINSYS.
	 *
	 * @param $post_id
	 *
	 * @return array
	 */
	public function process_checking( $post_id ): array {

		self::$action = 'CHECKING';

		if ( Conditions::has_entity_disable( self::$entity, self::$action ) ) {
			return [];
		}

		$fields = apply_filters(
			'ainsys_process_update_fields_' . self::$entity,
			$this->prepare_data( $post_id ),
			$post_id
		);

		return $this->send_data( $post_id, self::$entity, self::$action, $fields );
	}


	/**
	 * Sends delete post details to AINSYS
	 *
	 * @param  int $post_id
	 *
	 * @return void
	 */
	public function process_delete( int $post_id ): void {

		self::$action = 'DELETE';

		if ( Conditions::has_entity_disable( self::$entity, self::$action ) ) {
			return;
		}

		if ( ! $this->has_entity_data( $post_id ) ) {
			return;
		}

		$fields = apply_filters(
			'ainsys_process_delete_fields_' . self::$entity,
			$this->prepare_data( $post_id ),
			$post_id
		);

		$this->send_data( $post_id, self::$entity, self::$action, $fields );

	}


	/**
	 * @param $entities_list
	 *
	 * @return mixed
	 */

	public function entity_to_list( $entities_list ) {

		$entities_list[ self::$entity ] = __( 'Content', AINSYS_CONNECTOR_TEXTDOMAIN );

		return $entities_list;

	}


	/**
	 * @param                              $result_entity
	 * @param                              $entity
	 * @param  Admin_UI_Entities_Checking  $entities_checking
	 *
	 * @return mixed
	 */
	public function check_entity( $result_entity, $entity, Admin_UI_Entities_Checking $entities_checking ) {

		if ( $entity !== self::$entity ) {
			return $result_entity;
		}

		$entities_checking->make_request = false;

		$result_test   = $this->get_content();
		$result_entity = Settings::get_option( 'check_connection_entity' );

		return $entities_checking->get_result_entity( $result_test, $result_entity, $entity );

	}


	/**
	 * @return array
	 *
	 * Get product data for AINSYS
	 *
	 */
	protected function get_content(): array {

		$args = [
			'post_type'              => 'any',
			'posts_per_page'         => 200,
			'post_status'            => 'public',
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_query'             => [
				'relation' => 'OR',
				[
					'key'     => '_ainsys_entity_data',
					'compare' => 'EXISTS',
				],
				[
					'key'     => '_ainsys_page_lang',
					'compare' => 'EXISTS',
				],
				[
					'key'     => '_ainsys_page_role',
					'compare' => 'EXISTS',
				],
				[
					'key'     => '_ainsys_page_template',
					'compare' => 'EXISTS',
				],
			],
		];

		$query_contents = get_posts( $args );

		if ( empty( $query_contents ) ) {
			return [
				'request'  => __( 'Error: There is no data to check.', AINSYS_CONNECTOR_TEXTDOMAIN ),
				'response' => __( 'Error: There is no data to check.', AINSYS_CONNECTOR_TEXTDOMAIN ),
			];
		}

		$post_ids = Helper::get_rand_array( $query_contents );
		$post_id  = reset( $post_ids );

		return $this->process_checking( $post_id );

	}


	/**
	 * @param $post_id
	 *
	 * @return array|mixed|void
	 */
	public function prepare_data( $post_id ) {

		return get_post_meta( $post_id, '_ainsys_entity_data', true ) ? : [];

	}


	/**
	 * @param $post_id
	 *
	 * @return bool
	 */
	protected function has_entity_data( $post_id ): bool {

		return ! empty( get_post_meta( $post_id, '_ainsys_entity_data', true ) );
	}

}