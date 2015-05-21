<?php
/**
 * User Private Files Loader
 *
 * User Private Files is a fork of the Haibui (http://haibui.com) plugin
 * 
 * @package User Private Files
 */

/**
 * Plugin Name: User Private Files
 * Plugin URI:  https://github.com/espellcaste/User-Private-Files
 * Description: Plugin to manage private files for users. You can upload files for your users to access, files are only viewable/downloadable for the designated users.
 * Author:      Renato Alves
 * Author URI:  http://ralv.es
 * Version:     1.0.0
 * Text Domain: user-private-files
 * Domain Path: /languages
 * License:     The same as WordPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists('User_Private_Files') ) :
	/**
	* User Private Files Class
	*
	* The main Class
	*
	* @since 1.0.0
	*/
	class User_Private_Files {

		/**
		 * Main instance
		 *
		 * @since 1.0.0
		 * 
		 * @return instance
		 */
		private static function instance() {

			// Store the instance locally to avoid private static replication
			static $instance = null;

			// Only run these methods if they haven't been run previously
			if ( null === $instance ) {
				$instance = new User_Private_Files;
				$instance->setup_globals();
				$instance->includes();
				$instance->setup_actions();
			}

			// Always return the instance
			return $instance;
		}

		/**
		 * A dummy constructor to prevent User_Private_Files from being loaded more than once.
		 *
		 * @since 1.0.0
		 * 
		 * @see User_Private_Files::instance()
		 */
		private function __construct() { /* Do nothing here */ }

		/**
		 * Set some smart defaults to class variables. Allow some of them to be
		 * filtered to allow for early overriding.
		 *
		 * @since 1.0.0
		 * @access private
		 *
		 * @uses plugin_dir_path() Taking the plugin directory path
		 * @uses apply_filters() In case you want to change it
		 */
		private function setup_globals() {
			$this->file       = __FILE__;
			$this->plugin_dir = plugin_dir_path( $this->file );
		}

		/**
		 * Includes needed files
		 *
		 * @since 1.0.0
		 * 
		 * @return array
		 */
		private function includes() {
			require( $this->plugin_dir . 'inc/front-end.php' );
		}

		/**
		 * Adds the Actions and Filters for the UPF_Admin_Class
		 * 
		 * @since 1.0.0
		 *
		 * @return array
		 */
		private function setup_actions() {
			add_action('admin_menu', 		array( $this, 'upf_menu' ) );
			add_action( 'init', 			array( $this, 'upf_register_custom_post_type' ) );
			add_action( 'init', 			array( $this, 'upf_register_taxonomy_file_categories' ) );
			add_action( 'manage_userfile_posts_custom_column', array( $this, 'upf_user_column_display', 10, 2 ) );

			add_filter( 'manage_edit-userfile_columns', array( $this, 'upf_user_column_register' ) );
			add_filter( 'manage_edit-userfile_sortable_columns', array( $this, 'upf_user_column_register_sortable' ) );
			add_filter( 'request', array( $this, 'upf_user_column_orderby' ) );
			add_filter('get_sample_permalink_html', array( $this, 'upf_hide_sample_permalink', null, 4 ) );
			add_filter( 'upload_dir', array( $this, 'upf_custom_upload_dir' ) );

			add_action( 'post_edit_form_tag' , array( $this, 'upf_post_edit_form_tag' ) );
			add_action('admin_menu', array( $this, 'upf_meta_box') );
			add_action('save_post', array( $this, 'upf_save_post') );
			add_action('init', array( $this, 'upf_get_download') );
		}

		/**
		 * Creates the User File Page Menu
		 * 
		 * @since 1.0.0
		 *
		 * @uses add_submenu_page() For page creation
		 */
		public function upf_menu() {
			add_submenu_page( 
				'edit.php?post_type=user-private-files', 
				__('User Private Files', 'user-private-files'),
				__('Settings', 'user-private-files'), 
				'manage_options', 
				'upf_options', 
				'upf_options_page'
			);
		}

		/**
		 * Adds the Admin Options
		 * 
		 * @since 1.0.0
		 *
		 * @todo Add nounce
		 * @todo Add the proper url for the form POST action
		 */
		public function upf_options_page() {
			if ( ! current_user_can('manage_options') )
				wp_die( __('You do not have sufficient permissions to access this page.', 'user-private-files') );

			if ( ! empty( $_POST['update'] ) ) {

				if ( $_POST['upf_email_subject'] ) { 
					update_option('upf_email_subject', $_POST['upf_email_subject'] );
				}
				
				if ( $_POST['upf_email_message'] ) { 
					update_option('upf_email_message', esc_attr( $_POST['upf_email_message'] ) );
				} ?>

				<div class="updated settings-error" id="setting-error-settings_updated">
					<p><strong><?php _e('Settings Saved', 'user-private-files'); ?>.</strong></p>
				</div>
			
			<?php }

			$upf_email_subject = get_option('upf_email_subject');
			$upf_email_message = get_option('upf_email_message');
			?>

			<div class="wrap">
				<h2><?php _e('User Private Files Settings', 'user-private-files');?></h2>
				<form method="POST" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
					<h3><?php _e('Notification', 'user-private-files');?></h3>
					<table class="form-table">
						<tbody>
							<tr valign="top">
								<th scope="row">
									<label for="upf_email_subject"><?php _e('Email Subject:', 'user-private-files');?></label>
								</th>

								<td>
									<input id="upf_email_subject" class="regular-text" type="text" name="upf_email_subject" value="<?php echo esc_attr( $upf_email_subject ); ?>">
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><label for="upf_email_subject"><?php _e('Email Message:', 'user-private-files');?></label></th>
								<td>
									<textarea class="regular-text" name="upf_email_message" rows="5" cols="50"><?php echo esc_html( $upf_email_message ); ?></textarea>
									<p class="description"><?php _e('Available Variables: ', 'user-private-files');?> <br/><strong>%blogname%, %siteurl%, %user_login%, %filename%, %download_url%, %category%</strong></p>
								</td>
							</tr>
						</tbody>
					</table>
					<input type="hidden" name="update" value="update">
					<p class="submit"><input id="submit" class="button-primary" type="submit" value="<?php _e('Save Changes', 'user-private-files');?>" name="submit"></p>
				</form>
			</div>

		<?php }

		/**
		 * Register the User Private File Post Type
		 *
		 * @since 1.0.0
		 * 
		 * @return void
		 */
		public function upf_register_custom_post_type() {

			$labels = array( 
		        'name' 					=> _x( 'User Private Files', 'user-private-files' ),
		        'singular_name' 		=> _x( 'User Private File', 'user-private-files' ),
		        'add_new' 				=> _x( 'Add New', 'user-private-files' ),
		        'add_new_item' 			=> __( 'Add New User File', 'user-private-files' ),
		        'edit_item' 			=> __( 'Edit User File', 'user-private-files' ),
		        'new_item' 				=> __( 'New User File', 'user-private-files' ),
		        'view_item' 			=> __( 'View User Private File', 'user-private-files' ),
		        'search_items' 			=> __( 'Search User Private Files', 'user-private-files' ),
		        'not_found' 			=> __( 'No user files found', 'user-private-files' ),
		        'not_found_in_trash' 	=> __( 'No user files found in Trash', 'user-private-files' ),
		        'parent_item_colon' 	=> __( 'Parent User File:', 'user-private-files' ),
		        'menu_name' 			=> __( 'User Private Files', 'user-private-files' ),
		    );

		    $args = array( 
		        'labels' 				=> $labels,
		        'hierarchical' 			=> false,
		        'supports' 				=> array( 'title' ),
		        'taxonomies' 			=> array( 'file_categories' ),
		        'public' 				=> true,
		        'show_ui' 				=> true,
		        'show_in_menu' 			=> true,
		        'show_in_nav_menus' 	=> false,
		        'publicly_queryable' 	=> true,
		        'exclude_from_search' 	=> true,
		        'has_archive' 			=> false,
		        'query_var' 			=> true,
		        'menu_position' 		=> 10,
				'menu_icon' 			=> 'dashicons-media-document',
		        'can_export' 			=> false,
		        'rewrite' 				=> false
		    );

		    register_post_type( 'user-private-files', $args );
		}

		/**
		 * Registers the File Category Taxonomy to the User Private Files Post Type
		 * 
		 * @return void
		 * @since 1.0.0
		 */
		public function upf_register_taxonomy_file_categories() {

		    $labels = array( 
		        'name' 							=> _x( 'Categories', 'file_categories' ),
		        'singular_name' 				=> _x( 'Category', 'file_categories' ),
		        'search_items' 					=> __( 'Search Categories', 'file_categories' ),
		        'popular_items' 				=> __( 'Popular Categories', 'file_categories' ),
		        'all_items' 					=> __( 'All Categories', 'file_categories' ),
		        'parent_item' 					=> __( 'Parent Category', 'file_categories' ),
		        'parent_item_colon' 			=> __( 'Parent Category:', 'file_categories' ),
		        'edit_item' 					=> __( 'Edit Category', 'file_categories' ),
		        'update_item' 					=> __( 'Update Category', 'file_categories' ),
		        'add_new_item' 					=> __( 'Add New Category', 'file_categories' ),
		        'new_item_name' 				=> __( 'New Category', 'file_categories' ),
		        'separate_items_with_commas' 	=> __( 'Separate categories with commas', 'file_categories' ),
		        'add_or_remove_items' 			=> __( 'Add or remove categories', 'file_categories' ),
		        'choose_from_most_used' 		=> __( 'Choose from the most used categories', 'file_categories' ),
		        'menu_name' 					=> __( 'Categories', 'file_categories' ),
		    );

		    $args = array( 
		        'labels' 			=> $labels,
		        'public' 			=> true,
		        'show_in_nav_menus' => false,
		        'show_ui' 			=> true,
		        'show_tagcloud' 	=> false,
		        'hierarchical' 		=> true,
		        'rewrite' 			=> false,
		        'query_var' 		=> true
		    );

		    register_taxonomy( 'file_categories', array('user-private-files'), $args );
		}

		/**
		 * Register the column
		 * 
		 * @param  array $columns Columns Object
		 * @return array $columns Returns the Columns with the new Object
		 * @since 1.0.0
		 */
		public function upf_user_column_register( $columns ) {
			$columns['user'] = __( 'User', 'user-private-files' );
			return $columns;
		}

		/**
		 * Display the column content
		 * 
		 * @since 1.0.0
		 */
		public function upf_user_column_display( $column_name, $post_id ) {
			if ( 'user' != $column_name )
				return;
		 
			$username = get_post_meta( $post_id, 'upf_user', true );

			return $username; // echo 
		}

		/**
		 * Register the column as sortable
		 * 
		 * @since 1.0.0
		 */
		public function upf_user_column_register_sortable( $columns ) {
			if ( $columns['user'] != 'user' )
				$columns['user'] = 'user';
			
			return $columns;
		}

		/**
		 * 
		 * 
		 * @since 1.0.0
		 */
		public function upf_user_column_orderby( $vars ) {
			if ( isset( $vars['orderby'] ) && 'user' == $vars['orderby'] ) {
				$vars = array_merge( 
					$vars, array(
						'meta_key' 	=> 'upf_user',
						'orderby' 	=> 'meta_value'
					) 
				);
			}
		 
			return $vars;
		}

		/**
		 * [upf_hide_sample_permalink description]
		 * 
		 * @param  [type] $return    [description]
		 * @param  [type] $id        [description]
		 * @param  [type] $new_title [description]
		 * @param  [type] $new_slug  [description]
		 * @return [type]            [description]
		 * @since 1.0.0
		 */
		private function upf_hide_sample_permalink( $return, $id, $new_title, $new_slug ) {
			global $post;
			$post = get_post( $id );
			
			if ( ! $post )
	    	return '';

			if ( $post->post_type == 'user-private-files') {
				$return = '';
			}

			return $return;
		}

		/**
		 * [upf_get_user_dir description]
		 * 
		 * @param  [type] $user_id [description]
		 * @return [type]          [description]
		 * @since 1.0.0
		 */
		private function upf_get_user_dir( $user_id ) {
			
			if ( empty( $user_id ) ) 
				return false;

			$dir = get_user_meta($user_id, 'upf_dir', true);

			if ( empty($dir) ) {
				$dir = uniqid($user_id.'_');
				add_user_meta( $user_id, 'upf_dir', $dir );
			}

			return $dir;
		}

		private function upf_post_edit_form_tag() {
			global $post;

		    // if invalid $post object or post type is not 'user-private-files', return
		    if(! $post || get_post_type($post->ID) != 'user-private-files') return;
		       	
			echo 'enctype="multipart/form-data" autocomplete="off"';
		}
		
		public function upf_meta_box() {
			add_meta_box('user-private-files', 
				__('User File', 'user-private-files'), 
				'upf_meta_fields', 
				'user-private-files', 
				'normal', 
				'high'
			);
		}

		private function upf_meta_fields() { 
			global $post;

		    wp_nonce_field(plugin_basename(__FILE__), 'wp_upf_nonce');

			$upf_file = get_post_meta($post->ID, 'upf_file', true);
			if (!empty($upf_file)) { ?>
				<p><?php _e('Current file:', 'user-private-files');?> <a href="<?php echo $upf_file['url'];?>" target="_blank"><?php echo basename($upf_file['file']);?></a></p>
				<?php }
			?>
			<p class="label"><label for="upf_file"><?php _e('Upload a PDF file here', 'user-private-files');?></label></p>	
			<p><input type="file" name="upf_file" id="upf_file" /></p>
			<p class="label"><label for="upf_user"><?php _e('Select a user', 'user-private-files');?></label></p>	
			<select name="upf_user" id="upf_user">
				<?php
				$users = get_users();
				$upf_user = get_post_meta($post->ID, 'upf_user', true);
				foreach ($users as $user) { ?>
					<option value="<?php echo $user->ID;?>" <?php if ($upf_user == $user->user_login) echo 'selected="selected"';?>><?php echo $user->user_login;?></option>
					<?php
				}
				?>
			</select>
			<p class="label"><input type="checkbox" name="upf_notify" value="1"> <label for="upf_notify"><?php _e('Notify User', 'user-private-files');?></label></p>	
			<?php 
		}

		private function upf_save_post($post_id, $post = null) {
			global $post;

			/* --- security verification --- */  
			// if(!wp_verify_nonce($_POST['wp_upf_nonce'], plugin_basename(__FILE__)))
				// return $post_id;  

			if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
				return $post_id;  

		    // if invalid $post object or post type is not 'user-private-files', return
		    if(!$post || get_post_type($post->ID) != 'user-private-files') return;

			$user_info = get_userdata($_POST['upf_user']);
			add_post_meta($post_id, 'upf_user', $user_info->user_login);
			update_post_meta($post_id, 'upf_user', $user_info->user_login);

			// Make sure the file array isn't empty
			if(!empty($_FILES['upf_file']['name'])) {
				// Setup the array of supported file types. In this case, it's just PDF.
				$supported_types = array('application/pdf');

				// Get the file type of the upload
				$arr_file_type = wp_check_filetype(basename($_FILES['upf_file']['name']));

				$uploaded_type = $arr_file_type['type'];
				// Check if the type is supported. If not, throw an error.
				if(in_array($uploaded_type, $supported_types)) {
					$upf_file = get_post_meta($post_id, 'upf_file', true);
					if ($upf_file) {
						$upf_file_path = WP_CONTENT_DIR.'/userfiles/'.$upf_file['file'];
						if (file_exists($upf_file_path)) unlink($upf_file_path);
					}

					// Use the WordPress API to upload the file
					$upload = wp_handle_upload( $_FILES['upf_file'], array( 'test_form' => false ) );

					if(isset($upload['error']) && $upload['error'] != 0) {
						wp_die(__('There was an error uploading your file. The error is: ' . $upload['error'], 'user-private-files'));
					} else {
						// Update custom field
						$upload['file'] = substr($upload['file'],stripos($upload['file'],'wp-content/userfiles/')+21);
						add_post_meta($post_id, 'upf_file', $upload);
						update_post_meta($post_id, 'upf_file', $upload);
					} // end if/else
				} else {
					wp_die(__("The file type that you've uploaded is not a PDF.", 'user-private-files'));
				} // end if/else
			} // end if


			if ($_POST['upf_notify'] == '1') {
				$upf_file = get_post_meta($post_id, 'upf_file', true);

				$email_subject = get_option('upf_email_subject');
				$email_msg = get_option('upf_email_message');

				$email_msg = str_replace('%blogname%', get_bloginfo('name'), $email_msg);
				$email_msg = str_replace('%siteurl%', get_bloginfo('url'), $email_msg);
				$email_msg = str_replace('%user_login%', $user_info->user_login, $email_msg);
				$email_msg = str_replace('%filename%', basename($upf_file['file']), $email_msg);
				$email_msg = str_replace('%download_url%', get_bloginfo('url').'/?upf=dl&id='.$post_id, $email_msg);

				$cats = wp_get_post_terms($post_id, 'file_categories', array("fields" => "names"));
				$email_msg = str_replace('%category%', implode(", ", $cats), $email_msg); 

				$headers[] ='From: "'.htmlspecialchars_decode(get_bloginfo('name'), ENT_QUOTES).'" <'.get_option('admin_email').'>';
					
				wp_mail($user_info->user_email, $email_subject, $email_msg, $headers);
			}
		}

		private function upf_custom_upload_dir( $default_dir ) {
			if ( ! isset( $_POST['post_ID'] ) || $_POST['post_ID'] < 0 )
				return $default_dir;

			if ( ! isset( $_POST['upf_user'] ) )
				return $default_dir;

			if ( $_POST['post_type'] != 'user-private-files' )
				return $default_dir;

			$dir = WP_CONTENT_DIR . '/userfiles';
			$url = WP_CONTENT_URL . '/userfiles';

			$bdir = $dir;
			$burl = $url;

			$subdir = '/' . upf_get_user_dir( $_POST['upf_user'] );
			
			$dir .= $subdir;
			$url .= $subdir;

			$custom_dir = array( 
				'path'    => $dir,
				'url'     => $url, 
				'subdir'  => $subdir, 
				'basedir' => $bdir, 
				'baseurl' => $burl,
				'error'   => false, 
			);

			return $custom_dir;
		}

		public function upf_get_download() {
			if (isset($_GET['upf']) && isset($_GET['id'])) {
				if (is_user_logged_in()) {
					global $current_user;
					get_currentuserinfo();

					// if the file was not assigned to the current user, return 
					if (get_post_meta($_GET['id'], 'upf_user', true) != $current_user->user_login) return;

					$upf_file = get_post_meta($_GET['id'], 'upf_file', true);
					$upf_file_path = WP_CONTENT_DIR.'/userfiles/'.$upf_file['file'];
					$upf_file_name = substr($upf_file['file'], stripos($upf_file['file'], '/')+1);
					set_time_limit(0);

					$action = $_GET['upf']=='vw'?'view':'download';
					output_file($upf_file_path, $upf_file_name, $upf_file['type'], $action);
				}
				else {
					wp_redirect(wp_login_url($_SERVER['REQUEST_URI']));
					exit;
				}
			}
		}

		private function output_file($file, $name, $mime_type='', $action = 'download') {
			if(!is_readable($file)) {
				die('File not found or inaccessible!<br />'.$file.'<br /> '.$name);
				return;
			}
			$size = filesize($file);
			$name = rawurldecode($name);

			$known_mime_types=array(
				"pdf" => "application/pdf",
				"txt" => "text/plain",
				"html" => "text/html",
				"htm" => "text/html",
				"exe" => "application/octet-stream",
				"zip" => "application/zip",
				"doc" => "application/msword",
				"xls" => "application/vnd.ms-excel",
				"ppt" => "application/vnd.ms-powerpoint",
				"gif" => "image/gif",
				"png" => "image/png",
				"jpeg"=> "image/jpg",
				"jpg" =>  "image/jpg",
				"php" => "text/plain"
			);

			if($mime_type==''){
				$file_extension = strtolower(substr(strrchr($file,"."),1));
				if(array_key_exists($file_extension, $known_mime_types)){
					$mime_type=$known_mime_types[$file_extension];
				} else {
					$mime_type="application/force-download";
				};
			};

			@ob_end_clean(); //turn off output buffering to decrease cpu usage

			// required for IE, otherwise Content-Disposition may be ignored
			if(ini_get('zlib.output_compression'))
				ini_set('zlib.output_compression', 'Off');

			header('Content-Type: ' . $mime_type);
			if ($action == 'download') header('Content-Disposition: attachment; filename="'.$name.'"');
			else header('Content-Disposition: inline; filename="'.$name.'"');
			header("Content-Transfer-Encoding: binary");
			header('Accept-Ranges: bytes');

			/* The three lines below basically make the	download non-cacheable */
			header("Cache-control: private");
			header('Pragma: private');
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

			// multipart-download and download resuming support
			if(isset($_SERVER['HTTP_RANGE']))
			{
				list($a, $range) = explode("=",$_SERVER['HTTP_RANGE'],2);
				list($range) = explode(",",$range,2);
				list($range, $range_end) = explode("-", $range);
				$range=intval($range);

				if(!$range_end) {
					$range_end=$size-1;
				} else {
					$range_end=intval($range_end);
				}

				$new_length = $range_end-$range+1;
				header("HTTP/1.1 206 Partial Content");
				header("Content-Length: $new_length");
				header("Content-Range: bytes $range-$range_end/$size");
			} else {
				$new_length=$size;
				header("Content-Length: ".$size);
			}

			/* output the file itself */
			$chunksize = 1*(1024*1024); //you may want to change this
			$bytes_send = 0;
			if ($file = fopen($file, 'r'))
			{
				if(isset($_SERVER['HTTP_RANGE']))
					fseek($file, $range);

				while(!feof($file) && (!connection_aborted()) && ($bytes_send<$new_length)) {
					$buffer = fread($file, $chunksize);
					print($buffer); //echo($buffer); // is also possible
					flush();
					$bytes_send += strlen($buffer);
				}
				fclose($file);
			} 
			else die('Error - can not open file.');

			die();
		}
	}
endif;

/**
 * The main function responsible for returning the one true User_Private_Files Instance to functions everywhere.
 *
 * @return User_Private_Files The one true User_Private_Files Instance.
 */
function User_Private_Files() {
	return User_Private_Files::instance();
}
add_action( 'plugins_loaded', 'User_Private_Files');

// That's it! =)
