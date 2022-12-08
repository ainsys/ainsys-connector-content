<?php

namespace Ainsys\Connector\Content;


use Ainsys\Connector\Content\Webhooks\Handle_Replace_Content;
use Ainsys\Connector\Master\DI_Container;
use Ainsys\Connector\Master\Hooked;
use Ainsys\Connector\Master\Logger;
use Ainsys\Connector\Master\Plugin_Common;
use Ainsys\Connector\Master\Settings\Settings;
use Ainsys\Connector\Master\Settings\Admin_UI;

class Plugin implements Hooked {

	use Plugin_Common;

	/**
	 * @var \Ainsys\Connector\Master\DI_Container
	 */
	protected DI_Container $di_container;


	public function __construct() {

		$this->init_plugin_metadata();
		$this->di_container = DI_Container::get_instance();
		$this->components['replace_content_webhook'] = $this->di_container->resolve( Handle_Replace_Content::class );
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

		$status_items['content'] = [
			'title'  => __( 'AINSYS Connector Headless CMS', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN ),
			'slug'         => 'ainsys-connector-content',
			'active'        => $this->is_plugin_active( 'ainsys-connector-content/plugin.php' ),
			'install'        => $this->is_plugin_install( 'ainsys-connector-content/plugin.php' ),
		];

		return $status_items;
	}

}
