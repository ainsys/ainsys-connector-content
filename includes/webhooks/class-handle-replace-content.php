<?php

namespace Ainsys\Connector\Content\Webhooks;

use Ainsys\Connector\Master\Hooked;
use Ainsys\Connector\Master\Logger;
use Ainsys\Connector\Master\Webhook_Handler;
use Ainsys\Connector\Master\Webhooks\Handle;

class Handle_Replace_Content extends Handle implements Hooked, Webhook_Handler {

	protected static string $entity = 'content';


	/**
	 * Initializes WordPress hooks for component.
	 *
	 * @return void
	 */
	public function init_hooks(): void {

		add_filter( 'ainsys_webhook_action_handlers', [ $this, 'register_webhook_handler' ], 10, 1 );

		add_filter( 'the_content', [ $this, 'replace_content' ], 100 );
	}


	public function register_webhook_handler( $handlers = [] ) {

		$handlers[ self::$entity ] = [ $this, 'handler' ];

		return $handlers;
	}


	protected function create( array $data, string $action ): array {

		return $this->create_entity_data( $data, $action );

	}


	/**
	 * @param $data
	 * @param $action
	 * @param $object_id
	 *
	 * @return array
	 */
	protected function update( $data, $action, $object_id ): array {

		return $this->update_entity_data( $data, $action, $object_id );

	}


	protected function delete( $object_id, $data, $action ): array {

		// TODO: Implement delete() method.
		return [
			'id'      => 0,
			'message' => $this->handle_error(
				$data,
				'',
				__( 'Error: It is impossible to delete content, the DELETE method does not work', AINSYS_CONNECTOR_TEXTDOMAIN ),
				self::$entity,
				$action
			),
		];

	}


	//TODO: сделать проверку на главную страницу
	protected function create_entity_data( array $data, $action ): array {

		if ( empty( $data['pageUrl'] ) ) {
			return [
				'id'      => 0,
				'message' => $this->handle_error(
					$data,
					'',
					sprintf( __( 'Error: %s is missing', AINSYS_CONNECTOR_TEXTDOMAIN ), 'pageUrl', $object_id ),
					self::$entity,
					$action
				),
			];
		}

		if ( empty( $data['pageRole'] ) ) {

			return [
				'id'      => 0,
				'message' => $this->handle_error(
					$data,
					'',
					sprintf( __( 'Error: %s is missing', AINSYS_CONNECTOR_TEXTDOMAIN ), 'pageRole', $object_id ),
					self::$entity,
					$action
				),
			];
		}

		if ( empty( $data['pageLang'] ) ) {

			return [
				'id'      => 0,
				'message' => $this->handle_error(
					$data,
					'',
					sprintf( __( 'Error: %s is missing', AINSYS_CONNECTOR_TEXTDOMAIN ), 'pageLang', $object_id ),
					self::$entity,
					$action
				),
			];

		}

		[ $page_slug, $page ] = $this->get_page( $data['pageUrl'] );

		if ( $page ) {
			return [
				'id'      => 0,
				'message' => $this->handle_error(
					$data,
					'',
					__( 'Error: Page with this URL already exist', AINSYS_CONNECTOR_TEXTDOMAIN ),
					self::$entity,
					$action
				),
			];
		}

		$page_user = ! empty( $data['pageUser'] ) ? absint( $data['pageUser'] ) : 1;

		if ( ! empty( $data['pageTitle'] ) ) {
			$post_title = sanitize_text_field( $data['pageTitle'] );
		} else {
			$post_title = sprintf( '%s - %s', __( 'Page created via AINSYS system', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN ), $this->sanitize_field( $page_slug ) );
		}

		$post_template = ! empty( $data['pageTemplate'] ) ? $this->sanitize_field( $data['pageTemplate'] ) : '';
		$post_role     = $this->sanitize_field( $data['pageRole'] );

		$post_content = $this->get_template_content( $post_template, $post_role );

		$post_args = [
			'post_title'   => $post_title,
			'post_content' => $post_content,
			'post_name'    => $this->sanitize_field( $page_slug ),
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_author'  => $page_user,
			'meta_input'   => [
				'_ainsys_entity_data'   => $data,
				'_ainsys_page_lang'     => sanitize_text_field( $data['pageLang'] ),
				'_ainsys_page_role'     => sanitize_text_field( $data['pageRole'] ),
				'_ainsys_page_template' => sanitize_text_field( $data['pageTemplate'] ),
			],
		];

		$result = wp_insert_post( $post_args, true, false );

		if ( ! is_wp_error( $result ) ) {

			$entity_data = get_post_meta( $result, '_ainsys_entity_data', true );

			foreach ( $entity_data as $key => $val ) {
				if ( 'pageId' === $key ) {
					$entity_data[ $key ] = $result;
				}
			}

			update_post_meta( $result, '_ainsys_entity_data', $entity_data );
		}

		return [
			'id'      => is_wp_error( $result ) ? 0 : $result,
			'message' => $this->get_message( $result, $data, self::$entity, $action ),
		];

	}


