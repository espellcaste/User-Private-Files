<?php
/**
 * Front-End Functions
 * 
 * Adds the User Files Shortcode
 * 
 * @package User Private Files
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * List User Files Shortcode
 *
 * @since 1.0.0
 *
 * @todo Show message when there is no file for one particular user
 * @todo Improve WP_Query
 * @todo Add security for Globals ($_POST)
 * 
 * @return  Shortcode with custom files for the logged in user 
 */
function upf_list_user_files() {
	if ( ! is_user_logged_in() ) 
		return;

	global $current_user;

    get_currentuserinfo();

	$current_url = esc_url( get_permalink() );

	if ( strpos( $current_url, '?' ) !== false) $current_url .= '&';

	else $current_url .= '?';

	ob_start(); ?>

	<div class="filter clearfix">
		<form action="<?php the_permalink();?>" method="post" autocomplete="off">
			<select name="upf_year">			
				<option value=""><?php _e('Mostrar todos os anos', 'user-private-files');?></option>
				
				<?php
				global $wpdb;

				$years = $wpdb->get_col(
					"SELECT DISTINCT YEAR(post_date) 
					FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta
					WHERE wposts.ID = wpostmeta.post_id 
					AND wposts.post_type = 'userfile' 
					AND wpostmeta.meta_key = 'upf_user' 
					AND wpostmeta.meta_value = '$current_user->user_login'
					ORDER BY post_date DESC");
				
				foreach($years as $year) { ?>
					<option <?php if ( isset( $_POST['upf_year']) && $_POST['upf_year'] == $year) echo 'selected="selected"';?>><?php echo $year; ?></option>
				
				<?php } ?>
			</select>

			<select name="upf_cat">
				<option value=""><?php _e('Mostrar todas categorias', 'user-private-files');?></option>
				<?php
				$cats = get_terms('file_categories');
				foreach($cats as $cat) { ?>
					<option value="<?php echo $cat->slug;?>" <?php if (isset($_POST['upf_cat']) && $_POST['upf_cat'] == $cat->slug) echo 'selected="selected"';?>><?php echo $cat->name;?></option>
				
				<?php } ?>
			</select>
		
			<input type="submit" value="<?php _e('Filter', 'user-private-files'); ?>" />
		</form>
	</div>
	
	<div class="upf_filelist">
		<?php
		$args = array(
			'post_type' 	=> 'userfile',
			'meta_key' 		=> 'upf_user', 
			'meta_value' 	=> $current_user->user_login,
			'orderby' 		=> 'date',
			'order' 		=> 'DESC'
		);

		if (!empty($_POST['upf_year'])) $args['year'] = $_POST['upf_year'];
		if (!empty($_POST['upf_cat'])) $args['file_categories'] = $_POST['upf_cat'];
		
		$the_query = new WP_Query( $args );
		$html = '';
		$current_year = '';

		// The Loop
		if ( $the_query->have_posts() ) :
			while ( $the_query->have_posts() ) : $the_query->the_post(); 
				$year = get_the_date('Y');
				if ( $year != $current_year) {
					echo '<h2>' . $year . '</h2>';
					$current_year = $year;
				} ?>

				<div class="report-wrap clearfix">
					<span class="report-name"><?php the_title();?></span>
					<div class="right">
						<a target="_blank" class="view-print" href="?upf=vw&id=<?php echo get_the_ID();?>">
							<?php _e('View and Print', 'user-private-files'); ?>
						</a>
						<a target="_blank" class="download" href="?upf=dl&id=<?php echo get_the_ID();?>">
							<?php _e('Download', 'user-private-files'); ?>
						</a>
					</div>
				</div>
				<?php
			endwhile; 
		endif;

		// Reset Query
		wp_reset_postdata();

	$html .= ob_get_clean();
	$html .= '</div>';
	return $html;
}
add_shortcode('user-private-files', 'upf_list_user_files');

/**
 * Adds a no follow and a no index for the User File post type
 *
 * @since 1.0.0
 *
 * @uses get_post_type() To fetch the current post type
 * @return string Meta info for robots
 */
function upf_robots() {
	if ( 'userfile' == get_post_type() ) { ?>
		<meta name="robots" content="noindex, nofollow" />
	<?php  }
}
add_action('wp_head', 'upf_robots');

/**
 * Filter the_content and adds custom view and links
 *
 * @since 1.0.0
 *
 * @global $wp The main query
 * 
 * @uses add_filter() For filtering the the_content()
 */
function upf_userfile_cpt_template() {
    global $wp;

    if ( isset( $wp->query_vars['post_type'] ) && $wp->query_vars['post_type'] == 'userfile' )
        
        if ( have_posts() )
            add_filter( 'the_content', 'upf_userfile_content_filter' );
        
        else
            $wp->is_404 = true;
}
add_action( 'template_redirect', 'upf_userfile_cpt_template' );

/**
 * Returns the current user view and print links
 *
 * @since 1.0.0
 * 
 * @param  void $content The WP the_content
 * @return string Returns the view and print links
 */
function upf_userfile_content_filter( $content ) {
    global $wp_query, $current_user;
	
	// get_currentuserinfo();

    // var_dump($current_user);

    (int) $post_id = $wp_query->post->ID;

	$output = __('You are not authorized to access this page.', 'user-private-files');

	if ( is_user_logged_in() )

		// if the file was not assigned to the current user, return message above
		if ( get_post_meta($post_id, 'upf_user', true) == $current_user->user_login )
    		
    		$output = $content;
			$output .= '<p><a target="_blank" class="view-print" href="?upf=vw&id=' . $post_id . '">' . __('View and Print', 'user-private-files') . '</a><br/>
						<a target="_blank" class="download" href="?upf=dl&id=' . $post_id . '">' . __('Download', 'user-private-files') . '</a></p>';

    return $output;
}
