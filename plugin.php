<?php
/*
Plugin Name: Share to - Configurable
Plugin URI: https://github.com/Maingron/yourls-shareto-configurable
Description: Add configurable sharing options to the share box of YOURLS.
Version: 1.2.1
Author: Maingron
Author URI: https://maingron.com
*/

// No direct call
if( !defined( 'YOURLS_ABSPATH' ) ) die();

yourls_add_action( 'plugins_loaded', 'maingron_shareto_confable_add_settings' );

if(yourls_get_option('shareto_disable', false)) {
	yourls_add_filter( 'table_add_row_action_array', 'maingron_shareto_disable_row' );
	yourls_add_filter( 'shunt_share_box', 'maingron_shareto_disable_shunt' );
	return;
} else {
	yourls_add_action( 'share_links', 'maingron_shareto_confable_shareto_init');
	foreach(['shareto_qr', 'shareto_email', 'shareto_linkedin', 'shareto_whatsapp', 'shareto_tumblr', 'shareto_custom1', 'shareto_custom2', 'shareto_custom3', 'shareto_custom4'] as $st_name) {
		$myEnable = maingron_shareto_confable_get_setting($st_name . '_enable');
		if($myEnable) {
			if(function_exists('maingron_shareto_confable_' . $st_name)) {
				yourls_add_action( 'share_links', 'maingron_shareto_confable_' . $st_name);
			}
		}
	}
}


function maingron_shareto_confable_get_setting( string $setting, $fallback = null, $forceDefault = false ) {
	$result = yourls_get_option($setting, null);

	if($result !== null && !$forceDefault) {
		return $result ?? $fallback;
	}

	$defaults = [
		'shareto_disable' => false,
		'shareto_email_enable' => true,
		'shareto_email_title' => yourls__('E-Mail', 'maingron_shareto_confable'),
		'shareto_email_platform_link_template' => 'mailto:?subject=%title%&body=%shortUrl%',
		'shareto_email_icon' => YOURLS_PLUGINURL . '/' . yourls_plugin_basename(__DIR__) . "/img/email.png",
		'shareto_qr_enable' => true,
		'shareto_qr_title' => yourls__('QR Code', 'maingron_shareto_confable'),
		'shareto_qr_platform_link_template' => 'https://api.qrserver.com/v1/create-qr-code/?data=%shortUrl%&size=%window_x%x%window_y%',
		'shareto_qr_icon' => YOURLS_PLUGINURL . '/' . yourls_plugin_basename(__DIR__) . "/img/qr_code.png",
		'shareto_qr_window_x' => 350,
		'shareto_qr_window_y' => 350,
		'shareto_linkedin_enable' => false,
		'shareto_linkedin_title' => yourls__('LinkedIn', 'maingron_shareto_confable'),
		'shareto_linkedin_platform_link_template' => 'https://www.linkedin.com/sharing/share-offsite/?url=%shortUrl%',
		'shareto_linkedin_icon' => YOURLS_PLUGINURL . '/' . yourls_plugin_basename(__DIR__) . "/img/linkedin.png",
		'shareto_tumblr_enable' => false,
		'shareto_tumblr_title' => yourls__('Tumblr', 'maingron_shareto_confable'),
		'shareto_tumblr_platform_link_template' => 'https://www.tumblr.com/widgets/share/tool?canonicalUrl=%shortUrl%&title=%title%',
		'shareto_tumblr_icon' => YOURLS_PLUGINURL . '/' . yourls_plugin_basename(__DIR__) . "/img/tumblr.svg",
		'shareto_whatsapp_enable' => false,
		'shareto_whatsapp_title' => yourls__('WhatsApp', 'maingron_shareto_confable'),
		'shareto_whatsapp_platform_link_template' => 'https://api.whatsapp.com/send?text=%shortUrl%',
		'shareto_whatsapp_icon' => YOURLS_PLUGINURL . '/' . yourls_plugin_basename(__DIR__) . "/img/whatsapp.svg",
	];
	foreach(["custom1", "custom2", "custom3", "custom4", "custom5"] as $customShare) {
		$defaults['shareto_' . $customShare . '_enable'] = false;
		$defaults['shareto_' . $customShare . '_title'] = yourls__('Custom Title', 'maingron_shareto_confable');
		$defaults['shareto_' . $customShare . '_platform_link_template'] = YOURLS_SITE . "/%shortUrl%+";
		$defaults['shareto_' . $customShare . '_icon'] = YOURLS_PLUGINURL . '/' . yourls_plugin_basename(__DIR__) . "/img/transparent.png";
		$defaults['shareto_' . $customShare . '_window_x'] = '';
		$defaults['shareto_' . $customShare . '_window_y'] = '';
	}
	if($forceDefault) {
		return $defaults[$setting] ?? null;
	}
	return $defaults[$setting] ?? $fallback ?? null;
}

