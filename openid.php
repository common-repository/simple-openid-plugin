<?php
/*
Plugin Name: WordPress OpenID
Plugin URI: http://www.netsensei.nl/homebrew/simple-openid-plugin/
Description: Enable OpenID on your wordpress blog
Author: Matthias Vandermaesen
Version: 0.1 beta
Author URI: http://www.netsensei.nl

Email: matthias@netsensei.nl

Version history:
- 3 may 2007 (0.1 alpha): initial public testing
- 4 may 2007 (0.1 beta): fixed theme breaking, input weirdness
- 5 may 2007 (0.1 gamma): fixed user/mail/url input checking
  			  fixed faulty validation of OpenID URL
- 8 may 2007 (0.1 delta): rewrote cookie assignement
			  used some more regexp
- 9 may 2007 (0.1): initial release

*/

/*  Copyright 2007  Matthias Vandermaesen  (email : matthias@netsensei.nl)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

define('OPENID_ICON', get_settings('siteurl') . '/wp-content/plugins/openid/openid-small.gif');

/* Required by YADIS */
@session_start();

/***
* This class contains all the logic for OpenID authentication and registers it with WP
*/
class oidBase {
	function init() {
		/* references to the JanRAIN OpenID PHP library (REQUIRED!!) */
		require_once "common.php"; 
		/* attach functions to some WP hooks */
		add_action('init', array(&$this, 'oid_start_authenticate'));
		add_action('init', array(&$this, 'oid_end_authenticate'));
	}
			
	/***
	* several checks to make sure the plugin only launches from the right page(s) and with proper input
	*/
	function oid_active_url() {
		if(!empty($_POST['openid_url']))
			return true;
		else
			return false;
	}
		
	function oid_active_page() {
		if(eregi('wp-comments-post.php$', $_SERVER['PHP_SELF'])) {
			if ($_POST || $_GET)
				return true;
			else
				return false;
		} else {
			return false;
		}
	}
			
	function oid_check_url($url) {
		if (eregi('^http(s)?\://[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(:[a-zA-Z0-9]*)?/?[a-zA-Z0-9\-\~\?\/\\\.]*$', $url))
			return true;
		else
			return false;
	}

	/***
	* Un/register variables: generate cookies that can be stored and passed on through page transitions
	*/
	function register_var($name, $value, $expire = -1) {
		if ($expire == -1)
			$expire = time()+33100;

		$name = 'oid_comment_' . $name . '_' . COOKIEHASH;

		setcookie($name, $value, $expire, COOKIEPATH, $_SERVER['server_name']);
	}

	function unregister_var($name, $value, $expire = -1) {
		if ($expire == -1)
			$expire = time()-33100;

		$name = 'oid_comment_' . $name . '_' . COOKIEHASH;

		setcookie($name, $value, $expire, COOKIEPATH, $_SERVER['server_name']);
	}
		
	/***
	* First part of authentication. 
	*/		
	function oid_start_authenticate() {
		if ($this->oid_active_page()) {
			if($this->oid_active_url()) {
		
				if (!$this->oid_check_url($_POST['openid_url']))
					wp_die( __('Error: please enter a valid OpenID URL string') );
				
				global $consumer;

				$this->register_var('content', $_POST['comment']);
				$this->register_var('postid', $_POST['comment_post_ID']);
				$this->register_var('author', $_POST['author']);
				$this->register_var('email', $_POST['email']);
				$this->register_var('url', $_POST['url']);							
																				
				$auth_request = $consumer->begin($_POST['openid_url']);
					
				if (!$auth_request)
					wp_die( __('Error: could not create $auth_request object. Is the OpenID URL you provided active/valid?') );		
				
				/* 
				* IMPORTANT
				* Simple registration support is required. These fields are optional and only override if there isn't any
				* input in the name/email fields.
				*/
				$auth_request->addExtensionArg('sreg','optional','nickname,email');				
								
				$process_url = get_bloginfo('url') . '/wp-comments-post.php';
				$trust_root = get_bloginfo('url');
				
				$redirect_url = $auth_request->redirectURL($trust_root, $process_url);
					
				wp_redirect($redirect_url);
					
				// catch problems with redirect not always being executed.
				// This is due to some issue with $_POST variables and input weirdness
				wp_die( __('<p>Error: Something went wrong. Redirect to:</p><p>$redirect_url</p><p>wasn\'t executed properly.</p>') );  
			}
		}
	}
		
	/***
	* Second part of authentication
	* it mostly does what wp-comments-post.php should have done
	*/		
	function oid_end_authenticate() {		
		if ($this->oid_active_page()) {
			if ($_GET) {
				global $consumer;
								
				$response = $consumer->complete($_GET);			
						
				if ($response->status == Auth_OpenID_CANCEL)
					wp_die( __('Error: The OpenID verification was cancelled.') );
				else if ($response->status == Auth_OpenID_FAILURE)
   					wp_die( __('Error: OpenID authentication failed: ' . $response->message) );
				else if ($response->status == Auth_OpenID_SUCCESS) {	  						
		    			$sreg = $response->extensionResponse('sreg');
	
	 				$comment = new oidComment($sreg, $response->identity_url);    				    				
	    				$commentdata = $comment->comment_compact();

	    				$comment_id = wp_new_comment( $commentdata );
   				    				
	    				$this->oid_register($comment_id);

	    				$comment = get_comment($comment_id);
    				    				    
	    				$location = (get_permalink($comment->comment_post_ID) . '#comment-' . $comment_id );
					$location = apply_filters('comment_post_redirect', $location, $comment);

					$this->unregister_var('content', $_POST['comment']);
					$this->unregister_var('postid', $_POST['comment_post_ID']);
					$this->unregister_var('author', $_POST['author']);
					$this->unregister_var('email', $_POST['email']);
					$this->unregister_var('url', $_POST['url']);
								
					wp_redirect($location); // here it all ends: return to the original page
       				}
		        }
		}
	}
		
