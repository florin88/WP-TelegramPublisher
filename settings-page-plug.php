<?php

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! is_admin() ) die;

function telegramPublisher_pageInit() {
	$settings_page = add_options_page( TelegramPublisher . ' Impostazioni', TelegramPublisher, 'manage_options', 'telegram-publisher', 'telegramPublisher_options_page' );
	add_action( "load-{$settings_page}", 'telegramPublisher_loadSettingsPage' );
}

function telegramPublisher_adminInit() {
}

function telegramPublisher_loadSettingsPage() {
	if ( 'Y' == $_POST["telegram-publisher-settings-submit"] ) {
		check_admin_referer( "telegram-publisher-setting-page" );
		telegramPublisher_saveSettings();
		$url_parameters = isset( $_GET['tab'] ) ? 'updated=true&tab=' . $_GET['tab'] : 'updated=true';
		wp_redirect( admin_url( "options-general.php?page=telegram-publisher&$url_parameters" ) );
		exit;
	}
}

function telegramPublisher_saveSettings() {			
	if ( '' != Trim( $_POST['telegramPublisher_channelName'] ) ) {
		$channelName = Trim( $_POST['telegramPublisher_channelName'] );
		if ( strpos( $channelName, '@' ) === false ) {
			$channelName = '@' . $channelName;
		}
	}
	update_option( 'telegramPublisher_botToken', Trim( $_POST['telegramPublisher_botToken'] ), 'no' );
	update_option( 'telegramPublisher_channelName', $channelName, 'no' );
	update_option( 'telegramPublisher_autoPost', $_POST['telegramPublisher_autoPost'], 'no' );
	update_option( 'telegramPublisher_messageStyle', $_POST['telegramPublisher_messageStyle'], 'no' );	   		
	update_option( 'telegramPublisher_MarkD', $_POST['telegramPublisher_MarkD'], 'no' );
	update_option( 'telegramPublisher_messageTemplate', wp_filter_kses( $_POST['telegramPublisher_messageTemplate'] ), 'no' );
}

