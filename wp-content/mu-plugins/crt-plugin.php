<?php
/*
Plugin Name: CRT Site Specific Plugin
Description: Site specific code changes for Chris Reed Tech project sites.
*/
/* Start Adding Functions Below this Line */

//* Don't support featured images
add_action( 'after_setup_theme', 'crt_remove_featured_images' );
function crt_remove_featured_images() {

	// Remove support for post thumbnails on all post types
	remove_theme_support( 'post-thumbnails' );

}

//* Limit number of post revisions to keep
add_filter( 'wp_revisions_to_keep', 'crt_limit_revisions', 10, 2 );
function crt_limit_revisions( $num, $post ) {

	$num = 5;
	return $num;
	
}

//* Change post title to date if no title is provided
add_filter( 'wp_insert_post_data', 'crt_update_blank_title' );
function crt_update_blank_title( $data ) {

	$title = $data['post_title'];
	$post_type = $data['post_type'];
	
	if ( empty( $title ) && ( $post_type == 'post' ) ) {

		$timezone = get_option('timezone_string');
		date_default_timezone_set( $timezone );
		$title = date( 'Y-m-d H.i.s' );
		$data['post_title'] = $title;

	}

	return $data;

}

// Populate image title, alt-text, caption, and description on upload
add_action( 'add_attachment', 'crt_set_image_meta' );
function crt_set_image_meta( $post_ID ) {

	// Check if uploaded file is an image, else do nothing
	if ( wp_attachment_is_image( $post_ID ) ) {

		// Set image meta to 'Chris and Kara: Date'
		$timezone = get_option('timezone_string');
		date_default_timezone_set( $timezone );
		$image_title = 'Chris and Kara: '.date( 'Y-m-d H.i.s' );
		$image_meta = array(

			'ID'			=> $post_ID,
			'post_title'	=> $image_title,
			'post_excerpt'	=> $image_title,
			'post_content'	=> $image_title,

		);

		// Set the image alt-text
		update_post_meta( $post_ID, '_wp_attachment_image_alt', $image_title );

		// Set the image meta
		wp_update_post( $image_meta );

	}

}

//* Add support for link post format
add_theme_support( 'post-formats', array(

		'link',
	
	) );

//* Change post titles in RSS feed for link and sponsored posts
add_filter( 'the_title_rss', 'crt_change_feed_post_title' );
function crt_change_feed_post_title( $title ) {
	
	$link_url = get_field( 'link_url' );
	$sponsored_post = get_field( 'sponsored_post' );
	
	if ( has_post_format( 'link' )  && ( $sponsored_post == true ) && !empty( $link_url ) ) {
		
		$title = get_the_title().' [Sponsor] →';
	
	}
	
	else if ( has_post_format( 'link' )  && !empty( $link_url ) ) {
		
		$title = get_the_title().' →';
	
	}
	
	return $title;

}

//* Change permalink to external URL in RSS feed for link posts
add_filter( 'the_permalink_rss', 'crt_change_feed_permalink' );
function crt_change_feed_permalink( $permalink ) {
	
	$link_url = get_field( 'link_url' );
	
	if ( has_post_format( 'link' )  && !empty( $link_url ) ) {
		
		$permalink = $link_url;
	
	}
	
	return $permalink;

}

//* Add link to post permalink and source in RSS feed for link posts
add_filter( 'the_content_feed', 'crt_feed_permalink' );
function crt_feed_permalink( $content ) {
	
	$link_url = get_field( 'link_url' );
	
	if ( has_post_format( 'link' )  && !empty( $link_url ) ) {
		
		$link_source = get_field( 'link_source' );
		
		if ( !empty( $link_source ) ) {
			
			$content .= 'Source: <a href="'.$link_url.'">'.$link_source.'</a><br>';
		
		}
		
		$content .= '<a href="'.get_permalink().'">☍ Permalink</a>';
	
	}
	
	return $content;
}

//* Convert markdown to HTML and restore markdown on post open
// Only run in the admin and frontend (skip if called by CLI, etc.)
if (!defined('ABSPATH')) exit;

// 1. Include Parsedown
require_once __DIR__ . '/parsedown.php';

// 2. Convert Markdown to HTML on save, and store original Markdown in meta
add_filter('wp_insert_post_data', function($data, $postarr) {
    // Post types to use (add or remove as needed)
    $allowed_types = ['post', 'page', 'portfolio'];
    if (!in_array($data['post_type'], $allowed_types)) return $data;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $data;
    if (isset($postarr['action']) && $postarr['action'] === 'inline-save') return $data;
    if (empty($data['post_content'])) return $data;

    // Only convert if not already HTML (basic: has a closing HTML tag)
    if (preg_match('/<\/[a-z][\s\S]*>/', $data['post_content'])) return $data;

    // Save the markdown for later on save_post (using a closure for $data scope)
    add_action('save_post', function($post_id) use ($data) {
        // Save the original markdown to meta
        update_post_meta($post_id, '_crt_markdown', $data['post_content']);
    });

    // Convert Markdown to HTML
    $Parsedown = new Parsedown();
    // $Parsedown->setSafeMode(true); // Optional for added security
    $data['post_content'] = $Parsedown->text($data['post_content']);

    return $data;
}, 9, 2);

// 3. When opening a post in the editor, replace content with stored Markdown (if any)
add_filter('the_post', function($post) {
    // Only on admin post edit screens (not frontend, not quick/bulk edit)
    if (is_admin() && isset($_GET['action']) && $_GET['action'] === 'edit') {
        $markdown = get_post_meta($post->ID, '_crt_markdown', true);
        if (!empty($markdown)) {
            $post->post_content = $markdown;
        }
    }
    return $post;
});

/* Stop Adding Functions Below this Line */
?>