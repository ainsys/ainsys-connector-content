<?php

namespace Ainsys\Connector\Content\Webhooks;

use Ainsys\Connector\Master\Core;
use Ainsys\Connector\Master\Hooked;
use Ainsys\Connector\Master\Logger;
use Ainsys\Connector\Master\Webhook_Handler;

class Handle_Replace_Content implements Hooked, Webhook_Handler {

	/**
	 * @var \Ainsys\Connector\Master\Logger
	 */
	protected Logger $logger;

	/**
	 * @var Core
	 */
	private Core $core;


	public function __construct( Core $core, Logger $logger ) {

		$this->core   = $core;
		$this->logger = $logger;
	}


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

		$handlers['content'] = [ $this, 'handler' ];

		return $handlers;
	}


	/**
	 * @param  string $action
	 * @param         $data
	 * @param  int    $object_id
	 *
	 * @return string
	 */
	public function handler( string $action, $data, int $object_id ): string {

		$data     = (array) $data;
		$response = __( 'Action not registered', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN );

		$this->logger::save_log_information(
			[
				'object_id'       => 0,
				'entity'          => 'content',
				'request_action'  => $action,
				'request_type'    => 'incoming data',
				'request_data'    => '',
				'server_response' => serialize( $data ),
			]
		);

		switch ( $action ) {
			case 'CREATE':
			case 'UPDATE':
				if ( $data['pageId'] && $this->is_local( $data['pageLang'] ) ) {

					$this->update_entity_data( $data );

					$response = 'The action has been completed successfully. Content imported';
				} else {
					$response = 'Page id is missing';
				}

				break;
			case 'delete':

		}

		return $response;
	}


	/**
	 * @param  array $data
	 *
	 * @return void
	 */
	protected function update_entity_data( array $data ): void {

		$sites = get_sites( [
			'fields'        => 'ids',
			'no_found_rows' => false,
		] );

		foreach ( $sites as $site_id ) {
			switch_to_blog( $site_id );

			$page = get_post( (int) $data['pageId'] );

			$update_data = null;

			if ( $page && ( 'post' === $page->post_type || 'page' === $page->post_type ) ) {
				$current_data = get_post_meta( $page->ID, '_ainsys_entity_data', true );
				$update_data  = array_replace( $current_data, $data );

				update_post_meta( $page->ID, '_ainsys_entity_data', $update_data );

				$this->logger::save_log_information(
					[
						'object_id'       => $page->ID,
						'entity'          => 'content',
						'request_action'  => 'UPDATE',
						'request_type'    => 'updated data',
						'request_data'    => serialize( $current_data ),
						'server_response' => serialize( $update_data ),
					]
				);
			}

			restore_current_blog();

		}
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