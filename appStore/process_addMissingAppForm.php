<?php
/************************************************************ 
PROJECT NAME:  sample app
FILE NAME   :  process_sampleform.php
PHP VERSION :  8.3.14
template ver:  1.0

FILE DESCRIPTION:
can be called directly via a normal http request/get/post or alternatively as a php include/require or via ajax


Adam's TO DO list

do the other template vars on the other sample app FILES including in the db connection files and therefore 
in here where i refer to those namespaces

the small section with the following code should check templatevar and var but only checks var 
//set a flag (to be used in the following IF ) to say whether any of the variables have variable-specific errors registered against them.

add singlelogin to authenticated() for API access

OUTPUT:
JSON or TEXT depending on [$$nameSpaceID]['templateVar']['outputMode']['val']

VER:   DATE:     INITIALS:  DESCRIPTION OF CHANGE:
1.0    28/09/25  AB         Initial Version

**************************************************************/

//in stream mode this will require the timeout cancellers and 
//something to kill it getmypid(): int|false into a db :

//TEMPLATE	-ENSURE GLOBAL VARIABLES _SERVER and _REQUEST, WHICH THIS TEMPLATE DEPENDS ON, ARE AVAILABLE. $_SERVER for example is normally only available when the script is run
//from a web server not from the CLI but this block of code will create those globals if they are found not to exist and to not be populated with the basic values needed for the
//script to succeed

session_start();

//a true 'global', potentially spanning namespaces if scripts are included in others, used to collect what would traditionally be screen-based output, to be redirected into the JSON object which forms the response when outputMode is set to JSON
if(!isset($commentary)){
	$commentary = array();
}
if(!array_key_exists('buffer',$commentary)){
	$commentary['buffer'] = array();
}




//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//write_to_log("STEP 1 - 'ASSURE \$_SERVER ARRAY IS POPULATED'","start_step", array('lineNumberOverride'=> __LINE__));
$commentary['buffer'][] = array('value'=>"STEP 1 - 'ASSURE \$_SERVER ARRAY IS POPULATED'", 'type'=>'start_step');
	
	if(!isset($_SERVER)){$_SERVER = array();}
	
	//particlarly when running in CLI, PHP won't have _server['document_root'] set, even if _server its self was set. so, if that circumstance is detected, then ask the local // webserver what the value for document_root is. 
	//this method of asking the web server depends on the localhost web server being available to respond to this script through a CURL call which the script makes next, 
	//and on the server being the host to a folder directly off the document_root which is called 'server'.  the folder must contain a copy of getVar.php 
	//getVar.php simply echos $_SERVER[$_REQUEST['var']] (having checked if it can, by establishing if it exists. the server folder contains a htaccess file which restricts access
	//to getVar.php so that only a call from inside the server can successfully run getVar.php. limiting access to getVar.php ensures that the $_SERVER array of vars
	//isn't exposed publicly. 
	if (array_key_exists('DOCUMENT_ROOT',$_SERVER) === false || (array_key_exists('DOCUMENT_ROOT',$_SERVER) === true && $_SERVER['DOCUMENT_ROOT'] === "" )) {
		
		/* we have a catch-22 here (which is resolved) but here's how it exists, and how it is resolved:  the issue is that one of the principles of this script 
		is that all of its variables will be stored inside the script's namespace to prevent clashing. 
		At this early point in the script, the namespace doesnt yet exist so no variables can be associated with it. normally this is no issue. The namespace would first
		be created then variables are subseqeuently created within it. HOWEVER. As the script has reached this point because it does not have a _SERVER['DOCUMENT_ROOT'] 
		global variable (which likely means it is running in CLI mode, but there might be other causes), then in order to get that value so as to create the namespace,
		the script needs to create some variables - but it has no namespace to put them in.  So, to break the deadlock, the code to calculate document_root has been 
		enclosed within an IIFE(Immediately-invoked function expression) which uses an anonymous function to immediately run the code and return the result. this is done
		because that anonymous function CAN contain variables. their scope is limited to within the function so they can not clash with other variables and therefore can 
		safely exist as variables within that function without ever clashing with other variables of the same name elsewhere. */
		
		$_SERVER['DOCUMENT_ROOT'] = (function() {
			
			/* the point of having a namespace is that it avoids variable clashes (eg. if script A calls script B as an include, and both have a variable of the same name).  
			with a namespace, all variable names are named with a convention which sees the variable name prefixed with the namespace id which is unique to each script.
			using this IIFE(Immediately-invoked function expression), which operates with the CURL command, the php script presents its self to the URL provided as an argument
			as if it is a (mozilla) browser asking for a web page. This means that the web server at the URL will respond with the same response as a client would receive.  
	
			Although this function is generically useful to retrieve any web page passed it as a url into a php variable (called 'content' in this script),	it is commonly 
			called when the php script it sits in is run from the command line (CLI) and is passed a URL pointing to localhost. The result is that the webserver at localhost responds with information about the webserver which the php script running in CLI cant access its self eg:$_SERVER['document_root'] to assure no man-in-the-middle, a conversation should reference a localhost SSL certificate.  When used to gain access to sensitive localhost server info, eg. $_SERVER variables, the target url 
			should be secured in a folder that only the local machine can access. This can be achived with a .htaccess file placed in the containing folder with the following content:
				Order Deny,Allow
				Deny from all
				Allow from 127.0.0.1 localhost ::1
			*/
			global $commentary;

			function does_ssl_exist($url){
				
				global $commentary;				
				
				//a test to see if asking for https in a given domain url is successful
				$original_parse = parse_url($url, PHP_URL_HOST);
				$get = stream_context_create(array("ssl" => array("capture_peer_cert" => TRUE)));				
				$read = stream_socket_client("ssl://" . $original_parse . ":443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $get);
				if($read){
					$cert = stream_context_get_params($read);
					$certinfo = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);
					if (isset($certinfo) && !empty($certinfo)) {
						if (
							isset($certinfo['name']) && !empty($certinfo['name']) &&
							isset($certinfo['issuer']) && !empty($certinfo['issuer'])
						) {
							return true;
						}
						return false;
					}
					return false;
				}else{
					return false;
				}
			}

			//set baseline CURL options in preparation to curl a response out of the server to a request for its 
			$options = array(
				CURLOPT_RETURNTRANSFER => true,   // return web page
				CURLOPT_HEADER         => false,  // don't return headers
				CURLOPT_FOLLOWLOCATION => true,   // follow redirects
				CURLOPT_MAXREDIRS      => 10,     // stop after 10 redirects
				CURLOPT_ENCODING       => "",     // handle compressed
				CURLOPT_USERAGENT      => "localhost", // name of client
				CURLOPT_AUTOREFERER    => true,   // set referrer on redirect
				CURLOPT_CONNECTTIMEOUT => 120,    // time-out on connect
				CURLOPT_TIMEOUT        => 120,    // time-out on response
			); //this 'options' variable only exists within the scope of its containing anonymous IIFE(Immediately-invoked function expression) so we can safely overlook the fact that its not stored within the namespace.

			//if (@does_ssl_exist($domain)){
			//if (@does_ssl_exist('localhost')){
			if (@does_ssl_exist("http://localhost/")){
				//echo "SSL is enabled!";
				//extend the CURL options array to include SSL info to make an ssl connection
				$options[CURLOPT_SSLVERSION] = "all";		
				$options[CURLOPT_SSL_VERIFYPEER] = true;
				$options[CURLOPT_SSL_VERIFYHOST] = 0;
				$options[CURLOPT_CAINFO]  = '/etc/ssl/certs/localhost.crt';  //this should ideally not be hard coded but passed as an argument 
				//advice on how to create this should be placed here even if a youtube video but one that actually works!!!
				//all the steps.
				$protocol = "https://";
			}else{
				$protocol = "http://";
			}
			$ch = curl_init($protocol."localhost/server/getVar.php?var="."DOCUMENT_ROOT"); //this 'ch' var only exists within the scope of its containing anonymous IIFE(Immediately-invoked function expression) so we can safely overlook the fact that its not stored within the namespace.
			curl_setopt_array($ch, $options);
			$content  = curl_exec($ch); //this 'content' var only exists within the scope of its containing anonymous IIFE(Immediately-invoked function expression) so we can safely overlook the fact that its not stored within the namespace.
			curl_close($ch);
			
			return $content;
		})(); //end of anonymous self-invoking IIFE(Immediately-invoked function expression)
	} //end of if array_key_exists 'document_root' == false
	
	$commentary['buffer'][] = array('value'=>$_SERVER, 'type'=>'DETAILS');

	
	//now i know server document root, i can include fundamental template-supporting functions.
    include_once( $_SERVER['DOCUMENT_ROOT']. "/klogin/corePHPFunctions.php");


write_to_log("END OF 'ASSURE \$_SERVER ARRAY IS POPULATED'","end_step");	
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
write_to_log("STEP 2 - 'ASSURE \$_REQUEST ARRAY IS POPULATED'","start_step");
	

	if(!isset($_REQUEST)){$_REQUEST = array();}

	//if the script has received the sso username and sso password in the HTTP GET header rather than as request / cli arguments, 
	//pull the header arguments into the £_REQUEST array so that from this point forward we're on familar turf
	if(isset($_SERVER) && array_key_exists('HTTP_USERNAME', $_SERVER)){
		$_REQUEST['ssoUsername'] = $_SERVER['HTTP_USERNAME'];
	}
	if(isset($_SERVER) && array_key_exists('HTTP_PASSWORD', $_SERVER)){
		$_REQUEST['ssoPassword'] = $_SERVER['HTTP_PASSWORD'];
	}
	
	write_to_log($_REQUEST,"DETAILS");


write_to_log("END OF 'ASSURE \$_REQUEST ARRAY IS POPULATED'","end_step");	
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
	
	
	/*
	if this script is ever run in CLI mode (i.e. from the command line / command prompt instead of from a web browser)
	it is running from outside of the web server.  this means that variables which are set by the web server are not 
	available to this script.  This can cause code to act unexpectedly - most commonly, where a developer has referred to $_SERVER['DOCUMENT_ROOT']
	in the path to an include/request or URI/URL to a resource.  This template includes a work-around to that issue. it asks the 
	localhost web server for a web page called /server/getVar.php which returning the content as a PHP variable.  
	that script takes the argument 'var' in its querystring to be a key in the $_SERVER array and returns the value
	of the relevant entry so that the  web server has provided the values this script needs when running outside of the web server. 
	obviously this only works if the /server/getVar.php script is present to reply.

	to ensure this script runs in CLI, as well as from a browser, the coder should ensure that any 
	other $_SERVER array values which their script uses  are checked to be present else are gathered in the same way. 
	these checks would be best placed in the TEMPLATE section directly above this comment



	
	
	To ease adoption of the conventions used here....
	CURL
	IIFE(Immediately-invoked function expression)
	CLI
	Streaming
	JSON
	SPRINTF
	LOCALE
	prepared mysql statements
	
	
	*/

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
write_to_log("STEP 3 - 'COMPUTE A NAMESPACE","start_step");

	//$_SERVER needs to hold DOCUMENT_ROOT first before this can run. 

	/* 
	the purpose of a namespace is that it is a unique ID for each .php file. This allows the coder to apply a convention whereby any variables
	named within that .php file have their name prefixed with the id unique to that script. As a result, if script A.php is included in script B.php
	and both use a variable of the same name, then the prefix of the namespace on each, which will be different on each, keeps the two variables 
	from clashing.

	This convention obliges the coder to compromise a little of their own liberty to code however they like, for the payoff that their code becomes more 
	valuable if it can be re-used/included in scripts written elsewhere or even by themselves at another time, without the need for painful and laborious 
	reworking to assure there are no conflicted variables. The obligation on the coder to apply this convetion is very low so this is not much to ask, 
	but to learn the convention, there are a couple of PHP techniques which the coder will have to use. Because PHP is so flexible, it accommodates many
	different, and equally valid, ways for a coder to solve a problem so coders develop personal styles which might not have left them familiar with the 
	techniques used here in this convention.
	*/



	//if there is already a namespace at this stage it indicates this script has been included/embedded in another
	//so set aside the parent's name for now. the last action of this script will be to hand back to the parent's name if there were one.
	if(isset($nameSpaceID) === true && $nameSpaceID != ""){$prevNameSpaceID = $nameSpaceID;}
	$nameSpaceID =  "NS_" . trim(preg_replace("/[^A-Za-z0-9_]/", '',preg_replace("#[/\\\\\.]+#", "_", substr(realpath(__FILE__),strlen($_SERVER["DOCUMENT_ROOT"])))),"\n\r\t\v\0_");

	if(!isset($$nameSpaceID) || !is_array($$nameSpaceID)){
		$$nameSpaceID = array();
	}
	if(!array_key_exists('templateVar',$$nameSpaceID) || !is_array($$nameSpaceID['templateVar'])){
		$$nameSpaceID['templateVar'] = array();
	}
	if(!array_key_exists('var',$$nameSpaceID) || !is_array($$nameSpaceID['var'])){
		$$nameSpaceID['var'] = array();
	}
		
	
	write_to_log('namespace id: '. $nameSpaceID ,'P');

write_to_log("END OF 'COMPUTE A NAMESPACE","end_step");
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
write_to_log("STEP 4 - 'RETRIEVE ANY CLI ARGUMENTS","start_step"); 

	write_to_log(
	"Merge any command line argument name-value pairs into the request array so they can be handled
	the same as all possible argument feeds - like the traditional \$_REQUEST array.
	this should be done early, before the \$_REQUEST array is referred to which gives scripts a 
	good chance to work from equally from the command line or a browser"
	,"P");
	
	
	if (php_sapi_name() == "cli"){	
		if(isset($argv) === true && isset($argc) === true && $argc > 0){
			for($i = 0;$i < $argc; $i++){
				if(strpos($argv[$i],'=') === false){
					//skip. this is not a name-value pair 
				}else{
					$_REQUEST[explode("=", $argv[$i])[0]] = explode("=", $argv[$i])[1];
					write_to_log("Adding '".explode("=", $argv[$i])[0]."' to \$_REQUEST array","P");
				}
			}
		}
	} // end of if php_sapi_name = cli (ie script is currently running from CLI not browser)

