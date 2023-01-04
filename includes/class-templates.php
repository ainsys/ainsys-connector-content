<?php

namespace Ainsys\Connector\Content;

use Ainsys\Connector\Master\Hooked;

class Templates implements Hooked {

	public static string $fields = '_ainsys_template_fields';


	public function init_hooks() {

		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box_review' ] );
		add_action( 'save_post_ainsys_template', [ $this, 'save_metabox' ], 10, 2 );
	}


	public function add_meta_box_review( $post_type ): void {

		add_meta_box(
			'ainsys_template_metabox',
			__( 'Template Settings', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN ),
			[ $this, 'render_meta_box_content' ],
			'ainsys_template',
			'side',
			'high',
		);

	}


	/**
	 *
	 * @param  int $post_id
	 *
	 * @return void
	 */
	public function save_metabox( int $post_id ): void {


		if ( ! isset( $_POST['ainsys_template_inner_nonce'] ) ) {
			return;
		}

		$nonce = $_POST['ainsys_template_inner_nonce'];

		if ( ! wp_verify_nonce( $nonce, 'ainsys_template_inner' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( 'ainsys_template' === $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}

		} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( empty( $_POST[ self::$fields ] ) ) {
			return;
		}

		$_ainsys_fields = array_map( 'sanitize_text_field', $_POST[ self::$fields ] );

		update_post_meta( $post_id, self::$fields, $_ainsys_fields );
	}


	/**
	 *
	 * @param  /WP_Post $post
	 */
	public function render_meta_box_content( $post ): void {

		wp_nonce_field( 'ainsys_template_inner', 'ainsys_template_inner_nonce' );

		$value_ainsys_fields = get_post_meta( $post->ID, self::$fields, true );

		$fields = [
			'template_name'    => [
				'label'             => __( 'Template Name', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN ),
				'required'          => true,
				'type'              => 'text',
				'class'             => [ 'ainsys-template-input' ],
				'value'             => $value_ainsys_fields ? $value_ainsys_fields['template_name'] : '',
				'custom_attributes' => [ 'style' => 'width:100%' ],
			],
			'template_default' => [
				'label' => __( 'Template Default', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN ),
				'type'  => 'checkbox',
				'class' => [ 'ainsys-template-checkbox' ],
				'value' => $value_ainsys_fields ? $value_ainsys_fields['template_default'] : 0,
			],
		];

		foreach ( $fields as $key => $field ) {
			$this->metabox_field( $key, $field, $field['value'] );
		}

	}


	public function metabox_field( $key, $args, $value = null ) {

		$defaults = [
			'type'              => 'text',
			'label'             => '',
			'description'       => '',
			'placeholder'       => '',
			'maxlength'         => false,
			'required'          => false,
			'autocomplete'      => false,
			'id'                => $key,
			'class'             => [],
			'label_class'       => [],
			'input_class'       => [],
			'return'            => false,
			'options'           => [],
			'custom_attributes' => [],
			'validate'          => [],
			'default'           => '',
			'autofocus'         => '',
			'priority'          => '',
		];

		$args = wp_parse_args( $args, $defaults );
		$args = apply_filters( 'ainsys_form_field_args', $args, $key, $value );

		if ( is_string( $args['class'] ) ) {
			$args['class'] = [ $args['class'] ];
		}

		$required = '';
		if ( $args['required'] ) {
			$args['class'][] = 'validate-required';
			$required        = '&nbsp;*';
		}

		if ( is_string( $args['label_class'] ) ) {
			$args['label_class'] = [ $args['label_class'] ];
		}

		if ( is_null( $value ) ) {
			$value = $args['default'];
		}

		$custom_attributes         = [];
		$args['custom_attributes'] = array_filter( (array) $args['custom_attributes'], 'strlen' );

		if ( $args['maxlength'] ) {
			$args['custom_attributes']['maxlength'] = absint( $args['maxlength'] );
		}

		if ( ! empty( $args['autocomplete'] ) ) {
			$args['custom_attributes']['autocomplete'] = $args['autocomplete'];
		}

		if ( true === $args['autofocus'] ) {
			$args['custom_attributes']['autofocus'] = 'autofocus';
		}

		if ( $args['description'] ) {
			$args['custom_attributes']['aria-describedby'] = $args['id'] . '-description';
		}

		if ( ! empty( $args['custom_attributes'] ) && is_array( $args['custom_attributes'] ) ) {
			foreach ( $args['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		if ( ! empty( $args['validate'] ) ) {
			foreach ( $args['validate'] as $validate ) {
				$args['class'][] = 'validate-' . $validate;
			}
		}

		$field           = '';
		$label_id        = $args['id'];
		$sort            = $args['priority'] ? : '';
		$field_container = '<p class="form-row %1$s" id="%2$s" data-priority="' . esc_attr( $sort ) . '">%3$s</p>';

		switch ( $args['type'] ) {
			case 'textarea':
				$field .= sprintf(
					'<textarea name="%s[%s]" class="input-text %s" id="%s" placeholder="%s" %s%s%s>%s</textarea>',
					self::$fields,
					esc_attr( $key ),
					esc_attr( implode( ' ', $args['input_class'] ) ),
					esc_attr( $args['id'] ), esc_attr( $args['placeholder'] ),
					empty( $args['custom_attributes']['rows'] ) ? ' rows="2"' : '',
					empty( $args['custom_attributes']['cols'] ) ? ' cols="5"' : '',
					implode( ' ', $custom_attributes ),
					esc_textarea( $value )
				);

				break;
			case 'checkbox':
				$field = sprintf(
					'<label class="checkbox %s" %s><input type="%s" class="input-checkbox %s" name="%s[%s]" id="%s" value="1" %s /> %s%s</label>',
					implode( ' ', $args['label_class'] ),
					implode( ' ', $custom_attributes ),
					esc_attr( $args['type'] ),
					esc_attr( implode( ' ', $args['input_class'] ) ),
					self::$fields,
					esc_attr( $key ),
					esc_attr( $args['id'] ),
					checked( $value, 1, false ),
					$args['label'],
					$required
				);

				break;
			case 'text':
			case 'password':
			case 'datetime':
			case 'datetime-local':
			case 'date':
			case 'month':
			case 'time':
			case 'week':
			case 'number':
			case 'email':
			case 'url':
			case 'tel':
				$field .= sprintf(
					'<input type="%s" class="input-text %s" name="%s[%s]" id="%s" placeholder="%s"  value="%s" %s />',
					esc_attr( $args['type'] ),
					esc_attr( implode( ' ', $args['input_class'] ) ),
					self::$fields,
					esc_attr( $key ), esc_attr( $args['id'] ),
					esc_attr( $args['placeholder'] ),
					esc_attr( $value ),
					implode( ' ', $custom_attributes )
				);

				break;
			case 'hidden':
				$field .= sprintf(
					'<input type="%s" class="input-hidden %s" name="%s[%s]" id="%s" value="%s" %s />',
					esc_attr( $args['type'] ),
					esc_attr( implode( ' ', $args['input_class'] ) ),
					self::$fields,
					esc_attr( $key ),
					esc_attr( $args['id'] ),
					esc_attr( $value ),
					implode( ' ', $custom_attributes )
				);

				break;
			case 'select':
				$field   = '';
				$options = '';

				if ( ! empty( $args['options'] ) ) {
					foreach ( $args['options'] as $option_key => $option_text ) {
						if ( '' === $option_key ) {
							if ( empty( $args['placeholder'] ) ) {
								$args['placeholder'] = $option_text ? : __( 'Choose an option', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN );
							}
							$custom_attributes[] = 'data-allow_clear="true"';
						}

						$options .= sprintf(
							'<option value="%s" %s>%s</option>',
							esc_attr( $option_key ),
							selected( $value, $option_key, false ),
							esc_html( $option_text )
						);
					}

					$field .= sprintf(
						'<select name="%s[%s]" id="%s" class="select %s" %s data-placeholder="%s">%s</select>',
						self::$fields,
						esc_attr( $key ),
						esc_attr( $args['id'] ),
						esc_attr( implode( ' ', $args['input_class'] ) ),
						implode( ' ', $custom_attributes ),
						esc_attr( $args['placeholder'] ),
						$options
					);
				}

				break;
			case 'radio':
				$label_id .= sprintf( '_%s', current( array_keys( $args['options'] ) ) );

				if ( ! empty( $args['options'] ) ) {
					foreach ( $args['options'] as $option_key => $option_text ) {
						$field .= sprintf(
							'<input type="radio" class="input-radio %s" value="%s" name="%s[%s]" %s id="%s_%s"%s />',
							esc_attr( implode( ' ', $args['input_class'] ) ),
							esc_attr( $option_key ),
							self::$fields,
							esc_attr( $key ),
							implode( ' ', $custom_attributes ),
							esc_attr( $args['id'] ),
							esc_attr( $option_key ),
							checked( $value, $option_key, false )
						);
						$field .= sprintf(
							'<label for="%s_%s" class="radio %s">%s</label>',
							esc_attr( $args['id'] ),
							esc_attr( $option_key ),
							implode( ' ', $args['label_class'] ),
							esc_html( $option_text )
						);
					}
				}

				break;
		}

		if ( ! empty( $field ) ) {
			$field_html = '';

			if ( $args['label'] && 'checkbox' !== $args['type'] ) {
				$field_html .= sprintf(
					'<label for="%s" class="%s">%s%s</label>',
					esc_attr( $label_id ),
					esc_attr( implode( ' ', $args['label_class'] ) ),
					wp_kses_post( $args['label'] ),
					$required
				);
			}

			$field_html .= '<span class="ainsys-input-wrapper">' . $field;

			if ( $args['description'] ) {
				$field_html .= sprintf(
					'<span class="description" id="%s-description" aria-hidden="true">%s</span>',
					esc_attr( $args['id'] ),
					wp_kses_post( $args['description'] )
				);
			}

			$field_html .= '</span>';

			$container_class = esc_attr( implode( ' ', $args['class'] ) );
			$container_id    = esc_attr( $args['id'] ) . '_field';
			$field           = sprintf( $field_container, $container_class, $container_id, $field_html );
		}

		$field = apply_filters( 'ainsys_form_field_' . $args['type'], $field, $key, $args, $value );

		$field = apply_filters( 'ainsys_form_field', $field, $key, $args, $value );

		if ( $args['return'] ) {
			return $field;
		}

		echo $field;
	}


	public function register_post_type(): void {

		$labels = [
			'name'          => __( 'Templates', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN ),
			'singular_name' => __( 'Template', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN ),
			'add_new'       => __( 'Add template', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN ),
			'add_new_item'  => __( 'Add new template', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN ),
			'edit_item'     => __( 'Edit template', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN ),
			'new_item'      => __( 'New template', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN ),
			'all_items'     => __( 'All templates', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN ),
			'view_item'     => __( 'View template', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN ),
			'search_items'  => __( 'Search template', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN ),
			'menu_name'     => __( 'AINSYS Templates', AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN ),
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
			'menu_position' => 57,
			'supports'      => [ 'title', 'editor', 'custom-fields' ],
		];
		register_post_type( 'ainsys_template', $args );

	}

}