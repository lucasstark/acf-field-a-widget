<?php

class acf_field_a_widget extends acf_field {
	/*
	 *  __construct
	 *
	 *  This function will setup the field type data
	 *
	 *  @type	function
	 *  @date	5/03/2014
	 *  @since	5.0.0
	 *
	 *  @param	n/a
	 *  @return	n/a
	 */

	public function __construct() {

		/*
		 *  name (string) Single word, no spaces. Underscores allowed
		 */

		$this->name = 'a_widget';


		/*
		 *  label (string) Multiple words, can include spaces, visible when selecting a field type
		 */

		$this->label = __( 'Single Widget', 'acf-a_widget' );


		/*
		 *  category (string) basic | content | choice | relational | jquery | layout | CUSTOM GROUP NAME
		 */

		$this->category = 'widgets';


		/*
		 *  defaults (array) Array of default settings which are merged into the field object. These are used later in settings
		 */

		$this->defaults = array(
		    'widget' => ''
		);


		/*
		 *  l10n (array) Array of strings that are used in JavaScript. This allows JS strings to be translated in PHP and loaded via:
		 *  var message = acf._e('a_widget', 'error');
		 */

		$this->l10n = array();

		// do not delete!
		parent::__construct();
	}

	public function load_field( $field ) {
		global $wp_widget_factory;
		$widget = isset( $field['value'] ) && isset( $field['value']['the_widget'] ) ? $field['value']['the_widget'] : $field['widget'];

		// This is a chance for plugins to replace missing widgets
		$the_widget = !empty( $wp_widget_factory->widgets[$widget] ) ? $wp_widget_factory->widgets[$widget] : false;
		
		if (empty($the_widget)) {
			return $field;
		}
		
		// get any ACF field groups attached to the widget. 
		$field_groups = acf_get_field_groups( array('widget' => $the_widget->id_base) );

		$field['sub_fields'] = array();
		if ( !empty( $field_groups ) ) {
			foreach ( $field_groups as $group ) {
				$field['sub_fields'] += acf_get_fields( $group );
			}
		}

		return $field;
	}

	/*
	 *  render_field_settings()
	 *
	 *  Create extra settings for your field. These are visible when editing a field
	 *
	 *  @type	action
	 *  @since	3.6
	 *  @date	23/01/13
	 *
	 *  @param	$field (array) the $field being edited
	 *  @return	n/a
	 */

	public function render_field_settings( $field ) {
		global $wp_widget_factory;
		$widgets = array();
		$options = array();
		foreach ( $wp_widget_factory->widgets as $class => $widget_obj ) {
			$widgets[$class] = array(
			    'class' => $class,
			    'title' => !empty( $widget_obj->name ) ? $widget_obj->name : __( 'Untitled Widget', 'siteorigin-panels' ),
			    'description' => !empty( $widget_obj->widget_options['description'] ) ? $widget_obj->widget_options['description'] : '',
			    'installed' => true,
			    'groups' => array(),
			);

			$options[$class] = (!empty( $widget_obj->name ) ? $widget_obj->name : __( 'Untitled Widget', 'a_widget' )) . ': ' . (!empty( $widget_obj->widget_options['description'] ) ? $widget_obj->widget_options['description'] : '');
		}

		asort( $options );

		acf_render_field_setting( $field, array(
		    'label' => __( 'Widget', 'acf-a_widget' ),
		    'instructions' => __( 'Choose the widget to display', 'acf-a_widget' ),
		    'type' => 'select',
		    'choices' => $options,
		    'name' => 'widget'
		) );
	}

	/*
	 *  render_field()
	 *
	 *  Create the HTML interface for your field
	 *
	 *  @param	$field (array) the $field being rendered
	 *
	 *  @type	action
	 *  @since	3.6
	 *  @date	23/01/13
	 *
	 *  @param	$field (array) the $field being edited
	 *  @return	n/a
	 */

	public function render_field( $field ) {
		global $wp_widget_factory;

		$widget = isset( $field['value'] ) && isset( $field['value']['the_widget'] ) ? $field['value']['the_widget'] : $field['widget'];
		$the_widget = !empty( $wp_widget_factory->widgets[$widget] ) ? $wp_widget_factory->widgets[$widget] : false;
		
		if (empty($the_widget)) {
			return;
		}
		
		$the_widget->number = isset($field['value']) && isset($field['value']['number']) ? $field['value']['number'] : uniqid();
		$the_widget->id = isset($field['value']) && isset($field['value']['widget_id']) ? $field['value']['widget_id'] : $the_widget->id_base . '-' . $the_widget->number;
		$instance = apply_filters( 'widget_form_callback', isset( $field['value'] ) && isset( $field['value']['instance'] ) ? $field['value']['instance'] : array(), $the_widget );

		if ( false !== $instance ) {
			ob_start();
			$the_widget->form( $instance );
			//do_action_ref_array( 'in_widget_form', array( &$the_widget, &$return, $instance ) );
			$form = ob_get_clean();

			// Convert the widget field naming into ones that Page Builder uses
			$exp = preg_quote( $the_widget->get_field_name( '____' ) );
			$exp = str_replace( '____', '(.*?)', $exp );
			$final = preg_replace( '/' . $exp . '/', $field['name'] . '[widget_fields][$1]', $form );

			echo $final;
		}
		
		//Now the regular widget fields are rendered.   
		//Render any additional fields that were added to the widget using advanced custom fields widget fields. 
		$el = 'div';
		foreach ( $field['sub_fields'] as $sub_field ) {
			// add value
			if ( isset( $field['value'][$sub_field['key']] ) ) {
				// this is a normal value
				$sub_field['value'] = $field['value'][$sub_field['key']];
			} elseif ( isset( $sub_field['default_value'] ) ) {
				// no value, but this sub field has a default value
				$sub_field['value'] = $sub_field['default_value'];
			}
			
			//Setup a field prefix so in our update value function we can grab the correct info. 
			$sub_field['prefix'] = "{$field['name']}";
			acf_render_field_wrap( $sub_field, $el );
		}
	}

