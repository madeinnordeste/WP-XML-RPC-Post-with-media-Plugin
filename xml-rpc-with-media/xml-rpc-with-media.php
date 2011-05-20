<?php
/*
Plugin Name: XML-RPC With Media
Plugin URI: http://beto.euqueroserummacaco.com
Description: Add Support media atach for new post or page
Version: 0.1
Author: Luiz Alberto
Author URI: http://beto.euqueroserummacaco.com
*/

/*
Copyright (c) 2011 Luiz Alberto Silva Ribeiro. All rights reserved.
Released under the GPL license
http://www.opensource.org/licenses/gpl-license.php

Example to use:

	include 'IXR_Library.php';	
	$client->debug = true;	 
	$data["title"] = 'My Post Title'
	$data["content"] = 'My Post Content text';	
	$data["author"] = 1;
	$data["categories"] = array(6,7);
	$data['custom_fields'] = array('first_custom_field' => 'first',
							'second_custom_field' => 'second',
							'custom_date' => date('Y-m-d H:i:s'));
	
	$data["medias"] = array('http://www.domain.com/1.jpg',
							'http://www.domain.com/3.gif',
							'http://www.domain.com/3.png');						
	
	$args = array('username', 'password', $data);
	
	$client = new IXR_Client('http://www.you_wordpress_blog.com/xmlrpc.php');
	
	if (!$client->query('postWithMedia', $args)) {
	    die('Something went wrong - '.$client->getErrorCode().' : '.$client->getErrorMessage());
	}else{
	    echo "Article Posted Successfully";
	}


*/

add_filter('xmlrpc_methods', 'add_xrwm_method' );

function add_xrwm_method( $methods ) {
    $methods['postWithMedia'] = 'xrwm_post_with_media';
    return $methods;
}

function xrwm_download_media($url){
	
	if($url){
		
		$prefix = '/import_'.date('Y-m_d_h_i_s');

		$uploads = wp_upload_dir();				

		$newfilename = $uploads['path'].$prefix.basename($url);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		set_time_limit(300); 
		curl_setopt($ch, CURLOPT_TIMEOUT, 300);
		$outfile = fopen($newfilename, 'wb');
		curl_setopt($ch, CURLOPT_FILE, $outfile);
		curl_exec($ch);
		fclose($outfile);
		curl_close($ch);

		return $newfilename;
		
	}else{
		
		return FALSE;
	}
	
}


function xrwm_post_with_media($args){
		
		//arguments
		$username	= $args[0];
		$password	= $args[1];
		$data 		= $args[2];

		global $wp_xmlrpc_server;

		//check connect
		if(!$user = $wp_xmlrpc_server->login($username, $password)){
			return $wp_xmlrpc_server->error;
		}

		//Extract datas	
		// String title
		$title = $data["title"];
		
		// String content
		$content = $data["content"];
		
		// Int autor id
		$author = $data["author"];
		
		// Array categories ids
		$categories = $data["categories"];	
			
		// String status post: 'publish', 'draft'
		$status = isset($data["status"]) ? $data["status"] : 'publish'; 
		
		// String status post: 'post', 'page'
		$type = isset($data["type"]) ? $data["type"] : 'post';
		
		// Int post parent Id
		$post_parent = isset($data["post_parent"]) ? $data["post_parent"] : 0; 
		
		// Int menu order
		$menu_order = isset($data["menu_order"]) ? $data["menu_order"] : 0; 
				
		// Array custom fields : key => value
		$custom_fields 	= is_array($data["custom_fields"]) ? $data["custom_fields"] : array();
		// Array medias URL
		$medias = is_array($data["medias"]) ? $data["medias"] : array();
		
		
		// Format the new post
		// post_status: publish, draft
		$new_post = array(			
			'post_title' => $title,
			'post_type' => $type,
			'post_parent' => $post_parent,
		 	'post_content' => $content,
			'post_status' => $status,
			'menu_order' => $menu_order,
			'post_author' => $author,
			'post_category' => $categories,
		);

		// Insert new post
		$new_post_id = wp_insert_post($new_post);
		
		//custom fields
		foreach ($custom_fields as $meta_key => $meta_value) {
			add_post_meta($new_post_id, $meta_key, $meta_value);
		}
		
		//images
		foreach ($medias as $media) {
			
			$download_file = xrwm_download_media($media);

			$wp_filetype = wp_check_filetype(basename($download_file), null );
			$attachment = array(
			     'post_mime_type' => $wp_filetype['type'],
			     'post_title' => preg_replace('/\.[^.]+$/', '', basename($download_file)),
			     'post_content' => '',
			     'post_status' => 'inherit'
			  );
			
			$attach_id = wp_insert_attachment( $attachment, $download_file, $new_post_id );
			require_once(ABSPATH . 'wp-admin/includes/image.php');
			$attach_data = wp_generate_attachment_metadata( $attach_id, $download_file );
			wp_update_attachment_metadata( $attach_id, $attach_data );
		}
		
				
		return TRUE;
}