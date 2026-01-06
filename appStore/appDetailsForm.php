<?php
/************************************************************ 
PROJECT NAME:  design
FILE NAME   :  index.php
PHP VERSION :  8.3.14
template ver:  1.0

FILE DESCRIPTION:
compliant with the phpSample app, this page is based on the component https://localhost/design/components/pages/TLMB_Index/
which gives an index page suitable for components to be built into it  from the design components library. 
The format of this particular index page component is TLMB meaning there is:
 a top panel (holding the stadard gov.uk page header with our login form embedded),
 a left panel (holding a left-hand-side navigation menu)
 a main panel (holding the main contents of the Page)
 a bottom panel(holding a lightly tailored standard gov.uk footer) 

OUTPUT:
HTML full document or <form> and supporting content only if included into an existing DOM document 

VER:   DATE:     INITIALS:  DESCRIPTION OF CHANGE:
1.0    28/09/25  AB         Initial Version

**************************************************************/


//to do - review if all vars are needed
//write_to_log - need to cache to $commentary until i'm in a position to create the write to log FUNCTION
//within the namespace or better to include the file after i know server document root
//figure out js things like top window displaying login prompt popup for a child?


session_start();



//a true 'global', potentially spanning namespaces if scripts are included in others, used to collect what would traditionally be screen-based output
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
	
	
	//write_to_log($_SERVER,"DETAILS");
	$commentary['buffer'][] = array('value'=>$_SERVER, 'type'=>'DETAILS');


//write_to_log("END OF 'ASSURE \$_SERVER ARRAY IS POPULATED'","end_step");	
$commentary['buffer'][] = array('value'=>"END OF 'ASSURE \$_SERVER ARRAY IS POPULATED'", 'type'=>'end_step');




	//now i know server document root, i can include fundamental template-supporting functions.
    include_once( $_SERVER['DOCUMENT_ROOT']. "/klogin/corePHPFunctions.php");
	//now this is in, i can stop buffering debug info to $commentary and send it straight to write_to_log()


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
	
	//write_to_log($_REQUEST,"DETAILS");
	$commentary['buffer'][] = array('value'=>$_REQUEST, 'type'=>'DETAILS');


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
	
	//write_to_log('namespace id: '. $nameSpaceID ,'P');
	$commentary['buffer'][] = array('value'=>'namespace id: '. $nameSpaceID , 'type'=>'p');