function maingron_shareto_disable_row($actions) {
	if(isset($actions['share'])) {
		unset($actions['share']);
	}
	return $actions;
}

function maingron_shareto_disable_shunt() {
	return true;
}

function maingron_shareto_confable_add_settings() {
	yourls_load_custom_textdomain( "maingron_shareto_confable", dirname( __FILE__ ) . '/lang' );
	yourls_register_plugin_page( 'shareto_confable_settings', 'Share Settings', 'maingron_shareto_confable_settings_page' );
}

function maingron_shareto_confable_settings_page() {
	$maingron_shareto_custom_shares = [
		'shareto_qr' => yourls__("QR Code", 'maingron_shareto_confable'),
		'shareto_email' => yourls__("E-Mail", 'maingron_shareto_confable'),
		'shareto_linkedin' => yourls__("LinkedIn", 'maingron_shareto_confable'),
		'shareto_whatsapp' => yourls__("WhatsApp", 'maingron_shareto_confable'),
		'shareto_tumblr' => yourls__("Tumblr", 'maingron_shareto_confable'),
		'shareto_custom1' => yourls__("Custom 1", 'maingron_shareto_confable'),
		'shareto_custom2' => yourls__("Custom 2", 'maingron_shareto_confable'),
		'shareto_custom3' => yourls__("Custom 3", 'maingron_shareto_confable'),
		'shareto_custom4' => yourls__("Custom 4", 'maingron_shareto_confable'),
	];

	// Check if form was submitted
	if( isset( $_POST['shareto_timestamp'] ) ) {
		// If so, verify nonce
		yourls_verify_nonce( 'shareto_confable_settings' );
		// and process submission if nonce is valid
		maingron_shareto_confable_settings_update();
	}

	$nonce = yourls_create_nonce( 'shareto_confable_settings' );
	$shareto_timestamp = time(); // Used to verify if form was submitted. //TODO: Check if settings have been changed in the meantime by another user.
	$shareto_disable = maingron_shareto_confable_get_setting('shareto_disable') ? 'checked' : '';
	$shareto_disable_twitter = maingron_shareto_confable_get_setting('shareto_disable_twitter') ? 'checked' : '';
	$shareto_disable_facebook = maingron_shareto_confable_get_setting('shareto_disable_facebook') ? 'checked' : '';

	foreach($maingron_shareto_custom_shares as $st_name => $st_name_human) {
		${$st_name . '_enable'} = maingron_shareto_confable_get_setting($st_name . '_enable') ? 'checked' : '';
		${$st_name . '_title'} = maingron_shareto_confable_get_setting($st_name . '_title', 'Title');
		${$st_name . '_platform_link_template'} = maingron_shareto_confable_get_setting($st_name . '_platform_link_template', YOURLS_SITE . "/%shortUrl%+") ?? '';
		${$st_name . '_icon'} = maingron_shareto_confable_get_setting($st_name . '_icon', YOURLS_PLUGINURL . '/' . yourls_plugin_basename(__DIR__) . "/img/transparent.png") ?? '';
		${$st_name . '_window_x'} = maingron_shareto_confable_get_setting($st_name . '_window_x', '') ?? '';
		${$st_name . '_window_y'} = maingron_shareto_confable_get_setting($st_name . '_window_y', '') ?? '';
	}

	?>

	<main>
		<h2>Share To Configurable - <?php yourls_e('Settings', 'maingron_shareto_confable') ?></h2>
		<form method="post" id="maingron_shareto_confable_settings_form">
			<style>
				#maingron_shareto_confable_settings_form,
				#maingron_shareto_confable_settings_form fieldset,
				#maingron_shareto_confable_settings_form label {
					box-sizing: border-box;
				}
				#maingron_shareto_confable_settings_form label input:not([type="checkbox"]) {
					width: 100%;
					box-sizing: border-box;
					margin-left: 0 !important;
					margin-right: 0 !important;
				}
			</style>
			<input type="hidden" name="nonce" value="<?php echo $nonce; ?>" />
			<input type="hidden" name="shareto_timestamp" value="<?php echo $shareto_timestamp ?>" />
			<fieldset>
				<legend><?php yourls_e('General', 'maingron_shareto_confable'); ?></legend>
				<label>
					<?php yourls_e('Disable Share feature entirely', 'maingron_shareto_confable')?>
					<input type="checkbox" name="shareto_disable" <?php echo $shareto_disable ?> />
				</label>
			</fieldset>

			<fieldset>
				<legend><?php yourls_e('Twitter', 'maingron_shareto_confable'); ?></legend>
				<label>
					<?php yourls_e('Disable', 'maingron_shareto_confable')?>
					<input type="checkbox" name="shareto_disable_twitter" <?php echo $shareto_disable_twitter ?> />
				</label>
			</fieldset>

			<fieldset>
				<legend><?php yourls_e('Facebook', 'maingron_shareto_confable'); ?></legend>
				<label>
					<?php yourls_e('Disable', 'maingron_shareto_confable')?>
					<input type="checkbox" name="shareto_disable_facebook" <?php echo $shareto_disable_facebook ?> />
				</label>
			</fieldset>

			<br><br>
			<details>
				<summary><?php yourls_e('Variables', 'maingron_shareto_confable') ?></summary>
				<div>
					<p>
						<code>%shortUrl%</code> - <?php yourls_e('The shortened URL', 'maingron_shareto_confable') ?><br>
						<code>%longUrl%</code> - <?php yourls_e('The original URL', 'maingron_shareto_confable') ?><br>
						<code>%title%</code> - <?php yourls_e('The title of the link (if any)', 'maingron_shareto_confable') ?><br>
						<code>%message%</code> - <?php yourls_e('Content of the share text box (Same as %title%, but link doesn\'t get removed from the message)', 'maingron_shareto_confable') ?><br>
						<code>%window_x%</code> - <?php yourls_e('Width of the popup window (if any)', 'maingron_shareto_confable') ?><br>
						<code>%window_y%</code> - <?php yourls_e('Height of the popup window (if any)', 'maingron_shareto_confable') ?><br>
					</p>
				</div>
			</details>

		<?php foreach($maingron_shareto_custom_shares as $st_name => $st_name_human): ?>
			<fieldset>
				<legend><?php echo $st_name_human ?></legend>
				<label>
					<?php yourls_e('Enable', 'maingron_shareto_confable') ?>
					<input type='checkbox' name="<?php echo $st_name ?>_enable" <?php echo ${$st_name . '_enable'} ?> />
				</label><br><br>
				<label>
					<?php yourls_e('Title', 'maingron_shareto_confable') ?>
					<input type='text' name="<?php echo $st_name ?>_title" value="<?php echo ${$st_name . '_title'} ?>" />
				</label><br><br>
				<label>
					<?php yourls_e('Platform Link Template', 'maingron_shareto_confable') ?>
					<input type='text' name="<?php echo $st_name ?>_platform_link_template" value="<?php echo ${$st_name . '_platform_link_template'} ?>" />
				</label><br><br>
				<label>
					<?php yourls_e('Icon URL', 'maingron_shareto_confable') ?>
					<input type='text' name="<?php echo $st_name ?>_icon" value="<?php echo ${$st_name . '_icon'} ?>" />
				</label><br><br>
				<label>
					<?php yourls_e('Popup Window Width (px)', 'maingron_shareto_confable') ?>
					<input type='number' name="<?php echo $st_name ?>_window_x" value="<?php echo ${$st_name . '_window_x'} ?>" />
				</label><br><br>
				<label>
					<?php yourls_e('Popup Window Height (px)', 'maingron_shareto_confable') ?>
					<input type='number' name="<?php echo $st_name ?>_window_y" value="<?php echo ${$st_name . '_window_y'} ?>" />
				</label>
			</fieldset><br><br>
		<?php endforeach; ?>
		<p><input type="submit" value="<?php yourls_e('Save', 'maingron_shareto_confable') ?>" class="button" /></p>
		</form>
	</main>
	<?php
}