	/*
	 *  input_admin_enqueue_scripts()
	 *
	 *  This action is called in the admin_enqueue_scripts action on the edit screen where your field is created.
	 *  Use this action to add CSS + JavaScript to assist your render_field() action.
	 *
	 *  @type	action (admin_enqueue_scripts)
	 *  @since	3.6
	 *  @date	23/01/13
	 *
	 *  @param	n/a
	 *  @return	n/a
	 */

	public function input_admin_enqueue_scripts() {

		$dir = plugin_dir_url( __FILE__ );


		// register & include JS
		wp_register_script( 'acf-input-a_widget', "{$dir}js/input.js" );
		wp_enqueue_script( 'acf-input-a_widget' );

		// register & include CSS
		wp_register_style( 'acf-input-a_widget', "{$dir}css/input.css" );
		wp_enqueue_style( 'acf-input-a_widget' );
	}

	/*
	 *  update_value()
	 *
	 *  This filter is applied to the $value before it is saved in the db
	 *
	 *  @type	filter
	 *  @since	3.6
	 *  @date	23/01/13
	 *
	 *  @param	$value (mixed) the value found in the database
	 *  @param	$post_id (mixed) the $post_id from which the value was loaded
	 *  @param	$field (array) the field array holding all the field options
	 *  @return	$value
	 */

	public function update_value( $value, $post_id, $field ) {
		global $wp_widget_factory;
		$widget = isset( $field['value'] ) && isset( $field['value']['the_widget'] ) ? $field['value']['the_widget'] : $field['widget'];

		// This is a chance for plugins to replace missing widgets
		$the_widget = !empty( $wp_widget_factory->widgets[$widget] ) ? $wp_widget_factory->widgets[$widget] : false;

		if (empty($the_widget)){
			return;
		}
		
		if ( !empty( $value ) ) {
			
			$value['widget_id_base'] = $the_widget->id_base;
			$value['number'] = isset( $value['number'] ) ? $value['number'] : uniqid();
			$value['widget_id'] = $value['widget_id_base'] . '-' . $value['number'];
			$value['instance'] = array();
			$value['the_widget'] = $widget;
			
			if ( isset( $value['widget_fields'] ) && class_exists( $widget ) && method_exists( $widget, 'update' ) ) {
				$the_widget = new $widget;
				$value['instance'] = $the_widget->update( $value['widget_fields'], $value['widget_fields'] );
			}

			unset( $value['widget_fields'] );
			// update sub fields

			if ( !$field['sub_fields'] ) {
				return $value;
			}

			foreach ( $field['sub_fields'] as $sub_field ) {
				$v = false;
				if ( isset( $value[$sub_field['key']] ) ) {
					$v = $value[$sub_field['key']];
				} elseif ( isset( $value[$sub_field['name']] ) ) {
					$v = $value[$sub_field['name']];
				} else {
					// input is not set (hidden by conditioanl logic)
					continue;
				}

				//Save ACF Widget Fields using a mock widget ID
				acf_update_value( $v, 'widget_' . $value['widget_id'], $sub_field );

				// modify name for save
				$sub_field['name'] = "{$field['name']}_{$sub_field['name']}";
				// update value
				acf_update_value( $v, $post_id, $sub_field );
			}
		}

		return $value;
	}

	/*
	 *  load_value()
	 *
	 *  This filter is applied to the $value after it is loaded from the db
	 *
	 *  @type	filter
	 *  @since	3.6
	 *  @date	23/01/13
	 *
	 *  @param	$value (mixed) the value found in the database
	 *  @param	$post_id (mixed) the $post_id from which the value was loaded
	 *  @param	$field (array) the field array holding all the field options
	 *  @return	$value
	 */

	public function load_value( $value, $post_id, $field ) {

		// bail early if no value
		if ( empty( $value ) || !isset( $field['sub_fields'] ) || empty( $field['sub_fields'] ) ) {
			return $value;
		}

		// loop through sub fields
		foreach ( array_keys( $field['sub_fields'] ) as $j ) {
			// get sub field
			$sub_field = $field['sub_fields'][$j];
			
			if ( isset( $value['widget_id'] ) && !empty( $value['widget_id'] ) ) {
				$acf_widget_api_key = 'widget_' . $value['widget_id'];
				$sub_value = acf_get_value( $acf_widget_api_key, $sub_field );
			} else {
				$sub_value = null;
			}
			
			//Store the value so we don't have to reterive it again in the render function. 
			$value[$sub_field['key']] = $sub_value;
		}

		return $value;
	}

	public function update_field( $field ) {
		// remove sub fields
		unset( $field['sub_fields'] );
		return $field;
	}

}

// create field
new acf_field_a_widget();
