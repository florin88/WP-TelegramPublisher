<?php
/*
Plugin Name: Telegram Publisher
Plugin URI: https://2088.it
Version: 1.2
Author: Flavius Florin Harabor (@ErBoss88)
Author URI: https://2088.it/io-nerd/
Description: Questo plugin invia in automatico un messaggio nel tuo canale Telegram, ogni volta che pubblichi un articolo nel tuo blog o nel tuo e-commerce. Il post è compost da: Post Title, Link, Tag e Categoria
*/


if ( !function_exists( 'add_action' ) ) {
	echo( 'Questo è un plugin per WordPress, non può fare cose diverse da quelle che sono le funzioni con cui è stato sviluppato' );
	exit;
}

if ( is_admin() ) {
	include_once( 'settings-page-plug.php' );
}
require_once( 'notifcaster.class.php' );

function telegramPublisher_register( $plugin_array ) {
}

function telegramPublisher_addSettings( $links, $file ) {
	static $this_plugin;
	if ( !$this_plugin ) $this_plugin = plugin_basename( __FILE__ );
	if ( $file == $this_plugin ) {
		$settings_link = '<a href="options-general.php?page=telegram-publisher">' . esc_html__('Impostazioni', 'telegram-publisher') . '</a>';
		$links = array_merge( array( $settings_link ), $links );
	}
	return( $links );
}

function telegramPublisher_triggerError( $message ) {
    if ( isset( $_GET['action'] ) && $_GET['action'] == 'error_scrape' ) {
        echo( "<strong>$message</strong>" );
        exit;
    } else {
    	trigger_error( $message, E_USER_ERROR );
    }
}

function telegramPublisher_setOptions() {
	if ( get_option( 'telegramPublisher botToken' ) === false ) {
		update_option( 'telegramPublisher_botToken', '', 'no' );
	}
	if ( get_option( 'telegramPublisher_channelName' ) === false ) {
		update_option( 'telegramPublisher_channelName', '', 'no' );
	}
	if ( get_option( 'telegramPublisher_autoPost' ) === false ) {
		update_option( 'telegramPublisher_autoPost', true, 'no' );
	}
	if ( get_option( 'telegramPublisher_messageStyle' ) === false ) {
		update_option( 'telegramPublisher_messageStyle', 'webpreview', 'no' );
	}
	if ( get_option( 'telegramPublisher MarkD' ) === false ) {
		update_option( 'telegramPublisher_MarkD', true, 'no' );
	}
	if ( get_option( 'telegramPublisher_messageTemplate' ) === false ) {
		update_option( 'telegramPublishermessageTemplate', '{TITLE} {FULLURL} {TAGS}', 'no' );
	}
}

function telegramPublisher_isActive() {
	if ( '' == get_option('telegramPublisher_botToken') ) {
		return( '1' );
	}
	else if ( '' == get_option('telegramPublisher_channelName') ) {
		return( '2' );
	}
	else if ( '' != get_option('telegramPublisher botToken') ) {
		$nt = new Notifcaster_Class();
		$nt->_telegram( get_option('telegramPublisher_botToken') );
		$result = $nt->get_bot();
		if ( '1' != $result['ok'] ) {
			return( '3' ); 
		}
	}
	return( true );
}


function telegramPublisher_activateBlogMultisite( $blogID ) {
    global $wpdb;
    if ( is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
		switch_to_blog( $blogID );
		my_plugin_activate();
		restore_current_blog();
    }
}
add_action( 'wp_insert_site', 'telegramPublisher_activateBlogMultisite' );


function telegramPublisher_postIt() {
	global $post;
	if ( telegramPublisher_isActive() === true ) {
		$toChannel = false;
		if ( get_option('telegramPublisher_autoPost' ) ) {
			$toChannel = true;
		}
		$isSent = get_post_meta( $post->ID, '_telegramPublisher_isSent', true );
		if ( true == $isSent ) {
			$toChannel = false;
		}
		if ( 'publish' == get_post_status ( $post->ID ) ) {
			$toChannel = false;
		} else {
			if ( 'auto-draft' != get_post_status ( $post->ID ) ) {
				switch ( get_post_meta( $post->ID, '_telegramPublisher_postOnTelegram', true ) ) {
					case 0:
						$toChannel = false;
						break;
					case 1:
						$toChannel = true;
						break;
				}
			}
		}
		wp_nonce_field( plugin_basename(__FILE__), 'telegramPublisher_toChannel_nonce' );
		?>
			<div class="misc-pub-section telegram-publisher">
				<label for="telegramPublisher_toChannel">
					<input type="checkbox" name="telegramPublisher_toChannel" id="telegramPublisher_toChannel" value="1" <?php checked( $toChannel ); ?>/>
					<?php if ( true == $isSent ): ?>
						<?php esc_html_e('Ripubblica su Telegram', 'telegram-publisher'); ?>
					<?php else: ?>
						<?php esc_html_e('Pubblica su Telegram', 'telegram-publisher'); ?>
					<?php endif; ?>
				</label>
			</div>
		<?php
	}
}
add_action( 'post_submitbox_misc_actions', 'telegramPublisher_postIt', 10, 1 );


