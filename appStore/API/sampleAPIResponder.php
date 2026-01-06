<?php

// the purpose of this script is to authenticate a user and return a JSON dataset to them.
// this script expects to receive a username, password, appid and roleid 
// it passes them to single sign-on to see if they authenticate.
// a JSON object is returned. it will carry information about the inputs
// the script received, and the authentication.
// in the block where the user is known to be authenticated, the coder can
// choose what payload of data to send back to the user.


// STEP 1 - BASIC HEADERS

	// session_start(); //usually this is required but there is no need for a session as there is nothing for this script to 'remember'.

	// send basic headers to secure this script
	header("Cache-Control: no-cache");
	header('X-Content-Type-Options: nosniff');  // to avoid IE sniffing (penetration testing 18/12/13)
	header("Expires: -1");


// STEP 2 - START ERROR HANDLING AS SOON AS POSSIBLE. 

	// as i will be building a JSON Object as this script's response, i don't want its creation to be disturbed by the ocurrence of any 
	// catchable PHP error which, if not caught, would output to stdio (the monitor), contrary to the intent that all output should be
	// contained in the returned JSON Object 
	set_error_handler('myHandler');


//STEP 3 - DECLARE AND INITIATE VARIABLES

	// given that this is an API script, intended to be called by a machine, not by a human, it likely has not have been triggered by a browser
	// so don't assume that the $_REQUEST array and $_SERVER array have been created. Check, and create them if necessary.  
	if(!isset($_REQUEST) || !is_array($_REQUEST)){$_REQUEST = array();}
	if(!isset($_SERVER) || !is_array($_SERVER)){$_SERVER = array();}


	//these are internal structural variables
	$debugging = false;				//flag to say whether script should collect and return debugging info or not.
	//$argc							//inbuilt variable -do not declare. holds a count of the number of any command-line arguments php received 		
	//$argv							//inbuilt variable -do not declare. holds an array of containing any command-line arguments php received - conventionally in a name=value "=" separated pair.
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

	//need something which finds the referer - what process/query is calling this? 
	//need to log when its run, so we can block it from running too frequently.


	//create the default (blank) JSONObject will be what this script will send back as its response

	if(!isset($JSONObj)){
		$JSONObj = json_decode('{}');
		$JSONObj->query = array();
		$JSONObj->data = array();
	}

	//create a default (blank) JSON Object to later hold the single sign on API's response
	$ssoResponse = json_decode('{}');
	$ssoResponse-> a = -1;			//has sso authorised the user or not?
	$ssoResponse-> ae = -1;			//has sso said account is expired?
	$ssoResponse-> al = -1;			//has sso said account is locked?
	$ssoResponse-> err = $err;		//error mesage
	$ssoResponse-> lng = "en-GB";	//locale for user
	$ssoResponse-> sr = "";			//server non-error response code if AND
	$ssoResponse-> vu = "";			//username (email) is verified?
	$ssoResponse-> va = -1;			//app id is verified?
	$ssoResponse-> vp = -1;			//password is verified?
	$ssoResponse-> vr = -1;			//role is verified?

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
		$_REQUEST['password'] = $_SERVER['HTTP_PASSWORD'];
	}


// STEP 4 - CHECK THAT THE SCRIPT WAS CALLED OVER HTTPS ELSE CONVERSATION IS NOT SECURE

	//assure that the script has been recevied over HTTPS so that we are secure
	if (!isset($_SERVER) || !is_array($_SERVER) || !isset($_SERVER['HTTPS']) || empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
		$err = "request for data via API must be made via SSL (HTTPS)";
	}


