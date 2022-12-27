<?php

namespace Ainsys\Connector\Content\Webhooks;

use Ainsys\Connector\Master\Core;
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


	/**
	 * @param $data
	 * @param $action
	 * @param $object_id
	 *
	 * @return string
	 */
	protected function update( $data, $action, $object_id ): string {

		$response = '';

		if ( is_multisite() ) {

			$sites = get_sites( [
				'fields'        => 'ids',
				'no_found_rows' => false,
			] );

			foreach ( $sites as $site_id ) {
				switch_to_blog( $site_id );

				$response = $this->update_entity_data( $data, $action );

				restore_current_blog();

			}

		} else {
			$response = $this->update_entity_data( $data, $action );
		}

		return $response;

	}


	protected function create( array $data, string $action ): string {

		// TODO: Implement create() method.

		$response = __( 'Error: It is impossible to create content, the CREATE method does not work', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN );

		Logger::save(
			[
				'object_id'       => 0,
				'entity'          => self::$entity,
				'request_action'  => $action,
				'request_type'    => 'create data',
				'request_data'    => $data,
				'server_response' => $response,
				'error'           => 1,
			]
		);

		return $response;
	}


	protected function delete( $object_id, $data, $action ): string {

		// TODO: Implement delete() method.

		$response = __( 'Error: It is impossible to delete content, the DELETE method does not work', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN );

		Logger::save(
			[
				'object_id'       => 0,
				'entity'          => self::$entity,
				'request_action'  => $action,
				'request_type'    => 'delete data',
				'request_data'    => $data,
				'server_response' => $response,
				'error'           => 1,
			]
		);

		return $response;
	}


	protected function update_entity_data( array $data, $action ) {

		if ( empty( $data['pageId'] ) ) {

			$response = __( 'Page id is missing', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN );

			Logger::save(
				[
					'object_id'       => 0,
					'entity'          => self::$entity,
					'request_action'  => $action,
					'request_type'    => 'updated data',
					'request_data'    => $data,
					'server_response' => $response,
					'error'           => 1,
				]
			);

			return $response;

		}

		if ( empty( $data['pageLang'] ) && $this->is_local( $data['pageLang'] ) ) {
			$response = __( 'Page lang is missing', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN );

			Logger::save(
				[
					'object_id'       => 0,
					'entity'          => self::$entity,
					'request_action'  => $action,
					'request_type'    => 'updated data',
					'request_data'    => $data,
					'server_response' => $response,
					'error'           => 1,
				]
			);

			return $response;
		}

		$page = get_post( (int) $data['pageId'] );

		if ( $page && ( 'post' === $page->post_type || 'page' === $page->post_type ) ) {
			$current_data = get_post_meta( $page->ID, '_ainsys_entity_data', true );

			if ( empty( $current_data ) ) {
				$update_data = $data;
			} else {
				$update_data = array_replace( $current_data, $data );
			}

			update_post_meta( $page->ID, '_ainsys_entity_data', $update_data );

			Logger::save(
				[
					'object_id'       => $page->ID,
					'entity'          => self::$entity,
					'request_action'  => $action,
					'request_type'    => 'updated data',
					'request_data'    => $current_data,
					'server_response' => $update_data,
				]
			);

			$response = __( 'The action has been completed successfully. Content imported', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN );
		} else {

			$response = __( 'Error: The page was not found or it does not exist', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN );

			Logger::save(
				[
					'object_id'       => 0,
					'entity'          => self::$entity,
					'request_action'  => $action,
					'request_type'    => 'updated data',
					'request_data'    => $data,
					'server_response' => $response,
					'error'           => 1,
				]
			);

		}

		return $response;
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

}