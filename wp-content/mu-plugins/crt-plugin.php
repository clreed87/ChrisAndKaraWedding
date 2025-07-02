<?php
/*
Plugin Name: CRT Site Specific Plugin
Description: Site specific code changes for Chris Reed Tech project sites.
Version: 1.0
Author: Chris Reed
*/

/* -------------------------------
   1. Theme & Editor Customization
   ------------------------------- */

// Remove featured images support on all post types and enable 'link' post format
add_action('after_setup_theme', function() {
    remove_theme_support('post-thumbnails');
    add_theme_support('post-formats', array('link'));
});

/* -----------------------------
   2. Content Handling & Cleanup
   ----------------------------- */

// Limit number of post revisions to keep
add_filter('wp_revisions_to_keep', function($num, $post) {
    return 5;
}, 10, 2);

// Change post title to date if no title is provided (for posts only)
add_filter('wp_insert_post_data', function($data) {
    if (empty($data['post_title']) && $data['post_type'] === 'post') {
        $data['post_title'] = current_time('Y-m-d H.i.s');
    }
    return $data;
});

/* -----------------------------------------
   3. Media Uploads: Image Metadata & Titles
   ----------------------------------------- */

// Populate image title, alt-text, caption, and description on upload, using EXIF DateTimeOriginal if available
add_action('add_attachment', function($post_ID) {
    if (wp_attachment_is_image($post_ID)) {
        $file = get_attached_file($post_ID);
        $meta = wp_read_image_metadata($file);

        // Use DateTimeOriginal (created_timestamp) from EXIF if available, fallback to current time
        if (!empty($meta['created_timestamp'])) {
            $image_time = date('Y-m-d H.i.s', $meta['created_timestamp']);
        } else {
            $image_time = current_time('Y-m-d H.i.s');
        }

        $image_title = 'Chris and Kara: ' . $image_time;
        $image_meta = array(
            'ID'           => $post_ID,
            'post_title'   => $image_title,
            'post_excerpt' => $image_title,
            'post_content' => $image_title,
        );
        // Only set alt if not already set
        if (!get_post_meta($post_ID, '_wp_attachment_image_alt', true)) {
            update_post_meta($post_ID, '_wp_attachment_image_alt', $image_title);
        }
        wp_update_post($image_meta);
    }
});

/* -----------------------------------------------------
   4. RSS Feed Enhancements for Link and Sponsored Posts
   ----------------------------------------------------- */

// Change post titles in RSS feed for link and sponsored posts
add_filter('the_title_rss', function($title) {
    if (function_exists('get_field')) {
        $link_url = get_field('link_url');
        $sponsored_post = get_field('sponsored_post');
        if (has_post_format('link') && $sponsored_post && !empty($link_url)) {
            return get_the_title() . ' [Sponsor] →';
        } elseif (has_post_format('link') && !empty($link_url)) {
            return get_the_title() . ' →';
        }
    }
    return $title;
});

// Change permalink to external URL in RSS feed for link posts
add_filter('the_permalink_rss', function($permalink) {
    if (function_exists('get_field')) {
        $link_url = get_field('link_url');
        if (has_post_format('link') && !empty($link_url)) {
            return $link_url;
        }
    }
    return $permalink;
});

// Add link to post permalink and source in RSS feed for link posts
add_filter('the_content_feed', function($content) {
    if (function_exists('get_field')) {
        $link_url = get_field('link_url');
        if (has_post_format('link') && !empty($link_url)) {
            $link_source = get_field('link_source');
            if (!empty($link_source)) {
                $content .= 'Source: <a href="' . esc_url($link_url) . '">' . esc_html($link_source) . '</a><br>';
            }
            $content .= '<a href="' . esc_url(get_permalink()) . '">☍ Permalink</a>';
        }
    }
    return $content;
});

/* -----------------------------------
   5. Markdown: Store & Convert to HTML
   ----------------------------------- */

// Require Parsedown for Markdown support
require_once __DIR__ . '/parsedown.php';

/**
 * Convert Markdown to HTML on save, and store original Markdown in post meta.
 * Applies to 'post' and 'page' post types.
 */
add_filter('wp_insert_post_data', function($data, $postarr) {
    $allowed_types = ['post', 'page'];
    if (!in_array($data['post_type'], $allowed_types)) return $data;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $data;
    if (isset($postarr['action']) && $postarr['action'] === 'inline-save') return $data;
    if (empty($data['post_content'])) return $data;
    // Only convert if not already HTML (basic: has a closing HTML tag)
    if (preg_match('/<\/[a-z][\s\S]*>/', $data['post_content'])) return $data;

    // Save Markdown to post meta after save
    add_action('save_post', 'crt_save_markdown_meta', 10, 1);

    // Convert Markdown to HTML for public content
    $Parsedown = new Parsedown();
    $data['post_content'] = $Parsedown->text($data['post_content']);
    return $data;
}, 9, 2);

/**
 * Save the original Markdown from the editor to post meta.
 */
function crt_save_markdown_meta($post_id) {
    $post = get_post($post_id);
    if (!$post) return;
    $allowed_types = ['post', 'page'];
    if (!in_array($post->post_type, $allowed_types)) return;
    $original_markdown = isset($_POST['content']) ? $_POST['content'] : '';
    if ($original_markdown) {
        update_post_meta($post_id, '_crt_markdown', $original_markdown);
    }
    remove_action('save_post', 'crt_save_markdown_meta', 10); // prevent recursion
}

/**
 * Restore Markdown to the editor for admin editing (if present).
 */
add_filter('the_post', function($post) {
    if (is_admin() && isset($_GET['action']) && $_GET['action'] === 'edit') {
        $markdown = get_post_meta($post->ID, '_crt_markdown', true);
        if (!empty($markdown)) {
            $post->post_content = $markdown;
        }
    }
    return $post;
});

/* End of CRT Site Specific Plugin */