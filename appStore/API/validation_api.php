<?php
/************************************************************************
| Project Name:    | Single Sign On password validation for APIs        |
| File Name:       | validation_api.php                                 |
| File Description:| asks single signon to check a username (email      |
|                  | address) and pw to confirm if the user has an sso  |
|                  | role in a given sso-protected app                  |
|                  |                                                    |
|                  |                                                    |
| Version Control: | 1.1                                                |
|***********************************************************************|
| Ver.    Date:   Initials   Description of Change                      |
|***********************************************************************|
| 1.0   08/04/2025   AB      Initial version                            |                                                                 |
|***********************************************************************|
| Php Version:   7.2                                                    |
|***********************************************************************|
| Crown Copyright (c) 2025                                              |
 ***********************************************************************/



//session_start(); //unusually, there is no need for a session as there is nothing for this script to 'remember'.
//send basic headers to secure this script
header("Cache-Control: no-cache");
header('X-Content-Type-Options: nosniff');  // to avoid IE sniffing (penetration testing 18/12/13)
header("Expires: -1");
		
//given that this API-related script likely has not have been triggered by a browser-initiated call, don't assume a $_REQUEST array or $_SERVER has been created 
if(!isset($_REQUEST) || !is_array($_REQUEST)){$_REQUEST = array();}
if(!isset($_SERVER) || !is_array($_SERVER)){$_SERVER = array();}


//declare and initiate variables 
//these are internal structural variables
$debugging = false;				//flag to say whether script should collect and return debugging info or not.
//$argc							//inbuilt variable -do not declare. holds a count of the number of any command-line arguments php received 		
//$argv							//inbuilt variable -do not declare. holds an array of containing any command-line arguments php received - conventionally in a name=value "=" separated pair.
//$_SERVER						//inbuilt variable - do not declare. holds many HTTP header variables. What is contained or not can be inconsistent between PHP/APACHE/LINUX versions
$sr  = "";						//server response.  any non-error message from the server.
$err = "";						//carries any error response intended for the user
$lng = "en-GB";  				//locale - for future use with international users
$c = "";						//carries any commentary from debugging mode from the server

//check that the script which calls this has asked for a JSON response (it's the only one we will give it!)
if(array_key_exists('mode', $_REQUEST) && strtoupper($_REQUEST['mode'] == "JSON")){
	ob_start(); // from this point forward all 'screen' output is buffered and not released until ready to be returned
	$mode = "JSON";
	header("Content-Type: application/json", true);	
}else{
	//no other modes supported for now
	$mode = "";
	$err = "mode must be 'JSON'";
}

	
//to hold the dirty values received by this script
$username_DIRTY = "";			//collects sso username (email address) declared by end user who is looking for authority to do something
$password_DIRTY = "";			//collects password declared by end user who is looking for authority to do something
$appID_DIRTY = "";				//collects sso App ID for the app to which the end user's permissions are being verified 
$roleID_DIRTY = "";				//collects sso role ID for the app role to which the end user's permissions are being verified

//validation flag variables to register whether the dirty values received by this script are valid (that is, conceivably/possibly what they should be)
//-1 = not tested, 0 = no, 1 = yes.
$gotValidUsername = -1;			//does the username (email address) received in username_DIRTY look like an email address?
$gotValidPassword = -1;			//does the password received in password_DIRTY look like a password?
$gotValidAppID = -1;			//does the appID recevied in appID_DIRTY look like an appID?
$gotValidRoleID	= -1;			//does the roleID received in roleID_DIRTY look like a roleID?

//validated then later verified variables
$username = "";	
$password = "";
$appID = "";
$roleID = "";

//veification flag variables whether the username and password are verified against the existing user accounts
//and, optionally (if query is received), whether the verified account has app and role permissions
//-1 = not tested, 0 = no, 1 = yes.
$gotVerifiedUsername = -1;		//is there an sso record for the username (email address) stored in username? 
$gotVerifiedPassword = -1;		//when there is an sso record for the username, does the password match with the password stored in 'password'?
$gotVerifiedAppID = -1;			//when there is a username and password match, does the related account have app-level permissions?
$gotVerifiedRoleID = -1;		//when there is a username and password match, does the related account have role-level permissions?

//flags relating to the user's sso the account
//-1 = not tested, 0 = no, 1 = yes.
$isLocked = -1; 				//is the user account locked out by the administrator?
$isExpired = -1; 				//is the user account 

//main payload of the response. is the user authorised or not authorised?
//-1 = not tested, 0 = no, 1 = yes.
$authorised = -1;				//'1' if account exists and password is good [and optionally app and group (role) permissions meet the criteria specified] 

//the JSONObject will be what this script responds with
$JSONObj =json_decode('{}');