function maingron_shareto_confable_settings_update() {
	$maingron_shareto_custom_shares = ['shareto_qr', 'shareto_email', 'shareto_linkedin', 'shareto_whatsapp', 'shareto_tumblr', 'shareto_custom1', 'shareto_custom2', 'shareto_custom3', 'shareto_custom4'];

	$shareto_confable_settings = [
		'shareto_timestamp' => (int)$_POST['shareto_timestamp'] ?? 0,
		'shareto_disable' => isset($_POST['shareto_disable']),
		'shareto_disable_twitter' => isset($_POST['shareto_disable_twitter']),
		'shareto_disable_facebook' => isset($_POST['shareto_disable_facebook']),
	];

	foreach($maingron_shareto_custom_shares as $st_name) {
		$shareto_confable_settings[$st_name . '_enable'] = isset($_POST[$st_name . '_enable']);
		$shareto_confable_settings[$st_name . '_title'] = $_POST[$st_name . '_title'] ?? 'Title';
		$shareto_confable_settings[$st_name . '_platform_link_template'] = $_POST[$st_name . '_platform_link_template'] ?? YOURLS_SITE . "/%shortUrl%+";
		$shareto_confable_settings[$st_name . '_icon'] = $_POST[$st_name . '_icon'] ?? YOURLS_PLUGINURL . '/' . yourls_plugin_basename(__DIR__) . "/img/transparent.png";
		$shareto_confable_settings[$st_name . '_window_x'] = $_POST[$st_name . '_window_x'] ?? '';
		$shareto_confable_settings[$st_name . '_window_y'] = $_POST[$st_name . '_window_y'] ?? '';
	}

	foreach( $shareto_confable_settings as $key => $value ) {
		$option_is_in_db = yourls_get_option($key, null) !== null;

		if((empty($value) && $value !== false) || $value == maingron_shareto_confable_get_setting($key, null, true)) {
			// If option is same as default, there's no need to store a value in db
			// Also: If option is cleared, restore default value
			if($option_is_in_db) {
				// No need to delete if not in db anyway
				yourls_delete_option( $key );
			}
			continue;
		}

		if(!$option_is_in_db) {
			// assume option doesn't exist in db yet. We can't reliably update an non-existant option to "false" for some reason
			yourls_add_option($key, $value);
			continue;
		}

		yourls_update_option( $key, $value );
	}
}

