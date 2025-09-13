<?php
/*
Plugin Name: Share to - Configurable
Plugin URI: https://github.com/Maingron/yourls-shareto-configurable
Description: Add configurable sharing options to the share box of YOURLS.
Version: 0.1-dev
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
	foreach(['shareto_qr', 'shareto_linkedin', 'shareto_whatsapp', 'shareto_tumblr', 'shareto_custom1', 'shareto_custom2', 'shareto_custom3', 'shareto_custom4', 'shareto_custom5'] as $st_name) {
		$myEnable = yourls_get_option($st_name . '_enable', false);
		if($myEnable) {
			if(function_exists('maingron_shareto_confable_' . $st_name)) {
				yourls_add_action( 'share_links', 'maingron_shareto_confable_' . $st_name);
			}
		}
	}
}

function maingron_shareto_confable_get_setting( $setting, $fallback, $forceDefault = false ) {
	$result = yourls_get_option( $setting );
	if($result != null && !$forceDefault) {
		return $result ?? $fallback;
	}
	$defaults = [
		'shareto_disable' => false,
		'shareto_qr_enable' => false,
		'shareto_qr_title' => 'QR Code',
		'shareto_qr_platform_link_template' => 'https://api.qrserver.com/v1/create-qr-code/?data=%shortUrl%&size=%window_x%x%window_y%',
		'shareto_qr_icon' => YOURLS_PLUGINURL . '/' . yourls_plugin_basename(__DIR__) . "/img/qr_code.png",
		'shareto_qr_window_x' => 350,
		'shareto_qr_window_y' => 350,
		'shareto_linkedin_enable' => false,
		'shareto_linkedin_title' => 'LinkedIn',
		'shareto_linkedin_platform_link_template' => 'https://www.linkedin.com/sharing/share-offsite/?url=%shortUrl%',
		'shareto_linkedin_icon' => YOURLS_PLUGINURL . '/' . yourls_plugin_basename(__DIR__) . "/img/linkedin.png",
		'shareto_tumblr_enable' => false,
		'shareto_tumblr_title' => 'Tumblr',
		'shareto_tumblr_platform_link_template' => 'https://www.tumblr.com/widgets/share/tool?canonicalUrl=%shortUrl%&title=%title%',
		'shareto_tumblr_icon' => YOURLS_PLUGINURL . '/' . yourls_plugin_basename(__DIR__) . "/img/tumblr.svg",
		'shareto_whatsapp_enable' => false,
		'shareto_whatsapp_title' => 'WhatsApp',
		'shareto_whatsapp_platform_link_template' => 'https://api.whatsapp.com/send?text=%shortUrl%',
		'shareto_whatsapp_icon' => YOURLS_PLUGINURL . '/' . yourls_plugin_basename(__DIR__) . "/img/whatsapp.svg",
	];
	foreach(["custom1", "custom2", "custom3", "custom4", "custom5"] as $customShare) {
		$defaults['shareto_' . $customShare . '_enable'] = false;
		$defaults['shareto_' . $customShare . '_title'] = 'Custom Title';
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
	// yourls_load_custom_textdomain( "maingron_shareto_confable", dirname( __FILE__ ) . '/lang'  );
	yourls_register_plugin_page( 'shareto_confable_settings', 'Share Settings', 'maingron_shareto_confable_settings_page' );
}

function maingron_shareto_confable_settings_page() {
	$maingron_shareto_custom_shares = ['shareto_qr', 'shareto_linkedin', 'shareto_whatsapp', 'shareto_tumblr', 'shareto_custom1', 'shareto_custom2', 'shareto_custom3', 'shareto_custom4', 'shareto_custom5'];

	// Check if form was submitted
	if( isset( $_POST['shareto_timestamp'] ) ) {
		// If so, verify nonce
		yourls_verify_nonce( 'shareto_confable_settings' );
		// and process submission if nonce is valid
		maingron_shareto_confable_settings_update();
	}

	// $random_length = yourls_get_option('random_shorturls_length', 5);
	$nonce = yourls_create_nonce( 'shareto_confable_settings' );
	$shareto_timestamp = time(); // Used to verify if form was submitted. //TODO: Check if settings have been changed in the meantime by another user.
	$shareto_disable = yourls_get_option('shareto_disable', false) ? 'checked' : '';
	$shareto_qr_enable = yourls_get_option('shareto_qr_enable', false) ? 'checked' : '';
	foreach($maingron_shareto_custom_shares as $st_name) {
		${$st_name . '_enable'} = maingron_shareto_confable_get_setting($st_name . '_enable', false) ? 'checked' : '';
		${$st_name . '_title'} = maingron_shareto_confable_get_setting($st_name . '_title', 'Title');
		${$st_name . '_platform_link_template'} = maingron_shareto_confable_get_setting($st_name . '_platform_link_template', YOURLS_SITE . "/%shortUrl%+") ?? '';
		${$st_name . '_icon'} = maingron_shareto_confable_get_setting($st_name . '_icon', YOURLS_PLUGINURL . '/' . yourls_plugin_basename(__DIR__) . "/img/transparent.png") ?? '';
		${$st_name . '_window_x'} = maingron_shareto_confable_get_setting($st_name . '_window_x', '') ?? '';
		${$st_name . '_window_y'} = maingron_shareto_confable_get_setting($st_name . '_window_y', '') ?? ''; 
	}

	echo <<<HTML
		<main>
			<h2>Share To Configurable - Settings</h2>
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
				<input type="hidden" name="nonce" value="$nonce" />
				<input type="hidden" name="shareto_timestamp" value="$shareto_timestamp" />
				<fieldset>
					<legend>General</legend>
					<label>
						Disable Share feature entirely
						<input type="checkbox" name="shareto_disable" $shareto_disable />
					</label>
				</fieldset>

				<br><br>
				<details>
					<summary>Variables</summary>
					<div>
						<p>
							<code>%shortUrl%</code> - The shortened URL<br>
							<code>%longUrl%</code> - The original URL<br>
							<code>%title%</code> - The title of the link (if any)<br>
							<code>%window_x%</code> - Width of the popup window (if any)<br>
							<code>%window_y%</code> - Height of the popup window (if any)<br>
						</p>
					</div>
				</details>
HTML;

	foreach($maingron_shareto_custom_shares as $st_name) {
		echo "<fieldset>";
		echo "<legend>" . str_replace('shareto_', '', $st_name) . "</legend>";
		echo	 "<label>Enable";
		echo 		"<input type='checkbox' name='${st_name}_enable' ${$st_name . '_enable'} />";
		echo 	"</label><br><br>";
		echo	 "<label>Title";
		echo 		"<input type='text' name='${st_name}_title' value='${$st_name . '_title'}' />";
		echo 	"</label><br><br>";
		echo	 "<label>Platform Link Template";
		echo 		"<input type='text' name='${st_name}_platform_link_template' value='${$st_name . '_platform_link_template'}' />";
		echo 	"</label><br><br>";
		echo	 "<label>Icon URL";
		echo 		"<input type='text' name='${st_name}_icon' value='${$st_name . '_icon'}' />";
		echo 	"</label><br><br>";
		echo	 "<label>Popup Window Width (px)";
		echo 		"<input type='number' name='${st_name}_window_x' value='${$st_name . '_window_x'}' />";
		echo 	"</label><br><br>";
		echo	 "<label>Popup Window Height (px)";
		echo 		"<input type='number' name='${st_name}_window_y' value='${$st_name . '_window_y'}' />";
		echo 	"</label>";
		echo "</fieldset><br><br>";
	}

	echo <<<HTML
				<p><input type="submit" value="Save" class="button" /></p>
			</form>
		</main>
HTML;
}


function maingron_shareto_confable_settings_update() {
	$maingron_shareto_custom_shares = ['shareto_qr', 'shareto_linkedin', 'shareto_whatsapp', 'shareto_tumblr', 'shareto_custom1', 'shareto_custom2', 'shareto_custom3', 'shareto_custom4', 'shareto_custom5'];

	$shareto_confable_settings = [
		'shareto_timestamp'   => (int)$_POST['shareto_timestamp'] ?? 0,
		'shareto_disable' => isset($_POST['shareto_disable'])
	];

	foreach($maingron_shareto_custom_shares as $st_name) {
		// $shareto_confable_settings[${$st_name . '_enable'}] = isset($_POST["${$st_name . '_enable'}"]);
		$shareto_confable_settings[$st_name . '_enable'] = isset($_POST[$st_name . '_enable']);
		$shareto_confable_settings[$st_name . '_title'] = $_POST[$st_name . '_title'] ?? 'Title';
		$shareto_confable_settings[$st_name . '_platform_link_template'] = $_POST[$st_name . '_platform_link_template'] ?? YOURLS_SITE . "/%shortUrl%+";
		$shareto_confable_settings[$st_name . '_icon'] = $_POST[$st_name . '_icon'] ?? YOURLS_PLUGINURL . '/' . yourls_plugin_basename(__DIR__) . "/img/transparent.png";
		$shareto_confable_settings[$st_name . '_window_x'] = $_POST[$st_name . '_window_x'] ?? '';
		$shareto_confable_settings[$st_name . '_window_y'] = $_POST[$st_name . '_window_y'] ?? '';
	}

	foreach( $shareto_confable_settings as $key => $value ) {
		if(empty($value) || $value == maingron_shareto_confable_get_setting($key, null, true)) {
			if((strpos($key, '_icon') !== false || strpos($key, '_title') !== false) || strpos($key, '_window_x') !== false || strpos($key, '_window_y') !== false || strpos($key, '_platform_link_template') !== false) {
				yourls_delete_option( $key );
				continue;
			}
		}
		yourls_update_option( $key, $value );
	}
}

function maingron_shareto_confable_shareto_init( $args ) {
	echo <<<HTML
		<script>
			var maingronSharetoConfables = [];
		</script>

		<style>
			#share_links a.maingron_shareto_confable_link {
				backgorund-color: transparent;
				background-image: var(--linkicon);
				background-repeat: no-repeat;
				background-size: contain;
			}
		</style>
HTML;
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

	echo <<<HTML
	<a id="$args[key]" href="#" title="$args[title]">$args[title]</a>
	<script>
		maingronSharetoConfables.push(
			{
				"enable": "$args[enable]",
				"title": "$args[title]",
				"platform_link_template": "$args[platform_link_template]",
				"key": "$args[key]",
				"icon": "$args[icon]",
				"window_x": "$args[window_x]",
				"window_y": "$args[window_y]",
			}
		);

		$('#tweet_body').keypress(function(){
			let shortUrl = encodeURIComponent( document.querySelector('#copylink').value );
			let longUrl = document.querySelector("#origlink").value;
			maingronSharetoConfables.forEach( (confable) => {
				if(confable['enable']) {
					let parsedPlatformLink = confable['platform_link_template']
						.replace('%shortUrl%', shortUrl)
						.replace('%longUrl%', encodeURIComponent(longUrl))
						.replace('%title%', encodeURIComponent(confable['title']))
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
		});
	</script>
HTML;
}

function maingron_shareto_confable_shareto_custom( $shareName, $args ) {
	$myMainKey = 'shareto_' . $shareName;
	maingron_shareto_confable_shareto_javascript([
		'enable' => maingron_shareto_confable_get_setting($myMainKey . '_enable', false),
		'title' => maingron_shareto_confable_get_setting($myMainKey . '_title', 'Share to' . ($shareName ?? '???')),
		'key' => 'maingron_shareto_confable_' . $myMainKey,
		'platform_link_template' => maingron_shareto_confable_get_setting($myMainKey . '_platform_link_template', YOURLS_SITE . "/%shortUrl%+"),
		'icon' => maingron_shareto_confable_get_setting($myMainKey . '_icon', YOURLS_PLUGINURL . '/' . yourls_plugin_basename(__DIR__) . "/img/transparent.png")
	]);
}

function maingron_shareto_confable_shareto_qr($args) {
	maingron_shareto_confable_shareto_javascript([
		'enable' => maingron_shareto_confable_get_setting('shareto_qr_enable', false),
		'title' => maingron_shareto_confable_get_setting('shareto_qr_title', 'QR Code'),
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

function maingron_shareto_confable_shareto_custom5( $args ) {
	maingron_shareto_confable_shareto_custom( "custom5", $args );
}
