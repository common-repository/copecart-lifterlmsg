<?php

/**
 * LifterLMS CopeCart Setting Class
 * 
 * @since 1.0.0
 */
class LLMS_Copecart_Settings {
	
	/**
	 * LifterLMS CopeCart Intilization
	 * 
	 * @since 1.0.0
	 */
	public static function init() {
		global $wp_post_types;
		// Add filter to when settings page is loaded
		add_filter( 'lifterlms_integrations_settings', array( __CLASS__, 'get_integration_settings' ), 10, 1 );
		
		// Setting Saved
		//add_action( 'lifterlms_settings_save_integrations', array( __CLASS__, 'settings_save' ), 7 );
		
		// Add copecart meta in course CPT
		add_action('add_meta_boxes', array( __CLASS__, 'copecart_course_member_meta_box'), 10, 1 );
		
		// Save CopeCart meta in course CPT
		add_action( 'save_post', array(__CLASS__, 'copecart_save_coursedata'), 10, 1 );
		
		// Save CopeCart meta in member CPT
		add_action( 'save_post', array(__CLASS__, 'copecart_save_memberdata'), 10, 1 );
		
		$tab_setting = get_option('lifterlms_copecart_disable_order_checkout', false );
		
		if( $tab_setting == 'yes' ) {
			
			add_filter( 'lifterlms_register_post_type_order', array( __CLASS__, 'copecart_remove_order_data_post_type' ) );


			add_filter( 'lifterlms_settings_tabs_array', array( __CLASS__, 'copecart_remove_tab_setting' ), 100 );
		}
	}
	
	/**
	 * This function remove checkout tab
	 * array that makes up the settings page. It takes in
	 * 
	 * the CopeCart info to it.
	 * 
	 * @since 1.0.1
	 */
	public static function copecart_remove_tab_setting( $tabs ) {
		
		if( isset( $tabs['checkout'] ) ) {
			unset( $tabs['checkout'] );
		}
		
		return $tabs;
	}

	/**
	 * This function remove order Post type
	 * array that makes up the settings page. It takes in
	 * 
	 * the CopeCart info to it.
	 * 
	 * @since 1.0.1
	 */
	public static function copecart_remove_order_data_post_type( $argument ) {

		if( isset( $argument['show_ui'] ) ) {
			$argument['show_ui']	= false;
		}
		
		return $argument;
	}

	/**
	 * This function remove checkout tab
	 * array that makes up the settings page. It takes in
	 * 
	 * the CopeCart info to it.
	 * 
	 * @since 1.0.1
	 */
	/*public static function copecart_remove_tab_setting( $args ) {
		
		global $wp_filter;
		
		//array_splice( $wp_filter['lifterlms_settings_tabs_array']->callbacks[20], -4, 1 );
		
 		return $args;
	}*/

	/**
	 * This function adds the appropriate content to the
	 * array that makes up the settings page. It takes in
	 * the content passed to it via the filter and then adds
	 * the CopeCart info to it.
	 * 
	 * @since 1.0.0
	 */
	public static function get_integration_settings( $content ) {
		
		// get copecart option
		global $LLMS_Settings_Checkout,$wp_filter;

		$copecart_lists	= get_option( 'llms_copecart_lists', array() );
		$tab_setting = get_option('lifterlms_copecart_disable_order_checkout',false);
		
		// Merge default as 'None'
		array_unshift( $copecart_lists, __( 'None', 'copecart-lifterlms' ) );
		
		// Start add settings
		$content[]	= array(
							'type' => 'sectionstart',
							'id' => 'copecart_options',
							'class' =>'top'
						);
		
		$content[]	= array(
							'title' => __( 'Copecart Payment IPN Settings', 'copecart-lifterlms' ),
							'type' => 'title',
							'desc' => '',
							'id' => 'copecart_options'
						);
		//&courseipn=1	
			$content[]	= array(
								'title'	=> __( 'Course IPN URL', 'copecart-lifterlms' ),
								'type' 	=> 'custom-html',
								'id' 	=> 'lifterlms_copecart_course_ipn',
								'disabled'  => true,
								'readonly'  => true,
								'value'	=> '<tr valign="top">
	                                        <th><label for="lifterlms_copecart_member_ipn">'. __( 'Course IPN URL:', 'copecart-lifterlms' ) . '</label></th>
	                                        <td class="forminp forminp-text">
	                                                '.site_url().'/?llmsipn=true
	                                        </tr>'
							);
			// add here checkbox value 
			$content[]	= array(
								'title'	=> __( 'Hide Checkout settings and Order Menu', 'copecart-lifterlms' ),
								'type'  => 'checkbox',
								'desc'          => __( 'Remove checkout and order.', 'copecart-lifterlms' ) .
								   '<br><em>' . __( 'Enabling this will remove checkout tab and order post-type.', 'copecart-lifterlms' ) . '</em>',
								 'id'            => 'lifterlms_copecart_disable_order_checkout', 
								 'default'       => 'no',
								 'autoload'      => true, 
			);
		
		$content[]	= array(
							'id' => 'copecart_options',
							'type' => 'sectionend',
						);
		
		return $content;
    }
	