function maingron_shareto_confable_shareto_init( $args ) {
	?>

	<script>
		var maingronSharetoConfables = [];

		// Function to initialize/update share buttons
		function initShareButtons() {
			let shortUrl = encodeURIComponent( document.querySelector('#copylink').value );
			let longUrl = document.querySelector("#origlink").value;
			let titleLink = document.querySelector("#titlelink").value;
			let tweetBody = document.querySelector("#tweet_body").value;
			maingronSharetoConfables.forEach((confable) => {
				if(confable['enable']) {
					let parsedPlatformLink = confable['platform_link_template']
						.replace('%shortUrl%', shortUrl)
						.replace('%longUrl%', longUrl)
						.replace('%title%', titleLink)
						.replace('%message%', tweetBody ?? titleLink)
						.replace('%window_x%', confable['window_x'] ?? '')
						.replace('%window_y%', confable['window_y'] ?? '');

					let windowOpenString = 'menubar=no,toolbar=no,location=no,';

					if(confable['window_x']) {
						windowOpenString += 'width=' + confable['window_x'] + ',';
					}

					if(confable['window_y']) {
						windowOpenString += 'height=' + confable['window_y'] + ',';
					}

					$("#" + confable['key'])
						.attr("href", parsedPlatformLink)

					if(!confable['isInitialized']) {
						confable['isInitialized'] = true;
						$("#" + confable['key'])
							.addClass("maingron_shareto_confable_link")
							.attr("style", "--linkicon: url(" + confable['icon'] + ")")
							.attr("target", "_blank")
							.on("click", function() {
								window.open(this.href,'', windowOpenString);
								return false;
							});
					}
				}
			});
		}

		$("#copylink")
		// Keep link in sync in case someone was to edit the link for some bizarre reason lol
			.on("keydown", (e) => {
				if(!e.target.originalValue) {
					e.target.originalValue = e.target.value;
					e.target.initialValue = e.target.value;
				}
			})
			.on("keyup", (e) => {
				if(e.target.value.indexOf("://") === -1) {
					e.target.value = e.target.initialValue;
					event.preventDefault();
					return false;
				}
				let originalValue = e.target.originalValue ?? "";
				let tweetBodyVal = $("#tweet_body").val();
				let newTweetBodyVal = tweetBodyVal.replace(originalValue, e.target.value);
				$("#tweet_body").val(newTweetBodyVal);
				e.target.originalValue = e.target.value;
				$('#tweet_body').trigger("keypress");
			});

		$("#tweet_body")
		// Keep tweet body in sync - people probably want to edit their text
			.on("keydown", (e) => {
				if(!e.target.originalValue) {
					e.target.originalValue = e.target.value;
				}
			})
			.on("keypress", function(e) {
			// Update when tweet body changes
				setTimeout(() => {
					// Not sure why, but YOURLS seems to have this wrong already and works with a delay instead of keyup. Thus we have to do this too, unfortunately.
					initShareButtons();
				}, 100);
			})
			.on("keyup", (e) => {
				let linkVal = $("#copylink").val();
				let myFullVal = e.target.value;
				let myValMinusLink = myFullVal.replace(linkVal, "");
				$("#titlelink").val(myValMinusLink.trim());
				
				if(e.target.value.indexOf($("#copylink").val()) === -1) {
					event.preventDefault();
					e.target.value = e.target.originalValue;
					$("#copylink").focus();
					return false;
				} else {
					e.target.originalValue = e.target.value;
				}
			});
	</script>

	<style>
		#share_links a.maingron_shareto_confable_link {
			background-color: transparent;
			background-image: var(--linkicon);
			background-repeat: no-repeat;
			background-size: contain;
		}
	</style>

	<?php foreach(['twitter' => 'tw', 'facebook' => 'fb'] as $k => $v): ?>
		<?php if(maingron_shareto_confable_get_setting('shareto_disable_' . $k, false )): ?>
			<script>
				$("#share_links #share_<?php echo $v; ?>").remove();
			</script>
		<?php endif; ?>
	<?php endforeach; ?>

	<?php
}