// STEP 5 - VALIDATE ALL ARGUMENTS.  (VALIDATE IS NOT THE SAME AS VERIFY!) BY THIS POINT, ALL ARGUMENTS ARE GATHERED INSIDE THE REQUEST ARRAY

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
		$err = "no password received";
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

	
// STEP 6 - CREATE A PART OF THIS SCRIPT WHICH CAN ONLY BE EXECUTED BY SSO-AUTHENTICATED USERS
	
	// if there are no errors and all my required inputs are valid...
	if($err == "" && $gotValidUsername == 1 && $gotValidPassword == 1 && $gotValidAppID == 1 && $gotValidRoleID == 1 && $mode = "JSON"){	

		// ... then ask single signon to confirm that the user has permission by making an API call to the sso, passing it the  sso username (email), password, app and role id 
		$ssoResponse = ssoAuthenticate($username, $password, $appID, $roleID); //will be 'false' if the request to get an ssoResponse using ssoAuthenticate() fails.
						
		if($ssoResponse){ // only let the script enter this part of the code if the sso response been received 
			$ssoResponse = json_decode($ssoResponse); // the reply from SSO is stored
			/*a typical good ssoResponse is 
			{
				"a": 1, 								// user is authorised  -1 = failed to check, 0 = checked and no, 1 = checked and yes
				"err":"", 
				"lng":"en-GB", 
				"sr":"", 
				"vu": "adam.beirne399@mod.gov.uk",		// verified user id
				"va": 1,                                // verified app id
				"vp": 1,                                // verified password
				"vr": 1                                 // verified role id
			}
			
			where a = authorised. 1 is yes, 0 is no, -1 is 'not checked (probably due to a script error having ocurred, preventing the check from happening');
			err = any error string created by the coder of the sso API, passed from the sso API becasue certain conditions were/were not met.
			lng = for future use, locale setting associated with account. defaults to hardocded english british for now
			sr = 'server response', not used but standard to carry a reply from the server which is not an error.
			u = username (email address) of person who has been authenticated on sso
			*/
			
			if($ssoResponse->a == 1){
				//the 'a' in '$ssoResponse->a' stands for  authorised.

			
				///////////////////////////////////////////////////////////////////////////////////////////////////////////
				//
				//    as we know the user is now approved to access app ID $appID under the role $roleID the coder can 
				//	  collect the data the user needs, and can build a JSON object here to carry it back to via the API.
				//	  the object must be stored in variable $JSONObj
				/*	  for consitency/predictability, the recommended JSON object structure is: 
				{				
					"data":[
						{"name":"val1", "name2":"val2"},
						{"name":"val3", "name2":"val4"}
					]
				}
				where each {squiggly bracket line} below is a mysqli result row. 
				Added in other areas of this script, there will be other parts to the object besides the "data" part illustrated above 
				but the 'payload' should be in "data"
				*/

							//your code here. for example:	

							if($appID == "70" && $roleID == "171"){   					 // if the user is verified as having role 171 in app 70 then...
							
								include "/var/www/html/applications/athena/connects/civilian_echo.php";  // connect to a database
								$conn = $db_link;                                                        // rename here to be consistent. in the past we were not consistent!
								mysqli_set_charset($conn, "utf8mb4"); 									 // this is vital. it forces utf8 encoding on the data.  
																										 // if the source database (Wrongly!) contains non-UTF8 characters, this will prevent 
																										 // JSON objects being built from the data.
								
								$sql1 = 'SELECT `TABLE_NAME` FROM `information_schema`.`TABLES` LIMIT 10';
															
								$result1 = mysqli_query($conn, $sql1);
								if(mysqli_error($conn)){
									trigger_error(mysqli_error($conn));									//note the use of trigger_error to ensure the error is passed to the myHandle error handler, and not lost.
								}else{
									if(mysqli_num_rows($result1) > 0){
										while($row = mysqli_fetch_assoc($result1)){
											$data[] = $row;
										}
										$JSONObj->data = $data;											//put the returned data into the JSON object which will be returned
									}else{
										// no data
									}
								}
							}else{
								//the api request supplied a valid username and password, and the account has the
								//role in the app which the api request declared, but those arent the role and app 
								//required to access this particular data.
								$err = "mismatch between the  and roleID sent by API request, and the appID and roleID which the api responder expected";
							}

				//
				//    
				//	  
				//
				///////////////////////////////////////////////////////////////////////////////////////////////////////////
				
			}else{ 
				//sso check happened but came back saying user was not authorised.
				$err = $ssoResponse-> err;
			}
		}else{
			//single signon said no, so reset to the default ssoResponse JSON Object
			//a default (blank) JSON Object to later hold the single sign on API's response
			$err = "the ssoAuthenticate() function failed to converse with the sso API";
			$ssoResponse = json_decode('{}');
			$ssoResponse-> a = -1;			//has sso authorised the user or not?
			$ssoResponse-> ae = -1;			//has sso said account is expired?
			$ssoResponse-> al = -1;			//has sso said account is locked?
			$ssoResponse-> err = $err;		//error mesage
			$ssoResponse-> lng = "en-GB";	//locale for user
			$ssoResponse-> sr = "";			//server non-error response code if AND
			$ssoResponse-> vu = "";			//username (email) is verified?
			$ssoResponse-> va = -1;			//app id is verified?
			$ssoResponse-> vp = -1;			//password is verified?
			$ssoResponse-> vr = -1;			//role is verified?
		}//end of check that sso approves
	}else{ 
		if($err == ""){
			$err = "one or more mandatory inputs are missing or invalid";
		}
	}//end of check that script arguments are valid


