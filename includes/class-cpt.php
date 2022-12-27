<?php

namespace Ainsys\Connector\Content;

use Ainsys\Connector\Master\Hooked;

class CPT  implements Hooked{

	public function init_hooks() {
		add_action( 'init', [ $this, 'templates' ] );
	}
	public function templates(): void {

		$labels = [
			'name'          => __( 'Templates', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN ),
			'singular_name' => __( 'Template', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN ),
			'add_new'       => __( 'Add template', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN ),
			'add_new_item'  => __( 'Add new template', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN ),
			'edit_item'     => __( 'Edit template', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN ),
			'new_item'      => __( 'New template', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN ),
			'all_items'     => __( 'All templates', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN ),
			'view_item'     => __( 'View template', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN ),
			'search_items'  =>__( 'Search template', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN ),
			'menu_name'     => __( 'Templates', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN ),
		];

		$args = [
			'labels'        => $labels,
			'public'        => false,
			'show_ui'       => true,
			'show_in_rest'  => true,
			'has_archive'   => false,
			'query_var'     => true,
			'rewrite'       => [
				'slug'       => 'ainsys_template',
				'with_front' => false,
			],
			'menu_icon'     => 'dashicons-superhero-alt',
			'menu_position' => 5,
			'supports'      => [ 'title', 'editor', 'thumbnail', 'custom-fields' ],
		];
		register_post_type( 'ainsys_template', $args );

	}
}