function maingron_shareto_confable_shareto_javascript($args) {
	if(!$args['enable'] || yourls_get_option('shareto_disable', false)) {
		return;
	}

	$args = array_merge([
		'enable' => false,
		'title' => 'Share to ???',
		'key' => 'maingron_shareto_confable_shareto_undefined',
		'platform_link_template' => '#',
		'icon' => YOURLS_PLUGINURL . '/' . yourls_plugin_basename(__DIR__) . "/img/transparent.png",
		'window_x' => '',
		'window_y' => '',
	], $args);

	?>

	<a id="<?php echo $args['key'] ?>" href="#" title="<?php echo $args['title'] ?>">
		<?php echo $args['title'] ?>
	</a>

	<script>
		maingronSharetoConfables.push({
			"enable": true,
			"title": "<?php echo $args['title'] ?>",
			"platform_link_template": "<?php echo $args['platform_link_template'] ?>",
			"key": "<?php echo $args['key'] ?>",
			"icon": "<?php echo $args['icon'] ?>",
			"window_x": "<?php echo $args['window_x'] ?>",
			"window_y": "<?php echo $args['window_y'] ?>",
		});
	</script>
	<?php
}

function maingron_shareto_confable_shareto_custom( $shareName, $args ) {
	$myMainKey = 'shareto_' . $shareName;
	maingron_shareto_confable_shareto_javascript([
		'enable' => true,
		'title' => maingron_shareto_confable_get_setting($myMainKey . '_title', 'Share to' . ($shareName ?? '???')),
		'key' => 'maingron_shareto_confable_' . $myMainKey,
		'platform_link_template' => maingron_shareto_confable_get_setting($myMainKey . '_platform_link_template', YOURLS_SITE . "/%shortUrl%+"),
		'icon' => maingron_shareto_confable_get_setting($myMainKey . '_icon', YOURLS_PLUGINURL . '/' . yourls_plugin_basename(__DIR__) . "/img/transparent.png")
	]);
}

