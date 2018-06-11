<?php
/*
Plugin Name: Commentariat
Plugin URI: http://www.allendav.com/
Description: Quickly generate or delete comments on a WordPress blog for testing
Version: 1.0.0
Author: allendav
Author URI: http://www.allendav.com
License: GPL2
*/

class Commentariat {
	private static $instance;

	public static function getInstance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __clone() {
	}

	private function __wakeup() {
	}

	protected function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	public function admin_menu() {
		add_submenu_page(
			'tools.php',
			__( 'Commentariat', 'commentariat' ),
			__( 'Commentariat', 'commentariat' ),
			'manage_options',
			'commentariat-page',
			array( $this, 'page' )
		);
	}
	
	public function page() {
		global $title;

		$action = isset( $_POST['action'] ) ? sanitize_text_field( $_POST['action'] ) : '';
		$number = isset( $_POST['number'] ) ? (int) sanitize_text_field( $_POST['number'] ) : 0;
		$email  = isset( $_POST['email'] ) ? sanitize_text_field( $_POST['email'] ) : '';

		// Get any action
		if ( ! empty( $action ) ) {
			check_admin_referer( $action );

			// Handle comment generation if needed
			if ( 'generate-comments' === $action ) {
				if ( $number < 1 ) {
					add_settings_error(
						'commentariat',
						'commentariat',
						__( 'Number of comments to create must be at least 1.' ),
						'error'
					);
				} else if ( ! is_email( $email ) ) {
					add_settings_error(
						'commentariat',
						'commentariat',
						__( 'You must give a valid email address for the comments.' ),
						'error'
					);
				} else {
					// Find the latest post - we'll add the comments there
					$posts = get_posts(
						array(
							'numberposts' => 1,
							'orderby' => 'date',
							'order' => 'DESC',
							'postype' => 'post'
						)
					);

					if ( empty( $posts ) ) {
						add_settings_error(
							'commentariat',
							'commentariat',
							__( 'You must have at least one published post before you can create comments.' ),
							'error'
						);
					}

					$post_ID = $posts[0]->ID;
					$start_time = time();
				
					for ( $i = 0; $i < $number; $i++ ) {
						wp_new_comment(
							array(
								'comment_post_ID'      => $post_ID,
								'comment_author'       => 'Comment Author',
								'comment_author_email' => $email,
								'comment_author_url'   => 'http://example.com',
								'comment_content'      => 'Comment messsage... ' . rand(),
								'comment_type'         => ''
							)
						);
					}

					$stop_time = time();

					add_settings_error(
						'commentariat',
						'commentariat',
						sprintf(
							__( 'Created %1$d comment(s) in %2$d seconds' ),
							$number,
							$stop_time - $start_time
						),
						'updated'
					);
				}
			}

			// Handle comment deletion if needed
			if ( 'delete-comments' === $action ) {

				$start_time = time();
				$comments = get_comments();
				foreach( (array) $comments as $comment ) {
					wp_delete_comment( $comment->comment_ID, true );
				}
				$stop_time = time();

				add_settings_error(
					'commentariat',
					'commentariat',
					sprintf(
						__( 'Deleted %1$d comment(s) in %2$d seconds' ),
						count( $comments ),
						$stop_time - $start_time
					),
					'updated'
				);
			}
		}

		$current_user = wp_get_current_user();

		$number = (int) get_user_meta( $current_user->ID, 'commentariat_number', true );
		if ( 0 === $number ) {
			$number = 500;
		}
		
		$email = get_user_meta( $current_user->ID, 'commentariat_email', true );
		if ( ! is_email( $email ) ) {
			$email = $current_user->user_email;
		}

		?>
		<div class="wrap">
			<h1>
				<?php echo esc_html( $title ); ?>
			</h1>

			<p class="description">
				<?php esc_html_e( 'Quickly generate or delete comments on a WordPress blog for testing', 'commentariat' ); ?>
			</p>

			<?php settings_errors( 'commentariat' ); ?>

			<h2 class="title">
				<?php esc_html_e( 'Generate Comments', 'commentariat' ) ?>
			</h2>

			<form method="post" action="">
				<input type="hidden" name="action" value="generate-comments">
				<table class="form-table">
					<tr>
						<th scope="row">
							<?php _e( 'Number of comments to generate' ); ?>
						</th>
						<td>
							<input type="number" name="number" id="number" value="<?php echo esc_attr( $number ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php _e( 'Commenter Email Address' ); ?>
						</th>
						<td>
							<input class="regular-text" type="email" name="email" id="email" value="<?php echo esc_attr( $email ); ?>">
						</td>
					</tr>
				</table>

				<?php
				wp_nonce_field( 'generate-comments' );
				submit_button( __( 'Generate!' ), 'primary', 'generate' );
				?>
			</form>

			<br/>
			<br/>

			<h2 class="title">
				<?php esc_html_e( 'Delete Comments', 'commentariat' ) ?>
			</h2>
			<p>
				<?php esc_html_e( 'Deletes all comments on the site', 'commentariat' ); ?>
			</p>

			<form method="post" action="">
				<input type="hidden" name="action" value="delete-comments">
				<?php
				wp_nonce_field( 'delete-comments' );
				submit_button( __( 'Delete' ), 'delete', 'delete' );
				?>
			</form>
		</div>
	<?php
	}
}

Commentariat::getInstance();
