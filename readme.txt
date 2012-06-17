=== qTranslate Separate Comments ===
Contributors: nikolov.tmw
Tags: qTranslate, comments, comment languages
Requires at least: 3.3.2
Tested up to: 3.4
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automatically separate the user comments by the language they viewed the article in.

== Description ==

This plugin separates the user comments by the language they viewed the article in - this way you avoid duplicate content(which is frowned-upon from search engines) and comments in other languages than the one the current visitor is using(which is generally bad user experiecen). 

You can manually change the language of each comment(and you will have to set it in the begining).

Bulk-editing of the comments language is also available.

The plugin is using comment-meta to specify the language for each comment, so nothing will break if the comment is deactivated.

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

== Screenshots ==

1. Here you can see the different comments for the different translations of the "Hello World!" post.
2. Here you can see different aspects of the back-end integration. | (1) This is the dropdown with available languages and the "Bulk Set Language" button. (2) This is the confirmation message that appears after a successfull modification of the language. (3) This is the notification message when a comment's language has either not been set-up or that language is currently disabled.

== Changelog ==

= 1.0 =
* Initial release.
* Automatic language setting for new comments.
* Manual setting of a comment's language through the admin.
* Bulk setting of comment's language from the Edit Comments dashboard section(using AJAX).
* Fixed qTranslate issue of not returning the commenter to the correct language.