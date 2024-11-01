<?php

global $consumer;

define('OPENID_USER_URL', 'openid_user_url');
define('OPENID_FILESTORE', ABSPATH . '/wp-content/tmp');

/* initialize JanRain OpenID library */
$path = ini_get('include_path');
$oid_path = dirname(__FILE__);

/***
* !! Important !!
* If you are using the plugin in a windows environment (i.e. XAMPP), replace the ; with :
*/
$path = $oid_path . ';' . $path;

ini_set('include_path', $path);

require_once('Auth/OpenID/Consumer.php');
require_once('Auth/OpenID/FileStore.php');

/* Create a file store */
if (!file_exists(OPENID_FILESTORE) && !mkdir(OPENID_FILESTORE)) {
	wp_die( __("Error: could not create store"));	
}

/* instantiate a consumer */
$store = new Auth_OpenID_FileStore(OPENID_FILESTORE);
$consumer = new Auth_OpenID_Consumer($store); 

?>
