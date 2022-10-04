<?php

namespace Ainsys\Connector\Content;

use Ainsys\Connector\Content\Webhooks\Handle_Replace_Content;
use Ainsys\Connector\Master\Core;
use Ainsys\Connector\Master\Hooked;
use Ainsys\Connector\Master\Logger;
use Ainsys\Connector\Master\Plugin_Common;
use Ainsys\Connector\Master\Settings\Settings;
use Ainsys\Connector\Master\Settings\Admin_UI;

class Plugin implements Hooked {

	use Plugin_Common;

	/**
	 * @var Core
	 */
	private Core $core;

	/**
	 * @var Logger
	 */
	private Logger $logger;

	/**
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * @var Admin_UI
	 */
	private Admin_UI $admin_ui;


	public function __construct( Core $core, Logger $logger, Settings $settings, Admin_UI $admin_ui ) {

		$this->core     = $core;
		$this->logger   = $logger;
		$this->settings = $settings;
		$this->admin_ui = $admin_ui;

		$this->init_plugin_metadata();

		$this->components['replace_content_webhook'] = new Handle_Replace_Content( $this->logger );
	}


	/**
	 * Links all logic to WP hooks.
	 *
	 * @return void
	 */
	public function init_hooks() {

		add_filter( 'ainsys_status_list', [ $this, 'add_status_of_component' ], 10, 1 );

		foreach ( $this->components as $component ) {
			if ( $component instanceof Hooked ) {
				$component->init_hooks();
			}
		}
	}


	/**
	 * Generates a component status to show on the General tab of the master plugin settings.
	 *
	 * @return array
	 */
	public function add_status_of_component( $status_items = [] ) {

		$status_items['replace_content'] = [
			'title'  => __( 'Replace Content', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN ),
			'active' => true,
		];

		return $status_items;
	}

}
