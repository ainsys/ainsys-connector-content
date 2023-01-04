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

		$this->components['replace_content_webhook']   = $this->di_container->resolve( Handle_Replace_Content::class );
		$this->components['replace_content_templates'] = $this->di_container->resolve( Templates::class );
	}


	/**
	 * Links all logic to WP hooks.
	 *
	 * @return void
	 */
	public function init_hooks() {

		foreach ( $this->components as $component ) {
			if ( $component instanceof Hooked ) {
				$component->init_hooks();
			}
		}
	}


}
