<?php
/**
 * Plugin Name: Featured Image Via URL
 * Plugin URI: http://blog.tsaiid.idv.tw/project/wordpress-plugins/featured-image-via-url/
 * Description: Allows you to set featured image via URL. The image will be fetched back and saved into the Media Library with thumbnails.
 * Version: 0.1
 * Author: Tsai I-Ta
 * Author URI: http://blog.tsaiid.idv.tw/
 * Modified from Auto Post Thumbnail by adityamooley
 */

/*  Copyright 2013 (ittsai@gmail.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

add_action('submitpost_box', 'fivu_thumbnail_meta_box');
add_action('save_post', 'fivu_publish_post');

// This hook will now handle all sort publishing including posts, custom types, scheduled posts, etc.
//add_action('transition_post_status', 'fivu_check_required_transition');


/*
*
*/
function fivu_thumbnail_meta_box() {
	$screen = get_current_screen();
	$post_type = $screen->post_type;
	if ( current_theme_supports( 'post-thumbnails', $post_type ) && post_type_supports( $post_type, 'thumbnail' ) )
		add_meta_box('postimageviaurldiv', __('Featured Image via URL'), 'fivu_thumbnail_meta_box_html', null, 'side', 'low');
}

function fivu_thumbnail_meta_box_html() {
	echo '<label for="featured_image_url">';
 	_e("URL of featured image", 'fivu_textdomain' );
	echo '</label> ';
	echo '<input type="text" id="featured_image_url" name="featured_image_url" value="" size="30" />';
}

/**
 * Function to check whether scheduled post is being published. If so, apt_publish_post should be called.
 *
 * @param $new_status
 * @param $old_status
 * @param $post
 * @return void
 */
function fivu_check_required_transition($new_status='', $old_status='', $post='') {
    global $post_ID; // Using the post id from global reference since it is not available in $post object. Strange!

    if ('publish' == $new_status) {
        fivu_publish_post($post_ID);
    }
}


function fivu_publish_post($post_id) {
//		error_log('<pre>'.print_r($_POST['featured_image_url']).'</pre>');
//		exit(1);
	$imageUrl = $_POST['featured_image_url'];

    // First check whether Post Thumbnail is already set for this post.
    if (get_post_meta($post_id, '_thumbnail_id', true) || get_post_meta($post_id, 'skip_post_thumb', true)) {
        return;
    }

    // Generate thumbnail

    $thumb_id = fivu_generate_post_thumb($imageUrl, $post_id);

    // If we succeed in generating thumg, let's update post meta
    if ($thumb_id) {
        update_post_meta( $post_id, '_thumbnail_id', $thumb_id );
    }
}

function fivu_generate_post_thumb ($imageUrl, $post_id) {
	// Get image from url & check if is an available image

	// Get the file name
	$filename = substr($imageUrl, (strrpos($imageUrl, '/'))+1);

    if (!(($uploads = wp_upload_dir(current_time('mysql')) ) && false === $uploads['error'])) {
        return null;
    }

    // Generate unique file name
    $filename = wp_unique_filename( $uploads['path'], $filename );

    // Move the file to the uploads dir
    $new_file = $uploads['path'] . "/$filename";

    if (!ini_get('allow_url_fopen')) {
        $file_data = curl_get_file_contents($imageUrl);
    } else {
        $file_data = @file_get_contents($imageUrl);
    }

    if (!$file_data) {
        return null;
    }

    file_put_contents($new_file, $file_data);

    // Set correct file permissions
    $stat = stat( dirname( $new_file ));
    $perms = $stat['mode'] & 0000666;
    @ chmod( $new_file, $perms );

    // Get the file type. Must to use it as a post thumbnail.
    $wp_filetype = wp_check_filetype( $filename, $mimes );

    extract( $wp_filetype );

    // No file type! No point to proceed further
    if ( ( !$type || !$ext ) && !current_user_can( 'unfiltered_upload' ) ) {
        return null;
    }

    // Compute the URL
    $url = $uploads['url'] . "/$filename";

    // Construct the attachment array
    $attachment = array(
        'post_mime_type' => $type,
        'guid' => $url,
        'post_parent' => null,
        'post_title' => $imageTitle,
        'post_content' => '',
    );

    $thumb_id = wp_insert_attachment($attachment, $file, $post_id);
    if ( !is_wp_error($thumb_id) ) {
        require_once(ABSPATH . '/wp-admin/includes/image.php');

        // Added fix by misthero as suggested
        wp_update_attachment_metadata( $thumb_id, wp_generate_attachment_metadata( $thumb_id, $new_file ) );
        update_attached_file( $thumb_id, $new_file );

        return $thumb_id;
    }

    return null;

}

/**
 * Function to fetch the contents of URL using curl in absense of allow_url_fopen.
 *
 * Copied from user comment on php.net (http://in.php.net/manual/en/function.file-get-contents.php#82255)
 */
function curl_get_file_contents($URL) {
    $c = curl_init();
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_URL, $URL);
    $contents = curl_exec($c);
    curl_close($c);

    if ($contents) {
        return $contents;
    }

    return FALSE;
}