function telegramPublisher_saveIt( $post_id ) {

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return( false );
    if ( empty( $post_id ) ) return( false );
    if ( false !== wp_is_post_revision( $post_id ) ) return( false );
    if ( !wp_verify_nonce( $_POST['telegramPublisher_toChannel_nonce'], plugin_basename(__FILE__) ) ) return( $post_id );
    if ( telegramPublisher_isActive() !== true ) return( false );
    
    if ( 'publish' != get_post_status( $post_id ) ) {
    	if ( $_POST['telegramPublisher_toChannel'] ) {
    		update_post_meta( $post_id, '_telegramPublisher_postOnTelegram', 1 );
    	}
    	else {
    		update_post_meta( $post_id, '_telegramPublisher_postOnTelegram', 0 );
    	}
    }
    
}
add_action( 'save_post', 'telegramPublisher_saveIt' );


function telegramPublisher_publishPost( $post_id ) {   

	if ( is_admin() ) {
	    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return( false );
	    if ( empty( $post_id ) ) return( false );
	    if ( false !== wp_is_post_revision( $post_id ) ) return( false );
	    if ( !wp_verify_nonce( $_POST['telegramPublisher_toChannel_nonce'], plugin_basename(__FILE__) ) ) return( $post_id );
	    if ( !isset( $_POST['telegramPublisher_toChannel'] ) ) return( $post_id );
	    if ( true != $_POST['telegramPublisher_toChannel'] ) return( false );
	    if ( telegramPublisher_isActive() !== true ) return( false );
	}
	
	if ( !defined( 'DOING_CRON' ) && !DOING_CRON ) {
		update_post_meta( $post_id, '_telegramPublisher_postOnTelegram', $_POST['telegramPublisher_toChannel'] );
	}
    
    $botToken = get_option('telegramPublisher_botToken');
    $channelName = get_option('telegramPublisher_channelName');
    
    switch ( get_option('telegramPublisher_messageStyle') ) {
	    case 'plaintext':
	    	$disableWebPreview = true;
	    	$featuredImage = false;
	    	break;
    	case 'featuredimage':
    		$disableWebPreview = true;
    		$featuredImage = true;
    		break;
    	case 'webpreview':
    	default:
    		$disableWebPreview = false;
    		$featuredImage = false;
    		break;
    }
    
    $postMessageTel = get_option('telegramPublisher_messageTemplate');
    if ( '' == $postMessageTel ) {
    	$postMessageTel = '{TITLE} {EXCERPT} {FULLURL}';
    }
    
    $postMessageTel = str_replace( '{TITLE}', get_the_title( $post_id ), $postMessageTel );
    $postMessageTel = str_replace( '{FULLURL}', get_permalink( $post_id ), $postMessageTel );
    $postMessageTel = str_replace( '{SHORTURL}', wp_get_shortlink( $post_id ), $postMessageTel );
    $postMessageTel = str_replace( '{EXCERPT}', wp_trim_words( get_post_field( 'post_content', $post_id ), 60, '...' ), $postMessageTel );
    
    if ( strpos( $postMessageTel, '{TAGS}' ) !== false ) {
    	$postTags = wp_get_post_tags( $post_id, array( 'fields' => 'names' ) );
    	foreach ($postTags as $tag) {
    		$tagList .= ' #' . str_replace( ' ', '', $tag );
    	}
    	$postMessageTel = str_replace( '{TAGS}', substr( $tagList, 1 ), $postMessageTel );
    }
    
    if ( strpos( $postMessageTel, '{CATEGORIES}' ) !== false ) {
    	$postCategories = wp_get_post_categories( $post_id, array( 'fields' => 'names' ) );
    	foreach ($postCategories as $category) {
    		$categoriesList .= $category . ', ' ;
    	}
    	$postMessageTel = str_replace( '{CATEGORIES}', substr( $categoriesList, 0, -2), $postMessageTel );
    }
    
    if ( get_option('telegramPublisher_MarkD') && get_post_meta( $post_id, '_telegramPublisher_isSent', true ) ) {
    	$postMessageTel = '*' . strtoupper( esc_html('Post Aggiornato Recentemente', 'telegram-publisher') ) . '* '."\n". $postMessageTel;
    }
    
    if ( $featuredImage && !has_post_thumbnail( $post_id ) ) {
    	$featuredImage = false;
    } else if ( $featuredImage ) {
    	$theFeaturedImage = get_attached_file( get_post_thumbnail_id( $post_id ) );
    }
    
    $nt = new Notifcaster_Class();
    $nt->_telegram( $botToken, 'markdown', $disableWebPreview );
	
	if ( $featuredImage ) {
		$sentResult = $nt->channel_photo( $channelName, $postMessageTel, $theFeaturedImage );
	} else {
		$sentResult = $nt->channel_text( $channelName, $postMessageTel);
	}
	
	if ( true == $sentResult["ok"] ) {
		add_post_meta( $post_id, '_telegramPublisher_isSent', true );
		delete_post_meta( $post_id, '_telegramPublisher_postOnTelegram' );
	}
}
add_action( 'publish_post', 'telegramPublisher_publishPost', 10, 2 );



add_filter( 'plugin_action_links', 'telegramPublisher_addSettings', 10, 2 );
add_filter( 'plugin_row_meta', 'telegramPublisher_addLinks', 10, 2 );
add_action( 'plugins_loaded', 'telegramPublisher_updateAction' );
add_action( 'admin_menu', 'telegramPublisher_pageInit' );
register_activation_hook( __FILE__, 'telegramPublisher_activatePlugin' );
register_uninstall_hook( __FILE__, 'telegramPublisher_uninstallPlugin' );


?>