function maingron_shareto_confable_shareto_qr($args) {
	maingron_shareto_confable_shareto_javascript([
		'enable' => true,
		'title' => maingron_shareto_confable_get_setting('shareto_qr_title'),
		'key' => 'maingron_shareto_confable_shareto_qr',
		'platform_link_template' => maingron_shareto_confable_get_setting('shareto_qr_platform_link_template', 'https://api.qrserver.com/v1/create-qr-code/?data=%shortUrl%&size=%window_x%x%window_y%'),
		'window_x' => maingron_shareto_confable_get_setting('shareto_qr_window_x', 300) ?? 300,
		'window_y' => maingron_shareto_confable_get_setting('shareto_qr_window_y', 300) ?? 300,
		'icon' => maingron_shareto_confable_get_setting('shareto_qr_icon', YOURLS_PLUGINURL . '/' . yourls_plugin_basename(__DIR__) . "/img/qr_code.png")
	]);
}

function maingron_shareto_confable_shareto_linkedin( $args ) {
	maingron_shareto_confable_shareto_custom( 'linkedin', $args );
}

function maingron_shareto_confable_shareto_tumblr( $args ) {
	maingron_shareto_confable_shareto_custom( 'tumblr', $args );
}

function maingron_shareto_confable_shareto_whatsapp( $args ) {
	maingron_shareto_confable_shareto_custom( 'whatsapp', $args );
}

function maingron_shareto_confable_shareto_email( $args ) {
	maingron_shareto_confable_shareto_javascript([
		'enable' => true,
		'title' => maingron_shareto_confable_get_setting('shareto_email_title', 'Email'),
		'key' => 'maingron_shareto_confable_shareto_email',
		'platform_link_template' => maingron_shareto_confable_get_setting('shareto_email_platform_link_template', 'mailto:?subject=%title%&body=%shortUrl%'),
		'icon' => maingron_shareto_confable_get_setting('shareto_email_icon', YOURLS_PLUGINURL . '/' . yourls_plugin_basename(__DIR__) . "/img/email.png")
	]);
}

function maingron_shareto_confable_shareto_custom1( $args ) {
	maingron_shareto_confable_shareto_custom( "custom1", $args );
}

function maingron_shareto_confable_shareto_custom2( $args ) {
	maingron_shareto_confable_shareto_custom( "custom2", $args );
}

function maingron_shareto_confable_shareto_custom3( $args ) {
	maingron_shareto_confable_shareto_custom( "custom3", $args );
}

function maingron_shareto_confable_shareto_custom4( $args ) {
	maingron_shareto_confable_shareto_custom( "custom4", $args );
}
