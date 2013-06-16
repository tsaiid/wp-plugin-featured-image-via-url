<?php
/*
Plugin Name: Featured Image Via URL
Description: Allows you to set featured image via URL, ....
Version: 0.1
Author: Tsai I-Ta
Author URI: http://blog.tsaiid.idv.tw/
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
class featured_image_via_url {

	//Version
	static $version ='0.1';

	public function __construct() {

		//Register scripts
		add_action('submitpost_box', array($this,'register_scripts'));
		
	}

	/*
	* Settings
	*/



	/*
	* Register the scripts for the PageDown editor
	*/
	function register_scripts() {
		$screen = get_current_screen();
		$post_type = $screen->post_type;
		if ( current_theme_supports( 'post-thumbnails', $post_type ) && post_type_supports( $post_type, 'thumbnail' ) )
			add_meta_box('postimageviaurldiv', __('Featured Image via URL'), 'post_thumbnail_meta_box', null, 'side', 'low');
	}
	
}

$featured_image_via_url = new featured_image_via_url ();
