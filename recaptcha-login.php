<?php
/**
 * Plugin Name: reCAPTCHA Login
 * Plugin URI: http://onnayokheng.com
 * Description: Integrates reCAPTCHA anti-spam solutions with login form wordpress
 * Version: 1.0
 * Author: Onnay Okheng and Siriokun
 * Author URI: http://onnayokheng.com
 */

// add admin settings
if (is_admin()) require_once dirname( __FILE__ ) . '/admin.php';


/* Init widget/styles/scripts */
function recaptchalogin_widget_init() {
    
        if( is_admin() && get_option('recaptchalogin_public_key') == '' && get_option('recaptchalogin_private_key') == '' ){
            
            add_action('admin_notices', 'recaptchalogin_missing_keys_notice');
            function recaptchalogin_missing_keys_notice(){
            
                $options_url = admin_url('options-general.php?page=recaptcha-login');
                $error_message = sprintf(__('You enabled reCAPTCHA LOGIN, but some of the reCAPTCHA API Keys seem to be missing <a href="%s" title="reCAPTCHA LOGIN Options">Fix this</a>', 'recaptchalogin'), $options_url);

                echo '<div class="error"><p><strong>' . $error_message . '</strong></p></div>';

                return;
                
            }
        }
        
        if(!is_admin()) wp_enqueue_style('recaptcha-fluid', plugin_dir_url(__FILE__) . 'recaptcha-fluid.css');
    
	// Register widget
	class RL_Widget extends WP_Widget {
	    function RL_Widget() {
                $this->WP_Widget('recaptchalogin-widget', __('RL Form Login', 'recaptchalogin'), array('description' => __( 'Sidebar form login.','recaptchalogin') ));  
	    }
	    function widget($args, $instance) {    
	        
	        recaptchalogin_widget_form( $args );
	
	    }
	} 
	register_widget('RL_Widget');
	
}
add_action('init', 'recaptchalogin_widget_init', 1);

