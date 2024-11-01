<?php
/*
Plugin Name: TagFor.me
Plugin URI: http://TagFor.me/
Version: v0.5.5
Author: <a href="mailto:arnon@tagfor.me">Arnon</a>
Description: Plugin for a <a href="http://TagFor.me">TagFor.me</a> 
 */

 
// widget
function tagforme_widget() {

		$tagforme_guid = get_option('tagforme_guid');
		// guid exist 
		if(strlen($tagforme_guid )> 0 ){
			$img = '<img style="visibility:hidden;width:0px;height:0px;" border=0 width=0 height=0 src="http://counters.gigya.com/wildfire/IMP/CXNID=2000002.0NXC/bT*xJmx*PTEyNDE*NzQ5MDk5MDYmcHQ9MTI*MTQ3NDkyMjc4MSZwPTYyMTcxMiZkPSZnPTEmdD*mbz*wNzAyNTliZjc4Mjk*ZTA2YTBjODc1OTBjOWI*NTg5YSZvZj*w.gif" />';
			$iframe = '<iframe src="http://stage.TagFor.me/widget/Widget.aspx?id='.$tagforme_guid.'" width=250 height=120 frameborder=0 bordercolor=blue allowtransparency=true scrolling=no></iframe>';
			echo $img.$iframe;
		}
}
// init widget
function init_tagforme(){
    register_sidebar_widget("tagforme", "tagforme_widget");     
}
 // Create admin menu
function mt_add_pages() {
    add_submenu_page('tools.php', 'TagFor.Me', 'TagFor.Me', 8, 'sub-page', 'tagforme_page');
	
}
// connect tagforme ws
function curl($url) {
	//echo '<p>url: '.$url;
	$str  = array(
	"Accept-Language: en-us,en;q=0.5",
	"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7",
	"Keep-Alive: 300",
	"Connection: keep-alive"
	);

	$ch = curl_init() or die(curl_error());
	curl_setopt($ch, CURLOPT_HTTPHEADER, $str);
	curl_setopt($ch, CURLOPT_POST,0);
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$page=curl_exec($ch);
	curl_close($ch); 
	//echo '<p>'.$page;
	return $page ;
}
// parse returned value from XML
function parse_ret_value($response){	
	$res = simplexml_load_string($response);
	//echo '<p>--'.$res;
	return $res;
}
// get status
function get_status($server_url, $tagforme_guid){
	$url = $server_url.'GetStatusWithIcon?AppGuid='.base64_encode($tagforme_guid) ;
	$response = curl($url);
	$line = parse_ret_value($response);
	//echo '<p> status line: '.$line;
	$token = strtok($line, ",");
	$status = base64_decode($token);
	$token = strtok(",");
	$icon = base64_decode($token);
	
	//echo '<p> get_status icon='.$icon.'<p>';
	
	$url = $server_url.'GetIconsList' ;
	$response = curl($url);
	$response = parse_ret_value($response);
	$icons_form = set_icons_form($response );
	
	$status = $status.'|'.$icons_form;
	
		
	return $status ;
}

// build the icons form
function set_icons_form($icons_list)
{
	//echo '<p>'.$icons_list;
	$icons_list = $icons_list.',';
	$i = 0;
	$html  = $html.'<table><tr>';
	
	$token = strtok($icons_list, ",");
	while ($token != false)
	{
		$pos = strpos($token, "=");
		$id = substr($token, 0, $pos);
		$url = substr($token, $pos+1);
		//echo '<p>'.$pos.' - '.$id.':'.$url.'<br/>';
		
		$html  = $html.'<td><input type="radio" name="icons" value="'.$id .'" /></td>';
		$html  = $html.'<td><img src="'.$url.'" width="50" height="50" /></td>';
		$html  = $html.'<td>&nbsp;&nbsp;</td>';
		if($i == 12)
		{
			$html  = $html.'</tr><tr>';
			$i=-1;
		}	
		$i = $i+1;
		$token = strtok(",");
		
	}
	$html  = $html.'<br/></tr></table>';
	//echo '<p>'.$html;

	return $html;
}
// set status
function set_status($server_url, $tagforme_guid, $status, $icon){
	if(strlen($icon) == 0)
		$icon = 0;
	//echo '<p> set_status icon='.$icon.'<p>';
	$url = $server_url.'SetStatus?AppGuid='.base64_encode($tagforme_guid).'&Status='.base64_encode($status).'&IconId='.base64_encode($icon) ;
	$response = curl($url);
	$status = parse_ret_value($response);
	$status = base64_decode($status);
	return $status ;
}
// login
function login($server_url, $user, $password){
	$url = $server_url.'Login?user='.base64_encode($user).'&pass='.base64_encode($password) ;
	$response = curl($url);
	$status = parse_ret_value($response);
	$status = base64_decode($status);
	return $status ;
}
// register
function register($server_url, $user, $password, $email){
	$url = $server_url.'SignUp?user='.base64_encode($user).'&pass='.base64_encode($password).'&email='.base64_encode($email) ;
	$response = curl($url);
	$status = parse_ret_value($response);
	$status = base64_decode($status);
	return $status ;
}

 // Create admin page