// STEP 7 - PREPARE THE REPLY

	$JSONObj->sr = $sr;											// server response.  any non-error message from the server.
	$JSONObj->err = $err;										// carries any error response intended for the user
	$JSONObj->lng = "en-GB";  									// locale - for future use with international users

	$JSONObj->query['appID'] = $appID;							// 
	$JSONObj->query['appID_DIRTY'] = $appID_DIRTY;				// 
	$JSONObj->query['gotValidAppID'] = $gotValidAppID;			// 
	$JSONObj->query['gotValidPassword'] = $gotValidPassword;	// 
	$JSONObj->query['gotValidRoleID'] = $gotValidRoleID;		// 
	$JSONObj->query['gotValidUsername'] = $gotValidUsername;	// 

	//this block of values are passed back from validation_api
	$JSONObj->query['gotVerifiedAppID'] = $ssoResponse-> va;	// 
	$JSONObj->query['gotVerifiedPassword'] = $ssoResponse-> vp;	// 
	$JSONObj->query['gotVerifiedRoleID'] = $ssoResponse-> vr;	// 
	$JSONObj->query['gotVerifiedUsername'] = $ssoResponse-> vu;	// 
	$JSONObj->query['isExpired'] = $ssoResponse-> ae;			// 
	$JSONObj->query['isLocked'] = $ssoResponse-> al;			// 

	$JSONObj->query['mode'] = $mode;							// 
	$JSONObj->query['password'] = ($password == "" ? "(no pw received)": "(pw received and accepted)");
	$JSONObj->query['password_DIRTY'] = ($password_DIRTY == "" ? "(no pw received)": "(pw received)");
	$JSONObj->query['roleID'] = $roleID;						// 
	$JSONObj->query['roleID_DIRTY'] = $roleID_DIRTY;			// 

	if($debugging == true){
		//put this script's output buffer into the JSON object to pick up any stray output which would traditionally end up on a browser screen
		$commentary = ob_get_clean();
		$commentary = preg_replace("/\n/", "", $commentary);     // 
		$commentary = preg_replace("/\r/", "", $commentary);     // 
		$commentary = preg_replace("/\t/", "", $commentary);     //		
		$JSONObj->c = $commentary;				
	}

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

// STEP 8 - RETURN THE JSON REPLY AND TURN OFF MY ERROR HANDLER

	echo json_encode($JSONObj,JSON_UNESCAPED_SLASHES);
	restore_error_handler(); 


// STEP 9 - END OF SCRIPT. STANDARD FUNCTIONS ARE BELOW