	/**
	 * Called before integration settings are saved
	 * If the MC api key has changed, adds action called after settings are saved
	 * which will test the new api key and output a message if there was an error
	 * 
	 * @since 1.0.0
	 */
	public function settings_save() {
		
		// Check CopeCart setting checkbox enable event, API Ley & Signature Fields in Admin
		//if ( $new_key !== $saved_key && $new_sign !== $saved_sign) { // 
		//if ( $new_sign !== $saved_sign) { // 
			//add_action( 'lifterlms_settings_save_integrations', array( $this, 'settings_save_after' ), 777 );
		//}
	}
	
	/**
	 * After Save function.
	 * 
	 * @since 1.0.0
	 */
    public function settings_save_after() {
		
    }
    
	/**
	 * Add metabox in Membership & Course
	 * 
	 * @since 1.0.0
	 */
	public static function copecart_course_member_meta_box() {
		
		// Add Custom Meta Boxes in Course post type for Product Id in Admin
		add_meta_box( 'wporg_box_id', __( 'Copecart Course ID', 'copecart-lifterlms' ), array( __CLASS__, 'copecart_course_meta_html'), 'course');
		
		// // Add Custom Meta Boxes in Membership post type for Product Id in Admin
		add_meta_box( 'wporg_box_id', __( 'Copecart Membership ID', 'copecart-lifterlms' ), array( __CLASS__, 'copecart_member_meta_html'), 'llms_membership');
	}
	
	/**
	 * Add HTML in the Metabox
	 * of Course page
	 * 
	 * @since 1.0.0
	 */
    public static function copecart_course_meta_html( $post ) {
    	
		$copecart_pro_id	= get_post_meta( $post->ID, '_copecart_meta_key', true );
		$copecart_pro_id	= esc_attr( $copecart_pro_id );
		 ?>
		
		<label for="wporg_field"><?php echo __( 'Product ID', 'copecart-lifterlms' );?>:</label>
		<input type="text" name="copecart_pro_id" id="copecart_pro_id" value="<?php echo esc_html( $copecart_pro_id ); ?>">
		<?php
    }
	
	/**
	 * Add HTML in the Metabox
	 * of Membership page
	 * 
	 * @since 1.0.0
	 */
    public static function copecart_member_meta_html( $post ) {
    	
    	$copecart_pro_id	= get_post_meta($post->ID, '_copecart_meta_key', true); 
    	$copecart_pro_id	= esc_attr( $copecart_pro_id );?>
    	
	    <label for="wporg_field"><?php echo __( 'Membership ID', 'copecart-lifterlms' );?>:</label>
	    <input type="text" name="copecart_member_id" id="copecart_member_id" value="<?php echo esc_html( $copecart_pro_id ); ?>"><?php
    }
	
	/**
	 * Save Meta setting of
	 * Course Page.
	 * 
	 * @since 1.0.0
	 */
    public static function copecart_save_coursedata( $post_id ) {
    	
		if( array_key_exists( 'copecart_pro_id', $_POST ) ) {
			
			$copecart_pro_id	= sanitize_text_field( $_POST['copecart_pro_id'] );
			update_post_meta( $post_id, '_copecart_meta_key', $copecart_pro_id );
		}
    }
	
	/**
	 * Save Meta setting of
	 * Membership Page.
	 * 
	 * @since 1.0.0
	 */
	public static function copecart_save_memberdata( $post_id ) {
		
		if( array_key_exists( 'copecart_member_id', $_POST ) ) {
			
			$copecart_member_id	= sanitize_text_field( $_POST['copecart_member_id'] );
			update_post_meta( $post_id, '_copecart_meta_key', $copecart_member_id );
		}
	}
}

LLMS_Copecart_Settings::init();
return new LLMS_Copecart_Settings();