function tagforme_page() {
	//$server_url = 'http://tagfor.me/ws/statusservEX.asmx/';
	$server_url = 'http://stage.tagfor.me/ws/statusservex.asmx/';
	$tagforme_guid = get_option('tagforme_guid');
	echo '<div class="wrap">';
	 echo "<h2>" . __( 'TagFor.Me', 'mt_trans_domain' ) . ' <img class="png" height="30" width="30" src="http://tagfor.me/ico.png" alt="TagFor.Me"/></h2><hr />';
	 
	 // check if post back
	 // UpdateStatus
	 if( $_POST[ 'hidden_field_name' ] == 'UpdateStatus' ) {
		// send update status
		set_status($server_url , $tagforme_guid, $_POST[ 'Status' ], $_POST[ 'icons' ]);
	 }
	 // login
	 else if( $_POST[ 'hidden_field_name' ] == 'Login' ) {
		// send login
		$tagforme_guid = login($server_url , $_POST[ 'LoginName' ],  $_POST[ 'Password' ]);
		// update guid
		update_option( 'tagforme_guid', $tagforme_guid );
	 }
	 // Register
	 else if( $_POST[ 'hidden_field_name' ] == 'Register' ) {
		// send register
		$tagforme_guid = register($server_url , $_POST[ 'UserName' ],  $_POST[ 'Password' ],  $_POST[ 'Email' ]);
		// update guid
		update_option( 'tagforme_guid', $tagforme_guid );
	 }
	 //Logout
	 else if( $_POST[ 'hidden_field_name' ] == 'Logout' ) {
		// delete guid
		update_option( 'tagforme_guid', '' );
		$tagforme_guid = '';
	 }
	 // show register form
	 else if( $_POST[ 'hidden_field_name' ] == 'RegisterForm' ) {
		$registration_form = true;
	 ?>
		 Sign Up for Your New Account<p>
		<form name="form1" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
				<input type="hidden" name="hidden_field_name" value="Register">
			<table>
				<tr>	
					<td><?php _e("User Name:", 'mt_trans_domain' ); ?> </td>
					<td><input type="text" name="UserName" size="30"></td>
				</tr>
				<tr>
					<td><?php _e("Password:", 'mt_trans_domain' ); ?> </td>
					<td><input type="password" name="Password" size="30"></td>
				</tr>
				<tr>
					<td><?php _e("E-mail:", 'mt_trans_domain' ); ?> </td>
					<td><input type="text" name="Email" size="30"></td>
				</tr>
				<tr>
					<td>
					<p class="submit">
					<input type="submit" name="Submit" value="<?php _e('Register', 'mt_trans_domain' ) ?>" />
					</td>
				</tr>
			</table>
			</form> 
	<?php		

	 }
	 if( ! $registration_form ==true){
	
		// if guid exist in local db
		if(strlen($tagforme_guid )> 0 ){
			//echo "GUI: ".$tagforme_guid." ".base64_encode($tagforme_guid) ;
			
			// get current status
			$line = get_status($server_url , $tagforme_guid);
			$status  = strtok($line, "|");
			$icons_form = strtok(",");
			echo "Your current Status: <strong><em>".$status."</em></strong>";
			// set status
			?>
			<p>Set Your Status Below and Publish It 

			<form name="form1" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
			<input type="hidden" name="hidden_field_name" value="UpdateStatus">
			<table>
				<tr>
					<td><input type="text" name="Status" size="50"></td>
				</tr>
				<tr><td>
				<?
				echo $icons_form;
				?>
				<tr><td>
				<tr>
					<td>
					<p class="submit">
					<input type="submit" name="Submit" value="<?php _e('Publish', 'mt_trans_domain' ) ?>" /></td>
					</tr>
			</table>
			</form>
			
			<form name="form2" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
				<input type="hidden" name="hidden_field_name" value="Logout">
				<p class="submit">
				<input type="submit" name="Submit" value="<?php _e('Logout', 'mt_trans_domain' ) ?>" />
			</form>
			
			<?php
			
		}
		// guid not exist - show login page
		else{
			?>	
			Please login to TagFor.Me<p>
			<form name="form1" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
			<input type="hidden" name="hidden_field_name" value="Login">
			<table>
				<tr>
					<td><?php _e("LoginName:", 'mt_trans_domain' ); ?>  </td>
					<td><input type="text" name="LoginName" size="30"></td>
				</tr>
				<tr>
					<td><?php _e("Password:", 'mt_trans_domain' ); ?> </td>
					<td><input type="password" name="Password" size="30"></td>
				</tr>
				<tr>
					<td>
					<p class="submit">
					<input type="submit" name="Submit" value="<?php _e('Login', 'mt_trans_domain' ) ?>" /></td>
					</tr>
			</table>
			</form>
			<form name="form2" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
				<input type="hidden" name="hidden_field_name" value="RegisterForm">
				<p class="submit">
				<input type="submit" name="Submit" value="<?php _e('Register', 'mt_trans_domain' ) ?>" />
			</form>

			<?php
		}
	} 
	?>
	<p></p>
	<hr>
<a href="http://TagFor.Me">TagFor.Me</a> is a unique service that allows you to inform the world about the changes  <br/>
 of your personal status.All you need to do, to install our  widget on your blogs or sites,<br/>
 then any change in your personal status via TagFor.Me will automatically be <br/>
 shown on all of your blogs.
	<?php
}
 //load wigdet
add_action("plugins_loaded", "init_tagforme");
// load admin page
add_action('admin_menu', 'mt_add_pages');

?>