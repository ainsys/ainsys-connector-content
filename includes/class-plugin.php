<?php

namespace Ainsys\Connector\Content;


use Ainsys\Connector\Content\Webhooks\Handle_Replace_Content;
use Ainsys\Connector\Content\WP\Process_Content;
use Ainsys\Connector\Master\DI_Container;
use Ainsys\Connector\Master\Hooked;
use Ainsys\Connector\Master\Plugin_Common;

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
		$this->components['replace_content_process']   = $this->di_container->resolve( Process_Content::class );

		$this->components['replace_content_templates'] = $this->di_container->resolve( Templates::class );
		$this->components['replace_content_templates'] = $this->di_container->resolve( Shortcodes::class );
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

	public static function flush_rewrite_rules(): void {
		flush_rewrite_rules();
	}


}
