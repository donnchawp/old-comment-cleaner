<?php
/*
Plugin Name: Old Comment Cleaner
Plugin URI: https://odd.blog/old-comment-cleaner/
Description: Deletes old comment data based on user-defined settings.
Version: 1.0
Author: Donncha O Caoimh
*/

// Prevent direct access to the plugin file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Old_Comment_Cleaner {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'occ_delete_old_comments', array( $this, 'delete_old_comments' ) );
		add_action( 'occ_delete_old_comments_now', array( $this, 'delete_old_comments' ) );
		add_action( 'admin_post_occ_delete_now', array( $this, 'schedule_delete_now' ) );
		add_action( 'admin_notices', array( $this, 'show_next_scheduled_delete' ) );
		add_action( 'load-settings_page_old-comment-cleaner', array( $this, 'check_and_schedule_event' ) );
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
		register_setting( 'occ_settings', 'occ_days_old' );
		register_setting( 'occ_settings', 'occ_delete_email' );
		register_setting( 'occ_settings', 'occ_delete_name' );
		register_setting( 'occ_settings', 'occ_delete_url' );
		register_setting( 'occ_settings', 'occ_confirm_delete' );
	}

	public function settings_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Old Comment Cleaner Settings', 'old-comment-cleaner' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'occ_settings' ); ?>
				<?php do_settings_sections( 'occ_settings' ); ?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Delete comments older than (days)', 'old-comment-cleaner' ); ?></th>
						<td><input type="number" name="occ_days_old" value="<?php echo esc_attr( get_option( 'occ_days_old' ) ); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Delete email addresses', 'old-comment-cleaner' ); ?></th>
						<td><input type="checkbox" name="occ_delete_email" value="1" <?php checked( 1, get_option( 'occ_delete_email' ), true ); ?> /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Delete names', 'old-comment-cleaner' ); ?></th>
						<td><input type="checkbox" name="occ_delete_name" value="1" <?php checked( 1, get_option( 'occ_delete_name' ), true ); ?> /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Delete website URLs', 'old-comment-cleaner' ); ?></th>
						<td><input type="checkbox" name="occ_delete_url" value="1" <?php checked( 1, get_option( 'occ_delete_url' ), true ); ?> /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Confirm Deletion', 'old-comment-cleaner' ); ?></th>
						<td><input type="checkbox" name="occ_confirm_delete" value="1" <?php checked( 1, get_option( 'occ_confirm_delete' ), true ); ?> /></td>
					</tr>
				</table>
				<?php submit_button(); ?>
				
			</form>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="occ_delete_now">
				<?php wp_nonce_field( 'occ_delete_now_action', 'occ_delete_now_nonce' ); ?>
				<?php submit_button( esc_html__( 'Delete Now', 'old-comment-cleaner' ), 'secondary' ); ?>
			</form>
			
		</div>
		<?php
	}

	public function delete_old_comments() {
		global $wpdb;

		// Check if the confirmation checkbox is checked
		if ( ! get_option( 'occ_confirm_delete', 0 ) ) {
			return;
		}

		$days_old = get_option( 'occ_days_old', 730 );
		$delete_email = get_option( 'occ_delete_email', 0 );
		$delete_name = get_option( 'occ_delete_name', 0 );
		$delete_url = get_option( 'occ_delete_url', 0 );

		// Check if all options are 0, if so, return early
		if ( $delete_email == 0 && $delete_name == 0 && $delete_url == 0 ) {
			return;
		}

		$cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-$days_old days" ) );
		$batch_size = 100;
		$offset = 0;
		$c = 0;
		do {
			$comments = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->comments} 
					WHERE comment_date < %s 
					AND comment_author_email != %s
					AND comment_author != %s
					LIMIT %d OFFSET %d",
					$cutoff_date,
					'example@example.com',
					sanitize_text_field( __( 'Anonymous Guest', 'old-comment-cleaner' ) ),
					$batch_size,
					$offset
				)
			);

			foreach ( $comments as $comment ) {
				$c++;
				$update_data = array();
				$update_format = array();

				if ( $delete_email ) {
					$update_data['comment_author_email'] = 'example@example.com';
					$update_format[] = '%s';
				}

				if ( $delete_name ) {
					$update_data['comment_author'] = esc_html__( 'Anonymous Guest', 'old-comment-cleaner' );
					$update_format[] = '%s';
				}

				if ( $delete_url ) {
					$update_data['comment_author_url'] = '';
					$update_format[] = '%s';
				}

				if ( ! empty( $update_data ) ) {
					error_log( $c . ' - update_data for comment ID ' . $comment->comment_ID . ' at ' . $comment->comment_date );
					
					/*
					$wpdb->update(
						$wpdb->comments,
						$update_data,
						array( 'comment_ID' => $comment->comment_ID ),
						$update_format,
						array( '%d' )
					);
					*/
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
		if ( ! isset( $_POST['occ_delete_now_nonce'] ) || ! wp_verify_nonce( $_POST['occ_delete_now_nonce'], 'occ_delete_now_action' ) ) {
			wp_die( esc_html__( 'Nonce verification failed.', 'old-comment-cleaner' ) );
		}

		// Schedule a one-time event to delete old comments
		if ( ! wp_next_scheduled( 'occ_delete_old_comments_now' ) ) {
			wp_schedule_single_event( time(), 'occ_delete_old_comments_now' );
		}
		set_transient( 'occ_delete_old_comments_now', true, 3 );
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
		$notice_type = get_option( 'occ_confirm_delete', 0 ) ? 'info' : 'error';
		$notice_message = get_option( 'occ_confirm_delete', 0 )
			? esc_html__( 'Comments will be updated when the scheduled job runs.', 'old-comment-cleaner' ) 
			: esc_html__( 'Comments will not be updated. Please check the confirmation checkbox to allow updates.', 'old-comment-cleaner' );

		echo '<div class="notice notice-' . esc_attr( $notice_type ) . ' is-dismissible">';
		echo '<p>' . $notice_message . '</p>';
		echo '</div>';

		$timestamp = wp_next_scheduled( 'occ_delete_old_comments' );
		if ( $timestamp ) {
			$scheduled_time = date( 'Y-m-d H:i:s', $timestamp );
			echo '<div class="notice notice-info is-dismissible">';
			echo '<p>' . sprintf( esc_html__( 'Next scheduled cleaning operation: %s', 'old-comment-cleaner' ), esc_html( $scheduled_time ) ) . '</p>';
			echo '</div>';
		}

		// Check if the 'scheduled' query parameter is set
		if ( get_transient( 'occ_delete_old_comments_now' ) ) {
			echo '<div class="notice notice-info is-dismissible">';
			echo '<p>' . esc_html__( 'Old comments are being processed.', 'old-comment-cleaner' ) . '</p>';
			echo '</div>';
		}
	}

	public function check_and_schedule_event() {
		if ( ! wp_next_scheduled( 'occ_delete_old_comments' ) ) {
			wp_schedule_event( time(), 'daily', 'occ_delete_old_comments' );
			return;
		}
	}

	public static function activate() {
		if ( ! wp_next_scheduled( 'occ_delete_old_comments' ) ) {
			wp_schedule_event( time(), 'daily', 'occ_delete_old_comments' );
		}
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( 'occ_delete_old_comments' );

		// Delete plugin options
		delete_option( 'occ_days_old' );
		delete_option( 'occ_delete_email' );
		delete_option( 'occ_delete_name' );
		delete_option( 'occ_delete_url' );
		delete_option( 'occ_confirm_delete' );
	}

	public static function uninstall() {
		// Delete plugin options
		delete_option( 'occ_days_old' );
		delete_option( 'occ_delete_email' );
		delete_option( 'occ_delete_name' );
		delete_option( 'occ_delete_url' );
		delete_option( 'occ_confirm_delete' );
	}
}

$old_comment_cleaner = new Old_Comment_Cleaner();

register_activation_hook( __FILE__, array( 'Old_Comment_Cleaner', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Old_Comment_Cleaner', 'deactivate' ) );
register_uninstall_hook( __FILE__, array( 'Old_Comment_Cleaner', 'uninstall' ) );