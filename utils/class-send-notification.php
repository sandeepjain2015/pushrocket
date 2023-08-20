<?php
/**
 * Send_Notification class for sending notifications on post publish.
 *
 * @package Pushrocket
 */

namespace Pushrocket;

/**
 * Send_Notification class for sending notifications on post publish.
 */
class Send_Notification {
	/**
	 * Constructor for the Send_Notification class.
	 */
	public function __construct() {
		add_action( 'wp_after_insert_post', array( $this, 'send_notification_on_publish' ), 10, 4 );
	}
	/**
	 * Sends a notification when a post or web story is published.
	 *
	 * @param int     $id          The ID of the post being published.
	 * @param WP_Post $post        The post object being published.
	 * @param bool    $update      Whether this is an update to an existing post.
	 * @param WP_Post $post_before The post object before the update.
	 */
	public function send_notification_on_publish( $id, $post, $update, $post_before ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		$show_multiple_site = get_transient( 'pushrocket_show_multiple_site' );
		if ( 'yes' !== $show_multiple_site ) {
			return;
		}
		$post_type = $post->post_type;
		if ( 'publish' !== $post->post_status || ( $post_before && 'publish' === $post_before->post_status ) || wp_is_post_revision( $id ) ) {
			return;
		}
		if ( 'post' === $post_type || 'web-story' === $post_type ) {
			if ( $post->post_date === $post->post_modified ) {
				$pushrocket_panel_url     = get_option( 'pushrocket_panel_url' );
				$pushrocket_website_code  = get_option( 'pushrocket_website_code' );
				$pushrocket_username      = get_option( 'pushrocket_username' );
				$pushrocket_password      = get_option( 'pushrocket_password' );
				$pushrocket_website_lists = get_option( 'pushrocket_website_lists' );
				$website_lists            = count( $pushrocket_website_lists ) > 0 ? implode( ',', $pushrocket_website_lists ) : '';
				if ( 'post' === $post_type ) {
					$pushrocket_push_on_publish = get_option( 'pushrocket_push_on_publish' );
					if ( 1 !== (int) $pushrocket_push_on_publish ) {
						return false;
					}
				}
				if ( 'web-story' === $post_type ) {
					$pushrocket_push_on_publish_for_webstories = get_option( 'pushrocket_push_on_publish_for_webstories' );
					if ( 1 !== (int) $pushrocket_push_on_publish_for_webstories ) {
						return false;
					}
				}

				if ( class_exists( 'WPSEO_Options' ) ) {
					$title       = get_post_meta( $id, '_yoast_wpseo_title', true );
					$description = get_post_meta( $id, '_yoast_wpseo_metadesc', true );
				} elseif ( class_exists( 'RankMath' ) ) {
					$title       = get_post_meta( $id, 'rank_math_title', true );
					$description = get_post_meta( $id, 'rank_math_description', true );
				}
				if ( 'web-story' === $post_type ) {
					$title       = get_the_title( $id );
					$description = get_post_field( 'post_excerpt', $id );
				}
				if ( empty( $title ) ) {
					$title = $post->post_title;
				}
				if ( empty( $description ) ) {
					$description = $post->post_content;
					$description = apply_filters( 'the_content', $post->post_content );
				}
					$img_url      = get_the_post_thumbnail_url( $id, 'large' );
					$data_to_send = array(
						'WebsiteURL'      => $pushrocket_panel_url,
						'WebsiteCode'     => $pushrocket_website_code,
						'UserName'        => $pushrocket_username,
						'Password'        => $pushrocket_password,
						'WebsiteList'     => $website_lists,
						'MetaTitle'       => $title,
						'MetaDescription' => $description,
						'ImageURL'        => $img_url,
						'PostURL'         => get_the_permalink( $id ),
						'PostType'        => $post_type,
					);
					$response     = wp_remote_post(
						'https://pushrocket.one/api/User/SendNotification',
						array(
							'body' => $data_to_send, // Pass the data here.
						)
					);
			}
		} else {
			return false;
		}
	}
}

new Send_Notification();