	/***
	* register a succesfull openid login attempt
	*/
	function oid_register($comment_id) {
		global $wpdb;
		$sql = "UPDATE `$wpdb->comments` SET `openid_login` = '1' WHERE `wp_comments`.`comment_ID` = " . $comment_id . " LIMIT 1 ";
		$results = $wpdb->query( $sql );
		return true;
	}
}

/***
* This class is used to instantiate a 'comment' object which reconstructs the initial comment
* Also does some operations to be found in wp-comments-post.php
*/
class oidComment {
	var $author;
	var $email;
	var $url;
	var $content;
	var $post_id;
	
	function oidComment($sreg = array(), $url) {
		
		if (!$this->email = $this->check_email($sreg))
			wp_die( __('Error: couldn\'t find an email adress'));
		
		if (!$this->author = $this->check_author($sreg))
			wp_die( __('Error: couldn\'t find an author'));							
		
		if (!$_COOKIE['oid_comment_url_' . COOKIEHASH])
			$this->url = $url;
		else
			$this->url = trim($_COOKIE['oid_comment_url_' . COOKIEHASH]);		
		
		$this->content = trim($_COOKIE['oid_comment_content_' . COOKIEHASH]);
		$this->post_id = trim($_COOKIE['oid_comment_postid_' . COOKIEHASH]);	
		
		if ('' == $this->content)
			wp_die( __('Error: comment contains no content. Make sure you typed something.') );
					
		$this->comment_status($this->post_id);
	}
	
	/***
	* Several input validity checks
	*/
	function check_email($sreg = array()) {		
		$email = '';
		
		if (!$sreg['email'] && !$_COOKIE['oid_comment_email_' . COOKIEHASH])
			return false;
		elseif ($sreg['email'])
			$email = $sreg['email'];			 
		elseif($_COOKIE['oid_comment_email_' . COOKIEHASH])
			$email = $_COOKIE['oid_comment_email_' . COOKIEHASH];
			
		if (( 6 > strlen($email)) || !is_email($email))
			wp_die( __('Error: please enter a valid email address.') );
		
		return $email;		
	}
	
	function check_author($sreg = array()) {
		if (!$sreg['nickname'] && !$_COOKIE['oid_comment_author_' . COOKIEHASH])
			return false;
		elseif ($sreg['nickname'])
			return $sreg['nickname'];
		elseif($_COOKIE['oid_comment_author_' . COOKIEHASH])
			return $_COOKIE['oid_comment_author_' . COOKIEHASH];
	}
	
	function comment_status($post_id) {
		global $wpdb;
		
		$status = $wpdb->get_row("SELECT post_status, comment_status FROM $wpdb->posts WHERE ID = '$post_id'");
		
		if (empty($status->comment_status)) {
			wp_die( __('Error: post not found in database') );
		} elseif ('closed' == $status->post_status) {
			wp_die( __('Error: post is closed') );
		} elseif ('draft' == $status->post_status) {
			wp_die( __('Error: post is a draft') );
		}
	}
	
	/***
	* Prepare an array to feed wp_new_comment()
	*/
	function comment_compact() {
		$commentdata = array();
		$commentdata['comment_post_ID'] = $this->post_id;
		$commentdata['comment_author'] = $this->author;
		$commentdata['comment_author_email'] = $this->email;
		$commentdata['comment_content'] = $this->content;
		$commentdata['comment_author_url'] = $this->url;
		$commentdata['comment_type'] = '';
		$commentdata['user_ID'] = '';
		
		return $commentdata;
	}
}

/***
* Optional: show the OpenID logo next to comments posted by OpenID users.
* Put this function in your comments template
*/
function oid_show_logo() {
	global $wpdb;
	
	$comment_id = get_comment_id();
	
	$status = $wpdb->get_row("SELECT openid_login FROM $wpdb->comments WHERE comment_id = '" . $comment_id . "' LIMIT 1");
	
	if ($status->openid_login)
		echo '<img src="' . OPENID_ICON . '" alt="openid" />';
	else
		echo '';
}

/***
* add a nice lil'inputbox to the comment form
* Put this function in your comments template
*/
function oid_add_inputbox() {			
	$user = wp_get_current_user();  
	if (!$user->id) { // don't show if already logged in
		printf('<p><input type="text" name="openid_url" id="openid_url" size="22" tabindex="4" />');
		printf('<label for="openid_url"><small><img src="' . OPENID_ICON . '" alt="openid" /></small></label></p>');
	}
}

/***
* add the 'openid_login' field to register comments with openid login 
* Only called when the plugin is de/activated
*/
function oid_install() {
	global $wpdb;
	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
	
	if($wpdb->get_var("SHOW COLUMNS FROM $wpdb->comments LIKE 'openid_login'") != $table_name) {
		$sql = "ALTER TABLE $wpdb->comments ADD `openid_login` TINYINT(1) NOT NULL DEFAULT '0'";	
		$wpdb->query($sql);
	}
}

add_action('activate_openid/openid.php','oid_install');

/***
* initialize plugin logic when someone wants to login
* !!use only on wp-comments-post.php!!
*/
if(eregi('wp-comments-post.php$', $_SERVER['PHP_SELF'])) {
	$base = new oidBase();
	$base->init();	
}

?>