//as i am building a JSONObject as the response, i don't want its creation to be disturbed
//by any catchable PHP error, so capture any php errors (where possible) and store them in the JSON Object to retun them in the response.
set_error_handler('myHandler');

//if the script is coming from a command line call, there may be command line arguments instead of a $_REQUEST array. 
//pull any command line arguments into the $_REQUEST array so that from this point forward, we're on familar turf
//arguments need to be in name=value format to be recognised.
if (php_sapi_name() == "cli"){	
	if(isset($argv) === true && isset($argc) === true && $argc > 0){
		for($i = 0;$i < $argc; $i++){
			if(strpos($argv[$i],'=') === false){
				//skip. this is not a name-value pair 
			}else{
				$_REQUEST[explode("=", $argv[$i])[0]] = explode("=", $argv[$i])[1];
			}
		}
	}
}

//if the script has received the username and password in the HTTP GET header rather than as request / cli arguments, 
//pull the header arguments into the £_REQUEST array so that from this point forward we're on familar turf
if(isset($_SERVER) && array_key_exists('HTTP_USERNAME', $_SERVER)){
	$_REQUEST['username'] = $_SERVER['HTTP_USERNAME'];
}
if(isset($_SERVER) && array_key_exists('HTTP_PASSWORD', $_SERVER)){
	$_REQUEST['password'] = $_SERVER['HTTP_USERNAME'];
}

if (!isset($_SERVER) || !is_array($_SERVER) || !isset($_SERVER['HTTPS']) || empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
    $err = "request for sso authentication in validation_api.php must be made via SSL (HTTPS)";
}


//validate each of the inputs (by now all pooled in the $_REQUEST array) - are all manadtory variables present and correctly formatted?
//check the username
if(isset($_REQUEST) && array_key_exists('username', $_REQUEST)){
	$username_DIRTY  = $_REQUEST['username'];
	
	if(invalidEmail($username_DIRTY)){
		$gotValidUsername = 0;
		$err = "username invalid";
	}else{
		$username = strtolower($username_DIRTY);
		$gotValidUsername = 1;
	}
}else{
	$gotValidUsername = 0;
	$err = "username missing";
}
//check the password
if(isset($_REQUEST) && array_key_exists('password', $_REQUEST)){
	$password_DIRTY = $_REQUEST['password'];
	if($password_DIRTY != ""){
		$password = $password_DIRTY;
		$gotValidPassword = 1;
	}else{
		$gotValidPassword = 0;
		$err = "password doesn't look like a password";
	}
}else{
	$gotValidPassword = 0;
	$err = "no password recived";
}
//check the appID
if(isset($_REQUEST) && array_key_exists('appID', $_REQUEST)){
	$appID_DIRTY = $_REQUEST['appID'];
	if($appID_DIRTY != "" && is_numeric($appID_DIRTY) && $appID_DIRTY > 0){
		$appID = $appID_DIRTY;
		$gotValidAppID = 1;
	}else{
		$gotValidAppID = 0;
		$err = "appID is invalid";
	}
}else{
	$gotValidAppID = 0;
	$err = "appID is missing";	
}
//check the roleID
if(isset($_REQUEST) && array_key_exists('roleID', $_REQUEST)){
	$roleID_DIRTY = $_REQUEST['roleID'];
	if($roleID_DIRTY != "" && is_numeric($roleID_DIRTY) && $roleID_DIRTY > 0){
		$roleID = $roleID_DIRTY;
		$gotValidRoleID = 1;
	}else{
		$gotValidRoleID = 0;
		$err = "roleID is invalid";
	}
}else{
	$gotValidRoleID = 0;  
	$err = "roleID is missing";
}

