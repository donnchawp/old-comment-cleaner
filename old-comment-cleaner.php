<?php
/*
Plugin Name: Old Comment Cleaner
Plugin URI: https://odd.blog/old-comment-cleaner/
Description: Deletes old comment data based on user-defined settings.
Version: 1.2.0
Author: Donncha O Caoimh
Text Domain: old-comment-cleaner
Domain Path: /languages
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.txt
*/

// Prevent direct access to the plugin file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Old_Comment_Cleaner_Plugin {

	const DAYS_OLD_DEFAULT = 730;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'old_comment_cleaner_delete_old_comments', array( $this, 'delete_old_comments' ) );
		add_action( 'old_comment_cleaner_delete_old_comments_now', array( $this, 'delete_old_comments' ) );
		add_action( 'admin_post_old_comment_cleaner_delete_now', array( $this, 'schedule_delete_now' ) );
		add_action( 'admin_notices', array( $this, 'show_next_scheduled_delete' ) );
		add_action( 'load-settings_page_old-comment-cleaner', array( $this, 'check_and_schedule_event' ) );
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'old-comment-cleaner', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	public function add_settings_page() {
		add_options_page(
			esc_html__( 'Old Comment Cleaner Settings', 'old-comment-cleaner' ),
			esc_html__( 'Old Comment Cleaner', 'old-comment-cleaner' ),
			'manage_options',
			'old-comment-cleaner',
			array( $this, 'settings_page' )
		);
	}

	public function register_settings() {
		register_setting(
			'old_comment_cleaner_settings',
			'old_comment_cleaner_days_old',
			array(
				'sanitize_callback' => array( $this, 'sanitize_days_old' ),
				'default'           => self::DAYS_OLD_DEFAULT,
			)
		);
		register_setting(
			'old_comment_cleaner_settings',
			'old_comment_cleaner_delete_email',
			array(
				'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
				'default'           => 0,
			)
		);
		register_setting(
			'old_comment_cleaner_settings',
			'old_comment_cleaner_delete_name',
			array(
				'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
				'default'           => 0,
			)
		);
		register_setting(
			'old_comment_cleaner_settings',
			'old_comment_cleaner_delete_url',
			array(
				'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
				'default'           => 0,
			)
		);
		register_setting(
			'old_comment_cleaner_settings',
			'old_comment_cleaner_confirm_delete',
			array(
				'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
				'default'           => 0,
			)
		);
	}

	public function sanitize_days_old( $input ) {
		$input = absint( $input );
		return ( $input > 0 ) ? $input : self::DAYS_OLD_DEFAULT;
	}

	public function sanitize_checkbox( $input ) {
		return ( 1 == $input ) ? 1 : 0;
	}

	public function settings_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Old Comment Cleaner Settings', 'old-comment-cleaner' ); ?></h1>
			<p>
				<?php esc_html_e( 'This plugin will clean up comments older than the specified number of days. It will update email addresses, names, and website URLs in those comments. It will not delete them.', 'old-comment-cleaner' ); ?>
				<?php esc_html_e( 'Please be sure to backup your comments before running the cleanup as this cannot be undone.', 'old-comment-cleaner' ); ?>
			</p>
			<p>
				<?php esc_html_e( 'After you check the "Confirm Cleaning" checkbox, the plugin will become destructive and start cleaning comments the next time the scheduled cleaning operation runs. This also applies to the "Clean Now" button.', 'old-comment-cleaner' ); ?>
			</p>
			<p>
				<?php esc_html_e( 'When comments are cleaned:', 'old-comment-cleaner' ); ?>
				<ol>
					<li><?php esc_html_e( 'Email addresses will be replaced with "example@example.com".', 'old-comment-cleaner' ); ?></li>
					<li><?php esc_html_e( 'Names will be replaced with "Anonymous Guest".', 'old-comment-cleaner' ); ?></li>
					<li><?php esc_html_e( 'Website URLs will be replaced with an empty string.', 'old-comment-cleaner' ); ?></li>
				</ol>
			</p>
			<form method="post" action="options.php">
				<?php settings_fields( 'old_comment_cleaner_settings' ); ?>
				<?php do_settings_sections( 'old_comment_cleaner_settings' ); ?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Clean comments older than (days)', 'old-comment-cleaner' ); ?></th>
						<td><input type="number" name="old_comment_cleaner_days_old" value="<?php echo esc_attr( get_option( 'old_comment_cleaner_days_old', self::DAYS_OLD_DEFAULT ) ); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Clean email addresses', 'old-comment-cleaner' ); ?></th>
						<td><input type="checkbox" name="old_comment_cleaner_delete_email" value="1" <?php checked( 1, get_option( 'old_comment_cleaner_delete_email' ), true ); ?> /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Clean names', 'old-comment-cleaner' ); ?></th>
						<td><input type="checkbox" name="old_comment_cleaner_delete_name" value="1" <?php checked( 1, get_option( 'old_comment_cleaner_delete_name' ), true ); ?> /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Clean website URLs', 'old-comment-cleaner' ); ?></th>
						<td><input type="checkbox" name="old_comment_cleaner_delete_url" value="1" <?php checked( 1, get_option( 'old_comment_cleaner_delete_url' ), true ); ?> /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Confirm Cleaning', 'old-comment-cleaner' ); ?></th>
						<td><input type="checkbox" name="old_comment_cleaner_confirm_delete" value="1" <?php checked( 1, get_option( 'old_comment_cleaner_confirm_delete' ), true ); ?> /></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="old_comment_cleaner_delete_now">
				<?php wp_nonce_field( 'old_comment_cleaner_delete_now_action', 'old_comment_cleaner_delete_now_nonce' ); ?>
				<?php submit_button( esc_html__( 'Clean Now', 'old-comment-cleaner' ), 'secondary' ); ?>
			</form>
			<?php $this->display_affected_comments_count(); ?>
		</div>
		<?php
	}

	private function display_affected_comments_count() {
		$days_old = get_option( 'old_comment_cleaner_days_old', 0 );
		if ( $days_old > 0 ) {
			$cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( "-$days_old days" ) );
			$args        = array(
				'date_query'          => array(
					array(
						'before'    => $cutoff_date,
						'inclusive' => false,
					),
				),
				'count'                => true,
			);

			$comment_query = new WP_Comment_Query();
			$count         = $comment_query->query( $args );

			/* translators: %d: number of comments to be updated */
			echo '<p>' . sprintf( esc_html__( 'Comments older than %d days: %d', 'old-comment-cleaner' ), esc_html( $days_old ), esc_html( $count ) ) . '</p>';
		}
	}

	public function delete_old_comments() {
		global $wpdb;

		// Check if the confirmation checkbox is checked
		if ( ! get_option( 'old_comment_cleaner_confirm_delete', 0 ) ) {
			return;
		}

		$days_old     = get_option( 'old_comment_cleaner_days_old', 730 );
		$delete_email = get_option( 'old_comment_cleaner_delete_email', 0 );
		$delete_name  = get_option( 'old_comment_cleaner_delete_name', 0 );
		$delete_url   = get_option( 'old_comment_cleaner_delete_url', 0 );

		// Check if all options are 0, if so, return early
		if ( 0 == $delete_email && 0 == $delete_name && 0 == $delete_url ) {
			return;
		}

		$cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( "-$days_old days" ) );
		$batch_size  = 100;
		$offset      = 0;
		do {
			$args = array(
				'date_query' => array(
					array(
						'before'    => $cutoff_date,
						'inclusive' => false,
					),
				),
				'number'     => $batch_size,
				'offset'     => $offset,
				'orderby'    => 'comment_date',
				'order'      => 'ASC',
			);

			$comment_query = new WP_Comment_Query();
			$comments      = $comment_query->query( $args );

			foreach ( $comments as $comment ) {
				$update_data = array();

				if ( $delete_email ) {
					$update_data['comment_author_email'] = 'example@example.com';
				}

				if ( $delete_name ) {
					$update_data['comment_author'] = esc_html__( 'Anonymous Guest', 'old-comment-cleaner' );
				}

				if ( $delete_url ) {
					$update_data['comment_author_url'] = '';
				}

				if ( ! empty( $update_data ) ) {
					$update_data['comment_ID'] = $comment->comment_ID;
					wp_update_comment( $update_data );
				}
			}

			$offset += $batch_size;
		} while ( count( $comments ) == $batch_size );
	}

	public function schedule_delete_now() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'old-comment-cleaner' ) );
		}

		// Verify nonce
		if ( ! isset( $_POST['old_comment_cleaner_delete_now_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['old_comment_cleaner_delete_now_nonce'] ) ), 'old_comment_cleaner_delete_now_action' ) ) {
			wp_die( esc_html__( 'Nonce verification failed.', 'old-comment-cleaner' ) );
		}

		// Schedule a one-time event to delete old comments
		if ( ! wp_next_scheduled( 'old_comment_cleaner_delete_old_comments_now' ) ) {
			wp_schedule_single_event( time(), 'old_comment_cleaner_delete_old_comments_now' );
		}
		set_transient( 'old_comment_cleaner_delete_old_comments_now', true, 3 );
		wp_redirect( admin_url( 'options-general.php?page=old-comment-cleaner' ) );
		exit;
	}

	public function show_next_scheduled_delete() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $screen->id !== 'settings_page_old-comment-cleaner' ) {
			return;
		}

		// Check if the confirmation checkbox is checked
		$notice_type = get_option( 'old_comment_cleaner_confirm_delete', 0 ) ? 'info' : 'error';
		$notice_message = get_option( 'old_comment_cleaner_confirm_delete', 0 )
			? __( 'Comments will be updated when the scheduled job runs.', 'old-comment-cleaner' )
			: __( 'Comments will not be updated. Please check the confirmation checkbox to allow updates.', 'old-comment-cleaner' );

		echo '<div class="notice notice-' . esc_attr( $notice_type ) . ' is-dismissible">';
		echo '<p>' . esc_html( $notice_message ) . '</p>';
		echo '</div>';

		$timestamp = wp_next_scheduled( 'old_comment_cleaner_delete_old_comments' );
		if ( $timestamp ) {
			$scheduled_time = gmdate( 'Y-m-d H:i:s', $timestamp );
			echo '<div class="notice notice-info is-dismissible">';
			/* translators: %s: scheduled time */
			echo '<p>' . sprintf( esc_html__( 'Next scheduled cleaning operation: %s', 'old-comment-cleaner' ), esc_html( $scheduled_time ) ) . '</p>';
			echo '</div>';
		}

		// Check if the 'scheduled' query parameter is set
		if ( get_transient( 'old_comment_cleaner_delete_old_comments_now' ) ) {
			echo '<div class="notice notice-info is-dismissible">';
			echo '<p>' . esc_html__( 'Old comments are being processed.', 'old-comment-cleaner' ) . '</p>';
			echo '</div>';
		}
	}

	public function check_and_schedule_event() {
		if ( ! wp_next_scheduled( 'old_comment_cleaner_delete_old_comments' ) ) {
			wp_schedule_event( time(), 'daily', 'old_comment_cleaner_delete_old_comments' );
			return;
		}
	}

	public static function activate() {
		if ( ! wp_next_scheduled( 'old_comment_cleaner_delete_old_comments' ) ) {
			wp_schedule_event( time(), 'daily', 'old_comment_cleaner_delete_old_comments' );
		}
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( 'old_comment_cleaner_delete_old_comments' );

		// Delete plugin options
		delete_option( 'old_comment_cleaner_days_old' );
		delete_option( 'old_comment_cleaner_delete_email' );
		delete_option( 'old_comment_cleaner_delete_name' );
		delete_option( 'old_comment_cleaner_delete_url' );
		delete_option( 'old_comment_cleaner_confirm_delete' );
	}

	public static function uninstall() {
		// Delete plugin options
		delete_option( 'old_comment_cleaner_days_old' );
		delete_option( 'old_comment_cleaner_delete_email' );
		delete_option( 'old_comment_cleaner_delete_name' );
		delete_option( 'old_comment_cleaner_delete_url' );
		delete_option( 'old_comment_cleaner_confirm_delete' );
	}
}

$old_comment_cleaner = new Old_Comment_Cleaner_Plugin();

register_activation_hook( __FILE__, array( 'Old_Comment_Cleaner_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Old_Comment_Cleaner_Plugin', 'deactivate' ) );
register_uninstall_hook( __FILE__, array( 'Old_Comment_Cleaner_Plugin', 'uninstall' ) );
