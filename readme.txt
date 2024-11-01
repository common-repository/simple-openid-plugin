=== Simple OpenID plugin ===
Contributors: matthias vandermaesen
Donate link: http://www.netsensei.nl/homebrew/simple-openid-plugin/
Tags: comments
Requires at least: 2.1
Tested up to: 2.1.3
Stable tag: 0.1

Instead of giving an username, email and website to comment on a post, 
this plugin enables the use of an OpenID account to add the comment.

== Description ==

Simple OpenID plugin is a plugin for Wordpress that enables visitors
to use their OpenID if they want to post a comment instead of a 
registering yet another local Wordpress account or typing their credentials
over and again.

In the OpenID lingo: the plugin acts as a consumer and checks the user’s 
credentials which are stored on the OpenID provider he/she’s registered with.

What is OpenID? A blurb from the website (http://openid.net):

OpenID is an open, decentralized, free framework for user-centric digital identity.

How does it work? Instead registering 10 accounts at 10 different websites, 
OpenID makes it possible to use only 1 account. This means you only have to 
remember one username/password combo.

OpenID is a flexibel protocol. Most of the crypto-authentication stuff is done
behind the curtains. The only thing you have to do is type in your OpenID URI 
(i.e. your website or the URL pointing to your OpenID account), authenticate with 
your OpenID provider and everything else is taken care of.

This plugin relies heavily on the JanRain OpenID library for PHP.

Note: This software is still under heavy development and provided as is. Installation
 and usage at your own discretion!

== Installation ==

Installation is pretty straightforward, but there are several things you need to take care of:

* Put the openid/ folder in your wp-content/plugins/ folder
* Make sure the wp-content/ folder is writable. The plugin creates a tmp/ folder upon
first time activation. Alternatively, you can create this folder by hand and make it writable.
You can use the chmod 766 command.
* In your comments.php template add these two functions: `<?php oid_add_inputbox(); ?>` and `<?php oid_show_logo(); ?>`
* Activate the plugin in the plugin management panel of your Wordpress installation.

== Frequently Asked Questions ==

= Q: Why do I get weird SQL statement errors in my theme when using oid_show_logo()? =

A: Upon first time activation, the plugin should create an extra field called ‘openid_login’ in 
your wp_comments table. If it didn’t, try to create it manually through i.e. phpmyadmin.

= Q: When I try to authenticate, the plugin times out with an error ‘maximum execution time exceeded’ =

A: The crypto stuff can be a bit of a stress on older or weak configurations. Especially if your 
PHP environment uses the bcmath module. Ask your host (or install yourself) if they can compile and 
install the gmp module for PHP. This will enhance performance and get rid of this error.

= Q: the plugin doesn’t work. I get an error refering to some includes that can’t be found. =

A: Are you trying to install the plugin in a Windows environment (i.e. XAMPP)? Check the common.php 
file how to deal with this.

= Q: OpenID comments are thrown in the spambin! =

A: Unfortunately, Akismet is very unforgiving. Being an antyspambot, it treats OpenID comments as 
spam so they don’t show up in your comments. You should make sure to check your akismet spambin once 
a day to make sure no comments are being held back. The good news is that Akismet learns form it’s mistakes. 
Once a person is whitelisted, his/her comments aren’t held back anymore. Unless he/she uses another nickname,
e-mail adress or OpenID. This is a problem that still needs some work! Input on the matter is valued.

