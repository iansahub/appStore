<?php
$apiUsername = "adam.beirne399@mod.gov.uk";
$apiPassword = "password";
$apiAppID = 70;
$apiRoleID = 171;
$apiReply = getAPIData($apiUsername, $apiPassword, $apiAppID, $apiRoleID);

echo "if you have not edited the php code for ". __FILE__ ." to include your single signon details at the top of the script, this page will return a JSON with an err of 'wrong password' and  an empty data array"."<BR><BR>";
 
echo "The JSON returned from your API responder follows below. To display it here as a string on your screen, it has been passed through the json_encode and json_decode function.
Normally you would not want to pass the JSON through both functions because you dont want to display the result on the screen, but instead you want to use the data in your code."."<BR>";
echo "<pre>";
echo json_decode(json_encode($apiReply));
echo "</pre>";

echo "to just use the data in your code, you likely only want to pass it thorough the json_decode function which leaves you able to dissect the JSON and pick out the bits you want
for example, you could just pick out the 'data' section as follows:"."<BR>";

$apiReply2 = json_decode($apiReply);
$myArray = $apiReply2->data; 
echo "<pre>";
print_r($myArray);
echo "</pre>";



function getAPIData($username, $password, $appID, $roleID){
    //returns JSONObject with info from the SSO server about the account being queried
    //or returns false if the query to the SSO server fails, or can't happen over SSL
    //should realy reverify arguments and return false if not verified.

    $protocol = "https://";                //https:// or http:// to reach the file - in this case the sso verify_api.php file
    $sv = "echo.dasa.r.mil.uk";            //the server name which sso lives on. 
    $urlOfAPI = $protocol.$sv. ($protocol="https://" ?  ":443":  "") . "/phpSample/sampleAPIResponder.php?mode=JSON&username=" . $username . "&password=" . $password . "&appID=" . $appID . "&roleID=" . $roleID;

    //set baseline CURL options in preparation to curl a response out of the server to a request for its 
    $options = array(
        CURLOPT_RETURNTRANSFER => true,   // return web page
        CURLOPT_HEADER         => false,  // don't return headers
        CURLOPT_FOLLOWLOCATION => true,   // follow redirects
        CURLOPT_MAXREDIRS      => 10,     // stop after 10 redirects
        CURLOPT_ENCODING       => "",     // handle compressed
        CURLOPT_USERAGENT      => $sv,       // how the 'client' will identify itself to the SSO server (https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/User-Agent#syntax)
        CURLOPT_AUTOREFERER    => true,   // set referrer on redirect
        CURLOPT_CONNECTTIMEOUT => 120,    // time-out on connect
        CURLOPT_TIMEOUT        => 120,    // time-out on response
    ); //this 'options' variable only exists within the scope of its containing anonymous IIFE(Immediately-invoked function expression) so we can safely overlook the fact that its not stored within the namespace.

    if(does_ssl_exist($urlOfAPI) == true){
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
        $ch = curl_init($urlOfAPI);
        curl_setopt_array($ch, $options);
        $content  = curl_exec($ch); 
        return $content;
    }else{
        return false; //do nothing as the query must not be allowed to happen if not over encrypted SSL 
    }
}


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
?>
