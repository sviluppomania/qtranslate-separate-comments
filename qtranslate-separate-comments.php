<?php
/*
Plugin Name: qTranslate(-x) Separate Comments
Description: This plugin separates the user comments by the language they viewed the article in - this way you avoid duplicate content and comments in other languages than the one the current visitor is using. You can manually change the language of each comment(and you will have to set it in the begining).
Version: 1.2.3
Author: Nikola Nikolov
Author URI: https://paiyakdev.com/
License: GPLv2 or later

==========================================
Licensing information

Copyright 2017 Nikola Nikolov (email : nikolov.tmw@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


/**
* This is the base class of the plugin - most of our important code is contained within it.
*/
class qTranslate_Separate_Comments {
	
	function __construct() {
		self::add_filters();
		self::add_actions();
	}

	/**
	* Registers any filter hooks that the plugin is using
	* @access private
	* @uses add_filter()
	**/
	private static function add_filters() {
		add_filter( 'comments_array', array( 'qTranslate_Separate_Comments', 'filter_comments_by_lang' ), 10, 2 );
		add_filter( 'manage_edit-comments_columns', array( 'qTranslate_Separate_Comments', 'filter_edit_comments_t_headers' ), 100 );
		add_filter( 'get_comments_number', array( 'qTranslate_Separate_Comments', 'fix_comments_count' ), 100, 2 );
	}

	/**
	* Registers any action hooks that the plugin is using
	* @access private
	* @uses add_action()
	**/
	private static function add_actions() {
		// This hook is fired whenever a new comment is created
		add_action( 'comment_post', array( 'qTranslate_Separate_Comments', 'new_comment' ), 10, 2 );

		// This hooks is usually fired around the submit button of the comments form
		add_action( 'comment_form', array( 'qTranslate_Separate_Comments', 'comment_form_hook' ), 10 );

		// Fired whenever an comment is editted
		add_action( 'edit_comment', array( 'qTranslate_Separate_Comments', 'save_comment_lang' ), 10, 2 );

		// Fired at the footer of the Comments edit screen
		add_action( 'admin_footer-edit-comments.php', array( 'qTranslate_Separate_Comments', 'print_comment_scripts' ), 10 );

		// This is for our custom Admin AJAX action "qtc_set_language"
		add_action( 'wp_ajax_qtc_set_language', array('qTranslate_Separate_Comments', 'handle_ajax_update' ), 10 );

		add_action( 'plugins_loaded', array('qTranslate_Separate_Comments', 'plugin_init' ), 10 );
		add_action( 'manage_comments_custom_column', array('qTranslate_Separate_Comments', 'render_comment_lang_col' ), 10, 2 );
		add_action( 'admin_init', array( 'qTranslate_Separate_Comments', 'admin_init' ), 10 );
	}