write_to_log("END OF 'COMPUTE A NAMESPACE","end_step");
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
write_to_log("STEP 4 - 'RETRIEVE ANY CLI ARGUMENTS","start_step"); 


	write_to_log(
	"Merge any command line argument name-value pairs into the request array so they can be handled
	the same as all possible argument feeds - like the traditional \$_REQUEST array.
	this should be done early, before the \$_REQUEST array is referred to which gives scripts a 
	good chance to work from equally from the command line or a browser"
	,"P");
	/*$commentary['buffer'][] = array('value'=>"Merge any command line argument name-value pairs into the request array so they can be handled
	the same as all possible argument feeds - like the traditional \$_REQUEST array.
	this should be done early, before the \$_REQUEST array is referred to which gives scripts a 
	good chance to work from equally from the command line or a browser" , 'type'=>'p');
	*/
	
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
	

	/*
		argType = adds additional information on how to process the variable. 
	   -1.  if 'argType' attribute has a value of -1, the variable is internal to this script neither delivered to it via argc or by the URI ($_REQUEST array).
		0. the variable is to be provided to the script from outside (eg. by argv or in $_REQUEST) but the script will not error if it is not provided. there are many reasons why this may be desirable - perhaps because the variables absence from the user-supplied arguments is informative to the script, or perhaps because the value doesnt NEED to come from the user as it can fall back on a default declared in this script. 
		1. the variable is to be provided to the script from outside (eg by argv or in $_REQUEST) and is mandatory.the script will error if it is not provided.
	*/
	

	
	$commentary[]= "nameSpaceID = ". $nameSpaceID;
	
	$$nameSpaceID['templateVar']['debugging'] = array("val"=> false, "DIRTY" => "", "tmpVal" => "", "argType" => -1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => "
	BELONGS TO THE TEMPLATE.  toggle debugging mode. debugging mode outputs 	debug info the developer things are useful for their self and future developers to help them to troubleshoot.  Developers should set this to true when troubleshooting. and, when deveoping,  should wrap any output intended for debugging developers in an 'if' which checks that this value is true. When in debug mode, any redirects should be wrapped in an 'if not debugging' statement to prevent the redirection, leaving debug info displayed");
	
	$$nameSpaceID['templateVar']['outputMode'] = array("val"=> (function(){
		
		//if this is the top level document, i should expect to be able to send document headers here (for the first time).
		if (str_replace('\\', '/', __FILE__) == $_SERVER['SCRIPT_FILENAME']) {
	
			//if i want this script to output something other than XML (html), this is where to branch, and where relevant document headers would be sent
			header("Cache-Control: no-cache");
			header('X-Content-Type-Options: nosniff');  // to avoid IE sniffing (penetration testing 18/12/13)
			header("Expires: -1");
		}
		
		global $commentary;
		
		return "TEXT";
		
	})(), //end of self-invoking function to set db_conn
	"DIRTY" => "", "tmpVal" => "", "argType" => -1, "mval"=> false, "count" => 0,"varSpecificErrs" => array(), "includeInResponse" => false, "info" => "
	BELONGS TO THE TEMPLATE.  sets the document up to output text (html). if i wanted to output alternative document types, this is where the decision
	is informed/calculated, and branching to the relevent document headers would happen. 
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
<html lang="en-GB" dir="ltr" class="govuk-template govuk-template--rebranded">
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
	
	$$nameSpaceID['templateVar']['r2lLocaleScripts']  = array("val"=> array('Arab','Hebr','Nkoo','Syrc','Thaa','Tfng'), "argType" => -1, "mVal"=> true, "count" => 6 , "varSpecificErrs" => array(), "includeInResponse" => false, "info" => 
	"BELONGS TO THE TEMPLATE.  a reference list of locales with language scripts which are written right-to-left. when the 'locale' variable is set, it is compared to this list. if it is found in it, the separate 'r2l' variable is set to true.
	this can then be used in the html to assure a right-to-left document layout"
	);
	
	$$nameSpaceID['templateVar']['r2l'] =  array("val"=> false, "argType" => -1, "mVal"=> false, "count" => 0 , "varSpecificErrs" => array(), "includeInResponse" => false, "info" => 
	"BELONGS TO THE TEMPLATE.  a flag which should be  set to true when the 'locale' variable changes, and is found in the list of langauges which are written right-to-left. that list of languages is stored in 'r2lLocaleScripts' variable"
	);
	
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

	$$nameSpaceID['templateVar']['error'] = array("val"=> "", "DIRTY" => "",  "tmpVal" => "", "argType" => 0, "mval"=> false, "count" => -1, "varSpecificErrs" => array(),"includeInResponse" => false, "info" => "
	BELONGS TO THE TEMPLATE.  used as a flag for the script to say that an error has/has not been encountered. in non-complex scripts it might hold the error code/ message with no value (a blank string) indicating that no error has been encountered. Generally don't expect to return the content of this to the user (unless user is a debugging developer)... Generally, instead, each anticipated user-supplied argument will store within its { nameSpaceID.'_var'}['xxxxxx']['varSpecificErrs'] array, a list of error codes intended to be returned to the user where the front-end code will de-code them into an error message in the appropriate human langauge.
	Where an internal code error e.g SQL error has been encountered, it will be written into this variable. 
	");
	
	$$nameSpaceID['templateVar']['errorInt'] = array("val"=> 0, "DIRTY" => "","tmpVal" => "", "argType" => -1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "
	where the error variable is found to hold a decimal fraction number eg 3.9, this holds the 3
	");
	
	$$nameSpaceID['templateVar']['errorDec'] = array("val"=> 0, "DIRTY" => "", "tmpVal" => "", "argType" => -1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "
	where the error variable is found to hold a decimal fraction number eg 3.9 this holds the 9
	");
	
		
	$$nameSpaceID['templateVar']['fieldValidationsNameSpace'] = array("val"=> "NS_".explode("/",str_replace("\\", "/", substr(realpath(__FILE__),strlen($_SERVER["DOCUMENT_ROOT"])+1)))[0]."_languagePacks_".str_replace("-","",$$nameSpaceID['templateVar']['loc']['val'])."_fieldValidationFunctions_php",
	"DIRTY" => "", "tmpVal" => "", "argType" => -1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => "
	uses __FILE__ to calculate the namespace for the fieldValidationFunctions.php associated with the current locale for this app. fieldValidationFunctions.php is where validation functions for inputs to scripts in this app ought to be stored. this will be refenced later to call the functions, ensuring that the call goes to the function in the correct namespace.
	");
	
	$$nameSpaceID['templateVar']['invalidCharacters']  = array("val"=>  array("'", "\"", "<", ">", "/", "\\", ";", ":", "`", "{", "}"), "argType" => -1, "mVal"=> true, "count" => 11, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => 
	"BELONGS TO THE TEMPLATE.  dirty values received from the querystring/posted, like previously rejected inputs submitted for processing or bad values to display in debug messages need to be cleaned enough to ensure no foul play before being presented ons-screen for human review. these are the characters which will be stripped. probably not bullet proof but maybe good enough for now.
	");
	
	$$nameSpaceID['templateVar']['sr'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "",  "argType" => 0, "mval"=> false, "count" => 0, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => "
	BELONGS TO THE TEMPLATE.  used as a non-error 'good' response code from the server like 'your input was successfully saved' 
	");
	
	$$nameSpaceID['templateVar']['srInt'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "",  "argType" => -1, "mval"=> false, "count" => 0, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => "
	BELONGS TO THE TEMPLATE.  
	where the sr variable is found to hold a decimal fraction number eg 1.6, this holds the 1
	");
	
	$$nameSpaceID['templateVar']['srDec'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "",  "argType" => -1, "mval"=> false, "count" => 0, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => "
	BELONGS TO THE TEMPLATE.  
	where the sr variable is found to hold a decimal fraction number eg 1.6, this holds the 6
	");
	
	$$nameSpaceID['templateVar']['srTimeElapsed'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "",  "argType" => -1, "mval"=> false, "count" => 0, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => "
	BELONGS TO THE TEMPLATE.  
	where the sr varaible is found to hold a decimal fraction and is split at teh decimal point srInt.srDec and srDec contains a unixtimestamp, the number of seconds elapsed between that timestamp and now can be recorded here
	");
	
	
	$$nameSpaceID['templateVar']['JSONErrors'] = array("val"=> array(), "DIRTY"=> "", "argType" => -1, "mVal"=> false, "count" => 0, "includeInResponse" => false, "info" => "
	BELONGS TO THE TEMPLATE.  when the query string is processed, any key-value-pair which the script expects (has a corresponding variable) to receive has its value checked. to see if it is a string delimited by the ¬ symbol. if it is, the first value is treated as an invalid value (such as one provided by the user which was rejected when checked by a server side script before being returned to the user) 
	and the subsequent portions of the ¬delimited string are expected to be integers representing error codes. which say why the first value is invalid. This JSONErrros value forms a structure into which the errors are divided. it may no longer be necesary
	as the errors are now going to be moved into the 'varSpecificErrs' part of each corresponding variable.
	but keeping for now
	");

	
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
	
	$$nameSpaceID['templateVar']['sso_link'] =  array( "val" => array("db_conn_file" =>  $_SERVER['DOCUMENT_ROOT']."/klogin/sso_database.php"),
	"argType" => -1, "mVal"=> true, "count" => 0, "varSpecificErrs" => array(), "includeInResponse" => false, "info" => 
	"BELONGS TO THE TEMPLATE.  the variable holds a value 'db_conn_file' set by the coder to point to a php file which establishes a connection
	to the Analysis Dir Single Signon  database to give this script the ability to verify a user against that login system.
	in this template, the Analysis Dir Single Signon system is used to authenticate a user IF THEY ARE USING THIS SCRIPT TO 
	REQUEST AN API RESPONSE.  for non-api authenticaton, the klogin_link (above) is used. There are two separate signon systems at present
	because the Analysis Dir Single Signon is being phased out and we are using klogin to phase toward a mod-wide SSO.

	the setting of the value db_conn, below, uses an anonymous function which includes (and so runs) the php file
	named above, which subsequently sets the other values including db_conn, sso_database etc.  db_conn is then the useable reference to the 
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

	$$nameSpaceID['templateVar']['sql']['query'] = "SELECT  `id`, `shortName`, `medname`, `shortDescription`, `protocol`, `devDomain`, `testDomain`, `prodDomain` , `appRoot`, `homePage`,`legalAndPolicyLink` FROM `%s`.`apps_%s` WHERE appRoot = ? LIMIT 1";
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
	
	
	
	
	$$nameSpaceID['var']['sampleFormText'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, in this instance it is to be associated with a text input");
	$$nameSpaceID['var']['sampleFormCheckbox'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 0, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script. in this instance it is to be associated with a checkbox input");

	
	/*
	$$nameSpaceID['var']['sampleFormTextArea'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script. in this instance it is to be associated with a text area input");
	$$nameSpaceID['var']['sampleFormColour'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script. in this instance it is to be associated with a colour selection input");
	$$nameSpaceID['var']['sampleFormInteger'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script. in this instance it is to be associated with a colour selection input");
	$$nameSpaceID['var']['sampleFormCheckbox'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script. in this instance it is to be associated with a checkbox input");
	$$nameSpaceID['var']['sampleFormOption'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script. in this instance it is to be associated with a checkbox input");
	$$nameSpaceID['var']['sampleFormTel'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, to be associated with a text input");
	$$nameSpaceID['var']['sampleFormEmail'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, to be associated with a text input");
	$$nameSpaceID['var']['sampleFormPassword'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, to be associated with a text input");
	$$nameSpaceID['var']['sampleFormURL'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, to be associated with a text input");
	$$nameSpaceID['var']['sampleFormButton'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, to be associated with a text input");
	$$nameSpaceID['var']['sampleFormDate'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, to be associated with a text input");
	$$nameSpaceID['var']['sampleFormDateTimeLocal'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, to be associated with a text input");
	$$nameSpaceID['var']['sampleFormRadio'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, to be associated with a text input");
	$$nameSpaceID['var']['sampleFormImage'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, to be associated with a text input");
	$$nameSpaceID['var']['sampleFormFile'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, to be associated with a text input");
	$$nameSpaceID['var']['sampleFormRange'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, to be associated with a text input");
	$$nameSpaceID['var']['sampleFormReset'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, to be associated with a text input");
	$$nameSpaceID['var']['sampleFormWeek'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "includeInResponse" => true, "info" => "an example of a variable set in the template script, to be associated with a text input");
	*/

	
	
	
	
	
	
	
	
	write_to_log($$nameSpaceID['var'],"DETAILS");
	
write_to_log("END OF 'CODER TO DECLARE OWN VARIABLES'","END_STEP");
///////////////////////////////////////////////////////////////////////////////////////////////////////////	
write_to_log("STEP 16 - TRY TO START/RESUME SERVER SESSION","start_step");
	
	if(session_status() === PHP_SESSION_NONE){
	
		//these first 2 lines are to allow the session cookie to work when called from an iframe - needs PHP 7.3 or higher allegedly
		ini_set('session.cookie_samesite', 'None');			//https://stackoverflow.com/questions/64023550/sessions-are-not-working-when-the-site-is-called-by-an-iframe
		session_set_cookie_params(['samesite' => 'None']);	//https://stackoverflow.com/questions/64023550/sessions-are-not-working-when-the-site-is-called-by-an-iframe
		session_start();

		date_default_timezone_set($$nameSpaceID['var']['userLocalTimeZone']['val']); //might be set to something else later by recived arguments/user settings 
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
write_to_log("STEP 18 - SETTING COMMON DOCUMENT HEADERS","start_step");

	
	//write the document headers which are necessary regardless of whether the document 
	//produced is JSON or HTML but only do this if this script is not called as an include/require
	
	/*
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
*/

write_to_log("END OF 'SETTING COMMON DOCUMENT HEADERS'","END_STEP");
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
				
				//check if the query string assserts that there are input errors with the field
				if(strpos($$nameSpaceID[$$nameSpaceID['templateVar']['rkey']['storageArea']][$$nameSpaceID['templateVar']['rkey']['val']]['DIRTY'], "¬")){
				
			
					$$nameSpaceID[$$nameSpaceID['templateVar']['rkey']['storageArea']][$$nameSpaceID['templateVar']['rkey']['val']]['DIRTY'] = explode("¬",$$nameSpaceID[$$nameSpaceID['templateVar']['rkey']['storageArea']][$$nameSpaceID['templateVar']['rkey']['val']]['DIRTY']);
					
					
					
					$$nameSpaceID[$$nameSpaceID['templateVar']['rkey']['storageArea']][$$nameSpaceID['templateVar']['rkey']['val']]['count'] = count($$nameSpaceID[$$nameSpaceID['templateVar']['rkey']['storageArea']][$$nameSpaceID['templateVar']['rkey']['val']]['DIRTY']);
					$$nameSpaceID[$$nameSpaceID['templateVar']['rkey']['storageArea']][$$nameSpaceID['templateVar']['rkey']['val']]['varSpecificErrs'] = array_slice($$nameSpaceID[$$nameSpaceID['templateVar']['rkey']['storageArea']][$$nameSpaceID['templateVar']['rkey']['val']]['DIRTY'],1);
							
					for($i=0;$i< count($$nameSpaceID[$$nameSpaceID['templateVar']['rkey']['storageArea']][$$nameSpaceID['templateVar']['rkey']['val']]['varSpecificErrs']);$i++){
						if(isset($errors[$$nameSpaceID[$$nameSpaceID['templateVar']['rkey']['storageArea']][$$nameSpaceID['templateVar']['rkey']['val']]['varSpecificErrs'][$i]])){
							$$nameSpaceID[$$nameSpaceID['templateVar']['rkey']['storageArea']][$$nameSpaceID['templateVar']['rkey']['val']]['varSpecificErrs'][$i] = $errors[$$nameSpaceID[$$nameSpaceID['templateVar']['rkey']['storageArea']][$$nameSpaceID['templateVar']['rkey']['val']]['varSpecificErrs'][$i]]['msg'];
						}else{
							$$nameSpaceID[$$nameSpaceID['templateVar']['rkey']['storageArea']][$$nameSpaceID['templateVar']['rkey']['val']]['varSpecificErrs'][$i] = $errors[57]['msg'] . ' '. $$nameSpaceID[$$nameSpaceID['templateVar']['rkey']['storageArea']][$$nameSpaceID['templateVar']['rkey']['val']]['varSpecificErrs'][$i];//unrecognized error code
						} 
						
						
						//this creates teh wrong format for the JSONErrors string. it should be 
						//[{"name":["errmsg1","errmsg2"],"name2":["errmsg3"]}
						
						$$nameSpaceID['templateVar']['JSONErrors']['val'][$$nameSpaceID['templateVar']['rkey']['val']][] =$$nameSpaceID[$$nameSpaceID['templateVar']['rkey']['storageArea']][$$nameSpaceID['templateVar']['rkey']['val']]['varSpecificErrs'][$i];
						
					}

					
					//as the value for this field has been 'rejected' by the back end, i want to re-present the value but i am right to be suspicious about it so before accepting it out of the DIRTY form, strip any questionable characters from it. target the 0'th element of DIRTY to isolate the objectionable value only, not the ¬ separated error values
					$$nameSpaceID[$$nameSpaceID['templateVar']['rkey']['storageArea']][$$nameSpaceID['templateVar']['rkey']['val']]['val'] = str_replace($$nameSpaceID['templateVar']['invalidCharacters']['val'], "",$$nameSpaceID[$$nameSpaceID['templateVar']['rkey']['storageArea']][$$nameSpaceID['templateVar']['rkey']['val']]['DIRTY'][0]);
					
					write_to_log("ERROR in value for ". $$nameSpaceID['templateVar']['rkey']['val']. implode(", ",$$nameSpaceID[$$nameSpaceID['templateVar']['rkey']['storageArea']][$$nameSpaceID['templateVar']['rkey']['val']]['varSpecificErrs']),"P");

			
				
				}else{ //no errors are being asserted by the querystring.
				
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
////////////////////////////////////////////////////////////////////////////////////////




							if($$nameSpaceID['templateVar']['rkey']['val'] == 'sr'){
								//if the key of the request array key-value-pair currently being considered is 'sr' (server response code) then split
								//the code at the decimal point, if there is one. the integral part (left of the decimal point) should be an integer
								//corresponding to a server response code stored in the serverResponses array and the fractional part (to the right of the decimal point), if 
								//present, can be a data payload number, for example a timestamp from the server or anything else the developer wishes to use it for.
								$$nameSpaceID['templateVar']['srInt']['val'] = intval($$nameSpaceID['templateVar']['sr']['DIRTY']);			

								if((is_numeric($$nameSpaceID['templateVar']['srInt']['val']) && $$nameSpaceID['templateVar']['srInt']['val'] > 0)){
									if(isset($serverResponses[$$nameSpaceID['templateVar']['srInt']['val']]['msg'])){
										$$nameSpaceID['templateVar']['sr']['val'] = $serverResponses[$$nameSpaceID['templateVar']['srInt']['val']]['msg'];
									}else{
										$$nameSpaceID['templateVar']['sr']['val'] = $errors[59]['msg'] . ' ' . $$nameSpaceID['templateVar']['srInt']['val']; //"unrecognized response from server"
									}
									if(strpos($$nameSpaceID['templateVar']['sr']['DIRTY'],".") !== false){	
										$$nameSpaceID['templateVar']['srDec']['val'] = substr(($$nameSpaceID['templateVar']['sr']['DIRTY']),strlen($$nameSpaceID['templateVar']['srInt']['val'])+1);
										if($$nameSpaceID['templateVar']['srDec']['val']< time()){
											$$nameSpaceID['templateVar']['srTimeElapsed']['val'] = strtotime('now') - strtotime('@'.$$nameSpaceID['templateVar']['srDec']['val']);
										}
									}
								}else{
									$$nameSpaceID['templateVar']['sr']['val'] = $errors[59]['msg']; //"unrecognized response from server"
								}
							}elseif($$nameSpaceID['templateVar']['rkey']['val'] == 'error'){
								
								if($$nameSpaceID['templateVar']['error']['val'] != ""){
									$$nameSpaceID['templateVar']['errorInt']['val'] = intval($$nameSpaceID['templateVar']['error']['DIRTY']);				
									if((is_numeric($$nameSpaceID['templateVar']['errorInt']['val']) && $$nameSpaceID['templateVar']['errorInt']['val'] > 0)){
										if(isset($errors[$$nameSpaceID['templateVar']['errorInt']['val']]['msg'])){
											$$nameSpaceID['templateVar']['error']['val'] = $errors[$$nameSpaceID['templateVar']['errorInt']['val']]['msg'];
										}else{
											$$nameSpaceID['templateVar']['error']['val'] = $errors[59]['msg']; //"unrecognized response from server"
										}
									}else{
										$$nameSpaceID['templateVar']['error']['val'] = $errors[59]['msg']; //"unrecognized response from server"
									}
								}
							}						
						
					
///////////////////////////////////////////////////////////////////////////////////////						
						}else{
							
							$$nameSpaceID[$$nameSpaceID['templateVar']['rkey']['storageArea']][$$nameSpaceID['templateVar']['rkey']['val']]['varSpecificErrs'][] = $e; //$e holds the error code at this point, which is going to be returned to user.
								
							write_to_log("argument '".$$nameSpaceID['templateVar']['rkey']['val']."' was expected, but failed validation by 'check".$$nameSpaceID['templateVar']['rkey']['val']."()' (likely defined in '".$_SERVER['DOCUMENT_ROOT']. "/".$$nameSpaceID['templateVar']['appInfo']['val']['appRoot'] ."/languagePacks/".$$nameSpaceID['templateVar']['loc']['val']."/fieldValidationFunctions.php'), returning error code ".$e .".","P");
							
							if(is_array($errors) && array_key_exists($e,$errors)){
								write_to_log("code ". $e. " = '". $errors[$e]."'. decoded by this app's 'serverResponses.php'","P");
							}else{
								write_to_log("code '". $e.  "' is undefined in 'serverResponses.php'. Either adjust 'check" . $$nameSpaceID['templateVar']['rkey']['val'] . "()' or define the code in serverResponses.php.","P");
							}
						}
					}else{	
						
						write_to_log("no validation function 'check".$$nameSpaceID['templateVar']['rkey']['val']."()' for argument '".
						$$nameSpaceID['templateVar']['rkey']['val']. " (expected in '".$_SERVER['DOCUMENT_ROOT']."/".$$nameSpaceID['templateVar']['appInfo']['val']['appRoot'] .'/languagePacks/'.$$nameSpaceID['templateVar']['loc']['val']."/fieldValidationFunctions.php') 
						so argument will pass through this script, unused","red");

					}
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
		step (as part of nameSpaceID['var']). A declared 'argType' of 1 requires the script to  have an argument 
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
	the script is working to produce an HTML front end user interface
	which will either run as an independent full html5 document With
	a head and body or as just the contents of the body if this script
	detects that the page is being included inside an existing document
	*/
	
if (str_replace('\\', '/', __FILE__) == $_SERVER['SCRIPT_FILENAME']) { ?>
<!DOCTYPE html>
<html lang="<?php echo $$nameSpaceID['templateVar']['loc']['val'];?>" <?php if($$nameSpaceID['templateVar']['r2l']['val'] == true){echo 'dir="rtl"';}?>  style="background-color: #f3f2f1" class="govuk-template govuk-template--rebranded">
<head>
	<meta charset="utf-8">
	<meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
	<meta content="Default page" name="description">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" >
	<meta name="theme-color" content="#0b0c0c">
	<meta name="robots" content="noindex, nofollow">

	<title><?php echo $$nameSpaceID['DOM']['title']['innerHTML'] ?? 'missing title' ;?></title>

	<link rel="icon" href="/<?php echo $$nameSpaceID['templateVar']['appInfo']['val']['appRoot'];?>/favicon.ico" type="image/x-icon" >
	<!--for the moment, bootstrap is used for the modal lightbox popups-->
	<link rel="stylesheet" href="/klogin/style/bootstrap.min.css">
	<link rel="stylesheet" href="/klogin/style/bootstrapFixes.css"><!--my own fixes to bootstrap-->
	
	
<!--choose from one of the styles :)-->
<!--<link href="/<?php //echo $$nameSpaceID['templateVar']['appInfo']['val']['appRoot'];?>/stylesheets/gov.uk.css" rel="stylesheet" media="all">-->


<!--this responsiveLayout.css contains lots of junk which is replaced by govuk-frontend-5.13.0.min.css but also the critical instructions on page Layout
which needs to be extracted carefully into its own css file-->
<link href="/<?php echo $$nameSpaceID['templateVar']['appInfo']['val']['appRoot'];?>/stylesheets/responsiveLayout.css" rel="stylesheet" >
<link href="/<?php echo $$nameSpaceID['templateVar']['appInfo']['val']['appRoot'];?>/stylesheets/govuk-frontend-5.13.0.min.css" rel="stylesheet" >
<!--this comes after the govuk-frontend css to allow overrides -eg internationalization-->
<link href="/<?php echo $$nameSpaceID['templateVar']['appInfo']['val']['appRoot'];?>/stylesheets/mod-dos-template.css" rel="stylesheet" >

<!--<link href="/klogin/style/standard.css"  rel="Stylesheet" type="text/css" >-->
	
	<link rel="icon" sizes="48x48" href="/<?php echo $$nameSpaceID['templateVar']['appInfo']['val']['appRoot'];?>/assets/rebrand/images/favicon.ico">
	<link rel="icon" sizes="any" href="/<?php echo $$nameSpaceID['templateVar']['appInfo']['val']['appRoot'];?>/assets/rebrand/images/favicon.svg" type="image/svg+xml">
	<link rel="mask-icon" href="/<?php echo $$nameSpaceID['templateVar']['appInfo']['val']['appRoot'];?>/assets/rebrand/images/govuk-icon-mask.svg" color="#0b0c0c">
	<link rel="apple-touch-icon" href="/<?php echo $$nameSpaceID['templateVar']['appInfo']['val']['appRoot'];?>/assets/rebrand/images/govuk-icon-180.png">
	<link rel="manifest" href="/<?php echo $$nameSpaceID['templateVar']['appInfo']['val']['appRoot'];?>/assets/rebrand/manifest.json">
		
	
	<script src="/klogin/js/jquery.min.js"></script>
	<!--for the moment, bootstrap is used for the modal lightbox popups-->
	<script src="/klogin/js/bootstrap.min.js"></script>
	<script src="/klogin/js/EventSourcePolyfil.js"></script>
	<script src="/klogin/js/moment-with-locales.min.js"></script>
	<script src="/klogin/js/moment-timezone-with-data-2012-2022.min.js"></script>
	<script src="/klogin/js/functionLibrary.js"></script>
	
	

	
	

	<?php if(isset($$nameSpaceID['templateVar']['r2l']['val']) && $$nameSpaceID['templateVar']['r2l']['val']){ ?>
	<style>
		* {direction: rtl;unicode-bidi: bidi-override;}
		input[type="text"]:-moz-placeholder {unicode-bidi: bidi-override;}
		input[type="text"]:-ms-input-placeholder {unicode-bidi: bidi-override;}
		input[type="text"]::-webkit-input-placeholder {unicode-bidi: bidi-override;}
	</style>
	<?php } ?>
			
			</head>
			<body class="govuk-template__body">
				<script>document.body.className += ' js-enabled' + ('noModule' in HTMLScriptElement.prototype ? ' govuk-frontend-supported' : '');</script>

	
<?php } //end of if this script is delivering the page's parent DOM	?>

<?php include_once($_SERVER['DOCUMENT_ROOT'] ."/". $$nameSpaceID['templateVar']['appInfo']['val']['appRoot']."/javascripts/formAutomationJS.php"); ?>




<?php
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
	foreach(array_keys($$nameSpaceID['templateVar']) as $$nameSpaceID['templateVar']['rkey']['val']){
		if(array_key_exists('varSpecificErrs', $$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]) && 
		count($$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]['varSpecificErrs']) > 0){
			$$nameSpaceID['templateVar']['rkey']['tmpVal'][$$nameSpaceID['templateVar']['rkey']['val']] = implode(' ',$$nameSpaceID['templateVar'][$$nameSpaceID['templateVar']['rkey']['val']]['varSpecificErrs']);
		}
	}

	//second part of assessing if there have been any errors...
	//this IF uses the transitory flag from the first part of the assessment of whether there are any errors. the flag contains a boolean true if any of the script's declared variables have a variable specific error (a problem with the value held in the variable)
	//or if the parts of the script which make up its template have encountered an error (for example, if a database connection has failed).
	//if($$nameSpaceID['templateVar']['error']['val'] === ""  && count($$nameSpaceID['templateVar']['rkey']['tmpVal']) == 0){ //if there has been no error in the inputs/head section of this script...
		
		write_to_log("no errors in template so coder's app-specific code can run","P"); 
	
		if($$nameSpaceID['templateVar']['includeSecurity']['val'] == false || ($$nameSpaceID['templateVar']['includeSecurity']['val'] === true && authenticated() === "true" )){  
			
					
			write_to_log("login required: ".($$nameSpaceID['templateVar']['includeSecurity']['val'] == true? "true" : "false"),"P");
			
			write_to_log("if login is required, one of the following must equate to true:","P");
			write_to_log("authenticated(): ".(authenticated() == "true"? "true" : "false"),"P");


			write_to_log("user needs to be logged on (explain)","P"); 

			write_to_log("START OF CODER'S APP-SPECIFIC CODE","start_step");



			try {			
write_to_log("STEP 25 - SAFE CONTAINER FOR CODER'S CODE","start_step");
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				?>
	<!--header component-->
	<header class="govuk-header" data-module="govuk-header">
		<div class="govuk-header__container govuk-width-container">
			<div class="govuk-header__logo">
				<a href="/" class="govuk-header__link govuk-header__link--homepage">
					<svg
						focusable="false"
						role="img"
						class="govuk-header__logotype"
						xmlns="http://www.w3.org/2000/svg"
						viewBox="0 0 148 30"
						height="30"
						width="148"
						aria-label="GOV.UK"
					>
						<title>GOV.UK</title>
						<path d="M22.6 10.4c-1 .4-2-.1-2.4-1-.4-.9.1-2 1-2.4.9-.4 2 .1 2.4 1s-.1 2-1 2.4m-5.9 6.7c-.9.4-2-.1-2.4-1-.4-.9.1-2 1-2.4.9-.4 2 .1 2.4 1s-.1 2-1 2.4m10.8-3.7c-1 .4-2-.1-2.4-1-.4-.9.1-2 1-2.4.9-.4 2 .1 2.4 1s0 2-1 2.4m3.3 4.8c-1 .4-2-.1-2.4-1-.4-.9.1-2 1-2.4.9-.4 2 .1 2.4 1s-.1 2-1 2.4M17 4.7l2.3 1.2V2.5l-2.3.7-.2-.2.9-3h-3.4l.9 3-.2.2c-.1.1-2.3-.7-2.3-.7v3.4L15 4.7c.1.1.1.2.2.2l-1.3 4c-.1.2-.1.4-.1.6 0 1.1.8 2 1.9 2.2h.7c1-.2 1.9-1.1 1.9-2.1 0-.2 0-.4-.1-.6l-1.3-4c-.1-.2 0-.2.1-.3m-7.6 5.7c.9.4 2-.1 2.4-1 .4-.9-.1-2-1-2.4-.9-.4-2 .1-2.4 1s0 2 1 2.4m-5 3c.9.4 2-.1 2.4-1 .4-.9-.1-2-1-2.4-.9-.4-2 .1-2.4 1s.1 2 1 2.4m-3.2 4.8c.9.4 2-.1 2.4-1 .4-.9-.1-2-1-2.4-.9-.4-2 .1-2.4 1s0 2 1 2.4m14.8 11c4.4 0 8.6.3 12.3.8 1.1-4.5 2.4-7 3.7-8.8l-2.5-.9c.2 1.3.3 1.9 0 2.7-.4-.4-.8-1.1-1.1-2.3l-1.2 4c.7-.5 1.3-.8 2-.9-1.1 2.5-2.6 3.1-3.5 3-1.1-.2-1.7-1.2-1.5-2.1.3-1.2 1.5-1.5 2.1-.1 1.1-2.3-.8-3-2-2.3 1.9-1.9 2.1-3.5.6-5.6-2.1 1.6-2.1 3.2-1.2 5.5-1.2-1.4-3.2-.6-2.5 1.6.9-1.4 2.1-.5 1.9.8-.2 1.1-1.7 2.1-3.5 1.9-2.7-.2-2.9-2.1-2.9-3.6.7-.1 1.9.5 2.9 1.9l.4-4.3c-1.1 1.1-2.1 1.4-3.2 1.4.4-1.2 2.1-3 2.1-3h-5.4s1.7 1.9 2.1 3c-1.1 0-2.1-.2-3.2-1.4l.4 4.3c1-1.4 2.2-2 2.9-1.9-.1 1.5-.2 3.4-2.9 3.6-1.9.2-3.4-.8-3.5-1.9-.2-1.3 1-2.2 1.9-.8.7-2.3-1.2-3-2.5-1.6.9-2.2.9-3.9-1.2-5.5-1.5 2-1.3 3.7.6 5.6-1.2-.7-3.1 0-2 2.3.6-1.4 1.8-1.1 2.1.1.2.9-.3 1.9-1.5 2.1-.9.2-2.4-.5-3.5-3 .6 0 1.2.3 2 .9l-1.2-4c-.3 1.1-.7 1.9-1.1 2.3-.3-.8-.2-1.4 0-2.7l-2.9.9C1.3 23 2.6 25.5 3.7 30c3.7-.5 7.9-.8 12.3-.8m28.3-11.6c0 .9.1 1.7.3 2.5.2.8.6 1.5 1 2.2.5.6 1 1.1 1.7 1.5.7.4 1.5.6 2.5.6.9 0 1.7-.1 2.3-.4s1.1-.7 1.5-1.1c.4-.4.6-.9.8-1.5.1-.5.2-1 .2-1.5v-.2h-5.3v-3.2h9.4V28H55v-2.5c-.3.4-.6.8-1 1.1-.4.3-.8.6-1.3.9-.5.2-1 .4-1.6.6s-1.2.2-1.8.2c-1.5 0-2.9-.3-4-.8-1.2-.6-2.2-1.3-3-2.3-.8-1-1.4-2.1-1.8-3.4-.3-1.4-.5-2.8-.5-4.3s.2-2.9.7-4.2c.5-1.3 1.1-2.4 2-3.4.9-1 1.9-1.7 3.1-2.3 1.2-.6 2.6-.8 4.1-.8 1 0 1.9.1 2.8.3.9.2 1.7.6 2.4 1s1.4.9 1.9 1.5c.6.6 1 1.3 1.4 2l-3.7 2.1c-.2-.4-.5-.9-.8-1.2-.3-.4-.6-.7-1-1-.4-.3-.8-.5-1.3-.7-.5-.2-1.1-.2-1.7-.2-1 0-1.8.2-2.5.6-.7.4-1.3.9-1.7 1.5-.5.6-.8 1.4-1 2.2-.3.8-.4 1.9-.4 2.7zM71.5 6.8c1.5 0 2.9.3 4.2.8 1.2.6 2.3 1.3 3.1 2.3.9 1 1.5 2.1 2 3.4s.7 2.7.7 4.2-.2 2.9-.7 4.2c-.4 1.3-1.1 2.4-2 3.4-.9 1-1.9 1.7-3.1 2.3-1.2.6-2.6.8-4.2.8s-2.9-.3-4.2-.8c-1.2-.6-2.3-1.3-3.1-2.3-.9-1-1.5-2.1-2-3.4-.4-1.3-.7-2.7-.7-4.2s.2-2.9.7-4.2c.4-1.3 1.1-2.4 2-3.4.9-1 1.9-1.7 3.1-2.3 1.2-.5 2.6-.8 4.2-.8zm0 17.6c.9 0 1.7-.2 2.4-.5s1.3-.8 1.7-1.4c.5-.6.8-1.3 1.1-2.2.2-.8.4-1.7.4-2.7v-.1c0-1-.1-1.9-.4-2.7-.2-.8-.6-1.6-1.1-2.2-.5-.6-1.1-1.1-1.7-1.4-.7-.3-1.5-.5-2.4-.5s-1.7.2-2.4.5-1.3.8-1.7 1.4c-.5.6-.8 1.3-1.1 2.2-.2.8-.4 1.7-.4 2.7v.1c0 1 .1 1.9.4 2.7.2.8.6 1.6 1.1 2.2.5.6 1.1 1.1 1.7 1.4.6.3 1.4.5 2.4.5zM88.9 28 83 7h4.7l4 15.7h.1l4-15.7h4.7l-5.9 21h-5.7zm28.8-3.6c.6 0 1.2-.1 1.7-.3.5-.2 1-.4 1.4-.8.4-.4.7-.8.9-1.4.2-.6.3-1.2.3-2v-13h4.1v13.6c0 1.2-.2 2.2-.6 3.1s-1 1.7-1.8 2.4c-.7.7-1.6 1.2-2.7 1.5-1 .4-2.2.5-3.4.5-1.2 0-2.4-.2-3.4-.5-1-.4-1.9-.9-2.7-1.5-.8-.7-1.3-1.5-1.8-2.4-.4-.9-.6-2-.6-3.1V6.9h4.2v13c0 .8.1 1.4.3 2 .2.6.5 1 .9 1.4.4.4.8.6 1.4.8.6.2 1.1.3 1.8.3zm13-17.4h4.2v9.1l7.4-9.1h5.2l-7.2 8.4L148 28h-4.9l-5.5-9.4-2.7 3V28h-4.2V7zm-27.6 16.1c-1.5 0-2.7 1.2-2.7 2.7s1.2 2.7 2.7 2.7 2.7-1.2 2.7-2.7-1.2-2.7-2.7-2.7z"></path>
					</svg>
					<span class="govuk-header__product-name">
					Department of State Digital
					</span>
				</a>
				<a style="line-height:100%;height:100%;float:right" href="/klogin/process_logout.php" title="log out">log out</a>
			</div>
		</div>
	</header>
	<!--end of header component-->
	
	<!--horizontal menu-->
		<!--optionally, the top banner menu component goes here-->
	<!--end of horizontal menu -->
	
	<div class="app-width-container app-main-wrapper"><!--main layout wrapper-->
	 
		<!--breadcrumbs component-->
		<nav class="govuk-breadcrumbs app-breadcrumbs" aria-label="Breadcrumb">
			<ol class="govuk-breadcrumbs__list">
				<li class="govuk-breadcrumbs__list-item">
					<a class="govuk-breadcrumbs__link" href="/appStore/">App Store Home</a>
				</li>
			</ol>
		</nav>
		<!--end of breadcrumbs component-->
		
		
		<main id="main-content" role="main">
			
			<!--main page header-->
			<h1 id="H1MainTitle" class="govuk-heading-xl">
				<?php echo $$nameSpaceID['DOM']['H1MainTitle']['innerHTML'] ?? "Set this Main Title in the languagePack's DOMContent.PHP" ;?>
			</h1>
			<!--end of main page header-->

			<!--prose component-->
			<div class="app-prose-scope">
				<p id="prose"><?php echo $$nameSpaceID['DOM']['prose']['innerHTML'] ?? "a brief statement of what the user is looking at should be added to the languagePack's DOMContent.PHP" ;?></p>
			</div>
			<!--end of prose component-->
		</main>

		<!--back to top component (for long pages)-->
			<!--optionally, the back-to-top component can be placed here-->
		<!--end of back to top component-->

	</div><!--end of main layout wrapper-->
	
	<!--footer component-->
	<footer class="govuk-footer">
		<div class="govuk-width-container">
			<div class="govuk-footer__meta">
				<div class="govuk-footer__meta-item govuk-footer__meta-item--grow">
					<svg
					  aria-hidden="true"
					  focusable="false"
					  class="govuk-footer__licence-logo"
					  xmlns="http://www.w3.org/2000/svg"
					  viewBox="0 0 483.2 195.7"
					  height="17"
					  width="41"
					>
					<path
					fill="currentColor"
					d="M421.5 142.8V.1l-50.7 32.3v161.1h112.4v-50.7zm-122.3-9.6A47.12 47.12 0 0 1 221 97.8c0-26 21.1-47.1 47.1-47.1 16.7 0 31.4 8.7 39.7 21.8l42.7-27.2A97.63 97.63 0 0 0 268.1 0c-36.5 0-68.3 20.1-85.1 49.7A98 98 0 0 0 97.8 0C43.9 0 0 43.9 0 97.8s43.9 97.8 97.8 97.8c36.5 0 68.3-20.1 85.1-49.7a97.76 97.76 0 0 0 149.6 25.4l19.4 22.2h3v-87.8h-80l24.3 27.5zM97.8 145c-26 0-47.1-21.1-47.1-47.1s21.1-47.1 47.1-47.1 47.2 21 47.2 47S123.8 145 97.8 145"
					/>
					</svg>
					<span>
					  Created by civil servants in MOD's Analysis Digital Team, saving time and money.
					</span> 
					<span class="govuk-footer__licence-description">
					  All content is available under the
					  <a
						class="govuk-footer__link"
						href="https://www.nationalarchives.gov.uk/doc/open-government-licence/version/3/"
						rel="license"
					  >Open Government Licence v3.0</a>, except where otherwise stated
					</span>
				</div>
				<div class="govuk-footer__meta-item">
					<a
					  class="govuk-footer__link govuk-footer__copyright-logo"
					  href="https://www.nationalarchives.gov.uk/information-management/re-using-public-sector-information/uk-government-licensing-framework/crown-copyright/"
					>
					  © Crown copyright
					</a>
				</div>
			</div>
		</div>
	</footer>
	<!--end of footer component-->

	
	<script>
	</script>
	<style>
		form .inputLargeLabel{
			visibility:visible;
		}
	</style>	





<?php 
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
		}else{ //else authenticated() != true and its required..
			
			echo '<!--- authentication required -->';
			
			if($$nameSpaceID['templateVar']['includeSecurity']['val'] == true){ 
				include( $_SERVER['DOCUMENT_ROOT'] ."/klogin/loginform.php"); 
			}
		} //end of if authenticated() === true

/*
	}else{	
		//end of if no error in inputs / head section of this script
		write_to_log("FAIL","H4");
		write_to_log("The coder's app-specific code has not been attempted as the following script-level errors in the template section of the code needs to be resovled first.","red");
	
		write_to_log(array("title"=>"template variables with errors:","cols"=>array("variable","error code(s)")), "start_table");
		write_to_log($$nameSpaceID['templateVar']['rkey']['tmpVal'],"TABLE_ROWS_KEYED");
	}
*/
	
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


if (str_replace('\\', '/', __FILE__) == $_SERVER['SCRIPT_FILENAME']) { ?>
<!--template-->
<div id="modal-form" class="modal fade" data-backdrop="true" data-keyboard="true" data-focus="true" onclick="$('#modal-form').modal('hide');">
	<div class="modal-dialog">
		<div   class="modal-content">
			<div id="modal-title"></div>
			<div  class="modal-body">
				<div style="position:relative;height:100%;">		
					<!--<img src="/klogin/images/saved_icon.png" style="opacity:0.6;position:absolute;max-width:100%; min-width:100%;height:auto" >-->
					<div id="modalMsg" style="position:relative">&nbsp;</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
	"use strict";
	<?php /*this script block is only included once per generated page, intended for template script 
	which isn't specific to a given page, but which requires php-generated customization. 
	(else it would go in the functionLibrary.js file*/?>
			<?php
		//display any recent server response in the modal popup
		if($$nameSpaceID['templateVar']['sr']['val'] != ""){	
			if($$nameSpaceID['templateVar']['srTimeElapsed']['val']  < 20){?> //if < 20 secs since server responded (or no time limit because elapsed = 0) 		
				$('#modalMsg').html('<?php echo addslashes($$nameSpaceID['templateVar']['sr']['val'] );?>');
				$('#modal-form').modal('show'); 
				setTimeout(function(){ 
					$('#modal-form').modal('hide');
				}, 2500);
			<?php 
			}
		}
		if($$nameSpaceID['templateVar']['error']['val'] != ""){
			if(intval($$nameSpaceID['templateVar']['error']['val']) >0 && isset($errors) && isset($errors[$$nameSpaceID['templateVar']['error']['val']]) && isset($errors[$$nameSpaceID['templateVar']['error']['val']]['msg'])){$modalMsg = $errors[$$nameSpaceID['templateVar']['error']['val']]['msg'];}else{$modalMsg = $$nameSpaceID['templateVar']['error']['val'];}?>
			$('#modalMsg').html('<?php echo addslashes($modalMsg);?>');
			$('#modal-form').modal('show'); 
			setTimeout(function(){ 
				$('#modal-form').modal('hide');
			}, 2500);
		<?php }?>

	window.addEventListener('load', function(){
		if(window.self !== window.top) {
			[].forEach.call(document.querySelectorAll('.topWindowOnly'), (e)=>{
				e.style.display = 'none';
			});
			//send a message back to the top window. using js session. storage automatically stamps messges with 
			//sending url and timestamp. if multiple messages are needed, they could go in an array or multiple values
			//can be set but the 'listener' event on the parent can just react to a final given value.
		}
				
		<?php
			
			$nameSpaces = get_included_files();
			foreach ($nameSpaces as $key => $value) { //with the name of the current php file and its includes,
				$value = substr($value,strlen($_SERVER["DOCUMENT_ROOT"]));//strip server doc_root
				$value = preg_replace("#[/\\\\\.]+#", "_", $value);//replace slashes with underscores
				$value = preg_replace("/[^A-Za-z0-9_]/", '', $value); //remove non AlphaNum chars except slashes
				$value = trim($value,"\n\r\t\v\0_"); //trim weird chars and initial underscore if present
				$nameSpaces[$key] = array($nameSpaces[$key],$value);
				
				
				
				echo "        if (typeof window.NS_$value == 'undefined') {".PHP_EOL;//if it doesnt already exist..
				echo "            window.NS_$value  = {};".PHP_EOL; //then create a js namespace named as the cleansed php filename
				echo "        }".PHP_EOL;				
				echo " //console.log('window.NS_$value.init type:' + window.typeof NS_$value.init);".PHP_EOL;
				echo "        if (typeof window.NS_$value.init == 'function') {".PHP_EOL; //if there is an init JS function for the included file..
				echo "            window.NS_".$value.".init();".PHP_EOL;	//then run it. (only runs if namespace didnt exist. ensures it runs just once.)
				echo "        }else{".PHP_EOL;
				echo "            if(typeof debugging !== 'undefined' && debugging){console.warn(\"the portion of '".addslashes($nameSpaces[$key][0])."' included by PHP in '".addslashes($nameSpaces[0][0])."' did not contain a javascript function called 'NS_".$value.".init()' for its onload event. If you were expecting the inclusion to trigger its onload event, it will not.\");}".PHP_EOL;
				echo "        }".PHP_EOL;

			}
			echo "//this file: NS_".$nameSpaces[0][1].".init()".PHP_EOL;
		?>		
	});	
</script>
<svg style="display:none" xmlns="http://www.w3.org/2000/svg">
  <desc>defines icons. paths created in inkscape, viewBox from inkscape file transferred to symbol else manually tune - set initial scaling as 0 0 1 100, increase the 100 til image fits height of container then increase the 1 to pull the image left into the centre of the viewbox. to create svg from image, open in paint, save as black n white bmp, import into inkscape, trace to svg. change page to fit image, save. shrink with https://svgoptimizer.com/, remove pathss from resulting file into symbol per below</desc>
  <desc>for styling advice, see https://tympanus.net/codrops/2015/07/16/styling-svg-use-content-css/ in short, give the 'use' tag of the icon instance a class with style. ALSO set css for svg and/or its descendents (eg svg path) atttributes eg fill, to inherit </desc>
    
  <symbol id="circle" viewbox="0 0 100 100">
    <circle cx="50%" cy="50%" r="45" stroke-width="1" stroke="#f00" fill="#f00" />
  </symbol>

  <symbol id="errExclaim" viewBox="0 0 5.996 5.993">
	<g transform="translate(0 -291.007)"><path style="fill:#fd9e00;fill-opacity:1;stroke:#fd9e00;stroke-width:.01411111;stroke-opacity:1" d="M.247 296.985a.338.338 0 0 1-.218-.188c-.02-.047-.022-.347-.022-2.79 0-2.428.002-2.745.022-2.79a.31.31 0 0 1 .154-.172l.064-.03h2.745c3.048 0 2.797-.008 2.903.095.103.1.096-.126.092 2.919l-.004 2.738-.043.07a.366.366 0 0 1-.112.111l-.069.043-2.738.002c-1.507 0-2.755-.002-2.775-.008z"/><rect width="1.044" height="2.117" x="2.481" y="292.205" ry="0" style="fill:#fff;stroke-width:.26818326"/><ellipse style="fill:#fff;stroke-width:.26458332" cx="3.003" cy="295.211" rx=".522" ry=".494"/></g>
  </symbol>  
  
  <symbol id="download" viewbox="0 0 24 16">
    <path d="M19.4,6 C18.7,2.6 15.7,0 12,0 C9.1,0 6.6,1.6 5.4,4 C2.3,4.4 0,6.9 0,10 C0,13.3 2.7,16 6,16 L19,16 C21.8,16 24,13.8 24,11 C24,8.4 21.9,6.2 19.4,6 L19.4,6 Z M17,9 L12,14 L7,9 L10,9 L10,5 L14,5 L14,9 L17,9 L17,9 Z"/>
  </symbol>

  <symbol id="delete" viewbox="0 0 650 500">
    <path d="m 89.4,100 26.2,347.9 c 2.5,32.5 29.6,58.1 60.7,58.1 h 159.3 c 31.1,0 58.2,-25.6 60.7,-58.1 L 422.6,100 Z m 100.7,360.8 c 0.3,7.1 -5.1,12.7 -12,12.7 -6.9,0 -12.7,-5.7 -13.1,-12.7 L 150.4,164.2 c -0.5,-9.6 5.7,-17.4 13.8,-17.4 8.1,0 14.9,7.8 15.3,17.4 z m 78.4,0 c 0,7.1 -5.7,12.7 -12.5,12.7 -6.8,0 -12.5,-5.7 -12.5,-12.7 l -2,-296.6 c -0.1,-9.6 6.4,-17.4 14.5,-17.4 8.1,0 14.6,7.8 14.5,17.4 z m 78.4,0 c -0.3,7.1 -6.2,12.7 -13.1,12.7 -6.9,0 -12.2,-5.7 -12,-12.7 l 10.6,-296.6 c 0.3,-9.6 7.2,-17.4 15.3,-17.4 8.1,0 14.3,7.8 13.8,17.4 z"/><path d="M 445.3,82.8 H 66.7 v 0 C 64.9,61.7 77.4,44.4 94.6,44.4 h 322.9 c 17.1,0 29.6,17.4 27.8,38.4 z"/><path d="M 324.3,58.6 H 187.7 l -0.2,-7.8 C 186.7,26.3 202.1,6 221.9,6 h 68.3 c 19.7,0 35.1,20.3 34.4,44.7 z"/>
  </symbol>
  
  <symbol id="add" viewBox="0 0 209.401 209.584">
  <path style="fill:#e0e0e0;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#dbdbdb;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#d5d5d5;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#cfcfcf;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#cacaca;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#c4c4c4;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#bfbfbf;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#b9b9b9;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#b3b3b3;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#aeaeae;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#a8a8a8;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#a3a3a3;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#9d9d9d;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#979797;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#929292;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#8c8c8c;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#878787;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#818181;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#7b7b7b;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#767676;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#707070;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#6b6b6b;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#656565;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#5f5f5f;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#5a5a5a;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#545454;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#4f4f4f;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#494949;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#444;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#3e3e3e;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#383838;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/><path style="fill:#333;fill-opacity:1;stroke-width:.44797176" d="M-159.408 252.934c-18.38-1.918-34.83-7.668-49.098-17.16-6.276-4.176-10.05-7.257-15.559-12.704-16.615-16.43-26.781-36.466-30.392-59.902-.576-3.737-.793-7.794-.792-14.783.002-10.927.683-16.64 3.132-26.27 8.756-34.443 34.824-62.266 68.76-73.388 19.802-6.49 40.69-6.955 61.09-1.36 28.575 7.836 52.701 27.997 65.919 55.083 7.17 14.692 10.5 29.266 10.5 45.935 0 14.019-2.454 26.941-7.44 39.161-11.543 28.296-33.9 49.634-62.676 59.824-5.225 1.85-13.12 3.809-19.03 4.721-4.516.698-20.516 1.25-24.414.843zm19.794-30.66c1.365-.515 3.927-2.787 4.77-4.229.375-.643.586-8.24.746-26.878l.224-25.983 26.054-.224 26.053-.224 1.72-1.37c.947-.753 2.183-2.265 2.746-3.36.991-1.925 1.02-2.317.896-12.108l-.128-10.12-1.238-1.784c-.681-.982-2.092-2.275-3.136-2.873l-1.898-1.087-25.636-.129-25.635-.13-.123-25.89-.123-25.893-1.238-1.786c-.68-.982-2.092-2.272-3.135-2.865-1.78-1.012-2.413-1.09-10.225-1.246-10.516-.21-12.723.186-15.401 2.765-1.203 1.159-2.123 2.545-2.43 3.658-.33 1.2-.492 9.95-.494 26.542l-.002 24.75h-24.168c-15.912 0-24.955.17-26.47.494-2.902.622-5.924 3.349-6.653 6.002-.626 2.277-.649 18.251-.03 20.48.597 2.149 2.333 4.193 4.499 5.298 1.645.84 2.793.876 27.27.876h25.552l.003 24.975c.002 15.797.173 25.586.465 26.64.584 2.103 2.307 4.168 4.402 5.275 1.458.77 2.656.861 11.564.879 5.969.012 10.442-.17 11.2-.456z" transform="translate(255.249 -43.49)"/>
  </symbol>
  
  <symbol id="profile" viewBox="-40 -40 300 300">
	<path style="stroke-width:.44797176" d="M-229.95 417.698c-17.256-.843-38.713-3.256-61.52-6.918-14.252-2.289-18.208-3.187-21.595-4.903-5.022-2.545-6.108-5.3-5.656-14.352.64-12.808 4.232-23.456 11.71-34.704 7.504-11.288 15.828-17.047 37.192-25.731 12.613-5.128 19.25-8.096 23.627-10.57 2.154-1.217 4.38-2.213 4.949-2.213.619 0 1.741.73 2.803 1.824 4.246 4.374 11.74 8.842 17.898 10.671 12.063 3.583 24.59.302 36.022-9.433 1.978-1.684 3.906-3.062 4.285-3.062.38 0 2.452.996 4.606 2.213 4.377 2.474 11.013 5.442 23.627 10.57 16.164 6.57 25.492 11.975 31.305 18.137 4.82 5.109 10.892 15.038 13.694 22.388 3.186 8.36 4.951 22.218 3.585 28.141-.624 2.706-1.941 4.29-4.858 5.837-3.385 1.795-6.044 2.506-15.28 4.082-41.241 7.038-74.791 9.569-106.394 8.023zm14.784-89.986c-7.906-1.112-14.95-4.764-21.078-10.927-5.559-5.59-9.574-12.072-11.685-18.861-.599-1.927-1.7-3.765-3.892-6.496-3.699-4.607-7.574-12.204-8.856-17.36-.765-3.078-.895-4.804-.775-10.303.163-7.444.595-8.9 2.944-9.935l1.456-.64.004-8.992c.007-16.196 1.136-22.908 5.161-30.69 7.151-13.823 21.1-21.034 40.764-21.073 20.292-.04 34.252 7.229 41.475 21.592 3.767 7.492 4.868 14.305 4.875 30.171l.004 8.991 1.456.64c2.35 1.035 2.781 2.492 2.944 9.936.12 5.499-.01 7.225-.775 10.303-1.265 5.088-5.163 12.738-8.846 17.36-2.22 2.786-3.318 4.65-4.094 6.944-1.391 4.117-4.193 9.644-6.585 12.991-2.242 3.136-7.58 8.382-10.676 10.49-6.752 4.595-16.23 6.927-23.821 5.86z" transform="translate(318.814 -192.435)"/><path style="stroke-width:.44797176" d="M-229.502 417.698c-17.256-.843-38.713-3.256-61.52-6.918-14.731-2.366-18.41-3.228-22.046-5.165-4.926-2.626-6.126-5.64-5.64-14.169.715-12.541 4.332-23.099 11.719-34.21 7.637-11.488 16.2-17.44 37.618-26.146 12.613-5.128 19.25-8.096 23.627-10.57 2.154-1.217 4.18-2.213 4.5-2.213.322 0 1.382.82 2.356 1.824 4.246 4.374 11.74 8.842 17.898 10.671 12.49 3.71 25.37.401 37-9.503l3.677-3.131 2.508 1.444c4.428 2.55 11.666 5.86 21.77 9.96 19.234 7.804 28.775 13.202 35.093 19.852 5.006 5.27 11.077 15.131 13.939 22.64 3.147 8.258 4.895 21.563 3.582 27.26-.627 2.72-2.25 4.649-5.25 6.24-3.44 1.825-6.066 2.53-15.333 4.11-41.081 7.012-74.39 9.545-105.498 8.024zm14.784-89.995c-7.97-1.093-14.932-4.734-21.303-11.14-5.783-5.815-9.767-12.201-11.908-19.087-.599-1.927-1.7-3.765-3.892-6.496-3.65-4.546-7.559-12.17-8.822-17.206-.653-2.606-.889-5.048-.889-9.229 0-7.258.808-9.768 3.472-10.787.998-.381 1.008-.471 1.012-9.244.007-16.065 1.143-22.784 5.171-30.583 7.183-13.908 21.49-21.454 40.754-21.496 19.65-.042 33.895 7.394 41.178 21.496 4.029 7.8 5.165 14.518 5.172 30.583.004 8.773.013 8.863 1.011 9.244 2.664 1.02 3.472 3.53 3.472 10.787 0 4.18-.235 6.623-.889 9.229-1.246 4.967-5.178 12.647-8.811 17.206-2.22 2.786-3.319 4.65-4.094 6.944-1.392 4.119-4.194 9.645-6.587 12.991-2.253 3.15-8.04 8.842-11.123 10.939-6.622 4.504-15.75 6.833-22.924 5.849z" transform="translate(318.814 -192.435)"/><path style="0#000;stroke-width:.44797176" d="M-229.054 417.698c-17.256-.843-38.713-3.256-61.52-6.918-14.964-2.403-18.365-3.213-22.385-5.332-3.472-1.83-4.755-3.448-5.505-6.946-1.03-4.807.412-16.514 2.96-24.009 2.737-8.054 9.129-19.04 14.456-24.845 5.52-6.015 15.609-11.843 32.125-18.559 12.992-5.282 18.406-7.707 23.174-10.38 4.844-2.715 4.858-2.715 7.512.059 3.904 4.08 10.956 8.286 16.818 10.032 12.765 3.8 25.652.637 37.035-9.092 1.901-1.624 3.775-2.953 4.165-2.953.39 0 2.261.892 4.159 1.983 3.912 2.248 10.791 5.323 23.162 10.352 16.325 6.637 26.447 12.454 32.002 18.392 4.748 5.076 10.864 15.183 13.737 22.703 1.972 5.16 3.265 10.952 3.84 17.198.914 9.94-.218 13.263-5.454 16.013-3.817 2.004-6.266 2.672-15.68 4.279-40.914 6.983-73.992 9.52-104.6 8.023zm14.783-90.012c-4.085-.535-9.433-2.406-13.663-4.779-4.37-2.452-10.848-8.544-13.627-12.813-2.168-3.33-5.291-9.668-6.314-12.811-.5-1.537-1.85-3.764-3.667-6.048-3.5-4.397-7.873-13.003-9.102-17.91-.651-2.6-.881-4.983-.863-8.959.03-6.589.767-9.084 2.992-10.12l1.444-.671.013-8.736c.025-15.901 1.123-22.429 5.108-30.362 4.004-7.971 9.166-13.098 17.004-16.888 14.875-7.192 35.868-6.792 50.018.954 6.727 3.682 11.37 8.596 15.057 15.934 3.984 7.933 5.083 14.46 5.107 30.362l.014 8.736 1.443.672c2.225 1.035 2.963 3.53 2.993 10.12.018 3.975-.212 6.358-.864 8.959-1.22 4.871-5.602 13.51-9.069 17.878-1.861 2.346-3.202 4.59-3.88 6.496-1.477 4.148-4.736 10.523-6.985 13.663-2.238 3.125-7.13 7.925-10.234 10.04-6.872 4.683-16.014 7.189-22.925 6.283z" transform="translate(318.814 -192.435)"/><path style="stroke-width:.44797176" d="M-228.606 417.698c-17.256-.843-38.713-3.256-61.52-6.918-14.568-2.34-18.444-3.233-22.39-5.163-5.087-2.488-6.732-6.305-6.155-14.284.88-12.191 4.653-23.045 11.707-33.678 7.715-11.628 16.235-17.62 37.145-26.118 12.153-4.94 20.203-8.536 24.19-10.809 1.905-1.085 3.804-1.973 4.22-1.973.416 0 1.451.72 2.3 1.601 3.859 4.004 12.49 9.15 17.895 10.668 12.443 3.496 25.096.48 36.346-8.663 1.986-1.614 3.88-3.127 4.21-3.362.415-.296 1.26-.046 2.756.815 4.254 2.45 12.628 6.282 22.316 10.21 24.263 9.84 32.727 15.566 40.634 27.492 6.898 10.403 10.475 20.241 11.622 31.964 1.292 13.203-1.78 16.58-17.762 19.528-3.203.591-10.46 1.785-16.127 2.654-35.9 5.503-64.143 7.368-91.387 6.036zm14.783-90.037c-3.992-.474-10.576-2.744-14.193-4.892-3.687-2.191-9.398-7.466-12.49-11.54-2.588-3.408-5.766-9.505-7.134-13.687-.75-2.291-1.783-4.04-4.079-6.902-3.554-4.43-6.506-9.909-8.265-15.339-1.01-3.119-1.27-4.86-1.432-9.631-.236-6.922.477-9.672 2.905-11.2l1.425-.895.02-8.512c.04-15.683 1.168-22.37 5.143-30.462 4.753-9.677 13.103-16.475 24.213-19.711 12.076-3.518 25.422-3.116 37.014 1.114 14.572 5.318 23.96 18.425 25.478 35.57.242 2.738.45 8.808.462 13.49l.02 8.51 1.426.897c2.427 1.527 3.14 4.277 2.905 11.199-.163 4.77-.422 6.512-1.432 9.631-1.753 5.412-4.709 10.906-8.225 15.289-2.3 2.865-3.341 4.647-4.3 7.35-2.836 8.002-6.136 13.19-11.975 18.83-5.936 5.734-10.318 8.274-17.255 10-4.376 1.087-6.798 1.298-10.23.89z" transform="translate(318.814 -192.435)"/><path style="stroke-width:.44797176" d="M-227.934 417.71c-17-.828-39.725-3.374-61.566-6.896-15.417-2.487-19.252-3.386-23.12-5.419-3.616-1.9-5.05-3.76-5.812-7.534-.922-4.573.49-15.463 2.98-22.991 2.64-7.977 9.122-19.056 14.636-25.012 5.804-6.268 15.653-11.984 32.34-18.768 12.371-5.03 19.25-8.104 23.163-10.352 1.898-1.09 3.721-1.983 4.052-1.983.331 0 1.4.82 2.373 1.824 5.934 6.113 15 10.57 23.627 11.615 11.048 1.339 21.636-2.222 31.407-10.56l3.539-3.02 2.509 1.443c4.267 2.454 10.614 5.346 20.876 9.511 5.42 2.2 13.282 5.687 17.47 7.746 12.39 6.093 18.128 10.809 24.089 19.795 7.203 10.86 10.811 20.622 12.017 32.516 1.402 13.828-1.886 16.71-23.114 20.257-40.719 6.805-72.36 9.246-101.466 7.828zm9.312-91.133c-4.39-1.205-7.28-2.55-11.59-5.398-3.426-2.262-8.836-7.773-11.322-11.533-2.263-3.422-5.397-9.782-6.252-12.683-.447-1.52-1.688-3.58-3.643-6.048-6.427-8.114-10.12-17.833-10.083-26.534.025-5.675.91-8.61 2.987-9.907l1.439-.9.02-8.287c.04-15.69 1.301-22.915 5.382-30.843 2.768-5.378 5.781-9.13 9.95-12.392 6.471-5.064 14.977-8.139 25.566-9.243 9.474-.987 20.86.884 28.76 4.727 7.951 3.868 13.207 9.013 17.27 16.908 4.081 7.928 5.342 15.153 5.382 30.843l.02 8.288 1.44.899c2.077 1.298 2.962 4.232 2.987 9.907.037 8.677-3.656 18.421-10.036 26.474-1.991 2.513-3.216 4.577-3.857 6.496-1.28 3.835-4.578 10.309-6.91 13.564-2.243 3.13-7.59 8.384-10.65 10.468-8.42 5.73-18.091 7.6-26.86 5.194z" transform="translate(318.814 -192.435)"/><path style="stroke-width:.44797176" d="M-229.95 417.25c-20.553-1.005-58.828-5.745-75.61-9.363-5.134-1.107-9.677-3.473-11.373-5.922-1.259-1.819-1.267-1.875-1.232-8.587.07-13.332 3.458-23.882 11.592-36.1 7.55-11.342 16.194-17.283 38.098-26.188 12.37-5.03 19.25-8.104 23.162-10.352 1.898-1.09 3.721-1.983 4.052-1.983.331 0 1.4.82 2.373 1.824 4.345 4.476 10.965 8.387 17.681 10.445 7.695 2.359 13.887 2.238 21.906-.425 5.698-1.893 10.304-4.565 15.447-8.96l3.539-3.025 2.509 1.443c4.267 2.454 10.614 5.346 20.876 9.511 19.336 7.85 29.733 13.692 35.785 20.107 4.9 5.195 11.12 15.39 13.902 22.784 2.629 6.99 4.174 16.972 3.73 24.093-.322 5.155-1.395 6.698-6.338 9.117-4.014 1.965-7.468 2.7-26.024 5.545-36.3 5.564-65.462 7.435-94.075 6.036zm14.974-89.957c-7.58-1.032-14.362-4.58-20.602-10.778-2.356-2.34-5.05-5.432-5.984-6.87-2.273-3.494-5.386-9.837-6.224-12.682-.447-1.52-1.688-3.58-3.643-6.048-3.618-4.567-7.594-12.18-8.777-16.805-.664-2.598-.878-4.941-.878-9.646 0-7.2.46-8.83 2.89-10.264l1.59-.938.004-9.027c.007-16.339.962-22.11 4.94-29.836 4.038-7.845 9.34-13.03 17.206-16.828 8.683-4.192 20.874-5.685 32.95-4.037 6.406.874 9.372 1.72 14.136 4.029 7.881 3.82 13.172 8.996 17.208 16.836 3.976 7.726 4.932 13.497 4.939 29.836l.004 9.027 1.59.938c2.43 1.435 2.89 3.064 2.89 10.264 0 4.705-.214 7.048-.878 9.646-1.178 4.603-5.155 12.232-8.73 16.745-1.991 2.513-3.216 4.577-3.856 6.496-1.28 3.835-4.579 10.309-6.911 13.564-2.243 3.13-7.59 8.384-10.65 10.468-2.88 1.96-8.081 4.373-11.085 5.142-3.235.828-9.002 1.194-12.13.768z" transform="translate(318.814 -192.435)"/><path style="stroke-width:.44797176" d="M-229.502 417.25c-17.256-.843-38.713-3.256-61.52-6.918-14.252-2.289-18.208-3.187-21.595-4.903-4.98-2.524-6.126-5.367-5.64-13.983.706-12.512 4.328-23.097 11.694-34.177 7.504-11.288 15.828-17.047 37.192-25.732 12.613-5.127 19.25-8.095 23.627-10.57 2.154-1.216 4.18-2.212 4.5-2.212.322 0 1.382.82 2.356 1.824 4.246 4.374 11.74 8.842 17.898 10.671 12.155 3.61 24.945.502 36.127-8.778 5.088-4.223 4.239-4.074 8.74-1.53 4.421 2.498 11.027 5.455 23.673 10.595 16.164 6.571 25.492 11.976 31.305 18.138 4.82 5.108 10.892 15.038 13.694 22.388 3.143 8.248 4.896 21.565 3.585 27.245-.624 2.706-1.941 4.29-4.858 5.837-3.385 1.795-6.044 2.506-15.28 4.082-41.081 7.011-74.39 9.544-105.498 8.023zm14.784-89.995c-7.858-1.078-14.945-4.749-21.078-10.918-5.559-5.59-9.574-12.072-11.685-18.861-.599-1.927-1.7-3.765-3.892-6.496-3.7-4.609-7.574-12.205-8.857-17.366-.757-3.042-.895-4.8-.775-9.855.161-6.826.596-8.285 2.833-9.516l1.344-.74.278-12.059c.358-15.559 1.144-19.768 5.134-27.515 6-11.65 16.302-18.31 31.546-20.394 10.2-1.394 21.217-.375 29.453 2.725 13.017 4.9 21.566 15.539 24.335 30.284.293 1.562.658 8.267.81 14.9l.278 12.06 1.344.739c2.238 1.23 2.672 2.69 2.834 9.516.12 5.054-.019 6.813-.775 9.855-1.266 5.093-5.163 12.742-8.847 17.366-2.22 2.786-3.319 4.65-4.094 6.944-1.392 4.117-4.193 9.644-6.586 12.991-2.241 3.136-7.58 8.382-10.676 10.49-6.615 4.503-15.75 6.834-22.924 5.85z" transform="translate(318.814 -192.435)"/><path style="stroke-width:.44797176" d="M-229.502 417.25c-16.78-.82-38.346-3.218-60.252-6.7-14.886-2.365-18.573-3.187-22.016-4.907-5.857-2.928-6.442-4.054-6.391-12.301.062-10.08 1.78-17.268 6.357-26.593 2.976-6.065 7.58-13.142 10.978-16.875 5.25-5.768 16.175-12.025 32.799-18.784 12.726-5.175 18.391-7.71 22.718-10.167 4.352-2.472 4.43-2.469 7.072.293 4.02 4.2 10.185 7.848 16.579 9.807 7.888 2.418 14.67 2.293 22.783-.42 5.418-1.813 9.505-4.196 14.491-8.451 1.901-1.622 3.775-2.95 4.165-2.95.39 0 2.261.887 4.159 1.971 3.865 2.208 9.728 4.819 22.266 9.917 16.462 6.694 27.413 12.94 32.701 18.652 4.615 4.984 11.062 15.757 13.824 23.096 2.602 6.917 4.195 16.884 3.749 23.459-.364 5.36-1.538 6.949-7.045 9.544-3.71 1.749-8.13 2.67-25.758 5.373-36.173 5.545-65.017 7.413-93.179 6.036zm15.008-89.963c-10.176-1.376-20.572-8.113-26.636-17.265-2.205-3.327-5.786-10.532-6.57-13.218-.343-1.179-1.763-3.504-3.418-5.6-3.49-4.42-7.917-12.965-9.089-17.546-.655-2.56-.874-4.911-.867-9.292.012-6.866.695-9.112 3.07-10.096l1.4-.58.004-9.017c.007-16.262.93-21.89 4.881-29.761 4.269-8.504 8.847-13.043 17.273-17.125 9.054-4.387 20.52-5.887 32.495-4.253 6.542.893 9.386 1.721 14.576 4.245 8.439 4.103 13.007 8.634 17.273 17.133 3.95 7.871 4.874 13.499 4.88 29.76l.005 9.018 1.4.58c2.375.984 3.058 3.23 3.07 10.096.006 4.38-.213 6.732-.867 9.292-1.167 4.56-5.588 13.109-9.075 17.546-1.697 2.16-3.146 4.563-3.646 6.048-1.131 3.356-4.052 9.299-6.214 12.644-7.446 11.52-22.044 18.999-33.945 17.39z" transform="translate(318.814 -192.435)"/><path style="stroke-width:.44797176" d="M-229.054 417.25c-17.256-.843-38.713-3.256-61.52-6.918-14.731-2.366-18.41-3.228-22.046-5.165-4.884-2.604-6.144-5.698-5.62-13.806.599-9.255 2.607-16.81 6.618-24.897 4.871-9.822 11.074-17.545 18.133-22.58 4.99-3.558 15.457-8.714 28.15-13.863 10.276-4.17 17.617-7.53 21.61-9.892l2.349-1.388 2.58 2.36c3.68 3.368 6.542 5.363 10.642 7.42 14.778 7.414 30.464 5.107 44.335-6.519 1.838-1.54 3.524-2.8 3.747-2.8.223 0 1.995.895 3.936 1.988 4.027 2.268 10.978 5.362 23.242 10.347 21.382 8.692 29.978 14.65 37.516 25.998 7.112 10.709 10.569 20.208 11.635 31.975.87 9.603-.263 12.896-5.372 15.606-3.44 1.825-6.066 2.53-15.333 4.11-40.915 6.984-73.993 9.521-104.602 8.024zm14.783-90.012c-7.885-1.033-14.92-4.707-21.302-11.124-5.783-5.814-9.767-12.2-11.908-19.086-.599-1.927-1.7-3.765-3.892-6.496-3.65-4.546-7.559-12.17-8.822-17.206-.633-2.526-.889-5.047-.889-8.78 0-6.799.853-9.338 3.472-10.34.998-.381 1.008-.472 1.012-9.244.002-4.872.202-11.098.444-13.836 1.344-15.173 9.262-27.338 21.692-33.324 14.225-6.85 35.906-6.418 49.088.977 11.312 6.346 18.445 18.052 19.71 32.347.242 2.738.442 8.964.445 13.836.004 8.772.013 8.863 1.011 9.244 2.62 1.002 3.472 3.541 3.472 10.34 0 3.733-.255 6.254-.889 8.78-1.246 4.967-5.178 12.647-8.811 17.206-2.22 2.786-3.319 4.65-4.094 6.944-1.392 4.119-4.194 9.645-6.587 12.991-2.253 3.15-8.04 8.842-11.123 10.939-6.48 4.407-15.228 6.723-22.029 5.832z" transform="translate(318.814 -192.435)"/><path style="stroke-width:.44797176" d="M-228.606 417.25c-17.256-.843-38.713-3.256-61.52-6.918-14.561-2.338-18.445-3.233-22.38-5.158-4.786-2.341-6.286-5.967-5.72-13.82.882-12.201 4.652-23.06 11.69-33.67 7.585-11.433 15.852-17.218 36.717-25.699 12.153-4.94 20.203-8.536 24.19-10.809 1.905-1.085 3.804-1.973 4.22-1.973.416 0 1.451.72 2.3 1.601 3.859 4.004 12.49 9.15 17.895 10.668 12.443 3.496 25.096.48 36.346-8.663 1.986-1.614 3.88-3.127 4.21-3.362.415-.296 1.26-.046 2.756.815 4.254 2.45 12.628 6.282 22.316 10.21 24.207 9.817 32.438 15.359 40.199 27.063 6.737 10.162 10.354 19.957 11.568 31.327 1.519 14.241-1.3 16.988-20.857 20.325-41.005 6.998-73.752 9.539-103.93 8.063zm14.783-90.037c-3.986-.474-10.574-2.743-14.177-4.883-3.706-2.201-9.015-7.09-12.06-11.1-2.587-3.41-5.764-9.508-7.132-13.688-.75-2.291-1.783-4.04-4.08-6.902-3.555-4.431-6.507-9.912-8.262-15.339-.993-3.067-1.273-4.909-1.442-9.472-.26-7.015.449-9.804 2.855-11.224l1.517-.895.004-8.579c.007-15.678 1.137-22.411 5.125-30.53 4.69-9.55 12.696-16.039 23.765-19.264 12.076-3.517 25.422-3.115 37.014 1.115 9.123 3.33 16.149 9.595 20.35 18.15 3.987 8.118 5.118 14.85 5.125 30.529l.004 8.58 1.516.894c2.407 1.42 3.116 4.21 2.856 11.224-.17 4.563-.45 6.405-1.442 9.472-1.75 5.409-4.705 10.904-8.223 15.289-2.299 2.865-3.341 4.647-4.3 7.35-2.804 7.912-6.153 13.214-11.75 18.605-5.72 5.51-10.187 8.074-17.032 9.776-4.375 1.088-6.798 1.3-10.23.892z" transform="translate(318.814 -192.435)"/><path style="stroke-width:.44797176" d="M-228.606 417.25c-17.256-.843-38.713-3.256-61.52-6.918-14.964-2.403-18.365-3.213-22.385-5.332-3.468-1.827-4.755-3.448-5.501-6.927-.98-4.566.452-15.766 2.955-23.132 2.738-8.054 9.13-19.04 14.457-24.845 5.52-6.015 15.61-11.845 32.125-18.558 12.37-5.03 19.25-8.104 23.162-10.352 1.898-1.09 3.721-1.983 4.052-1.983.331 0 1.4.82 2.373 1.824 4.194 4.32 11.072 8.45 17.021 10.22 13.188 3.923 26.44.73 38.013-9.16l3.539-3.025 2.509 1.443c4.267 2.454 10.614 5.346 20.876 9.511 12.67 5.144 24.041 10.746 29.024 14.3 4.891 3.49 8.334 7.135 12.104 12.82 7.309 11.017 10.911 20.74 12.021 32.44.893 9.41-.254 12.648-5.44 15.372-3.817 2.004-6.266 2.672-15.68 4.279-40.742 6.953-73.599 9.495-103.705 8.023zm14.783-90.036c-4.025-.491-9.393-2.36-13.663-4.755-4.37-2.452-10.848-8.544-13.627-12.813-2.168-3.33-5.291-9.668-6.314-12.81-.5-1.538-1.85-3.765-3.667-6.049-3.5-4.397-7.873-13.003-9.102-17.91-.608-2.426-.888-5.048-.888-8.319 0-6.003.798-8.675 2.971-9.958l1.51-.89.003-8.579c.007-15.693 1.12-22.286 5.098-30.206 2.686-5.348 5.65-9.14 9.585-12.268 14.281-11.349 39.43-12.976 56.54-3.658 6.707 3.652 11.369 8.583 15.058 15.926 3.978 7.92 5.09 14.513 5.098 30.206l.004 8.58 1.508.89c2.174 1.282 2.971 3.954 2.971 9.957 0 3.271-.28 5.893-.888 8.32-1.22 4.871-5.602 13.51-9.069 17.878-1.86 2.346-3.201 4.59-3.88 6.496-1.477 4.148-4.736 10.523-6.984 13.663-2.239 3.125-7.131 7.925-10.235 10.04-3.238 2.207-9.086 4.789-12.621 5.573-4.196.93-6.204 1.077-9.407.686z" transform="translate(318.814 -192.435)"/><path style="stroke-width:.44797176" d="M-227.934 417.262c-17-.828-39.725-3.374-61.566-6.896-14.876-2.4-19.088-3.354-22.572-5.112-3.715-1.875-5.208-3.741-5.891-7.364-.916-4.853.441-15.408 2.96-23.02 2.584-7.812 9.06-18.952 14.403-24.774 5.52-6.015 15.61-11.845 32.125-18.558 12.37-5.03 19.25-8.104 23.162-10.352 1.898-1.09 3.721-1.983 4.052-1.983.331 0 1.4.82 2.373 1.824 5.934 6.113 15 10.57 23.627 11.615 11.005 1.333 21.371-2.135 31.386-10.501 1.935-1.616 3.607-2.938 3.717-2.938.11 0 1.752.892 3.65 1.983 3.912 2.248 10.791 5.323 23.162 10.352 16.325 6.637 26.447 12.454 32.002 18.392 4.72 5.046 10.93 15.293 13.654 22.53 3.063 8.136 4.838 19.752 3.857 25.24-1.194 6.682-5.377 8.85-22.635 11.734-40.719 6.805-72.36 9.246-101.466 7.828zm9.312-91.133c-8.937-2.453-17.184-8.504-22.464-16.484-2.261-3.418-5.397-9.778-6.252-12.682-.447-1.52-1.688-3.58-3.643-6.048-6.427-8.114-10.12-17.833-10.083-26.534.025-5.829.92-8.722 2.995-9.686l1.446-.672.013-8.512c.025-15.568 1.247-22.9 5.093-30.556 2.701-5.377 5.659-9.166 9.6-12.298 6.498-5.164 15.045-8.284 25.749-9.4 9.495-.99 20.86.884 28.804 4.748 7.91 3.849 13.037 8.942 17.06 16.95 3.846 7.656 5.067 14.988 5.092 30.556l.014 8.512 1.446.672c2.075.964 2.97 3.857 2.994 9.686.038 8.677-3.655 18.421-10.035 26.474-1.991 2.513-3.216 4.577-3.857 6.496-1.279 3.833-4.577 10.307-6.91 13.564-1.058 1.478-3.308 4.01-4.998 5.627-9.558 9.14-21.11 12.593-32.064 9.587z" transform="translate(318.814 -192.435)"/><path style="stroke-width:.44797176" d="M-229.95 416.802c-17.238-.842-38.713-3.257-61.456-6.908-13.936-2.238-16.59-2.828-20.414-4.545-3.077-1.38-5.026-3.439-5.745-6.066-.722-2.638-.231-12.1.921-17.756 2.09-10.255 7.509-21.157 15.015-30.207 5.365-6.47 15.39-12.477 32.258-19.335 12.613-5.127 19.25-8.096 23.627-10.57 2.154-1.217 4.124-2.212 4.379-2.212.255 0 1.718 1.166 3.251 2.591 10.042 9.332 23.176 13.243 35.021 10.43 7.07-1.679 13.138-4.83 19.04-9.888 1.613-1.382 3.175-2.658 3.472-2.836.304-.184 2.255.645 4.48 1.902 4.4 2.487 11.02 5.45 23.65 10.583 14.447 5.873 23.944 11.143 29.738 16.502.834.77 2.985 3.36 4.78 5.754 9.451 12.598 14.096 25.892 14.085 40.315-.007 9.09-3.218 11.408-19.708 14.223-41.229 7.037-74.791 9.569-106.394 8.023zm14.784-89.994c-13.923-1.999-26.504-13.52-31.814-29.131-.739-2.172-1.97-4.26-4.26-7.223-3.414-4.417-6.111-9.326-7.957-14.48-.958-2.678-1.152-4.14-1.329-10-.225-7.474.364-10.495 2.157-11.064 2.128-.675 2.213-1.086 2.217-10.663.007-15.859 1.004-22.02 4.798-29.646 3.34-6.713 9.393-12.878 15.864-16.157 13.346-6.763 35.812-6.763 49.158 0 6.472 3.28 12.525 9.444 15.865 16.157 3.794 7.627 4.79 13.787 4.798 29.646.004 9.577.089 9.988 2.217 10.663 1.793.57 2.382 3.59 2.156 11.064-.176 5.86-.37 7.322-1.329 10a52.885 52.885 0 0 1-7.925 14.44c-2.254 2.917-3.532 5.084-4.354 7.387-.64 1.792-1.969 4.871-2.953 6.843-7.554 15.117-22.899 24.233-37.31 22.164z" transform="translate(318.814 -192.435)"/><path style="stroke-width:.44797176" d="M-229.502 416.802c-10.375-.507-25.95-1.944-38.443-3.548-13.223-1.697-34.746-5.147-39.172-6.279-4.43-1.132-7.619-2.959-9.291-5.32-1.338-1.889-1.344-1.924-1.325-8.017.04-12.974 3.48-23.874 11.217-35.535 3.73-5.622 8.547-10.704 13.48-14.222 4.969-3.544 14.553-8.26 27.249-13.408 9.665-3.92 18.068-7.764 22.28-10.19l2.12-1.223 2.36 2.208c6.809 6.377 15.965 10.762 24.533 11.748 10.449 1.203 22.247-3.158 32.221-11.912l1.984-1.74 6.976 3.484c3.836 1.917 11.511 5.316 17.055 7.553 24.41 9.853 32.923 15.595 40.858 27.563 6.662 10.048 9.944 18.891 11.135 30.002 1.543 14.41-.787 17.359-15.923 20.146-3.207.591-10.469 1.785-16.135 2.654-36.173 5.545-65.017 7.413-93.179 6.036zm14.784-89.994c-4.09-.56-9.302-2.38-12.849-4.487-3.688-2.191-9.398-7.466-12.491-11.54-2.656-3.497-5.858-9.657-7.06-13.58-.7-2.287-1.64-3.875-4.11-6.944-3.766-4.684-7.244-11.415-8.518-16.483-.658-2.622-.89-5.044-.89-9.31 0-6.718.497-8.404 2.89-9.816l1.59-.938.004-9.027c.007-15.998 1.153-23.006 4.96-30.324 3.503-6.73 10.034-13.116 16.672-16.298 11.867-5.688 31.027-6.275 44.11-1.35 8.244 3.102 15.651 9.712 19.78 17.648 3.808 7.318 4.954 14.326 4.96 30.324l.005 9.027 1.59.938c2.393 1.412 2.89 3.098 2.89 9.816 0 4.266-.232 6.688-.89 9.31-1.268 5.044-4.753 11.8-8.478 16.431-2.449 3.045-3.414 4.686-4.29 7.294-1.45 4.322-3.857 9.119-6.282 12.52-2.25 3.155-8.483 9.294-11.559 11.384-6.192 4.208-14.997 6.368-22.034 5.405z" transform="translate(318.814 -192.435)"/><path style="stroke-width:.44797176" d="M-229.502 416.802c-20.514-1.003-58.82-5.743-75.545-9.35-5.313-1.145-9.874-3.494-11.47-5.908-1.207-1.827-1.234-2.012-1.196-8.171.062-10.221 1.792-17.387 6.475-26.826 4.9-9.879 10.349-16.837 17.121-21.866 4.432-3.29 15.2-8.715 26.09-13.143 12.75-5.185 18.393-7.711 22.755-10.189l3.94-2.237 2.332 2.192c8.964 8.431 20.79 12.816 31.613 11.72 7.902-.8 15.853-4.382 23.444-10.562 1.9-1.546 3.675-2.811 3.943-2.811.27 0 2.042.886 3.94 1.97 3.865 2.208 9.728 4.819 22.266 9.917 10.912 4.437 21.667 9.857 26.09 13.15 9.84 7.324 18.65 21.235 22.02 34.767 1.208 4.852 1.998 12.681 1.709 16.94-.337 4.969-1.456 6.543-6.283 8.841-4.057 1.932-7.876 2.742-26.065 5.53-36.173 5.545-65.017 7.413-93.179 6.036zm15.008-89.963c-12.502-1.69-24.016-10.92-30.411-24.38-.97-2.043-2.065-4.726-2.432-5.963-.439-1.476-1.686-3.51-3.629-5.916-3.517-4.356-7.585-12.198-8.807-16.978-.632-2.47-.863-4.913-.863-9.138 0-6.718.497-8.404 2.89-9.816l1.59-.938.004-9.027c.005-10.826.564-17.738 1.791-22.155 1.203-4.327 4.77-11.36 7.396-14.584 9.198-11.292 26.12-16.54 45.014-13.962 6.552.894 9.394 1.724 14.559 4.248 7.824 3.824 12.922 8.87 16.833 16.664 3.961 7.893 4.883 13.507 4.89 29.789l.004 9.027 1.59.938c2.393 1.412 2.89 3.098 2.89 9.816 0 4.225-.231 6.668-.863 9.138-1.215 4.75-5.291 12.623-8.762 16.922-2.025 2.508-3.199 4.464-3.843 6.402-1.21 3.645-4.953 11.091-6.922 13.775-.856 1.166-3.471 3.95-5.811 6.187-3.548 3.392-5.019 4.443-8.85 6.328-6.575 3.234-12.474 4.404-18.258 3.623z" transform="translate(318.814 -192.435)"/><path style="stroke-width:.44797176" d="M-229.054 416.802c-17.256-.843-38.713-3.256-61.52-6.918-14.252-2.289-18.208-3.187-21.595-4.903-3.274-1.66-4.776-3.455-5.402-6.46-1.02-4.892.42-16.561 2.965-24.028 2.73-8.008 8.788-18.3 14.238-24.187 6.022-6.504 15.765-12.022 35.029-19.838 10.276-4.169 17.617-7.529 21.61-9.89l2.349-1.39 2.58 2.36c3.68 3.369 6.542 5.364 10.642 7.42 14.777 7.414 30.464 5.108 44.335-6.518 1.838-1.54 3.524-2.8 3.747-2.8.223 0 1.995.895 3.936 1.988 4.027 2.268 10.978 5.362 23.242 10.347 21.324 8.669 29.685 14.434 37.083 25.572 7.097 10.685 10.555 20.195 11.62 31.953.862 9.503-.164 12.634-4.977 15.187-3.385 1.795-6.044 2.506-15.28 4.082-40.915 6.983-73.993 9.52-104.602 8.023zm14.783-90.012c-7.774-1.018-14.933-4.72-21.077-10.9-5.559-5.591-9.574-12.073-11.685-18.862-.599-1.927-1.7-3.765-3.892-6.496-3.702-4.61-7.574-12.205-8.859-17.372-.746-3.002-.894-4.797-.775-9.408.164-6.338.638-7.852 2.835-9.061l1.344-.74.278-12.059c.358-15.559 1.144-19.768 5.134-27.515 6-11.65 16.302-18.31 31.546-20.394 12.515-1.71 25.203.279 34.259 5.373 9.694 5.453 16.31 15.267 18.633 27.636.293 1.562.658 8.267.81 14.9l.278 12.06 1.344.739c2.198 1.209 2.672 2.723 2.835 9.061.12 4.611-.028 6.406-.774 9.408-1.268 5.099-5.163 12.746-8.85 17.372-2.219 2.786-3.318 4.65-4.093 6.944-1.392 4.117-4.193 9.644-6.586 12.991-2.241 3.136-7.58 8.382-10.676 10.49-6.473 4.406-15.227 6.724-22.029 5.833z" transform="translate(318.814 -192.435)"/><path style="stroke-width:.44797176" d="M-228.606 416.802c-17.256-.843-38.713-3.256-61.52-6.918-14.069-2.26-18.443-3.243-21.884-4.92-4.866-2.371-6.329-5.83-5.766-13.631.899-12.441 4.553-22.786 11.963-33.868 7.738-11.572 15.532-16.782 39.802-26.608 5.543-2.244 13.309-5.684 17.256-7.643 3.947-1.96 7.385-3.563 7.64-3.563.254 0 1.157.725 2.006 1.612 2.574 2.688 6.351 5.396 10.55 7.563 15.045 7.763 31.065 5.453 44.927-6.477l3.228-2.779 7.257 3.604c3.992 1.981 11.793 5.44 17.337 7.683 24.242 9.815 32.168 15.11 39.814 26.602 7.16 10.761 10.608 20.141 11.77 32.02.577 5.894.18 9.902-1.186 11.987-2.174 3.318-7.195 5.214-19.264 7.274-41.005 6.997-73.752 9.538-103.93 8.062zm14.783-90.038c-7.06-.84-15.174-4.727-20.709-9.919-5.053-4.74-10.026-12.613-12.233-19.369-.697-2.132-1.768-4.068-3.345-6.048-4.73-5.936-6.962-9.949-8.97-16.127-1-3.077-1.278-4.894-1.448-9.472-.264-7.11.452-9.879 2.85-11.022l1.522-.726.004-8.765c.007-15.635 1.142-22.621 4.918-30.267 5.962-12.07 16.479-18.941 32.26-21.073 5.616-.76 9.674-.813 14.795-.196 16.716 2.012 27.432 8.798 33.592 21.27 3.776 7.645 4.91 14.631 4.918 30.266l.004 8.765 1.522.726c2.398 1.143 3.114 3.912 2.85 11.022-.355 9.55-3.158 16.45-10.404 25.6-1.567 1.978-2.71 4.06-3.565 6.495-2.574 7.325-6.4 13.461-11.362 18.227-5.43 5.216-10.5 8.115-17.021 9.736-4.325 1.075-6.757 1.284-10.177.877z" transform="translate(318.814 -192.435)"/><path style="stroke-width:.44797176" d="M-228.606 416.802c-17.256-.843-38.713-3.256-61.52-6.918-14.534-2.334-18.177-3.176-21.919-5.065-3.44-1.736-4.833-3.42-5.504-6.65-.984-4.742.415-15.799 2.94-23.228 2.678-7.88 9.07-18.936 14.235-24.619 5.225-5.75 15.579-11.701 31.899-18.336 12.37-5.03 19.25-8.104 23.162-10.352 1.898-1.091 3.721-1.983 4.052-1.983.331 0 1.4.82 2.373 1.824 8.07 8.314 20.384 12.797 31.877 11.605 8.01-.83 14.945-3.848 21.892-9.526 5.44-4.446 4.564-4.246 8.556-1.952 3.964 2.279 10.803 5.337 23.217 10.384 16.16 6.57 26.528 12.504 31.805 18.204 4.58 4.946 10.68 15.098 13.486 22.443 1.974 5.165 3.266 10.958 3.821 17.125.846 9.41-.223 12.376-5.406 14.993-3.511 1.772-6.199 2.481-15.26 4.028-40.743 6.953-73.6 9.495-103.706 8.023zm14.783-90.036c-4.025-.491-9.393-2.36-13.663-4.755-4.36-2.446-10.424-8.137-13.178-12.365-2.168-3.328-5.292-9.666-6.315-12.81-.5-1.538-1.85-3.765-3.667-6.049-3.5-4.397-7.873-13.003-9.102-17.91-1.139-4.546-1.217-12.127-.157-15.238.58-1.7 1.041-2.273 2.24-2.774l1.51-.63.003-8.842c.007-15.972 1.1-22.51 5.098-30.468 2.673-5.321 5.583-9.053 9.366-12.014 14.283-11.175 39.363-12.716 56.312-3.46 6.661 3.64 10.94 8.17 14.609 15.474 3.997 7.957 5.09 14.496 5.098 30.468l.004 8.842 1.51.63c1.198.501 1.66 1.073 2.24 2.774 1.06 3.11.98 10.692-.158 15.239-1.221 4.871-5.603 13.51-9.07 17.878-1.86 2.346-3.201 4.59-3.88 6.496-1.476 4.145-4.735 10.522-6.983 13.663-2.224 3.107-6.668 7.461-9.788 9.59-3.234 2.207-9.083 4.79-12.621 5.575-4.196.93-6.204 1.077-9.407.686z" transform="translate(318.814 -192.435)"/><path style="stroke-width:.44797176" d="M-228.606 416.802c-17.256-.843-38.713-3.256-61.52-6.918-14.559-2.338-18.176-3.175-21.95-5.08-4.867-2.457-6.258-5.738-5.701-13.45.663-9.188 2.672-16.529 6.799-24.852 4.722-9.522 10.447-16.721 17.412-21.896 4.499-3.342 15.799-8.915 28.675-14.142 10.283-4.174 16.604-7.055 20.912-9.53l2.545-1.464 2.158 2.01c9.232 8.599 20.86 12.783 32.246 11.602 8.303-.86 15.93-4.326 23.768-10.8l3.234-2.671 3.486 2.003c3.944 2.267 10.798 5.332 23.196 10.372 10.872 4.42 20.78 9.417 25.2 12.708 6.651 4.953 12.282 11.91 16.852 20.822 4.135 8.064 6.222 15.17 7.065 24.06.878 9.256-.308 12.598-5.38 15.16-3.544 1.788-6.213 2.494-15.291 4.043-40.743 6.953-73.6 9.495-103.706 8.023zm14.783-90.036c-7.385-.901-14.565-4.47-20.51-10.195-6.077-5.853-9.977-11.939-12.61-19.683-.76-2.231-1.867-4.085-4.065-6.809-3.571-4.423-7.42-12.004-8.733-17.201-.617-2.447-.895-5.026-.895-8.333 0-6.34.902-8.908 3.472-9.89.998-.382 1.008-.473 1.012-9.245.007-16.028 1.097-22.52 5.131-30.552 6.226-12.396 17.843-19.505 34.592-21.166 4.348-.431 6.683-.431 11.006 0 12.84 1.282 20.965 4.83 28.375 12.391 2.852 2.91 4.113 4.65 5.915 8.158 4.348 8.464 5.457 14.79 5.464 31.17.004 8.771.013 8.862 1.011 9.243 2.57.983 3.472 3.551 3.472 9.891 0 3.307-.277 5.886-.895 8.333-1.308 5.178-5.162 12.78-8.704 17.167-2.154 2.668-3.313 4.607-4.132 6.913-1.484 4.179-4.753 10.699-6.679 13.323-.84 1.143-3.548 4.019-6.02 6.39-3.862 3.708-5.136 4.624-9.078 6.525-6.376 3.076-11.842 4.215-17.129 3.57z" transform="translate(318.814 -192.435)"/><path style="stroke-width:.44797176" d="M-227.934 416.814c-17-.828-39.725-3.374-61.566-6.896-15.115-2.438-19.292-3.398-22.703-5.216-3.267-1.741-4.62-3.485-5.32-6.855-.94-4.533.469-15.436 2.97-22.977 2.63-7.923 8.797-18.338 14.436-24.376 6.292-6.738 15.807-12.147 35.226-20.026 9.803-3.977 17.336-7.408 21.383-9.739l2.121-1.221 2.36 2.208c6.72 6.294 15.156 10.331 23.766 11.374 10.919 1.323 22.067-2.381 31.789-10.562l3.393-2.855 3.511 1.977c4.008 2.256 10.974 5.357 23.222 10.335 21.382 8.692 29.978 14.65 37.516 25.998 6.928 10.43 10.388 19.886 11.58 31.642 1.36 13.418-1.484 15.896-22.218 19.361-40.719 6.805-72.36 9.246-101.466 7.828zm9.305-91.135c-6.718-1.844-11.8-4.98-17.18-10.602-5.24-5.476-9.39-12.324-11.16-18.418-.557-1.917-1.579-3.63-3.854-6.466-6.288-7.838-9.815-17.038-9.81-25.588.004-6.298.94-8.983 3.47-9.95.996-.382 1.007-.48 1.011-9.02.002-4.75.202-10.853.445-13.563 1.71-19.09 12.465-32.331 29.806-36.69 10.07-2.532 19.847-2.546 29.953-.043 11.21 2.776 20.005 9.61 25.125 19.522 3.89 7.531 5.147 15.036 5.154 30.773.004 8.54.015 8.64 1.011 9.02 2.53.968 3.466 3.653 3.47 9.95.005 8.524-3.523 17.752-9.756 25.52-2.274 2.835-3.298 4.575-4.063 6.905-1.324 4.034-4.134 9.598-6.56 12.99-2.245 3.141-8.037 8.84-11.098 10.922-7.93 5.392-17.33 7.108-25.964 4.738z" transform="translate(318.814 -192.435)"/><path style="stroke-width:.44797176" d="M-229.502 416.354c-17.256-.843-38.713-3.256-61.52-6.918-18.765-3.013-22.635-4.245-25.114-7.993-1.118-1.69-1.167-2.03-1.15-7.846.04-12.918 3.492-23.849 11.199-35.465 7.857-11.844 16.055-17.38 40.3-27.211 9.665-3.92 18.068-7.764 22.28-10.19l2.12-1.223 2.36 2.208c6.809 6.377 15.965 10.762 24.533 11.748 10.449 1.203 22.247-3.158 32.221-11.912l1.984-1.74 6.976 3.484c3.836 1.917 11.511 5.316 17.055 7.553 19.074 7.699 28.073 12.79 34.42 19.471 4.93 5.191 10.715 14.518 13.485 21.743 3.798 9.908 5.224 25.284 2.72 29.336-1.053 1.703-3.542 3.364-6.73 4.488-2.5.882-10.689 2.397-23.96 4.431-36.173 5.545-65.017 7.413-93.179 6.036zm14.784-89.994c-4.084-.559-9.3-2.379-12.833-4.477-3.707-2.202-9.016-7.09-12.06-11.102-2.656-3.499-5.858-9.66-7.06-13.58-.7-2.287-1.64-3.875-4.108-6.944-3.767-4.684-7.246-11.415-8.519-16.483-.658-2.622-.89-5.044-.89-9.31 0-6.542.515-8.421 2.599-9.499.728-.377 1.45-1.012 1.603-1.41.153-.4.28-4.59.282-9.312.007-15.532 1.184-22.626 4.96-29.884 3.479-6.686 9.634-12.692 16.266-15.87 11.824-5.668 31.007-6.247 44.067-1.33 8.206 3.088 15.253 9.358 19.333 17.2 3.776 7.258 4.954 14.352 4.96 29.884.003 4.722.13 8.913.283 9.312.153.398.874 1.033 1.603 1.41 2.084 1.078 2.598 2.957 2.598 9.499 0 4.266-.231 6.688-.89 9.31-1.267 5.044-4.753 11.8-8.477 16.431-2.449 3.045-3.414 4.686-4.29 7.294-1.45 4.32-3.857 9.117-6.282 12.52-2.24 3.142-8.025 8.836-11.11 10.935-6.188 4.208-14.997 6.369-22.035 5.406z" transform="translate(318.814 -192.435)"/><path style="stroke-width:.44797176" d="M-229.502 416.354c-16.78-.82-38.346-3.218-60.252-6.7-15.139-2.405-18.569-3.184-22.3-5.063-4.814-2.425-5.26-3.387-5.211-11.25.062-10.187 1.797-17.366 6.475-26.794 4.876-9.829 10.101-16.542 16.673-21.42 4.434-3.29 15.204-8.715 26.09-13.141 12.75-5.185 18.393-7.711 22.755-10.189l3.94-2.237 2.332 2.192c8.964 8.431 20.79 12.816 31.613 11.72 7.902-.8 15.853-4.382 23.444-10.562 1.9-1.546 3.675-2.811 3.943-2.811.27 0 2.042.886 3.94 1.97 3.865 2.208 9.728 4.819 22.266 9.917 10.906 4.435 21.664 9.856 26.09 13.148 6.383 4.748 11.44 11.104 16.162 20.313 4.064 7.924 5.924 14.059 6.83 22.52.703 6.572.365 11.35-.952 13.482-1.59 2.572-6.773 4.814-14.308 6.187-39.69 7.235-77.81 10.27-109.53 8.718zm15.008-89.963c-12.46-1.684-23.633-10.608-29.963-23.933-.97-2.042-2.065-4.725-2.432-5.962-.439-1.476-1.686-3.51-3.629-5.916-3.517-4.356-7.585-12.198-8.807-16.978-.989-3.865-1.21-13.748-.361-16.18.302-.867 1.193-1.876 2.24-2.537l1.738-1.098.004-9.065c.007-16.323.926-21.93 4.89-29.828 3.896-7.765 8.566-12.383 16.385-16.202 8.988-4.39 20.485-5.899 32.478-4.262 6.56.896 9.4 1.725 14.559 4.254 7.829 3.838 12.491 8.45 16.385 16.21 3.964 7.898 4.883 13.505 4.89 29.828l.004 9.065 1.738 1.098c1.047.66 1.938 1.67 2.24 2.536.848 2.433.628 12.316-.361 16.181-1.215 4.75-5.291 12.623-8.762 16.922-2.025 2.508-3.199 4.464-3.843 6.402-1.212 3.65-4.953 11.092-6.927 13.782-.859 1.17-3.379 3.855-5.6 5.97-3.298 3.138-4.878 4.255-8.62 6.096-6.563 3.228-12.465 4.398-18.246 3.617z" transform="translate(318.814 -192.435)"/><path style="stroke-width:.44797176" d="M-229.054 416.354c-17.256-.843-38.713-3.256-61.52-6.918-14.174-2.276-18.211-3.189-21.514-4.862-4.683-2.373-5.774-5.118-5.253-13.222.787-12.253 4.427-22.742 11.67-33.624 7.615-11.443 15.7-16.818 40.332-26.812 10.276-4.169 17.617-7.529 21.61-9.89l2.349-1.39 2.58 2.36c3.68 3.369 6.542 5.364 10.642 7.42 14.778 7.414 30.464 5.108 44.335-6.518 1.838-1.54 3.524-2.8 3.747-2.8.223 0 1.995.895 3.936 1.988 4.027 2.268 10.978 5.362 23.242 10.347 16.005 6.507 25.564 12.021 31.106 17.945 4.639 4.959 10.706 14.948 13.445 22.133 2.856 7.495 4.708 20.177 3.722 25.482-.637 3.42-1.507 4.607-4.596 6.266-3.316 1.78-5.987 2.494-15.231 4.072-40.915 6.983-73.993 9.52-104.602 8.023zm14.783-90.012c-7.66-1.003-14.945-4.734-20.852-10.678-5.335-5.368-9.383-11.95-11.462-18.636-.599-1.927-1.7-3.765-3.892-6.496-3.65-4.546-7.559-12.17-8.822-17.206-.657-2.62-.88-4.955-.855-8.96.04-6.275.576-8.272 2.465-9.18.72-.347 1.457-.85 1.638-1.12.181-.27.334-4.422.34-9.227.006-4.804.21-10.975.451-13.713 1.33-15.013 8.773-26.638 20.753-32.407 14.266-6.87 35.89-6.451 49.13.952 10.814 6.046 17.565 17.332 18.815 31.455.243 2.738.446 8.909.451 13.713.006 4.805.16 8.957.34 9.227.181.27.918.773 1.638 1.12 1.89.908 2.426 2.905 2.465 9.18.025 4.005-.197 6.34-.855 8.96-1.246 4.967-5.178 12.647-8.811 17.206-2.22 2.786-3.319 4.65-4.094 6.944-1.39 4.114-4.192 9.642-6.585 12.991-2.228 3.12-7.12 7.92-10.229 10.04-6.465 4.406-15.225 6.727-22.029 5.835z" transform="translate(318.814 -192.435)"/><path style="stroke-width:.44797176" d="M-228.83 416.366c-15.75-.767-36.517-3-56.22-6.044-20.91-3.23-25.162-4.249-28.831-6.906-3.035-2.2-3.43-3.375-3.379-10.074.098-12.776 3.561-23.296 11.712-35.578 7.708-11.616 15.557-16.84 40.433-26.91 5.543-2.245 13.104-5.583 16.801-7.418 3.697-1.836 6.933-3.337 7.192-3.337.258 0 1.164.725 2.013 1.612 4.067 4.248 11.442 8.604 17.67 10.438 12.365 3.639 25.85.568 36.57-8.328 1.724-1.431 3.484-2.852 3.91-3.156.656-.469 1.803-.042 7.474 2.778 3.685 1.833 11.235 5.168 16.779 7.412 18.564 7.513 28.006 12.718 34.075 18.783 4.725 4.722 11.449 15.493 14.278 22.873 3.58 9.337 5.116 24.014 2.952 28.198-.91 1.762-3.675 3.77-6.799 4.939-2.856 1.068-10.277 2.479-24.571 4.67-35.926 5.507-64.558 7.388-92.059 6.048zm14.56-90.024c-6.716-.88-14.313-4.53-19.758-9.494-4.958-4.52-10.601-13.433-12.547-19.82-.528-1.734-1.633-3.72-3.113-5.6-7.598-9.644-10.5-17.089-10.5-26.94 0-6.394.615-8.384 2.931-9.488l1.55-.739.003-9.078c.002-4.994.202-11.32.445-14.057 1.33-15.028 8.941-26.747 21.18-32.614 11.81-5.66 30.244-6.238 43.22-1.354 13.995 5.268 22.853 17.65 24.297 33.968.243 2.738.443 9.063.445 14.057l.004 9.078 1.549.739c2.316 1.104 2.93 3.094 2.93 9.487 0 9.839-2.886 17.258-10.458 26.88-1.437 1.826-2.619 3.967-3.289 5.957-6.253 18.569-23.04 31.095-38.89 29.018z" transform="translate(318.814 -192.435)"/><path style="stroke-width:.44797176" d="M-228.606 416.354c-10.375-.507-25.95-1.944-38.443-3.548-13.064-1.676-34.729-5.142-39.044-6.246-4.22-1.08-8.415-3.206-9.596-4.865-2.11-2.963-2.289-9.652-.505-18.914 2.808-14.578 11.927-29.782 22.628-37.728 4.5-3.34 15.807-8.917 28.675-14.14 10.283-4.175 16.604-7.056 20.912-9.532l2.545-1.463 2.158 2.01c9.232 8.599 20.86 12.783 32.246 11.602 8.303-.86 15.93-4.326 23.768-10.8l3.234-2.671 3.486 2.003c3.944 2.267 10.798 5.332 23.196 10.372 10.868 4.418 20.779 9.415 25.2 12.706 6.471 4.817 11.853 11.502 16.404 20.376 4.135 8.064 6.222 15.17 7.065 24.06.87 9.173-.185 12.315-4.944 14.717-3.531 1.783-6.207 2.49-15.28 4.038-40.742 6.953-73.599 9.495-103.705 8.023zm14.783-90.036c-7.256-.885-14.546-4.468-20.275-9.965-5.846-5.607-9.792-11.803-12.398-19.465-.759-2.231-1.866-4.085-4.064-6.809-3.623-4.487-7.437-12.042-8.772-17.374-.742-2.964-.9-4.777-.78-8.96.166-5.851.683-7.422 2.836-8.606l1.344-.74.278-12.059c.357-15.496 1.114-19.58 5.108-27.534 6.088-12.124 17.461-19.014 34.117-20.666 9.12-.905 19.032.459 26.776 3.682 7.952 3.31 14.31 9.087 18.053 16.398 4.324 8.446 5.068 12.301 5.433 28.12l.277 12.06 1.344.739c2.153 1.184 2.67 2.755 2.837 8.607.12 4.182-.039 5.995-.78 8.96-1.33 5.312-5.15 12.887-8.744 17.338-2.154 2.669-3.313 4.608-4.132 6.914-1.486 4.183-4.753 10.7-6.684 13.33-.842 1.146-3.455 3.924-5.806 6.172-3.614 3.455-4.985 4.43-8.85 6.294-6.366 3.07-11.834 4.209-17.118 3.564z" transform="translate(318.814 -192.435)"/><path style="stroke-width:.44797176" d="M-227.934 416.366c-17-.828-39.725-3.374-61.566-6.896-15.064-2.43-19.294-3.399-22.65-5.188-3.165-1.686-4.228-3.078-4.928-6.448-.938-4.52.475-15.434 2.974-22.964 2.578-7.773 8.72-18.214 14.184-24.116 6.022-6.504 15.765-12.022 35.029-19.838 9.803-3.977 17.336-7.408 21.383-9.739l2.121-1.222 2.36 2.21c6.72 6.293 15.156 10.33 23.766 11.373 10.918 1.323 22.067-2.381 31.789-10.562l3.393-2.855 3.511 1.977c4.008 2.256 10.973 5.357 23.222 10.335 16.164 6.571 25.492 11.976 31.306 18.138 4.856 5.148 11.087 15.359 13.661 22.388 2.869 7.832 4.598 19.439 3.67 24.633-1.117 6.248-4.835 8.118-21.759 10.946-40.719 6.805-72.36 9.246-101.466 7.828zm9.305-91.135c-6.642-1.823-11.83-4.999-16.96-10.382-5.025-5.273-9.196-12.213-10.932-18.19-.56-1.924-1.577-3.627-3.883-6.495-4.091-5.09-7.587-12.048-8.894-17.701-.792-3.428-.954-5.256-.81-9.124.2-5.417.77-7.065 2.832-8.2l1.344-.74.279-11.835c.36-15.264 1.296-20.338 5.115-27.722 6.385-12.342 17.294-18.909 34.37-20.689 9.254-.964 20.793.873 28.297 4.505 11.32 5.479 18.263 15.226 20.916 29.363.28 1.489.633 8.033.787 14.543l.278 11.836 1.344.739c2.063 1.135 2.633 2.783 2.833 8.2.144 3.868-.019 5.696-.81 9.124-1.3 5.62-4.805 12.614-8.84 17.632-2.306 2.87-3.324 4.594-4.092 6.934-1.324 4.032-4.133 9.596-6.559 12.99-2.234 3.127-7.578 8.38-10.651 10.472-7.921 5.392-17.33 7.11-25.964 4.74z" transform="translate(318.814 -192.435)"/><path style="stroke-width:.44797176" d="M-229.95 415.906c-10.375-.507-25.95-1.944-38.443-3.548-12.147-1.559-34.495-5.095-38.244-6.052-4.274-1.09-8.006-3.134-9.184-5.027-.97-1.56-1.032-2.091-.98-8.385.105-12.796 3.391-23.038 11.142-34.729 7.708-11.628 15.681-16.986 39.872-26.796 9.678-3.925 18.064-7.762 22.3-10.202l2.14-1.233 2.115 2.022c8 7.648 18.646 12.114 28.776 12.07 4.49-.019 10.791-1.48 15.404-3.571 3.563-1.616 9.37-5.432 12.552-8.248l2.204-1.951 6.979 3.486c3.839 1.918 11.515 5.317 17.059 7.555 24.293 9.805 32.35 15.186 39.99 26.708 6.684 10.082 9.996 18.917 11.112 29.648.704 6.765.41 11.586-.83 13.59-1.887 3.055-6.213 4.696-17.438 6.614-41.317 7.061-74.876 9.597-106.526 8.05zm14.784-89.994c-11.347-1.629-22.037-9.932-28.132-21.85-1.096-2.144-2.445-5.28-2.998-6.968-.749-2.289-1.782-4.04-4.067-6.887-3.348-4.172-6.12-9.132-7.907-14.144-.98-2.75-1.181-4.208-1.36-9.901-.254-8.04.131-9.825 2.343-10.88.83-.396 1.623-1.144 1.762-1.662.14-.517.257-4.872.263-9.677.005-4.804.21-10.992.454-13.751 1.186-13.4 7.904-24.38 18.587-30.379 13.176-7.399 37.445-7.399 50.62 0 10.683 5.999 17.402 16.98 18.588 30.379.244 2.759.448 8.947.454 13.751.005 4.805.123 9.16.262 9.677.14.518.933 1.266 1.763 1.662 2.211 1.055 2.596 2.84 2.343 10.88-.18 5.693-.38 7.15-1.36 9.901-1.781 4.995-4.557 9.968-7.867 14.094-2.287 2.85-3.33 4.635-4.287 7.335-2.772 7.818-6.172 13.24-11.526 18.38-3.879 3.723-6.57 5.63-10.406 7.369-5.367 2.433-12.072 3.455-17.53 2.671z" transform="translate(318.814 -192.435)"/><path style="stroke-width:.44797176" d="M-229.502 415.906c-16.78-.82-38.346-3.218-60.252-6.7-14.784-2.349-18.586-3.19-21.844-4.835-3.597-1.816-4.888-3.43-5.285-6.609-.664-5.314.79-16.658 2.948-23.01 2.472-7.279 9.027-18.5 14.009-23.982 5.225-5.75 15.579-11.701 31.899-18.336 12.416-5.048 19.252-8.106 23.22-10.385l3.508-2.017 2.539 2.397c6.737 6.36 15.444 10.482 24.266 11.487 10.05 1.146 21.518-2.821 30.772-10.645 1.772-1.499 3.417-2.724 3.656-2.724.24 0 1.813.791 3.498 1.759 3.547 2.036 10.743 5.237 22.774 10.128 16.16 6.57 26.528 12.504 31.805 18.204 4.553 4.917 10.747 15.21 13.402 22.27 2.325 6.18 3.86 14.852 3.835 21.648-.023 5.941-1.18 7.839-6.185 10.14-3.294 1.513-8.715 2.619-25.386 5.174-36.173 5.545-65.017 7.413-93.179 6.037zm14.974-89.964c-7.32-.977-14.384-4.596-20.152-10.323-2.109-2.094-4.6-4.984-5.536-6.421-2.273-3.489-5.387-9.834-6.226-12.683-.447-1.52-1.688-3.58-3.643-6.048-3.618-4.567-7.594-12.18-8.777-16.805-.648-2.535-.878-4.938-.878-9.198 0-6.586.556-8.658 2.447-9.133.638-.16 1.357-.658 1.597-1.106.24-.45.438-4.8.44-9.692.007-15.854.957-21.81 4.657-29.175 3.968-7.897 8.79-12.676 16.593-16.443 8.63-4.166 20.32-5.639 32.055-4.037 6.405.874 9.37 1.72 14.135 4.029 7.817 3.789 12.628 8.559 16.593 16.451 3.7 7.366 4.65 13.32 4.658 29.175.002 4.892.2 9.242.44 9.692.24.448.958.946 1.597 1.106 1.891.475 2.446 2.547 2.446 9.133 0 4.26-.23 6.663-.877 9.198-1.178 4.603-5.155 12.232-8.73 16.745-1.991 2.513-3.216 4.577-3.856 6.496-1.279 3.83-4.577 10.306-6.91 13.564-2.215 3.096-6.665 7.46-9.756 9.569-6.705 4.575-15.284 6.845-22.317 5.906z" transform="translate(318.814 -192.435)"/><path style="stroke-width:.44797176" d="M-229.054 415.906c-17.256-.843-38.713-3.256-61.52-6.918-17.389-2.792-21.222-3.863-24.36-6.803-1.722-1.613-2.334-4.998-1.96-10.833.794-12.337 4.416-22.717 11.811-33.84 7.29-10.966 15.176-16.152 39.744-26.14 10.294-4.185 17.633-7.546 21.61-9.899l2.349-1.388 2.58 2.36c3.68 3.368 6.542 5.363 10.642 7.42 14.778 7.413 30.464 5.107 44.335-6.519 1.838-1.54 3.524-2.8 3.747-2.8.223 0 1.995.895 3.936 1.988 4.027 2.268 10.978 5.362 23.242 10.347 15.846 6.442 25.646 12.071 30.91 17.757 4.469 4.827 10.52 14.86 13.193 21.873 2.879 7.555 4.709 20.174 3.707 25.561-1.036 5.571-4.346 7.248-19.364 9.811-40.915 6.983-73.993 9.52-104.602 8.024zm14.783-90.012c-7.539-.988-14.957-4.747-20.627-10.455-5.112-5.146-9.193-11.83-11.239-18.411-.599-1.927-1.7-3.765-3.892-6.496-3.65-4.546-7.559-12.17-8.822-17.206-.657-2.62-.88-4.955-.855-8.96.04-6.436.51-8.043 2.658-9.115l1.564-.78.277-12.32c.348-15.528 1.12-19.856 4.876-27.326 3.618-7.199 8.714-12.234 15.867-15.679 14.225-6.85 35.906-6.418 49.088.977 9.445 5.299 15.636 14.567 17.95 26.87.295 1.561.66 8.383.812 15.158l.276 12.32 1.565.78c2.148 1.072 2.617 2.679 2.657 9.115.025 4.005-.197 6.34-.855 8.96-1.246 4.967-5.178 12.647-8.811 17.206-2.22 2.786-3.319 4.65-4.094 6.944-4.013 11.872-11.749 20.955-21.741 25.526-5.475 2.505-11.554 3.56-16.654 2.892z" transform="translate(318.814 -192.435)"/><path style="stroke-width:.44797176" d="M-229.054 415.906c-17.256-.843-38.713-3.256-61.52-6.918-18.767-3.014-22.636-4.245-25.114-7.994-1.116-1.689-1.166-2.026-1.124-7.621.095-12.493 3.582-23.352 11.173-34.793 7.883-11.884 16.112-17.425 40.524-27.287 5.543-2.24 13.155-5.6 16.915-7.466l6.836-3.394 2.124 1.967c4.751 4.404 11.761 8.419 17.802 10.197 12.647 3.722 25.8.578 37.639-8.998l3.703-2.996 6.655 3.31c3.66 1.82 11.191 5.142 16.735 7.381 24.367 9.842 32.636 15.393 40.423 27.137 6.649 10.028 9.933 18.882 11.121 29.983.984 9.193.044 12.982-3.777 15.22-3.844 2.254-8.16 3.22-27.832 6.236-36.04 5.524-64.578 7.391-92.283 6.037zm14.783-90.01c-4.04-.529-9.275-2.348-12.832-4.461-3.707-2.202-9.016-7.09-12.06-11.102-2.656-3.499-5.858-9.66-7.06-13.58-.7-2.287-1.64-3.875-4.108-6.944-3.767-4.684-7.246-11.415-8.519-16.483-1.011-4.028-1.237-13.02-.388-15.457.302-.867 1.193-1.876 2.24-2.536l1.738-1.098.004-9.065c.005-11.471.58-17.966 2.033-22.981 3.698-12.762 13.119-22.177 25.738-25.723 11.582-3.255 26.156-2.8 36.627 1.14 10.776 4.057 18.917 13.047 22.26 24.583 1.454 5.015 2.028 11.51 2.033 22.98l.004 9.066 1.738 1.098c1.047.66 1.938 1.67 2.24 2.536.85 2.436.623 11.429-.388 15.457-1.267 5.044-4.753 11.8-8.478 16.431-2.448 3.045-3.414 4.686-4.29 7.294-1.45 4.32-3.856 9.117-6.282 12.52-2.239 3.142-8.024 8.836-11.11 10.935-6.059 4.12-14.46 6.262-21.14 5.39z" transform="translate(318.814 -192.435)"/><path style="stroke-width:.44797176" d="M-228.606 415.906c-17.256-.843-38.713-3.256-61.52-6.918-14.174-2.276-18.211-3.189-21.514-4.862-4.627-2.345-5.79-5.182-5.24-12.793.87-12.04 4.527-22.445 11.656-33.157 7.616-11.443 15.702-16.818 40.333-26.812 9.803-3.977 17.336-7.408 21.383-9.739l2.121-1.222 2.36 2.21c3.335 3.123 6.288 5.178 10.421 7.252 14.989 7.52 31.278 5.176 45.214-6.505l3.324-2.785 3.505 1.974c4.003 2.253 10.973 5.355 23.217 10.332 16.005 6.507 25.564 12.021 31.106 17.945 4.639 4.959 10.706 14.948 13.445 22.133 1.731 4.544 3.178 11.188 3.686 16.929 1.135 12.838-1.012 14.79-19.791 17.995-40.743 6.953-73.6 9.495-103.706 8.024zm14.783-90.036c-7.543-.92-14.929-4.694-20.852-10.654-5.335-5.368-9.383-11.95-11.462-18.636-.599-1.927-1.7-3.765-3.892-6.496-3.65-4.545-7.559-12.17-8.822-17.206-.637-2.54-.879-4.95-.855-8.512.042-6.12.58-7.828 2.8-8.89l1.646-.788.004-9.068c.007-15.948 1.102-22.747 4.875-30.25 6.23-12.388 17.233-19.124 33.952-20.782 8.554-.848 18.504.393 25.677 3.204 13.777 5.399 22.303 17.53 23.742 33.782.242 2.738.443 9.058.445 14.046l.004 9.068 1.645.787c2.22 1.063 2.758 2.771 2.8 8.891.025 3.561-.217 5.972-.854 8.512-1.246 4.967-5.18 12.647-8.812 17.206-2.22 2.786-3.319 4.65-4.094 6.944-1.39 4.115-4.192 9.642-6.584 12.991-2.23 3.12-7.12 7.92-10.23 10.04-6.286 4.284-14.695 6.596-21.132 5.811z" transform="translate(318.814 -192.435)"/>
  </symbol>
  
  <symbol id="camera" viewBox="0 0 149.235 114.844">
	<path style="fill:#d1d1d1;stroke-width:.44797176" d="M-787.165 722.639c-16.298-1.453-35-7.287-48.76-15.208-10.142-5.84-17.82-11.739-26.152-20.094-19.636-19.69-31.274-43.612-35-71.941-.482-3.671-.588-71.638-.588-376.52 0-356.85.033-372.26.811-377.417 3.418-22.64 11.75-42.117 25.199-58.908 4.208-5.253 13.396-14.36 18.854-18.686 17.518-13.887 36.593-21.894 61.305-25.734 2.778-.432 32.64-.589 132.885-.699l129.3-.142 66.911-67.795c36.801-37.287 70.741-71.73 75.422-76.538 4.682-4.81 10.433-10.268 12.782-12.131 18.593-14.747 40.822-23.62 64.718-25.835 6.999-.649 313.964-.626 320.971.023 26.35 2.443 49.88 13.024 70.343 31.63 2.66 2.42 37.428 37.305 77.26 77.522l72.42 73.123 126.63.13 126.63.13 6.23 1.02c33.698 5.513 61.155 21.989 80.787 48.479 12.697 17.13 20.615 38.489 22.51 60.71.356 4.184.45 108.705.337 375.597l-.156 369.801-.955 5.6c-4.736 27.752-15.737 49.538-34.552 68.422-18.953 19.023-40.844 30.022-69.498 34.919-4.508.77-31.107.806-638.584.86-348.634.03-635.76-.113-638.06-.318zm662.826-183.38c36.096-3.076 69.742-11.357 101.562-24.995 72.247-30.966 130.66-85.718 165.72-155.332 9.936-19.728 15.867-34.349 21.524-53.064 17.347-57.385 18.809-119.498 4.185-177.865C155.81 76.75 129.93 28.119 94.272-11.765 53.205-57.697-1.248-91.855-60.68-108.966c-43.074-12.401-92.046-15.755-137.03-9.385-34.625 4.904-65.534 14.303-96.972 29.486-74.687 36.071-132.395 97.964-163.704 175.574-21.352 52.927-28.272 112.261-19.942 170.981 9.602 67.68 41.979 133.094 90.442 182.724 29.535 30.246 63.788 54.14 102.822 71.726 33.694 15.18 73.376 25.052 109.753 27.305 4.312.268 8.747.562 9.855.655 4.712.394 33.536-.196 41.117-.842zm-41.565-78.496c-3.832-.22-16.498-1.482-19.486-1.943-12.737-1.96-19.424-3.29-28.447-5.655-36.724-9.625-67.587-25.397-97.287-49.716-7.743-6.34-23.199-21.516-30.231-29.685-35.905-41.703-56.647-92.235-60.664-147.788-.684-9.466-.3-32.969.684-41.753 5.325-47.575 21.784-89.727 49.233-126.087 7.416-9.824 12.914-16.055 23.557-26.699 10.656-10.655 16.907-16.17 26.7-23.552 37.13-27.995 80.835-44.746 128.55-49.27 9.88-.936 31.584-1.06 40.99-.235 32.29 2.836 61.821 10.874 89.849 24.457C11.262 8.87 55.554 55.143 78.7 109.412c10.363 24.294 16.49 47.815 19.492 74.811.982 8.83 1.373 32.291.694 41.72-2.892 40.22-15.02 79.123-35.108 112.606-11.034 18.394-23.893 34.569-40.245 50.625-13.06 12.824-23.474 21.302-37.838 30.803-35.606 23.551-77.366 37.557-121.137 40.628-5.888.413-24.34.509-30.463.158zm629.045-513.23c23.646-6.101 39.286-28.781 36.728-53.26-.657-6.29-1.832-10.59-4.343-15.903-5.232-11.07-12.367-18.33-23.236-23.644-5.733-2.804-10.042-4.02-16.618-4.688-19.987-2.033-39.265 8.395-48.47 26.22-4.274 8.275-5.792 15.444-5.393 25.468.324 8.149 1.368 12.386 4.76 19.324 5.221 10.677 12.125 17.662 22.71 22.976 7.582 3.807 13.724 5.077 23.242 4.808 4.798-.136 7.294-.442 10.62-1.3z" transform="matrix(.1 0 0 .1 89.766 42.548)"/><path style="fill:#a9a9a9;stroke-width:.44797176" d="M-787.613 722.191c-36.117-3.22-69.313-23.226-89.89-54.173-3.883-5.839-10.156-18.225-12.553-24.785-3.63-9.935-5.988-20.783-6.935-31.893-.356-4.187-.45-108.773-.338-376.048l.155-370.249.957-5.6c3.783-22.13 11.837-40.45 25.01-56.892 9.845-12.285 24.15-23.878 38.498-31.2 12.993-6.63 24.56-10.254 40.765-12.772 2.779-.432 32.685-.589 133.11-.7l129.528-.141 67.132-68.017c36.923-37.41 70.862-71.851 75.42-76.537 12.345-12.692 20.96-19.114 34.718-25.88 13.806-6.79 26.77-10.444 42.11-11.865 6.999-.649 314.86-.627 321.867.023 22.385 2.076 42.869 10.222 61.372 24.408 7.495 5.746 17.494 15.62 88.53 87.432l69.676 70.435 126.852.13 126.852.13 6.048.995c6.97 1.145 15.234 3.098 20.388 4.819 20.221 6.75 38.387 18.59 52.134 33.98 17.638 19.748 27.828 43.314 30.061 69.52.357 4.183.45 108.827.338 376.044l-.156 370.249-.956 5.6c-4.642 27.163-15.761 49.103-34.335 67.746-18.716 18.786-40.77 29.906-68.818 34.699-4.508.77-31.123.806-639.032.86-348.88.03-636.209-.113-638.508-.318zm662.826-182.487c78.625-6.68 150.851-40.044 206.156-95.227 38.954-38.87 67.728-86.736 83.501-138.909 21.49-71.083 18.485-148.149-8.461-216.987C112.764-22.918 12.482-102.268-105.651-118.78c-34.605-4.837-71.657-4.06-105.498 2.213-33.715 6.249-65.755 17.433-95.53 33.346-83.296 44.517-143.47 121.93-166.058 213.628-11.426 46.386-12.523 96.638-3.137 143.634 21.05 105.4 91.397 193.592 189.914 238.093 34.004 15.36 74.574 25.497 111.097 27.76 4.312.267 8.747.561 9.855.654 4.624.388 32.645-.2 40.22-.844zm-41.565-79.39c-3.832-.219-16.498-1.481-19.486-1.941-12.75-1.964-19.444-3.295-28.431-5.655-36.067-9.47-67.266-25.485-96.854-49.717-7.76-6.355-22.774-21.093-29.785-29.237-35.988-41.803-56.663-92.015-60.662-147.328-.69-9.553-.302-33.898.681-42.66 5.848-52.125 24.657-96.54 57.76-136.391 5.771-6.949 23.583-24.76 30.532-30.533 40.5-33.64 86.596-52.826 138.854-57.79 9.91-.942 32.428-1.068 41.885-.235 15.106 1.33 27.937 3.479 41.886 7.015C-47.668-23.432-9.26-1.914 22.428 28.814c20.34 19.723 36.622 41.763 49.284 66.71 14.279 28.132 22.404 55.674 26.035 88.251.982 8.807 1.376 33.109.692 42.628C94.465 281.665 73.74 332 37.768 373.764c-6.59 7.65-21.285 22.193-28.515 28.218-27.2 22.668-57.116 38.756-90.266 48.544-17.271 5.1-35.342 8.323-53.98 9.631-5.88.413-25.216.51-31.359.158zm623.83-511.309c33.385-4.643 52.644-40.41 38.273-71.077-6.809-14.531-19.88-24.678-35.698-27.714-5.145-.988-13.921-.967-18.73.045-17.677 3.718-31.467 15.728-37.327 32.51-3.127 8.953-3.6 20.18-1.236 29.311 1.537 5.934 5.208 13.338 9.134 18.417 10.532 13.628 28.43 20.894 45.585 18.508z" transform="matrix(.1 0 0 .1 89.766 42.548)"/><path style="fill:#949494;stroke-width:.44797176" d="M-788.509 721.743c-15.864-1.415-33.319-6.9-46.967-14.761-22.192-12.78-38.022-29.495-48.981-51.717-7.177-14.552-10.74-27.24-12.086-43.03-.356-4.183-.454-109.108-.35-376.72.135-347.477.192-371.217.91-375.623 3.027-18.579 8.801-33.652 18.449-48.157 7.435-11.18 14.717-19.28 23.86-26.54 19.378-15.384 41.654-24.402 65.538-26.53 3.369-.301 48.818-.46 131.33-.462 69.4 0 126.495-.122 126.88-.27.619-.237 124.286-125.255 143.172-144.736 11.321-11.678 21.392-19.125 34.718-25.673 13.447-6.608 26.396-10.264 41.214-11.637 7-.649 316.651-.627 323.66.023 11.44 1.06 23.168 4.042 34.374 8.737 13.15 5.509 25.325 13.8 38.546 26.249 4.011 3.777 38.453 38.366 76.537 76.865 38.083 38.5 69.676 70.1 70.206 70.225.529.125 57.81.28 127.29.343l126.328.117 6.213 1.016c24.2 3.96 45.619 14.248 63.854 30.672 13.06 11.762 25.285 30.511 31.17 47.802 3.546 10.418 5.158 18.007 6.05 28.479.357 4.183.45 109.072.338 376.94l-.156 371.145-.956 5.6c-3.124 18.28-8.946 33.19-18.748 48.016-9.598 14.516-19.819 24.696-34.68 34.545-14.059 9.317-29.341 15.178-48.381 18.553-3.975.705-46.127.76-639.704.828-349.496.04-637.329-.094-639.628-.299zm662.826-181.595c62.794-5.305 121.052-27.188 170.55-64.061 30.445-22.68 58.042-51.68 79.03-83.046 17.15-25.631 32.115-57.272 41.018-86.725 17.817-58.942 19.293-118.433 4.415-177.846-8.242-32.913-20.96-63.22-38.825-92.526C109.44 1.392 79.36-31.518 45.8-56.729c-26.873-20.188-59.125-37.19-91.057-48.005-53.383-18.078-110.705-22.344-164.997-12.281-32.533 6.03-60.428 15.417-89.818 30.225-26.66 13.433-48.672 28.418-71.004 48.338-10.462 9.333-25.701 25.065-34.747 35.87-56.53 67.533-83.913 156.433-75.203 244.145 3.273 32.963 11.796 66.455 24.609 96.7 16.35 38.594 38.897 72.39 68.3 102.372 30.972 31.582 63.272 54.233 102.604 71.953 33.899 15.272 74.954 25.487 111.545 27.753 4.312.268 8.747.562 9.855.656 4.495.378 30.775-.202 38.429-.849zm-41.564-80.281c-3.833-.22-16.499-1.482-19.487-1.942-16.844-2.594-25.128-4.456-39.198-8.81-48.814-15.103-93.344-46.282-124.27-87.012-30.191-39.763-47.33-85.12-50.918-134.748-.703-9.715-.307-35.755.676-44.476 2.188-19.415 5.39-34.556 10.852-51.293 10.226-31.34 25.508-58.8 47.36-85.097 5.767-6.94 21.798-22.972 28.739-28.74 36.82-30.594 77.806-49.308 123.399-56.34 13.112-2.022 22.263-2.67 38.078-2.691 23.485-.033 41.256 2.072 62.044 7.349C-53.266-24.615-18.65-6.67 10.82 18.319c38.2 32.391 66.796 77.968 79.702 127.033 5.192 19.74 7.39 36.568 7.749 59.346.256 16.234-.121 24.464-1.696 36.983-7.004 55.686-32.927 107.792-73.264 147.265-26.277 25.715-57.569 45.216-92.06 57.371-20.977 7.393-42.39 11.781-65.35 13.392-5.861.411-26.972.512-33.15.158zm630.04-511.695c6.217-1.574 12.927-4.676 17.279-7.987 4.48-3.41 11.14-10.654 13.506-14.694 5.552-9.479 8.242-21.03 7.127-30.613-2.101-18.077-12.35-32.955-27.968-40.605-15.43-7.558-32.528-6.858-47.489 1.944-3.914 2.303-11.213 8.957-14.476 13.197-4.517 5.869-7.947 14.2-9.347 22.699-1.556 9.445.029 19.744 4.452 28.927 5.174 10.741 13.122 18.674 23.798 23.753 11.098 5.279 21.443 6.334 33.118 3.379z" transform="matrix(.1 0 0 .1 89.766 42.548)"/><path style="fill:#6a6a6a;stroke-width:.44797176" d="M-787.613 721.743c-15.87-1.415-33.366-6.797-47.036-14.47-21.955-12.322-38.614-29.796-49.584-52.008-7.32-14.82-10.927-27.693-12.31-43.925-.356-4.187-.45-108.773-.338-376.048l.155-370.249.957-5.6c3.783-22.13 11.84-40.46 25.012-56.892 9.7-12.1 23.746-23.454 38.047-30.752 12.994-6.63 24.562-10.254 40.766-12.772 2.779-.432 32.685-.589 133.113-.7l129.529-.141 67.354-68.24c37.045-37.531 70.983-71.972 75.418-76.535 12.108-12.458 20.804-18.926 34.494-25.66 13.806-6.79 26.77-10.443 42.11-11.864 6.999-.649 314.86-.627 321.867.023 22.385 2.076 42.869 10.222 61.372 24.408 7.485 5.738 17.458 15.588 88.528 87.432l69.675 70.435 126.854.13 126.853.13 6.048.994c6.97 1.146 15.234 3.1 20.388 4.82 46.082 15.382 77.67 55.202 81.747 103.052.357 4.183.45 108.827.338 376.044l-.156 370.249-.956 5.6c-4.623 27.054-15.758 49.088-34.12 67.519-18.503 18.57-40.65 29.704-68.585 34.478-4.508.77-31.123.806-639.032.86-348.88.03-636.209-.113-638.508-.318zm662.826-181.59c78.747-6.692 150.856-40.04 206.38-95.446 39.18-39.096 67.919-86.855 83.725-139.139 22.915-75.797 17.902-158.423-13.97-230.247-46.669-105.169-143.847-178.733-257-194.55-11.79-1.647-21.49-2.474-34.435-2.933-69.456-2.464-136.895 16.752-195.009 55.565C-382.54-34.91-421.89 9.569-447.25 60.176c-23.547 46.987-35.36 96.808-35.36 149.133 0 86.926 33.657 169.593 94.275 231.552 30.02 30.686 63.58 54.197 102.376 71.72 34.004 15.36 74.574 25.498 111.097 27.76 4.312.268 8.747.562 9.855.655 4.624.388 32.645-.2 40.22-.844zm-41.565-80.286c-3.832-.22-16.498-1.482-19.486-1.942-12.75-1.964-19.444-3.295-28.431-5.655-36.067-9.47-67.28-25.492-96.853-49.717-7.777-6.37-22.35-20.67-29.338-28.789-35.99-41.809-56.663-92.015-60.662-147.328-.69-9.553-.302-33.898.681-42.66 5.848-52.125 24.66-96.546 57.76-136.391 5.77-6.947 23.137-24.314 30.084-30.084 40.494-33.639 86.596-52.827 138.854-57.791 9.91-.942 32.428-1.068 41.885-.235 32.433 2.855 60.906 10.613 88.96 24.236C6.643 7.568 46.442 46.618 71.265 95.525c14.279 28.13 22.404 55.673 26.035 88.25.982 8.807 1.376 33.109.692 42.628-3.974 55.262-24.696 105.592-60.67 147.361-6.575 7.634-20.824 21.731-28.068 27.769-27.188 22.662-57.115 38.757-90.266 48.545-17.271 5.1-35.342 8.323-53.98 9.631-5.88.413-25.216.51-31.359.158zm623.83-510.414c18.772-2.61 34.683-15.767 40.793-33.731 7.006-20.597-.009-43.647-17.228-56.614-9.027-6.797-19.114-10.126-30.537-10.075-13.069.057-24.483 4.673-33.946 13.728-10.996 10.523-16.296 23.716-15.654 38.972.317 7.538 1.72 13.098 4.987 19.77 9.603 19.604 30.435 30.892 51.586 27.95z" transform="matrix(.1 0 0 .1 89.766 42.548)"/><path style="fill:#545454;stroke-width:.44797176" d="M-787.165 721.743c-19.094-1.702-38.81-8.612-55.4-19.416-12.272-7.992-25.901-21.638-34.474-34.518-3.51-5.274-10.277-18.747-12.57-25.024-3.63-9.942-5.987-20.785-6.934-31.893-.356-4.187-.45-108.65-.338-375.6l.155-369.8.957-5.6c2.417-14.142 5.945-24.998 12.01-36.958 5.555-10.953 11.952-19.645 21.454-29.15 9.81-9.815 18.09-15.9 29.617-21.772 13.477-6.865 24.824-10.442 41.192-12.985 2.78-.432 32.486-.586 132.376-.685 70.835-.07 129.214-.228 129.73-.35.76-.178 109.56-109.953 143.084-144.366 12.883-13.225 20.268-18.752 34.27-25.65 14.243-7.016 27.037-10.65 42.558-12.09 6.999-.648 313.964-.626 320.971.024 22.05 2.045 43.542 10.505 61.052 24.035 7.072 5.464 15.115 13.405 82.253 81.206 37.694 38.066 70.264 70.925 72.378 73.02l3.843 3.807 126.878.235 126.878.234 6.048.993c33.34 5.475 60.283 21.556 79.97 47.73 12.541 16.672 20.744 38.65 22.613 60.589.357 4.183.45 108.704.338 375.596l-.156 369.801-.956 5.6c-2.508 14.673-6.271 26.106-12.624 38.348-5.785 11.147-12.294 19.97-21.696 29.406-10.285 10.322-19.402 16.872-32.323 23.22-11.678 5.738-22.075 9.004-36.51 11.47-4.508.771-31.107.807-638.584.86-348.634.03-635.76-.112-638.06-.317zm662.826-181.589c72.69-6.193 137.579-34.085 193.287-83.081 36.76-32.331 69.338-79.092 87.567-125.691 19.379-49.54 26.6-102.053 21.374-155.446A328.804 328.804 0 0 0 131.4 36.84C105.996-4.82 73.165-39.183 32.924-66.228-13.533-97.451-65-115.82-120.696-121.053c-18.003-1.692-43.475-1.694-61.634-.004-64.075 5.96-123.878 30.035-175.081 70.484-53.607 42.347-93.703 102.335-112.623 168.497-14.294 49.987-16.518 105.346-6.288 156.566 10.457 52.358 32.758 100.12 66.564 142.562 41.945 52.66 101.34 92.473 165.669 111.05 22.787 6.58 47.01 10.892 68.778 12.24 4.312.267 8.747.562 9.855.654 4.712.395 33.536-.195 41.117-.841zm-41.565-80.287c-3.832-.22-16.498-1.482-19.486-1.942-8.035-1.238-12.177-1.958-17.023-2.96-34.208-7.072-69.508-22.893-97.074-43.509-13.68-10.23-30.48-25.882-41.42-38.588-35.538-41.274-56.229-91.748-60.216-146.892-.684-9.466-.3-32.969.684-41.753 5.798-51.804 24.733-96.718 57.308-135.942 5.775-6.953 24.477-25.655 31.43-31.43 39.947-33.176 86.364-52.406 138.406-57.34 9.88-.936 31.584-1.06 40.99-.235 25.959 2.28 50.384 7.997 73.095 17.108 8.672 3.48 23.834 10.842 32.223 15.648C1.627 8.424 28.849 32.785 49.437 60.422 57.69 71.5 64.33 82.287 71.04 95.524c14.536 28.667 22.617 55.969 26.257 88.698.981 8.83 1.372 32.291.694 41.72-3.962 55.093-24.702 105.69-60.225 146.925-6.617 7.682-22.206 23.114-29.41 29.115-16.528 13.77-30.767 22.993-50.396 32.644-29.756 14.63-59.93 22.734-93.402 25.082-5.888.413-24.34.509-30.463.158zM463.082-51.555c5.96-1.539 10.383-3.475 15.221-6.662 13.507-8.898 21.475-22.75 22.643-39.362 1.096-15.59-6.023-32.423-17.703-41.86-6.698-5.411-14.01-8.95-21.986-10.637-5.523-1.17-15.178-1.226-20.383-.12-20.67 4.39-35.98 20.288-39.435 40.948-1.096 6.554-.735 15.602.867 21.766 4.9 18.846 20.982 33.574 40.136 36.758 5.74.954 15.197.573 20.64-.831z" transform="matrix(.1 0 0 .1 89.766 42.548)"/><path style="fill:#2c2c2c;stroke-width:.44797176" d="M-787.613 721.295c-15.87-1.415-33.366-6.797-47.036-14.47-21.872-12.276-38.192-29.4-49.136-51.56-7.32-14.82-10.927-27.693-12.31-43.925-.356-4.187-.45-108.773-.338-376.048l.155-370.249.957-5.6c3.783-22.132 11.845-40.47 25.013-56.892 9.553-11.914 23.347-23.032 37.599-30.304 12.993-6.63 24.562-10.255 40.765-12.772 2.78-.432 32.486-.586 132.376-.685 70.835-.07 129.217-.229 129.738-.351.77-.182 114.911-115.337 143.524-144.801 11.87-12.224 20.648-18.74 34.27-25.439 13.806-6.79 26.77-10.443 42.11-11.864 6.999-.649 314.86-.627 321.867.023 22.385 2.076 42.869 10.222 61.372 24.408 7.475 5.73 17.426 15.558 88.314 87.22 38.205 38.622 69.897 70.324 70.426 70.449.53.124 57.71.278 127.066.342l126.104.117 6.048.993c14.672 2.412 26.925 6.41 38.97 12.717 32.54 17.039 55.03 47.875 61.394 84.18.456 2.602 1.052 7.339 1.323 10.527.357 4.183.45 108.827.338 376.044l-.155 370.249-.957 5.6C586.11 654.745 568 683 538.98 702.212c-13.807 9.14-30.115 15.305-49.053 18.54-4.508.771-31.123.807-639.032.86-348.88.03-636.209-.112-638.508-.317zM-124.787 540.6c78.87-6.702 150.848-40.029 206.597-95.657 39.418-39.334 68.113-86.97 83.956-139.375 21.49-71.083 18.485-148.149-8.461-216.987C121.732-2.295 46.955-73.98-44.835-105.202c-31.29-10.643-61.812-16.221-95.252-17.408-69.456-2.464-136.895 16.752-195.009 55.565-40.355 26.952-75.752 64.025-100.472 105.23-31.367 52.283-47.49 110.382-47.49 171.124 0 87.057 33.68 169.664 94.497 231.775 30.357 31.003 63.464 54.214 102.636 71.955 33.786 15.303 74.553 25.489 111.062 27.75 4.312.267 8.747.562 9.855.655 4.624.388 32.645-.2 40.22-.844zm-41.565-81.181c-3.832-.22-16.498-1.482-19.486-1.942-12.75-1.964-19.444-3.295-28.431-5.655-36.067-9.47-67.293-25.5-96.851-49.717-7.797-6.387-21.927-20.248-28.893-28.34-35.992-41.816-56.662-92.016-60.661-147.33-.69-9.552-.302-33.897.681-42.66 5.848-52.124 24.663-96.551 57.76-136.39 5.77-6.945 22.691-23.866 29.636-29.636 40.487-33.636 86.596-52.827 138.854-57.791 9.91-.942 32.428-1.068 41.885-.235 32.433 2.855 60.906 10.613 88.96 24.236C6.578 7.985 46.032 46.693 70.817 95.525c14.279 28.13 22.404 55.673 26.035 88.25.982 8.807 1.376 33.109.692 42.628-3.974 55.262-24.693 105.587-60.67 147.361-6.56 7.617-20.361 21.269-27.62 27.32-27.174 22.655-57.114 38.758-90.266 48.546-17.271 5.1-35.342 8.323-53.98 9.631-5.88.413-25.216.51-31.359.158zM457.478-50.1c10.874-1.512 20.065-6.071 28.35-14.063 15.758-15.202 20.055-38.2 10.82-57.91-6.944-14.816-20.68-25.555-36.595-28.61-5.145-.988-13.921-.967-18.73.045-9.905 2.083-17.741 6.216-24.993 13.18-11.25 10.804-16.519 23.815-15.872 39.19.317 7.538 1.72 13.098 4.987 19.77 9.72 19.843 30.805 31.351 52.034 28.398z" transform="matrix(.1 0 0 .1 89.766 42.548)"/><path style="fill:#000;stroke-width:.44797176" d="M-788.06 720.847c-27.08-2.414-53.772-14.978-73.11-34.41-9.347-9.395-16.167-19.024-22.392-31.62-6.2-12.544-9.127-21.68-11.287-35.23l-1.025-6.431v-373.16c0-306.281.106-373.964.591-377.64 2.47-18.742 8.6-35.286 18.68-50.42 5.124-7.691 8.602-11.981 14.533-17.923 8.572-8.587 18.353-15.69 29.41-21.357 13.318-6.826 24.01-10.207 40.268-12.733 2.78-.432 32.53-.586 132.6-.685 70.958-.071 129.442-.23 129.964-.352.774-.182 116.221-116.653 143.746-145.02 11.503-11.855 20.282-18.387 33.822-25.162 13.74-6.876 26.435-10.49 41.886-11.921 7-.649 315.756-.627 322.763.023 22.17 2.056 42.276 10.11 60.924 24.408 7.464 5.722 17.39 15.525 88.31 87.219 38.207 38.622 69.9 70.324 70.43 70.449.529.124 57.91.287 127.514.361l126.552.135 5.715.958c24.77 4.155 45.113 13.826 63.273 30.082 15.688 14.042 28.781 35.802 34.1 56.671 1.61 6.318 2.726 12.996 3.303 19.774.357 4.183.45 108.95.338 376.492l-.155 370.697-.957 5.6c-2.48 14.512-5.909 24.928-12.194 37.039-12.14 23.395-30.043 40.83-54.001 52.59-11.472 5.632-20.889 8.584-35.166 11.024-4.508.77-31.14.806-639.48.86-349.126.03-636.657-.113-638.956-.318zm662.825-179.8c79.268-6.718 151.327-40.05 207.272-95.88 43.793-43.703 73.774-96.476 88.494-155.767 16.783-67.605 12.137-136.96-13.574-202.61-24.05-61.41-68.36-116.3-124.236-153.9-51.208-34.46-110.903-53.756-173.048-55.94-77.947-2.738-154.608 22.486-216.189 71.132-16.966 13.403-35.78 31.718-49.758 48.44-32.666 39.079-55.473 84.261-67.807 134.333-20.773 84.331-6.98 174.582 38.174 249.804 23.788 39.626 58.732 76.65 97.534 103.341 37.217 25.6 79.225 43.19 124.312 52.051 12.731 2.503 27.491 4.433 39.646 5.186 4.312.267 8.747.562 9.855.655 4.56.384 31.711-.2 39.325-.846zM-166.8 458.97c-3.833-.22-16.499-1.482-19.487-1.942-14.401-2.218-23.195-4.092-34.776-7.41-50.328-14.424-96.734-46.303-128.678-88.398-29.822-39.297-46.9-84.737-50.485-134.326-.697-9.635-.305-34.826.678-43.568 5.833-51.868 24.593-96.02 57.762-135.942 5.769-6.943 22.245-23.42 29.188-29.188 40.578-33.714 86.384-52.84 138.406-57.793 9.948-.947 33.265-1.075 42.781-.235 25.82 2.28 49.764 7.842 71.799 16.682C-45.244-17.385-27.336-8.025-14.683.335 22.056 24.61 50.694 56.728 70.593 95.973c14.398 28.396 22.16 54.663 25.812 87.354.982 8.785 1.38 33.93.689 43.535-4.454 61.947-30.462 118.33-74.454 161.413-12.22 11.968-22.948 20.652-36.944 29.91-36.144 23.907-76.56 37.564-120.242 40.628-5.87.412-26.093.51-32.253.158zM457.03-49.651c4.135-.575 9.936-2.209 14.317-4.033 9.559-3.98 20.048-14.035 25-23.968 1.759-3.526 3.981-10.22 4.822-14.524.82-4.2.699-13.802-.229-18.068-4.013-18.464-16.398-32.791-33.513-38.77-14.98-5.232-31.237-3.166-44.656 5.675-3.33 2.194-10.306 8.959-13.007 12.614-7.903 10.694-11.439 25.463-9.187 38.377 2.432 13.945 10.06 25.864 21.852 34.144 9.798 6.88 23.06 10.158 34.6 8.553z" transform="matrix(.1 0 0 .1 89.766 42.548)"/>
  </symbol>
  	
	<symbol id="folder" viewBox="0 0 1559.508 1084.004">
		<path class="back" style="fill:#deb887;stroke:#d2691e;stroke-width:12" d="M7.95 1077.856V74.288C7.95 43.524 29.187 7.09 66.902 6.2h351.23c75.299-3.788 92.973 45.346 139.778 68.087l680.184-.382c26.26-.023 41.263 19.67 41.263 34.715v882.771c.891 40.218-44.508 89.555-78.828 86.464z"/>
		<path class="front" style="fill:#deb887;stroke:#d2691e;stroke-width:12" d="M1536.22 220.368c23.463 27.605 17.07 55.304 15.252 64.145l-288.498 758.013c-11.788 14.035-24.551 24.475-41.495 33.943l-1213.53.982 225.824-792.815c11.439-49.537 25.113-78.79 62.077-78.79h1194.465c17.222 0 27.354-.075 45.904 14.522z"/>
	</symbol>
	
	<symbol id="padlock" viewBox="0 0 103.607 134.169">
		<path style="fill:#000;stroke-width:1.06907809" d="m48.271 220.275-2.523-2.043-.308-35.179c-.302-34.412-.26-35.242 1.948-38.048 1.502-1.91 3.272-2.868 5.294-2.868h3.038l.36-12.028c.31-10.379.753-12.87 3.24-18.177 3.777-8.058 12.132-16.77 19.82-20.662 5.7-2.887 7.049-3.122 17.923-3.122 10.764 0 12.286.258 17.962 3.045 8.175 4.015 15.803 11.807 20.1 20.533 3.127 6.354 3.529 8.254 3.96 18.74.467 11.324.554 11.67 2.94 11.67 1.381 0 3.427 1.234 4.673 2.817 2.137 2.718 2.216 4.034 2.216 37.274s-.079 34.556-2.216 37.274l-2.216 2.816H97.64c-44.52 0-46.969-.1-49.368-2.042zm56.2-16.743c1.285-1.286 1.68-4.007 1.68-11.595 0-7.015.508-10.936 1.738-13.401 1.448-2.903 1.53-4.182.49-7.652-1.568-5.234-5.49-7.9-11.624-7.9-3.933 0-5.175.556-7.797 3.491-3.532 3.953-4.232 10.131-1.55 13.677 1.123 1.485 1.579 5.025 1.603 12.445.029 8.726.33 10.544 1.905 11.46 2.968 1.73 11.637 1.393 13.555-.525zm17.716-68.685c0-14.3-4.543-23.013-14.39-27.598-9.584-4.463-19.728-2.89-27.13 4.206-5.677 5.44-7.599 10.276-8.363 21.037l-.684 9.645h50.567z" transform="translate(-45.306 -88.148)"/>
	</symbol>
	
	<symbol id="fullScreen" viewBox="0 0 259.34 259.324">
		<path style="stroke-width:.26458332" d="M-312.331 253.887c-10.104-1.698-18.363-8.733-21.487-18.302-1.537-4.708-1.445 2.346-1.445-110.942 0-113.28-.091-106.235 1.444-110.94 3.17-9.712 11.378-16.643 21.69-18.315 3.611-.586 209.4-.587 213.01-.002C-87.138-2.67-78.305 6.16-76.363 18.134c.587 3.617.587 209.4 0 213.018-1.665 10.268-8.644 18.533-18.313 21.69-4.711 1.538 2.395 1.447-111.207 1.418-84.911-.02-104.764-.09-106.447-.373zm212.678-18.993c2.06-.962 3.35-2.244 4.278-4.252l.749-1.621V20.265l-.742-1.588c-.962-2.06-2.244-3.35-4.252-4.278l-1.62-.749h-208.757l-1.621.75c-2.008.927-3.29 2.216-4.252 4.277l-.742 1.588V229.02l.742 1.587c.408.874 1.123 1.986 1.588 2.472.952.994 2.864 2.05 4.35 2.401.571.136 45.108.218 104.842.195l103.85-.04zm-189.133-27.084c-.097-.097-.177-14.563-.177-32.147 0-25.46.068-31.97.332-31.97.183 0 5.39 5.06 11.574 11.244l11.241 11.244 16.015-16.007c8.808-8.803 16.188-16.006 16.401-16.006.507 0 18.256 17.75 18.256 18.255 0 .213-7.203 7.594-16.006 16.402l-16.007 16.015 11.244 11.241c6.184 6.183 11.244 11.391 11.244 11.574 0 .264-6.51.332-31.97.332-17.584 0-32.05-.08-32.147-.177zm101.752-101.756c-4.983-4.984-9.06-9.18-9.06-9.321 0-.142 7.23-7.492 16.068-16.334l16.069-16.077-11.306-11.317c-6.218-6.224-11.306-11.405-11.306-11.513 0-.107 14.436-.165 32.08-.128l32.081.067.068 32.081c.037 17.645-.021 32.08-.129 32.08-.107 0-5.288-5.087-11.512-11.305l-11.317-11.306-16.077 16.069c-8.842 8.837-16.194 16.068-16.337 16.068-.144 0-4.338-4.079-9.322-9.064z" transform="translate(335.264 5.052)"/>
	</symbol>
	
		
	<symbol id="imgFile" viewBox="0 0 57.68 77.808">
		<path class="pageOutline" style="fill:#8e8f90;fill:black;stroke-width:1" d="M-238.371 143.824v77.789h57.68v-60.887l-7.992-8.597-8.629-8.305zm.266.266h40.777l8.434 8.202 7.937 8.45v60.606h-57.148z" transform="translate(238.371 -143.805)"/>
		<path class="foldShadow1" style="fill:none;stroke:#000;stroke-width:.26458332px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1" d="m-197.312 143.824-.039 16.935" transform="translate(238.371 -143.805)"/>
		<path class="foldShadow2" style="fill:#8e8f90;fill-opacity:1;stroke:#000;stroke-width:.26458332px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1" d="m-180.691 160.726-16.66.033" transform="translate(238.371 -143.805)"/>
		<path class="foldedBorder" style="fill:#8e8f90;fill-opacity:1;stroke:#8e8f90;stroke-width:.1383381;stroke-opacity:1" d="M-745.853 607.796c0-.174-.087-.3-.207-.3-.184 0-.208-3.009-.208-26.25 0-14.437.042-28.817.092-31.956l.091-5.706h.802l-.099 31.783-.1 31.783 31.218-.042 31.217-.043v.846h-12.912c-7.101 0-21.233.042-31.403.092l-18.49.092z" transform="matrix(.26458 0 0 .26458 238.371 -143.805)"/>
		<path class="mountainsAndBorder" style="fill:#0077d6;stroke:#0077d6;stroke-width:.26458338;stroke-opacity:1" d="M-228.978 163.272h39.158v39.158h-39.158zm29.264 27.32c-2.262-4.21-4.186-7.848-4.277-8.085-.108-.28.273-.99 1.089-2.028.69-.878 1.369-1.671 1.432-1.597l8.936 10.539c.254.329-.211-2.275-.204-11.53l.204-11.75h-33.536v20.289l10.87-13.356 19.484 25.286c.062-.062-1.737-3.558-3.999-7.768z" transform="translate(238.371 -143.805)"/>
		<path class="paperBack" style="fill:#e5e5e5;fill-opacity:1;stroke:none;stroke-width:.08649372;stroke-opacity:1" d="M155.64 37.047c.036-14.545.09-28.426.119-30.847l.053-4.402 15.473 15.04 15.474 15.04 14.789 15.743 14.789 15.742-9.288.052c-5.108.029-18.78.058-30.38.065l-21.093.012z" transform="scale(.26458)"/>
		<path class="sky" style="fill:#f7f8f8;fill-opacity:1;stroke:none;stroke-width:.12626907;stroke-opacity:1" d="m87.618 110.592-41.126 50.51.103-76.522 126.646-.004v87.831l-33.8-39.844-9.565 13.187 31.091 59.992z" transform="scale(.26458)"/>
		<path class="paperFront" style="fill:#f7f8f8;fill-opacity:1;stroke:#f7f8f8;stroke-width:.35355338;stroke-opacity:1" d="M1.17 147.048V1.207h153.54l-.262 31.279c-.244 29.094-.22 31.32.334 31.875.556.555 2.755.58 31.326.345l30.729-.252v228.435H1.169zm182.433.53V73.332H35.463v148.492h148.14z" transform="scale(.26458)"/>
	</symbol>
</svg>




	<script type="module" src="/<?php echo $$nameSpaceID['templateVar']['appInfo']['val']['appRoot'];?>/javascripts/govuk-frontend-5.13.0.min.js"></script>
    <script type="module">
      import { initAll } from '/<?php echo $$nameSpaceID['templateVar']['appInfo']['val']['appRoot'];?>/javascripts/govuk-frontend-5.13.0.min.js';
      initAll();
    </script>

</body>


</html>

<?php } //end of if this is top level dom document ?> 

<?php
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	



if(isset($prevNameSpaceID) === true && $prevNameSpaceID != ""){
	$nameSpaceID = $prevNameSpaceID;
}
	
?>