write_to_log("END OF 'RETRIEVE ANY CLI ARGUMENTS","end_step"); 
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
write_to_log("STEP 5 - 'DECLARE CRITICAL TEMPLATE VARIABLES'","start_step"); 
	
	//uses namespace
	
	if(!isset($JSONObj)){
		$JSONObj = json_decode('{}'); //create an empty object which will carry the contents which will 
		$JSONObj->query = array();
		$JSONObj->data = array();
	}

	
	$commentary[]= "nameSpaceID = ". $nameSpaceID;
	
	$$nameSpaceID['templateVar']['debugging'] = array("val"=> true, "DIRTY" => "", "tmpVal" => "", "argType" => -1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => "
	BELONGS TO THE TEMPLATE.  toggle debugging mode. debugging mode outputs 	debug info the developer things are useful for their self and future developers to help them to troubleshoot.  Developers should set this to true when troubleshooting. and, when deveoping,  should wrap any output intended for debugging developers in an 'if' which checks that this value is true. When in debug mode, any redirects should be wrapped in an 'if not debugging' statement to prevent the redirection, leaving debug info displayed");
	
	$$nameSpaceID['templateVar']['outputMode'] = array("val"=> (function(){
		
		global $nameSpaceID;
		global $$nameSpaceID;
		
		
		if($$nameSpaceID['templateVar']['debugging']['val'] === true){
			header("Cache-Control: no-cache");
			header('X-Content-Type-Options: nosniff');  // to avoid IE sniffing (penetration testing 18/12/13)
			header("Expires: -1");
		}
		
		global $JSONObj;
		global $commentary;
		
		if(isset($_REQUEST)=== true && array_key_exists('mode',$_REQUEST) === true){
			
			if($_REQUEST['mode'] === 'JSON'){
				ob_start(); // from this point forward all 'screen' output is buffered and not released until ready to be returned  with JSON object 
				
				header("Content-Type: application/json", true);	
				
				

				
				//seeing as i'm going to be returning an object, ensure it is going to encapsulate all errors warnings notices etc.
				//templateErrorHandler or output cacheing may need to be kicked off eariler if in JSON mode? 
				//because for example, line 1202 can generate an error which is not  json-ified here.
				function templateErrorHandler($errno, $errstr, $errfile, $errline ){
				
		
					global $JSONObj;
					global $commentary;
	
					// create the JSON object if it doesn't exist - perhaps the error was encountered before the JSON object was 
					//created.
					if(!isset($JSONObj)){
						$JSONObj = json_decode('{}');
						$JSONObj->query = array();
						$JSONObj->data = array();
					}
					$JSONObj = convertToArray($JSONObj);
					
					if(array_key_exists('PHPErrs',$JSONObj) && is_array($JSONObj['PHPErrs'])){
						$arr = $JSONObj['PHPErrs'];
					}else{
						$arr = array();
					}
					
					$errstr = preg_replace("/\n/", "", $errstr);
					$errstr  = preg_replace("/\r/", "", $errstr);
					$errstr  = preg_replace("/\t/", "", $errstr);
					$arr[] = ['errno' => $errno, 'errstr' =>$errstr, 'errfile' =>$errfile, 'errline' =>$errline];
					$JSONObj['PHPErrs'] = $arr;
					
					$JSONObj = convertToJSON($JSONObj,__LINE__);
					
					//json_encode($JSONObj,JSON_UNESCAPED_SLASHES);

					
				}	
				
				set_error_handler('templateErrorHandler');
	
				return "JSON";	
			}else{
				return "TEXT";
			}
		}else{
			return "TEXT";
		}
	})(), //end of self-invoking function to set db_conn
	"DIRTY" => "", "tmpVal" => "", "argType" => -1, "mval"=> false, "count" => 0,"varSpecificErrs" => array(), "includeInResponse" => false, "info" => "
	BELONGS TO THE TEMPLATE.  standardized 'process' (server-side) scripts will output a STREAM, a JSON or TEXT (HTML is TEXT). it is assumed at the outset as default that this is TEXT but the arguments received by the script might change that if TEXT isnt the desired output
	");	
	
write_to_log("END OF 'DECLARE CRITICAL TEMPLATE VARIABLES'","end_step");
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
write_to_log("STEP 6 - REGISTER SHUTDOWN HANDLER FUNCTION","start_step");	

	write_to_log('shutdown handler function declaration. this function runs when the script ends even if it ends in error.',"P");
	
	register_shutdown_function('shutdownHandler');//finishes off tidly if script later terminally errors out and ensures the script returns something. 


write_to_log("END OF 'DECLARE CRITICAL TEMPLATE VARIABLES'","end_step");
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
write_to_log("STEP 7 - RESUME DECLARING TEMPLATE VARIABLES","start_step");


	write_to_log("The purpose of these inbuilt template variables is to operate the workings of the template.
	these variables are stored in 'templateVar', a storage area of the namespace dedicated to that purpose.
	they are the internal variables used by the template itself.","P");
	write_to_log("the coder of an app built with the template has their own separate storage area ('var')","P"); 
	
	write_to_log('a description of the mandatory structure of the variables is in 
	<a href="/design/components/php-vars/">the components library</a>',"P"); 
	
	$$nameSpaceID['templateVar']['userLocalTimeZone'] = array("val"=> "Europe/London", "DIRTY" => "", "argType" => -1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => "
	should contain a timezone value listed in https://www.php.net/manual/en/timezones.php
	");
	
write_to_log("STEP 6 - 'RESUME DECLARING TEMPLATE VARIABLES'","end_step");
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
write_to_log("STEP 6.5 (XML IN DEBUG ONLY) - START XML DOCUMENT","start_step");
	

	//take a quick break from declaring template variables to insert the head and basic style into the document
	//at the earliest possible opportunity
	if($$nameSpaceID['templateVar']['debugging']['val'] === true && $$nameSpaceID['templateVar']['outputMode']['val'] === "TEXT"){
		echo '<!DOCTYPE html>
<html lang="en-GB" dir="ltr" class="govuk-template">
	<head>
		<meta charset="utf-8">
		<meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
		<meta content="Default page" name="description">
		<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" >
		<meta name="theme-color" content="#0b0c0c">
		<meta name="robots" content="noindex, nofollow">
		<title>debug mode</title>
		<link rel="icon" href="/favicon.ico" type="image/x-icon" >
	</head>
	<body>
		<style>
			body{font-family: arial;}
			pre{ white-space: pre-wrap;white-space: -moz-pre-wrap;white-space: -pre-wrap;white-space: -o-pre-wrap;word-wrap: break-word;}
			.debugDiv{ overflow-wrap: break-word;margin:1em 1em;padding:1em 0.5em; border:1px solid gray; background-color:WhiteSmoke}
			.debugDiv H4:has(+ .debugTable) {
					margin-block-end:0px;
			}
			.debugDiv H4:has(+ .debugCode) {
					margin-block-end:0px;
			}
			.debugDiv:has(> p.debugRed) {
				border-color: red;
				background-color:#FABDBD}
			.debugDiv:has(> p.debugAmber) {
				border-color: #f57900;
				background-color:#e9b96e}
			.debugDiv:has(> p.debugGreen) {
				border-color: black;
				background-color:#D2EBD0}
			.debugTable, .debugTable thead tr th, .debugTable tbody tr td{
				border:1px solid black;
				border-collapse: collapse;
				background-color:white;
				color:black;
			}
			.debugTable thead tr th{
				background-color:black;
				color:white;
				font-weight:bold;
			}
			
			.debugCode{
				border:1px solid black;
				background-color:white;
				border-style: dashed;
				padding: 0.5em 1em;
				margin-top:0px;
				font-family: "Courier New", Courier, monospace;
			}
		</style>'.PHP_EOL;
	
	
	
		echo '		<pre>'.PHP_EOL;
					trigger_error("you are seeing this output because the variable '\${\$nameSpaceID}['templateVar']['debugging']['val']' in file '".__FILE__."' was manually set to 'true'" , E_USER_WARNING);
		echo '		</pre>'.PHP_EOL;
	}



write_to_log("END OF '(XML IN DEBUG ONLY) - START XML DOCUMENT'","end_step");
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
write_to_log("STEP 7 - RELEASE PRE-DOCUMENT BUFFER","start_step");
	
	write_to_log("now that headers are sent, encourage the release of any apache-buffered user-facing output this should release any debug information buffered by the write_to_log() function prior tothe document headers having been completed.","P");
	
	if($$nameSpaceID['templateVar']['debugging']['val'] == true){
		ob_flush();
		flush(); //consider fastcgi_finish_request() instead
	}
	
	write_to_log("the document contents above this step are likely from the buffer.","P");

		
write_to_log("END OF 'RELEASE PRE-DOCUMENT BUFFER'","end_step");
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
write_to_log("STEP 8 - 'RESUME DECLARING TEMPLATE VARIABLES (2)'","start_step");		
	
	$$nameSpaceID['templateVar']['includeSecurity']  = array("val" => true, "DIRTY" => "", "tmpVal" => "",  "argType" => -1, "mVal"=> false, "count" => 0, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => 
	"BELONGS TO THE TEMPLATE.  switches on (val = true) and off (val = false) elements of the template which embed security controls and restraints offered by klogin app
	");	
	
	$$nameSpaceID['templateVar']['supportedLocales']  = array("val"=> array("en-GB" => array("native" => "English (GB)","en" => "English (GB)"),"zh-Hans" => array("native" => "简体中文", "en" => "Chinese (Simplified)"),"en-Arab" => array("native" => "English (Arab)","en" => "English (Arab)")),"DIRTY" => "", 
	"tmpVal" => "", "argType" => -1, "mVal"=> true, "count" => 3, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => 
	"BELONGS TO THE TEMPLATE.  a list of locales with the associated language presented once its local langauge and again in english (for the developers convenience). This is used
	as a script-level validation of the input variable loc (ie. does the loc value given by the user correspond to a supported Locale listed in supportedLocales and it might also be referenced in the context of user interface scripts for presenting a list of all locales/languages a user might choose to view a page in
	");
	
	$$nameSpaceID['templateVar']['loc']  = array("val"=> "en-GB", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> false, "count" => 0,
	"varSpecificErrs" => array(), "includeInResponse" => false, "info" => 
	"BELONGS TO THE TEMPLATE.  a list of locales with the associated language presented once its local langauge and again in english (for the developers convenience). This is used
	as a script-level validation of the input variable loc (ie. does the loc value given by the user correspond to a supportedLocale and it might also be referenced in the context of user interface scripts for presenting a list of all languages a user might choose to view a page in
	loc is expected to be a IETF BCP 47 standard compliant locale code like en-GB or zh-Hans
	");
	
	//check what locale is contained in the URL, if any
	if(is_array($_REQUEST)&& array_key_exists('loc',$_REQUEST) && array_key_exists($_REQUEST['loc'],$$nameSpaceID['templateVar']['supportedLocales']['val'])){
		$$nameSpaceID['templateVar']['loc']['val'] = $_REQUEST['loc'];
	}

	$$nameSpaceID['templateVar']['STDIN'] 	= array("val"=>  (function () { global $commentary; if(php_sapi_name() == "cli"){stream_set_blocking(STDIN, 0); $fh = fopen('php://stdin', 'r'); $read  = array($fh);  $write = NULL; $except = NULL;if(stream_select( $read, $write,$except, 0 ) === 1 ){return $fh;}else{return false;}}else{return false;}})(),
	"DIRTY" => "", "tmpVal" => "", "argType" => -1, "mval"=> true, "count" => 0, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => "
	val is set by an self-invoking inline function which checks whether the script is running in CLI (in which STDIN stream can exist). if it is running in CLI, it checks for data in the STDIN stream. if there is any it sets val to the handle
    of the stream, else in other cirumstances where there is no data or no STDIN stream or script is not runing in CLI, it sets the val to false. The benefit of using an inline function in this manner is two-fold; firstly the general structure of this
    PHP script is kept uniform, so that inputs whether they be from STDIN, REQUEST or ARGV are all handled similarly, and secondly it means that the code to grab hold of any STDIN handle can make use of the liberty of 
    not worrying about name-spaced variable names, because their scope is limited to the inline function.  The check on STDIN used here takes place without progressing the stream pointer psat the start so that after
    the inline function sets 'val', we know whether STDIN should be processed without having interfered with the actual data stream. 
    ");
	
	$$nameSpaceID['templateVar']['QUARANTINE_DIRTY'] 		= array("val"=> array(), "DIRTY" => "", "tmpVal" => "", "argType" => -1, "mval"=> true, "count" => 0, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => "
	BELONGS TO THE TEMPLATE.  will safely contain any arguments received by this script which are not registered with an argType value of 0 or >0 which would indiciate that they are expected by this script the arguments contained will not be passed back with the return from this script
	");

	$$nameSpaceID['templateVar']['error'] = array("val"=> "", "DIRTY" => "",  "tmpVal" => "", "argType" => -1, "mval"=> false, "count" => -1, "varSpecificErrs" => array(),"includeInResponse" => false, "info" => "
	BELONGS TO THE TEMPLATE.  used as a flag for the script to say that an error has/has not been encountered. in non-complex scripts it might hold the error code/ message with no value (a blank string) indicating that no error has been encountered. Generally don't expect to return the content of this to the user (unless user is a debugging developer)... Generally, instead, each anticipated user-supplied argument will store within its { nameSpaceID.'_var'}['xxxxxx']['varSpecificErrs'] array, a list of error codes intended to be returned to the user where the front-end code will de-code them into an error message in the appropriate human langauge.
	Where an internal code error e.g SQL error has been encountered, it will be written into this variable. 
	");
		
	$$nameSpaceID['templateVar']['fieldValidationsNameSpace'] = array("val"=> "NS_".explode("/",str_replace("\\", "/", substr(realpath(__FILE__),strlen($_SERVER["DOCUMENT_ROOT"])+1)))[0]."_languagePacks_".str_replace("-","",$$nameSpaceID['templateVar']['loc']['val'])."_fieldValidationFunctions_php",
	"DIRTY" => "", "tmpVal" => "", "argType" => -1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => "
	uses __FILE__ to calculate the namespace for the fieldValidationFunctions.php associated with the current locale for this app. fieldValidationFunctions.php is where validation functions for inputs to scripts in this app ought to be stored. this will be refenced later to call the functions, ensuring that the call goes to the function in the correct namespace.
	");
	
	$$nameSpaceID['templateVar']['sr'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "",  "argType" => -1, "mval"=> false, "count" => 0, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => "
	BELONGS TO THE TEMPLATE.  used as a non-error 'good' response code from the server like 'your input was successfully saved' 
	");
	
		
	////// Analysis Dir SSO Variables used for authentication for API responses//////
	$$nameSpaceID['templateVar']['ssoUsername'] 				= array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mval"=> false, "count" => 0, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => "
	BELONGS TO THE TEMPLATE.  collects sso username (email address) declared by end user who is looking for authority to do something' 
	");
	
	$$nameSpaceID['templateVar']['ssoPassword'] 				= array("val"=> "", "DIRTY" => "", "tmpVal" => "","argType" => 0, "mval"=> false, "count" => 0, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => "
	BELONGS TO THE TEMPLATE.  collects password declared by end user who is looking for authority to do something' 
	");
	
	$$nameSpaceID['templateVar']['ssoAppID'] 				= array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mval"=> false, "count" => 0, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => "
	BELONGS TO THE TEMPLATE.  collects sso App ID for the app to which the end user's permissions are being verified ' 
	");
	
	$$nameSpaceID['templateVar']['ssoRoleID'] 				= array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mval"=> false, "count" => 0, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => "
	BELONGS TO THE TEMPLATE.  collects sso role ID for the app role to which the end user's permissions are being verified' 
	");
	
	
	
	
	
	
	
	
	
	
	
	
	//////End of Analysis Dir SSO Variables//////
	

	
	//thought: i could make this a global by not putting it into the namespace but otherwise keep the same structure to the variable.
	$$nameSpaceID['templateVar']['klogin_link'] =  array( "val" => array("db_conn_file" =>  $_SERVER['DOCUMENT_ROOT']."/klogin/klogin_database.php"), "DIRTY" => "", "tmpVal" => "",
	"argType" => -1, "mVal"=> true, "count" => 0, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => 
	"BELONGS TO THE TEMPLATE.  the variable holds a value 'db_conn_file' set by the coder to point to a php file which establishes a connection
	to the single login/sl/klogin/authentication database to make login security privileges and controls available to this script
	so that they can be called upon. the setting of the value db_conn, below, uses an anonymous function which includes (so runs) the php file
	named above, which subsequently sets the other values including db_conn, login_database etc.  db_conn is then the useable reference to the 
	database connection for the rest of the script, and this script has access to all of the connection's parameters but the convention that these
	are set and maintained in a separate file is respected. If the connection to the database is not successful, the authenticated() function cannot
	function to check if a current app user is authenticated (logged in). if the variable \${\$nameSpaceID}['templateVar'}['includeSecurity']['val'] = true 
	the failure of a successful authentication will prevent the coder's app-specific code from being run. if security and authentication isn't needed, ensure
	\${\$nameSpaceID}['templateVar]['includeSecurity']['val'] is set to false");
	
	
		//thought: i could make this a global by not putting it into the namespace but otherwise keep the same structure to the variable.
	$$nameSpaceID['templateVar']['appStore_link'] =  array( "val" => array("db_conn_file" =>  $_SERVER['DOCUMENT_ROOT']."/klogin/appStore_database.php"), "argType" => -1, "mVal"=> true, "count" => 0, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => 
	"BELONGS TO THE TEMPLATE.  the variable holds a value 'db_conn_file' set by the coder to point to a php file which establishes a connection
	to the appStore database to give this script access to the information about the app which it belongs to. 
	the setting of the value db_conn, below, uses an anonymous function which includes (so runs) the php file
	named above, which subsequently sets the other values including db_conn, appStore_database etc.  db_conn is then the useable reference to the 
	database connection for the rest of the script, and this script has access to all of the connection's parameters but the convention that these
	are set and maintained in a separate file is respected. 
	");
	

	write_to_log($$nameSpaceID['templateVar'],"details");

write_to_log("END OF 'RESUME DECLARING TEMPLATE VARIABLES (2)'","end_step");
//////////////////////////////////////////////////////////////////////////////////////////////////////////
write_to_log("STEP 9 - CONNECT TO KLOGIN DATABASE","start_step");

	write_to_log("The Klogin app is a single login system. this part of the template's code will populate the template's \${\$nameSpaceID}['templateVar']['klogin_link'] variable with a link to the login database.","P");
	write_to_log("With the klogin app integrated into their app, a coder can simply use php code  <b>&lt;?php if(authenticated() === \"true\"){ //do secured stuff; }?></b> to secure parts of their code.  <a href=''>more documentation is here</a>'","details");

		
	//if the developer has chosen to secure their script using the klogin system...
	if(array_key_exists('includeSecurity',$$nameSpaceID['templateVar']) === true && array_key_exists('val',$$nameSpaceID['templateVar']['includeSecurity']) === true && $$nameSpaceID['templateVar']['includeSecurity']['val'] === true){


	$$nameSpaceID['templateVar']['klogin_link']['val']['db_conn'] = (function () {
		
		global $nameSpaceID; //make the namespaceid from the main body of the script available within the scope of this function
		global $$nameSpaceID; 
		
		//global $$nameSpaceID['var']; //now i can make available all main body variables (which SHOULD all be stored within the namespace!) 
		//global $$nameSpaceID['templateVar']; //now i can make available all main body variables (which SHOULD all be stored within the namespace!) 
		global $commentary;		
		if(!isset($$nameSpaceID['templateVar']['klogin_link']['val']['db_conn_file'])){
			write_to_log("FAIL!","red");
			write_to_log("no db link file was named. the path to and name of a .php file which creates a connection to a mysql database should be stored in variable 
					\${\$nameSpaceID.\"_temlateVar\"}['klogin_link']['val']['db_conn_file'].  Normally, the value would be '/var/www/html/klogin/klogin_database.php'.
					without that value in the variable, the database for the 'klogin' app can't be connnected to.","red");
		
			return false;
		}else{

			if(!file_exists($$nameSpaceID['templateVar']['klogin_link']['val']['db_conn_file'])){
				write_to_log("FAIL!","red");
				write_to_log("klogin db connection file named in variable ['klogin_link']['val']['db_conn_file'] as ".$$nameSpaceID['templateVar']['klogin_link']['val']['db_conn_file']." doesn't exist. so this script cant retrieve info needed to connect to the klogin database","red");
				return false;
			}else{
				
				$$nameSpaceID['templateVar']['klogin_link']['val']['db_conn_file_nameSpaceID'] = "NS_" . trim(preg_replace("/[^A-Za-z0-9_]/", '',preg_replace("#[/\\\\\.]+#", "_", substr(realpath($$nameSpaceID['templateVar']['klogin_link']['val']['db_conn_file']),strlen($_SERVER["DOCUMENT_ROOT"])))),"\n\r\t\v\0_");
				
				include($$nameSpaceID['templateVar']['klogin_link']['val']['db_conn_file']);
				
				//as this code is inside a function, variables produced here, including those declared in the include
				//inside this function, will not be accessible outside of the function in the main scope. 
				//seeing as I want to refer to at least one variable created in the include, ( login_database) later
				//outside of this current function, copy the include's namespace into the global namespace.
				$$nameSpaceID['templateVar']['klogin_link']['val']['db_conn_file_nameSpace'] =  ${$$nameSpaceID['templateVar']['klogin_link']['val']['db_conn_file_nameSpaceID']}['templateVar'];
				

				if(isset( $$nameSpaceID['templateVar']['klogin_link']['val']['db_conn_file_nameSpace']['app_link_err']['val'])=== true){
					//to look inside the namespace of the klogin app's database connection file, use
					//$$nameSpaceID['templateVar']['klogin_link']['val']['db_conn_file_nameSpace']
					//so if that file contains a variable which it refers to as $$nameSpaceID['var']['app_database']
					//then this script will know it as $$nameSpaceID['templateVar']['klogin_link']['val']['db_conn_file_nameSpace']['app_database']

					$$nameSpaceID['templateVar']['error']['val'] = $$nameSpaceID['templateVar']['klogin_link']['val']['db_conn_file_nameSpace']['app_link_err']['val'] ?? "unknown db connection error";
					write_to_log("FAIL!","red");
					write_to_log($$nameSpaceID['templateVar']['error']['val'],"red");
					return false;
				}else{
					write_to_log("SUCCESS!","green");
					return ${$$nameSpaceID['templateVar']['klogin_link']['val']['db_conn_file_nameSpaceID']}['templateVar']['app_link']['val'];	

				} //end of if mysqli connect error returned
			}//end of !file_exists(db_conn_file)
		}// end of !isset(db_conn_file)
	})(); //end of self-invoking function to set klogin db_conn
	
		
	}else{//coder has opted out of using klogin to secure their script by setting $$nameSpaceID['var']['includeSecurity']['val'] to false
		write_to_log("connecting to login database (klogin) was bypassed as the template's 'includeSecurity' variable is set to false meaning no klogin user authentication needed","P");
	}
		
write_to_log("END OF 'CONNECT TO KLOGIN DATABASE' STEP","end_step");
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
write_to_log("STEP 10 - CONNECT TO APPSTORE DATABASE","start_step");
		
	$$nameSpaceID['templateVar']['appStore_link']['val']['db_conn'] = (function () {
		
		global $nameSpaceID; //make the namespaceid from the main body of the script available within the scope of this function
		global $$nameSpaceID; 
		
		//global $$nameSpaceID['var']; //now i can make available all main body variables (which SHOULD all be stored within the namespace!) 
		//global $$nameSpaceID['templateVar']; //now i can make available all main body variables (which SHOULD all be stored within the namespace!) 
		global $commentary;
		
		if(!isset($$nameSpaceID['templateVar']['appStore_link']['val']['db_conn_file'])){
			write_to_log("FAIL!","red");
			write_to_log("no db link file was named. the path to and name of a .php file which creates a connection to a mysql database should be stored in variable 
					\${\$nameSpaceID.\"_temlateVar\"}['appStore_link']['val']['db_conn_file'].  Normally, the value would be '/var/www/html/klogin/appStore_database.php'.
					without that value in the variable, the database for the 'appStore' app can't be connnected to.","red");
			
			return false;
		}else{

			if(!file_exists($$nameSpaceID['templateVar']['appStore_link']['val']['db_conn_file'])){
				write_to_log("FAIL!","red");
				write_to_log("app store db connection file named in variable ['appStore_link']['val']['db_conn_file'] as ".$$nameSpaceID['templateVar']['appStore_link']['val']['db_conn_file']." doesn't exist. so this script cant retrieve info needed to connect to the appStore database","red");
				return false;
			}else{
				
				$$nameSpaceID['templateVar']['appStore_link']['val']['db_conn_file_nameSpaceID'] = "NS_" . trim(preg_replace("/[^A-Za-z0-9_]/", '',preg_replace("#[/\\\\\.]+#", "_", substr(realpath($$nameSpaceID['templateVar']['appStore_link']['val']['db_conn_file']),strlen($_SERVER["DOCUMENT_ROOT"])))),"\n\r\t\v\0_");				
				
				include($$nameSpaceID['templateVar']['appStore_link']['val']['db_conn_file']);

				//as this code is inside a function, variables produced here, including those declared in the include
				//inside this function, will not be accessible outside of the function in the main scope. 
				//seeing as I want to refer to at least one variable created in the include, ( login_database) later
				//outside of this current function, copy the include's namespace into the global namespace.
				$$nameSpaceID['templateVar']['appStore_link']['val']['db_conn_file_nameSpace'] =  ${$$nameSpaceID['templateVar']['appStore_link']['val']['db_conn_file_nameSpaceID']}['templateVar'];

			
				if(isset($$nameSpaceID['templateVar']['appStore_link']['val']['db_conn_file_nameSpace']['app_link_err']['val'])=== true){
					//the following needs to be adjusted to use this layout and then i can pass back the vars into this namespace.

					$$nameSpaceID['templateVar']['error']['val'] = $$nameSpaceID['templateVar']['appStore_link']['val']['db_conn_file_nameSpace']['app_link_err']['val'] ?? "unknown db connection error";
					write_to_log("FAIL!","red");
					write_to_log($$nameSpaceID['templateVar']['error']['val'],"red");
					return false;
				
				}else{
					write_to_log("SUCCESS!","green");
	
					//look in the namespace created for the file which holds the db connection details for the appStore for what it calls its app_link (the app it THAT files context being the app store)
					//return it here so that it ends up in THIS current scripts namespace as 'appStore_link'
					return ${$$nameSpaceID['templateVar']['appStore_link']['val']['db_conn_file_nameSpaceID']}['templateVar']['app_link']['val'];
										
				} //end of if mysqli connect error returned
			}//end of !file_exists(db_conn_file)
		}// end of !isset(db_conn_file)
	})(); //end of self-invoking function to link to appStore database
	
write_to_log("END OF 'CONNECT TO APPSTORE DATABASE'","end_step");
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
write_to_log("STEP 12 - RESUME DECLARING TEMPLATE VARIABLES (3)","start_step");

	$$nameSpaceID['templateVar']['appInfo'] = array("val"=> array(
		"appRoot"=> explode("/",str_replace("\\", "/", substr(realpath(__FILE__),strlen($_SERVER["DOCUMENT_ROOT"])+1)))[0]
	), "DIRTY" => "", "tmpVal" => "", "argType" => -1, "mVal"=> true, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => "
	the appInfo is the mysql row for this app from the appStore.
	the row should have been added when this app was registered as a new app in the appStore. without this, this rogue app 
	may not function fully. The record's appRoot must exactly match the folder name which this app lives in as a direct child of 
	server document_root.");
	

	$$nameSpaceID['templateVar']['sql'] 			= array("val"=>"", "query"=> "", "stmt"=>"", "result"=> "", "row"=>"", "rows"=>"", "rowCount"=>"", "DIRTY" => "", "tmpVal" => "", "argType" => -1, "mVal"=> false, "count" => 0, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => "
	holds an sql query as it will be presented to MYSQL to be executed, and all pre-completed versions of the query as it is perhaps constructed. the same variable
	will be re-used if there are multiple sql queries run by the script so that all queries can be quickly found by the coder by searching for S{SnameSpaceID}['templateVar'}['sql']
	");
	
	
	$$nameSpaceID['templateVar']['rkey'] 				= array("val"=> "", "DIRTY" => "", "tmpVal" => "",  "argType" => -1, "mval"=> false, "count" => 0, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => "
	array key an etheral variable which holds a single key (name) from any array as the array is iterated through. the named key equivalent of the traditional and ubiquitous 'i' 
	variable for arrays keyed with integers");
		
	$$nameSpaceID['templateVar']['vKey'] 				= array("val"=> "", "DIRTY" => "", "tmpVal" => "",  "argType" => -1, "mval"=> false, "count" => 0, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => "
	array key an etheral variable which holds a single key (name) from any array as the array is iterated through. the named key equivalent of the traditional and ubiquitous 'i' 
	variable for arrays keyed with integers");

	$$nameSpaceID['templateVar']['app_link'] = array("val" => array(
	"db_conn_file" =>  $_SERVER['DOCUMENT_ROOT']."/klogin/".explode("/",str_replace("\\", "/", substr(realpath(__FILE__),strlen($_SERVER["DOCUMENT_ROOT"])+1)))[0]."_database.php"
	), "argType" => -1, "mVal"=> true, "count" => 2, "includeInResponse" => false, "info" => "
	an array of two values. the first, db_conn, is initially a null but will subsequently be used to hold a myslqi connection object. the second, db_conn_file, is the path to and filename of a php file, relative to the document root, which creates a mysqli_connection to a database.  Note this doesn't MAKE the connection, that has to be done
	by including the include. it says where the include is and gives a place for the connection to be stored. 
	");

	$$nameSpaceID['templateVar']['previousURLQS'] 				= array("val"=> "", "DIRTY" => "", "tmpVal" => "",  "argType" => -1, "mval"=> false, "count" => 0, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => "
	holds the url (including query string) of the last page visited if known");

	$$nameSpaceID['templateVar']['currentURLQS'] 				= array("val"=> "", "DIRTY" => "", "tmpVal" => "",  "argType" => -1, "mval"=> false, "count" => 0, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => "
	holds the url (including query string) of the current page");

	$$nameSpaceID['templateVar']['currentHost'] 				= array("val"=> "", "DIRTY" => "", "tmpVal" => "",  "argType" => -1, "mval"=> false, "count" => 0, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => "
	holds the hostname of the current page");

	$$nameSpaceID['templateVar']['nextURLQS'] 				= array("val"=> "", "DIRTY" => "", "tmpVal" => "",  "argType" => -1, "mval"=> false, "count" => 0, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => "
	holds the url (including query string) of the next page to visit");

	
write_to_log("END OF 'RESUME DECLARING TEMPLATE VARIABLES (3)'","end_step");
/////////////////////////////////////////////////////////////////////////////////////////////
write_to_log("STEP 13 - CONNECT TO THIS APP'S DATABASE","start_step");

	if($$nameSpaceID['templateVar']['outputMode']['val'] == "TEXT"){
		write_to_log("this app's database is connected to using parameters described in the connection file stored on this server in folder ". $_SERVER['DOCUMENT_ROOT']. "/klogin/","P");
		write_to_log("the filename for the database must be the short name of this app followed  by '_database.php' for example, myapp_database.php . The short name is lifted from the
			current script's filepath (the name of the folder directly after ". $_SERVER['DOCUMENT_ROOT']."). In this case it was calculated as: '"
			.$_SERVER['DOCUMENT_ROOT']."/klogin/".explode("/",str_replace("\\", "/", substr(realpath(__FILE__),strlen($_SERVER["DOCUMENT_ROOT"])+1)))[0]."_database.php'","P");
	}
	


	$$nameSpaceID['templateVar']['app_link']['val']['db_conn'] = (function () {
		global $nameSpaceID; //make the namespaceid from the main body of the script available within the scope of this function
		global $$nameSpaceID;
		
		//global $$nameSpaceID['var']; //now i can make available all main body variables (which SHOULD all be stored within the namespace!) 
		//global $$nameSpaceID['templateVar']; //now i can make available all main body variables (which SHOULD all be stored within the namespace!) 
		global $commentary;
		if(!isset($$nameSpaceID['templateVar']['app_link']['val']['db_conn_file'])){
		
			write_to_log("FAIL!","red");
			write_to_log("no db link file was named. the path to and name of a .php file which creates a connection to a mysql database should be stored in variable 
					\$\$nameSpaceID['temlateVar'}['app_link']['val']['db_conn_file'].  Normally, the value would be '/var/www/html/klogin/'".explode("/",str_replace("\\", "/", substr(realpath(__FILE__),strlen($_SERVER["DOCUMENT_ROOT"])+1)))[0]."_database.php'.
					without that value in the variable, the database for this app can't be connnected to.","red");
			return false;
		}else{

			if(!file_exists($$nameSpaceID['templateVar']['app_link']['val']['db_conn_file'])){
				write_to_log("FAIL!","red");
				write_to_log("this app's connection file named in variable ['app_link']['val']['db_conn_file'] as ".$$nameSpaceID['templateVar']['app_link']['val']['db_conn_file']." doesn't exist. so this script cant retrieve info needed to connect to this app's database","red");
				return false;							
			}else{
				
				$$nameSpaceID['templateVar']['app_link']['val']['db_conn_file_nameSpaceID'] = "NS_" . trim(preg_replace("/[^A-Za-z0-9_]/", '',preg_replace("#[/\\\\\.]+#", "_", substr(realpath($$nameSpaceID['templateVar']['app_link']['val']['db_conn_file']),strlen($_SERVER["DOCUMENT_ROOT"])))),"\n\r\t\v\0_");
				
				include($$nameSpaceID['templateVar']['app_link']['val']['db_conn_file']);
				
				//as this code is inside a function, variables produced here, including those declared in the include
				//inside this function, will not be accessible outside of the function in the main scope. 
				//seeing as I want to refer to at least one variable created in the include,  later
				//outside of this current function, copy the include's namespace into the global namespace.
				$$nameSpaceID['templateVar']['app_link']['val']['db_conn_file_nameSpace'] =  ${$$nameSpaceID['templateVar']['app_link']['val']['db_conn_file_nameSpaceID']}['templateVar'];
				
				
				
				
				if(isset($$nameSpaceID['templateVar']['app_link']['val']['db_conn_file_nameSpace']['app_link_err']['val'])=== true){

					$$nameSpaceID['templateVar']['error']['val'] = $$nameSpaceID['templateVar']['app_link']['val']['db_conn_file_nameSpace']['app_link_err']['val'];
					write_to_log("FAIL!","red");
					write_to_log($$nameSpaceID['templateVar']['error']['val'],"red");
					return false;
				}else{
					write_to_log("SUCCESS!","green");
					return ${$$nameSpaceID['templateVar']['app_link']['val']['db_conn_file_nameSpaceID']}['templateVar']['app_link']['val'];
				} //end of if mysqli connect error returned
			}//end of !file_exists(db_conn_file)
		}// end of !isset(db_conn_file)
	})(); //end of self-invoking function 

write_to_log("END OF 'CONNECT TO  THIS APP's DATABASE'","end_step");
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
write_to_log("STEP 14 - TRY TO GET THIS APP'S INFO FROM APPSTORE DB","start_step");


	write_to_log("using app's appRoot as lookup key (".$$nameSpaceID['templateVar']['appInfo']['val']['appRoot'].")","P");

	$$nameSpaceID['templateVar']['sql']['query'] = "SELECT  `id`, `shortName`, `medname`, `shortDescription`, `protocol`, `devDomain`, `testDomain`, `prodDomain`,
	`appRoot`, `homePage`,`legalAndPolicyLink` FROM `%s`.`apps_%s` WHERE appRoot = ? LIMIT 1";
	$$nameSpaceID['templateVar']['sql']['query'] = sprintf($$nameSpaceID['templateVar']['sql']['query'],$$nameSpaceID['templateVar']['appStore_link']['val']['db_conn_file_nameSpace']['app_database']['val'], strtolower($$nameSpaceID['templateVar']['loc']['val']));
	$$nameSpaceID['templateVar']['sql']['stmt'] = mysqli_stmt_init($$nameSpaceID['templateVar']['appStore_link']['val']['db_conn']);
	mysqli_stmt_prepare($$nameSpaceID['templateVar']['sql']['stmt'],$$nameSpaceID['templateVar']['sql']['query']); 
	mysqli_stmt_bind_param($$nameSpaceID['templateVar']['sql']['stmt'],'s',$$nameSpaceID['templateVar']['appInfo']['val']['appRoot'] ); 

	if(($$nameSpaceID['templateVar']['appStore_link']['val']['db_conn']) === false){
		write_to_log("FAIL!","red");
		write_to_log("failed to get information about this app from the appStore database","red");
		write_to_log("could not establish a connection to the appstore database","red");
	}else{
		mysqli_stmt_execute($$nameSpaceID['templateVar']['sql']['stmt']);
		
		//if(!$$nameSpaceID['templateVar']['sql']['result']  = mysqli_query($$nameSpaceID['templateVar']['appStore_link']['val']['db_conn'], $$nameSpaceID['templateVar']['sql']['val'])){
		if(!$$nameSpaceID['templateVar']['sql']['result'] = mysqli_stmt_get_result($$nameSpaceID['templateVar']['sql']['stmt'])){
	
			$$nameSpaceID['templateVar']['error']['val'] = mysqli_error($$nameSpaceID['templateVar']['appStore_link']['val']['db_conn']);
			write_to_log("FAIL!","red");
			write_to_log($$nameSpaceID['templateVar']['error']['val'],"red");
			write_to_log("failed to get information about this app from the database","P");

			return false;
		}else{ //else of !result
		
		
			//if(mysqli_num_rows($$nameSpaceID['templateVar']['result']['val'] ) != 1){
			if(mysqli_num_rows($$nameSpaceID['templateVar']['sql']['result'] ) != 1){
				
				$$nameSpaceID['templateVar']['error']['val'] = 'sql found no apps with an appRoot of "'.$$nameSpaceID['templateVar']['appInfo']['val']['appRoot'].'" in the "'.$$nameSpaceID['templateVar']['loc']['val'].'" language appStore';
				write_to_log("FAIL!","red");
				write_to_log($$nameSpaceID['templateVar']['error']['val'],"red");
	
			}else{ //else of if num_rows
								
				$$nameSpaceID['templateVar']['sql']['row'] = mysqli_fetch_assoc($$nameSpaceID['templateVar']['sql']['result']);

				//merge the results into the appInfo values
				$$nameSpaceID['templateVar']['appInfo']['val'] = array_merge($$nameSpaceID['templateVar']['appInfo']['val'], $$nameSpaceID['templateVar']['sql']['row']);
				
				
				write_to_log(array("title"=>"Query Result","cols"=>array("key","value")),"start_table");
				write_to_log($$nameSpaceID['templateVar']['sql']['row'],"table_rows_keyed");
						
				write_to_log("SUCCESS!","green");
				write_to_log("got information about the app from the database","P");
				write_to_log($$nameSpaceID['templateVar']['sql']['result'],"object");
						
			
			} //end of if num_rows

		} //end of if !result
	} //end of if there is not a connection
	

write_to_log("END OF 'TRY TO GET THIS APP'S INFO FROM APPSTORE DB'","END_STEP");
///////////////////////////////////////////////////////////////////////////////////////////////////////////	
write_to_log("STEP 15 - CODER TO DECLARE OWN VARIABLES","start_step");
	
	/*
		argType = adds additional information on how to process the variable. 
	   -1.  if 'argType' attribute has a value of -1, the variable is internal to this script neither delivered to it via argc or by the URI ($_REQUEST array).
		0. the variable is to be provided to the script from outside (eg. by argv or in $_REQUEST) but the script will not error if it is not provided. there are many reasons why this may be desirable - perhaps because the variables absence from the user-supplied arguments is informative to the script, or perhaps because the value doesnt NEED to come from the user as it can fall back on a default declared in this script. 
		1. the variable is to be provided to the script from outside (eg by argv or in $_REQUEST) and is mandatory.the script will error if it is not provided.
	
	*/
	
	$$nameSpaceID['var']['id'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, in this instance it is to be associated with a text input"); 
	$$nameSpaceID['var']['shortName'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, in this instance it is to be associated with a text input"); 
	$$nameSpaceID['var']['medName'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, in this instance it is to be associated with a text input"); 
	$$nameSpaceID['var']['longName'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, in this instance it is to be associated with a text input"); 
	$$nameSpaceID['var']['shortDescription'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, in this instance it is to be associated with a text input"); 
	$$nameSpaceID['var']['longDescription'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, in this instance it is to be associated with a text input");
	$$nameSpaceID['var']['icon'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, in this instance it is to be associated with a text input"); 
	$$nameSpaceID['var']['iconColour'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, in this instance it is to be associated with a text input");
	$$nameSpaceID['var']['sortOrder'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, in this instance it is to be associated with a text input"); 	
	$$nameSpaceID['var']['publishStatus'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, in this instance it is to be associated with a text input"); 
	$$nameSpaceID['var']['primaryClient'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, in this instance it is to be associated with a text input"); 	
	$$nameSpaceID['var']['primaryClientUIN'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, in this instance it is to be associated with a text input"); 	
	$$nameSpaceID['var']['protocol'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, in this instance it is to be associated with a text input"); 
	$$nameSpaceID['var']['primaryDomain'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => -1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => "an example of a variable set in the template script, in this instance it is to be associated with a text input"); 
	$$nameSpaceID['var']['devDomain'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, in this instance it is to be associated with a text input"); 
	$$nameSpaceID['var']['testDomain'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, in this instance it is to be associated with a text input"); 
	$$nameSpaceID['var']['prodDomain'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, in this instance it is to be associated with a text input"); 
	$$nameSpaceID['var']['appRoot'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, in this instance it is to be associated with a text input"); 
	$$nameSpaceID['var']['homePage'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, in this instance it is to be associated with a text input"); 
	$$nameSpaceID['var']['legalAndPolicyLink'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, in this instance it is to be associated with a text input"); 
	$$nameSpaceID['var']['secureAnAccountLink'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, in this instance it is to be associated with a text input"); 	
	$$nameSpaceID['var']['allowNewIncidents'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, in this instance it is to be associated with a text input"); 
	$$nameSpaceID['var']['allowNewRFCs'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, in this instance it is to be associated with a text input"); 
	$$nameSpaceID['var']['allowNewRFIs'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, in this instance it is to be associated with a text input"); 
	$$nameSpaceID['var']['adminEmail'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, in this instance it is to be associated with a text input"); 
	$$nameSpaceID['var']['otherSMEs'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> true, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, in this instance it is to be associated with a text input"); 

// **** START BLOCK (ICS, Ian Sandever, 15/12/2025) ***
//	$$nameSpaceID['var']['longName'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> true, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => " : text input"); 
	$$nameSpaceID['var']['newRFCLink'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> true, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => " : text input"); 
	$$nameSpaceID['var']['newRFILink'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> true, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => " : text input"); 
	$$nameSpaceID['var']['newIncidentLink'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> true, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => " : text input"); 

	$$nameSpaceID['var']['maturity'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> true, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => " : text input"); 
//	$$nameSpaceID['var']['longDescription'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> true, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => " : text input"); 
	$$nameSpaceID['var']['client'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> true, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => " : text input"); 
	$$nameSpaceID['var']['alias'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> true, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => " : text input"); 
	$$nameSpaceID['var']['emailAddressInternalOwner'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> true, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => " : text input"); 
	$$nameSpaceID['var']['gitlabPath'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> true, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => " : text input"); 
	$$nameSpaceID['var']['documentLocation'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> true, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => " : text input"); 
// **** END BLOCK   (ICS, Ian Sandever, 15/12/2025) ***

	write_to_log($$nameSpaceID['var'],"DETAILS");
	
write_to_log("END OF 'CODER TO DECLARE OWN VARIABLES'","END_STEP");
///////////////////////////////////////////////////////////////////////////////////////////////////////////	
write_to_log("STEP 16 - TRY TO START/RESUME SERVER SESSION","start_step");
	
	if(session_status() === PHP_SESSION_NONE){
	
		//these first 2 lines are to allow the session cookie to work when called from an iframe - needs PHP 7.3 or higher allegedly
		ini_set('session.cookie_samesite', 'None');			//https://stackoverflow.com/questions/64023550/sessions-are-not-working-when-the-site-is-called-by-an-iframe
		session_set_cookie_params(['samesite' => 'None']);	//https://stackoverflow.com/questions/64023550/sessions-are-not-working-when-the-site-is-called-by-an-iframe
		session_start();

		//the server always uses en-GB
		//date_default_timezone_set($$nameSpaceID['var']['userLocalTimeZone']['val']); //might be set to something else later by recived arguments/user settings 
	}
	
	//register activity - used to keep alive user sessions and streams (keep-alive has not actually been coded. never used it so far). 
	$_SESSION['latestRequestTime'] = time();
	
	//register the current app's area in the session if it isn't already present
	if(array_key_exists('appInfo', $$nameSpaceID['templateVar']) === true && 
	array_key_exists('val',$$nameSpaceID['templateVar']['appInfo']) === true && 
	array_key_exists('appRoot',$$nameSpaceID['templateVar']['appInfo']['val']) === true &&  
	array_key_exists($$nameSpaceID['templateVar']['appInfo']['val']['appRoot'],$_SESSION) == false){		
		$_SESSION[$$nameSpaceID['templateVar']['appInfo']['val']['appRoot']] = array();
	}
	
	write_to_log("session status: ". array('PHP_SESSION_DISABLED','PHP_SESSION_NONE','PHP_SESSION_ACTIVE')[session_status()],"P");
	write_to_log($_SESSION,"details");

	//if the script is long-running, eg its delivering a stream, (or if any of its parents are delivering a stream then close the session

write_to_log("END OF 'TRY TO START/RESUME SERVER SESSION'","END_STEP");
/////////////////////////////////////////////////////////////////////////////////////////////////////
write_to_log("STEP 17 - GEOGRAPIC AND LOCALE SETTINGS","start_step");

	/*
	this section picks up and validates user preferences and settings for the localisation 
	(language/time zone) which the user is operating in.
	it validates by comparing the 'loc' value input to this script (if any) against the set of 
	'supportedLocales' set in the array should be set to contain locales which this script is set 
	up to work with. user's choice of language for output of content/error messages is determined 
	by receiving an argument called loc. support depends on the availablity of a corresponding 
	locale file under /languagePacks/ folder. loc informs the value stored in 
	$$nameSpaceID['templateVar']['loc']['val']  which is used through the script. 
	*/


	if(isset($_REQUEST)){
		if(array_key_exists('loc', $_REQUEST) === true){
			$$nameSpaceID['templateVar']['loc']['DIRTY'] = $_REQUEST['loc'];
			if(array_key_exists($$nameSpaceID['templateVar']['loc']['DIRTY'],$$nameSpaceID['templateVar']['supportedLocales']['val'])){
				$$nameSpaceID['templateVar']['loc']['val'] = $$nameSpaceID['templateVar']['loc']['DIRTY'];
				write_to_log("Locale was established from argument 'loc':".$$nameSpaceID['templateVar']['loc']['val'],"P"); 
			}else{
				write_to_log("received locale argument 'loc' was rejected as invalid. Valid values are:".implode(", ",$$nameSpaceID['templateVar']['supportedLocales']['val']),"P"); 
			}
		}else{
			write_to_log("defaulting to '".$$nameSpaceID['templateVar']['loc']['val']."'","P");
		}
	}
	
	
write_to_log("END OF 'GEOGRAPIC AND LOCALE SETTINGS'","END_STEP");
/////////////////////////////////////////////////////////////////////////////////////////////////////	
/*

write_to_log("STEP 18 - SETTING COMMON DOCUMENT HEADERS","start_step");

	
	//write the document headers which are necessary regardless of whether the document 
	//produced is JSON or HTML but only do this if this script is not called as an include/require
	
	
	if($$nameSpaceID['templateVar']['debugging']['val'] == true){
		write_to_log("Failed to send headers but no action is necessary. Your script is running in debug mode so headers cannot be set. This is expected and will resolve its self when the script is not in debug mode ","P");	
	}else{
		if (str_replace('\\', '/', __FILE__) == $_SERVER['SCRIPT_FILENAME']) {
			header("Cache-Control: no-cache");
			header('X-Content-Type-Options: nosniff');  // to avoid IE sniffing (penetration testing 18/12/13)
			header("Expires: -1");
			date_default_timezone_set('Europe/London'); //server-side this is always Europe/London (UTC/GMT) but 
											  //knowledge of user's $$nameSpaceID['templateVar']['loc']['value'] may be used to localize user-facing output
		}
	}


write_to_log("END OF 'SETTING COMMON DOCUMENT HEADERS'","END_STEP");

*/


/////////////////////////////////////////////////////////////////////////////////////////////////////
write_to_log("STEP 19 - CALL ANY STANDARD TEMPLATE INCLUDE FILES ","start_step");

	/*
	normally single login/sl/klogin/authentication includes are made here
	eg include_once($_SERVER['DOCUMENT_ROOT'] ."/klogin/cookiefunctions.php");
	*/

	include_once($_SERVER['DOCUMENT_ROOT'] ."/klogin/cookiefunctions.php");
	include_once( $_SERVER['DOCUMENT_ROOT'] ."/klogin/languagePacks/".$$nameSpaceID['templateVar']['loc']['val']."/fieldValidationFunctions.php");
	include_once($_SERVER['DOCUMENT_ROOT'] ."/klogin/languagePacks/".$$nameSpaceID['templateVar']['loc']['val']."/serverResponses.php");
	include_once($_SERVER['DOCUMENT_ROOT'] ."/klogin/languagePacks/".$$nameSpaceID['templateVar']['loc']['val']."/DOMContent.php");


	$included_files = get_included_files();
	$countOfIncludedFiles = count($included_files);
	write_to_log("the following files have been included so far:","P");
	if($countOfIncludedFiles == 1){ 
		//the first value returned is the name of the current file only, not an include.
		write_to_log("none","P");
	}else{
		for($i=1;$i < $countOfIncludedFiles ;$i++){
			write_to_log($included_files[$i],"P");
		}
	}
	

write_to_log("END OF 'CALL ANY STANDARD TEMPLATE INCLUDE FILES'","END_STEP");
/////////////////////////////////////////////////////////////////////////////////////////////////////
write_to_log("STEP 20 - CALL ANY APP-SPECIFIC INCLUDE OR REQUIRED FILES ","start_step");

	/*
	normally any app-specific include or require files are are named here eg:
	include_once( $_SERVER['DOCUMENT_ROOT']. "/". $$nameSpaceID['templateVar']['appInfo']['val']['appRoot'] . "/languagePacks/".$$nameSpaceID['templateVar']['loc']['val']."/fieldValidationFunctions.php");
	*/
	
	if($$nameSpaceID['templateVar']['outputMode']['val'] == "TEXT"){
		write_to_log("at a minimum, this likely includes an app-specific fieldValidationFunctions.php and also serverResponses.php and DOMContent.php (called from the app's languagePacks folder which normally sits in the app's root directory ","P"); 
	}

	include_once( $_SERVER['DOCUMENT_ROOT']. "/". $$nameSpaceID['templateVar']['appInfo']['val']['appRoot'] . "/languagePacks/".$$nameSpaceID['templateVar']['loc']['val']."/fieldValidationFunctions.php");	
	include_once($_SERVER['DOCUMENT_ROOT'] . "/". $$nameSpaceID['templateVar']['appInfo']['val']['appRoot'] . "/languagePacks/".$$nameSpaceID['templateVar']['loc']['val']."/serverResponses.php");
	include_once($_SERVER['DOCUMENT_ROOT'] . "/". $$nameSpaceID['templateVar']['appInfo']['val']['appRoot'] . "/languagePacks/".$$nameSpaceID['templateVar']['loc']['val']."/DOMContent.php");
    //cookiefunctions???
	
	//Any other includes/requires which you want to include should go HERE.
	
	
	$included_files = get_included_files();
	$countOfIncludedFiles = count($included_files);
	write_to_log("the following files have been included so far:","P");
	if($countOfIncludedFiles == 1){ 
		//the first value returned is the name of the current file only, not an include.
		write_to_log("none","P");
	}else{
		for($i=1;$i < $countOfIncludedFiles ;$i++){
			write_to_log($included_files[$i],"P");
		}
	}
	
write_to_log("END OF 'CALL ANY APP-SPECIFIC INCLUDE OR REQUIRED FILES'","END_STEP");
/////////////////////////////////////////////////////////////////////////////////////////////////////
write_to_log("STEP 21 - DISPLAY THE STANDARDIZED GENERIC DEBUGGING INFO ","start_step");

	/*
	presents several pieces of information generally useful to a developer when 
	debugging their code.  this only outputs content if this script is not a child
	of another and if debugging is enabled. 
	*/


	if($$nameSpaceID['templateVar']['debugging']['val'] === true && str_replace('\\', '/', __FILE__) == $_SERVER['SCRIPT_FILENAME']) { 
		if(function_exists('standardDebugOutput')){
			write_to_log(standardDebugOutput(__FILE__),"DETAILS");
		}else{
		write_to_log("the standardDebugOutput() function should have filled this section with debugging information but the function is unavailable.
		Since that function is normally sourced from a file called: '" . $_SERVER['DOCUMENT_ROOT'] . "/klogin/cookiefunctions.php', This suggests that 
		the instruction in this script (" . __FILE__ . ") to include the file called: '". $_SERVER['DOCUMENT_ROOT'] ."/klogin/cookiefunctions.php'
		is missing from the ''CALL ANY STANDARD TEMPLATE INCLUDE FILES' section of the script.
		if you find the instruction to include that file is present, then perhaps the included file is damaged or incomplete","P");
		}
	}
	
	
write_to_log("END OF 'DISPLAY THE STANDARDIZED GENERIC DEBUGGING INFO'","END_STEP");
/////////////////////////////////////////////////////////////////////////////////////////////////////
write_to_log("STEP 22 - CHECK ARGUMENTS ","start_step");

	/*
	arguments received into this script from $_REQUEST and/or $_ARGV are each passed to a 
	function which error checks and returns an error code of 0 (no error) or above for an error 
	*/


	
	if(isset($_REQUEST) === true){
		write_to_log("STEP 15.1 - VERIFY/VALIDATE EACH ARGUMENT","start_step");
		
		//verification/validation for each value received as an argument,		
		foreach(array_keys($_REQUEST) as $$nameSpaceID['templateVar']['rkey']['val']){
			//if it corresponds to an expected variable which is expecting to be set by an argument

			//check both the _var and _templateVar storage areas of the namespace for a variable name matching the argument from the request array
			//note which part it is found in so that the verification/validation checks can be targeted at the right storage area 
			$$nameSpaceID['templateVar']['rkey']['storageArea'] = "";  //first, reinitialise this variable to start with the assumption of no match
			
			
			
			
			if(isset($$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]) === true && $$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]['argType'] >= 0){
				$$nameSpaceID['templateVar']['rkey']['storageArea']= "var"; //found in the _var storage space
			}elseif(isset($$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]) === true && $$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]['argType'] >= 0){
				$$nameSpaceID['templateVar']['rkey']['storageArea']= "templateVar";  //found in the _templateStorage space.
			}
			
			if($$nameSpaceID['templateVar']['rkey']['storageArea'] != ""){ //if the namespace contains a variable matching the current request array item...
			
			
				//take the received value and store it, marked as dirty. 
				$$nameSpaceID[$$nameSpaceID['templateVar']['rkey']['storageArea']][$$nameSpaceID['templateVar']['rkey']['val']]['DIRTY'] = $_REQUEST[$$nameSpaceID['templateVar']['rkey']['val']];
				$e = -1;
				//check the dirty value against the corresponding checking function which should have been written and included in the app's
				//top level directory, in a file called fieldValidationFunctions.php using function the naming convention checkX()  eg checksiteID()
				
				//use the check functions to check if there are input errors which have not been asserted by the query string.
				//the functions are stored in an array in the namespace for the locale specific fieldValidationFunctions.php file
				//the name of the namespace for the fieldValidationFunctions file was calculated earier and stored in this file's
				//namespace vars (in $$nameSpaceID['templateVar']['fieldValidationsNameSpace']['val']).
	


			// this is how to acess the fn array in the other namespace:  ${$$nameSpaceID['templateVar']['fieldValidationsNameSpace']['val']}['fn']
				
			if(array_key_exists('check'.$$nameSpaceID['templateVar']['rkey']['val'],${$$nameSpaceID['templateVar']['fieldValidationsNameSpace']['val']}['fn']) && is_callable(${$$nameSpaceID['templateVar']['fieldValidationsNameSpace']['val']}['fn']['check'.$$nameSpaceID['templateVar']['rkey']['val']])=== true){
					
					
					$e =  ${$$nameSpaceID['templateVar']['fieldValidationsNameSpace']['val']}['fn']['check'.$$nameSpaceID['templateVar']['rkey']['val']]($$nameSpaceID[$$nameSpaceID['templateVar']['rkey']['storageArea']][$$nameSpaceID['templateVar']['rkey']['val']]['DIRTY']);

					if($e == 0){
						//if the check returned an error code of zero, no error so copy the dirty value received into the variable's long-term usable value.
						$$nameSpaceID[$$nameSpaceID['templateVar']['rkey']['storageArea']][$$nameSpaceID['templateVar']['rkey']['val']]['val'] = $$nameSpaceID[$$nameSpaceID['templateVar']['rkey']['storageArea']][$$nameSpaceID['templateVar']['rkey']['val']]['DIRTY'];		
						write_to_log("Value '".$$nameSpaceID[$$nameSpaceID['templateVar']['rkey']['storageArea']][$$nameSpaceID['templateVar']['rkey']['val']]['val']."' in argument '". $$nameSpaceID['templateVar']['rkey']['val']."' validated by function 'check" . $$nameSpaceID['templateVar']['rkey']['val'] . "()'","P");
					}else{
						
						$$nameSpaceID[$$nameSpaceID['templateVar']['rkey']['storageArea']][$$nameSpaceID['templateVar']['rkey']['val']]['varSpecificErrs'][] = $e; //$e holds the error code at this point, which is going to be returned to user.
							
						write_to_log("argument '".$$nameSpaceID['templateVar']['rkey']['val']."' was expected, but failed validation by 'check".$$nameSpaceID['templateVar']['rkey']['val']."()' (likely defined in '".$_SERVER['DOCUMENT_ROOT']. "/".$$nameSpaceID['templateVar']['appInfo']['val']['appRoot'] ."/languagePacks/".$$nameSpaceID['templateVar']['loc']['val']."/fieldValidationFunctions.php'), returning error code ".$e .".","P");
						
						if(is_array($errors) && array_key_exists($e,$errors)){
							write_to_log("code ". $e. " = '". $errors[$e]['msg']."'. decoded by this app's 'serverResponses.php'","P");
						}else{
							write_to_log("code '". $e.  "' is undefined in 'serverResponses.php'. Either adjust 'check" . $$nameSpaceID['templateVar']['rkey']['val'] . "()' or define the code in serverResponses.php.","P");
						}
					}
				}else{	
					
					write_to_log("no validation function 'check".$$nameSpaceID['templateVar']['rkey']['val']."()' for argument '".
					$$nameSpaceID['templateVar']['rkey']['val']. " (expected in '".$_SERVER['DOCUMENT_ROOT']."/".$$nameSpaceID['templateVar']['appInfo']['val']['appRoot'] .'/languagePacks/'.$$nameSpaceID['templateVar']['loc']['val']."/fieldValidationFunctions.php') 
					so argument will pass through this script, unused","red");

				}
			}else{ 
				//else the received value doesnt correspond to a variable expecting input from an argument so set it to one side.
				$$nameSpaceID['templateVar']['QUARANTINE_DIRTY']['val'][$$nameSpaceID['templateVar']['rkey']['val']] = $_REQUEST[$$nameSpaceID['templateVar']['rkey']['val']];				
			}
		}
				
		if(count($$nameSpaceID['templateVar']['QUARANTINE_DIRTY']['val'])>0){
			write_to_log("coder hasn't declared variables to receive all arguments received","red");		
			write_to_log(array(
						"title"=>"Argument(s) without values '".__FILE__. "'",
						"cols"=> array(
							"variable","value"
						)
						),"START_TABLE");
			//send a copy of the quarantine_dirty array, with the values removed
			write_to_log(array_fill_keys(array_keys($$nameSpaceID['templateVar']['QUARANTINE_DIRTY']['val']), "quarantined"),"table_rows_keyed");
	
		}else{
			write_to_log("all arguments had a validation function to check them","p");
		}
		write_to_log("END OF 'VERIFY/VALIDATE EACH ARGUMENT'","end_step");
		//////////////////////////////////////////////////////////////////
 		write_to_log("step 15.2 - CHECK EVERY MANDATED ARGUMENT HAS A VALUE","start_step");

		
		write_to_log(
		"in the template standard, variables should be declared in the 'CODER TO DECLARE THEIR OWN VARIABLES HERE' 
		step (as part of nameSpaceID._var). A declared 'argType' of 1 requires the script to  have an argument 
		paired with the variable.  In this step, each mandated argument is checked to see if it has a value. 
		An error is registered if it does not.","P");
		
		foreach(array_keys($$nameSpaceID) as $$nameSpaceID['templateVar']['rkey']['val']){
			if(count($$nameSpaceID[$$nameSpaceID['templateVar']['rkey']['val']]) >0){
				//create a temporary array of the coder's vars, containing only those with an argType of 1 and with no value
				$$nameSpaceID['templateVar']['vKey']['tmpArray'] = array_filter($$nameSpaceID[$$nameSpaceID['templateVar']['rkey']['val']], function ($variable) {
					return array_key_exists('argType',$variable) && $variable['argType'] == 1 && $variable['val'] == "" && $variable['DIRTY'] == "";	
				});

				if(count($$nameSpaceID['templateVar']['vKey']['tmpArray']) >0){
					foreach(array_keys($$nameSpaceID['templateVar']['vKey']['tmpArray']) as $$nameSpaceID['templateVar']['vKey']['val']){
						//cycle through the array of variables
						//add the error info to the related variable's varSpecificErrs array so that it is returned to the user
						$$nameSpaceID[$$nameSpaceID['templateVar']['rkey']['val']][$$nameSpaceID['templateVar']['vKey']['val']]['varSpecificErrs'][] = "60"; //mandatory value missing
						write_to_log($$nameSpaceID['templateVar']['vKey']['val']. " in ". $$nameSpaceID['templateVar']['rkey']['val'],"p");
					
					}
					write_to_log("FAIL!","H4");
					write_to_log(array("title" => "coder's mandated variables which are empty:", "cols" => array("#","variable")),"start_table");
					write_to_log(array_keys($$nameSpaceID['templateVar']['vKey']['tmpArray']),"table_rows_keyed");		
					write_to_log(
							"One or more mandated arguments which this script needs to receive are not stored in their corresponding variable.
							this means that the script was told that it is mandatory that it receives a particular input as an argument and to store the value in a corresponding variable but by this step of the code, the variable contains nothing.				
							Likely causes:<ul>
							<li>check the debug info from 'DISPLAY THE STANDARDIZED GENERIC DEBUGGING INFO' above to see if the the expected argument was received and with a value.
							<li>check you haven't mistakenly declared a variable twice with the same name (one overwriting the other)
							<li>perhaps 'CODER TO DECLARE THEIR OWN VARIABLES HERE' step of '" . __FILE__ ."' has an argType of 1 set for a non-mandatory argument 
							<li>perhaps 'CtmeplateVARIABLES HERE' step of '" . __FILE__ ."' has an argType of 1 set for a non-mandatory argument 
						</ul>","red");	
				}else{
					write_to_log("SUCCESS","H4");
					write_to_log("there are mandatory variables but none of them are empty","green");
				}
				unset($$nameSpaceID['templateVar']['vKey']['tmpArray']); //remove the transitory array
			}else{
				write_to_log("nothing to be 'mandatory with no value as coder has defined no variables.","P");
			}
		}
		
	
		write_to_log("END OF 'CHECK EVERY MANDATED ARGUMENT HAS A VALUE'","end_step");
		//////////////////////////////////////////////////////////////////
 		write_to_log("step 15.3 - STEP 15.3 - REFORMAT ANY SEMI-COLON-DELIMITED ARGUMENTS","start_step");
	
			
		write_to_log(
		"where a variable's value is set from an argument (indicated by an argType >=0) and the variable's value 
		is not already an array, but its mVal flag is set to true, meaning that it is expected to hold 
		multiple values, then convert it to an array. Assume any semi-colons are delimiters.
		where arguments are submitted from some HTML form controls or via URL / CLI argument, their flat
		string structure doesn't accommodate the transmission of arrays so this technique allows the argument 
		to be ingested as-sent, but then gives the coder the convenience of processing the inputs as an array 
		in their code (step 17).","P");
			


		if(count($$nameSpaceID['var']) >0){
			
			/*
			find the variables im interesed in. values are from arguments (argType >=0) and multvariable (mVal) flag = true
			later, skip any with errors as they'll not be used anyway, but leave them in scope here so that debugging info 
			can be used to explain why those fields aren't converted
			*/
			$$nameSpaceID['templateVar']['vKey']['tmpArray'] = 
			array_filter($$nameSpaceID['var'], function ($variable) {
				return 
				array_key_exists('argType',$variable) && $variable['argType'] >=0 && 
				array_key_exists('mVal',$variable) && $variable['mVal'] == true;
			});

			//if there are any such variables
			if(count($$nameSpaceID['templateVar']['vKey']['tmpArray']) >0){
				//cycle through them.
				foreach(array_keys($$nameSpaceID['templateVar']['vKey']['tmpArray']) as $$nameSpaceID['templateVar']['vKey']['val']){
					//if they have no errors, convert them into an array.
					if(count($$nameSpaceID['templateVar']['vKey']['tmpArray'][$$nameSpaceID['templateVar']['vKey']['val']]['varSpecificErrs']) > 0){
						//if there are errors associated with the variable's value, no point converting it as i wont be using it
						write_to_log("skipped variable '".$$nameSpaceID['templateVar']['vKey']['val']."' as there are known to be errors in its value","P");
					}else{
						//create the array by exploding at semi-colons if there are any present in the value
						if(strpos($$nameSpaceID['var'][$$nameSpaceID['templateVar']['vKey']['val']]['val'],";") > 0){
							$$nameSpaceID['var'][$$nameSpaceID['templateVar']['vKey']['val']]['val'] = explode(";", $$nameSpaceID['var'][$$nameSpaceID['templateVar']['vKey']['val']]['val']);
							$$nameSpaceID['var'][$$nameSpaceID['templateVar']['vKey']['val']]['count'] = count($$nameSpaceID['var'][$$nameSpaceID['templateVar']['vKey']['val']]['val']);
						}else{
							//otherwise iof there are no semi-colons, then just convert to a 1D array with one record.
							$$nameSpaceID['var'][$$nameSpaceID['templateVar']['vKey']['val']]['val'] = array($$nameSpaceID['var'][$$nameSpaceID['templateVar']['vKey']['val']]['val']);
						}
						write_to_log("converted: ".$$nameSpaceID['templateVar']['vKey']['val']."('".$$nameSpaceID['templateVar']['vKey']['tmpArray'][$$nameSpaceID['templateVar']['vKey']['val']]['val']."')","H4");
						write_to_log($$nameSpaceID['var'][$$nameSpaceID['templateVar']['vKey']['val']]['val'], "ARRAY");
					}
				}		
			}else{
				write_to_log("there are variables, but all are out of scope as none are both designed to be mutli-value, and fed from arguments","p");
			}
			unset($$nameSpaceID['templateVar']['vKey']['tmpArray']); //remove the transitory array
		}else{
			write_to_log("nothing to be considered as coder has defined no variables.","P");
		}

		write_to_log("END OF 'REFORMAT ANY SEMI-COLON-DELIMITED ARGUMENTS'","end_step");
		/////////////////////////////////////////////////////////////////////////////////	
	}//end request is set

write_to_log("END OF 'CHECK ARGUMENTS'","end_step");	
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
write_to_log("STEP 23 - PREPARE THE OUTPUT FOR THIS SCRIPT ","start_step");

	/*
	the script is either working to produce a URL with a query string loaded with things to 
	pass on, or it is working to produce a JSON object as the reply.
	this is determined by the value of $$nameSpaceID['templateVar']['outputMode']['val']
	this step produces the 'stub' of the appropriate output so that later the user's code can
	suppliment it. 
	*/

	write_to_log("output mode:  ". $$nameSpaceID['templateVar']['outputMode']['val'],"P");

	if($$nameSpaceID['templateVar']['outputMode']['val'] == "JSON"){
		write_to_log("the script will deliver a JSON reply by populating \$JSONObj which already exists","P");
	}else{
		write_to_log("the script will determine what the next URL should be so that its query string can be loaded before the user is redirected to it.","P");
		
		//calculate the current url of this script
		$$nameSpaceID['templateVar']['currentURLQS']['val'] = 'http';
		$$nameSpaceID['templateVar']['currentURLQS']['val'] .= ((array_key_exists('HTTPS',$_SERVER) === true && isset($_SERVER['HTTPS']) === true && strtolower($_SERVER['HTTPS']) === "on" )?"s":"");
		$$nameSpaceID['templateVar']['currentURLQS']['val'] .= '://';		
		$$nameSpaceID['templateVar']['currentHost']['val'] = ((array_key_exists('HTTP_HOST',$_SERVER) === true && isset($_SERVER['HTTP_HOST']) === true && $_SERVER['HTTP_HOST'] === "80")?'localhost':$_SERVER['HTTP_HOST']);
		$$nameSpaceID['templateVar']['currentURLQS']['val'] .= $$nameSpaceID['templateVar']['currentHost']['val'];
		$$nameSpaceID['templateVar']['currentURLQS']['val'] .= ((array_key_exists('SERVER_PORT',$_SERVER) === true && isset($_SERVER['SERVER_PORT']) === true && $_SERVER['SERVER_PORT'] != "" && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) ? ":" . $_SERVER['SERVER_PORT'] :""); 
		$$nameSpaceID['templateVar']['currentURLQS']['val'] .= ((array_key_exists('SCRIPT_NAME',$_SERVER) === true && isset($_SERVER['SCRIPT_NAME']) === true && $_SERVER['SCRIPT_NAME'] != "") ? $_SERVER['SCRIPT_NAME'] :""); 
		
		write_to_log("current url is:" . $$nameSpaceID['templateVar']['currentURLQS']['val'], "P");
		write_to_log("current host domain is:" . $$nameSpaceID['templateVar']['currentHost']['val'],"P");	
		
			
			
		//////////////////////////////////////////////////////////////////
 		write_to_log("TEXT METHOD 1: TRY TO USE \$_SERVER['HTTP_REFERER'] ","start_step");
			
		/*
		if it's present, don't just use \$_SERVER['HTTP_REFERER'] without checking it out.
		a common spoofing hack is to replace it to redirect traffic to another site.
		*/
		if(array_key_exists('HTTP_REFERER',$_SERVER) === true && isset($_SERVER['HTTP_REFERER']) === true && $_SERVER['HTTP_REFERER'] != "" && filter_var($_SERVER['HTTP_REFERER'], FILTER_VALIDATE_URL) != false ){
			$parse = parse_url($_SERVER['HTTP_REFERER']);
			if($parse['host'] === $$nameSpaceID['templateVar']['currentHost']['val']){
				$$nameSpaceID['templateVar']['previousURLQS']['val'] = $_SERVER['HTTP_REFERER'];
				
				//drop error and sr from prevURL if they are present before using it as nextURL
				$$nameSpaceID['templateVar']['nextURLQS']['val'] =  remove_qs_key(remove_qs_key($$nameSpaceID['templateVar']['previousURLQS']['val'], 'error'),'sr');
				
				write_to_log("SUCCESS","H4");
				write_to_log("\$_SERVER['HTTP_REFERER'] is set and valid and on the same host domain as this page","green");
			}else{
				write_to_log("FAIL","H4");
				write_to_log("\$_SERVER['HTTP_REFERER'] isn't from domain '" . $parse['host'] . "' so isn't trustworthy","red");
			}			
		}else{
			write_to_log("FAIL","H4");
			write_to_log("\$_SERVER['HTTP_REFERER'] is untrusted because it failed a trust check:", "P");
			write_to_log("\$_SERVER['HTTP_REFERER'] exists?    : " . (array_key_exists('HTTP_REFERER',$_SERVER)?"PASS":"FAIL"),"P");
			write_to_log("\$_SERVER['HTTP_REFERER'] is set?    : " . (isset($_SERVER['HTTP_REFERER'])?"PASS":"FAIL"),"P");
			write_to_log("\$_SERVER['HTTP_REFERER'] is valid?  : " . ((isset($_SERVER['HTTP_REFERER']) === true && filter_var($_SERVER['HTTP_REFERER'], FILTER_VALIDATE_URL) === true)?"PASS":"FAIL"),"P");
			write_to_log("\$_SERVER['HTTP_REFERER'] has value? : " . (isset($_SERVER['HTTP_REFERER']) === true && ($_SERVER['HTTP_REFERER']!="")?"PASS":"FAIL"),"P");
		}
			
		write_to_log("END OF TEXT METHOD 1 - TRY TO USE \$_SERVER['HTTP_REFERER']","end_step");
		//////////////////////////////////////////////////////////////////
 		write_to_log("TEXT METHOD 2 - 'process_xxx.php' FILE WITH A CORRESPONDING xxxx.php FILE","start_step");

		if($$nameSpaceID['templateVar']['nextURLQS']['val'] !== ""){
			write_to_log("skipped. a previous method worked","P");
		}else{
			
			write_to_log("current file: " . str_replace("\\", "/",( __DIR__ . "\\" . substr(__FILE__,strlen(realpath(dirname(__FILE__)))+1))),"P");										
	
			
			//if //if the current files' name is more than 8 chars long
			if(strlen(substr(__FILE__,strlen(realpath(dirname(__FILE__)))+1))> 9){
				if(strtoupper(substr(__FILE__,strlen(realpath(dirname(__FILE__)))+1,8)) === "PROCESS_"){
					
					write_to_log("filename starts with 'process_'","P");

					//if(file_exists(substr(__FILE__,strlen(realpath(dirname(__FILE__)))+9)) === true){
					if(file_exists(str_replace("\\", "/",( __DIR__ . "\\" . substr(__FILE__,strlen(realpath(dirname(__FILE__)))+1)))) === true){
						write_to_log("corresponding file exists called: ". substr(__FILE__,strlen(realpath(dirname(__FILE__)))+9),"P");

						//calculate nexturl from current url with the filename swapped out for the non process_ version
						$$nameSpaceID['templateVar']['nextURLQS']['val'] = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' .  $_SERVER['HTTP_HOST'] .  ($_SERVER['SERVER_PORT'] != (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 443 : 80) ? ':' . $_SERVER['SERVER_PORT'] : '') . pathinfo(parse_url($$nameSpaceID['templateVar']['currentURLQS']['val'], PHP_URL_PATH),PATHINFO_DIRNAME)."/" .substr(__FILE__,strlen(realpath(dirname(__FILE__)))+9);
						
						write_to_log("SUCCESS!","H4");
						write_to_log("next url calculated as:  ","green");
						write_to_log($$nameSpaceID['templateVar']['nextURLQS']['val'],"P");				
					}else{
						write_to_log("FAIL!","H4");
						write_to_log("a corresponding 'xxx.php' file does not exist","red");
					}
					
				}else{	
					write_to_log("FAIL!","H4");
					write_to_log("file name doesn't starts with 'process_' so this method is not applicable","red");
				}
			}else{
				write_to_log("FAIL!","H4");
				write_to_log("file name of this script is too short to start with 'process_' so this method is not applicable","red");
			}	
			
		} //end of method 2#
		write_to_log("END OF METHOD 2 - 'process_xxx.php' FILE WITH A CORRESPONDING xxxx.php FILE","END_STEP");
		/////////////////////////////////////////////////////////////////////////////////////////////////////	
		write_to_log("TEXT METHOD 3 - TRY TO USE APPSTORE DATABASE-DERIVED VALUES","start_step");	

		/*
		this isn't likely to always get the next url right, but if nothing else is working it may
		get you close or even there by taking and using database-derived app constants obtained earlier from the appStore
		*/

		if($$nameSpaceID['templateVar']['nextURLQS']['val'] !== ""){
			write_to_log("skipped. a previous method worked","P");
		}else{	


			if(
				array_key_exists('appInfo',$$nameSpaceID['templateVar']) === true &&
				array_key_exists('val',$$nameSpaceID['templateVar']['appInfo']) &&
				array_key_exists('protocol',$$nameSpaceID['templateVar']['appInfo']['val']) &&
				array_key_exists('primaryDomain',$$nameSpaceID['templateVar']['appInfo']['val']) &&
				array_key_exists('appRoot',$$nameSpaceID['templateVar']['appInfo']['val']) &&
				array_key_exists('homePage',$$nameSpaceID['templateVar']['appInfo']['val']) &&
				(filter_var( 
					$$nameSpaceID['templateVar']['appInfo']['val']['protocol'] .
					$$nameSpaceID['templateVar']['appInfo']['val']['primaryDomain'] . "/".
					$$nameSpaceID['templateVar']['appInfo']['val']['appRoot'] . "/".
					$$nameSpaceID['templateVar']['appInfo']['val']['homePage'],
				FILTER_VALIDATE_URL
				)!= false)
			){
				$$nameSpaceID['templateVar']['nextURLQS']['val'] = $$nameSpaceID['templateVar']['appInfo']['val']['protocol'] .
					$$nameSpaceID['templateVar']['appInfo']['val']['primaryDomain'] . "/".
					$$nameSpaceID['templateVar']['appInfo']['val']['appRoot'] . "/".
					$$nameSpaceID['templateVar']['appInfo']['val']['homePage'];
	
				write_to_log("SUCCESS!","H4");
				write_to_log("next url calculated as: " . $$nameSpaceID['templateVar']['nextURLQS']['val'],"green");
							
			}else{

				write_to_log("FAIL!","H4");
				write_to_log("couldn't retrieve and concateneate appStore database-derived variables for this app into a valid URL","RED");
				write_to_log(array("title"=>"variables which didn't concatenate into URL:","cols"=>array("name","exists?","value")),"start_table");
				write_to_log(array(
					array("appInfo",	array_key_exists('appInfo',$$nameSpaceID['templateVar']),						"N/A"),
					array("val",		array_key_exists('val',$$nameSpaceID['templateVar']['appInfo']),				"N/A"),
					array("protocol",	array_key_exists('protocol',$$nameSpaceID['templateVar']['appInfo']['val']),	$$nameSpaceID['templateVar']['appInfo']['val']['protocol']),
					array("primaryDomain", 	array_key_exists('primaryDomain',$$nameSpaceID['templateVar']['appInfo']['val']),	$$nameSpaceID['templateVar']['appInfo']['val']['primaryDomain']),
					array("appRoot", 	array_key_exists('appRoot',$$nameSpaceID['templateVar']['appInfo']['val']),	$$nameSpaceID['templateVar']['appInfo']['val']['appRoot']),
					array("homePage", 	array_key_exists('homePage',$$nameSpaceID['templateVar']['appInfo']['val']),	$$nameSpaceID['templateVar']['appInfo']['val']['homePage'])
				),"table_rows");
				write_to_log("Combined: ".
					($$nameSpaceID['templateVar']['appInfo']['val']['protocol'] ?? '{protocol-missing}') .
					($$nameSpaceID['templateVar']['appInfo']['val']['primaryDomain'] ?? '{primary-domain-missing}') . "/".
					($$nameSpaceID['templateVar']['appInfo']['val']['appRoot'] ?? '{appRoot-missing}') . "/".
					($$nameSpaceID['templateVar']['appInfo']['val']['homePage'] ?? '{homePage-missing}')
				,"P");
			}
			
		}//end of  method 3
		write_to_log("END OF TEXT METHOD 3 - TRY TO USE APPSTORE DATABASE-DERIVED VALUES","end_step");
		//////////////////////////////////////////////////////////////////
 		write_to_log("TEXT METHOD 4 - TRY TO USE THE CURRENT SCRIPT'S FOLDER","start_step");
							
		/*
		use available server variables plus path from document_root for this file to build a potential url
		for the next url - the default script for the current script's folder eg index.
		by not appending the file name, the web server settings for default filename will dictate the filename.
		*/


		if($$nameSpaceID['templateVar']['nextURLQS']['val'] != ""){
			write_to_log("skipped. a previous method worked","P");
		}else{
			$$nameSpaceID['templateVar']['nextURLQS']['tmpVal'] = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' .  $_SERVER['HTTP_HOST'] .  ($_SERVER['SERVER_PORT'] != (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 443 : 80) ? ':' . $_SERVER['SERVER_PORT'] : '') . pathinfo(parse_url($$nameSpaceID['templateVar']['currentURLQS']['val'], PHP_URL_PATH),PATHINFO_DIRNAME)."/" ;

			if(filter_var($$nameSpaceID['templateVar']['nextURLQS']['tmpVal'], FILTER_VALIDATE_URL) != false){	
				$$nameSpaceID['templateVar']['nextURLQS']['val'] = $$nameSpaceID['templateVar']['nextURLQS']['tmpVal'];
				write_to_log("SUCCESS!","H4");
				write_to_log("next url calculated as: " . $$nameSpaceID['templateVar']['nextURLQS']['val'],"green");
			}else{
				write_to_log("FAIL!","H4");
				write_to_log("'".$$nameSpaceID['templateVar']['nextURLQS']['tmpVal']."' did not render a valid url." ,"red");
			}
		}//end of method 4 
		
		write_to_log("END OF METHOD 4 'TRY TO USE TRY TO USE THE CURRENT SCRIPT'S FOLDER","end_step");
		//////////////////////////////////////////////////////////////////
 		write_to_log("TEXT METHOD 5 - TRY TO USE APP-SPECIFIC TOP LEVEL FOLDER AT SERVER DOCUMENT ROOT","start_step");
		
	
		if($$nameSpaceID['templateVar']['nextURLQS']['val'] != ""){
			write_to_log("skipped. a previous method worked","P");
		}else{
				
			$$nameSpaceID['templateVar']['nextURLQS']['tmpVal'] = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' .  $_SERVER['HTTP_HOST'] .  ($_SERVER['SERVER_PORT'] != (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 443 : 80) ? ':' . $_SERVER['SERVER_PORT'] : '') . "/". explode("/",pathinfo(parse_url($$nameSpaceID['templateVar']['currentURLQS']['val'], PHP_URL_PATH),PATHINFO_DIRNAME))[1] ."/" ;

			if(filter_var($$nameSpaceID['templateVar']['nextURLQS']['tmpVal'], FILTER_VALIDATE_URL) != false){	
		
				$$nameSpaceID['templateVar']['nextURLQS']['val'] = $$nameSpaceID['templateVar']['nextURLQS']['tmpVal'];
				write_to_log("SUCCESS!","H4");
				write_to_log("next url calculated as: " . $$nameSpaceID['templateVar']['nextURLQS']['val'],"green");
			}else{
				write_to_log("FAIL!","H4");
				write_to_log("'".$$nameSpaceID['templateVar']['nextURLQS']['tmpVal']."' did not render a valid url." ,"red");
			}
		}//end of method 5 
		
		write_to_log("END OF TEXT METHOD 5 - TRY TO USE APP-SPECIFIC TOP LEVEL FOLDER AT SERVER DOCUMENT ROOT","end_step");
		//////////////////////////////////////////////////////////////////
 		write_to_log("TEXT METHOD 6 - TRY TO USE SERVER DOCUMENT ROOT","start_step");
		
		if($$nameSpaceID['templateVar']['nextURLQS']['val'] != ""){
			write_to_log("skipped. a previous method worked","P");
		}else{
			$$nameSpaceID['templateVar']['nextURLQS']['tmpVal'] = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' .  $_SERVER['HTTP_HOST'] .  ($_SERVER['SERVER_PORT'] != (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 443 : 80) ? ':' . $_SERVER['SERVER_PORT'] : '') ."/" ;

			if(filter_var($$nameSpaceID['templateVar']['nextURLQS']['tmpVal'], FILTER_VALIDATE_URL) != false){	
				$$nameSpaceID['templateVar']['nextURLQS']['val'] = $$nameSpaceID['templateVar']['nextURLQS']['tmpVal'];
				write_to_log("SUCCESS!","H4");
				write_to_log("next url calculated as: " . $$nameSpaceID['templateVar']['nextURLQS']['val'],"green");
			}else{
				write_to_log("FAIL!","H4");
				write_to_log("'".$$nameSpaceID['templateVar']['nextURLQS']['tmpVal']."' did not render a valid url." ,"red");
			}
		}//end of method 6

		write_to_log("END OF TEXT METHOD 6 - TRY TO USE SERVER DOCUMENT ROOT","end_step");
		//////////////////////////////////////////////////////////////////

		write_to_log("Final stubs for navigational Variables are:","H4");
		write_to_log("Current URL: ". $$nameSpaceID['templateVar']['currentURLQS']['val'],"P");
		write_to_log("Next URL: ". $$nameSpaceID['templateVar']['nextURLQS']['val'],"P");
		
		//////////////////////////////////////////////////////////////////////////////////////////////////////////////
	}

write_to_log("END OF 'PREPARE THE OUTPUT STUB FOR THIS SCRIPT'","end_step");
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
write_to_log("STEP 24 - CREATE A SAFE CONTAINER FOR CODER'S APP-SPECIFIC CODE","start_step");

	/*
	assess whether there have been any errors encountered in the template script's actions, or if there 
	are any errors with the values received by this script as arguments in case of either type of error, 
	the coder's code will not be run.  The coder must not remove this safeguard but must fix the underlying issues.
	*/
	
	//first part of assessing if there have been any errors...
	//set a flag (to be used in the following IF ) to say whether any of the variables have variable-specific errors registered against them.
	$$nameSpaceID['templateVar']['rkey']['tmpVal'] = array(); //will use this as a transitory flag that this foreach loop identified an input error in any input
	foreach(array_keys($$nameSpaceID['var']) as $$nameSpaceID['templateVar']['rkey']['val']){
		if(array_key_exists('varSpecificErrs', $$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]) && 
		count($$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]['varSpecificErrs']) > 0){
			$$nameSpaceID['templateVar']['rkey']['tmpVal'][$$nameSpaceID['templateVar']['rkey']['val']] = implode(' ',$$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]['varSpecificErrs']);
		}
	}

	//second part of assessing if there have been any errors...
	//this IF uses the transitory flag from the first part of the assessment of whether there are any errors. the flag contains a boolean true if any of the script's declared variables have a variable specific error (a problem with the value held in the variable)
	//or if the parts of the script which make up its template have encountered an error (for example, if a database connection has failed).
	if($$nameSpaceID['templateVar']['error']['val'] === ""  && count($$nameSpaceID['templateVar']['rkey']['tmpVal']) == 0){ //if there has been no error in the inputs/head section of this script...
		
		write_to_log("no errors in template so coder's app-specific code can run","P"); 
	
		if($$nameSpaceID['templateVar']['includeSecurity']['val'] == false || ($$nameSpaceID['templateVar']['includeSecurity']['val'] === true && (authenticated() === "true" || json_decode(ssoAuthenticated(), true)['ssoResponse']['a'] === 1 ))){  
			
					
			write_to_log("login required: ".($$nameSpaceID['templateVar']['includeSecurity']['val'] == true? "true" : "false"),"P");
			
			write_to_log("if login is required, one of the following must equate to true:","P");
			write_to_log("authenticated(): ".(authenticated() == "true"? "true" : "false"),"P");


//var_dump(json_decode(ssoAuthenticated(), true));
			write_to_log("ssoAuthenticated(): ".(json_decode(ssoAuthenticated(), true)['a'] === 1? "true" : "false"),"P");
			

			write_to_log("user needs to be logged on (explain)","P"); 
			write_to_log("user needs to be logged on (explain)","P"); 
			write_to_log("user needs to be logged on (explain)","P"); 
			write_to_log("START OF CODER'S APP-SPECIFIC CODE","start_step");



			try {			
write_to_log("STEP 25 - SAFE CONTAINER FOR CODER'S CODE","start_step");
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


			/*
			this is a back end script. Depending on whether you're returning a JSON object or redirecting to a URL,
			you are either:
				pushing a JSON-Formatted data payload  JSONObj['data'] OR	
				editing the query string of the URL in $$nameSpaceID['templateVar']['nextURLQS']['val'] by editing the 
				values of your variables.
			*/


			$$nameSpaceID['templateVar']['sr']['val'] = 123;  	//trigger a server response of '1' which will be returned to the front end.  the front end will look it up in the languagepack/serverResponses.php and interpret it into a 'saved' message in the appropriate human language
			echo $$nameSpaceID['templateVar']['sr']['val'];
	
			





////////////////////////////////////////////////////////////////////////////////////////////////////////////
write_to_log("END OF SAFE CONTAINER FOR CODER'S CODE","end_step");

			} catch (Throwable $interceptedError) {
				write_to_log($interceptedError->getLine() .": ERROR (Throwable) ". $interceptedError->getCode()." ". $interceptedError->getMessage()." ".$interceptedError->getTraceAsString(),"red"); 	
			} catch (Error $interceptedError) {
				write_to_log($interceptedError->getLine() .": ERROR (Error) ". $interceptedError->getCode()." ". $interceptedError->getMessage()." ".$interceptedError->getTraceAsString(),"red"); 
			} catch (Exception $interceptedError) {
				write_to_log($interceptedError->getLine() .": ERROR (Exception) ". $interceptedError->getCode()." ". $interceptedError->getMessage()." ".$interceptedError->getTraceAsString(),"red"); 
			}
			write_to_log("END OF CODER'S APP-SPECIFIC CODE","end_step");

			if($$nameSpaceID['templateVar']['outputMode']['val'] == "JSON"){
				set_error_handler('templateErrorHandler');
			}	
		}else{ //else authenticated() != true
			
			//populating the error val like this passes the notification with this scripts output 
			$$nameSpaceID['templateVar']['error']['val'] = 1; //1 = you need to be logged in to do that.
			
			write_to_log("FAIL!","h4");
			write_to_log("not logged on so coder's code not run. templateVar['includeSecurity'] is true which activates login protections","red"); 

		} //end of if authenticated() === true
	}else{	
		//end of if no error in inputs / head section of this script
		write_to_log("FAIL","H4");
		write_to_log("The coder's app-specific code has not been attempted as the following script-level errors in the template section of the code needs to be resovled first.","red");
	
		write_to_log(array("title"=>"template variables with errors:","cols"=>array("variable","error code(s)")), "start_table");
		write_to_log($$nameSpaceID['templateVar']['rkey']['tmpVal'],"TABLE_ROWS_KEYED");
	}
	
	$$nameSpaceID['templateVar']['rkey']['tmpVal'] = ""; //clear the transitory flag
	
	if($$nameSpaceID['templateVar']['outputMode']['val'] == "JSON"){	
		//temporarily convert the JSON Object to an array so i can edit it easily.
		$JSONObj = convertToArray($JSONObj); 
		
		//datestamp the JSON Object
		$JSONObj['st'] = date(DATE_RFC2822);
	
		//revert the JSON Object (currently temporarily an array for ease of editing) to a JSON Object
		$JSONObj = convertToJSON($JSONObj,__LINE__);
	}

write_to_log("END OF SAFE CONTAINER FOR CODER'S APP-SPECIFIC CODE","end_step");
//////////////////////////////////////////////////////////////////////////////////////	
write_to_log("STEP 26 - attempt to add any script-level non-user-input error to the response","start_step");
	/*
	if there's been any error, return it. also return all input values and errors associated with them as
	the front end im returning to probably is a data capture form so it can be set back up in the state the
	user submitted it in.
	*/
	if($$nameSpaceID['templateVar']['error']['val'] == ""){
		write_to_log("no script-level errors to return","p");
	}else{	
		if($$nameSpaceID['templateVar']['outputMode']['val'] === "JSON"){
			//////////////////////////////////////////////////////////////////
			write_to_log("STEP 18a JSON - START 'RETURN SCRIPT-LEVEL ERRORS","start_step");
		
			write_to_log("script level errors will have already been caught by the custom error handler and inserted into the JSON object which is returned","P");

			write_to_log("END OF JSON - RETURN SCRIPT-LEVEL ERRORS","end_step");
			//////////////////////////////////////////////////////////////////
		}else{
			/*
			default choice enforced by using 'else', meaning...
			$$nameSpaceID['templateVar']['outputMode']['val'] === "TEXT"
			*/
			/////////////////////////////////////////////////////////////////////////////////////////////////////////
			write_to_log("STEP 18b TEXT -  Add script-level non-user-input error to nextURLQS variable","start_step");
						
			write_to_log("a script level error was encountered earlier. Adding a mention of it to nextURLQS variable","P");
			$$nameSpaceID['templateVar']['nextURLQS']['val'] = replace_qs_key($$nameSpaceID['templateVar']['nextURLQS']['val'], "error", "error=" .$$nameSpaceID['templateVar']['error']['val']);
		
			write_to_log('nextURLQS is now:',"H4");
			write_to_log($$nameSpaceID['templateVar']['nextURLQS']['val'],"CODE");

			write_to_log("END OF TEXT - Add script-level non-user-input error to nextURLQS variable","end_step");
			//////////////////////////////////////////////////////////////////////////////////////////////////////////
		}
	}	

write_to_log("END OF 'attempt to add any script-level non-user-input error to the response'","end_step");
////////////////////////////////////////////////////////////////////////////////////////////////////////
write_to_log("STEP 27 - ' attempt to add any server response code the response'","start_step");

	/*
	the server response code ('SR') is, like an error code, a value passed from a back-end server-side script
	which is represented by a code, so that it is not language-specific. it can be decoded later by
	looking it up in serverResponses.php.  SR codes represent non-error neutral or posirtive replies like 
	'record successfully saved'.
	*/

	
	if(array_key_exists('sr',$$nameSpaceID['templateVar']) == false || 
	array_key_exists('val',$$nameSpaceID['templateVar']['sr']) == false ||
	empty($$nameSpaceID['templateVar']['sr']['val'])){		
		write_to_log("no server response code to return","P");
	}elseif(array_key_exists('sr',$$nameSpaceID['templateVar']) && array_key_exists('val',$$nameSpaceID['templateVar']['sr']) &&
	$$nameSpaceID['templateVar']['sr']['val'] != ""){
		if($$nameSpaceID['templateVar']['outputMode']['val'] === "JSON"){
			$JSONObj = convertToArray($JSONObj); //convert JSON to an array to make it  editable
			$JSONObj['sr'] = $$nameSpaceID['templateVar']['sr']['val'];
			$JSONObj = convertToJSON($JSONObj,__LINE__); //convert it back to JSON
		}else{
			
			
			//$$nameSpaceID['templateVar']['outputMode']['val'] === "TEXT"
			write_to_log("'sr' is set to '". $$nameSpaceID['templateVar']['sr']['val']."'","P");

			$$nameSpaceID['templateVar']['nextURLQS']['val'] = replace_qs_key($$nameSpaceID['templateVar']['nextURLQS']['val'], "sr", "sr=" .$$nameSpaceID['templateVar']['sr']['val']);
			write_to_log('nextURLQS is now:',"H4");
			write_to_log($$nameSpaceID['templateVar']['nextURLQS']['val'],"CODE");
		}
	}

write_to_log("END OF ' attempt to add any server response code the response'","end_step");	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
write_to_log("STEP 28 - Prepare anticipated arguments for return","start_step");	
	
	if($$nameSpaceID['templateVar']['outputMode']['val'] === "JSON"){
		
		//temporarily convert the JSON Object to an array so i can edit it easily.
		$JSONObj = convertToArray($JSONObj); 
		
		//cycle through the template variables to see if any should be returned with this script's output.  
		foreach(array_keys($$nameSpaceID['var']) as $$nameSpaceID['templateVar']['rkey']['val']){
			//the determinant to which vars to return or not is an argtype of 0 or above 
			if($$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]['argType'] >=0){
				
				//from the variable i'm returning, make sure the first array key i'm going to use exists
				if(!array_key_exists('varSpecificErrs', $$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']])){
					$$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]['varSpecificErrs'] = array();
				}
				// from the variable i'm returning,make sure the second array key i'm going to use exists
				if(!array_key_exists('val', $$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']])){
					$$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]['val'] = array();
				}
				//write the array's value and any variable specific errors into the JSON object (v = value, e = errors) which will be output by this script
				
				if(strtoupper($$nameSpaceID['templateVar']['rkey']['val']) == 'SSOPASSWORD'){
					$JSONObj['query'][$$nameSpaceID['templateVar']['rkey']['val']]['v'] = "********";
				}else{
					$JSONObj['query'][$$nameSpaceID['templateVar']['rkey']['val']]['v'] = $$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]['val'];
				}				
				$JSONObj['query'][$$nameSpaceID['templateVar']['rkey']['val']]['e'] = $$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]['varSpecificErrs'];
				
			} 
		}//end of loop thru every variable 	
		
		//revert the JSON Object (currently temporarily an array for ease of editing) to a JSON Object
		$JSONObj = convertToJSON($JSONObj,__LINE__);
	}else{
		//default
		//$$nameSpaceID['templateVar']['outputMode']['val'] === "TEXT"
		
		write_to_log("the return URL is being constructed inside variable \${\$nameSpaceID}['templateVar']['nextURLQS']","P");
		write_to_log("returnable variables are returned as key=value.  value has any related error codes, if there are any, prepended to it. codes are separated by the ¬ character for example: name=john¬32¬2","P");
		write_to_log(array("title"=>"","cols"=>array("variable","potentially returnable? (argType >0)", "includeInReturn= true?","has errors?","return value")),"start_table");
	
		//for every script variable
		$$nameSpaceID['templateVar']['rkey']['tmpArray'] = array();
		foreach(array_keys($$nameSpaceID['var']) as $$nameSpaceID['templateVar']['rkey']['val']){
				
			//if the varibale is an argument provided by the user or potentially provided by the user...
			//if(array_key_exists("argType",$$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]) && $$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]['argType'] >=0){
			
			if($$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]['argType'] >=0 &&
			$$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]['includeInResponse'] == true
			){
				
				//add it as a key-value pair to the querystring of the url i redirect the user to (which is likely where they came from)
				//and using a '¬' separated list, append any error codes to the value of the key-value pair so that the receiving page can 
				//pick them apart. the ¬ symbol was chosen as a separator because it has no traditional use in mainstream programming and 
				//is highly unlikely to be present within the body of a value whereas the more common comma separator is quite likely to be
				//present in string values which would then confuse processing between what is part of the value and what is a separator.
				//the '¬' symbol has been used occasionally in short-hand for probability notation but this is probably (lol) likely to be
				//a universe distinct enough from programming to cause any clashes. 
				
				//example output would be:
				//myFieldName=myErroneousValue¬111¬202
			
				
				//store the variable's value in tmpVal, converting any arrays to semi-colon delimited strings as we go
				$$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]['tmpVal'] = 
				(is_array($$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]['val'])) ? implode(";",$$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]['val']) : $$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]['val'];
								
					if(array_key_exists('varSpecificErrs',$$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]) && count($$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]['varSpecificErrs']) > 0){

						//list them in a '¬' separated list stored in the variable's tmpVal space
						$$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]['tmpVal'] .= "¬" . implode("¬",$$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]['varSpecificErrs']);
					
					
						//edit the querystring of the url to which this script will redirect out to, to contain the name-value pair of all the arguments the script should have received, carrying any input error codes with it. The script is, at this point, already looping through those arguments.
						$$nameSpaceID['templateVar']['nextURLQS']['val'] = replace_qs_key(
							$$nameSpaceID['templateVar']['nextURLQS']['val'], 
							$$nameSpaceID['templateVar']['rkey']['val'], 
							rawurlencode($$nameSpaceID['templateVar']['rkey']['val']) ."=".$$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]['DIRTY'].$$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]['tmpVal']
						);
					}else{
						//no errors
						//$$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]['tmpVal'] is ready to use
					
						//edit the querystring of the url to which this script will redirect out to, to contain the name-value pair of all the arguments the script should have received, carrying any input error codes with it. The script is, at this point, already looping through those arguments.
						$$nameSpaceID['templateVar']['nextURLQS']['val'] = replace_qs_key(
							$$nameSpaceID['templateVar']['nextURLQS']['val'], 
							$$nameSpaceID['templateVar']['rkey']['val'], 
							rawurlencode($$nameSpaceID['templateVar']['rkey']['val']) ."=". $$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]['tmpVal']
						);
					}
						
				
			} //end of if this argument, the name of which is stored in $$nameSpaceID['templateVar']['rkey']['val'], is one the script should have received, (which is indicated by 
			  //the variable's argType being zero or greater)...
	
			$$nameSpaceID['templateVar']['rkey']['tmpArray'][] = array(
				$$nameSpaceID['templateVar']['rkey']['val'],
				($$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]['argType'] >=0)?"Yes":"No",
				($$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]['includeInResponse'] >=0)?"Yes":"No",
				(array_key_exists('varSpecificErrs',$$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]) && count($$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]['varSpecificErrs']) > 0)?"Yes":"No",
				(array_key_exists('val',$$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]))? var_export($$nameSpaceID['var'][$$nameSpaceID['templateVar']['rkey']['val']]['val'], true):""
			);	
		}//end of loop thru every variable
		write_to_log($$nameSpaceID['templateVar']['rkey']['tmpArray'],"table_rows");
	
	write_to_log('nextURLQS is now:',"H4");
	write_to_log($$nameSpaceID['templateVar']['nextURLQS']['val'],"CODE");

	}//end of output is text
	
	
write_to_log("END OF 'Prepare anticipated arguments for return'","end_step");	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
write_to_log("STEP 29 - add any returnable template variables to the response","start_step");	

	if($$nameSpaceID['templateVar']['outputMode']['val'] === "JSON"){
		//temporarily convert the JSON Object to an array so i can edit it easily.
		$JSONObj = convertToArray($JSONObj); 

		//cycle through the template variables to see if any should be returned with this script's output.  
		foreach(array_keys($$nameSpaceID['templateVar']) as $$nameSpaceID['templateVar']['rkey']['val']){
			//the determinant to which vars to return or not is an argtype of 0 or above 
			if($$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]['argType'] >=0){
				
				//from the variable i'm returning, make sure the first array key i'm going to use exists
				if(!array_key_exists('varSpecificErrs', $$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']])){
					$$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]['varSpecificErrs'] = array();
				}
				// from the variable i'm returning,make sure the second array key i'm going to use exists
				if(!array_key_exists('val', $$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']])){
					$$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]['val'] = array();
				}
				//write the array's value and any variable specific errors into the JSON object (v = value, e = errors) which will be output by this script
				
				if(strtoupper($$nameSpaceID['templateVar']['rkey']['val']) == 'SSOPASSWORD'){
					$JSONObj['query'][$$nameSpaceID['templateVar']['rkey']['val']]['v'] = "********";
				}else{
					$JSONObj['query'][$$nameSpaceID['templateVar']['rkey']['val']]['v'] = $$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]['val'];
				}				
				$JSONObj['query'][$$nameSpaceID['templateVar']['rkey']['val']]['e'] = $$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]['varSpecificErrs'];
				
			} 
		}//end of loop thru every variable 	
		
		//revert the JSON Object (currently temporarily an array for ease of editing) to a JSON Object
		$JSONObj = convertToJSON($JSONObj,__LINE__);
	}else{
		//default
		//$$nameSpaceID['templateVar']['outputMode']['val'] === "TEXT"
		
		write_to_log("the return URL is being constructed inside variable \${\$nameSpaceID}['templateVar']['nextURLQS']","P");
		write_to_log("returnable variables are returned as key=value.  value has any related error codes, if there are any, prepended to it. codes are separated by the ¬ character for example: name=john¬32¬2","P");
		write_to_log(array("title"=>"","cols"=>array("variable","returnable? (argType >0)","includeInReturn= true?","has errors?","return value")),"start_table");
	
		//for every script variable
		$$nameSpaceID['templateVar']['rkey']['tmpArray'] = array();
		foreach(array_keys($$nameSpaceID['templateVar']) as $$nameSpaceID['templateVar']['rkey']['val']){
				
			//if the varibale is an argument provided by the user or potentially provided by the user...
			//if(array_key_exists("argType",$$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]) && $$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]['argType'] >=0){
			
			if($$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]['argType'] >=0
				 && $$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]['includeInResponse'] == true
				 && !in_array(strtoupper($$nameSpaceID['templateVar']['rkey']['val']), array('SSOUSERNAME','SSOPASSWORD','SSOAPPID','SSOROLEID'))			
			){
				
				//add it as a key-value pair to the querystring of the url i redirect the user to (which is likely where they came from)
				//and using a '¬' separated list, append any error codes to the value of the key-value pair so that the receiving page can 
				//pick them apart. the ¬ symbol was chosen as a separator because it has no traditional use in mainstream programming and 
				//is highly unlikely to be present within the body of a value whereas the more common comma separator is quite likely to be
				//present in string values which would then confuse processing between what is part of the value and what is a separator.
				//the '¬' symbol has been used occasionally in short-hand for probability notation but this is probably (lol) likely to be
				//a universe distinct enough from programming to cause any clashes. 
				
				//example output would be:
				//myFieldName=myErroneousValue¬111¬202
			
				
				//store the variable's value in tmpVal, converting any arrays to semi-colon delimited strings as we go
				$$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]['tmpVal'] = 
				(is_array($$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]['val'])) ? implode(";",$$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]['val']) : $$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]['val'];
								
					if(array_key_exists('varSpecificErrs',$$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]) && count($$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]['varSpecificErrs']) > 0){

						//list them in a '¬' separated list stored in the variable's tmpVal space
						$$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]['tmpVal'] .= "¬" . implode("¬",$$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]['varSpecificErrs']);
					
					
						//edit the querystring of the url to which this script will redirect out to, to contain the name-value pair of all the arguments the script should have received, carrying any input error codes with it. The script is, at this point, already looping through those arguments.
						$$nameSpaceID['templateVar']['nextURLQS']['val'] = replace_qs_key(
							$$nameSpaceID['templateVar']['nextURLQS']['val'], 
							$$nameSpaceID['templateVar']['rkey']['val'], 
							rawurlencode($$nameSpaceID['templateVar']['rkey']['val']) ."=".rawurlencode($$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]['DIRTY']).$$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]['tmpVal']
						);
					}else{
						//no errors
						//$$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]['tmpVal'] is ready to use
					
						//edit the querystring of the url to which this script will redirect out to, to contain the name-value pair of all the arguments the script should have received, carrying any input error codes with it. The script is, at this point, already looping through those arguments.
						$$nameSpaceID['templateVar']['nextURLQS']['val'] = replace_qs_key(
							$$nameSpaceID['templateVar']['nextURLQS']['val'], 
							$$nameSpaceID['templateVar']['rkey']['val'], 
							rawurlencode($$nameSpaceID['templateVar']['rkey']['val']) ."=". $$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]['tmpVal']
						);
					}
						
				
			} //end of if this argument, the name of which is stored in $$nameSpaceID['templateVar']['rkey']['val'], is one the script should have received, (which is indicated by 
			  //the variable's argType being zero or greater)...
	
			$$nameSpaceID['templateVar']['rkey']['tmpArray'][] = array(
				$$nameSpaceID['templateVar']['rkey']['val'],
				($$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]['argType'] >=0)?"Yes":"No",
				($$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]['includeInResponse'] >=0)?"Yes":"No",
				(array_key_exists('varSpecificErrs',$$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]) && count($$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]['varSpecificErrs']) > 0)?"Yes":"No",
				(array_key_exists('val',$$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]))? var_export($$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]['val'], true):""
			);	
		}//end of loop thru every variable 

		write_to_log($$nameSpaceID['templateVar']['rkey']['tmpArray'],"table_rows");
		write_to_log('nextURLQS is now:',"H4");
		write_to_log($$nameSpaceID['templateVar']['nextURLQS']['val'],"CODE");
	}//end of output is text
	
write_to_log("END OF ' add any returnable template variables to the response'","end_step");	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
write_to_log("STEP 30 - add any 'includeInResponse' variables to the response","start_step");	

	/*
	assess the 'includeInResponse' space of each variable and add to the querystring of the url which this script redirects 
	to any variables which are mandated to be passed back (where they havent already been passed back above as error-laden)
	*/
	
	write_to_log("includeInResponse variables are variables which are flagged as mandatory for this script to output as a key-value-pair in its response.
	a variable is flagged as 'includeInResponse' by setting its 'includeInResponse' attribte to true eg. \${\$nameSpaceID}['var']['chocolate']['includeInResponse'] = true.","P");
	
	write_to_log("this is commonly set when the variable is declared in either of the following sections of this script (" .__FILE__."):<UL>
	<LI>TEMPLATE VARIABLES ARE DECLARED
	<LI>CODER TO DECLARE THEIR OWN VARIABLES HERE
	</UL>","P");
	write_to_log("variables with variable-specific errors will be skipped here since in the previous step they've already been added to the return","p");
	
	if(count($$nameSpaceID['var']) >0){
		//create a temporary array of the coder's vars, containing only those with includeInResponse = true and no varSpecificErrs (because
		//variables with varSpecificErrs have already been added to the return.
		
		$$nameSpaceID['templateVar']['vKey']['tmpArray'] = array_filter($$nameSpaceID['var'], function ($variable) {
			return array_key_exists('includeInResponse',$variable) && $variable['includeInResponse'] == true 
			&& array_key_exists('varSpecificErrs',$variable) && count($variable['varSpecificErrs'])== 0;
			
			});
			
		if(count($$nameSpaceID['templateVar']['vKey']['tmpArray']) >0){
			if($$nameSpaceID['templateVar']['outputMode']['val'] === "JSON"){
				$JSONObj = convertToArray($JSONObj); //open the json for editing
			} 
			
			foreach(array_keys($$nameSpaceID['templateVar']['vKey']['tmpArray']) as $$nameSpaceID['templateVar']['vKey']['val']){

				if($$nameSpaceID['templateVar']['outputMode']['val'] === "JSON"){
					//write the array's value and any variable specific errors into the JSON object (v = value, e = errors) which will be output by this script
					if(strtoupper($$nameSpaceID['templateVar']['vKey']['val']) == 'SSOPASSWORD'){
						$JSONObj['query'][$$nameSpaceID['templateVar']['vKey']['val']]['v'] = "********";
					}else{
						$JSONObj['query'][$$nameSpaceID['templateVar']['vKey']['val']]['v'] = $$nameSpaceID['var'][$$nameSpaceID['templateVar']['vKey']['val']]['val'];
					}
				}else{
					//TEXT so add key=value to url, stopping first to flatten any value which is an array
					if(is_array($$nameSpaceID['var'][$$nameSpaceID['templateVar']['vKey']['val']]['val'])){
						$$nameSpaceID['var'][$$nameSpaceID['templateVar']['vKey']['val']]['val'] = implode(";",$$nameSpaceID['var'][$$nameSpaceID['templateVar']['vKey']['val']]['val']);
					}	
					
					$$nameSpaceID['templateVar']['nextURLQS']['val'] = replace_qs_key(
						$$nameSpaceID['templateVar']['nextURLQS']['val'], 
						$$nameSpaceID['templateVar']['vKey']['val'], 
						rawurlencode($$nameSpaceID['templateVar']['vKey']['val']) ."=". $$nameSpaceID['var'][$$nameSpaceID['templateVar']['vKey']['val']]['val']
					);
				}
			}
			if($$nameSpaceID['templateVar']['outputMode']['val'] === "JSON"){	
				$JSONObj = convertToJSON($JSONObj,__LINE__); //close the json for editing
			}
		}else{
			write_to_log("there are no includeInResponse variables","p");
		}
		unset($$nameSpaceID['templateVar']['vKey']['tmpArray']); //remove the transitory array
	}else{
		write_to_log("nothing to be 'includeInResponse' as coder has defined no variables.","P");
	}

	if($$nameSpaceID['templateVar']['outputMode']['val'] === "TEXT"){
		write_to_log('nextURLQS is now:',"H4");
		write_to_log($$nameSpaceID['templateVar']['nextURLQS']['val'],"CODE");
	}

write_to_log(" END OF Attempting to add includeInResponse variables to output'","end_step");	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
write_to_log("STEP 31 - final part of template","start_step");	

	if($$nameSpaceID['templateVar']['outputMode']['val'] === "JSON"){
		$JSONObj = convertToArray($JSONObj);
		if(str_replace("\\'","'",addslashes(ob_get_clean())) != ""){
			$commentary[] = __LINE__.": uncaptured output: ". str_replace("\\'","'",addslashes(ob_get_clean())); //slashes in a way that i can store any of PHP's self-generated error HTML output in JSON !!! should really convert non-utf8 chars also!
		}
		
		if($$nameSpaceID['templateVar']['debugging']['val'] === true){
			if(!array_key_exists('c',$JSONObj)){
				$JSONObj['c'] = array();
			}
			
			if(isset($commentary) && array_key_exists('buffer',$commentary) && count($commentary['buffer'])>0){
				//loop thru the buffer calling a separate instance of this function to clear the buffer into the output
				while (count($commentary['buffer'])>0) {
					$record = array_shift($commentary['buffer']); // Removes and returns the first element
					write_to_log($record['value'],$record['type'], array('checkBuffer' => false, 'lineNumberOverride'=> $record['lineNumberOverride']));
				}
				//delete the buffer
					if(count($commentary['buffer']) == 0){
						unset($commentary['buffer']);
				}	
			}
			
			
			
			
			if($$nameSpaceID['templateVar']['debugging']['val'] == true){
				$commentary[] = __LINE__ . ": END OF final part of template.";
				$commentary[] = __LINE__ . ": END OF SCRIPT";
			}
			
			
			
			$JSONObj['c'] =  $commentary;	
		}	
		echo convertToJSON($JSONObj,__LINE__); //this should be the only echo or other output in the script when not in debug mode
		restore_error_handler(); 
	}else{
		//default 
		//$$nameSpaceID['templateVar']['outputMode']['val'] === "TEXT"
		
		//populate tmpVal on nextURLQS and currentURLQS with a reformatted version of the urls which only goes as far as the filename. no query string. 
		$$nameSpaceID['templateVar']['nextURLQS']['parsed'] = parse_url($$nameSpaceID['templateVar']['nextURLQS']['val']);
		$$nameSpaceID['templateVar']['nextURLQS']['tmpVal'] = ($$nameSpaceID['templateVar']['nextURLQS']['parsed']['scheme'] ?? 'nextURL-scheme') . '://' . ($$nameSpaceID['templateVar']['nextURLQS']['parsed']['host'] ?? '') . (isset($$nameSpaceID['templateVar']['nextURLQS']['parsed']['port']) ? ':' . $$nameSpaceID['templateVar']['nextURLQS']['parsed']['port'] : '') . ($$nameSpaceID['templateVar']['nextURLQS']['parsed']['path'] ?? '');			
		$$nameSpaceID['templateVar']['currentURLQS']['parsed'] = parse_url($$nameSpaceID['templateVar']['currentURLQS']['val']);
		$$nameSpaceID['templateVar']['currentURLQS']['tmpVal'] = ($$nameSpaceID['templateVar']['currentURLQS']['parsed']['scheme'] ?? 'nextURL-scheme') . '://' . ($$nameSpaceID['templateVar']['currentURLQS']['parsed']['host'] ?? '') . (isset($$nameSpaceID['templateVar']['currentURLQS']['parsed']['port']) ? ':' . $$nameSpaceID['templateVar']['currentURLQS']['parsed']['port'] : '') . ($$nameSpaceID['templateVar']['currentURLQS']['parsed']['path'] ?? '');

		if($$nameSpaceID['templateVar']['debugging']['val'] == true){

			write_to_log("when not in debugging mode, this script has an anti-looping defence which
			steps in if the script thinks that the current web page is the same file as the Next
			url which this script will redirect to","P");
			write_to_log("current url: ".$$nameSpaceID['templateVar']['currentURLQS']['tmpVal'],"P");
			write_to_log("   next url: ".$$nameSpaceID['templateVar']['nextURLQS']['tmpVal'],"P");		
			write_to_log("The script's mode is set to TEXT so, if not for debugging being enabled, the user would be forwarded to the following URL:","H4");
			write_to_log('<a href="'.$$nameSpaceID['templateVar']['nextURLQS']['val'].'">'.urldecode($$nameSpaceID['templateVar']['nextURLQS']['val']).'</a>',"CODE");
			write_to_log("you may simulate that by clicking the link.","P");

		}else{
			//if not debugging...
			if (str_replace('\\', '/', __FILE__) == $_SERVER['SCRIPT_FILENAME']) {
				//this comparison should not consider the query string just the url
				if($$nameSpaceID['templateVar']['nextURLQS']['tmpVal'] !== $$nameSpaceID['templateVar']['currentURLQS']['tmpVal']){
		
					if(isset($prevNameSpaceID) === true && $prevNameSpaceID != ""){
						$nameSpaceID = $prevNameSpaceID;
					}
					header("Location: ".$$nameSpaceID['templateVar']['nextURLQS']['val']);
					exit();
				}else{
					//make this present the error properly
					write_to_log("infinite loop prevented:","red");
					write_to_log("current URL: '". $$nameSpaceID['templateVar']['currentURLQS']['val']."'","P");
					write_to_log("next URL: '". $$nameSpaceID['templateVar']['nextURLQS']['val'] ."'","P");
					write_to_log("auto redirect to next URL prevented because it is the same as current URL","P");	
				}
			} //end of if this is not being run as an include

		} // end of if debugging	
		
		
		write_to_log("END OF final part of template. ","end_step");
		write_to_log("END OF SCRIPT","end_step");
		
		if($$nameSpaceID['templateVar']['debugging']['val'] == true){
			echo "\t".'</body>'.PHP_EOL;
			echo '</html>';
		}
		
	}
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	

	
	
if(isset($prevNameSpaceID) === true && $prevNameSpaceID != ""){
	$nameSpaceID = $prevNameSpaceID;
}
	
?>