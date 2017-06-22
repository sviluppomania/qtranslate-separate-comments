=== qTranslate(-X) Separate Comments ===
Contributors: nikolov.tmw, paiyakdev
Tags: qTranslate, qTranslate-X, comments, comment languages
Requires at least: 3.3.2
Tested up to: 4.8
Stable tag: 1.2.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automatically separate the user comments by the language they viewed the article in.

== Description ==

This plugin separates the user comments by the language they viewed the article in - this way you avoid duplicate content(which is frowned-upon from search engines) and comments in other languages than the one the current visitor is using(which is generally bad user experience). 

You can manually change the language of each comment(and you will have to set it in the begining).

Bulk-editing of the comments language is also available.

The plugin is using comment-meta to specify the language for each comment, so nothing will break if the plugin is deactivated.

It also should work out-of-the-box for all themes that use the comments_template() function to render their comments. 

The plugin also fixes an issue of qTranslate. The issue consists in the fact that whenever a user posts a comment(while viewing a post in a language different than the default one), he is redirected back to the post but in the default language and not the language he was reading the post before posting the comment.

== Installation ==

1. Download the plugin from [here](http://wordpress.org/extend/plugins/qtranslate-separate-comments/ "qTranslate Separate Comments").
1. Extract all the files. 
1. Upload everything (keeping the directory structure) to the `/wp-content/plugins/` directory.
1. There should be a `/wp-content/plugins/qtranslate-separate-comments` directory now with `qtranslate-separate-comments.php` in it.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Go to the Comments section in WordPress Dashboard and set the appropriate language for all of your comments(you can use the "Bulk Set Language" button after selecting some comments and the right language for them).

== Frequently Asked Questions ==

= I installed the plugin and all my comments are no longer displayed =

You have to associate each comment with a specific language. To do this, go to the Comments section in WordPress Dashboard and set the appropriate language for all of your comments(you can use the "Bulk Set Language" button after selecting some comments and the right language for them).

= The plugin doesn't redirect to the proper language and the language is set incorrectly =

In order to properly identify where the comment is coming from, this plugin adds a hidden input to the Comments form. This is done by hooking a function to the `comment_form` action hook, which is called from the `comment_form()` WordPress function. If you are using a custom comments form instead of calling this function, make sure to add `<?php do_action( 'comment_form', $post->ID ); ?>` . If this still doesn't work, replace that code with the following: `<?php global $post; do_action( 'comment_form', $post->ID ); ?>`.

== Screenshots ==

1. Here you can see the different comments for the different translations of the "Hello World!" post.
2. Here you can see different aspects of the back-end integration.
	1. This is the dropdown with available languages and the "Bulk Set Language" button.
	1. This is the confirmation message that appears after a successfull modification of the language.
	1. This is the notification message when a comment's language has either not been set-up or that language is currently disabled.

== Changelog ==

= 1.2.3 =
Tested with WordPress 4.8 - still works! Also added support for qTranslate-X, as it seems to be an active fork of qTranslate.

= 1.2.2 =
Tested with WordPress 3.9 and everything works fine. Note that the current version of qTranslate(2.5.39) however breaks my test site with a fatal error.

= 1.2.1 =
Changed the tested up to version to 3.5.1. Changed stable tag from trunk to tag number. 

= 1.2 =
Changed the passing of the comment language to a much, much more simple way, that should be more proof to errors.

= 1.1.1 =
Fixed a bug related to proper setting of the language/redirecting back. The code was tested both from a root-level install and a sub-directory install. 

= 1.1 =
* Fixed the `fix_comments_count()` function(a typo was returning the wrong comments count) - thanks @hyOzd
* Added support for qTranslate's "Query Mode"
* Fixed a couple of little bugs
* Updated the FAQ section
* Added a .pot file
* Added Bulgarian translation

= 1.0 =
* Initial release.
* Automatic language setting for new comments.
* Manual setting of a comment's language through the admin.
* Bulk setting of comment's language from the Edit Comments dashboard section(using AJAX).
* Fixed qTranslate issue of not returning the commenter to the correct language.