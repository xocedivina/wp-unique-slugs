<?php
defined( 'ABSPATH' ) OR exit;
/**
 * @package wp-unique-slugs
 * @version 1.0
 */
/*
Plugin Name: WP Unique Slugs
Plugin URI: http://wordpress.org/plugins/unique-slugs/
Description: This plugin generates unique slug to avoid conflict from different post types.
Author: Xoce Divina
Author URI: http://xocedivina.com/
Version: 1.0
*/


// check if class already exists
if( !class_exists('uniqueSlugs') ):

class uniqueSlugs {	
	function __construct() {
		// actions
		add_action('save_post', array($this, 'unique_slugs'), 10);
	}

	function unique_slugs($post_id) {
		global $wpdb;

		// get the post id if revision
		if ( $parent_id = wp_is_post_revision( $post_id ) ) 
			$post_id = $parent_id;

		// set sanitized title if slug is not present
		if(isset($_POST['post_name']) && isset($_POST['post_title']) && $_POST['post_name'] == '' && $_POST['post_title']) {
			$slug = sanitize_title( $_POST['post_title'] );
		} else {
			$slug = $_POST['post_name'];
		}

		$original_slug = $slug;

		// override slugs only for published items
		if ( ! in_array( $_POST['post_status'], array( 'draft', 'pending', 'auto-draft' ) ) && $_POST['post_type'] != 'attachment' ) {
			
			//check if post name is already existing
			$post_name_check = $this->check_unique($post_id, $slug);
			if($post_name_check == $slug) {
				$suffix = 2;
				while($post_name_check == $slug) {
					$slug = $original_slug . '-' . $suffix;
					$post_name_check = $this->check_unique($post_id, $slug);
					$suffix++;
				}

				// update post name to final slug name
				$wpdb->query($wpdb->prepare("UPDATE  {$wpdb->posts} SET post_name = %s WHERE ID = %d", $slug, $post_id));
			}
		}
	}

	function check_unique($post_id, $slug) {
		global $wpdb;

		// get public post types and set it into string
		$public_post_types = get_post_types(array('public' => true));
		if(in_array($_POST['post_type'], $public_post_types)) {
			$post_types_str = "('" . implode("','", $public_post_types) . "')";
		}

		// add excludes for future use
		$excludes = array();		
		$excludes[] = $post_id;
		$exlude_str = "AND ID NOT IN (" . implode(",", $excludes) .')';
		
		// query post_name check
		$sql = "SELECT post_name FROM {$wpdb->posts} WHERE post_name = %s AND post_type IN $post_types_str $exlude_str AND post_status != 'inherit' LIMIT 0,1" ;
		return $wpdb->get_var( $wpdb->prepare( $sql, $slug) );
	}
}

// initialize
$uniqueSlugs = new uniqueSlugs();

endif; 