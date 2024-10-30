<?php

/**
 * LifterLMS CopeCart IPN Class
 * 
 * @since 1.0.0
 */
class LLMS_Copecart_IPN {
	
	/**
	 * LifterLMS CopeCart Intilization
	 * 
	 * @since 1.0.0
	 */
	public static function init() {
		
		// CopeCaprt IPN Response Data
		add_action( 'init', array( __CLASS__, 'copecart_ipn_response'), 10, 1 );

		// add filter to add password to the email notification
		add_filter( 'llms_notification_viewstudent_welcome_get_body', array( __CLASS__, 'copecart_add_password_in_student_welcome_email_callback' ), 10, 2 );
	}

	/**
	 * Password added to registration notification.
	 * 
	 * @since 1.0.1
	 */
	public static function copecart_add_password_in_student_welcome_email_callback( $body, $data ) {

		$notification = new LLMS_Notification( $data->id );
		$user_info = new LLMS_Student( $notification->get( 'user_id' ) );
		$temp_data = get_option( 'lifterlms_copecart_temp_pass_'.$user_info->get( 'user_email' ) );
		
		if( $user_info->get( 'user_email' ) == $temp_data['email'] ){
			$temp_pass = base64_decode( $temp_data['password'] ); 
			$body = str_replace( "{{PASSWORD}}", $temp_pass, $body ); // shortcode for password is {{PASSWORD}}
			delete_option( 'lifterlms_copecart_temp_pass_'.$user_info->get( 'user_email' ) );
		}
		
		return $body;
	}