//if all inputs are good then perform the main function of the script - that is - check that the account exists with app and role permissions
if($err == "" && $gotValidUsername == 1 && $gotValidPassword == 1 && $gotValidAppID == 1 && $gotValidRoleID == 1 && $mode = "JSON"){	
	//ready to try and verify user's inputs against SSO database
	
	include "/var/www/html/applications/single_sign_on/connect.php";

	//check if the account exists at all, whether they have their password right or not.
    $sql1 = "SELECT 1 FROM userControl WHERE LOWER(email) = LOWER('%s') LIMIT 1";
	$sql1 = sprintf($sql1,mysqli_real_escape_string($conn,$username));
	
    $result1 = mysqli_query($conn, $sql1);
	if(mysqli_error($conn)){
		$PHPerr = mysqli_error($conn);
	}else{
		if(mysqli_num_rows($result1) > 0){
			$gotVerifiedUsername = 1;
		}else{
			$gotVerifiedUsername = 0;
		}
	}
	
	if($gotVerifiedUsername == 1){
		//Check if the login details presented match an account, and if so, return the account details.
		$sql2 = "SELECT userID, locked, user_password_expiry_date FROM userControl WHERE LOWER(email) = LOWER('%s') and password=SHA2('%s', 0) LIMIT 1";
		$sql2 = sprintf($sql2,mysqli_real_escape_string($conn,$username),mysqli_real_escape_string($conn,$password));
		
		$result2 = mysqli_query($conn, $sql2);
		if(mysqli_error($conn)){
			$PHPerr = mysqli_error($conn);
		}else{
			if(mysqli_num_rows($result2)> 0){
				$gotVerifiedPassword = 1;
				
				$accountDetails = mysqli_fetch_array($result2);
				
				if(strtotime($accountDetails['user_password_expiry_date']) < strtotime(date('Y-m-d'))){
					$isExpired = 1;	
					$err = "account is expired";
				}else{
					$isExpired = 0;
				}
				
				if(strtotime($accountDetails['locked']) == "1"){
					$isLocked = 1;	
					$err = "admin has locked the account";
				}else{
					$isLocked = 0;
				}
			}else{
				$gotVerifiedPassword = 0;
				$err = "wrong password";
			}
		}
		
		
		//if an app is being checked for permissions, check it
		if($gotVerifiedPassword == 1 && $gotValidAppID == 1){
			$sql3 = "SELECT count(*), t2.appNumbID, t2.roleNumberID FROM userControl as t1
			LEFT JOIN `userRoles` as t2 
			ON t1.userID = t2.userID
			WHERE LOWER(t1.email) =LOWER('%s')
			AND t2.appNumbID = %s
			AND t2.roleNumberID = %s";
			$sql3 = sprintf($sql3,
			mysqli_real_escape_string($conn,$username),
			mysqli_real_escape_string($conn,$appID),
			mysqli_real_escape_string($conn,$roleID)
			);
	
			$result3 = mysqli_query($conn, $sql3);
			if(mysqli_error($conn)){
				$PHPerr = mysqli_error($conn);
			}else{
				$gotVerifiedAppID =  mysqli_fetch_array($result3)[0];
				$gotVerifiedRoleID =  $gotVerifiedAppID;	
			}
		}	
		
		//make a quick note that the user has used their account
		if($gotVerifiedPassword == 1){
			$sql4 = "UPDATE userControl SET user_last_access_date = NOW() WHERE LOWER(email) = LOWER('%s') LIMIT 1";
			$sql4 = sprintf($sql4,mysqli_real_escape_string($conn,$username));
			$result4 = mysqli_query($conn, $sql4);
		}
	
	} // end of 'if account exists then check for app and role permissions


	//isExpired and isLocked are only checked if the username and password match so if they're both 0 (NO) the account its self is valid 
	if($isExpired == 0 && $isLocked == 0 && $gotVerifiedAppID == 1 && $gotVerifiedRoleID == 1 ){
		$authorised = 1;
	}else{
		$authorised = 0;
	}
}else{
	if($err == ""){
		$err = "one or more mandatory inputs are missing or invalid";
	}
}

	
if($debugging == true){
	//put this script's output buffer into the JSON object to pick up stray output

	$JSONObj-> appID = $appID;
	$JSONObj-> appID_DIRTY = $appID_DIRTY;
	$JSONObj-> gotValidAppID = $gotValidAppID;
	$JSONObj-> gotValidPassword = $gotValidPassword;
	$JSONObj-> gotValidRoleID = $gotValidRoleID;
	$JSONObj-> gotValidUsername = $gotValidUsername;
	$JSONObj-> gotVerifiedAppID = $gotVerifiedAppID;
	$JSONObj-> gotVerifiedPassword = $gotVerifiedPassword;
	$JSONObj-> gotVerifiedRoleID = $gotVerifiedRoleID;
	$JSONObj-> gotVerifiedUsername = $gotVerifiedUsername;
	$JSONObj-> isExpired = $isExpired;
	$JSONObj-> isLocked = $isLocked;
	$JSONObj-> mode = $mode;
	$JSONObj-> password = $password;
	$JSONObj-> password_DIRTY = $password_DIRTY;
	$JSONObj-> roleID = $roleID;
	$JSONObj-> roleID_DIRTY = $roleID_DIRTY;
	
	$commentary = ob_get_clean();
	$commentary = preg_replace("/\n/", "", $commentary);
	$commentary = preg_replace("/\r/", "", $commentary);
	$commentary = preg_replace("/\t/", "", $commentary);		
	$JSONObj->c = $commentary;
}

//prepare the standard response
$JSONObj-> a = $authorised;				// has sso authorised the user or not?
$JSONObj-> ae = $isExpired;				// did sso say account has expired?
$JSONObj-> al = $isLocked;				// did sso say account is locked?