function recaptchalogin_widget_form( $args ){
	global $user_ID, $current_user;
	
	/* To add more extend i.e when terms came from themes - suggested by dev.xiligroup.com */
	$defaults = array(
		'thelogin'=>'',
		'thewelcome'=>'',
		'theusername'=>__('Username:','recaptchalogin'),
		'thepassword'=>__('Password:','recaptchalogin'),
		'theremember'=>__('Remember me','recaptchalogin'),
		'theregister'=>__('Register','recaptchalogin'),
		'thepasslostandfound'=>__('Password Lost and Found','recaptchalogin'),
		'thelostpass'=>	__('Lost your password?','recaptchalogin'),
		'thelogout'=> __('Logout','recaptchalogin')
	);
	
	$args = array_merge($defaults, $args);
	extract($args);		
	
	get_currentuserinfo();

	if (is_user_logged_in()) {
	
		// User is logged in
		global $current_user;
  		get_currentuserinfo();
		
		if (empty($thewelcome)) $thewelcome = str_replace('%username%',ucwords($current_user->display_name),get_option('recaptchalogin_welcome_heading'));
		
		echo $before_widget . $before_title .$thewelcome. $after_title;
		
		if (get_option('recaptchalogin_avatar')=='1') echo '<div class="avatar_container">'.get_avatar($user_ID, $size = '38').'</div>';
		
		echo '<ul class="pagenav">';
		
		if(isset($current_user->user_level) && $current_user->user_level) $level = $current_user->user_level;
				
		$links = do_shortcode(trim(get_option('recaptchalogin_logged_in_links')));
		
		$links = explode("\n", $links);
		if (sizeof($links)>0)
		foreach ($links as $l) {
			$l = trim($l);
			if (!empty($l)) {
				$link = explode('|',$l);
				if (isset($link[1])) {
					$cap = strtolower(trim($link[1]));
					if ($cap=='true') {
						if (!current_user_can( 'manage_options' )) continue;
					} else {
						if (!current_user_can( $cap )) continue;
					}
				}
				// Parse %USERNAME%
				$link[0] = str_replace('%USERNAME%',sanitize_title($current_user->user_login),$link[0]);
				$link[0] = str_replace('%username%',sanitize_title($current_user->user_login),$link[0]);
				// Parse %USERID%
				$link[0] = str_replace('%USERID%',$current_user->ID,$link[0]);
				$link[0] = str_replace('%userid%',$current_user->ID,$link[0]);
				echo '<li class="page_item">'.$link[0].'</li>';
			}
		}
		
		$redir = trim(stripslashes(get_option('recaptchalogin_logout_redirect')));
		if (!$redir || empty($redir)) $redir = recaptchalogin_current_url('nologout');
		
		echo '<li class="page_item"><a href=" ' . wp_logout_url( $redir ) . '">' . $thelogout . '</a></li></ul>';
		
	} else {
	
		// User is NOT logged in!!!
		
		if (empty($thelogin)) $thelogin = get_option('recaptchalogin_heading');
		
		echo $before_widget . $before_title .'<span>'. $thelogin .'</span>' . $after_title;

		global $login_errors;

		if ( is_wp_error($login_errors) && $login_errors->get_error_code() ) {
			
			foreach ($login_errors->get_error_messages() as $error) {
				$error = apply_filters('recaptchalogin_error', $error);
				echo '<div class="login_error">' . $error . "</div>\n";
				break;
			}
				
		}
		
		// Get redirect URL
		$redirect_to = trim(stripslashes(get_option('recaptchalogin_login_redirect')));
		
		if ( empty( $redirect_to ) ) {
			if ( isset( $_REQUEST['redirect_to'] ) ) 
				$redirect_to = esc_url( $_REQUEST['redirect_to'] );
			else
				$redirect_to = recaptchalogin_current_url('nologout');
		}
		
		if ( force_ssl_admin() ) 
			$redirect_to = str_replace( 'http:', 'https:', $redirect_to );
		
		// login form
		$recaptchalogin_post_url = ( force_ssl_login() || force_ssl_admin() ) ? str_replace('http://', 'https://', recaptchalogin_current_url() ) : recaptchalogin_current_url();
		
		$login_form_args = apply_filters( 'recaptchalogin_form_args', array(
	        'echo' 			=> true,
	        'redirect' 		=> esc_attr( $redirect_to ), 
	        'label_username' 	=> $theusername,
	        'label_password' 	=> $thepassword,
	        'label_remember' 	=> $theremember,
	        'label_log_in' 		=> __('Login &raquo;', 'recaptchalogin'),
	        'remember' 		=> true,
	        'value_remember' 	=> true
	    ) );
		
		wp_login_form( $login_form_args );
			
		// Output other links
		$links = '';	
		if ( get_option('users_can_register') && get_option('recaptchalogin_register_link') == '1' ) { 

			if ( ! is_multisite() ) {
			
				$links .= '<li><a href="' . apply_filters( 'recaptchalogin_register_url', site_url('wp-login.php?action=register', 'login') ) . '" rel="nofollow">' . $theregister . '</a></li>';

			} else {
				
				$links .= '<li><a href="' . apply_filters( 'recaptchalogin_register_url', site_url('wp-signup.php', 'login') ) . '" rel="nofollow">' . $theregister . '</a></li>';

			}
		}
		if ( get_option( 'recaptchalogin_forgotton_link' ) == '1' )
			$links .= '<li><a href="' . apply_filters( 'recaptchalogin_lostpassword_url', wp_lostpassword_url() ) . '" rel="nofollow">' . $thelostpass . '</a></li>';

		if ($links)
			echo '<ul class="recaptchalogin_otherlinks">' . $links . '</ul>';	
	}		
		
	// echo widget closing tag
	echo $after_widget;    
}


