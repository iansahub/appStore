<?php
/************************************************************ 
PROJECT NAME:  phpSample
FILE NAME   :  process_sampleform.php
PHP VERSION :  5.3.3
template ver:  1

FILE DESCRIPTION:
can be called directly via a normal http request/get/post or alternatively as a php include/require or via ajax


// is the filename recognised against the relevant regex 
// is it of the right mime type?
// does the file have a dpia
// does the file have a MISR
// has the virus scan been passed on the file?
// if so move it into jobs/n/ and notify people using phpmail
//else reject by notifying sender and stakeholders  

so phppReceptionist db needs to contain a recrod per expected data item
regex, mime type, dpia, misr (for now just in db), server and job to move it to, stakeholders to inform.

//jobs when it arrives in jobs/n/ it should do whatever needs to be done to start ETL


OUTPUT:
NONE or STREAM or JSON or TEXT depending on ${$nameSpaceID."_var"}['outputMode']['val']

VER:   DATE:     INITIALS:  DESCRIPTION OF CHANGE:
1.0    04/02/22  AB         Initial Version


**************************************************************/

//in stream mode this will require the timeout cancellers and 
//something to kill it getmypid(): int|false into a db :

//TEMPLATE	-ENSURE GLOBAL VARIABLES _SERVER and _REQUEST, WHICH THIS TEMPLATE DEPENDS ON, ARE AVAILABLE. $_SERVER for example is normally only available when the script is run
//from a web server not from the CLI but this block of code will create those globals if they are found not to exist and to not be populated with the basic values needed for the
//script to succeed
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

			function does_ssl_exist($url){
				//a test to see if asking for https in a given domain url is successful
				$orignal_parse = parse_url($url, PHP_URL_HOST);
				$get = stream_context_create(array("ssl" => array("capture_peer_cert" => TRUE)));
				$read = stream_socket_client("ssl://" . $orignal_parse . ":443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $get);
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

			if (@does_ssl_exist($domain)){
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

	if(!isset($_REQUEST)){$_REQUEST = array();}

//END OF TEMPLATE	-ENSURE AVAILABILITY OF GLOBAL VARIABLES WHICH THIS TEMPLATE DEPENDS ON.
/*CODER ACTION
if this script is ever run in CLI mode (i.e. from the command line / command prompt instead of from a web browser)
it is running from outside of the web server.  this means that variables which are set by the web server are not 
available to this script.  This can cause code to act unexpectedly - most commonly, where a developer has referred to $_SERVER['DOCUMENT_ROOT']
in the path to an include/request or URI/URL to a resource.  This template includes a work-around to that issue. At the bottom
of the template code is a function called get_web_page.  it asks the localhost web server for a web page, returning the content as a 
PHP variable.  in the template section above, that function is called to ask the local web server to run a script called getVar.php
that script takes the argument 'var' in its querystring to be a key in the $_SERVER array and returns the value of the relevant entry so that the
web server has provided the values this script needs when running outside of the web server. obviously this only works if the getVar.php 
script is present to reply.

to ensure this script runs in CLI, as well as from a browser, the coder should ensure that any other $_SERVER array values which their script uses
are checked to be present else are gathered in the same way. these checks would be best placed in the TEMPLATE section directly above this comment
*/


//TEMPLATE	-AUTOMATICALLY COMPUTE A UNIQUE ID TO USE AS A NAMESPACE FOR THIS SCRIPT. THE NAMESPACE IS BASED ON THIS FILES PATH AND NAME
	/* the purpose of a namespace is that it is a unique ID for each .php file. This allows the coder to apply a convention whereby any variables
	named within that .php file have their name prefixed with the id unique to that script. As a result, if script A.php is included in script B.php
	and both use a variable of the same name, then the prefix of the namespace on each, which will be different on each, keeps the two variables 
	from clashing.

	This convention obliges the coder to compromise a little of their own liberty to code however they like, for the payoff that their code becomes more 
	valuable if it can be re-used/included in scripts written elsewhere or even by themselves at another time, without the need for painful and laborious 
	reworking to assure there are no conflicted variables. The obligation on the coder to apply this convetion is very low so this is not much to ask, 
	but to learn the convention, there are a couple of PHP techniques which the coder will have to use. Because PHP is so flexible, it accommodates many
	different, and equally valid, ways for a coder to solve a problem so coders develop personal styles which might not have left them familiar with the 
	techniques used here in this convention.
	
	To ease adoption of the conventions used here....
	CURL
	IIFE(Immediately-invoked function expression)
	CLI
	Streaming
	JSON
	SPRINTF
	LOCALE
	
	
	*/

	//if there is already a namespace at this stage it indicates this script has been included/embedded in another
	//so set aside the parent's name for now. the last action of this script will be to hand back to the parent's name if there were one.
	if(isset($nameSpaceID) === true && $nameSpaceID != ""){$prevNameSpaceID = $nameSpaceID;}
	$nameSpaceID =  "NS_" . trim(preg_replace("/[^A-Za-z0-9_]/", '',preg_replace("#[/\\\\\.]+#", "_", substr(realpath(__FILE__),strlen($_SERVER["DOCUMENT_ROOT"])))),"\n\r\t\v\0_");


	


//END OF TEMPLATE	-AUTOMATICALLY COMPUTE A UNIQUE ID TO USE AS A NAMESPACE FOR THIS SCRIPT. 


if (php_sapi_name() == "cli"){	
	//MERGE ANY COMMAND LINE ARGUMENT NAME-VALUE PAIRS INTO THE REQUEST ARRAY SO THEY CAN BE HANDLED EFFICIENTLY TOGETHER
	//ARGC VALUES WILL OVERWRITE EXISTING VALUES IN REQUEST ARRAY IF NAMES CLASH. this should be done early, before the $_REQUEST array is 
	//accessed by this code to give scripts a likelihood of working as expected if run from the command line instead of from a browser session
	//maybe instead if i, i sould generate a random var name until i find one that doesnt exist
	if(isset($argv) === true && isset($argc) === true && $argc > 0){
		for($i = 0;$i < $argc; $i++){
			if(strpos($argv[$i],'=') === false){
				//skip. this is not a name-value pair 
			}else{
				$_REQUEST[explode("=", $argv[$i])[0]] = explode("=", $argv[$i])[1];
			}
		}
	}
} // end of if php_sapi_name = cli (ie script is currently running from CLI not browser)

		
//TEMPLATE	- DECLARATION OF TEMPLATE VARIABLES WHOSE PURPOSE IS TO FUNCTIONALLY OPERATE THIS NO-DOCUMENTATION CODING TEMPLATE. 
//			IN A LATER SECTION, CODER WILL ADD ANY OF THEIR OWN VARIABLES IN THE SAME FORMAT AS THE STANDARD DESCRIBED HERE 
	/* arguments (aka variables) might be posted to this script in the $_REQUEST array, or sent in the query string when this script is called
	or may be sent as arguments if this php script is called from a command line interface (CLI). they should sent and recieved as name-value pairs
	either they will be expected or unexpected by the script. for arguments in argc (from CLI), the use of variableName=value should be used. 
   
	by the end of this unit of code, any unexpected arguments will have been side-lined into an array of variables called ${$nameSpaceID."_var"}['PASSTHRU_DIRTY']['val'] which, by standard, is passed on in the querystring to the next URL to be called (${$nameSpaceID."_nextURL"}). This permits this code to 'behave nicely'  if ever it is involved in another process un-anticipated by the original authors.

	any variables to expected by this php script are established by adding them into this array:
	${$nameSpaceID."_var"} = array();
	
	each array record is named(keyed) with what would traditionally be the name of the variable and its value is its self a sub-array. 
	the sub-array contains two keyed values.
	
	val = actual value in the variable. can be set on declaration to give a default.  for an argType of 0 or 1 (both of which indicate that the value can be set by user-supplied arguments to the script), val should not hold the input until it has been verified/validated. prior to that, the input should be stored in 'DIRTY'
		
	DIRTY = dirty un-checked/un-validated user input. only relevant to and necessary in vars with an argType of 0 or 1 (both of which indicate that the value can be set by user-supplied arguments to the script.) treat with suspicion, assume it deliberately injects dangerous content.
	
	tmpVal = (added later sometimes), temporary storage of the value eg. a copy set aside so it can be mainpulated or processed to overwrite its self. 
	
	argType = adds additional information on how to process the variable. 
	   -1.  if 'argType' attribute has a value of -1, the variable is internal to this script neither delivered to it via argc or by the URI ($_REQUEST array).
		0. the variable is to be provided to the script from outside (eg. by argv or in $_REQUEST) but the script will not error if it is not provided. there are many reasons why this may be desirable - perhaps because the variables absence from the user-supplied arguments is informative to the script, or perhaps because the value doesnt NEED to come from the user as it can fall back on a default declared in this script. 
		1. the variable is to be provided to the script from outside (eg by argv or in $_REQUEST) and is mandatory.the script will error if it is not provided.
	
	mVal = multi-value. this is a true/false flag. if true then, "val", when set, will hold an array of values not just a single value. where an argument destined to be stored (if validated) in an mval variable is semi-colon;delimted, it will be exploded into an array to be stored.
	
	count = if val holds an array, not a single value this must be indicated by mval being set to true. "count" exists to hold a count of values in the array.
	
	mustPassOn = boolean. if true, the variable's name-value pair will always be included in this scripts output eg. within the JSON object if the script's output is JSON or as a querystring name-value pair if this script finishes by redirecting to another URL.
	
	varSpecificErrs = only relevant to and necessary in vars with an argType of 0 or 1 (both of which indicate that the value can be set by user-supplied arguments to the script. varSpecificErrs holds a list of error codes (if any) for errors found in the user-supplied value. the value of varSpecificErrs is set during data validation/verification 
	
	info = for developers, a description of what the variable is for/what it holds/how it is used
	
	in the next line is exampled the declaration of a variable called siteID stored in the namespace _var array with an initial value of 0, and an 'argument' status of 1. meaning it is both expecting and mandating (ie will thrown an error if not done) that a value of this name is provided to the script as an argument. It has a an mVal (multi-value) attribute set to false because it stores just one value not an array of values. its mustPassOn key has a value of
	false because the value is not output by this script, just consumed by it. it has a description of the purpose of the variable stored under "info"
	
	${$nameSpaceID."_var"}['siteID'] = array("val"=> "0", "argType"=> 1, "mVal"=> false, "count" => 1, "mustPassOn" => false, "info" => "a siteID expects an integer which uniquely identifies an individual geographical site to which a record is associated.  siteIDs are registered in the app database in the sites table");
	*/

	${$nameSpaceID."_var"} = array();
	


	${$nameSpaceID."_var"}['debugging'] = array("val"=> false, "argType" => -1, "mVal"=> false, "count" => 1, "mustPassOn" => false, "info" => "
	BELONGS TO THE TEMPLATE.  toggle debugging mode. debugging mode outputs 	debug info the developer things are useful for their self and future developers to help them to troubleshoot.  Developers should set this to true when troubleshooting. and, when deveoping,  should wrap any output intended for debugging developers in an 'if' which checks that this value is true. When in debug mode, any redirects should be wrapped in an 'if not debugging' statement to prevent the redirection, leaving debug info displayed");
	
	if(${$nameSpaceID."_var"}['debugging']['val'] === true){
			echo "<style>
			body{font-family: arial;}
			pre{ white-space: pre-wrap;white-space: -moz-pre-wrap;white-space: -pre-wrap;white-space: -o-pre-wrap;word-wrap: break-word;}
			.debugDiv{ margin:1em 1em;padding:1em;0.5em;border:1px solid gray;background-color:graysmoke}
			</style>
			<pre style='color:red'>";
				trigger_error("you are seeing this output because the variable '\${\$nameSpaceID_var}['debugging']['val']' in file '".__FILE__."' was manually set to 'true'" , E_USER_WARNING);
			echo "</pre>";
	}
	
	
	${$nameSpaceID."_var"}['outputMode']	= array("val"=> (function(){
		
		header("Cache-Control: no-cache");
		header('X-Content-Type-Options: nosniff');  // to avoid IE sniffing (penetration testing 18/12/13)
		header("Expires: -1");
		
		if(isset($_REQUEST)=== true && array_key_exists('mode',$_REQUEST) === true){
			
			if($_REQUEST['mode'] === 'JSON'){
				ob_start(); // from this point forward all 'screen' output is buffered and not released until ready to be returned  with JSON object 
				
				header("Content-Type: application/json", true);	
				return "JSON";
			}elseif($_REQUEST['mode'] === 'STREAM'){
				//not coded yet :(
				return "STREAM";
			}elseif($_REQUEST['mode'] === 'NONE'){
				return "NONE";	
			}else{
				return "TEXT";
			}
		}else{
			return "TEXT";
		}
	})(), //end of self-invoking function to set db_conn
	"DIRTY" => "", "argType" => -1, "mval"=> false, "count" => 0, "mustPassOn" => false, "info" => "
	BELONGS TO THE TEMPLATE.  standardized 'process' (server-side) scripts will output a STREAM, a JSON or TEXT (HTML is TEXT). it is assumed at the outset as default that this is TEXT but the arguments received by the script might change that if TEXT isnt the desired output
	");	
		
		
	${$nameSpaceID."_var"}['includeSecurity']  = array("val" => true, "DIRTY" => "", "argType" => -1, "mVal"=> false, "count" => 0, "varSpecificErrs" => array(), "mustPassOn" => false, "info" => 
	"BELONGS TO THE TEMPLATE.  switches on (val = true) and off (val = false) elements of the template which embed security controls and restraints offered by klogin app
	");	
	
	${$nameSpaceID."_var"}['supportedLocales']  = array("val"=> array("en-GB" => array("native" => "English (GB)","en" => "English (GB)"),"zh-Hans" => array("native" => "简体中文", "en" => "Chinese (Simplified)"),"en-Arab" => array("native" => "English (Arab)","en" => "English (Arab)")),"DIRTY" => "", "argType" => -1, "mVal"=> true, "count" => 3, "varSpecificErrs" => array(), "mustPassOn" => false, "info" => 
	"BELONGS TO THE TEMPLATE.  a list of locales with the associated language presented once its local langauge and again in english (for the developers convenience). This is used
	as a script-level validation of the input variable loc (ie. does the loc value given by the user correspond to a supported Locale listed in supportedLocales and it might also be referenced in the context of user interface scripts for presenting a list of all locales/languages a user might choose to view a page in
	");

	
	${$nameSpaceID."_var"}['STDIN'] 	= array("val"=>  (function () { if(php_sapi_name() == "cli"){stream_set_blocking(STDIN, 0); $fh = fopen('php://stdin', 'r'); $read  = array($fh);  $write = NULL; $except = NULL;if(stream_select( $read, $write,$except, 0 ) === 1 ){return $fh;}else{return false;}}else{return false;}})(),"DIRTY" => "","argType" => -1, "mval"=> true, "count" => 0, "mustPassOn" => false, "info" => "
	val is set by an self-invoking inline function which checks whether the script is running in CLI (in which STDIN stream can exist). if it is running in CLI, it checks for data in the STDIN stream. if there is any it sets val to the handle
    of the stream, else in other cirumstances where there is no data or no STDIN stream or script is not runing in CLI, it sets the val to false. The benefit of using an inline function in this manner is two-fold; firstly the general structure of this
    PHP script is kept uniform, so that inputs whether they be from STDIN, REQUEST or ARGV are all handled similarly, and secondly it means that the code to grab hold of any STDIN handle can make use of the liberty of 
    not worrying about name-spaced variable names, because their scope is limited to the inline function.  The check on STDIN used here takes place without progressing the stream pointer psat the start so that after
    the inline function sets 'val', we know whether STDIN should be processed without having interfered with the actual data stream. 
    ");
	
	${$nameSpaceID."_var"}['PASSTHRU_DIRTY'] 		= array("val"=> array(), "DIRTY" => "", "argType" => -1, "mval"=> true, "count" => 0, "mustPassOn" => true, "info" => "
	BELONGS TO THE TEMPLATE.  will safely contain any arguments received by this script which are not registered with an argType value of 0 or >0 which would indiciate that they are expected by this script the arguments contained will be passed back with the return from this script so as to facilitate any unanticipated future use of this script by future developers");
	
	${$nameSpaceID."_var"}['error'] 				= array("val"=> "", "DIRTY" => "", "argType" => -1, "mval"=> false, "count" => 0, "mustPassOn" => false, "info" => "
	BELONGS TO THE TEMPLATE.  used as a flag for the script to say that an error has/has not been encountered. in non-complex scripts it might hold the error code/ message with no value (a blank string) indicating that no error has been encountered. Generally don't expect to return the content of this to the user (unless user is a debugging developer)... Generally, instead, each anticipated user-supplied argument will store within its { nameSpaceID.'_var'}['xxxxxx']['varSpecificErrs'] array, a list of error codes intended to be returned to the user where the front-end code will de-code them into an error message in the appropriate human langauge.
	Where an internal code error e.g SQL error has been encountered, it will be written into this variable. 
	");
	
	${$nameSpaceID."_var"}['sr'] 				= array("val"=> "", "DIRTY" => "", "argType" => -1, "mval"=> false, "count" => 0, "mustPassOn" => false, "info" => "
	BELONGS TO THE TEMPLATE.  used as a non-error 'good' response code from the server like 'your input was successfully saved' 
	");
	
	${$nameSpaceID."_var"}['loc']  = array("val"=> "en-GB", "DIRTY" => "", "argType" => 0, "mVal"=> false, "count" => 0, "varSpecificErrs" => array(), "mustPassOn" => false, "info" => 
	"BELONGS TO THE TEMPLATE.  a list of locales with the associated language presented once its local langauge and again in english (for the developers convenience). This is used
	as a script-level validation of the input variable loc (ie. does the loc value given by the user correspond to a supportedLocale and it might also be referenced in the context of user interface scripts for presenting a list of all languages a user might choose to view a page in
	loc is expected to be a IETF BCP 47 standard compliant locale code like en-GB or zh-Hans
	");
	
	${$nameSpaceID."_var"}['piecesOfArgVNameValuePair']  = array("val"=> array(),"DIRTY" => "", "argType" => -1, "mVal"=> true, "count" => 0, "varSpecificErrs" => array(), "mustPassOn" => false, "info" => 
	"BELONGS TO THE TEMPLATE.  tranistory. each ArgV argument, if any, are passed to php from the command line. These consist of Name=Value.  The template code later adds them into
	the _REQUEST array so they can be handled along with any Query String name-value pairs. to do that, it splits each in turn at the = symbol into this 'piecesOfArgVNameValuePair' array to then push them into _REQUEST
	");	
	
	//thought: i could make this a global by not putting it into the namespace but otherwise keep the same structure to the variable.
	${$nameSpaceID."_var"}['klogin_link'] =  array( "val" => array("db_conn_file" =>  $_SERVER['DOCUMENT_ROOT']."/klogin/klogin_database.php"), "DIRTY" => "", "argType" => -1, "mVal"=> true, "count" => 0, "varSpecificErrs" => array(), "mustPassOn" => false, "info" => 
	"BELONGS TO THE TEMPLATE.  the variable holds a value 'db_conn_file' set by the coder to point to a php file which establishes a connection
	to the single login/sl/klogin/authentication database to make login security privileges and controls available to this script
	so that they can be called upon. the setting of the value db_conn, below, uses an anonymous function which includes (so runs) the php file
	named above, which subsequently sets the other values including db_conn, login_database etc.  db_conn is then the useable reference to the 
	database connection for the rest of the script, and this script has access to all of the connection's parameters but the convention that these
	are set and maintained in a separate file is respected. If the connection to the database is not successful, the authenticated() function cannot
	function to check if a current app user is authenticated (logged in). if the variable \${\$nameSpaceID_var}['includeSecurity']['val'] = true 
	the failure of a successful authentication will prevent the coder's app-specific code from being run. if security and authentication isn't needed, ensure
	\${\$nameSpaceID_var}['includeSecurity']['val'] is set to false");
	
	if(${$nameSpaceID."_var"}['debugging']['val'] === true){
		echo '<div class="debugDiv"><h3>TEMPLATE STEP 1- CONNECT TO KLOGIN DATABASE.</h3>
		the Klogin app is a single login system. this part of the template\'s code will populate the template\'s \${\$nameSpaceID_var}[\'klogin_link\'] variable with a link to the login database.<br>';
		echo "<details><summary>more...</summary>With the klogin app integrated into their app, a coder can simply use php code  <b>&lt;?php if(authenticated() === \"true\"){ //do secured stuff; }?></b> to secure parts of their code.  <a href=''>more documentation is here</a></details>";
	}
	
	//if the developer has chosen to secure their script using the klogin system...
	if(array_key_exists('includeSecurity',${$nameSpaceID."_var"}) === true && array_key_exists('val',${$nameSpaceID."_var"}['includeSecurity']) === true && ${$nameSpaceID."_var"}['includeSecurity']['val'] === true){


	
	${$nameSpaceID."_var"}['klogin_link']['val']['db_conn'] = (function () {
		
		global $nameSpaceID; //make the namespaceid from the main body of the script available within the scope of this function
		global ${$nameSpaceID."_var"}; //now i can make available all main body variables (which SHOULD all be stored within the namespace!) 
		
		if(!isset(${$nameSpaceID."_var"}['klogin_link']['val']['db_conn_file'])){
			
			if(${$nameSpaceID."_var"}['debugging']['val'] === true){
				echo "no db link file was named. the path to and name of a .php file which creates a connection to a mysql database should be stored in variable 
				${$nameSpaceID."_var"}['klogin_link']['val']['db_conn_file'].  Normally, the value would be ''
				without that value in the variable, the database for the '' app can't be connnected to.<BR>";
			}
			return false;
		}else{

			if(!file_exists(${$nameSpaceID."_var"}['klogin_link']['val']['db_conn_file'])){
				if(${$nameSpaceID."_var"}['debugging']['val'] === true){
					echo "This script expects a file named in variable ['klogin_link']['val']['db_conn_file'] as ".${$nameSpaceID."_var"}['klogin_link']['val']['db_conn_file']." to exist, it doesnt. As a result, the script cannot read in the info needed to connect to the klogin database. that info would expected to be found in that file" ."<BR>";
				}							
				return false;
			}else{
				
				${$nameSpaceID."_var"}['klogin_link']['val']['db_conn_file_nameSpaceID'] = "NS_" . trim(preg_replace("/[^A-Za-z0-9_]/", '',preg_replace("#[/\\\\\.]+#", "_", substr(realpath(${$nameSpaceID."_var"}['klogin_link']['val']['db_conn_file']),strlen($_SERVER["DOCUMENT_ROOT"])))),"\n\r\t\v\0_");
				
				include(${$nameSpaceID."_var"}['klogin_link']['val']['db_conn_file']);
				
				//as this code is inside a function, variables produced here, including those declared in the include
				//inside this function, will not be accessible outside of the function in the main scope. 
				//seeing as I want to refer to at least one variable created in the include, ( login_database) later
				//outside of this current function, copy the include's namespace into the global namespace.
				${$nameSpaceID."_var"}['klogin_link']['val']['db_conn_file_nameSpace'] =  ${${$nameSpaceID."_var"}['klogin_link']['val']['db_conn_file_nameSpaceID']."_var"};
				
				if(isset(${$nameSpaceID."_var"}['klogin_link']['val']['db_conn_file_nameSpace']['app_link_err']['val'])=== true){
					//the following needs to be adjusted to use this layout and then i can pass back the vars into this namespace.

					${$nameSpaceID."_var"}['error']['val'] = ${$nameSpaceID."_var"}['klogin_link']['val']['db_conn_file_nameSpace']['app_link_err']['val'];
					if(${$nameSpaceID."_var"}['debugging']['val'] === true){
						echo "<pre>";
						trigger_error(${$nameSpaceID."_var"}['klogin_link']['val']['db_conn_file_nameSpace']['app_link_err']['val'], E_USER_WARNING);
						echo "</pre>";
					}
					return false;
				}else{
					if(${$nameSpaceID."_var"}['debugging']['val'] === true){
						echo "SUCCESS!";
						echo '<pre>';
						echo var_dump(${${$nameSpaceID."_var"}['klogin_link']['val']['db_conn_file_nameSpaceID']."_var"}['app_link']['val']);
						echo '</pre>';
					}
					
					return ${${$nameSpaceID."_var"}['klogin_link']['val']['db_conn_file_nameSpaceID']."_var"}['app_link']['val'];	
					
				} //end of if mysqli connect error returned
			}//end of !file_exists(db_conn_file)
		}// end of !isset(db_conn_file)
	})(); //end of self-invoking function to set klogin db_conn
	}else{//coder has opted out of using klogin to secure their script by setting ${$nameSpaceID."_var"}['includeSecurity']['val'] to false
		if(${$nameSpaceID."_var"}['debugging']['val'] === true){
			echo "<br>As the coder has set the variable \${\$nameSpaceID_var}['includeSecurity']['val'] to 'false', this template step has been bypassed and the coder cannot secure their code with the klogin system while the value is set to 'false'";
		}
	}
	if(${$nameSpaceID."_var"}['debugging']['val'] === true){
		echo "<h3>END OF 'CONNECT TO KLOGIN DATABASE' STEP</h3></div>";
	}
	
	//thought: i could make this a global by not putting it into the namespace but otherwise keep the same structure to the variable.
	${$nameSpaceID."_var"}['appStore_link'] =  array( "val" => array("db_conn_file" =>  $_SERVER['DOCUMENT_ROOT']."/klogin/appStore_database.php"), "argType" => -1, "mVal"=> true, "count" => 0, "varSpecificErrs" => array(), "mustPassOn" => false, "info" => 
	"BELONGS TO THE TEMPLATE.  the variable holds a value 'db_conn_file' set by the coder to point to a php file which establishes a connection
	to the appStore database to give this script access to the information about the app which it belongs to. 
	the setting of the value db_conn, below, uses an anonymous function which includes (so runs) the php file
	named above, which subsequently sets the other values including db_conn, appStore_database etc.  db_conn is then the useable reference to the 
	database connection for the rest of the script, and this script has access to all of the connection's parameters but the convention that these
	are set and maintained in a separate file is respected. 
	");
	
	if(${$nameSpaceID."_var"}['debugging']['val'] === true){
		echo "<div class=\"debugDiv\"><h3>TEMPLATE STEP 2 - CONNECT TO APPSTORE DATABASE</h3>the appStore database holds information about apps including useful default values. You should have registered this app in the appStore";
	}
	
	${$nameSpaceID."_var"}['appStore_link']['val']['db_conn'] = (function () {
		
		global $nameSpaceID; //make the namespaceid from the main body of the script available within the scope of this function
		global ${$nameSpaceID."_var"}; //now i can make available all main body variables (which SHOULD all be stored within the namespace!) 
		
		if(!isset(${$nameSpaceID."_var"}['appStore_link']['val']['db_conn_file'])){
			
			if(${$nameSpaceID."_var"}['debugging']['val'] === true){
				echo "describe that theres no db file named and the ramifications"."<BR>";
			}
			return false;
		}else{

			if(!file_exists(${$nameSpaceID."_var"}['appStore_link']['val']['db_conn_file'])){
				if(${$nameSpaceID."_var"}['debugging']['val'] === true){
					echo "This script expects a file named in variable ['appStore_link']['val']['db_conn_file'] as ".${$nameSpaceID."_var"}['appStore_link']['val']['db_conn_file']." to exist, it doesnt. As a result, the script cannot read in the info needed to connect to the appStore database. that info would expected to be found in that file" ."<BR>";
				}							
				return false;
			}else{
				
				${$nameSpaceID."_var"}['appStore_link']['val']['db_conn_file_nameSpaceID'] = "NS_" . trim(preg_replace("/[^A-Za-z0-9_]/", '',preg_replace("#[/\\\\\.]+#", "_", substr(realpath(${$nameSpaceID."_var"}['appStore_link']['val']['db_conn_file']),strlen($_SERVER["DOCUMENT_ROOT"])))),"\n\r\t\v\0_");
				
				include(${$nameSpaceID."_var"}['appStore_link']['val']['db_conn_file']);
				
				//as this code is inside a function, variables produced here, including those declared in the include
				//inside this function, will not be accessible outside of the function in the main scope. 
				//seeing as I want to refer to at least one variable created in the include, ( login_database) later
				//outside of this current function, copy the include's namespace into the global namespace.
				${$nameSpaceID."_var"}['appStore_link']['val']['db_conn_file_nameSpace'] =  ${${$nameSpaceID."_var"}['appStore_link']['val']['db_conn_file_nameSpaceID']."_var"};
				
				if(isset(${$nameSpaceID."_var"}['appStore_link']['val']['db_conn_file_nameSpace']['app_link_err']['val'])=== true){
					//the following needs to be adjusted to use this layout and then i can pass back the vars into this namespace.

					${$nameSpaceID."_var"}['error']['val'] = ${$nameSpaceID."_var"}['appStore_link']['val']['db_conn_file_nameSpace']['app_link_err']['val'];
					if(${$nameSpaceID."_var"}['debugging']['val'] === true){
						echo "<pre>";
						trigger_error(${$nameSpaceID."_var"}['appStore_link']['val']['db_conn_file_nameSpace']['app_link_err']['val'], E_USER_WARNING);
						echo "</pre>";
					}
					return false;
				}else{
					if(${$nameSpaceID."_var"}['debugging']['val'] === true){
						echo "<br>SUCCESS!";
					}
					//look in the namespace created for the file which holds the db connection details for the appStore for what it calls its app_link (the app it THAT files context being the app store)
					//return it here so that it ends up in THIS current scripts namespace as 'appStore_link'
					return ${${$nameSpaceID."_var"}['appStore_link']['val']['db_conn_file_nameSpaceID']."_var"}['app_link']['val'];
				} //end of if mysqli connect error returned
			}//end of !file_exists(db_conn_file)
		}// end of !isset(db_conn_file)
	})(); //end of self-invoking function to link to appStore database
	if(${$nameSpaceID."_var"}['debugging']['val'] === true){		
		echo "<h3>END OF 'CONNECT TO APPSTORE DATABASE' STEP</h3></div>";
	}
	
	
	if(${$nameSpaceID."_var"}['debugging']['val'] === true){
		echo "<div class=\"debugDiv\"><h3>TEMPLATE STEP 3 - HARDCODED DECLARATION OF THIS APP'S APPID</h3>";
	}
	
//CODER - REPLACE THE xxx on the next line with your appID	
	${$nameSpaceID."_var"}['appID'] = array("val"=> "98", "argType" => -1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "mustPassOn" => false, "info" => "
	the appID is the mysql row ID in the appStore for the record you created when you registered the new app in the appStore. It should be hardcoded in to replace the default 
	xxx value above.
	");
	
	if(${$nameSpaceID."_var"}['debugging']['val'] === true){
		if(array_key_exists('appID', ${$nameSpaceID."_var"}) === true && array_key_exists('val',${$nameSpaceID."_var"}['appID']) === true && filter_var(${$nameSpaceID."_var"}['appID']['val'], FILTER_VALIDATE_INT) !== false){
			echo 'appID in '. __FILE__ . ' is set to: '. ${$nameSpaceID."_var"}['appID']['val'];
		}else{
			trigger_error("Template violation. The variable \${\$nameSpaceID_var}['appID']['val'] does not exist or is not set to an integer" , E_USER_WARNING);
		}	
		echo "<h3>END OF 'HARDCODED DECLARATION OF THIS APP'S APPID' STEP</h3></div>";
	}
	
	//check that the template contains the template variables whose purpose is to functionally operate this low-documentation coding template.
	if(isset(${$nameSpaceID."_var"}) === false){
		trigger_error("Template violation.  The script template uses an array called '\${\$nameSpaceID_var'} to contain its internal variables but the array doesn't exist. either new code was inserted into the template to remove the array or the standard code to create the array was removed" , E_USER_ERROR);
	}elseif(array_key_exists('debugging',${$nameSpaceID."_var"}) === false || is_array(${$nameSpaceID."_var"}['debugging']) === false  || array_key_exists('val',${$nameSpaceID."_var"}['debugging']) === false){
		trigger_error("Template violation.  The script template uses an array called '\${\$nameSpaceID_var'} to contain its internal variables. The array exists but does not contain a required key called ['debugging']['val']. It has been manually removed from the template or unset in error." , E_USER_ERROR);
	}elseif(${$nameSpaceID."_var"}['debugging']['val'] !== false && ${$nameSpaceID."_var"}['debugging']['val'] !== true){
		trigger_error("Template violation.  The script template's variable '\${\$nameSpaceID_var'}['debugging']['val'] must be set to true or false by the coder. this has not been done. the coder is required to correct this value or the script can not continue" , E_USER_ERROR);
	}elseif(${$nameSpaceID."_var"}['debugging']['val'] === true){
		echo '<div class="debugDiv"><H3>TEMPLATE STEP 4- TEMPLATE VARIABLES ARE DECLARED</H3>The purpose of these inbuilt template variables is to operate the workings of the template. They should generally not be touched.<details><summary>view variables...</summary><pre>';
		var_dump(${$nameSpaceID."_var"});
		echo "</details></pre><H3>END OF 'TEMPLATE VARIABLES ARE DECLARED' STEP</H3></div>";
	}
	
	
	
//END OF TEMPLATE DECLARE TEMPLATE VARIABLES WHOSE PURPOSE IS TO FUNCTIONALLY OPERATE THIS LOW-DOCUMENTATION CODING TEMPLATE

//CODER ACTION - REVIEW TEMPLATE VARIABLE VALUES
	/*review default val set in ${$nameSpaceID."_var"}['debugging']
		while the coder is developing and trouble-shooting their code in this document, they may want to switch debugging to true
		this has two impacts. 
			1) it collects and outputs debug information as the script runs, so that it can be presented to the coder
			2) if the code is running with an outputMode of 'TEXT', it would normally end by handing over to (redirecting to) another script (named in the nextURL) variable. but in debug mode, that handover is suppressed, giving the coder the opportunity to view the debug info presented to them.
	
	review default val set in ${$nameSpaceID."_var"}['includeSecurity']
		when 'includeSecurity' is true, the non-template section of this script (coded by the coder) will only run if it is doing so under an authorised session created by klogin security.  the coder should determine whether or not the proceses they are coding should only be runnable by a signed in user (with an authorised session) and should set 'includeSecurity' to true if that is the case.
	
	review default val set in ${$nameSpaceID."_var"}['supportedLocales']
		any application developed to the NO_DOCUMETATION standard will be capable of supporting multiple languages and associated locales(eg time zones). 
		To set up support for a locale, the root folder of the app on the web server will contain a folder called 'languagePacks' into which langauge-specific output eg. error messages are stored. If a new language is to be supported by the app, then to let this script know that it should be made available, the 'supportedLangages' variable should be updated to list that additional languagePack.  It is not automaitcally assumed that because the languagePack might be available in the folder that it should be considered 'supported'. It's up to the coder to register supported locales by maintaining the supportedLocales variable within templates.
		
	review calculation of default val set in ${$nameSpaceID."_var"}['supportedLocales']
		
	
	
	
	*/
//END OF CODER ACTION
	
	${$nameSpaceID."_var"}['sql'] 			= array("val"=> "", "argType" => -1, "mVal"=> false, "count" => 0, "mustPassOn" => false, "info" => "
	holds an sql query as it will be presented to MYSQL to be executed, and all pre-completed versions of the query as it is perhaps constructed. the same variable
	will be re-used if there are multiple sql queries run by the script so that all queries can be quickly found by the coder by searching for S{SnameSpaceID.'_var'}['sql']
	");
	
	${$nameSpaceID."_var"}['result'] 			= array("val"=> "", "argType" => -1, "mVal"=> false, "count" => 0, "mustPassOn" => false, "info" => "
	holds the mysqli_query response which will be the resultset as a resource of a true/false value depending on the nature of the query. count would be
	mysqli_num_rows /affected rows
	");
	
	${$nameSpaceID."_var"}['row'] 			= array("val"=> array(), "argType" => -1, "mVal"=> true, "count" => 0, "mustPassOn" => false, "info" => "holds a single row from a mysqli_query resultset in a mysqli query response ");
	
	${$nameSpaceID."_var"}['rows'] 			= array("val"=> array(), "argType" => -1, "mVal"=> true, "count" => 0, "mustPassOn" => false, "info" => "
	holds multiple rows or an entire recordset returned from a mysqli_query result. useful for either receiving a mysqli_fetch_all or for accumilating individual instances of 'row'
	");
		
	${$nameSpaceID."_var"}['rowCount'] 			= array("val"=> -1, "argType" => -1, "mVal"=> false, "count" => 1, "mustPassOn" => false, "info" => "");
	
	${$nameSpaceID."_var"}['rowCount2'] 		= array("val"=> -1, "argType" => -1, "mVal"=> false, "count" => 1, "mustPassOn" => false, "info" => "");
	
	${$nameSpaceID."_var"}['rKey'] 				= array("val"=> "", "argType" => -1, "mval"=> false, "count" => 0, "mustPassOn" => false, "info" => "
	array key an etheral variable which holds a single key (name) from any array as the array is iterated through. the named key equivalent of the traditional and ubiquitous 'i' variable for arrays keyed with integers");
		
	${$nameSpaceID."_var"}['vKey'] 				= array("val"=> "", "argType" => -1, "mval"=> false, "count" => 0, "mustPassOn" => false, "info" => "
	array key an etheral variable which holds a single key (name) from any array as the array is iterated through. the named key equivalent of the traditional and ubiquitous 'i' variable for arrays keyed with integers");

	
    //self-populates  - NOTE! documentRoot set here may be overwritten in TEMPLATE STEP 6 by value returned from DB. make sure value in DB is correct with no leading or trailing forward slashes
	${$nameSpaceID."_var"}['app_link'] = array("val" => array(
	"documentRoot" => explode("/",str_replace("\\", "/", substr(realpath(__FILE__),strlen($_SERVER["DOCUMENT_ROOT"])+1)))[0],
	"db_conn_file" =>  $_SERVER['DOCUMENT_ROOT']."/klogin/".explode("/",str_replace("\\", "/", substr(realpath(__FILE__),strlen($_SERVER["DOCUMENT_ROOT"])+1)))[0]."_database.php"
	), "argType" => -1, "mVal"=> true, "count" => 2, "mustPassOn" => false, "info" => "
	an array of two values. the first, db_conn, is initially a null but will subsequently be used to hold a myslqi connection object. the second, db_conn_file, is the path to and filename of a php file, relative to the document root, which creates a mysqli_connection to a database.  Note this doesn't MAKE the connection, that has to be done
	by including the include. it says where the include is and gives a place for the connection to be stored. 
	");

	if(${$nameSpaceID."_var"}['debugging']['val'] === true){
		echo "<div class=\"debugDiv\"><h3>TEMPLATE STEP 5 - CONNECT TO THIS APP'S DATABASE</h3>";
		echo "this app's database is connected to using parameters described in the connection file stored on this server in folder ". $_SERVER['DOCUMENT_ROOT']. "/klogin/<BR>";
		echo "the filename for the database must be the short name of this app followed  by '_database.php' for example, myapp_database.php . The short name is lifted from the
		current script's filepath (the name of the folder directly after ". $_SERVER['DOCUMENT_ROOT']."). In this case it was calculated as: '";
		echo $_SERVER['DOCUMENT_ROOT']."/klogin/".explode("/",str_replace("\\", "/", substr(realpath(__FILE__),strlen($_SERVER["DOCUMENT_ROOT"])+1)))[0]."_database.php'";
	}

	${$nameSpaceID."_var"}['app_link']['val']['db_conn'] = (function () {
		global $nameSpaceID; //make the namespaceid from the main body of the script available within the scope of this function
		global ${$nameSpaceID."_var"}; //now i can make available all main body variables (which SHOULD all be stored within the namespace!) 

		if(!isset(${$nameSpaceID."_var"}['app_link']['val']['db_conn_file'])){
			
			if(${$nameSpaceID."_var"}['debugging']['val'] === true){
				echo "describe that theres no db file named and the ramifications"."<BR>";
			}
			return false;
		}else{

			if(!file_exists(${$nameSpaceID."_var"}['app_link']['val']['db_conn_file'])){
				if(${$nameSpaceID."_var"}['debugging']['val'] === true){
					echo "This script expects a file named in variable ['app_link']['val']['db_conn_file'] as ".${$nameSpaceID."_var"}['app_link']['val']['db_conn_file']." to exist, it doesnt. As a result, the script cannot read in the info needed to connect to the app database. that info would expected to be found in that file" ."<BR>";
				}							
				return false;
			}else{
				
				${$nameSpaceID."_var"}['app_link']['val']['db_conn_file_nameSpaceID'] = "NS_" . trim(preg_replace("/[^A-Za-z0-9_]/", '',preg_replace("#[/\\\\\.]+#", "_", substr(realpath(${$nameSpaceID."_var"}['app_link']['val']['db_conn_file']),strlen($_SERVER["DOCUMENT_ROOT"])))),"\n\r\t\v\0_");
				
				include(${$nameSpaceID."_var"}['app_link']['val']['db_conn_file']);
				
				//as this code is inside a function, variables produced here, including those declared in the include
				//inside this function, will not be accessible outside of the function in the main scope. 
				//seeing as I want to refer to at least one variable created in the include, ( login_database) later
				//outside of this current function, copy the include's namespace into the global namespace.
				${$nameSpaceID."_var"}['app_link']['val']['db_conn_file_nameSpace'] =  ${${$nameSpaceID."_var"}['app_link']['val']['db_conn_file_nameSpaceID']."_var"};
				if(isset(${$nameSpaceID."_var"}['app_link']['val']['db_conn_file_nameSpace']['app_link_err']['val'])=== true){
					//the following needs to be adjusted to use this layout and then i can pass back the vars into this namespace.
				

					${$nameSpaceID."_var"}['error']['val'] = ${$nameSpaceID."_var"}['app_link']['val']['db_conn_file_nameSpace']['app_link_err']['val'];
					if(${$nameSpaceID."_var"}['debugging']['val'] === true){

						echo "<pre>";
						echo ${$nameSpaceID."_var"}['app_link']['val']['db_conn_file_nameSpace']['app_link_err']['val'];
						//trigger_error(${$nameSpaceID."_var"}['app_link']['val']['db_conn_file_nameSpace']['app_link_err']['val'], E_USER_WARNING);
						echo "</pre>";
					}
					return false;
				}else{
					if(${$nameSpaceID."_var"}['debugging']['val'] === true){
						echo "SUCCESS!";
					}
					return ${${$nameSpaceID."_var"}['app_link']['val']['db_conn_file_nameSpaceID']."_var"}['app_link']['val'];
				} //end of if mysqli connect error returned
			}//end of !file_exists(db_conn_file)
		}// end of !isset(db_conn_file)
	})(); //end of self-invoking function to set app_constants db_conn
	if(${$nameSpaceID."_var"}['debugging']['val'] === true){
		echo "<h3>END OF 'CONNECT TO THIS APP'S DATABASE'</h3></div>";
	}
	
		
			
	if(!isset(${$nameSpaceID."_var"}['appID']['val'])){
		if(${$nameSpaceID."_var"}['debugging']['val'] === true){
			echo "debugging explain that no attempt was made to retrieve any app constants from the db because appid val is not set "."<BR>";
			echo "explain to the coder what the app id is and how to get an existing one or look one up (the app database).<BR>";
		}
		return false;
	}else{ //else of !isset(appID)
	
		//if there is an app id, use connection to the app database to run sql to populate the parent array with whatever values are returned by the query. 
		//get the app data if possible stick this in the 
		${$nameSpaceID."_var"}['sql']['val'] = "SELECT  `medname`, `shortDescription`, `protocol`, `domain`, `documentRoot`, `homePage`,`legalAndPolicyLink` FROM `%s`.`apps_%s` WHERE id = '%s' LIMIT 1";
		
		${$nameSpaceID."_var"}['sql']['val'] = sprintf(${$nameSpaceID."_var"}['sql']['val'],${$nameSpaceID."_var"}['appStore_link']['val']['db_conn_file_nameSpace']['app_database']['val'], strtolower(${$nameSpaceID."_var"}['loc']['val']), ${$nameSpaceID."_var"}['appID']['val']);
		if(${$nameSpaceID."_var"}['debugging']['val'] === true){
			echo "<div class=\"debugDiv\" ><H3>TEMPLATE STEP 6 - TRY TO GET THIS APP'S INFO FROM APPSTORE DB</H3>";
			echo 'attempted to use existing connection to db, which was created using connection details in '. (${$nameSpaceID."_var"}['appStore_link']['val']['db_conn_file']).'<BR>';
			if((${$nameSpaceID."_var"}['appStore_link']['val']['db_conn']) === false){
				echo 'failed to connect to database<BR>';
			}else{
				echo 'connected to database successfully<BR>';
				echo 'attempted to get information about the current app, identifying the current app by its id<BR>';
				echo "the app's id was retrieved from a variable in this current script, which should have been set by the programmer. The variable is called";
				echo " '<em>\${\$nameSpaceID_var}['appID']</em>' and is currently set to '".${$nameSpaceID."_var"}['appID']['val']."'<BR>"; 					
				echo "used this information to construct the following SQL:<BR><BR>";
				echo ${$nameSpaceID."_var"}['sql']['val']."<BR><BR>";
			}
			
			
			

		}
		
		if((${$nameSpaceID."_var"}['appStore_link']['val']['db_conn']) === false){
			echo "</pre><H3>FAIL! failed to get information about the app from the appStore database. could not establish a connection to the appstore database </H3></div>";
		}else{	
			if(!${$nameSpaceID."_var"}['result']['val']  = mysqli_query(${$nameSpaceID."_var"}['appStore_link']['val']['db_conn'], ${$nameSpaceID."_var"}['sql']['val'])){
				$error= mysqli_error(${$nameSpaceID."_var"}['appStore_link']['val']['db_conn']);
				$allErrors['error'][] = $error;
				if(${$nameSpaceID."_var"}['debugging']['val'] === true){
					echo "<H3>FAIL! failed to get information about the app from the database</H3>";
					echo $error;
					echo "</pre></div>";
				}
			}else{ //else of !result
				if(mysqli_num_rows(${$nameSpaceID."_var"}['result']['val'] ) != 1){
					$error = 'sql found no apps with appID "'.${$nameSpaceID."_var"}['appID']['val'].'" in the "'.$language.'" language appStore';
					$allErrors['error'][] = $error;
					if(${$nameSpaceID."_var"}['debugging']['val'] === true){
						echo "<H3>FAIL! failed to get information about the app from the database</H3>";
						var_dump(${$nameSpaceID."_var"}['result']['val']);
						echo "</pre></div>";
					}
				}else{ //else of if num_rows
					
					
					${$nameSpaceID."_var"}['row']['val'] = mysqli_fetch_assoc(${$nameSpaceID."_var"}['result']['val']);
					
					foreach(array_keys(${$nameSpaceID."_var"}['row']['val']) as ${$nameSpaceID."_var"}['rKey']['val']){
						${$nameSpaceID."_var"}['appStore_link']['val'][${$nameSpaceID."_var"}['rKey']['val']] = ${$nameSpaceID."_var"}['row']['val'][${$nameSpaceID."_var"}['rKey']['val']];
						if(${$nameSpaceID."_var"}['debugging']['val'] === true){
							echo ${$nameSpaceID."_var"}['rKey']['val'] ." = ". ${$nameSpaceID."_var"}['row']['val'][${$nameSpaceID."_var"}['rKey']['val']]."<BR>";
						}
					}
					
					if(${$nameSpaceID."_var"}['debugging']['val'] === true){
						echo "<pre>";
						var_dump(${$nameSpaceID."_var"}['result']['val']);
						echo "</pre><H3>SUCCESS! got information about the app from the database</H3></div>";
					}
					
					//not done - validate the inputs using the same approach as for request array but outputting to ${$nameSpaceID."_var"}['appStore_link']['val']['xxxx']


				} //end of if num_rows

			} //end of if !result
		} //end of if there is not a connection
	}//end if !isset appid 


	
	${$nameSpaceID."_var"}['userLocalTimeZone'] = array("val"=> "Europe/London", "DIRTY" => "", "argType" => 0, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "mustPassOn" => false, "info" => "
	should contain a timezone value listed in https://www.php.net/manual/en/timezones.php
	");

	/*
	
	example:
	${$nameSpaceID."_var"}['chocolate'] = array("val"=> "mars bar", "DIRTY" => "", "tmpVal" => "", "argType" => 1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "mustPassOn" => true, "info" => "an example of a variable set in the template script.");

	val = the actual value in the variable. it can be set on declaration to give a default.  
	DIRTY = dirty un-checked/un-validated user input. move value to 'val' once its been validated. 
	tmpVal = a transitory copy of 'val' eg. set aside so it can be mainpulated or processed to overwrite its self. 
	argType = how to process the variable. 
	   -1. variable is internal to this script. it cannot be overwritten by inputs from argv or the querystring
	    0. optionally expected to be received from argv or querystring. The script will not error if it is not provided but can validate and use it if it is.
		1. variable must be provided to the script from argv or querysrting. the script will error if it is not provided.
	
	mVal = multi-value. boolean. if true then, "val", when set, will hold an array of values not just a single value. where an argument destined to be stored (if validated) in an mval variable is semi-colon;delimted, it will be exploded into an array to be stored.
	count = if val holds an array (so its mVal is set to true),then "count" holds a count of values in the array to save it being recounted repeatedly.
	mustPassOn = boolean. if true, the variable's name-value pair will always be included in this scripts output eg. within the JSON object if the script's output is JSON or as a querystring name-value pair if this script finishes by redirecting to another URL.
	varSpecificErrs = used with argTypes 0 and 1. a ¬ delimited list of error codes (if any) returned during templated data validation/verification 
	info = a description of what the variable is for/what it holds/how it is used
	*/	
	if(${$nameSpaceID."_var"}['debugging']['val'] === true){echo '<div class="debugDiv"><H3>TEMPLATE STEP 7 - CODER TO DECLARE OWN VARIABLES</H3><details><summary>more...</summary><pre>'; trigger_error("this warning is thrown for the sole purpose of letting you know around which line of code you should declare your variables" , E_USER_WARNING); echo '</pre></details>';}	
//CODER ACTION - CODER TO DECLARE THEIR OWN VARIABLES BELOW HERE 
	
	
	//example variable. see long green text above for full details:
	//${$nameSpaceID."_var"}['chocolate'] = array("val"=> "mars bar", "DIRTY" => "", "tmpVal" => "", "argType" => 1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "mustPassOn" => true, "info" => "an example of a variable set in the template script.");
	${$nameSpaceID."_var"}['medName'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "mustPassOn" => true, "info" => "an example of a variable set in the template script.");
	${$nameSpaceID."_var"}['longDescription'] = array("val"=> "", "DIRTY" => "", "tmpVal" => "", "argType" => 1, "mVal"=> false, "count" => 1, "varSpecificErrs" => array(), "mustPassOn" => true, "info" => "an example of a variable set in the template script.");

//END OF CODER TO DECLARE THEIR OWN VARIABLES HERE
	if(${$nameSpaceID."_var"}['debugging']['val'] === true){
		echo '<details><summary>view variables...</summary><pre>';
		var_dump(${$nameSpaceID."_var"});
		echo "</pre></details><H3>END OF 'CODER TO DECLARE OWN VARIABLES' STEP</H3></div>";
	}

//TEMPLATE	-START THE SESSION AND SET SECURITY-ASSOCIATED DOCUMENT HEADERS 
//			99.9% of time nothing must come before this except variable declarations.
//this needs more thought in terms of thinking about this script being embedded into another script which is runnign as a stream
//just to make sure sessions are re-closed again if they were closed by the parent script. 
	if(session_status() === PHP_SESSION_NONE){
		
		if(${$nameSpaceID."_var"}['debugging']['val'] === true){
			echo '<div class="debugDiv"><H3>TEMPLATE STEP 8 - TRY TO START/RESUME SERVER SESSION</H3><p>YOU ARE IN DEBUG MODE. This step WILL throw warnings as you are in debug mode. Debug mode ouputs this debug information which youre reading now. Unfortunately the outputting of anything causes a session to start earlier than expected. in debug mode, this is unavoidable, expect warnings here. debug mode is toggled on and off in the code with this variable: \${\$nameSpaceID."_var"}[\'debugging\'][\'val\']<pre>';
		}
		session_start();
		if(${$nameSpaceID."_var"}['debugging']['val'] === true){
			echo "</pre><H3>END OF 'TRY TO START/RESUME SERVER SESSION' STEP</H3></div>";
		}
		
		date_default_timezone_set(${$nameSpaceID."_var"}['userLocalTimeZone']['val']); //might be set to something else later by recived arguments/user settings 
	}
	
	//register activity - used to keep alive user sessions and streams (keep-alive has not actually been coded. never used it so far). 
	$_SESSION['latestRequestTime'] = time();

	//if the script is long-running, eg its delivering a stream, (or if any of its parents are delivering a stream then close the session

//END OF TEMPLATE - START THE SESSION



//TEMPLATE	-GEOGRAPIC AND LOCALE SETTINGS (TIMEZONE/LANGUAGE ETC)
	/*this section picks up and validates user preferences and settings for the localisation (language/time zone) which the user is operating in.
	it validates by comparing the 'loc' value input to this script (if any) against the set of 'supportedLocales' set in the array should be set to 
	contain locales which this script is set up to work with. user's choice of language for output of content/error messages is determined by receiving
	an argument called loc. support depends on the availablity of a corresponding locale file under /languagePacks/ folder. loc informs the value 
	stored in ${$nameSpaceID."_var"}['loc']['val']  which is used through the script. */
	if(${$nameSpaceID."_var"}['debugging']['val'] == true){
		echo '<div class="debugDiv"><h3>TEMPLATE STEP 9 - GEOGRAPIC AND LOCALE SETTINGS (TIMEZONE/LANGUAGE ETC)</h3>';
	}

	if(isset($_REQUEST)){
		if(array_key_exists('loc', $_REQUEST) === true){
			${$nameSpaceID."_var"}['loc']['DIRTY'] = $_REQUEST['loc'];
			if(array_key_exists(${$nameSpaceID."_var"}['loc']['DIRTY'],${$nameSpaceID."_var"}['supportedLocales']['val'])){
				${$nameSpaceID."_var"}['loc']['val'] = ${$nameSpaceID."_var"}['loc']['DIRTY'];
				if(${$nameSpaceID."_var"}['debugging']['val'] == true){
					echo "Locale was established from Query String Argument 'loc':".${$nameSpaceID."_var"}['loc']['val']."<BR>";
				}
			}else{
				if(${$nameSpaceID."_var"}['debugging']['val'] == true){
					echo 'Locale/Language "'.str_replace(array("'", "\"", "<", ">", "/", "\\", ";", ":", "`", "{", "}"), "*",${$nameSpaceID."_var"}['loc']['DIRTY']).'"specified in Query String Argument \'loc\' is not supported, defaulting to en-GB. (for security, any suspect characters have been deliberately replaced with * before this debug message was presented)<br>';
					echo "supported locales are those for which a languagePack has been created in the languagePacks folder in the root of this app's document root folder.<br>";
					echo "a list of the language packs is manually maintained by you the coder in the \${\$nameSpaceID_var}['supportedLocales']['val'] variable in this current script (" . __FILE__ . ")";
				}
			}
		}else{
			if(${$nameSpaceID."_var"}['debugging']['val'] == true){
				echo "for info only: no argument 'loc' was included in the query string of the URL requesting this script, so the script's default of ".${$nameSpaceID."_var"}['loc']['val']."(set when \${\$nameSpaceID_var}['loc'] was declared) will be used. ";
			}
		}
		
	}
	if(${$nameSpaceID."_var"}['debugging']['val'] == true){
		echo "<h3>END OF 'GEOGRAPIC AND LOCALE SETTINGS (TIMEZONE/LANGUAGE ETC)' STEP</h3></DIV>";
	}		
//END OF TEMPLATE	-GEOGRAPIC AND LOCALE SETTINGS (TIMEZONE/LANGUAGE ETC)	
	
	

//TEMPLATE  - COMMON DOCUMENT HEADER ELEMENTS 
	//headers which are necessary regardless of whether the document produced is JSON or HTML but only if this script is not called as an include/require
	
	if(${$nameSpaceID."_var"}['debugging']['val'] == true){
		echo '<div class="debugDiv"><H3>TEMPLATE STEP 10 - SETTING COMMON DOCUMENT HEADERS</H3>';
		echo 'Failed to send headers but no action is necessary. Your script is running in debug mode so headers cannot be set. This is expected and will resolve its self when the script is not in debug mode ';
	}else{
		if (str_replace('\\', '/', __FILE__) == $_SERVER['SCRIPT_FILENAME']) {
			header("Cache-Control: no-cache");
			header('X-Content-Type-Options: nosniff');  // to avoid IE sniffing (penetration testing 18/12/13)
			header("Expires: -1");
			date_default_timezone_set('Europe/London'); //server-side this is always Europe/London (UTC/GMT) but 
											  //knowledge of user's ${$nameSpaceID."_var"}['loc']['value'] may be used to localize user-facing output
		}
	}
	if(${$nameSpaceID."_var"}['debugging']['val'] == true){
		echo "<H3>END OF 'SETTING COMMON DOCUMENT HEADERS' STEP</H3></div>";
	}
//END OF TEMPLATE  - COMMON DOCUMENT HEADER ELEMENTS 


	



//TEMPLATE - CALL ANY INCLUDE FILES WHICH ARE USED FOR ALL FILES BUILT TO THE TEMPLATE STANDARD 
	if(${$nameSpaceID."_var"}['debugging']['val'] === true){
		echo '<div class="debugDiv"><H3>TEMPLATE STEP 11 - CALL ANY STANDARD TEMPLATE INCLUDE FILES</H3><pre>';
	}

	//normally single login/sl/klogin/authentication includes are made here
	//eg include_once($_SERVER['DOCUMENT_ROOT'] ."/klogin/cookiefunctions.php");

	include_once($_SERVER['DOCUMENT_ROOT'] ."/klogin/cookiefunctions.php");
	include_once( $_SERVER['DOCUMENT_ROOT'] ."/klogin/fieldValidationFunctions.php");
	include_once($_SERVER['DOCUMENT_ROOT'] ."/klogin/languagePacks/".${$nameSpaceID."_var"}['loc']['val']."/serverResponses.php");
	include_once($_SERVER['DOCUMENT_ROOT'] ."/klogin/languagePacks/".${$nameSpaceID."_var"}['loc']['val']."/DOMContent.php");

	if(${$nameSpaceID."_var"}['debugging']['val'] === true){
		
		echo "the following files have been included so far:\n";
		$included_files = get_included_files();
		$countOfIncludedFiles = count($included_files);
		if($countOfIncludedFiles == 1){
			echo "none\n"; //the first value returned is the name of the current file only, not an include.
		}else{
			for($i==1;$i < $countOfIncludedFiles ;$i++){
				echo $included_files[$i]."\n";
			}
		}
		
		
		echo "</pre><H3>END OF 'CALL ANY STANDARD TEMPLATE INCLUDE FILES' STEP</H3></DIV>";
	}
//END OF TEMPLATE - CALL ANY INCLUDE FILES WHICH ARE USED FOR ALL FILES BUILT TO THE TEMPLATE STANDARD 	


//CODER - CALL ANY APP-SPECIFIC INCLUDE OR REQUIRED FILES WHICH THE APP'S DEVELOPER (you?) SAID WERE NEEDED
	if(${$nameSpaceID."_var"}['debugging']['val'] === true){
		echo '<div class="debugDiv"><H3>TEMPLATE STEP 12 - CALL ANY APP-SPECIFIC INCLUDE OR REQUIRED FILES</H3><pre>';
		echo 'at a minimum, this likely includes an app-specific fieldValidationFunctions.php (called from the app\'s root directory), and also serverResponses.php and DOMContent.php (called from the app\'s languagePacks folder which normally sits in the app\'s root directory '; 
	}

	//normally any app-specific include or require files are are named here eg:
	//include_once( $_SERVER['DOCUMENT_ROOT']. "/". ${$nameSpaceID."_var"}['appStore_link']['val']['documentRoot'] . "/fieldValidationFunctions.php");




	include_once( $_SERVER['DOCUMENT_ROOT']. "/". ${$nameSpaceID."_var"}['appStore_link']['val']['documentRoot'] . "/fieldValidationFunctions.php");
	include_once($_SERVER['DOCUMENT_ROOT'] . "/". ${$nameSpaceID."_var"}['appStore_link']['val']['documentRoot'] . "/languagePacks/".${$nameSpaceID."_var"}['loc']['val']."/serverResponses.php");
	include_once($_SERVER['DOCUMENT_ROOT'] . "/". ${$nameSpaceID."_var"}['appStore_link']['val']['documentRoot'] . "/languagePacks/".${$nameSpaceID."_var"}['loc']['val']."/DOMContent.php");


	if(${$nameSpaceID."_var"}['debugging']['val'] === true){
		
		echo "the following files have been included so far:\n";
		$included_files = get_included_files();
		$countOfIncludedFiles = count($included_files);
		if($countOfIncludedFiles == 1){
			echo "none\n"; //the first value returned is the name of the current file only, not an include.
		}else{
			for($i==1;$i < $countOfIncludedFiles ;$i++){
				echo $included_files[$i]."\n";
			}
		}
		
		echo "</pre><H3>END OF 'CALL ANY APP-SPECIFIC INCLUDE OR REQUIRED FILES' STEP</H3></DIV>";
	}
//END OF CODER - CALL ANY INCLUDES


//TEMPLATE - DISPLAY THE STANDARD DEBUGGING INFO FOR THIS SCRIPT (if the script is running in debug mode - that is, with variable ${$nameSpaceID."_var"}['debugging']['val'] set to true)
	if(${$nameSpaceID."_var"}['debugging']['val'] === true){
		echo '<div class="debugDiv"><H3>TEMPLATE STEP 13 - DISPLAY THE STANDARDIZED GENERIC DEBUGGING INFO</H3>';
	}






	if(${$nameSpaceID."_var"}['debugging']['val'] === true && str_replace('\\', '/', __FILE__) == $_SERVER['SCRIPT_FILENAME']) { 
		if(function_exists('standardDebugOutput')){
			echo "<details><summary>View debug info...</summary><pre>";
			standardDebugOutput(__FILE__);
			echo "</pre></details>";
		}else{
			echo "the standardDebugOutput() function should have filled this section with debugging information but the function is unavailable.<BR>
			Since that function is normally sourced from a file called: '" . $_SERVER['DOCUMENT_ROOT'] . "/klogin/cookiefunctions.php', This suggests that 
			the instruction in this script (" . __FILE__ . ") to include the file called: '". $_SERVER['DOCUMENT_ROOT'] ."/klogin/cookiefunctions.php'
			is missing from the 'TEMPLATE - CALL ANY INCLUDE FILES WHICH ARE USED FOR ALL FILES BUILT TO THE TEMPLATE STANDARD' section of the script<BR>
			if you find the instruction to include that file is present, then perhaps the included file is damaged or incomplete";
		}
	}
	
	if(${$nameSpaceID."_var"}['debugging']['val'] === true){
		echo "</pre><H3>END OF 'DISPLAY THE STANDARDIZED GENERIC DEBUGGING INFO'</H3></DIV>";
	}	
//END OF TEMPLATE - DISPLAY THE STANDARDIZED DEBUGGING INFO FOR ANY TEMPLATED SCRIPT


//TEMPLATE - CHECK ARGUMENTS
	if(${$nameSpaceID."_var"}['debugging']['val'] === true){
		echo '<div class="debugDiv"><H3>TEMPLATE STEP 14 - CHECK ARGUMENTS</H3>';
		echo 'by this point, the template code has ensured a $_REQUEST array exists, and has moved any ARGV arguments into it so that all arguments can
		be processed from the $_REQUEST array';
	}
	
	
	if(isset($_REQUEST) === true){
		
		if(${$nameSpaceID."_var"}['debugging']['val'] === true){
			echo '<div class="debugDiv"><H3>TEMPLATE STEP 15 - VERIFY/VALIDATE EACH ARGUMENT</H3>';
		}
		
		


		
		//verification/validation for each value received as an argument,		
		foreach(array_keys($_REQUEST) as ${$nameSpaceID."_var"}['rKey']['val']){
			//if it corresponds to an expected variable which is expecting to be set by an argument
			if(isset(${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]) === true && ${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]['argType'] >= 0){
				//take the received value and store it, marked as dirty. 
				${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]['DIRTY'] = $_REQUEST[${$nameSpaceID."_var"}['rKey']['val']];
				$e = -1;
				//check the dirty value against the corresponding checking function which should have been written and included in the app's
				//top level directory, in a file called fieldValidationFunctions.php using function the naming convention checkX()  eg checksiteID()
				
				if(function_exists("check".${$nameSpaceID."_var"}['rKey']['val'])=== true){
				
					$e = ("check".${$nameSpaceID."_var"}['rKey']['val'])(${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]['DIRTY']);
					if($e == 0){
						//if the check returned an error code of zero, no error so copy the dirty value received into the variable's long-term usable value.
						${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]['val'] = ${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]['DIRTY'];
					
                        if(${$nameSpaceID."_var"}['debugging']['val'] === true){
							echo "the value '<script style='display:inline-block'>".${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]['val']."</script>' in argument '". ${$nameSpaceID."_var"}['rKey']['val']."' is considered valid because the function check" . ${$nameSpaceID."_var"}['rKey']['val'] . "() returned a response of or equivalent to 0 as an error code (meaning no errror).<BR>
							the function is likely defined in '".$_SERVER['DOCUMENT_ROOT']."/".${$nameSpaceID."_var"}['appStore_link']['val']['documentRoot'] ."/fieldValidationFunctions.php'<BR><BR>";
						}					
					
					
					}else{
						
						
						${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]['varSpecificErrs'][] = $e; //$e holds the error code at this point, which is going to be returned to user.
						
						if(${$nameSpaceID."_var"}['debugging']['val'] === true){
							echo "this script anticipated receiving an argument called '".${$nameSpaceID."_var"}['rKey']['val']."' and received it. it attempted to verify and validate the value received in that variable. To do that, it used the check" . ${$nameSpaceID."_var"}['rKey']['val'] . "() function which is probably defined in '".$_SERVER['DOCUMENT_ROOT']."/".${$nameSpaceID."_var"}['appStore_link']['val']['documentRoot'] ."/fieldValidationFunctions.php'. The check" . ${$nameSpaceID."_var"}['rKey']['val'] . "() function exists, and received the value provided to it (value not shown here to avoid code injection). The function returned error code: " . $e. " meaning that the value which was checked is considered to be invalid. <BR>";	
							//see if i can decode the error code here.
							if(is_array($errors) && array_key_exists($e,$errors)){
								echo "the serverResponses.php file for this app defines error ". $e. " as:<em> ". $errors[$e]."</em>";
							}else{
								echo "the returned error code,". $e.  ", is not defined in the serverResponses.php file for this app, so the coder will have to examine the check" . ${$nameSpaceID."_var"}['rKey']['val'] . "() function to find out why the verification/validation check rejected the value it received.<BR>";
							}
							echo "<BR>";
						}
					}
				}else{	
					echo "<pre>";
					trigger_error("a key-value-pair '".${$nameSpaceID."_var"}['rKey']['val']."' was received by this script and was ignored since a corresponding validity checking function was not found. If the value is to be processed, a function named 'check".${$nameSpaceID."_var"}['rKey']['val']."()' is required. That function would traditionally be coded in file '".$_SERVER['DOCUMENT_ROOT']."/".${$nameSpaceID."_var"}['appStore_link']['val']['documentRoot'] ."/fieldValidationFunctions.php'. So on this occasion, the key-value-pair will be ignored except to pass it through to any script that this script redirects to on completion. This issue was encountered " , E_USER_WARNING);
					echo "</pre>";
					${$nameSpaceID."_var"}['PASSTHRU_DIRTY']['val'][${$nameSpaceID."_var"}['rKey']['val']] = $_REQUEST[${$nameSpaceID."_var"}['rKey']['val']];
				}
			}else{
				//else the received value doesnt correspond to a variable expecting input from an argument so set it to one side.
				${$nameSpaceID."_var"}['PASSTHRU_DIRTY']['val'][${$nameSpaceID."_var"}['rKey']['val']] = $_REQUEST[${$nameSpaceID."_var"}['rKey']['val']];				
			}
		}
		


		if(${$nameSpaceID."_var"}['debugging']['val'] === true){
			
					
			if(count(${$nameSpaceID."_var"}['PASSTHRU_DIRTY']['val'])>0){
				echo "this script (" . __FILE__ . ") did not anticipate receipt of the following arguments 
				as they are not declared in the 'CODER TO DECLARE THEIR OWN VARIABLES HERE' section. 
				as a result, they were not used by this script. instead they were  quarantined into the 
				\${\$nameSpaceID_var}['PASSTHRU_DIRTY'] array and will be 'passed through' (handed as-is) 
				to any response which this script delivers. This is a deliberate security measure to ensure that this 
				script doesnt ingest unexpected input.  The use of the  pass_thru array makes the  input available 'downstream' to any other process
				which might expect to receive it even if this script did not expect it. If you want this script to process any of these variables,
				this script must be told in the 'CODER TO DECLARE THEIR OWN VARIABLES HERE' section to expect it:.<BR><BR>";
				foreach(array_keys(${$nameSpaceID."_var"}['PASSTHRU_DIRTY']['val']) as ${$nameSpaceID."_var"}['rKey']['val']){
					echo "unexpected argument:'" . ${$nameSpaceID."_var"}['rKey']['val']."' was ignored<BR>";
				}
			}
			
			echo "<H3>END OF 'VERIFY/VALIDATE EACH ARGUMENT' STEP</H3></DIV>";
		}
		
		
		
		//check each variable which is mandated to be set from arguments is actually set. 
		if(${$nameSpaceID."_var"}['debugging']['val'] === true){
			echo '<div class="debugDiv"><H3>TEMPLATE STEP 16 - CHECK A VALUE IS SET FOR ANY ARGUMENT FLAGGED AS MANDATORY</H3>';
			echo "variables properly declared in the template standard (as part of nameSpaceID._var) should be declared in the 'CODER TO DECLARE THEIR OWN VARIABLES HERE' part of the code
			and, when declared to the standard used there, its 'argType' can be set to 1 to mark the variable as one for which it is mandatory that a variable of that name is received as an argument by this script. 
			The template code will now check each variable marked as mandatory to see if it has been set and will 
			register an error for any unset mandatory variable. Normally a variable's value would be set by receipt of a corresponding argument so likely an error registered here implies 
			that an argument is missing when this script was invoked.";
		}	
		

		
		${$nameSpaceID."_var"}['vKey']['tmpVal'] = false; //will use this as a transitory flag that this foreach loop identified an input error in any input
		foreach(array_keys(${$nameSpaceID."_var"}) as ${$nameSpaceID."_var"}['vKey']['val']){
			if(${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['vKey']['val']]['argType'] === 1 && ${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['vKey']['val']]['val'] === "" && ${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['vKey']['val']]['DIRTY'] === ""){
				
				//add the error info to the related variable's varSpecificErrs array so that it is returned to the user
				${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['vKey']['val']]['varSpecificErrs'][] = "60"; //mandatory value missing
				${$nameSpaceID."_var"}['vKey']['tmpVal'] = true;
				
				if(${$nameSpaceID."_var"}['debugging']['val'] === true){
					echo "<BR><BR>the scripts variables are storing no value for a mandatory argument called ". ${$nameSpaceID."_var"}['vKey']['val']."<BR>";
				}
			}
		}
		if(${$nameSpaceID."_var"}['debugging']['val'] === true){
			if(${$nameSpaceID."_var"}['vKey']['tmpVal'] == false){
				echo "<H3>SUCCESS! END OF 'CHECK A VALUE IS SET FOR ANY ARGUMENT FLAGGED AS MANDATORY' STEP. Any mandated argument is set</H3></div>";
			}else{
				echo "<H3>FAIL!</H3> One or more mandated arguments which this script needs to receive are not stored in their corresponding variable.<BR>this means that the script was told that it is mandatory that it receives a particular input as an argument and to store the value in a corresponding variable but by this step of the code, the variable contains nothing.<BR>				
				Likely causes are:<ul>
				<li>the expected argument was not received. The \$_REQUEST array in STEP 13's Debug information will show you what was received by this script or
				<li>the expected argument WAS received as evidenced by the \$_REQUEST array's content in STEP 13's Debug information -  but then in the previous step ('STEP 15 - VERIFY/VALIDATE EACH ARGUMENT') the received value of the argument was rejected as invalid, so the variable which would be used to store the argument's value remains empty or
				<li>the variable to receive the argument's value was overwritten or replaced. Perhaps the coder has mistakenly declared multiple variables of the same name?
				<li>perhaps the 'CODER TO DECLARE THEIR OWN VARIABLES HERE' step of '" . __FILE__ ."' mistakenly identifies a non-mandatory argument as mandatory so this script thinks that the argument should have been received when it should not have.
				</ul>
				mandatory arguments are identified by having a corresponding variable which has a key-value-pair of 'argType' set to 1. For example, if the script cannot function without receiving an argument called 'chocolate' this is registered in the 'CODER TO DECLARE THEIR OWN VARIABLES HERE' section of the script by the coder creating a variable as follows:<br>
				\${\$nameSpaceID.\"_var\"}['chocolate'] = array(\"val\"=> \"\", \"argType\" => 1,  ......etc...<br>
				</div>";

			}
		}
		${$nameSpaceID."_var"}['vKey']['tmpVal'] = ""; //remove the transitory flag
		
		
		if(${$nameSpaceID."_var"}['debugging']['val'] === true){
			echo '<div class="debugDiv"><H3>TEMPLATE STEP 17 - REFORMAT ANY SEMI-COLON-DELIMITED ARGUMENTS</h3>
			Assure that any validated/verified multi-value variables are PHP arrays. Convert from semi-colon-delimited strings if necessary.<br>'; 
		}
			
		//ensure that any multi-value name-value pair which has passed validation and verification is correctly stored as an array of values
		//regardless of whether it was received as a string containing a single value, a comma-separated string of values
		//or an array of values, and register a count of how many values there are.
		foreach(array_keys(${$nameSpaceID."_var"}) as ${$nameSpaceID."_var"}['vKey']['val']){
			
			//var_dump( ${$nameSpaceID."_var"}['vKey']['val']);
			
			if(${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['vKey']['val']]['argType'] >= 0 && ${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['vKey']['val']]['mVal'] == true && count(${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['vKey']['val']]['varSpecificErrs']) == 0){
				if(is_array(${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['vKey']['val']]['val']) === true){
					//do nothing its already an array
				}else{
					//set the value aside into its temp storage 
					${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['vKey']['val']]['tmpVal'] = ${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['vKey']['val']]['val'];
					//place an empty array ready to receive the value back again.
					${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['vKey']['val']]['val'] = array();
					//return the value to the array
					if(strpos(${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['vKey']['val']]['tmpVal'],";") ===  false){
						${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['vKey']['val']]['val'][] = ${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['vKey']['val']]['tmpVal'];
					}else{
						${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['vKey']['val']]['val'] = explode(";", ${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['vKey']['val']]['tmpVal']);
					}
					
					if(${$nameSpaceID."_var"}['debugging']['val'] === true){
						echo "string value '". ${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['vKey']['val']]['tmpVal'] ."' in variable '". ${$nameSpaceID."_var"}['vKey']['val'] ."' was converted to array <BR>";
						var_dump(${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['vKey']['val']]['val']);
					}
					
					//clean up the set-aside value which is now copied into the array.
					unset(${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['vKey']['val']]['tmpVal']);
				}
				${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['vKey']['val']]['count'] = count(${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['vKey']['val']]['val']);
			}//end of 'if variable should be an array
			
		}
		if(${$nameSpaceID."_var"}['debugging']['val'] === true){
			echo "<H3>END OF 'REFORMAT ANY SEMI-COLON-DELIMITED ARGUMENTS' STEP</H3></div>";
		}

	}//end request is set
	
	

	
	if(${$nameSpaceID."_var"}['debugging']['val'] === true){
		echo "<H3>END OF 'CHECK ARGUMENTS'</H3></div>";
	}
	
	

	if(${$nameSpaceID."_var"}['debugging']['val'] == true){
		echo '<div class="debugDiv"><h3>TEMPLATE STEP 18 - PREPARE EITHER A QUERY-STRING-LOADED URL OR A JSON OBJECT TO ACT AS THE OUTPUT FOR THIS SCRIPT</h3>';	
		echo "although the output for the script isn't required yet, prepare the stub of the output now, before the coder's app-specific code is reached, so that the coder's app can add more output if desired<BR><BR>"; 
		echo "output mode:  ". ${$nameSpaceID."_var"}['outputMode']['val']. "<BR><BR>";
	}
	if(${$nameSpaceID."_var"}['outputMode']['val'] == "TEXT"){
		
		echo "as the variable \${\$nameSpaceID_var}['outputMode']['val'] does not contain the value 'JSON' the script will build up a QUERY-STRING-LOADED URL. the final act of this script will be to
		REDIRECT to that URL. its query string will carry any output values to the QUERY-STRING-LOADED URL's destination. <BR><BR>Commonly, when an app has a front end and a back end, the QUERY-STRING-LOADED URL's destination
		will be the front end page to which this back end code is related...probably the page the user has just visited this page from.<BR><BR>
		Commonly, in other people's code, the URL to return to is taken at good faith from this page's headers, which include 'referrer' information. however, this is 
		known to be a secuirty vulnerability so this script avoids using that if possible.
		
		its general approach is to try and keep track in the \$_SESSION of each page a user visits, and to 
		step backward to the last page registered in the \$_SESSION. This is imperfect as the user might have several windows open simultaneously so the session's tracking may be eratic";
		

		
		if(${$nameSpaceID."_var"}['debugging']['val'] == true){
			echo "first calculate the full url of this current page and set the url aside temporarily in the \$CURRENTURL variable.<BR>;";
		}
	
		//calculate the current url of this script
		$CURRENTURL= 'http';
		$CURRENTURL .= ((array_key_exists('HTTPS',$_SERVER) === true && isset($_SERVER['HTTPS']) === true && strtolower($_SERVER['HTTPS']) === "on" )?"s":"");
		$CURRENTURL .= '://';		
		$CURRENTHOST = ((array_key_exists('HTTP_HOST',$_SERVER) === true && isset($_SERVER['HTTP_HOST']) === true && $_SERVER['HTTP_HOST'] === "80")?'localhost':$_SERVER['HTTP_HOST']);
		$CURRENTURL .= $CURRENTHOST;
		$CURRENTURL .= ((array_key_exists('SERVER_PORT',$_SERVER) === true && isset($_SERVER['SERVER_PORT']) === true && $_SERVER['SERVER_PORT'] != "" && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) ? ":" . $_SERVER['SERVER_PORT'] :""); 
		$CURRENTURL .= ((array_key_exists('SCRIPT_NAME',$_SERVER) === true && isset($_SERVER['SCRIPT_NAME']) === true && $_SERVER['SCRIPT_NAME'] != "") ? $_SERVER['SCRIPT_NAME'] :""); 
		
		if(${$nameSpaceID."_var"}['debugging']['val'] == true){
			echo "current url is:" . $CURRENTURL . "<BR>";
			echo "current host domain is:" . $CURRENTHOST. "<BR><BR>";	
		}
		
		
		//the intended 'normal' way. if using the session for tracking user's path, and they have only one tab open in the session.
		if(${$nameSpaceID."_var"}['debugging']['val'] === true){
			echo '<div class="debugDiv"><h3>TEMPLATE STEP 19 - TRY TO USE SESSION-BASED NAVIGATION VARS</h3>
			this server-side script needs to determine the URL which it will forward the client to after this code is complete. The preferred way 
			to do this is to use navigational information being tracked in the client\'s SESSION<br>';
		}
		if(array_key_exists("nextURL", $_SESSION) && isset($_SESSION['nextURL']) &&  filter_var($_SESSION['nextURL'], FILTER_VALIDATE_URL) != false  && $_SESSION['nextURL'] ===  $CURRENTURL){
			//primary method of navigation - use navigation session variables. if the inherited 'nexturl' is valid and matches the current scripts URL in \$CURRENTURL...
			$parse = parse_url($_SESSION['nextURL']);
			if($parse['host'] === $CURRENTHOST){
				if(${$nameSpaceID."_var"}['debugging']['val'] === true){
					echo "PRIMARY METHOD SUCCESS!  Primary method of tracking user journey using session navigational variables was SUCCESSFUL.<BR>";	
					echo "\$_SESSION['nextURL'] was inhereted from a previous page. It is a valid URL and the host domain is the same as the host domain of this script. it matches the current URL pefectly<BR>";
				}
				$_SESSION['nextURL'] = $_SESSION['thisURL']; //the inhereted 'thisURL' (where user came from) becomes the next url to go back to
				$_SESSION['prevURL'] = $_SESSION['thisURL']; //the inhereted 'thisURL' (where the user came from) becomes 'prevURL' (where the user came from).
				if(${$nameSpaceID."_var"}['debugging']['val'] == true){
					echo "
					Updated Navigational Session variables:<BR>.
					\$_SESSION['nextURL'] = ".$_SESSION['thisURL']."<BR>
					\$_SESSION['prevURL'] = ".$_SESSION['thisURL']."<BR>";
				}
			}else{
				if(${$nameSpaceID."_var"}['debugging']['val'] === true){
					echo "PRIMARY METHOD FAILED: reasons.  <BR>";	
					echo "the host of the 'nextURL' value received from the previous page does not match the current host:<BR>";
					echo "Exists: ". array_key_exists('nextURL',$_SESSION)."<BR>";
					echo "Is Set: ". (isset($_SESSION['nextURL']) === true? "true": "false")."<BR>";
					echo "valid : ". (isset($_SESSION['nextURL']) === true && (filter_var($_SESSION['nextURL'], FILTER_VALIDATE_URL) === false)?"false":"true")."<BR>";
					echo "value : ". (isset($_SESSION['nextURL']) === true ? $_SESSION['nextURL']: "not set")."<BR>";
					echo "host in nextURL: ".$parse['host']."<BR>";
					echo "current host: " . $CURRENTHOST . "<BR>";
					echo "the script will next try numerous methods to determine the values for the navigational variables.<BR>";						
				}	
				$_SESSION['prevURL'] = ""; //because we can't trust what's in this variable anyway and can use its emptiness as a flag. 
			}//if host of inherited nexturl = currenthost
		}else{ //if inherted nextURL is invalid
			if(${$nameSpaceID."_var"}['debugging']['val'] === true){
				echo "PRIMARY METHOD FAILED: <BR>";	
				echo "\$_SESSION['nextURL'] which was inehreted from the last page visitedcould not be adopted as _SESSION['prevURL'].  Checks on SERVER['nextURL'] found the following results:<BR>";
				echo "Exists: ". array_key_exists('nextURL',$_SESSION)."<BR>";
				echo "Is Set: ". (isset($_SESSION['nextURL']) === true? "true": "false")."<BR>";
				echo "valid : ". (isset($_SESSION['nextURL']) === true && (filter_var($_SESSION['nextURL'], FILTER_VALIDATE_URL) === false)?"false":"true")."<BR>";
				echo "value : ". (isset($_SESSION['nextURL']) === true ? $_SESSION['nextURL']: "not set")."<BR>";
				echo "the script will next try numerous methods to determine the values for the navigational variables.";						
			}	
			$_SESSION['prevURL'] = ""; //because we can't trust what's in this variable anyway and can use its emptiness as a flag. 
		}//if inhereted nextURL is valid
		

		
		if(${$nameSpaceID."_var"}['debugging']['val'] === true){
			echo "<h3>END OF 'TRY TO USE SESSION-BASED NAVIGATION VARS' STEP</h3></div>";
		}
		
		
		
		if($_SESSION['prevURL'] === ""){ //primary navigational method failed, so prevurl was wiped or was already blank...

			if(${$nameSpaceID."_var"}['debugging']['val'] == true){ //try using HTTP_REFERER
				echo "<div class=\"debugDiv\"><h3>ALTERNATIVE METHOD 1: TRY TO USE \$_SERVER['HTTP_REFERER']</h3>can the referring URL be determined from \$_SERVER['HTTP_REFERER'] with sufficient confidence?:";
				echo "If this page's headers include an \$_SERVER['HTTP_REFERER'] value at all, this script won't accept it at face value without validation. A common spoofing scam is to replace it.<BR><BR>";
			}
			if(array_key_exists('HTTP_REFERER',$_SERVER) === true && isset($_SERVER['HTTP_REFERER']) === true && $_SERVER['HTTP_REFERER'] != "" && filter_var($_SERVER['HTTP_REFERER'], FILTER_VALIDATE_URL) != false ){
				$parse = parse_url($_SERVER['HTTP_REFERER']);
				if($parse['host'] === $CURRENTHOST){
					$_SESSION['prevURL'] = $_SERVER['HTTP_REFERER'];
					
					//drop error and sr from prevURL if they are present before using it as nextURL
					$_SESSION['nextURL'] =  remove_qs_key(remove_qs_key($_SESSION['prevURL'], 'error'),'sr');
					
					if(${$nameSpaceID."_var"}['debugging']['val'] == true){
						echo ">SUCCESS!:  \$_SERVER['HTTP_REFERER'] is a valid URL and the host domain in \$_SERVER['HTTP_REFERER'] is the same as the host domain of this script<BR>";
						echo "this reassures that it is not being spoofed. This gave enough confidence to accept \$_SERVER['HTTP_REFERER'] as the true referring url and to store the url in \$_SESSION['prevURL']";
						echo "<h3>ALTERNATIVE METHOD 1- SUCCESS!:</h3></div>";
					}
					
				}else{
					if(${$nameSpaceID."_var"}['debugging']['val'] === true){
						echo ">Fail!:  any url in \$_SERVER['HTTP_REFERER'] couldn't be accepted confidently as the true referring (previous) URL.
						because its host domain is different to the host domain for this script.  host domain for \$_SERVER['HTTP_REFERER']: ". $parse['host'];
						echo "<h3>ALTERNATIVE METHOD 1- FAIL!</h3></div>";
					}
				}
			}else{
				if(${$nameSpaceID."_var"}['debugging']['val'] === true){
					echo "Fail! any url in \$_SERVER['HTTP_REFERER'] couldn't be accepted confidently as being the referring (previous) URL. This was determined from the following checks:<BR>";
					echo "\$_SERVER['HTTP_REFERER'] exists? : ";
					echo array_key_exists('HTTP_REFERER',$_SERVER)? "true":"false"."<BR>";
					echo "\$_SERVER['HTTP_REFERER'] is set?: ". (isset($_SERVER['HTTP_REFERER'])=== true?"true":"false")."<BR>";
					echo "\$_SERVER['HTTP_REFERER']  is valid? : ". ((isset($_SERVER['HTTP_REFERER']) === true && filter_var($_SERVER['HTTP_REFERER'], FILTER_VALIDATE_URL) === false)?"false":"true")."<BR>";
					echo "\$_SERVER['HTTP_REFERER'] value: ". (isset($_SERVER['HTTP_REFERER']) === true ? $_SERVER['HTTP_REFERER'] : "not set")."<BR>";
					echo "<h3>ALTERNATIVE METHOD 1- FAIL!</h3></div>";
				}
			} //end of method 1 - use HTTP_REFERER 	
			
		
			//if method 1 failed then prevURL is still unset. //method 2 is check if im in a file named 'process_xxx.php'
			//and see if i could hand back to xxx.php
			if($_SESSION['prevURL'] === ""){
				
				if(${$nameSpaceID."_var"}['debugging']['val'] === true){
					echo "<div class=\"debugDiv\"><h3>ALTERNATIVE METHOD 2 - IS THIS A 'process_xxx.php' file and does a corresponding xxxx.php file exist?</h3>";
					echo "this file: ". substr(__FILE__,strlen(realpath(dirname(__FILE__)))+1)."<BR>";										
				}
				
				//if //if the current files' name is more than 8 chars long
				if(strlen(substr(__FILE__,strlen(realpath(dirname(__FILE__)))+1))> 9){
					if(${$nameSpaceID."_var"}['debugging']['val'] === true){
						echo "file name is long enough to potentially be a 'process_xxx.php' file<BR>";
					}
					if(strtoupper(substr(__FILE__,strlen(realpath(dirname(__FILE__)))+1,8)) === "PROCESS_"){
						if(${$nameSpaceID."_var"}['debugging']['val'] === true){
							echo "file name starts with 'process_' so this is a 'process_xxx.php' file<BR>";
						}
						if(file_exists(substr(__FILE__,strlen(realpath(dirname(__FILE__)))+9)) === true){
							//then as the next url, use the file with the filename the same as this filename's without the 'process_' prefix
							$_SESSION['prevURL'] = substr(__FILE__,strlen(realpath(dirname(__FILE__)))+9);
							$_SESSION['nextURL'] = $_SESSION['prevURL'];
							
							if(${$nameSpaceID."_var"}['debugging']['val'] === true){
								echo "a corresponding 'xxx.php' file exists<BR>";
								echo "corresponding file is called: ". substr(__FILE__,strlen(realpath(dirname(__FILE__)))+9)."<BR>";

								echo "\$_SESSION['nextURL'] = ".$_SESSION['nextURL'];
								echo "<h3>ALTERNATIVE METHOD 2 - SUCCESS!</h3></div>";
							}

						}else{
							if(${$nameSpaceID."_var"}['debugging']['val'] === true){
								echo "a corresponding 'xxx.php' file does not exist<BR>";
								echo "<h3>ALTERNATIVE METHOD 2 - FAIL!</h3></div>";
							}
						}
						
					}else{
						if(${$nameSpaceID."_var"}['debugging']['val'] === true){
							echo "file name doesn't start with 'process_' so this isnt a 'process_xxx.php' file<BR>";
							echo "<h3>ALTERNATIVE METHOD 2 - FAIL!</h3></div>";

						}
					}
				}else{
					if(${$nameSpaceID."_var"}['debugging']['val'] === true){
						echo "file name is not long enough to potentially be a 'process_xxx.php' file<BR>";
						echo "<h3>ALTERNATIVE METHOD 2 - FAIL!</h3></div>";
					}
				}	
			}
			
			//if method 2 failed then prevURL is still unset. //method 3 is check database-derived app constants
			if($_SESSION['prevURL'] === ""){
				if(${$nameSpaceID."_var"}['debugging']['val'] === true){
					echo "<div class=\"debugDiv\"><h3>ALTERNATIVE METHOD 3 - TRY TO USE APPSTORE DATABASE-DERIVED CONSTANTS</h3>";
				}	
		
				
					//method 3 is check database-derived app constants
					//not done! - check that the values below (eg ['appStore_link']['val']['protocol']) exist and arent blank
				if(
					array_key_exists('appStore_link',${$nameSpaceID."_var"}) === true &&
					array_key_exists('val',${$nameSpaceID."_var"}['appStore_link']) &&
					array_key_exists('protocol',${$nameSpaceID."_var"}['appStore_link']['val']) &&
					array_key_exists('domain',${$nameSpaceID."_var"}['appStore_link']['val']) &&
					array_key_exists('documentRoot',${$nameSpaceID."_var"}['appStore_link']['val']) &&
					array_key_exists('homePage',${$nameSpaceID."_var"}['appStore_link']['val']) &&
					filter_var( 
						${$nameSpaceID."_var"}['appStore_link']['val']['protocol'] .
						${$nameSpaceID."_var"}['appStore_link']['val']['domain'] . "/".
						${$nameSpaceID."_var"}['appStore_link']['val']['documentRoot'] . "/".
						${$nameSpaceID."_var"}['appStore_link']['val']['homePage'],
					FILTER_VALIDATE_URL
					)!= false
				){
					$_SESSION['prevURL'] = 
					${$nameSpaceID."_var"}['appStore_link']['val']['protocol'] . 
					${$nameSpaceID."_var"}['appStore_link']['val']['domain'] . "/".
					${$nameSpaceID."_var"}['appStore_link']['val']['documentRoot'] . "/".
					${$nameSpaceID."_var"}['appStore_link']['val']['homePage'];
					$_SESSION['nextURL'] = $_SESSION['prevURL'];
					if(${$nameSpaceID."_var"}['debugging']['val'] === true){
						echo "\$_SESSION['nextURL'] = ".$_SESSION['nextURL'];
						echo "<h3>ALTERNATIVE METHOD 3 - SUCCESS!</h3></div>";
					}
					
					
				}else{
					if(${$nameSpaceID."_var"}['debugging']['val'] === true){
						echo "Fail:  when attempting to retrieve and combine appStore database-derived constants for this app into a valid URL, a url could not be constructed. The values they returned were: <BR>";
						
						//to-do:  remark on whether the appstore database could be connected to.
						
						echo "<table border=1>
						<thead><th>variable name<th>exists?<th>value
						<tbody>
						<tr><td>\${\$nameSpaceID_var}['appStore_link']<td>";
						echo array_key_exists('appStore_link',${$nameSpaceID."_var"})?"true<td>N/A":"false<td>N/A";
						echo "<tr><td>\${\$nameSpaceID_var}['appStore_link']['val']<td>";
						echo array_key_exists('val',${$nameSpaceID."_var"}['appStore_link'])?"true<td>N/A":"false<td>N/A";						
						echo "<tr><td>\${\$nameSpaceID_var}['appStore_link']['val']['protocol']<td>";
						echo array_key_exists('protocol',${$nameSpaceID."_var"}['appStore_link']['val'])? "true <td>".${$nameSpaceID."_var"}['appStore_link']['val']['protocol']: "false<td>";
						echo "<tr><td>\${\$nameSpaceID_var}['appStore_link']['val']['domain']<td>";
						echo array_key_exists('domain',${$nameSpaceID."_var"}['appStore_link']['val'])? "true <td>".${$nameSpaceID."_var"}['appStore_link']['val']['domain']: "false<td>";
						echo "<tr><td>\${\$nameSpaceID_var}['appStore_link']['val']['documentRoot']<td>";
						echo array_key_exists('documentRoot',${$nameSpaceID."_var"}['appStore_link']['val'])? "true <td>".${$nameSpaceID."_var"}['appStore_link']['val']['documentRoot']: "false<td>";	
						echo "<tr><td>\${\$nameSpaceID_var}['appStore_link']['val']['homePage']<td>";
						echo array_key_exists('homePage',${$nameSpaceID."_var"}['appStore_link']['val'])? "true <td>".${$nameSpaceID."_var"}['appStore_link']['val']['homePage']: "false<td>";
						echo "</table><h3>ALTERNATIVE METHOD 3 - FAIL</h3></div>";
					}
				}
			}
			
			//to-do could and probably should put an extra method in here beween 2 above and 3 below to try for the currrent script's folder IF it is not the same as the documentRoot
			
					
			if($_SESSION['prevURL'] === ""){ //if method 1 and 2 failed then prevURL is still unset. //method 3 is try for the app's root folder based on documentRoot

				if(${$nameSpaceID."_var"}['debugging']['val'] === true){
					echo "<div class=\"debugDiv\"><h3>ALTERNATIVE METHOD 4 - TRY TO USE APP-SPECIFIC TOP LEVEL FOLDER AT SERVER DOCUMENT ROOT</h3>";	
				}
	
				if(
				array_key_exists('appStore_link', ${$nameSpaceID."_var"}) &&
				array_key_exists('val', ${$nameSpaceID."_var"}['appStore_link']) &&
				array_key_exists('documentRoot', ${$nameSpaceID."_var"}['appStore_link']['val']) &&
				filter_var('http'. ((array_key_exists('HTTPS',$_SERVER) === true && isset($_SERVER['HTTPS']) === true && strtolower($_SERVER['HTTPS']) === "on" )?"s":""). '://'. $CURRENTHOST. ((array_key_exists('SERVER_PORT',$_SERVER) === true && isset($_SERVER['SERVER_PORT']) === true && $_SERVER['SERVER_PORT'] != "" && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) ? ":" . $_SERVER['SERVER_PORT'] :"")."/".${$nameSpaceID."_var"}['appStore_link']['val']['documentRoot']."/", FILTER_VALIDATE_URL) != false){
						
					$_SESSION['prevURL'] = 'http'. ((array_key_exists('HTTPS',$_SERVER) === true && isset($_SERVER['HTTPS']) === true && strtolower($_SERVER['HTTPS']) === "on" )?"s":""). '://'. $CURRENTHOST. ((array_key_exists('SERVER_PORT',$_SERVER) === true && isset($_SERVER['SERVER_PORT']) === true && $_SERVER['SERVER_PORT'] != "" && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) ? ":" . $_SERVER['SERVER_PORT'] :"")."/".${$nameSpaceID."_var"}['appStore_link']['val']['documentRoot']."/";
					$_SESSION['nextURL'] = $_SESSION['prevURL'];
					
					if(${$nameSpaceID."_var"}['debugging']['val'] === true){
						
						echo 'http'. ((array_key_exists('HTTPS',$_SERVER) === true && isset($_SERVER['HTTPS']) === true && strtolower($_SERVER['HTTPS']) === "on" )?"s":""). '://'. $CURRENTHOST. ((array_key_exists('SERVER_PORT',$_SERVER) === true && isset($_SERVER['SERVER_PORT']) === true && $_SERVER['SERVER_PORT'] != "" && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) ? ":" . $_SERVER['SERVER_PORT'] :"")."/".${$nameSpaceID."_var"}['appStore_link']['val']['documentRoot']."/";
						
						echo "<h3>ALTERNATIVE METHOD 4 - SUCCESS (muted success, this is a fallback position)</h3></div>";
					}	
				}else{
					if(${$nameSpaceID."_var"}['debugging']['val'] === true){
						echo "failed to make a valid url appending the '\${\$nameSpaceID_var}['appStore_link']['val']['documentRoot']' value to the currrent host server's root URL "; 
						
						echo 'http'. ((array_key_exists('HTTPS',$_SERVER) === true && isset($_SERVER['HTTPS']) === true && strtolower($_SERVER['HTTPS']) === "on" )?"s":""). '://'. $CURRENTHOST. ((array_key_exists('SERVER_PORT',$_SERVER) === true && isset($_SERVER['SERVER_PORT']) === true && $_SERVER['SERVER_PORT'] != "" && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) ? ":" . $_SERVER['SERVER_PORT'] :"")."/".${$nameSpaceID."_var"}['appStore_link']['val']['documentRoot']."/";
						echo "<pre>". var_dump(	${$nameSpaceID."_var"}['appStore_link']['val']['documentRoot'])."</pre>";					
						echo "<h3>ALTERNATIVE METHOD 4 - FAIL</h3></div>";
					}	
				}
				
			}
			
			
			if($_SESSION['prevURL'] === ""){ //if methods 1,2 and 3 failed then prevURL is still unset. //method 4 is try for the current webserver's root folder (likely incorrect).
				if(${$nameSpaceID."_var"}['debugging']['val'] === true){
					echo "<div class=\"debugDiv\"><h3>ALTERNATIVE METHOD 5 - USE THE URL FOR THE CURRENT SERVER'S DOCUMENT ROOT(likely to be incorrect)</h3>";
				}
				
				if(filter_var('http'. ((array_key_exists('HTTPS',$_SERVER) === true && isset($_SERVER['HTTPS']) === true && strtolower($_SERVER['HTTPS']) === "on" )?"s":""). '://'. $CURRENTHOST. ((array_key_exists('SERVER_PORT',$_SERVER) === true && isset($_SERVER['SERVER_PORT']) === true && $_SERVER['SERVER_PORT'] != "" && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) ? ":" . $_SERVER['SERVER_PORT'] :"")."/", FILTER_VALIDATE_URL) != false){
				//if current host + /  is a valid URL then...		
					$_SESSION['prevURL'] = 'http'. ((array_key_exists('HTTPS',$_SERVER) === true && isset($_SERVER['HTTPS']) === true && strtolower($_SERVER['HTTPS']) === "on" )?"s":""). '://'. $CURRENTHOST. ((array_key_exists('SERVER_PORT',$_SERVER) === true && isset($_SERVER['SERVER_PORT']) === true && $_SERVER['SERVER_PORT'] != "" && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) ? ":" . $_SERVER['SERVER_PORT'] :"")."/";
					$_SESSION['nextURL'] = $_SESSION['prevURL'];
						
					if(${$nameSpaceID."_var"}['debugging']['val'] === true){
						echo 'http'. ((array_key_exists('HTTPS',$_SERVER) === true && isset($_SERVER['HTTPS']) === true && strtolower($_SERVER['HTTPS']) === "on" )?"s":""). '://'. $CURRENTHOST. ((array_key_exists('SERVER_PORT',$_SERVER) === true && isset($_SERVER['SERVER_PORT']) === true && $_SERVER['SERVER_PORT'] != "" && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) ? ":" . $_SERVER['SERVER_PORT'] :"")."/";
						echo "<h3>ALTERNATIVE METHOD 5 - SUCCESS (muted success, this is a final fallback position. coder must seek to improve on this!)</h3></div>";
					}	
				}else{
					if(${$nameSpaceID."_var"}['debugging']['val'] === true){
						echo "failed to make a valid url by using the top level url of the current host."; 						
						echo 'http'. ((array_key_exists('HTTPS',$_SERVER) === true && isset($_SERVER['HTTPS']) === true && strtolower($_SERVER['HTTPS']) === "on" )?"s":""). '://'. $CURRENTHOST. ((array_key_exists('SERVER_PORT',$_SERVER) === true && isset($_SERVER['SERVER_PORT']) === true && $_SERVER['SERVER_PORT'] != "" && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) ? ":" . $_SERVER['SERVER_PORT'] :"")."/";
						echo "<h3>ALTERNATIVE METHOD 5 - FAIL</h3></div>";
					}	
				}
			}
			
		}//primary session navigation variables method of nagivation failed. All alternative methods are wrapped in the IF which ends here		
			
			

			
		if(${$nameSpaceID."_var"}['debugging']['val'] === true){
			echo "<div class=\"debugDiv\"><H3>Final navigational SESSION Variables are:</H3>";
		}
		//update thisURL which was inhereted from previous page so that it is equal to currentURL;
		if (filter_var($CURRENTURL, FILTER_VALIDATE_URL) != false){
			$_SESSION['thisURL'] = $CURRENTURL;
		}
	
		if(${$nameSpaceID."_var"}['debugging']['val'] === true){
			echo "\$_SESSION['prevURL'] :".$_SESSION['prevURL']."<BR>"; 
			echo "\$_SESSION['thisURL'] :".$_SESSION['thisURL']."<BR>";
			echo "\$_SESSION['nextURL'] :".$_SESSION['nextURL']."<BR>";
			echo "<H3></H3></div>";
		}
			

	}else{ //if outputmode != JSON...
		//outputmode = JSON
		$i = 0;
		$e = -1;
		$JSONObj = json_decode('{}');

		if(${$nameSpaceID."_var"}['debugging']['val'] == true){
			echo "The variable \${\$nameSpaceID_var}['outputMode']['val'] contains the value 'JSON' which determines that this script should output
			a JSON object. so instead of preparing to redirect to another url after this script has run, a JSON object has been established to contain
			any upcoming output from this script";
		}
	}
	
	
	
	if(${$nameSpaceID."_var"}['debugging']['val'] == true){
		echo "<h3>END OF 'PREPARE EITHER A QUERY-STRING-LOADED URL OR A JSON OBJECT TO ACT AS THE OUTPUT FOR THIS SCRIPT' STEP</h3></div>";
	}

	if(${$nameSpaceID."_var"}['debugging']['val'] == true){
		echo '<div class="debugDiv"><h3>TEMPLATE STEP 20 - CONTAINER FOR CODER\'S APP-SPECIFIC CODE</h3>';
		echo '<details><summary>more...</summary><pre>'; trigger_error("this warning is thrown for the sole purpose of letting you know around which line of the template's code the coder should insert their own code" , E_USER_WARNING); echo '</pre></details>';
	}	
	
	//assess whether there have been any errors encountered in the template script's actions, or if there are any errors with the values received by this script as arguments
	//in case of either type of error, the coder's code will not be run.  The coder must not remove this safeguard but must fix the underlying issues.
	
	//first part of assessing if there have been any errors...
	//set a flag (to be used in the following IF ) to say whether any of the variables have variable-specific errors registered against them.
	${$nameSpaceID."_var"}['rKey']['tmpVal'] = ""; //will use this as a transitory flag that this foreach loop identified an input error in any input
	foreach(array_keys(${$nameSpaceID."_var"}) as ${$nameSpaceID."_var"}['rKey']['val']){
		if(array_key_exists('varSpecificErrs', ${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]) && 
		count(${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]['varSpecificErrs']) > 0){
			${$nameSpaceID."_var"}['rKey']['tmpVal'] .= ${$nameSpaceID."_var"}['rKey']['val']." "; //set the transitory flag
			break;
		}
	}
	//second part of assessing if there have been any errors...
	//this IF uses the transitory flag from the first part of the assessment of whether there are any errors. the flag contains a boolean true if any of the script's declared variables have a variable specific error (a problem with the value held in the variable)
	//or if the parts of the script which make up its template have encountered an error (for example, if a database connection has failed).
	if(${$nameSpaceID."_var"}['error']['val'] === ""  && ${$nameSpaceID."_var"}['rKey']['tmpVal'] == ""){ //if there has been no error in the inputs/head section of this script...
		if(${$nameSpaceID."_var"}['debugging']['val'] == true){
			echo "no errors were encountered in the templated sections of the code which would prevent the coder's app-specific code from running<br>";
		}
		$authenticated = "true";
		if(${$nameSpaceID."_var"}['includeSecurity']['val'] === false || (${$nameSpaceID."_var"}['includeSecurity']['val'] === true && $authenticated === "true")){  
			if(${$nameSpaceID."_var"}['debugging']['val'] == true){
				echo "<div class=\"debugDiv\"><h3>START OF CODER'S APP-SPECIFIC CODE</h3>";
			}
			try {

/*CODER TO INSERT THEIR OWN APP CODE DIRECTLY BELOW THIS COMMENT BLOCK 
  any variables used in the code which the coder inserts below must be declared in the CODER TO DECLARE OWN VARIABLES step of this template (above), using the standard described at the top of that step
  this script will pass back any of the following:
   a) variables which have a errors, which are passed back in this format: name=badvalue¬errorcode1¬errorcode2
   b) varibles with the mustPassOn flag set to boolean true which do not have errors. these are passed back as name=value
   c) the contents of the 'error' variable if it is set. this is passed back as error=errorcode1.  this must not be used to carry errors related to specific inputs/arguments. those should be stored in the variable corresponding to the argument -for example, an argument called 'chocolate' would need a corresponging variable called ${$nameSpaceID."_var"}['chocolate']
   d) the contents of the 'sr' variable if it is set. sr is short for server response. this is passed back as sr=srCode1 and it is used to carry a general non-error response like 'your record was successfully saved'. 

   USING MYSQL
    At this point,(assuming there have been no connection errors,) the coder will have the following database connections available:

		the single login database:  ${$nameSpaceID."_var"}['klogin_link']['val']['db_conn']  
				tip: 	the end user's login status is established by seeing if authenticated() == "true". this is a string not a boolean true.
				tip: 	the details of any logged in end user are stored in the \$_SESSION
				  
		the appstore database:     ${$nameSpaceID."_var"}['appStore_link']['val']['db_conn']		  
				tip:  	the appstore holds a record for each app. the coder should have created the record already. the record holds info about the app including app constants
						each record (app) has an appID. The coder should have already declated this script's appID in step 3 above 

		this app's database:		${$nameSpaceID."_var"}['app_link']['val']['db_conn']
				tip:	this assumes this app has a database which has been created by/for the coder
				
	The following variables exist and are intended for use in mysqli commands. (if your script needs to juggle two or more sql statements at the same time, create duplicates of the following variables eg. ${$nameSpaceID."_var"}['sql2']['val'])	
		construct any sql command you want to run in	${$nameSpaceID."_var"}['sql']['val']
		return the result into 							${$nameSpaceID."_var"}['result']['val']
		return a single row into						${$nameSpaceID."_var"}['row']['val']
		return all rows (eg mysqli_fetch_all) into 		${$nameSpaceID."_var"}['rows']['val']
		store a count of rows in result in 				${$nameSpaceID."_var"}['rows']['count']
	
	For security, you should use sprintf and mysqli_real_escape_string to construct your sql statements eg: 
		
		//set a template of the statement with variable parts replaced by %s
		${$nameSpaceID."_var"}['sql']['val'] = 'SELECT * from users where id = %s';
	
		//use sprintf and mysqli_real_escape_string to inject the variable value for id into the template statement
		${$nameSpaceID."_var"}['sql']['val'] = 	sprintf(
												${$nameSpaceID."_var"}['sql']['val'],
												mysqli_real_escape_string(
													${$nameSpaceID."_var"}['klogin_link']['val']['db_conn'],
													${$nameSpaceID."_var"}['userID']['val']
												)
											)
											
		//run the statement, retrieving the result
		${$nameSpaceID."_var"}['result']['val'] =	mysqli_query(
														${$nameSpaceID."_var"}['klogin_link']['val']['db_conn'],
														${$nameSpaceID."_var"}['sql']['val']
													);
	
*/


			
${$nameSpaceID."_var"}['sr']['val'] = 1;

				
//END OF  - CODER TO INSERT MAIN PART OF THEIR CODE HERE.  CODER SHOULD HAVE NO REASON TO EDIT CODE BELOW THIS POINT. 		
			} catch (\Throwable $interceptedError) {
				if(${$nameSpaceID."_var"}['debugging']['val'] === true){ 
					if(${$nameSpaceID."_var"}['outputMode']['val'] == "JSON"){
						//add the error to the JSON object which acts as output
						$commentary .= $interceptedError;
					}else{
						//show it on screen 
						echo "'try' caught the following error in the coder's code:<BR><PRE>";
						var_dump($interceptedError);	
						echo "</PRE>";
					}
				}else{
					throw $interceptedError;
				}	
			}
		
			if(${$nameSpaceID."_var"}['debugging']['val'] == true){
				echo "<h3>END OF CODER'S APP-SPECIFIC CODE</h3></DIV>";
			}
	
		}else{ //else authenticated() != true
			//authenticated() is not true so set an error
			${$nameSpaceID."_var"}['error']['val'] = 1; //you need to be logged in to do that.
			
			if(${$nameSpaceID."_var"}['debugging']['val'] == true){
				echo "CODER'S APP-SPECIFIC CODE HAS NOT BEEN ATTEMPTED AS THE TEMPLATE'S LOGIN FEATURE HAS BEEN ACTIVATED ";
				echo "BUT AUTHENTICATION OF THE CURRENT USER FAILED";			
			}
		
		} //end of if authenticated() === true
	}else{	//end of if no error in inputs / head section of this script
	
		if(${$nameSpaceID."_var"}['debugging']['val'] == true){
			echo "<H4>CODER'S APP-SPECIFIC CODE HAS NOT BEEN ATTEMPTED BECAUSE...</H4><br>";
			if(${$nameSpaceID."_var"}['error']['val'] <> ""){
				echo "the following script-level error in the template section of the code needs to be resovled first: " .${$nameSpaceID."_var"}['error']['val'] . "<BR><BR>";
			}
			
			if(${$nameSpaceID."_var"}['rKey']['tmpVal'] <>  "" ){
				echo "the following variables, set before the coder's app-specific code have errors:" . ${$nameSpaceID."_var"}['rKey']['tmpVal'];
			}
		}
	}
	${$nameSpaceID."_var"}['rKey']['tmpVal'] = ""; //clear the transitory flag
	
	
	if(${$nameSpaceID."_var"}['debugging']['val'] == true){
		echo "<h3>END OF TEMPLATE CONTAINER FOR CODER'S APP-SPECIFIC CODE</h3></DIV>";
	}
	


	if(${$nameSpaceID."_var"}['outputMode']['val'] == "JSON"){
		
		$JSONObj->st = $serverTime;
		$JSONObj->s = ${$nameSpaceID."_var"}['rowCount2']['val'];
		$JSONObj->err = ${$nameSpaceID."_var"}['error']['val'];
		$commentary .=" [SQL ERROR] ";
		$commentary .= str_replace("\\'","'",addslashes(ob_get_clean()));//slashes in a way that i can store any of PHP's self-generated error HTML output in JSON !!! should really convert non-utf8 chars also!
		
		
		//put this script's output buffer into the JSON object to pick up stray output
		if(${$nameSpaceID."_var"}['debugging']['val'] === true){
			$JSONObj->commentary = $commentary  . ob_get_clean();
		}

		if(${$nameSpaceID."_var"}['error']['val'] !=""){
			$JSONObj->err = ${$nameSpaceID."_var"}['error']['val'];		
		}
		echo json_encode($JSONObj);
		if(isset($prevNameSpaceID) === true && $prevNameSpaceID != ""){
			 $nameSpaceID = $prevNameSpaceID;
		}
		die(); //echo the JSON then shut up. JSON document ends here. SHOULD NOT DIE IF CURRENT DOC IS AN INCLUCDE	
	}elseif(${$nameSpaceID."_var"}['outputMode']['val'] == "STREAM"){
		//not currently coded
	}elseif(${$nameSpaceID."_var"}['outputMode']['val'] == "NONE"){
		//not currently coded but shouldn't need any coding. just do nothing and exit.
	}else{
	
		if(${$nameSpaceID."_var"}['debugging']['val'] == true){
			echo '<div class="debugDiv"><h3>TEMPLATE STEP 21 - attempt to add any script-level non-user-input error to \$_SESSION[\'nextURL\']</h3>';
		}
		//if there's been any error, return it. also return all input values and errors associated with them as
		//the front end im returning to probably is a data capture form so it can be set back up in the state the
		//user submitted it in.
		if(${$nameSpaceID."_var"}['error']['val'] == ""){
			if(${$nameSpaceID."_var"}['debugging']['val'] == true){
				echo "no script-level errors to add<BR>";
			}
		}else{

			if(${$nameSpaceID."_var"}['debugging']['val'] == true){
				echo "a script level error was encountered earlier, adding a mention of it to _SESSION['nextURL']<BR>";
			}
			
			//add the error to the query string of the url the script redirects to when finished
			$_SESSION['nextURL'] = replace_qs_key($_SESSION['nextURL'], "error", "error=" .${$nameSpaceID."_var"}['error']['val']);
		}
		if(${$nameSpaceID."_var"}['debugging']['val'] == true){
			echo "\$_SESSION['nextURL'] now contains: ". $_SESSION['nextURL']."<BR>";
			echo "this may not be the final value for \$_SESSION['nextURL']<BR>";
		}
		
		if(${$nameSpaceID."_var"}['debugging']['val'] == true){
			echo "<h3>END OF attempt to add any script-level non-user-input error to _SESSION['nextURL']</h3></div>";
		}
		
		if(${$nameSpaceID."_var"}['debugging']['val'] == true){
			echo '<div class="debugDiv"><h3>TEMPLATE STEP 22 - attempt to add any server response code to \$_SESSION[\'nextURL\']</h3>';
		}
		//if there's been any error, return it. also return all input values and errors associated with them as
		//the front end im returning to probably is a data capture form so it can be set back up in the state the
		//user submitted it in.
		if(${$nameSpaceID."_var"}['sr']['val'] == ""){
			if(${$nameSpaceID."_var"}['debugging']['val'] == true){
				echo "no server response code to add<BR>";
			}
		}else{

			if(${$nameSpaceID."_var"}['debugging']['val'] == true){
				echo "a server response code was set. Adding a mention of it to _SESSION['nextURL']<BR>";
			}
			
			//add the error to the query string of the url the script redirects to when finished
			$_SESSION['nextURL'] = replace_qs_key($_SESSION['nextURL'], "sr", "sr=" .${$nameSpaceID."_var"}['sr']['val']);
		}
		if(${$nameSpaceID."_var"}['debugging']['val'] == true){
			echo "\$_SESSION['nextURL'] now contains: ". $_SESSION['nextURL']."<BR>";
			echo "this may not be the final value for \$_SESSION['nextURL']<BR>";
		}
		
		if(${$nameSpaceID."_var"}['debugging']['val'] == true){
			echo "<h3>END OF attempt to add any server response code to \$_SESSION['nextURL']</h3></div>";
		}
		
		
		
		if(${$nameSpaceID."_var"}['debugging']['val'] == true){
		echo '<div class="debugDiv"><h3>TEMPLATE STEP 23 - Attempting to prepare to return any user-inputs or other return values which have variable specific errors by adding them into the query string being constructed in  \$_SESSION[\'nextURL\']</h3><BR>
		<TABLE BORDER=1><THEAD><TH>variable name<TH>returnable? (argType >= 0)<TH>has errors?<TBODY>';
		}
	
		//for every script variable
		foreach(array_keys(${$nameSpaceID."_var"}) as ${$nameSpaceID."_var"}['rKey']['val']){
				
			//if the varibale is an argument provided by the user or potentially provided by the user...
			//if(array_key_exists("argType",${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]) && ${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]['argType'] >=0){
			
			if(${$nameSpaceID."_var"}['debugging']['val'] == true){
				echo "<TR><TD>".${$nameSpaceID."_var"}['rKey']['val'];
			}
			if(${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]['argType'] >=0){
				
				//add it as a key-value pair to the querystring of the url i redirect the user to (which is likely where they came from)
				//and using a '¬' separated list, append any error codes to the value of the key-value pair so that the receiving page can 
				//pick them apart. the ¬ symbol was chosen as a separator because it has no traditional use in mainstream programming and 
				//is highly unlikely to be present within the body of a value whereas the more common comma separator is quite likely to be
				//present in string values which would then confuse processing between what is part of the value and what is a separator.
				//the '¬' symbol has been used occasionally in short-hand for probability notation but this is probably (lol) likely to be
				//a universe distinct enough from programming to cause any clashes. 
				
				//example output would be:
				//myFieldName=myErroneousValue¬111¬202
				
				if(${$nameSpaceID."_var"}['debugging']['val'] == true){
				echo "<TD>YES";
				}
				
				//if there were any input errors registered against the argument...im going to store them in the variables's tmpVal space
				${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]['tmpVal'] = 
				is_array(${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]['val']) === true ?
					implode(";",
					${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]['val']
					)
					: 
					${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]['val'];
				
				
				if(count(${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]['varSpecificErrs']) > 0){
					
					//list them in a '¬' separated list stored in the variable's tmpVal space
					${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]['tmpVal'] .= "¬".
					
					
					implode("¬",${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]['varSpecificErrs']);
					
					if(${$nameSpaceID."_var"}['debugging']['val'] == true){
						echo "<TD>YES ". ${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]['tmpVal'];
					}					
				
					//as there was at least one error, (this is known because the error variable is not ""), edit the querystring of the url to which this script will redirect out to, to contain the name-value pair of all the arguments the script should have received, carrying any input error codes with it. The script is, at this point, already looping through those arguments.
					$_SESSION['nextURL'] = replace_qs_key(
						$_SESSION['nextURL'], 
						${$nameSpaceID."_var"}['rKey']['val'], 
						rawurlencode(${$nameSpaceID."_var"}['rKey']['val']) ."=".rawurlencode(${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]['DIRTY']).${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]['tmpVal']
					);
				
				}
				

			} //end of if this argument, the name of which is stored in ${$nameSpaceID."_var"}['rKey']['val'], is one the script should have received, (which is indicated by 
			  //the variable's argType being zero or greater)...
		}//end of loop thru every variable 
		
		if(${$nameSpaceID."_var"}['debugging']['val'] == true){
			echo "</TABLE>";
			echo "<h3>END OF Attempting to prepare to return any user-inputs or other return values which have variable specific errors by adding them into the query string being constructed in  _SESSION['nextURL']</h3></div>";
		}
	
	

	//having assessed each variable to see if it has an error returned, assess the 'mustPassOn' space of each variable and add to the querystring of the url which this script redirects to any variables which are mandated to be passed back (where they havent already been passed back above as error-laden)
	if(${$nameSpaceID."_var"}['debugging']['val'] == true){
		echo '<div class="debugDiv"><h3>TEMPLATE STEP 24 - Attempting to add mustPassOn variables into \$_SESSION[\'nextURL\']</h3>';
		echo "mustPassOn variables are variables which are flagged as mandatory for this script to output as a key-value-pair in its response<br>";
		echo "a variable is flagged as 'mustPassOn' by setting its 'mustPassOn' attribte to true eg. \${\$nameSpaceID.'_var'}['debugging']['mustPassOn'] = true;<br>";
		echo "this is commonly set when the variable is declared in either of the following sections of this script (" .__FILE__."):<br>";
		echo "TEMPLATE VARIABLES ARE DECLARED<BR>";
		echo "CODER TO DECLARE THEIR OWN VARIABLES HERE<BR><BR>";
		echo "variables with variable-specific errors will be skipped here since in the previous step they've already been prepared to be returned<BR>";
	}
	foreach(array_keys(${$nameSpaceID."_var"}) as ${$nameSpaceID."_var"}['rKey']['val']){
		if(array_key_exists('mustPassOn', ${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]) && 
		array_key_exists('argType', ${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]) && 
		array_key_exists('varSpecificErrs', ${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]) && 
		${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]['mustPassOn'] === true &&
		${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]['argType'] >= 0 &&
		count(${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]['varSpecificErrs']) == 0){
			//replace_qs_key(url,old_key,new_key=value)  value is converted to a semi-colon-delimited string if it is an array	
			//alternative might be to convert it to fieldname= delmited per standard for multi-select html5 input boxes

			$_SESSION['nextURL'] = replace_qs_key(
				$_SESSION['nextURL'], 
				${$nameSpaceID."_var"}['rKey']['val'], 
				rawurlencode(${$nameSpaceID."_var"}['rKey']['val']) ."=". (is_array(${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]['val']) === true ? implode(";",${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]['val']) : rawurlencode(${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]['val']))
			);

			if(${$nameSpaceID."_var"}['debugging']['val'] == true){
				echo rawurlencode(${$nameSpaceID."_var"}['rKey']['val']) ."=". (is_array(${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]['val']) === true ? implode(";",${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]['val']) : rawurlencode(${$nameSpaceID."_var"}[${$nameSpaceID."_var"}['rKey']['val']]['val'])). " addded into \$_SESSION['nextURL']<br>";
			}
		}//end of if this is a 'mustpasson' variable without an error
	} //end of foreach loop thru every variable
	
	if(${$nameSpaceID."_var"}['debugging']['val'] == true){
		echo "<h3>END OF Attempting to add mustPassOn variables into _SESSION['nextURL']</h3></div>";
	}
	
	
	//ensuring i don't overwrite anything already added to the URL, which would be a security violation in these circumstances, cycle through the passthru (dirty) 
	//variables and add them to the querystring of the url which this script redirects to any variables which are mandated to be passed back (where they havent
	//already been passed back.
	if(${$nameSpaceID."_var"}['debugging']['val'] == true){
		echo '<div class="debugDiv"><h3>TEMPLATE STEP 25 - Attempting to add PASSTHRU values into \$_SESSION[\'nextURL\']</h3>';
		echo "PASSTHRU values are key-value-pair arguments which this script received, for which there is no corresponding variable declared in either the<BR>";
		echo "TEMPLATE VARIABLES ARE DECLARED<BR>CODER TO DECLARE THEIR OWN VARIABLES HERE<BR>parts of the template.  They were identified as passthru values by the absence of a corresponding variable. The assumption is that while this script doesnt have any use for the arguments, there may be something elsewhere after this script has run which requires the values, so they're just passed through this script and returned with the response. They were identified as passthru values earlier in the script when all arguments were assessed.";
	}
	$url_components = parse_url($_SESSION['nextURL']);
	parse_str($url_components['query'], $params);
	foreach(array_keys(${$nameSpaceID."_var"}['PASSTHRU_DIRTY']['val']) as ${$nameSpaceID."_var"}['rKey']['val']){
		echo("passthru " . ${$nameSpaceID."_var"}['rKey']['val']. "<BR>"); 
		if(array_key_exists(${$nameSpaceID."_var"}['rKey']['val'],$params) === false){			
			//add_qs_key(url,new_key=value)		
			$_SESSION['nextURL'] = add_qs_key(
				$_SESSION['nextURL'],  
				rawurlencode(${$nameSpaceID."_var"}['rKey']['val']) ."=". rawurlencode(${$nameSpaceID."_var"}['PASSTHRU_DIRTY']['val'][${$nameSpaceID."_var"}['rKey']['val']])
			);		
		}else{
			if(${$nameSpaceID."_var"}['debugging']['val'] == true){
				echo "not adding the dirty value '".${$nameSpaceID."_var"}['rKey']['val']."' from  'PASSTHRU_DIRTY' to the querystring of the next url which this script will redirect to (_SESSION['nextURL']) since this script has independently already added a name-value-pair for '".${$nameSpaceID."_var"}['rKey']['val']."' and that takes precedence<BR>";
			}
		}
	}
	if(${$nameSpaceID."_var"}['debugging']['val'] == true){
		echo "<h3>END OF Attempting to add PASSTHRU values into _SESSION['nextURL']</h3></div>";
	}
	
	
	
	if(${$nameSpaceID."_var"}['debugging']['val'] == true){
		echo ob_get_clean();
		echo '<div class="debugDiv"><h3>TEMPLATE STEP 26 - final part of template. </h3>';
		echo "As the variable \${\$nameSpaceID_var}['debugging']['val'] = true, the debugging mode for this script is enabled. If debugging mode were disabled, the script is now complete
		and the user would be forwarded to the following url. you may simulate that by clicking the link below:<br>";
		echo "<a href='".$_SESSION['nextURL']."'>".urldecode($_SESSION['nextURL'])."</a>";
		if(isset($prevNameSpaceID) === true && $prevNameSpaceID != ""){
			$nameSpaceID = $prevNameSpaceID;
		}
		echo "<h3>END OF final part of template. </h3></div>";
	}else{ //else, if not debugging,
		
		if (str_replace('\\', '/', __FILE__) == $_SERVER['SCRIPT_FILENAME']) {
			//if this code is not being run as an include,		
			if($_SESSION['nextURL'] !== $_SESSION['thisURL']){
				if(isset($prevNameSpaceID) === true && $prevNameSpaceID != ""){
					$nameSpaceID = $prevNameSpaceID;
				}
				header("Location: ".$_SESSION['nextURL']);
				exit();
			}else{
				//make this present the error properly
				echo "infinite loop prevented:<BR>";
				echo "Previous URL: ". $_SESSION['prevURL']."<BR>";
				echo "this URL: ". $_SESSION['thisURL']."<BR>";
				echo "next URL: ". $_SESSION['nextURL']."<BR>";
				echo "auto redirect to next URL prevented because it is the same as current URL";	
			}
		} //end of if this is not being run as an include
	} // end of if debugging
}//end of mode is not = JSON, NONE or STREAM (so default to TEXT)
?>