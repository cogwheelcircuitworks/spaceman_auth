<?

$spaceman_forget_password_url = 'https://www.nova-labs.org/auth/password.html';
$spaceman_register_url        = 'https://www.nova-labs.org/auth/register.html';
$spaceman_url                 = 'https://www.nova-labs.org/auth/LOGIN';
$spaceman_account_url         = 'https://www.nova-labs.org/account/index.html';

// what we send back they push a button and submit form contents..
$sman_json_to_send = array( 'errors' => 0, 'html' => '');

function spaceman_enqueue_scripts() {
// arrange to have our .js sent to browser 
	wp_enqueue_script( 'jscookie', plugin_dir_url( __FILE__ ) . 'js.cookie.js', array( 'bootstrap','jquery' ),0,true); 
  wp_localize_script('spaceman_auth', 'sman', array( 'nonce' => wp_create_nonce('nonce'),));
	wp_enqueue_script( 'spaceman_auth', plugin_dir_url( __FILE__ ) . 'spaceman.js', array( 'bootstrap','jquery','jscookie'),0,true); 
}

// shortcode [spaceman_auth_login]
function spaceman_auth_login() {
	spaceman_enqueue_scripts();
	spaceman_render_page();
}

function spaceman_add_menu_items($items) { 
	$extra .= '<li  class="menu-item"><a href="/login"> ' .  __('LOGIN') . '</a> </li>';
	$items = $items . $extra;
	return $items;
}



function sman_auth_validate() {

	global $spaceman_forget_password_url;
	global $spaceman_register_url;
	global $spaceman_url;
	global $spaceman_account_url;
	global $sman_json_to_send;	

	// handle ajax form submission from login form
	// http submits come here if we are not logged into wordpress
	$nonce_submit = $_POST['nonce'];  // nonces aid against cross site scripting
	if (  ! wp_verify_nonce( $nonce_submit, 'nonce' ) ) {
		// check to see if the submitted nonce matches with the
		$m = 'wp_verify_nonce(): Epic Fail. Please contact us with this message';
		sman_err($m);
		sman_mesg_send();
	}
	/* Tested from command line:
	curl -X POST -F "username=coggs" -F "password=xxx" -F dest=json "https://www.nova-labs.org/auth/LOGIN"
	{
			"logged_in": "no"
	}
	 */

	// form a RESTful query to spacman

	// header stuff
	global $wp_version;
	$header_args = array(
		'timeout'     => 5,
		'redirection' => 5,
		'httpversion' => '1.0',
		'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),
		'blocking'    => true,
		'headers'     => array(),
		'cookies'     => array(),
		'body'        => null,
		'compress'    => false,
		'decompress'  => true,
		'sslverify'   => true,
		'stream'      => false,
		'filename'    => null
	); 


	// form the query
	$spaceman_query = $spaceman_url . 
		'?dest=' . 'json' .
		'&username=' . $_POST[username] . 
		'&password=' . $_POST[pw] ;

	// ask spaceman the question
	$wp_remote_get_response = wp_remote_get($spaceman_query,$header_args);

	if( is_wp_error( $wp_remote_get_response) ) {
		sman_err("spaceman returned an error");;
		sman_mesg_send();
		return false; // Bail early
	}

	if(! is_array($wp_remote_get_response) ) {
		sman_err("spaceman returned an error");;
		sman_mesg_send();
		return false; // Bail early
	}

	$spaceman_response = json_decode($wp_remote_get_response[body]);
	error_log(var_export($spaceman_response,True));

	if (!array_key_exists('logged_in', $spaceman_response)) {
		error_log("sman_auth_validate(): json object 'logged_in' does not exist");
		sman_err("spaceman returned an error");;
		sman_mesg_send();
		return false; // Bail early
	}

	if ($spaceman_response->logged_in == 'no') {
		error_log("sman_auth_validate(): spaceman username pw incorrect ");
		sman_err("user name or password incorrect");;
		sman_err('Forget your password ?', ' Click <a href="' . $spaceman_forget_password_url . '">HERE</a>');
		sman_err('Not Registered Yet ?', ' Click <a href="' . $spaceman_register_url . '">HERE</a>');
		sman_mesg_send();
		return false; // Bail early
	} else
	if ($spaceman_response->logged_in == 'yes') {
		error_log("sman_auth_validate(): username " . $_POST[username] . " successful");
    sman_mesg('info','You are now logged in');
    sman_mesg('info','You may access your account info <a href="' . $spaceman_account_url . '">HERE</a>');
		// gets sent but doesnt get picked up
		// setcookie('AuthCookie',$spaceman_response->AuthCookie);
	  $sman_json_to_send[AuthCookie] = $spaceman_response->AuthCookie; 
		sman_mesg_send();

		return false; // Bail early
	}

	error_log("sman_auth_validate(): spaceman responded with json, which was not understood:");
	error_log(var_export($wp_remote_get_response[body],True));
	sman_err("spaceman returned an error");;
	sman_mesg_send();
	return false; // Bail early

}


function spaceman_footer_load() {
?>
<!-- spaceman_footer_load() -->
<?
	return; 
}
?>

<?

function sman_err($lead_in, $message) {
	// short-hand version of sman_mesg()
	sman_mesg('important','<b>' . $lead_in . '</b> ' . $message);
}

