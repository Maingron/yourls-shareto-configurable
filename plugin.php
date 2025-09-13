<?php
/*
Plugin Name: Shareto - Configurable
Plugin URI: https://github.com/Maingron/yourls-shareto-configurable
Description: DESCRIPTION
Version: 0.0.1-dev
Author: Maingron
Author URI: https://maingron.com
*/

// No direct call
if( !defined( 'YOURLS_ABSPATH' ) ) die();

yourls_add_action( 'plugins_loaded', 'maingron_shareto_confable_add_settings' );
yourls_add_action( 'share_links', 'maingron_shareto_confable_shareto_init');

foreach(['shareto_test', 'shareto_qr'] as $st_name) {
	$myEnable = yourls_get_option($st_name . '_enable', false);
	if($myEnable) {
		if(function_exists('maingron_shareto_confable_' . $st_name)) {
			yourls_add_action( 'share_links', 'maingron_shareto_confable_' . $st_name);
		}
	}
}

function maingron_shareto_confable_add_settings() {
	yourls_register_plugin_page( 'shareto_confable_settings', 'Share to Settings', 'maingron_shareto_confable_settings_page' );
}

function maingron_shareto_confable_settings_page() {
	// Check if form was submitted
	if( isset( $_POST['shareto_timestamp'] ) ) {
		// If so, verify nonce
		yourls_verify_nonce( 'shareto_confable_settings' );
		// and process submission if nonce is valid
		shareto_confable_settings_update();
	}

	// $random_length = yourls_get_option('random_shorturls_length', 5);
	$nonce = yourls_create_nonce( 'shareto_confable_settings' );
	$shareto_timestamp = time(); // Used to verify if form was submitted. TODO: Check if settings have been changed in the meantime by another user.
	$shareto_disable = yourls_get_option('shareto_disable', false) ? 'checked' : '';
	$shareto_test_enable = yourls_get_option('shareto_test_enable', false) ? 'checked' : '';
	$shareto_test_title = yourls_get_option('shareto_test_title', 'TEST TITLE UNDEFINED');
	$shareto_qr_enable = yourls_get_option('shareto_qr_enable', false) ? 'checked' : '';


	echo <<<HTML
		<main>
			<h2>Share To Configurable - Settings</h2>
			<form method="post">
				<input type="hidden" name="nonce" value="$nonce" />
				<input type="hidden" name="shareto_timestamp" value="$shareto_timestamp" />
				<fieldset>
					<legend>General</legend>
					<label>
						Disable Share feature entirely
						<input type="checkbox" name="shareto_disable" $shareto_disable />
					</label>
				</fieldset>
				<fieldset>
					<legend>Test</legend>
					<label>
						Enable
						<input type="checkbox" name="shareto_test_enable" $shareto_test_enable />
					</label>
					<br>
					<label>
						Title
						<input type="text" name="shareto_test_title" value="$shareto_test_title" />
					</label>
				</fieldset>

				<fieldset>
					<legend>QR Code</legend>
					<label>
						Enable
						<input type="checkbox" name="shareto_qr_enable" $shareto_qr_enable />
					</label>
				</fieldset>
					
				<p><input type="submit" value="Save" class="button" /></p>
			</form>
		</main>
HTML;
}


function shareto_confable_settings_update() {
	$shareto_confable_settings = [
		'shareto_timestamp'   => (int)$_POST['shareto_timestamp'] ?? 0,
		'shareto_disable' => isset($_POST['shareto_disable']),
		'shareto_test_enable' => isset($_POST['shareto_test_enable']),
		'shareto_test_title' => $_POST['shareto_test_title'] ?? 'Test title',

		'shareto_qr_enable' => isset($_POST['shareto_qr_enable']),
	];

	foreach( $shareto_confable_settings as $key => $value ) {
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
	if(!$args['enable']) {
		return;
	}

	$args = array_merge([
		'enable' => false,
		'title' => 'Share to ???',
		'key' => 'maingron_shareto_confable_shareto_undefined',
		'platformLinkTemplate' => '#',
		'icon' => YOURLS_PLUGINURL . '/' . yourls_plugin_basename(__DIR__) . "/img/transparent.png",
		'windowX' => '',
		'windowY' => '',
	], $args);

	echo <<<HTML
	<a id="$args[key]" href="#" title="$args[title]">$args[title]</a>
	<script>
		maingronSharetoConfables.push(
			{
				"enable": "$args[enable]",
				"title": "$args[title]",
				"platformLinkTemplate": "$args[platformLinkTemplate]",
				"key": "$args[key]",
				"icon": "$args[icon]",
				"windowX": "$args[windowX]",
				"windowY": "$args[windowY]",

			}
		);

		$('#tweet_body').keypress(function(){
			let shortUrl = encodeURIComponent( document.querySelector('#copylink').value );
			let longUrl = document.querySelector("#origlink").value;
			maingronSharetoConfables.forEach( (confable) => {
				if(confable['enable']) {
					let parsedPlatformLink = confable['platformLinkTemplate']
						.replace('%shortUrl%', shortUrl)
						.replace('%longUrl%', encodeURIComponent(longUrl))
						.replace('%title%', encodeURIComponent(confable['title']))
						.replace('%windowX%', confable['windowX'] ?? '')
						.replace('%windowY%', confable['windowY'] ?? '');

					let windowOpenString = 'menubar=no,toolbar=no,location=no,';

					if(confable['windowX']) {
						windowOpenString += 'width=' + confable['windowX'] + ',';
					}

					if(confable['windowY']) {
						windowOpenString += 'height=' + confable['windowY'] + ',';
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

function maingron_shareto_confable_shareto_test( $args ) {
	maingron_shareto_confable_shareto_javascript([
		'enable' => yourls_get_option('shareto_test_enable', false),
		'title' => yourls_get_option('shareto_test_title', 'Share to ???'),
		'key' => 'maingron_shareto_confable_shareto_test',
		'platformLinkTemplate' => 'https://maingron.com/?red=%shortUrl%'
	]);
}

function maingron_shareto_confable_shareto_qr($args) {
	maingron_shareto_confable_shareto_javascript([
		'enable' => yourls_get_option('shareto_qr_enable', false),
		'title' => 'QR Code',
		'key' => 'maingron_shareto_confable_shareto_qr',
		'platformLinkTemplate' => 'https://api.qrserver.com/v1/create-qr-code/?data=%shortUrl%&size=%windowX%x%windowY%',
		'windowX' => 300,
		'windowY' => 300,
		'icon' => YOURLS_PLUGINURL . '/' . yourls_plugin_basename(__DIR__) . "/img/qr_code.png"
	]);
}