	/**
	* Loads the plugin textdomain for any future translations
	* @access public
	**/
	public static function plugin_init() {
		load_plugin_textdomain( 'qTranslate_Separate_Comments', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	* Registers a custom meta box for the Comment Language
	* @access public
	**/
	public static function admin_init() {
		add_meta_box('qtc', __( 'Comment Language', 'qTranslate_Separate_Comments' ), array('qTranslate_Separate_Comments', 'comment_language_metabox'), 'comment', 'normal');
	}

	/**
	* Createс a custom meta box for the Comment Language
	* @access public
	**/
	public static function comment_language_metabox($comment) {
		global $q_config;

		$curr_lang = get_comment_meta($comment->comment_ID, '_comment_language', true);
		?>
		<table class="form-table editcomment comment_xtra">
			<tbody>
				<tr valign="top">
					<td class="first"><?php _e( 'Select the Comment\'s Language:', 'qTranslate_Separate_Comments' ); ?></td>
					<td>
						<select name="qtc_language" id="qTranslate_Separate_Comments_language" class="widefat">
							<?php foreach ($q_config['enabled_languages'] as $lang) : ?>
								<option value="<?php echo $lang; ?>"<?php echo $lang == $curr_lang ? ' selected="selected"' : ''; ?>><?php echo $q_config['language_name'][$lang]; ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</tbody>
		</table>
	<?php
	}

	public static function fix_comments_count($count, $post_id) {
		if ( $count != 0 ) {
			global $q_config;
			$comments_query = new qTC_Comment_Query();
			$comments = $comments_query->query( array('post_id' => $post_id, 'status' => 'approve', 'order' => 'ASC', 'meta_query' => array(array('key' => '_comment_language', 'value' => $q_config['language']))) );

			return count($comments);
		}
	}

	/**
	* Adds a "Language" header for the Edit Comments screen
	* @access public
	**/
	public static function filter_edit_comments_t_headers($columns) {
		if ( ! empty($columns) ) {
			$response = $columns['response'];
			unset($columns['response']);
			$columns['qt_language'] = __('Language', 'qTranslate_Separate_Comments');
			$columns['response'] = $response;
		}

		return $columns;
	}

	/**
	* Renders the language for each comment in the Edit Comments screen
	* @access public
	**/
	public static function render_comment_lang_col($column, $commentID) {
		if ( $column == 'qt_language' ) {
			global $q_config;
			$comm_lang = get_comment_meta($commentID, '_comment_language', true);
			if ( in_array($comm_lang, $q_config['enabled_languages']) ) {
				echo $q_config['language_name'][$comm_lang];
			} else {
				echo '<p class="help">' . sprintf( __( 'Language not set, or inactive(language ID is "%s")', 'qTranslate_Separate_Comments' ), $comm_lang) . '</p>';
			}
		}
	}

	/**
	* Handles the AJAX POST for bulk updating of comments language
	* @access public
	**/
	public static function handle_ajax_update() {
		if ( isset( $_POST['language'] ) && isset( $_POST['ids'] ) && is_array( $_POST['ids'] ) && check_admin_referer( 'bulk-comments' ) ) {
			global $q_config;
			
			$language = $_POST['language'];
			$ids = $_POST['ids'];

			$result = array('success' => false, 'message' => '');

			if ( ! in_array($language, $q_config['enabled_languages']) ) {
				$result['success'] = false;
				$result['message'] = sprintf( __( 'The language with id "%s" is currently not enabled.', 'qTranslate_Separate_Comments' ), $language );
			} else {
				foreach ($ids as $id) {
					update_comment_meta($id, '_comment_language', $language);
				}
				$result['success'] = true;
				$result['message'] = sprintf( __( 'The language of comments with ids "%s" has been successfully changed to "%s".', 'qTranslate_Separate_Comments' ), implode(', ', $ids), $q_config['language_name'][$language] );
			}
			
			echo json_encode($result);
			exit;
		}
	}

	/**
	* Prints the necessary JS for the Edit Comments screen
	* @access public
	**/
	public static function print_comment_scripts() {
		global $q_config;

		$languages = array();
		foreach ($q_config['enabled_languages'] as $lang) {
			$languages[$lang] = $q_config['language_name'][$lang];
		}
		$languages = empty($languages) ? false : $languages; ?>
		<script type="text/javascript">
			var qTC_languages = <?php echo json_encode($languages); ?>;
			(function($){
				function selectedIDs (no_alert) {
					var ids = new Array;

					$('input[name="delete_comments[]"]:checked').each(function(){
						ids.push($(this).val());
					})
					ids = ids.length ? ids : false;

					if ( ! no_alert && ! ids ) {
						alert('<?php echo esc_js( __( "Please Select comment/s first!", "qTranslate_Separate_Comments" ) ); ?>');
					};
					return ids;
				}

				function update_lang (ids, curr_lang) {
					curr_lang = qTC_languages[curr_lang];
					$.each(ids, function(i, id){
						$('#comment-' + id + ' .column-qt_language').text(curr_lang);
						$('input[name="delete_comments[]"][value="' + id + '"]').removeAttr('checked');
					})
				}

				function display_message(message, is_error) {
					var css_class = is_error ? 'error' : 'updated qtc_fadeout';
					$('#comments-form .tablenav.top').after('<div class="' + css_class + '"><p>' + message + '</p></div>');
					if ( ! is_error ) {
						setTimeout(function(){
							$('#comments-form .qtc_fadeout').slideUp(function(){
								$(this).remove();
							})
						}, 5000);
					};
				}

				function set_language () {
					var ids = selectedIDs(),
						curr_lang = $('#qtc_language').val(),
						waiting = $('.qtc_languages_div .waiting');

					if ( ids && curr_lang ) {
						waiting.show();
						$.post(ajaxurl, {
							action: 'qtc_set_language',
							language: curr_lang,
							ids: ids,
							_wpnonce: $('#_wpnonce').val(),
							_wp_http_referer: $('#_wp_http_referer').val()
						}, function(data) {
							if ( ! data.success ) {
								display_message(data.message, true);
								$.each(ids, function(i, id) {
									$('input[name="delete_comments[]"][value="' + id + '"]').removeAttr('checked');
								})
							} else {
								update_lang(ids, curr_lang);
								display_message(data.message);
							};
							
							waiting.hide();
						}, 'json');
					};
				}

				$(document).ready(function(){
					if ( qTC_languages ) {
						$('#comments-form .tablenav.top .tablenav-pages').before('<div class="alignleft actions qtc_languages_div"></div>');
						$('.qtc_languages_div').append('<select id="qtc_language" name="qtc_language"></select> <input type="button" id="qtc_set_language" class="button-secondary action" value="<?php echo esc_js(__("Bulk Set Language", "qTranslate_Separate_Comments")) ?>"> <img class="waiting" style="display:none;" src="<?php echo esc_url( admin_url( "images/wpspin_light.gif" ) ); ?>" alt="" />');
						var select = $('#qtc_language');
						for (lang in qTC_languages) {
							select.append('<option value="' + lang + '">' + qTC_languages[lang] + '</option>');
						};

						$('#qtc_set_language').on('click', function(){
							set_language();

							return false;
						})
					};
				})
			})(jQuery)
		</script>
		<?php 
	}

	/**
	* Saves the comment language(single-comment-editting only)
	* @access public
	**/
	public static function save_comment_lang($commentID) {
		if ( isset($_POST['qtc_language']) && qtrans_isEnabled($_POST['qtc_language']) ) {
			update_comment_meta($commentID, '_comment_language', $_POST['qtc_language']);
		}
	}

	/**
	* Sets the language for new comments and fixes $q_config
	*
	* Besides simply setting the new comment's language, this also fixes the global current language in $q_config which helps to get proper URL to which the visitor is redirected
	*
	* @access public
	**/
	public static function new_comment($commentID) {
		global $q_config;

		if ( isset( $q_config ) ) {
			$comm_lang = isset( $_POST['qtc_comment_lang'] ) && in_array( $_POST['qtc_comment_lang'], $q_config['enabled_languages'] ) ? $_POST['qtc_comment_lang'] : $q_config['default_language'];
			$q_config['language'] = $comm_lang;
			update_comment_meta( $commentID, '_comment_language', $comm_lang );

			add_filter( 'comment_post_redirect', array( 'qTranslate_Separate_Comments', 'fix_comment_post_redirect' ), 10, 2 );
		}
	}

	/**
	* Fixes the "&amp;" in URL's parsed by qTranslate
	* Generally that's a good practice, but not and when you do a PHP redirect
	*
	* @access public
	**/
	public static function fix_comment_post_redirect( $location, $comment ) {
		$location = str_replace( '&amp;', '&', $location );
		if ( preg_match( '~lang=([a-z]{2}).*?lang=([a-z]{2})~', $location ) ) {
			global $q_config;
			$location = preg_replace( '~#comment-\d*~', '', $location );
			
			$location = remove_query_arg( 'lang', $location );
			if ( $q_config['language'] != $q_config['default_language'] || ! $q_config['hide_default_language'] ) {
				$location = add_query_arg( 'lang', $q_config['language'], $location );
			}
			$location .= '#comment-' . $comment->comment_ID;
		}

		return $location;
	}

	/**
	* Renders a hidden input in the comments form
	*
	* This hidden input contains the permalink of the current post(without the hostname) and is used to properly assign the language of the comment as well as the back URL
	*
	* @access public
	**/
	public static function comment_form_hook($post_id) {
		global $q_config;

		echo '<input type="hidden" name="qtc_comment_lang" value="' . esc_attr( $q_config['language'] ) . '" />';
	}

	/**
	* Filters comments for the current language only
	*
	* This function is called whenever comments are fetched for the comments_template() function. This way the right comments(according to the current language) are fetched automatically.
	* 
	* @access public
	**/
	public static function filter_comments_by_lang($comments, $post_id) {
		global $q_config, $wp_query, $withcomments, $post, $wpdb, $id, $comment, $user_login, $user_ID, $user_identity, $overridden_cpage;

		// Store the Meta Query arguments
		$meta_query = array( array( 'key' => '_comment_language', 'value' => $q_config['language'] ) );

		/**
		 * Comment author information fetched from the comment cookies.
		 *
		 * @uses wp_get_current_commenter()
		 */
		$commenter = wp_get_current_commenter();

		/**
		 * The name of the current comment author escaped for use in attributes.
		 */
		$comment_author = $commenter['comment_author']; // Escaped by sanitize_comment_cookies()

		/**
		 * The email address of the current comment author escaped for use in attributes.
		 */
		$comment_author_email = $commenter['comment_author_email'];  // Escaped by sanitize_comment_cookies()

		// WordPress core files use custom SQL for most of it's stuff, we're only using the $comments_query object to get the most simple query
		if ( $user_ID ) {
			// Build the Meta Query SQL
			$mq_sql = get_meta_sql( $meta_query, 'comment', $wpdb->comments, 'comment_ID' );

			$comments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->comments {$mq_sql['join']} WHERE comment_post_ID = %d AND (comment_approved = '1' OR ( user_id = %d AND comment_approved = '0' ) ) {$mq_sql['where']} ORDER BY comment_date_gmt", $post->ID, $user_ID ) );
		} else if ( empty( $comment_author ) ) {
			$comments_query = new qTC_Comment_Query();
			$comments = $comments_query->query( array('post_id' => $post_id, 'status' => 'approve', 'order' => 'ASC', 'meta_query' => $meta_query ) );
		} else {
			// Build the Meta Query SQL
			$mq_sql = get_meta_sql( $meta_query, 'comment', $wpdb->comments, 'comment_ID' );

			$comments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->comments {$mq_sql['join']} WHERE comment_post_ID = %d AND ( comment_approved = '1' OR ( comment_author = %s AND comment_author_email = %s AND comment_approved = '0' ) ) {$mq_sql['where']} ORDER BY comment_date_gmt", $post->ID, wp_specialchars_decode( $comment_author, ENT_QUOTES ), $comment_author_email ) );
		}

		return $comments;
	}
}

/**
 * WordPress Comment Query class + WP_Meta_Query.
 *
 * The default Wordpress Comment Query class with added compatibility for meta queries :)
 *
 * @since 3.1.0
 */
class qTC_Comment_Query extends WP_Comment_Query {
	/**
	 * Metadata query container
	 *
	 * @since 3.2.0
	 * @access public
	 * @var object WP_Meta_Query
	 */
	var $meta_query = false;

	/**
	 * Execute the query
	 *
	 * @since 3.1.0
	 *
	 * @param string|array $query_vars
	 * @return int|array
	 */
	function query( $query_vars ) {
		global $wpdb;

		$defaults = array(
			'author_email' => '',
			'ID' => '',
			'karma' => '',
			'number' => '',
			'offset' => '',
			'orderby' => '',
			'order' => 'DESC',
			'parent' => '',
			'post_ID' => '',
			'post_id' => 0,
			'post_author' => '',
			'post_name' => '',
			'post_parent' => '',
			'post_status' => '',
			'post_type' => '',
			'status' => '',
			'type' => '',
			'user_id' => '',
			'search' => '',
			'count' => false,
			'meta_key' => '',
			'meta_value' => '',
			'meta_query' => '',
		);

		$this->query_vars = wp_parse_args( $query_vars, $defaults );
		// Parse meta query
		$this->meta_query = new WP_Meta_Query();
		$this->meta_query->parse_query_vars( $this->query_vars );

		do_action_ref_array( 'pre_get_comments', array( &$this ) );
		extract( $this->query_vars, EXTR_SKIP );

		// $args can be whatever, only use the args defined in defaults to compute the key
		$key = md5( serialize( compact(array_keys($defaults)) )  );
		$last_changed = wp_cache_get('last_changed', 'comment');
		if ( !$last_changed ) {
			$last_changed = time();
			wp_cache_set('last_changed', $last_changed, 'comment');
		}
		$cache_key = "get_comments:$key:$last_changed";

		if ( $cache = wp_cache_get( $cache_key, 'comment' ) ) {
			return $cache;
		}

		$post_id = absint($post_id);

		if ( 'hold' == $status )
			$approved = "comment_approved = '0'";
		elseif ( 'approve' == $status )
			$approved = "comment_approved = '1'";
		elseif ( 'spam' == $status )
			$approved = "comment_approved = 'spam'";
		elseif ( 'trash' == $status )
			$approved = "comment_approved = 'trash'";
		else
			$approved = "( comment_approved = '0' OR comment_approved = '1' )";

		$order = ( 'ASC' == strtoupper($order) ) ? 'ASC' : 'DESC';

		if ( ! empty( $orderby ) ) {
			$ordersby = is_array($orderby) ? $orderby : preg_split('/[,\s]/', $orderby);
			$allowed_keys = array(
				'comment_agent',
				'comment_approved',
				'comment_author',
				'comment_author_email',
				'comment_author_IP',
				'comment_author_url',
				'comment_content',
				'comment_date',
				'comment_date_gmt',
				'comment_ID',
				'comment_karma',
				'comment_parent',
				'comment_post_ID',
				'comment_type',
				'user_id',
			);
			if ( !empty($this->query_vars['meta_key']) ) {
				$allowed_keys[] = $q['meta_key'];
				$allowed_keys[] = 'meta_value';
				$allowed_keys[] = 'meta_value_num';
			}
			$ordersby = array_intersect( $ordersby, $allowed_keys );
			foreach ($ordersby as $key => $value) {
				if ( $value == $q['meta_key'] || $value == 'meta_value' ) {
					$ordersby[$key] = "$wpdb->commentmeta.meta_value";
				} elseif ( $value == 'meta_value_num' ) {
					$ordersby[$key] = "$wpdb->commentmeta.meta_value+0";
				}
			}
			$orderby = empty( $ordersby ) ? 'comment_date_gmt' : implode(', ', $ordersby);
		} else {
			$orderby = 'comment_date_gmt';
		}

		$number = absint($number);
		$offset = absint($offset);

		if ( !empty($number) ) {
			if ( $offset )
				$limits = 'LIMIT ' . $offset . ',' . $number;
			else
				$limits = 'LIMIT ' . $number;
		} else {
			$limits = '';
		}

		if ( $count )
			$fields = 'COUNT(*)';
		else
			$fields = '*';

		$join = '';
		$where = $approved;

		if ( ! empty($post_id) )
			$where .= $wpdb->prepare( ' AND comment_post_ID = %d', $post_id );
		if ( '' !== $author_email )
			$where .= $wpdb->prepare( ' AND comment_author_email = %s', $author_email );
		if ( '' !== $karma )
			$where .= $wpdb->prepare( ' AND comment_karma = %d', $karma );
		if ( 'comment' == $type ) {
			$where .= " AND comment_type = ''";
		} elseif( 'pings' == $type ) {
			$where .= ' AND comment_type IN ("pingback", "trackback")';
		} elseif ( ! empty( $type ) ) {
			$where .= $wpdb->prepare( ' AND comment_type = %s', $type );
		}
		if ( '' !== $parent )
			$where .= $wpdb->prepare( ' AND comment_parent = %d', $parent );
		if ( '' !== $user_id )
			$where .= $wpdb->prepare( ' AND user_id = %d', $user_id );
		if ( '' !== $search )
			$where .= $this->get_search_sql( $search, array( 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_author_IP', 'comment_content' ) );

		$post_fields = array_filter( compact( array( 'post_author', 'post_name', 'post_parent', 'post_status', 'post_type', ) ) );
		if ( ! empty( $post_fields ) ) {
			$join = "JOIN $wpdb->posts ON $wpdb->posts.ID = $wpdb->comments.comment_post_ID";
			foreach( $post_fields as $field_name => $field_value )
				$where .= $wpdb->prepare( " AND {$wpdb->posts}.{$field_name} = %s", $field_value );
		}

		if ( !empty( $this->meta_query->queries ) ) {
			$clauses = $this->meta_query->get_sql( 'comment', $wpdb->comments, 'comment_ID', $this );
			$join .= $clauses['join'];
			$where .= $clauses['where'];
		}

		$pieces = array( 'fields', 'join', 'where', 'orderby', 'order', 'limits', 'groupby' );
		$clauses = apply_filters_ref_array( 'comments_clauses', array( compact( $pieces ), &$this ) );
		foreach ( $pieces as $piece )
			$$piece = isset( $clauses[ $piece ] ) ? $clauses[ $piece ] : '';

		$query = "SELECT $fields FROM $wpdb->comments $join WHERE $where ORDER BY $orderby $order $limits";
		// $wpdb->show_errors(); // Use for debugging, but genearally the query should be good :)

		if ( $count )
			return $wpdb->get_var( $query );

		$comments = $wpdb->get_results( $query );
		$comments = apply_filters_ref_array( 'the_comments', array( $comments, &$this ) );

		wp_cache_add( $cache_key, $comments, 'comment' );

		return $comments;
	}
}

// Required for the "is_plugin_active()" and "is_plugin_active_for_network()" functions
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
// Only run our plugin if qTranslate OR qTranslate-X is active
if (
	(
		is_plugin_active( 'qtranslate/qtranslate.php') ||
		( is_multisite() && is_plugin_active_for_network( 'qtranslate/qtranslate.php') )
	) || (
		is_plugin_active( 'qtranslate-x/qtranslate.php') ||
		( is_multisite() && is_plugin_active_for_network( 'qtranslate-x/qtranslate.php') )
	) ) {
	global $qTranslate_Separate_Comments;
	$qTranslate_Separate_Comments = new qTranslate_Separate_Comments();
}