function sman_mesg($type,$message) {
	// generates and queues a styled messages. Styles by sman_gen_mesg()
	global $sman_json_to_send;
	if ($type == 'important' ||
		$type == 'mybad' ||
		$type == 'error' ||
		0
	) // we use this for error
	{
		$sman_json_to_send[errors] = 1;
	}
	$sman_json_to_send[html] .= sman_gen_mesg($type,$message);
}


function  sman_mesg_send() {
	// transmits all the messages queued in sman_mesg()
	global $sman_json_to_send;
	wp_send_json($sman_json_to_send);
}



function sman_gen_mesg($type,$message,$icon_arg='') {
	/*
	where 'type' is one of the bootstrap label badge classes:
	warning           : orange
	important         : red
	success           : green
	default (nothing) : grey
	info              : blue
	 */

	$icon = 'fa-check-square-o';
	switch ($type) {
	case 'success':
		if ($icon_arg != '') $icon = $icon_arg;
		$t = 'OK ';
		$type = 'success';
		break;

	case 'warning':
		$type = $t = 'warning';
		$icon = 'fa-exclamation-triangle';
		if ($icon_arg != '') $icon = $icon_arg;
		$epl = ""; // exra padding left
		$epr = ""; // exra padding right
		break;

	case 'info':
		$type = $t = 'info';
		$icon = 'fa-comment';
		if ($icon_arg != '') $icon = $icon_arg;
		break;

	case 'error':
	case 'important':
		$type = 'important';
		$t = 'error';
		$icon = 'fa-exclamation-triangle';
		if ($icon_arg != '') $icon = $icon_arg;
		$sman_json_to_send[errors] = 1;
		break;

	case 'mybad':
		$type = 'important';
		$t = 'My Bad';
		$icon = 'fa-frown-o';
		if ($icon_arg != '') $icon = $icon_arg;
		$sman_json_to_send[errors] = 1;
		break;

	case '?':
		$type = 'warning';
		$icon = 'fa-question';
		if ($icon_arg != '') $icon = $icon_arg;
		$epl = "&nbsp"; // exra padding righ
		$epr = "&nbsp"; // exra padding right
		break;


	case 'bolt':
		$type = 'warning';
		$icon = 'fa-bolt';
		if ($icon_arg != '') $icon = $icon_arg;
		$epl = "&nbsp"; // exra padding right
		$epr = "&nbsp"; // exra padding right
		break;

	default:
		$type = 'info';
		$icon = 'fa-info';
		if ($icon_arg != '') $icon = $icon_arg;
		$t = 'FYI..';
	}

	// try to make them roughly same size
	$desired_len = 10;

	$needed_pad = $desired_len - strlen($t);


	$tp .= '&nbsp&nbsp' . $t;

	for($i=0;$i<$needed_pad;$i++)
		$tp .= '&nbsp';

	if (1)
		$text_in_label = '&nbsp&nbsp';
	else
		$text_in_label = '<b>' . $tp .  '</b>';

	// Having them all one color possibly looks better
	$type = 'primary';

	if ($icon == '') {
		$s = '<span class="label label-' .
			$type .  '">&nbsp&nbsp' .
			'<big>' . $alticon . '</big>' .
			$text_in_label .
			'</span> &nbsp ' .
			$message .
			'<br>';

	} else {

		$s = '<span class="label label-' .
			$type .  '">&nbsp&nbsp' .
			$epl . '<i style="line-height:120%" class="fa ' . $icon .  ' fa-lg"></i>' . $epr .
			$text_in_label .
			'</span> &nbsp ' .
			$message .
			'<br> <!-- -->' . "\n";
	}
	//sman_logu('sman_gen_mesg(): ' . $message );
	return $s;
}

function spaceman_render_page() {

	$loader_icon = plugin_dir_url( __FILE__ ) . 'ajax-loader.gif'; 
	$o = <<<SMAN1
<div id="sman_login_div">
			<br>
			<br>
	<form role="form" id="sman_form" class="form-horizontal">
		<div class="form-group">
			<label for="username" class="control-label col-sm-3"><b>USERNAME</b></label>
			<div class="col-sm-5">
				<input class="form-control input" type="username" name="username" id="username" autocomplete="on&quot;" value="" placeholder="">
			</div>
		</div>
		<div class="form-group">
			<label for="pw" class="control-label col-sm-3"><b>PASSWORD</b></label>
			<div class="col-sm-5">
				<input class="form-control" type="password" autocomplete="on" name="pw" id="pw" value="" placeholder=""> 
			</div>
		</div>

		<!--  Save/Cancel Buttons  -->
		<div class="form-group">
			<label for="btn_div" class="control-label col-sm-3"></label>
			<div class="col-sm-3" id="btn_div">
				<button id="sman_login_button" class="btn btn-block btn-primary">
					<i class="fa fa-lock pull-left"></i>login</button>
					<a href="/" class="btn btn-block btn-default"> <i class="fa fa-times pull-left"></i>cancel </a>
			</div>
		</div> <!--form group-->

		<div id="sman_loading_spinner_div" class="hidden">
			<div class="row">
				<div class="col-sm-5"> </div>
				<div class="col-sm-1">
				<img class="center" src="$loader_icon" style="">
				</div>
				<div class="col-sm-6"> </div>
			</div>

		</div>
		<div class="row">
			<div class="col-sm-1"> </div>
			<div id="sman_response_div" class="col-sm-11 hidden">
			</div>
		</div> 
	</form>
</div>

SMAN1;
	echo $o;

}