	protected function update_entity_data( array $data, $action, $object_id ): array {

		if ( empty( $data['pageUrl'] ) ) {
			return [
				'id'      => 0,
				'message' => $this->handle_error(
					$data,
					'',
					sprintf( __( 'Error: %s is missing', AINSYS_CONNECTOR_TEXTDOMAIN ), 'pageUrl', $object_id ),
					self::$entity,
					$action
				),
			];
		}

		if ( empty( $data['pageRole'] ) ) {

			return [
				'id'      => 0,
				'message' => $this->handle_error(
					$data,
					'',
					sprintf( __( 'Error: %s is missing', AINSYS_CONNECTOR_TEXTDOMAIN ), 'pageRole', $object_id ),
					self::$entity,
					$action
				),
			];
		}

		if ( empty( $data['pageLang'] ) ) {

			return [
				'id'      => 0,
				'message' => $this->handle_error(
					$data,
					'',
					sprintf( __( 'Error: %s is missing', AINSYS_CONNECTOR_TEXTDOMAIN ), 'pageLang', $object_id ),
					self::$entity,
					$action
				),
			];

		}

		[ $page_slug, $page ] = $this->get_page( $data['pageUrl'] );

		if ( ! $page ) {

			return [
				'id'      => 0,
				'message' => $this->handle_error(
					$data,
					'',
					__( 'Error: Page not found or it does not exist', AINSYS_CONNECTOR_TEXTDOMAIN ),
					self::$entity,
					$action
				),
			];

		}

		$current_data = get_post_meta( $page->ID, '_ainsys_entity_data', true );

		if ( empty( $current_data ) ) {
			$update_data = $data;
		} else {
			$update_data = array_replace( $current_data, $data );
		}

		$result = update_post_meta( $page->ID, '_ainsys_entity_data', $update_data );

		return [
			'id'      => $result ? $page->ID : 0,
			'message' => $this->get_message( $result, $update_data, self::$entity, $action ),
		];

	}


	protected function is_local( $local ): bool {

		return $this->data_local( $local ) === $this->current_local();
	}


	/**
	 * @param  string $local
	 *
	 * @return string
	 */
	protected function data_local( string $local ): string {

		$local = str_replace( '/', '', $local );

		return mb_strtolower( str_replace( '-', '_', $local ), 'UTF-8' );
	}


	/**
	 * @return string
	 */
	protected function current_local(): string {

		return mb_strtolower( str_replace( '-', '_', get_locale() ), 'UTF-8' );
	}


	/**
	 * Replaces codes like {xxx} with jso values in the text
	 *
	 * @param  string $text - text to search/replace.
	 *
	 * @throws \JsonException
	 * @package ainsys
	 */
	public function replace_content( string $text ): string {


		$data = get_post_meta( get_the_ID(), '_ainsys_entity_data', true );
		//error_log( print_r( $data, 1 ) );
		if ( $data ) {

			$text = $this->get_data_ainsys( $data, $text );

		} else {

			$text = $this->get_data_file( $text );
		}

		return $text;
	}


	/**
	 * @param         $data
	 * @param  string $text
	 *
	 * @return string
	 */
	protected function get_data_ainsys( $data, string $text ): string {

		$keys   = [];
		$values = [];

		foreach ( $data as $key => $value ) {

			if ( is_string( $value ) ) {
				$keys[]   = $key;
				$values[] = $value;
			}

		}

		return str_replace( $keys, $values, $text );
	}


	/**
	 * @param  string $text
	 *
	 * @return string
	 * @throws \JsonException
	 */
	protected function get_data_file( string $text ): string {

		$file_id   = get_field( 'json_file' ) ? trim( get_field( 'json_file' ) ) : '';
		$json_file = get_attached_file( $file_id );

		if ( ! $json_file ) {
			return $text;
		}

		if ( ! file_exists( $json_file ) ) {
			return $text;
		}

		$options = [
			"ssl" => [
				"verify_peer"      => false,
				"verify_peer_name" => false,
			],
		];

		$json = json_decode(
			file_get_contents( $json_file, false, stream_context_create( $options ) ),
			true,
			512,
			JSON_THROW_ON_ERROR
		);

		$keys   = [];
		$values = [];

		foreach ( $json as $key => $value ) {
			$keys[]   = $key;
			$values[] = trim( str_replace( '\n', '', $value ) );
		}

		return str_replace( $keys, $values, $text );

	}


	/**
	 * @param        $object_id
	 * @param        $action
	 * @param  array $data
	 * @param        $message
	 *
	 * @return string|void
	 */
	protected function get_error_data( $object_id, $action, array $data, $message ) {


		Logger::save(
			[
				'object_id'       => $object_id,
				'entity'          => self::$entity,
				'request_action'  => $action,
				'request_type'    => 'incoming',
				'request_data'    => serialize( $data ),
				'server_response' => $message,
				'error'           => 1,
			]
		);

		return $message;
	}


	/**
	 * @param $field
	 *
	 * @return string
	 */
	protected function sanitize_field( $field ): string {

		$field = rawurlencode( urldecode( $field ) );

		return trim( str_replace( '/', '', sanitize_text_field( $field ) ) );
	}


	/**
	 * @param  string $post_template
	 * @param  string $post_role
	 *
	 * @return string
	 */
	protected function get_template_content( string $post_template, string $post_role ): string {

		$args = [
			'meta_query'     => [
				'relation' => 'AND',
				[
					'key'   => '_ainsys_template_fields_template_name',
					'value' => $post_template,
				],
				[
					'key'   => '_ainsys_template_fields_template_role',
					'value' => $post_role,
				],
			],
			'post_type'      => 'ainsys_template',
			'posts_per_page' => 200,
		];

		$templates = get_posts( $args );

		return $templates[0]->post_content ? : '';
	}


	/**
	 * @param $page_url
	 *
	 * @return array
	 */
	protected function get_page( $page_url ): array {

		$page_slug = wp_parse_url( '//' . $page_url, PHP_URL_PATH );
		$page      = get_page_by_path( $page_slug );

		return [ $page_slug, $page ];
	}

}