// add recaptcha
if ( !function_exists('recaptchalogin_add_recaptcha') ) {
        function recaptchalogin_add_recaptcha( $args ){
                // check if the library has installed.
                if(!function_exists('_recaptcha_qsencode'))
                    require_once 'recaptchalib.php';
                
                if( get_option('recaptchalogin_public_key') != '' && get_option('recaptchalogin_private_key') != '' ){
                        # was there a reCAPTCHA response?
                        $error  = '';
                        $output = '';
                        
                        $output .= '<script type="text/javascript">
 var RecaptchaOptions = {
    theme : "'.get_option('recaptchalogin_color', 'red').'"
 };
 </script>';
                        if (isset($_POST["recaptcha_response_field"]) && $_POST["recaptcha_response_field"] != '') {
                                $resp = recaptcha_check_answer (get_option('recaptchalogin_private_key'),
                                                                $_SERVER["REMOTE_ADDR"],
                                                                $_POST["recaptcha_challenge_field"],
                                                                $_POST["recaptcha_response_field"]);

                                if (!$resp->is_valid) {
                                        # set the error code so that we can display it
                                        $error = $resp->error;
                                }
                        }
                        $output .= '<span>'.$error.'</span>';
                        $output .= recaptcha_get_html(get_option('recaptchalogin_public_key'), $error);
                        return $output;
                }
        }
}
add_filter('login_form_middle', 'recaptchalogin_add_recaptcha', 100, 1);

if ( !function_exists('recaptchalogin_add_recaptcha_admin') ) {
        function recaptchalogin_add_recaptcha_admin( $args ){
                // check if the library has installed.
                if(!function_exists('_recaptcha_qsencode'))
                    require_once 'recaptchalib.php';
                
                if( get_option('recaptchalogin_public_key') != '' && get_option('recaptchalogin_private_key') != '' ){                        
                        # was there a reCAPTCHA response?
                        $error = '';
                        $output = '';
                        
                        $output .= '<script type="text/javascript">
 var RecaptchaOptions = {
    theme : "'.get_option('recaptchalogin_color', 'red').'"
 };
 </script>';
                        if (isset($_POST["recaptcha_response_field"]) && $_POST["recaptcha_response_field"] != '') {
                                $resp = recaptcha_check_answer (get_option('recaptchalogin_private_key'),
                                                                $_SERVER["REMOTE_ADDR"],
                                                                $_POST["recaptcha_challenge_field"],
                                                                $_POST["recaptcha_response_field"]);

                                if (!$resp->is_valid) {
                                        # set the error code so that we can display it
                                        $error = $resp->error;
                                }
                        }
                        $output .= '<span>'.$error.'</span>';
                        $output .= recaptcha_get_html(get_option('recaptchalogin_public_key'), $error);
                        echo $output;
                }
        }
}
add_action('login_form','recaptchalogin_add_recaptcha_admin');

/* Get Current URL */
if ( !function_exists('recaptchalogin_current_url') ) {
	function recaptchalogin_current_url( $url = '' ) {
	
		$pageURL  = force_ssl_admin() ? 'https://' : 'http://';
		$pageURL .= esc_attr( $_SERVER['HTTP_HOST'] );
		$pageURL .= esc_attr( $_SERVER['REQUEST_URI'] );
	
		if ($url != "nologout") {
			if (!strpos($pageURL,'_login=')) {
				$rand_string = md5(uniqid(rand(), true));
				$rand_string = substr($rand_string, 0, 10);
				$pageURL = add_query_arg('_login', $rand_string, $pageURL);
			}	
		}
		
		return strip_tags( $pageURL );
	}
}


function recaptchalogin_login_recaptcha_process(){    
		global $errors;
		$ropt = get_option('recaptcha_options');
                
                // check if the library has installed.
                if(!function_exists('_recaptcha_qsencode'))
                    require_once 'recaptchalib.php';

		if ($_POST == array()) {
			return true;
		}

		if (!isset($_POST['recaptcha_response_field']) || $_POST['recaptcha_response_field'] == '') {
			header('Location: '.$_POST['redirect_to']);
			exit();
		}

		$recaptcha_response = recaptcha_check_answer($ropt['private_key'],
			$_SERVER['REMOTE_ADDR'],
			$_POST['recaptcha_challenge_field'],
			$_POST['recaptcha_response_field']);

		if (!$recaptcha_response->is_valid) {
			setcookie('wp_login_recaptcha_error', $recaptcha_response->error, time() + (3600 * 24 * 7));
                        $redirect_to = (get_option('recaptchalogin_login_redirect'))? get_option('recaptchalogin_login_redirect'):$_POST['redirect_to'];
			header('Location: ' . $redirect_to);
			exit();
		}
		setcookie('wp_login_recaptcha_error', '', time() + (3600 * 24 * 7));
}
add_action('wp_authenticate', 'recaptchalogin_login_recaptcha_process', 1);

?>