$JSONObj-> err = $err;					// error mesage
$JSONObj-> lng = $lng;					// locale for user
$JSONObj-> sr = $sr;					// server non-error response code
$JSONObj-> vu = $username;				// did sso say it has a user of the name (email address) provided?
$JSONObj-> va = $gotVerifiedAppID;		// did sso say the user has access to this app?
$JSONObj-> vp = $gotVerifiedPassword;	// did sso say the user's password was right?
$JSONObj-> vr = $gotVerifiedRoleID;		// did sso say user has the role on this app?

//send the standard response
$tmp = json_encode($JSONObj,JSON_UNESCAPED_SLASHES);  //do a trial run of creating the final JSON object. if it fails, then return something saying so instead
													  //the most likely cause of a failure to encode is if non-utf8 characters have been picked up in the data
													  //this is avoided by using 'mysqli_set_charset($conn, "utf8mb4");' immediately after creating a connection ($conn)
													  //to the data's database.
if(json_last_error_msg() !== "No error"){
	//replace broken JSON Object with a basic one, carrying the error back to the user
	$err = "JSON Error in:" . __FILE__. " contact MOD DoS Digital. ". json_last_error_msg();	
	$JSONObj = json_decode('{}');
	$JSONObj->err = $err;
}
echo json_encode($JSONObj,JSON_UNESCAPED_SLASHES);

restore_error_handler(); 



//below this point are  standard functions used above

function invalidEmail($email){
/* Validate an email address.Provide email address (raw input) Returns false if the email address is valid. returns errormessage 
(which triggers true) if address is invalid. checks address format and checks if domain exists with a mail service running.
does NOT check whether address is in use (in my database) alreday. if that is needed, use check_Email() which does both*/

	$commonEmailDomains = array("HOTMAIL.COM","GMAIL.COM","HOTMAIL.CO.UK","MOD.UK", "MOD.GOV.UK","DIGITAL.MOD.UK"); //the network (internet) based domain name check will be skipped for these domains as they are
	//known to have an email service.

	$retval = false;
	$atIndex = strrpos($email, "@");
	if (is_bool($atIndex) && !$atIndex){
		$retval = 'no_at_symbol';
	}else{
		$domain = substr($email, $atIndex+1);
		$local = substr($email, 0, $atIndex);
		$localLen = mb_strlen($local,'UTF-8');
		$domainLen = mb_strlen($domain,'UTF-8');
		if ($localLen < 1 || $localLen > 64){
			$retval = 'invalid_length_before_AT';// local part length exceeded
		}else if ($domainLen < 1 || $domainLen > 255){
			$retval = 'invalid_length_after_AT'; // domain part length exceeded
		}else if ($local[0] == '.' || $local[$localLen-1] == '.'){
			$retval = 'pre_AT_startsEnds_in_dot'; // local part starts or ends with '.'
		}else if (preg_match('/\\.\\./', $local)){
			$retval = 'consecutive_dots_before_AT';// local part has two consecutive dots
		}else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)){
			$retval = 'invalid_char_in_domain';// character not valid in domain part
		}else if (preg_match('/\\.\\./', $domain)){
			$retval = 'consecutive_dots_in_domain';// domain part has two consecutive dots
		}else if (preg_match('/^[^\.]+(\.[^\.]+){1,2}$/', $domain) != 1){ 
			$retval = 'domain_lacks_dots'; // domain has 2 or 3 words separated by dots. remove this check to allow for me@somewhere style (local domain) emails with no .com /.co.uk etc
		}else if(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',str_replace("\\\\","",$local))){
			// character not valid in local part unless local part is quoted
			if (!preg_match('/^"(\\\\"|[^"])+"$/',str_replace("\\\\","",$local))){
				$retval = 'invalid_char_pre_AT_not_in_quotes';
			}
		}
		
		if ($retval==false && !in_array(strtoupper($domain),$commonEmailDomains) && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))){
			$retval = 'no_mail_service_on_domain'; // domain not found in DNS
		}
	}
	return $retval;
}

function myHandler($errno, $errstr, $errfile, $errline ){
	//error handler to capture errors in the returned JSON object instead of killing the script by outputting to stdio
	global $JSONObj;
	if(is_array($JSONObj->PHPErrs)){
		$arr = $JSONObj->PHPErrs;
	}else{
		$arr = json_decode($JSONObj->PHPErrs);
	}
	$errstr = preg_replace("/\n/", "", $errstr);
	$errstr  = preg_replace("/\r/", "", $errstr);
	$errstr  = preg_replace("/\t/", "", $errstr);
	$arr[] = ['errno' => $errno, 'errstr' =>$errstr, 'errfile' =>$errfile, 'errline' =>$errline];
	$JSONObj->PHPErrs = $arr;
}
?>