function telegramPublisher_options_page() {
	?>
	<div class="wrap">
		<style>
			hr {
				margin-top: 10px !important;
				margin-bottom: 30px !important;
			}
			.st-infobox {
				display: block;
				background: #fff;
				border-left: 4px solid #fff;
				-webkit-box-shadow: 0 1px 1px 0 rgba(0, 0, 0, .1);
				box-shadow: 0 1px 1px 0 rgba(0, 0, 0, .1);
				margin: 20px 0 25px 0;
				padding: 10px 16px
			}
			.st-infobox.st-notice {
				border-left: 4px solid #ffba00;
			}
			.st-infobox.st-active {
				border-left: 4px solid #46b450;
			}
			.st-infobox.st-error {
				color: #b94a48;
				border-left-color: #dc3232
			}
			.st-infobox + h3 {
				padding-top: 8px;
			} 
			.st-infobox + .form-table {
				margin-top: -10px !important;
			} 
			.dashicons, .dashicons-before:before {
				line-height: 1.1 !important;
			}
			.btn {
				border: none; 
				color: white; 
				padding: 14px 28px; 
				cursor: pointer;
			}
			.save {
				background-color: #2196F3;}
            .save:hover {
				background: #0b7dda;}
			
			/* checkbox */
			.container {
				display: block;
				position: relative;
				padding-left: 35px;
				margin-bottom: 12px;
				cursor: pointer;  
				font-size: 16px;
				-webkit-user-select: none;
				-moz-user-select: none;
				-ms-user-select: none;
				user-select: none;
			}

			.container input {
				position: absolute;
				opacity: 0;
				cursor: pointer;
				height: 0;
				width: 0;
			}
			
			.checkmark {
				position: absolute;
				top: 0;
				left: 0;
				height: 25px;
				width: 25px;
				background-color: #fff;
			}
			
			.container:hover input ~ .checkmark {
				background-color: #ccc;
			}
			.container input:checked ~ .checkmark {
				background-color: #2196F3;
			}

			.checkmark:after {
				content: "";
				position: absolute;
				display: none;
			}

			.container input:checked ~ .checkmark:after {
				display: block;
			}

			.container .checkmark:after {
				left: 9px;
				top: 5px;
				width: 5px;
				height: 10px;
				border: solid white;
				border-width: 0 3px 3px 0;
				-webkit-transform: rotate(45deg);
				-ms-transform: rotate(45deg);
				transform: rotate(45deg);
			}
		</style>
		<div id="icon-options-general" class="icon32">
			<br>
		</div>
		<form method="post" action="<?php admin_url( 'options-general.php?page=telegram-publisher' ); ?>">
		<?php wp_nonce_field( "telegram-publisher-setting-page" ); ?>
		<hr />
		
		<?php $statusMeldung = telegramPublisher_isActive(); ?>
		
		<?php if ( '1' === $statusMeldung ): ?>
			<div class="st-infobox st-notice">
				<p><?php esc_html_e('Telegram Publisher non è attivo. Gentilmente imposta un bot come amministratore del tuo canale o gruppo Telegram.', 'telegram-publisher'); ?> :-)</p>
			</div>
		<?php elseif ( '2' === $statusMeldung ): ?>
			<div class="st-infobox st-notice">
				<p><?php esc_html_e('Telegram Publisher non è attivo. Gentilmente fornisci il nome di un canale o gruppo Telegram pubblico @ incluso nel nome.', 'telegram-publisher'); ?> :-)</p>
			</div>
		<?php elseif ( '3' === $statusMeldung ): ?>
			<div class="st-infobox st-error">
				<p><?php esc_html_e('Telegram Publisher non è attivo. Il token bot fornito non è corretto ', 'telegram-publisher'); ?></p>
			</div>
		<?php else: ?>
			<div class="st-infobox st-active">
				<?php
					$nt = new Notifcaster_Class();
					$nt->_telegram( get_option('telegramPublisher_botToken') );
					$result = $nt->get_bot();
				?>
				<p><strong><?php esc_html_e('Telegram Publisher è attivo.', 'telegram-publisher'); ?></strong></p>
				<p><strong><?php esc_html_e('Info bot collegato a questo blog e al canale o gruppo', 'telegram-publisher'); ?>: <?php echo( $result['result']['first_name'] ); ?> (@<?php echo( $result['result']['username'] ); ?>)</strong></p>
			</div>
		<?php endif; ?>
		
		<table class="form-table">
		
			<tr valign="top">
				<th scope="row"><label for="telegramPublisher_channelName"><?php esc_html_e('Username pubblico del canale o del gruppo', 'telegram-publisher'); ?>:</label></th>
				<td>
					<input type="text" style="width: 450px;" name="telegramPublisher_channelName" id="telegramPublisher_channelName" value="<?php echo( get_option('telegramPublisher_channelName') ); ?>" /> 
					<label for="telegramPublisher_channelName"><?php esc_html_e('Il nome del tuo canale o gruppo Telegram', 'telegram-publisher'); ?> <?php if ( '' != get_option('telegramPublisher_channelName') ): ?>(<a href="http://t.me/<?php echo( str_replace( '@', '', get_option('telegramPublisher_channelName') ) ); ?>"><?php echo( get_option('telegramPublisher_channelName') ); ?></a>)<?php endif; ?></label>
				</td>
			</tr>
			
			<tr valign="top">
				<th scope="row"><label for="telegramPublisher_botToken"><?php esc_html_e('Bot token', 'telegram-publisher'); ?>:</label></th>
				<td>
					<input type="text" style="width: 450px;" name="telegramPublisher_botToken" id="telegramPublisher_botToken" value="<?php echo( get_option('telegramPublisher_botToken') ); ?>" /> 
					<label for="telegramPublisher_botToken"><?php esc_html_e('Il bot nominato come amministratore per il tuo canale o gruppo Telegram:', 'telegram-publisher'); ?> </label>
				</td>
			</tr>
			
			<tr valign="top">
				<th scope="row"><label for="telegramPublisher_messageStyle"><?php esc_html_e('Stile messaggio', 'telegram-publisher'); ?></label></th>
				<td colspan="7">
					<select name="telegramPublisher_messageStyle" id="telegramPublisher_messageStyle" class="postform" style="min-width:450px;">
					   <option class="level-0" value="plaintext" <?php selected( get_option('telegramPublisher_messageStyle'), 'plaintext' ); ?>><?php esc_html_e('Solo testo', 'telegram-publisher'); ?></option> 
					   <option class="level-0" value="webpreview" <?php selected( get_option('telegramPublisher_messageStyle'), 'webpreview' ); ?>><?php esc_html_e('Mostra sottoforma di anteprima web', 'telegram-publisher'); ?> (<?php esc_html_e('Default', 'telegram-publisher'); ?>)</option>
					   <option class="level-0" value="featuredimage" <?php selected( get_option('telegramPublisher_messageStyle'), 'featuredimage' ); ?>><?php esc_html_e('Mostra immagine in anteprima con didascalia', 'telegram-publisher'); ?></option>
					</select>
					<label for="telegramPublisher_messageStyle"><?php esc_html_e('Opzioni per il sitele dei messaggi da inviare su Telegram:', 'telegram-publisher'); ?></label>
				</td>
			</tr>
			
			
			<tr valign="top">
				<th scope="row"><label for="telegramPublisher_autoPost"><?php esc_html_e('Predefinito per i nuovi post', 'telegram-publisher'); ?>:</label></th>
				<td>
					<label for="telegramPublisher_autoPost" class="container">
						<input type="checkbox" name="telegramPublisher_autoPost" id="telegramPublisher_autoPost" value="1" <?php checked( get_option('telegramPublisher_autoPost') ); ?>/>
						<?php esc_html_e('Invia automaticamente nuovi post al tuo canale Telegram!', 'telegram-publisher'); ?>
						<span class="checkmark"></span>
					</label>
				</td>
			</tr>
			
			<tr valign="top">
				<th scope="row"><label for="telegramPublisher_MarkD"><?php esc_html_e('Segna i post aggiornati', 'telegram-publisher'); ?>:</label></th>
				<td>
					<label for="telegramPublisher_MarkD" class="container">
						<input type="checkbox" name="telegramPublisher_MarkD" id="telegramPublisher_MarkD" value="1" <?php checked( get_option('telegramPublisher_MarkD') ); ?>/>
						<?php esc_html_e('Contrassegna i post ripubblicati con "Post Aggiornato Recentemente", questo vale solo se hai inviato il post in precedenza e poi hai aggiunto degli aggiornati manualmente!', 'telegram-publisher'); ?>
					    <span class="checkmark"></span>
					</label>
				</td>
			</tr>
			
			<tr valign="top">
				<th scope="row"><label for="telegramPublisher_messageTemplate"><?php esc_html_e('Costruisci il template del messaggio che verrà pubblicato nel canale o gruppo', 'telegram-publisher'); ?>:</label></th>
				<td>
					<table>
						<tr>
							<td style="padding-top:0; padding-left: 0;">
								<textarea name="telegramPublisher_messageTemplate" id="telegramPublisher_messageTemplate" style="width:450px; height:500px;"><?php echo( get_option('telegramPublisher_messageTemplate') ); ?></textarea>
							</td>
							<td style="padding-top:0;">
								<p style="margin-top:0 !important;"><strong><?php esc_html_e('Variabili supportate!', 'telegram-publisher'); ?></strong></p>
								<p><?php esc_html_e('Le variabili vanno aggiunte una per riga. Separando le varibili con una una riga di spazio, il testo nel messaggio finale verrà formatato con uno spazio tra gli elementi selezionati.', 'telegram-publisher'); ?></p>
								<br />
								<p><strong>{TITLE} =></strong> <i><?php esc_html_e('Titolo del post', 'telegram-publisher'); ?></i></p>
								<p><strong>{FULLURL} =></strong> <i><?php esc_html_e('URL classica', 'telegram-publisher'); ?></i></p>
								<p><strong>{SHORTURL} =></strong> <i><?php esc_html_e('URL accorciata, verrà generato un link struttando la tecnologia WordPress per gli shortlink (wp.me)', 'telegram-publisher'); ?></i></p>
								<p><strong>{EXCERPT} =></strong> <i><?php esc_html_e('Estratto iniziale del post (60 parole)', 'telegram-publisher'); ?></i></p>
								<p><strong>{TAGS} =></strong> <i><?php esc_html_e('Lista degli hashtag generata dai tag inseriti nel box del post', 'telegram-publisher'); ?></i></p>
								<p><strong>{CATEGORIES} =></strong> <i><?php esc_html_e('Lista delle categorie o della categoria di cui fa parte il post', 'telegram-publisher'); ?></i></p>
								<br />
								<p><strong><?php esc_html_e('Supporto di Markdown base', 'telegram-publisher'); ?>:</strong></p>
								<ul>
									<li><i>_<?php esc_html_e('corsivo', 'telegram-publisher'); ?>_</i>&nbsp;<strong>=></strong>&nbsp;Aggiungere un trattino ( _ ) in basso all'inizio e alla fine della prima della variabile</li>
									<li><strong>*<?php esc_html_e('grassetto', 'telegram-publisher'); ?>*</strong>&nbsp;<strong>=></strong>&nbsp;Aggiungere un asterisco ( * ) all'inizio e alla fine della prima della variabile</li>
									<li><?php esc_html_e('`Codice su singola riga`', 'telegram-publisher'); ?>&nbsp;<strong>=></strong>&nbsp;Con singolo accento grave ( ` ) iniziale e singolo accento grave finale si può inserire del testo preformattato su singola riga</li>
									<li><?php esc_html_e('` ` `Codice su più righe;` ` `', 'telegram-publisher'); ?>&nbsp;<strong>=></strong>&nbsp;Nel caso di testo preformattato su più righe, per esempio per la condivisione di codice di programmazione, in inserisce 3 volte l’accento grave ( ``` ) all’inizio e 3 volte alla fine il codice che vogliamo condividere.</li>
								</ul>
								<p><strong>Personalizza il template di messaggi:</strong> <br /> <i>Puoi personalizzare l'aspetto dei tuoi messaggi che verranno pubblicati nel canale o nel gruppo, sfruttando gli emoji. Telegram supporta questa lista di emoji che <a href="https://www.w3schools.com/charsets/ref_emoji.asp" target="_blank">trovi qui.</a></i></p>
								<p><strong>Nota Importante:</strong> <br /> <i>Indipendentemente dallo stile selezionto nella funzione precente, se non viene realizzato un template personalizzato, il plugin genera in automatico un messaggio composto dalle variabili {EXCERPT} {FULLURL} </i>	</p>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			
		</table>
		
		<p class="submit" style="clear: both;">
			<input type="submit" name="Submit" class="btn save" value="<?php esc_html_e('Salva', 'telegram-publisher'); ?>" />
			<input type="hidden" name="telegram-publisher-settings-submit" value="Y" />
		</p>
		
	</form>
	</div>
<?php } ?>