function does_ssl_exist($url){

	//a test to see if asking for https in a given domain url is successful
	$original_parse = parse_url($url, PHP_URL_HOST);

	$get = stream_context_create(array("ssl" => array(
	"capture_peer_cert" => TRUE,
	"verify_peer" => FALSE,	     //fails without this set to false for some reason
    "verify_peer_name" => FALSE  //fails without this set to false for some reason
	)));
	$read = stream_socket_client("ssl://" . $original_parse . ":443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $get);
	$cert = stream_context_get_params($read);
	
	$certinfo = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);
	if (isset($certinfo) && !empty($certinfo)) {
		//echo "<pre>";
		//var_dump($certinfo['name']);
		//var_dump($certinfo['issuer']);
		//echo "</pre>";
		
		if (
			isset($certinfo['name']) && !empty($certinfo['name']) &&
			isset($certinfo['issuer']) && !empty($certinfo['issuer'])
		) {
			return true;
		}
		return false;
	}
	return false;
}


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


function ssoAuthenticate($username, $password, $appID, $roleID){
	//returns JSONObject with info from the SSO server about the account being queried
	//or returns false if the query to the SSO server fails, or can't happen over SSL
	
	//should realy reverify arguments and return false if not verified.
	
	
	$protocol = "https://";				//https:// or http:// to reach the file - in this case the sso verify_api.php file
	$sv = "bravo.dasa.r.mil.uk";			//the server name which sso lives on. 
	$urlOfSSO = $protocol.$sv. ($protocol="https://" ?  ":443":  "") . "/phpSample/validation_api.php?mode=JSON&username=" . $username . "&password=" . $password . "&appID=" . $appID . "&roleID=" . $roleID;

	//should check if urlOfSSO exists and exit to an error if it does not!

	//set baseline CURL options in preparation to curl a response out of the server to a request for its 
	$options = array(
		CURLOPT_RETURNTRANSFER => true,   // return web page
		CURLOPT_HEADER         => false,  // don't return headers
		CURLOPT_FOLLOWLOCATION => true,   // follow redirects
		CURLOPT_MAXREDIRS      => 10,     // stop after 10 redirects
		CURLOPT_ENCODING       => "",     // handle compressed
		CURLOPT_USERAGENT      => $sv, 	  // how the 'client' will identify itself to the SSO server (https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/User-Agent#syntax)
		CURLOPT_AUTOREFERER    => true,   // set referrer on redirect
		CURLOPT_CONNECTTIMEOUT => 120,    // time-out on connect
		CURLOPT_TIMEOUT        => 120,    // time-out on response
	); //this 'options' variable only exists within the scope of its containing anonymous IIFE(Immediately-invoked function expression) so we can safely overlook the fact that its not stored within the namespace.

	if(does_ssl_exist($urlOfSSO) == true){
		$options[CURLOPT_SSLVERSION] = "all";		
		$options[CURLOPT_SSL_VERIFYPEER] = 0;  //using this option causes a failure for some reason
		$options[CURLOPT_SSL_VERIFYHOST] = 2;
		
		//CURLOPT_SSLCERT should point to the client certificate,maybe a .pem file.
		//In Red Hat Enterprise Linux (RHEL), client certificates are typically stored in /etc/pki/tls/certs
		//for the certificates and /etc/pki/tls/private for the private keys, although locations can be configured. 
		$options[CURLOPT_SSLCERT] ="";
	
		//CURLOPT_CAINFO is normally a ca-cert.pem file, typically in /etc/pki/tls/certs/
		$options[CURLOPT_CAINFO] ="/etc/pki/tls/certs/ca-bundle.crt";

		//CURLOPT_SSLKEY is normally a .pem file, typically in /etc/pki/CA/private/
		$options[CURLOPT_SSLKEY] ="/etc/pki/CA/private/echo.pem";
		
		$ch = curl_init($urlOfSSO);
		curl_setopt_array($ch, $options);
		$content  = curl_exec($ch); 
		return $content;
	}else{
		return false; //do nothing as the query to sso can't happen over encrypted SSL 
	}
}


function myHandler($errno, $errstr, $errfile, $errline ){
	//error handler to capture errors in the returned JSON object instead of killing the script by outputting to stdio
	global $JSONObj;
	
	// create the JSON object if it doesn't exist - perhaps the error was encountered before the JSON object was 
	//created.
	if(!isset($JSONObj)){
		$JSONObj = json_decode('{}');
		$JSONObj->query = array();
		$JSONObj->data = array();
	}
	
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