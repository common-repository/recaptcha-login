<?php

add_action( 'admin_init', 'recaptchalogin_options_init' );
add_action( 'admin_menu', 'recaptchalogin_options_add_page' );

/**
 * Define Options
 */
global $recaptchalogin_options;

$recaptchalogin_options = (
	array( 
		array(
			'', 
			array(
				array(
					'name' 		=> 'recaptchalogin_heading', 
					'std' 		=> __('Login', 'recaptchalogin'), 
					'label' 	=> __('Logged out heading', 'recaptchalogin'),  
					'desc'		=> __('Heading for the widget when the user is logged out.', 'recaptchalogin')
				),
				array(
					'name' 		=> 'recaptchalogin_welcome_heading', 
					'std' 		=> __('Welcome %username%', 'recaptchalogin'), 
					'label' 	=> __('Logged in heading', 'recaptchalogin'),  
					'desc'		=> __('Heading for the widget when the user is logged in.', 'recaptchalogin')
				),
			)
		),
		array(
			__('Redirects', 'recaptchalogin'), 
			array(
				array(
					'name' 		=> 'recaptchalogin_login_redirect', 
					'std' 		=> '', 
					'label' 	=> __('Login redirect', 'recaptchalogin'),  
					'desc'		=> __('Url to redirect the user to after login. Leave blank to use the current page.', 'recaptchalogin'),
					'placeholder' => 'http://'
				),
				array(
					'name' 		=> 'recaptchalogin_logout_redirect', 
					'std' 		=> '', 
					'label' 	=> __('Logout redirect', 'recaptchalogin'),  
					'desc'		=> __('Url to redirect the user to after logout. Leave blank to use the current page.', 'recaptchalogin'),
					'placeholder' => 'http://'
				),
			)
		),
		array(
			__('Authentication', 'recaptchalogin'), 
			array(
				array(
					'name' 		=> 'recaptchalogin_color', 
					'std' 		=> 'red', 
					'label' 	=> __('reCAPTCHA Color', 'recaptchalogin'),  
					'type'          => 'select',
                                        'options'       => array(   'red'   => 'Red (Default)',
                                                                    'white' => 'White',
                                                                    'blackglass' => 'Black Glass',
                                                                    'clean' => 'Clean'
                                                            ),
                                        'desc'		=> '',
				),
				array(
					'name' 		=> 'recaptchalogin_public_key', 
					'std' 		=> '', 
					'label' 	=> __('Public Key', 'recaptchalogin'),  
					'desc'		=> '',
				),
				array(
					'name' 		=> 'recaptchalogin_private_key', 
					'std' 		=> '', 
					'label' 	=> __('Private Key', 'recaptchalogin'),  
					'desc'		=> '',
				),
				array(
					'name' 		=> 'recaptchalogin_desc1',
					'label' 	=> '',  
					'desc'		=> __('<p>These keys are required before you are able to do anything else. You can get the keys <a href="https://www.google.com/recaptcha/admin/create" title="Get your reCAPTCHA API Keys">here</a>.<br/>
                                            Be sure not to mix them up! The public and private keys are not interchangeable!</p>', 'recaptchalogin'),
					'type' 		=> 'heading'
				),
			)
		),
		array(
			__('Links', 'recaptchalogin'), 
			array(
				array(
					'name' 		=> 'recaptchalogin_register_link', 
					'std' 		=> '1', 
					'label' 	=> __('Show Register Link', 'recaptchalogin'),  
					'desc'		=> sprintf( __('The <a href="%s" target="_blank">\'Anyone can register\'</a> setting must be turned on for this option to work.', 'recaptchalogin'), admin_url('options-general.php')),
					'type' 		=> 'checkbox'
				),
				array(
					'name' 		=> 'recaptchalogin_forgotton_link', 
					'std' 		=> '1', 
					'label' 	=> __('Show Lost Password Link', 'recaptchalogin'),  
					'desc'		=> '',
					'type' 		=> 'checkbox'
				),
				array(
					'name' 		=> 'recaptchalogin_avatar', 
					'std' 		=> '1', 
					'label' 	=> __('Show Logged in Avatar', 'recaptchalogin'),  
					'desc'		=> '',
					'type' 		=> 'checkbox'
				),
				array(
					'name' 		=> 'recaptchalogin_logged_in_links', 
					'std' 		=> "<a href='".  admin_url()."'>".__('Dashboard','recaptchalogin')."</a>\n<a href=\"".admin_url()."/profile.php\">".__('Profile','recaptchalogin')."</a>", 
					'label' 	=> __('Logged in links', 'recaptchalogin'),  
					'desc'		=> sprintf( __('One link per line. Note: Logout link will always show regardless. Tip: Add <code>|true</code> after a link to only show it to admin users or alternatively use a <code>|user_capability</code> and the link will only be shown to users with that capability (see <a href=\'http://codex.wordpress.org/Roles_and_Capabilities\' target=\'_blank\'>Roles and Capabilities</a>).<br/> You can also type <code>%%USERNAME%%</code> and <code>%%USERID%%</code> which will be replaced by the user\'s info. Default: <br/>&lt;a href="%s/wp-admin/"&gt;Dashboard&lt;/a&gt;<br/>&lt;a href="%s/wp-admin/profile.php"&gt;Profile&lt;/a&gt;', 'recaptchalogin'), get_bloginfo('wpurl'), get_bloginfo('wpurl') ),
					'type' 		=> 'textarea'
				),
			)
		)
	)
);
	
/**
 * Init plugin options to white list our options
 */
function recaptchalogin_options_init() {

	global $recaptchalogin_options;

	foreach($recaptchalogin_options as $section) {
		foreach($section[1] as $option) {
			if (isset($option['std'])) add_option($option['name'], $option['std']);
			register_setting( 'recaptchalogin-widget-login', $option['name'] );
		}
	}

	
}

/**
 * Load up the menu page
 */
function recaptchalogin_options_add_page() {
	add_options_page(__('reCAPTCHA Login','recaptchalogin'), __('reCAPTCHA Login','recaptchalogin'), 'manage_options', 'recaptcha-login', 'recaptchalogin_options');
}

/**
 * Create the options page
 */
function recaptchalogin_options() {
	global $recaptchalogin_options;

	if ( ! isset( $_REQUEST['settings-updated'] ) ) $_REQUEST['settings-updated'] = false;

	?>
	<div class="wrap">
		<?php screen_icon(); echo "<h2>" .__( 'Widget Login Options','recaptchalogin') . "</h2>"; ?>
		
		<form method="post" action="options.php">
		
			<?php settings_fields( 'recaptchalogin-widget-login' ); ?>
	
			<?php
			foreach($recaptchalogin_options as $section) {
			
				if ($section[0]) echo '<h3 class="title">'.$section[0].'</h3>';
				
				echo '<table class="form-table">';
				
				foreach($section[1] as $option) {
					
					echo '<tr valign="top"><th scope="row">'.$option['label'].'</th><td>';
					
					if (!isset($option['type'])) $option['type'] = '';
					
					switch ($option['type']) {
						
						case "checkbox" :
						
							$value = get_option($option['name']);
							
							?><input id="<?php echo $option['name']; ?>" name="<?php echo $option['name']; ?>" type="checkbox" value="1" <?php checked( '1', $value ); ?> /><?php
						
						break;
						case "select" :
						
							$value  = get_option($option['name']);
                                                        $color  = $option['options'];
                                                        
                                                        echo '<select id="'.$option['name'].'" name="'.$option['name'].'">';
                                                        
                                                        foreach($color as $select => $item){
                                                            
                                                            $selected   = ($value == $select)? ' selected':'';
                                                            echo '<option value="'.$select.'"'.$selected.'>'.$item.'</option>';
                                                        }
                                                        
                                                        echo '</select>';
							
						break;
						case "heading" :
							
							echo $option['desc'];
						
						break;
						case "textarea" :
							
							$value = get_option($option['name']);
							
							?><textarea id="<?php echo $option['name']; ?>" class="large-text" cols="50" rows="10" name="<?php echo $option['name']; ?>" placeholder="<?php if (isset($option['placeholder'])) echo $option['placeholder']; ?>"><?php echo esc_textarea( $value ); ?></textarea><?php
						
						break;
						default :
							
							$value = get_option($option['name']);
							
							?><input id="<?php echo $option['name']; ?>" class="regular-text" type="text" name="<?php echo $option['name']; ?>" value="<?php esc_attr_e( $value ); ?>" placeholder="<?php if (isset($option['placeholder'])) echo $option['placeholder']; ?>" /><?php
						
						break;
						
					}
					
					if ($option['desc'] && $option['type'] != 'heading') echo '<span class="description">'.$option['desc'].'</span>';
					
					echo '</td></tr>';
				}
				
				echo '</table>';
				
			}
			?>

			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e( 'Save Options', 'recaptchalogin'); ?>" />
			</p>
		</form>
	</div>
	<?php
}