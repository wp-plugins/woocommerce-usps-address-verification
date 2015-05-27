<?php
/*
Plugin Name: Woocommerce USPS Address Verification API
Plugin URI: http://lb.geek.rs.ba/woocommerce-usps-address-verification-api
Description: Uses USPS to verify and correct shipping addresses after IPN is received from PAYPAL
Author: Boris Smirnoff
Version: 0.0.1
Author URI: http://lb.geek.rs.ba/
*/

// Go ahead and edit stuff if you know what you're doing, if you're unsure or you need something that this plugin cannot do
// Feel free to contact me at contact me smirnoff@geek.rs.ba

//
defined( 'ABSPATH' ) or die( 'Plugin file cannot be accessed directly.' );

// No woocommerce
if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	die();
}

// Plugin not set.
$usps_id = get_option('usps_id');
$notif_addr = get_option('notif_email');

if (empty($usps_id) || $usps_id = "") {
	die();
}

if (empty($notif_addr) || $notif_addr = "") {
	die();
}

//
add_filter( 'woocommerce_verify_usps', 'verify_usps', '10', '1');

add_action( 'valid-paypal-standard-ipn-request', 'verify_usps', 10, 1 );

function verify_usps( $formdata )
{
	ob_start();
	
	// If the user is from US.
    if( $formdata['address_country_code'] == 'US') {
		$usps_incl = plugin_dir_path( __FILE__ ) . 'USPS-php-api-master/USPSAddressVerify.php';

		$usps_present = include $usps_incl;
		// If we can load USPS API
		if ($usps_present == true) {

			// We could get the id easier by exploding $formdata['invoice']. No need for unserialize or string replace. 
			$order_data = unserialize( str_replace('\"', '"', $formdata['custom'] ) );
			$order_id = $order_data[0];
			$order = new WC_Order( $order_id );
			$usps_id = get_option('usps_id');

			$verify = new USPSAddressVerify($usps_id);
			$address = new USPSAddress;

			$address->setApt($order->shipping_address_2);
			$address->setAddress($order->shipping_address_1);
			$address->setCity($order->shipping_city);
			$address->setState($order->shipping_state);
			$address->setZip5($order->postcode);
			$address->setZip4('');

			$verify->addAddress($address);
			$verify_result = $verify->verify();

			//$fp = fopen($log_dest, 'a');
			// Successfully verified address. Update the order and log.
			if($verify->isSuccess()) {
				$street_address_array = $verify->getArrayResponse();

				$corrected_address_streetadd = $street_address_array['AddressValidateResponse']['Address']['Address1'];
				$corrected_address_street    = $street_address_array['AddressValidateResponse']['Address']['Address2'];
				$corrected_address_city      = $street_address_array['AddressValidateResponse']['Address']['City'];
				$corrected_address_state     = $street_address_array['AddressValidateResponse']['Address']['State'];
				$corrected_address_postcode  = $street_address_array['AddressValidateResponse']['Address']['Zip5'];
				// Why not directly ? ^
				$order->shipping_address_1   = $corrected_address_street;
				$order->shipping_address_2   = $corrected_address_streetadd;
				$order->shipping_city        = $corrected_address_city;
				$order->shipping_state       = $corrected_address_state;
				$order->shipping_postcode    = $corrected_address_postcode;

				// This doesn't work. Status must be different.
				$current_status = $order->get_status();
				$order->update_status($current_status, 'Shipping Address Updated by USPS Address Verification API');

				$corrected_address_array     = array('address_1' => $order->shipping_address_1, 'address_2' => $order->shipping_address_2, 'city' => $order->shipping_city, 'state' => $order->shipping_state, 'postcode' => $order->shipping_postcode);

				$order->set_address($corrected_address_array, 'shipping');

				//$msg = "success: " . print_r($corrected_address_array, TRUE);
				//fwrite($fp, $msg);
			} else { // USPS failed to verify the address. Order should be placed on-hold and mail should be sent out to admin.
				$from_addr = get_option('admin_email');

				$headers   = array();
				$headers[] = "MIME-Version: 1.0";
				$headers[] = "Content-type: text/plain; charset=iso-8859-1";
				$headers[] = "From: Address Verification $from_addr";
				$headers[] = "Reply-To: Address Verification $from_addr";
				$headers[] = "Subject: Order on-hold, action needed";
				$headers[] = "X-Mailer: PHP/".phpversion();
				$to = get_option('notif_email');
				$subject = "Order on-hold, action needed";
				$email = "Order $order_id put on hold. Please go to on-hold orders section in admin, and review the address USPS flagged as invalid.";
				mail($to, $subject, $email, implode("\r\n", $headers));

				$order->update_status('on-hold', 'Shipping Address Changed to ON-HOLD by USPS Address Verification System');

			}

		} else {
	
			//$fp = fopen('ipndump-plugin.log', 'a');
			//fwrite($fp, "Not possible to load USPS api on address $usps_incl\r\n");
			//fclose($fp);
		}

	}

}

add_action('admin_menu', 'register_my_custom_submenu_page');
add_action('admin_init', 'usps_options_init' );

function register_my_custom_submenu_page()
{
    add_submenu_page( 'woocommerce', 'USPS Address Verification', 'USPS Address Verification', 'manage_options', 'woocommerce-usps-address-verification', 'settings_callback' ); 

}

function settings_callback()
{
	echo '<h3>USPS Address Verification</h3>'; ?>

	<div class="wrap">
			<h2>Make it work by getting USPS id and Notification email set up. Leave empty to disable it.</h2>
			<form method="POST" action="options.php">
				<?php settings_fields('usps_options_options'); ?>
				<?php $id = get_option('usps_id'); ?>
				<?php $email = get_option('notif_email'); ?>
				<table class="form-table">
					<tr valign="top"><th scope="row">USPS ID</th>
						<td><input name="usps_id" id="usps_id" type="text" value="<?php echo $id ?>" /></td>
					</tr>
					<tr valign="top"><th scope="row">Admin E-Mail</th>
						<td><input type="text" name="notif_email" id="notif_email" value="<?php echo $email; ?>" /></td>
					</tr>
				</table>
				<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save them Changes') ?>" />
				</p>
			</form>
		</div><?php

}


// Init plugin options to white list our options
function usps_options_init(){
	register_setting( 'usps_options_options', 'usps_id' );
	register_setting( 'usps_options_options', 'notif_email' );
}

?>