	/**
	 * CopeCart IPN
	 * 
	 * @since 1.0.0
	 */
	public static function copecart_ipn_response() {
		
		if( !empty( $_GET['llmsipn'] ) ) { // If IPN call from copecart
			
			$enable_log	= true;
			$log_file	= 'copecart.log';
			$log		= time();
			
			$postdata	= file_get_contents( "php://input" );
			$objectData	= json_decode( $postdata, true );
			$action		= $objectData['event_type'];
			
			switch( $action ) {
				
				case 'payment.made': // Case When payment event is made/succesfull from CopeCart response
					
					// Get informations from the IPN
					$copecart_productId	= isset( $objectData['product_id'] ) ? sanitize_text_field( $objectData['product_id'] ) : '';
					$user_email			= isset( $objectData['buyer_email'] ) ? sanitize_email( $objectData['buyer_email'] ) : '';
					$first_name			= isset( $objectData['buyer_firstname'] ) ? sanitize_text_field( $objectData['buyer_firstname'] ) : '';
					$last_name			= isset( $objectData['buyer_lastname'] ) ? sanitize_text_field( $objectData['buyer_lastname'] ) : '';
					$display_name		= isset( $objectData['buyer_lastname'] ) ? sanitize_text_field( $objectData['buyer_lastname'] ) : '';
					$user_nicename		= isset( $objectData['buyer_lastname'] ) ? sanitize_text_field( $objectData['buyer_lastname'] ) : '';
					$role				= LLMS_COPECART_STUDENT_ROLE;
					$password			= wp_generate_password();
					
					if ( !email_exists( $user_email ) ) { // If user not exist.
						
						$userdata	= array(
										'user_login' 	 		=> $user_email,
										'email_address'  		=> $user_email,
										'password' 		 		=> $password,
										'password_confirm'		=> $password,
										'first_name' 			=> $first_name,
										'last_name' 			=> $last_name,
										'role'					=> $role,
										'llms_billing_country'  => 'US'
							);
						
						$temp_data = array( 'email' => $user_email, 'password' => base64_encode( $password ) );
						update_option( 'lifterlms_copecart_temp_pass_' . $user_email, $temp_data );

						//$user_id = wp_insert_user( $userdata ) ;
						$user_id = llms_register_user( $userdata, 'registration', false );
						
						// Log in file user inserted.
						$log .= __( ', New User Created With user_id ', 'copecart-lifterlms' ) . $user_id;
						
					} else {
						
						// Get User from email
						$user		= get_user_by( 'email', $user_email );
						$user_id	= isset( $user->ID ) ? $user->ID : '';
						$log		.= __( ' , User Already Exists With user_id ', 'copecart-lifterlms' ) . $user_id;
					}
					
					$trial_user_check = get_user_meta($user_id,'llms_user_trial',true);
					
					if($trial_user_check)
					{
						$log .= __( ', User Already Enrolled', 'copecart-lifterlms' );
					}
					else{
								// Prepare Query Arguments
								$args	= array (
												'numberposts'	=> 1,
												'post_type'		=> array( 'course', 'llms_membership'),
												'meta_query'	=> array(
													array(
														'key' => '_copecart_meta_key',
														'value' => $copecart_productId,
                                    					'compare' => 'LIKE'
													)
												)
											);
								
								// Get Course/Membership data of relevent CopeCart product.
								$lifterlms_course = get_posts( $args );
								
								if( !empty( $lifterlms_course[0]->ID ) && !empty( $user_id ) ) { // If LifterLMS course or membership ID match
									
									// Enroll student to membership/course
									llms_enroll_student( $user_id, $lifterlms_course[0]->ID );
									
									// Log into the file
									$log .= __( ', User Enrolled To course_id ', 'copecart-lifterlms' ) . $lifterlms_course[0]->ID;
									
								} else { // If not match any course or membership
									
									$log .= __( ', Course Not Available', 'copecart-lifterlms' );
								}
					}
				break;
				case 'payment.trial':

						// Get informations from the IPN
					$copecart_productId	= isset( $objectData['product_id'] ) ? sanitize_text_field( $objectData['product_id'] ) : '';
					$user_email			= isset( $objectData['buyer_email'] ) ? sanitize_email( $objectData['buyer_email'] ) : '';
					$first_name			= isset( $objectData['buyer_firstname'] ) ? sanitize_text_field( $objectData['buyer_firstname'] ) : '';
					$last_name			= isset( $objectData['buyer_lastname'] ) ? sanitize_text_field( $objectData['buyer_lastname'] ) : '';
					$display_name		= isset( $objectData['buyer_lastname'] ) ? sanitize_text_field( $objectData['buyer_lastname'] ) : '';
					$user_nicename		= isset( $objectData['buyer_lastname'] ) ? sanitize_text_field( $objectData['buyer_lastname'] ) : '';
					$role				= LLMS_COPECART_STUDENT_ROLE;
					$password			= wp_generate_password();
					
					if ( !email_exists( $user_email ) ) { // If user not exist.
						
						$userdata	= array(
										'user_login' 	 		=> $user_email,
										'email_address'  		=> $user_email,
										'password' 		 		=> $password,
										'password_confirm'		=> $password,
										'first_name' 			=> $first_name,
										'last_name' 			=> $last_name,
										'role'					=> $role,
										'llms_billing_country'  => 'US'
							);
						
						$temp_data = array( 'email' => $user_email, 'password' => base64_encode( $password ) );
						update_option( 'lifterlms_copecart_temp_pass_' . $user_email, $temp_data );

						//$user_id = wp_insert_user( $userdata ) ;
						$user_id = llms_register_user( $userdata, 'registration', false );
						update_user_meta( $user_id, 'llms_user_trial', 'payment.trial' );
						// Log in file user inserted.
						$log .= __( ', New User Created With user_id ', 'copecart-lifterlms' ) . $user_id;
						
					} else {
						
						// Get User from email
						$user		= get_user_by( 'email', $user_email );
						$user_id	= isset( $user->ID ) ? $user->ID : '';
						$log		.= __( ' , User Already Exists With user_id ', 'copecart-lifterlms' ) . $user_id;
					}
					
					// Prepare Query Arguments
					$args	= array (
									'numberposts'	=> 1,
									'post_type'		=> array( 'course', 'llms_membership'),
									'meta_query'	=> array(
										array(
											'key' => '_copecart_meta_key',
											'value' => $copecart_productId,
                                    		'compare' => 'LIKE'
										)
									)
								);
					
					// Get Course/Membership data of relevent CopeCart product.
					$lifterlms_course = get_posts( $args );
					
					if( !empty( $lifterlms_course[0]->ID ) && !empty( $user_id ) ) { // If LifterLMS course or membership ID match
						
						// Enroll student to membership/course
						llms_enroll_student( $user_id, $lifterlms_course[0]->ID );
						
						// Log into the file
						$log .= __( ', User Enrolled To course_id using copecart trial ', 'copecart-lifterlms' ) . $lifterlms_course[0]->ID;
						
					} else { // If not match any course or membership
						
						$log .= __( ', Course Not Available', 'copecart-lifterlms' );
					}

				break;
					
				case 'payment.refunded':  // Case When payment refunded from CopeCart response
					
					$copecart_productId	= isset( $objectData['product_id'] ) ? sanitize_text_field( $objectData['product_id'] ) : '';
					$user_email			= isset( $objectData['buyer_email'] ) ? sanitize_email( $objectData['buyer_email'] ) : '';
					$first_name			= isset( $objectData['buyer_firstname'] ) ? sanitize_text_field( $objectData['buyer_firstname'] ) : '';
					$last_name			= isset( $objectData['buyer_lastname'] ) ? sanitize_text_field( $objectData['buyer_lastname'] ) : '';
					
					if ( email_exists( $user_email ) ) { // If user already exist
						
						$user		= get_user_by( 'email', $user_email );
						$user_id	= isset( $user->ID ) ? $user->ID : '';
						$log		.= __( ', User Already Exist With user_id ', 'copecart-lifterlms' ) . $user_id;
						
						// get mapped course id from lifterlms
						$args	= array(
									'numberposts'	=> 1,
									'post_type'		=> array('course', 'llms_membership'),
									'meta_query'	=> array(
										array(
											'key' => '_copecart_meta_key',
											'value' => $copecart_productId,
                                    		'compare' => 'LIKE'
										),
									)
								);
						
						// Get course or membership
						$lifterlms_course = get_posts( $args );
						
						if( !empty( $lifterlms_course[0]->ID ) && !empty( $user_id ) ) { // get mapped course id from lifterlms
							
							llms_unenroll_student( $user_id, $lifterlms_course[0]->ID );
							$log .= __( ', User Unenrolled To course_id ', 'copecart-lifterlms' ) . $lifterlms_course[0]->ID;
						} else {
							$log .= __( ', Course Not Found', 'copecart-lifterlms' );
						}
					} else {
						$log .= __( ', User Not Found', 'copecart-lifterlms' );
					}
					/* Case When payment event is refund from CopeCart response End */
				break;
				
				case 'payment.recurring.cancelled':  // Case When payment refunded from CopeCart response
					
					$copecart_productId	= isset( $objectData['product_id'] ) ? sanitize_text_field( $objectData['product_id'] ) : '';
					$user_email			= isset( $objectData['buyer_email'] ) ? sanitize_email( $objectData['buyer_email'] ) : '';
					$first_name			= isset( $objectData['buyer_firstname'] ) ? sanitize_text_field( $objectData['buyer_firstname'] ) : '';
					$last_name			= isset( $objectData['buyer_lastname'] ) ? sanitize_text_field( $objectData['buyer_lastname'] ) : '';
					
					if ( email_exists( $user_email ) ) { // If user already exist
						
						$user		= get_user_by( 'email', $user_email );
						$user_id	= isset( $user->ID ) ? $user->ID : '';
						$log		.= __( ', User Already Exist With user_id ', 'copecart-lifterlms' ) . $user_id;
						
						// get mapped course id from lifterlms
						$args	= array(
										'numberposts'	=> 1,
										'post_type'		=> array('course', 'llms_membership'),
										'meta_query'	=> array(
											array(
												'key' => '_copecart_meta_key',
												'value' => $copecart_productId,
                                    			'compare' => 'LIKE'
											),
										)
									);
						
						// Get course or membership
						$lifterlms_course = get_posts( $args );
						
						if( !empty( $lifterlms_course[0]->ID ) && !empty( $user_id ) ){ // get mapped course id from lifterlms
							
							llms_unenroll_student( $user_id, $lifterlms_course[0]->ID );
							$log .= __( ', User Unenrolled To course_id ', 'copecart-lifterlms' ) . $lifterlms_course[0]->ID;
						} else {
							$log .= __( ', Course Not Found', 'copecart-lifterlms' );
						}
					} else {
						$log .= __( ', User Not Found', 'copecart-lifterlms' );
					}
					/* Case When payment event is refund from CopeCart response End */
				break;
				
				case 'payment.charge_back':  // Case When payment refunded from CopeCart response
					
					$copecart_productId	= isset( $objectData['product_id'] ) ? sanitize_text_field( $objectData['product_id'] ) : '';
					$user_email			= isset( $objectData['buyer_email'] ) ? sanitize_email( $objectData['buyer_email'] ) : '';
					$first_name			= isset( $objectData['buyer_firstname'] ) ? sanitize_text_field( $objectData['buyer_firstname'] ) : '';
					$last_name			= isset( $objectData['buyer_lastname'] ) ? sanitize_text_field( $objectData['buyer_lastname'] ) : '';
					
					if ( email_exists( $user_email ) ) { // If user already exist
						
						$user		= get_user_by( 'email', $user_email );
						$user_id	= isset( $user->ID ) ? $user->ID : '';
						$log		.= __( ', User Already Exist With user_id ', 'copecart-lifterlms' ) . $user_id;
						
						// get mapped course id from lifterlms
						$args	= array(
										'numberposts'	=> 1,
										'post_type'		=> array('course', 'llms_membership'),
										'meta_query'	=> array(
											array(
												'key' => '_copecart_meta_key',
												'value' => $copecart_productId,
                                    			'compare' => 'LIKE'
											),
										)
									);
						
						// Get course or membership
						$lifterlms_course = get_posts( $args );
						
						if( !empty( $lifterlms_course[0]->ID ) && !empty( $user_id ) ){ // get mapped course id from lifterlms
							
							llms_unenroll_student( $user_id, $lifterlms_course[0]->ID );
							$log .= __( ', User Unenrolled To course_id ', 'copecart-lifterlms' ) . $lifterlms_course[0]->ID;
						} else {
							$log .= __( ', Course Not Found', 'copecart-lifterlms' );
						}
					} else {
						$log .= __( ', User Not Found', 'copecart-lifterlms' );
					}
					/* Case When payment event is refund from CopeCart response End */
				break;
				
				case 'payment.failed': 
					$log .= __( ', Payment Failed', 'copecart-lifterlms' ). $objectData['order_id'];
				break;
				
				default:
					$log .= __( ', No Event Found', 'copecart-lifterlms' );
			}
			
			if( $enable_log ) { // Create log file with copecart.log name at root of website setup
				
				$handle	= fopen( $log_file, 'a' ) or die( __( 'Cannot open file:  ', 'copecart-lifterlms' ) . $log_file );
				$data	= json_encode($objectData);
				$data	.= "\n";
				
				fwrite( $handle, $data );
				fclose( $handle );
			}
			
			echo json_encode( array('status' => 200, 'message' => $log ) );
			exit;
		}
	}
}

LLMS_Copecart_IPN::init();
return new LLMS_Copecart_IPN();