<?php
/************************************************************
PROJECT NAME:  Sample Project
FILE NAME   :  fieldValidationFunctions.php 

FILE DESCRIPTION: 
Functions that validate values provided by user eg. form field inputsinputs

VER:   DATE:     INITIALS:  DESCRIPTION OF CHANGE:
1.0    18/11/20  AB                 Initial Version

Php Version:   5.3.3
Creative Commons License
**************************************************************/

// a function will be named checkXxxxx() where Xxxxx is the name of the variable being checked. This is expected to be the same
// as the name of the input field which would receive the value and, when a value is stored, eg. a database or JSON dataset, the name of 
// the field.  A check function merely checks that a value meets the parameters/requirements  of the required format (eg length/allowed
// characters)

// a function will be named verifyXxxxx() if part of its check is to verify with another source (eg database) that a value exists. 
// typically the first action of a verify function will be to call for a vote of satisfaction from a corresponding checkXxx()


if(!isset($appID)){$appID = 1;} //dont put this in session. apps may be embedded in apps and we might want the parent one



if(isset($nameSpaceID) === true && $nameSpaceID != ""){$prevNameSpaceID = $nameSpaceID;}
$nameSpaceID =  "NS_" . trim(preg_replace("/[^A-Za-z0-9_]/", '',preg_replace("#[/\\\\\.]+#", "_", substr(realpath(__FILE__),strlen($_SERVER["DOCUMENT_ROOT"])))),"\n\r\t\v\0_");

$$nameSpaceID['fn'] = array();
 
$$nameSpaceID['fn']['checkdog'] = function($value){
      return 0;
};


//part of the template. error is used to hold a single numerical error code response from the server which does not 
//relate to a specific field/input but to the overall error response of the script
$$nameSpaceID['fn']['checkerror'] = function($value){
      if(is_numeric($value) && $value > 0){
            return 0;
      }else{
            return 57;
      }
};


//part of the template. sr is used to hold a single numerical error code response from the server which does not 
//relate to a specific field/input but to the overall non-error response of the script. SR may include a decimal
//the front end can use the portion of the number below zero to carry a payload number eg a timestamp from the server
$$nameSpaceID['fn']['checksr'] = function($value){
            if(is_numeric($value) && $value > 0){
                  return 0;
            }else{
                  return 59;
            }
      };

$$nameSpaceID['fn']['invalidEmail'] = function ($email){
      /* Validate an email address.Provide email address (raw input) Returns false if the email address is valid. returns errormessage 
      (which triggers true) if address is invalid. checks address format and checks if domain exists with a mail service running.
      does NOT check whether address is in use (in my database) alreday. if that is needed, use checkEmail() which does both*/

            $commonEmailDomains = array("HOTMAIL.COM","GMAIL.COM");
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
                  if(!in_array(strtoupper($domain),$commonEmailDomains)){
                        if ($retval==false && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))){
                              $retval = 'no_mail_service_on_domain'; // domain not found in DNS
                        }
                  }
                  
            }
            return $retval;
      };


$$nameSpaceID['fn']['checkEmail'] = function($email){
            $error = 0;
            if(!isset($debugging)){
                  $debugging = false;
            }
            if($debugging){print "<br>call to function checkEmail(".$email.")<br>";}
            $ie = invalidEmail($email);
            if($ie==false){         
                  include( $_SERVER['DOCUMENT_ROOT'] ."/klogin/klogin_database.php");
                  $sql = "SELECT email, tempEmail, COALESCE(email = '%s',0) as 'emailVerified' FROM %s.users WHERE email = '%s' OR tempEmail = '%s' LIMIT 1";
                  $sql = sprintf($sql,mysqli_real_escape_string($con,$email), $login_database, mysqli_real_escape_string($login_link,$email), mysqli_real_escape_string($login_link,$email));
                  if($debugging){print "<br>".$sql."<br>";}
                  
                  if($result = mysqli_query($login_link,$sql)){
                        if($row = mysqli_fetch_assoc($result)){
                              if($debugging){
                                    var_dump($row);
                              }
                              
                              if($row['emailVerified'] == "1"){
                                    $error = 11; //email already in use
                              }else{
                                    $error = 12;  //email already in use (unverified)
                              }
                        }else{
                              $error = 13;  //email not in database
                        }     
                  }else{
                        $error = "Query: ".$sql. " didn't execute successfully";
                  }
            }else{      
                    $error = 2; //a valid email address is required
                    if($debugging){print "<br>function validEmail called from checkEmail(".$email.") says ".$ie."<br>";}
            }
            if($debugging){print "<br>function checkEmail(".$email.") returns ".$error."<br>";}
            return $error;
      };
      
      
      


      ////// Analysis Dir SSO Variables used for authentication for API responses//////
      $$nameSpaceID['fn']['checkssoUsername'] = $$nameSpaceID['fn']['invalidEmail'];
            
      
      
      

      $$nameSpaceID['fn']['checkssoPassword'] = function($value){
            //its supposed to be a non blank string. do we have a complexity rule enforced? doubt it.
            $error = 0;
            if($value == ""){
                  $error = 99;
            }
            return $error;
      };

      $$nameSpaceID['fn']['checkssoAppID'] = function($value){
            //its supposed to be a poaitive integer 
            $errorCode = 0;
            $value = (int) $value;
            if(is_int($value) == false || $value <= 0){
                  $errorCode = 99; //invalid project id
            }
            return $errorCode;      
      };

      
      
      $$nameSpaceID['fn']['checkssoRoleID'] = function($value){
            //its supposed to be a poaitive integer 
            $errorCode = 0;
            $value = (int) $value;
            if(is_int($value) == false || $value <= 0){
                  $errorCode = 31; //invalid project id
            }
            return $errorCode;
      };
            
      //////End of Analysis Dir SSO input validation (not verification!) functions//////



$$nameSpaceID['fn']['checkloc'] = function($value){
            
            
            //the loc value should hold a language code from the IETF BCP 47 standard. the following check
            //is not absolute, it simply checks if the value supplied is formatted as if it is a code but doesnt
            //check if the code itself is valid.
            
            //This regex will match language codes that are two to three letters long, optionally followed by up to 
            //three extended language subtags that are two to three letters long, an optional script subtag that is four letters 
            //long or a numeric region subtag that is three digits long, and an optional region subtag that is two letters long or 
            //three digits long.
            
            
            $pattern = "/^[a-zA-Z]{2,3}(?:-[a-zA-Z]{2,3}){0,3}(?:-(?:[a-zA-Z]{4}|\\d{3}))?(?:-(?:[a-zA-Z]{2}|\\d{3}))?$/";
            preg_match($pattern, $value, $matches, PREG_OFFSET_CAPTURE);
            if(count($matches) == 1){
                  $retval = 0;
            }else{
                  $retval = 61;
            }     
            return $retval;
      };          

//does the localtimezone value (to be referred to when presenting dates to the client in their own local timezone) hold a valid timezone name as listed here: https://www.php.net/manual/en/timezones.php
      
$$nameSpaceID['fn']['checkuserLocalTimeZone'] =       function($value){
            $validTimezoneNames = ["Africa/Abidjan","Africa/Accra","Africa/Addis_Ababa","Africa/Algiers","Africa/Asmara","Africa/Bamako","Africa/Bangui","Africa/Banjul","Africa/Bissau","Africa/Blantyre","Africa/Brazzaville","Africa/Bujumbura","Africa/Cairo","Africa/Casablanca","Africa/Ceuta","Africa/Conakry","Africa/Dakar","Africa/Dar_es_Salaam","Africa/Djibouti","Africa/Douala","Africa/El_Aaiun","Africa/Freetown","Africa/Gaborone","Africa/Harare","Africa/Johannesburg","Africa/Juba","Africa/Kampala","Africa/Khartoum","Africa/Kigali","Africa/Kinshasa","Africa/Lagos","Africa/Libreville","Africa/Lome","Africa/Luanda","Africa/Lubumbashi","Africa/Lusaka","Africa/Malabo","Africa/Maputo","Africa/Maseru","Africa/Mbabane","Africa/Mogadishu","Africa/Monrovia","Africa/Nairobi","Africa/Ndjamena","Africa/Niamey","Africa/Nouakchott","Africa/Ouagadougou","Africa/Porto-Novo","Africa/Sao_Tome","Africa/Tripoli","Africa/Tunis","Africa/Windhoek","America/Adak","America/Anchorage","America/Anguilla","America/Antigua","America/Araguaina","America/Argentina/Buenos_Aires","America/Argentina/Catamarca","America/Argentina/Cordoba","America/Argentina/Jujuy","America/Argentina/La_Rioja","America/Argentina/Mendoza","America/Argentina/Rio_Gallegos","America/Argentina/Salta","America/Argentina/San_Juan","America/Argentina/San_Luis","America/Argentina/Tucuman","America/Argentina/Ushuaia","America/Aruba","America/Asuncion","America/Atikokan","America/Bahia","America/Bahia_Banderas","America/Barbados","America/Belem","America/Belize","America/Blanc-Sablon","America/Boa_Vista","America/Bogota","America/Boise","America/Cambridge_Bay","America/Campo_Grande","America/Cancun","America/Caracas","America/Cayenne","America/Cayman","America/Chicago","America/Chihuahua","America/Ciudad_Juarez","America/Costa_Rica","America/Creston","America/Cuiaba","America/Curacao","America/Danmarkshavn","America/Dawson","America/Dawson_Creek","America/Denver","America/Detroit","America/Dominica","America/Edmonton","America/Eirunepe","America/El_Salvador","America/Fort_Nelson","America/Fortaleza","America/Glace_Bay","America/Goose_Bay","America/Grand_Turk","America/Grenada","America/Guadeloupe","America/Guatemala","America/Guayaquil","America/Guyana","America/Halifax","America/Havana","America/Hermosillo","America/Indiana/Indianapolis","America/Indiana/Knox","America/Indiana/Marengo","America/Indiana/Petersburg","America/Indiana/Tell_City","America/Indiana/Vevay","America/Indiana/Vincennes","America/Indiana/Winamac","America/Inuvik","America/Iqaluit","America/Jamaica","America/Juneau","America/Kentucky/Louisville","America/Kentucky/Monticello","America/Kralendijk","America/La_Paz","America/Lima","America/Los_Angeles","America/Lower_Princes","America/Maceio","America/Managua","America/Manaus","America/Marigot","America/Martinique","America/Matamoros","America/Mazatlan","America/Menominee","America/Merida","America/Metlakatla","America/Mexico_City","America/Miquelon","America/Moncton","America/Monterrey","America/Montevideo","America/Montserrat","America/Nassau","America/New_York","America/Nome","America/Noronha","America/North_Dakota/Beulah","America/North_Dakota/Center","America/North_Dakota/New_Salem","America/Nuuk","America/Ojinaga","America/Panama","America/Paramaribo","America/Phoenix","America/Port_of_Spain","America/Port-au-Prince","America/Porto_Velho","America/Puerto_Rico","America/Punta_Arenas","America/Rankin_Inlet","America/Recife","America/Regina","America/Resolute","America/Rio_Branco","America/Santarem","America/Santiago","America/Santo_Domingo","America/Sao_Paulo","America/Scoresbysund","America/Sitka","America/St_Barthelemy","America/St_Johns","America/St_Kitts","America/St_Lucia","America/St_Thomas","America/St_Vincent","America/Swift_Current","America/Tegucigalpa","America/Thule","America/Tijuana","America/Toronto","America/Tortola","America/Vancouver","America/Whitehorse","America/Winnipeg","America/Yakutat","Antarctica/Casey","Antarctica/Davis","Antarctica/DumontDUrville","Antarctica/Macquarie","Antarctica/Mawson","Antarctica/McMurdo","Antarctica/Palmer","Antarctica/Rothera","Antarctica/Syowa","Antarctica/Troll","Antarctica/Vostok","Arctic/Longyearbyen","Asia/Aden","Asia/Almaty","Asia/Amman","Asia/Anadyr","Asia/Aqtau","Asia/Aqtobe","Asia/Ashgabat","Asia/Atyrau","Asia/Baghdad","Asia/Bahrain","Asia/Baku","Asia/Bangkok","Asia/Barnaul","Asia/Beirut","Asia/Bishkek","Asia/Brunei","Asia/Chita","Asia/Choibalsan","Asia/Colombo","Asia/Damascus","Asia/Dhaka","Asia/Dili","Asia/Dubai","Asia/Dushanbe","Asia/Famagusta","Asia/Gaza","Asia/Hebron","Asia/Ho_Chi_Minh","Asia/Hong_Kong","Asia/Hovd","Asia/Irkutsk","Asia/Jakarta","Asia/Jayapura","Asia/Jerusalem","Asia/Kabul","Asia/Kamchatka","Asia/Karachi","Asia/Kathmandu","Asia/Khandyga","Asia/Kolkata","Asia/Krasnoyarsk","Asia/Kuala_Lumpur","Asia/Kuching","Asia/Kuwait","Asia/Macau","Asia/Magadan","Asia/Makassar","Asia/Manila","Asia/Muscat","Asia/Nicosia","Asia/Novokuznetsk","Asia/Novosibirsk","Asia/Omsk","Asia/Oral","Asia/Phnom_Penh","Asia/Pontianak","Asia/Pyongyang","Asia/Qatar","Asia/Qostanay","Asia/Qyzylorda","Asia/Riyadh","Asia/Sakhalin","Asia/Samarkand","Asia/Seoul","Asia/Shanghai","Asia/Singapore","Asia/Srednekolymsk","Asia/Taipei","Asia/Tashkent","Asia/Tbilisi","Asia/Tehran","Asia/Thimphu","Asia/Tokyo","Asia/Tomsk","Asia/Ulaanbaatar","Asia/Urumqi","Asia/Ust-Nera","Asia/Vientiane","Asia/Vladivostok","Asia/Yakutsk","Asia/Yangon","Asia/Yekaterinburg","Asia/Yerevan","Atlantic/Azores","Atlantic/Bermuda","Atlantic/Canary","Atlantic/Cape_Verde","Atlantic/Faroe","Atlantic/Madeira","Atlantic/Reykjavik","Atlantic/South_Georgia","Atlantic/St_Helena","Atlantic/Stanley","Australia/Adelaide","Australia/Brisbane","Australia/Broken_Hill","Australia/Darwin","Australia/Eucla","Australia/Hobart","Australia/Lindeman","Australia/Lord_Howe","Australia/Melbourne","Australia/Perth","Australia/Sydney","Europe/Amsterdam","Europe/Andorra","Europe/Astrakhan","Europe/Athens","Europe/Belgrade","Europe/Berlin","Europe/Bratislava","Europe/Brussels","Europe/Bucharest","Europe/Budapest","Europe/Busingen","Europe/Chisinau","Europe/Copenhagen","Europe/Dublin","Europe/Gibraltar","Europe/Guernsey","Europe/Helsinki","Europe/Isle_of_Man","Europe/Istanbul","Europe/Jersey","Europe/Kaliningrad","Europe/Kirov","Europe/Kyiv","Europe/Lisbon","Europe/Ljubljana","Europe/London","Europe/Luxembourg","Europe/Madrid","Europe/Malta","Europe/Mariehamn","Europe/Minsk","Europe/Monaco","Europe/Moscow","Europe/Oslo","Europe/Paris","Europe/Podgorica","Europe/Prague","Europe/Riga","Europe/Rome","Europe/Samara","Europe/San_Marino","Europe/Sarajevo","Europe/Saratov","Europe/Simferopol","Europe/Skopje","Europe/Sofia","Europe/Stockholm","Europe/Tallinn","Europe/Tirane","Europe/Ulyanovsk","Europe/Vaduz","Europe/Vatican","Europe/Vienna","Europe/Vilnius","Europe/Volgograd","Europe/Warsaw","Europe/Zagreb","Europe/Zurich","Indian/Antananarivo","Indian/Chagos","Indian/Christmas","Indian/Cocos","Indian/Comoro","Indian/Kerguelen","Indian/Mahe","Indian/Maldives","Indian/Mauritius","Indian/Mayotte","Indian/Reunion","Pacific/Apia","Pacific/Auckland","Pacific/Bougainville","Pacific/Chatham","Pacific/Chuuk","Pacific/Easter","Pacific/Efate","Pacific/Fakaofo","Pacific/Fiji","Pacific/Funafuti","Pacific/Galapagos","Pacific/Gambier","Pacific/Guadalcanal","Pacific/Guam","Pacific/Honolulu","Pacific/Kanton","Pacific/Kiritimati","Pacific/Kosrae","Pacific/Kwajalein","Pacific/Majuro","Pacific/Marquesas","Pacific/Midway","Pacific/Nauru","Pacific/Niue","Pacific/Norfolk","Pacific/Noumea","Pacific/Pago_Pago","Pacific/Palau","Pacific/Pitcairn","Pacific/Pohnpei","Pacific/Port_Moresby","Pacific/Rarotonga","Pacific/Saipan","Pacific/Tahiti","Pacific/Tarawa","Pacific/Tongatapu","Pacific/Wake","Pacific/Wallis","UTC"];

            if(!in_array($value,$validTimezoneNames)){
                  $retval = 62;
            }else{
                  $retval = 0;
            }
            return $retval;
      };



$$nameSpaceID['fn']['checkFullName'] = function ($fullName,$minlen,$maxlen,$pattern){     
            $errorCode =0;
            if ($fullName == ""){
                  $errorCode = 15; //full name blank
            }elseif(mb_strlen($fullName,'UTF-8') < $minlen ){
                  $errorCode = 16; //full name too short
            }elseif(mb_strlen($fullName,'UTF-8') > $maxlen ){
                  $errorCode = 17; //full name too long
            }else if (preg_match($pattern, $fullName) !=1){
                  $errorCode = 18; //full name has invalid characters 
            }
            echo "2";
            return $errorCode;
      };

$$nameSpaceID['fn']['checkPreferredName'] = function($preferredName,$minlen,$maxlen,$pattern){  
            $errorCode =0;
            if ($preferredName == ""){
                  $errorCode = 19; //preferred name blank
            }elseif(mb_strlen($preferredName,'UTF-8') < $minlen ){
                  $errorCode = 20; //preferred name too short
            }elseif(mb_strlen($preferredName,'UTF-8') > $maxlen ){
                  $errorCode = 21; //preferred name too long
            }else if (preg_match($pattern, $preferredName) !=1){
                  $errorCode = 22; //preferred name has invalid characters 
            }
            return $errorCode;
      };

$$nameSpaceID['fn']['checkPassword'] =function($password,$confirmpassword){
            $errorCode =0;
            if($password == ""){
                  $errorCode = 23; //password is missing
            }else if (mb_strlen($password,'UTF-8') < 8){
                  //mb_strlen counts multi-byte (multi-part) chars like emojii and é as 1 glyph)
                  $errorCode = 24;
            }else if (mb_strlen($password,'UTF-8') > 128){
                  $errorCode = 25;
                  
                  
                  
            //deliberately omits ¬ symbol. all other British standard keyboard-visible symbols permitted. ¬ is used for testing (throws an error here) and for separation of values elsewhere. This approach prevents SQL injection eg submitting 1 OR 1=1 as the PW    
            //}else if (preg_match("/^[A-Za-z0-9".preg_quote('~!@#£$&_+={}:;<,>^*()|:.?/`-[]%"\'/\\','/')."]{8,128}$/", $password)!= 1){
            
            //allows any char in any language except control chars, and except the first and last being a space or control char.
            //includes a min of 8 and max of 128. this uses multi-byte count thanks to the /u flag, so multi-byte (multi-part) symbols like  like emojii and é which form one symbol/character/glyph are counted as 1 as desired.
            //should i also remove the = symbol? let's see!
            
            }else if (preg_match("/^([^\p{C} ]\p{^C}{6,126}[^\p{C} ])$/u", $password)!= 1){
                  $errorCode = 26;
            }else if ($password != $confirmpassword){
                  $errorCode = 27;
            } 
            return $errorCode;
      };



$$nameSpaceID['fn']['checkVerificationCode'] = function($verificationCode){
            $errorCode =0;
            if($verificationCode == ""){
                  $errorCode = 28; //verification code is missing 
            }else if (mb_strlen($verificationCode,'UTF-8') != 10){
                  $errorCode = 29; //verification code must be 10 characters long
            }else if (preg_match_all("/[^23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ]/", $verificationCode)> 0){
                  //pregmatch shows all characters which a verification code can contain. will have to think of the non latin alternative. 
                  $errorCode = 58; //verification code contains invalid characters. (prevents SQL injection eg vcode of "100 OR 1=1"
            }
            return $errorCode;
      };

$$nameSpaceID['fn']['checkProjectID'] =   function($projectID){
            //project ID is a positive integer
            $errorCode = 0;
            $projectID = (int) $projectID;
            if(is_int($projectID) == false || $projectID <= 0){
                  $errorCode = 31; //invalid project id
            }
            return $errorCode;
      };

$$nameSpaceID['fn']['verifyProjectID'] =  function ($projectID){
            if(!isset($debugging)){
                  $debugging = false;
            }
            if($debugging){print "<br>call to function verifyProjectID(".$projectID.")<br>";}
            
            $errorCode = checkProjectID($projectID);
            if($errorCode == 0){
                  include( $_SERVER['DOCUMENT_ROOT'] ."/klogin/sl_database.php");
                  $sql = "SELECT id FROM %s.projects WHERE id='%s' LIMIT 1";
                  $sql = sprintf($sql,$login_database, mysqli_real_escape_string($login_link,$projectID));
                  if($debugging){print "<br>".$sql."<br>";}

                  if($result = mysqli_query($login_link,$sql)){
                        if($debugging){print "<br>project ".$projectID." exists!<br>";}
                        //success! (errorCode remains 0)
                  }else{
                        $errorCode = mysqli_error($login_link);
                  }
            }
            
            if($debugging){print "<br>verifyProjectID(".$projectID.") returns ".$errorCode."<br>";}
            return $errorCode;
      };

$$nameSpaceID['fn']['checkCapcha'] =      function ($challenge_field, $response_field){

            require_once('recaptchalib.php');
            $message=""; // JPS
            $errorCapcha = array(); // JPS
            $privatekey = "6LeFNtUSAAAAAEA_tbpMgsQ2Sfmr90G6GYFct7nE";
            // $resp = recaptcha_check_answer ($privatekey, $_SERVER["REMOTE_ADDR"], $RECAPTCHA_CHALLENGE_FIELD, ffffffffffffffffffff);   JPS 24/09/13 commented out, replacement line below
            $resp = recaptcha_check_answer ($privatekey, $_SERVER["REMOTE_ADDR"], $challenge_field, $response_field); 
            if (!$resp->is_valid && substr($_SERVER['SERVER_NAME'], -9) != ".r.mil.uk")
            //if the response is not valid
            { 
                  $message = "incorrect_captcha";
                  $errorCapcha['errorMsg']=$message;
                  $errorCapcha['errorCode']=13;
            }
            else
            {
                  $errorCapcha['errorMsg']= "";
                  $errorCapcha['errorCode']= 0;
            }
            
            return $errorCapcha; // JPS
      };

$$nameSpaceID['fn']['isLeapYear'] = function($year){
            
            if(!is_numeric($year)){
                  $retval = -1;
            }else{
                  //  % = modulus not divide so this works
                  if( (0 == $year % 4) and (0 != $year % 100) or (0 == $year % 400) ){
                        $retval = 1;
                  }else{
                        $retval = 0;
                  }     
                  return $retval; //-1 invalid, 1 = leapyear, 0 = not a leap year
            }
      };

$$nameSpaceID['fn']['checkDateTime'] = function($value,$calendar){
            //checks a valid datetime in YYYY-MM-DDTHH:II:SS format has been received as $value. also acceptable for T to be a space
            
            $value = str_replace("T"," ",$value);
            
            $calendar = 'GREGORIAN';//not used yet
            $m30 = array('09','04','06','11');
            $m31 = array('01','03','05','07','08','10','12');
            $retval = true;//default

            if(mb_strlen($value,'UTF-8') == 19){ 
                  $m = intval(substr($value,5,2));
                  if(in_array($m,$m30)){
                        $mPattern = '0[1-9]|[1-2][0-9]|30';
                  }else if(in_array($m,$m31)){
                        $mPattern = '0[1-9]|[1-2][0-9]|3[0-1]';
                  }else if($m = '02'){
                        $ly = isLeapYear(substr($value,0,4));
                        if($ly == 0){
                              $mPattern = '0[1-9]|1[0-9]|2[0-8]';
                        }else if($ly == 1){
                              $mPattern = '0[1-9]|1[0-9]|2[0-9]';
                        }else{
                              $retval = false;
                        }
                  }else{
                        $retval = false;
                  }

                  $pattern = "/[0-9]{4}-(0[1-9]|1[0-2])-(". $mPattern. ") (2[0-3]|[01][0-9]):[0-5][0-9]:[0-5][0-9]/";
                  preg_match($pattern, $value, $matches, PREG_OFFSET_CAPTURE);
                  if(count($matches)> 0){
                        $retval = true;
                  }else{
                        $retval = false;
                  }
                  
            }else{
                  $retval = false;
            }
            return $retval;   
      };

$$nameSpaceID['fn']['checkUID'] =   function ($value){
            
            //checks if it meets critera (64 chars in len exactly and made up of only un-ambiguous 
            //characters a-z, A-Z, 0-9 (so not, for example, 0,O,o or 1,L,l)
            
            
            $pattern = "/^[23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ]{64}$/";
            preg_match($pattern, $value, $matches, PREG_OFFSET_CAPTURE);
            if(count($matches) == 1){
                  $retval = true;
            }else{
                  $retval = false;
            }     
            return $retval;
      };

$$nameSpaceID['fn']['generateHTMLMsgBubble'] = function ($fieldName,$direction,$field_errors=array(),$msgBubbleIcon="errExclaim"){
      
      $errors = count($field_errors) > 0;

      $delay = 0.25;
      $retval = '             <div id="%s_msgBubble" rate="1" class="msgBubble';
      if($errors == 0){$retval .= ' instantlyPopBubble';}
      $retval .= '">'.PHP_EOL;
      $retval .= '                        <div id="%s_bubble" class="bubble animated fadeIn">'.PHP_EOL;     
      $retval .= '                                    <div id="%s_bubbleBody" class="bubbleBody">'.PHP_EOL;
      $retval .= '                                          <div class="bubbleBodyPt1" style="color:blue">'.PHP_EOL;
      $retval .= '                               <div id="%s_msgBubbleIcon" class="msgBubbleIcon"><svg class="svgIcon" style="height:100%%;width:100%%"><use xlink:href="#%s" /></svg></div>'.PHP_EOL;
      $retval .= '                                                <span id="%sErrorText" class="bubbleContent">';
            if($errors){$errorString = implode(", ",$field_errors);}else{$errorString = "";}
      $retval .= str_replace('%','%%',$errorString);
      $retval .= '</span>'.PHP_EOL;
      $retval .= '                                          </div>'.PHP_EOL;
      $retval .= '                                          <div id="%s_grayBubbleCallout" class="grayBubbleCallout %s">'.PHP_EOL;
      $retval .= '                                          </div>'.PHP_EOL;
      $retval .= '                                          <div id="%s_whiteBubbleCallout" class="whiteBubbleCallout %s">'.PHP_EOL;
      $retval .= '                                          </div>'.PHP_EOL;
      $retval .= '                                    </div>'.PHP_EOL;
      $retval .= '                              </div>'.PHP_EOL;
      $retval .= '                  </div>'.PHP_EOL;
      $retval = sprintf($retval,$fieldName,$fieldName,$fieldName,$fieldName,$msgBubbleIcon, $fieldName,$fieldName,$direction, $fieldName,$direction);


      return $retval;
      };

      
$$nameSpaceID['fn']['PHPIFY'] =     function($pattern){
            //attempts to convert HTML input element pattern value to PHP-compatible regex pattern    
            //very very basic. only handles adding open and closing / and start/finish symbols and 
            //letter category names (removes the sc=) to convert and adds /u unicode flag.

            $pattern = "/^".$pattern."$/";
            if(strpos($pattern,"sc=")){
                  $pattern = str_replace("sc=","",$pattern);
                  $pattern .= "u";
            }
      return $pattern;  
      };


$$nameSpaceID['fn']['humanTiming'] =      function($time){
            $time = time() - $time; // to get the time since that moment
            $time = ($time<1)? 1 : $time;
            $tokens = array (
                  31536000 => 'yr',
                  2592000 => 'mth',
                  604800 => 'wk',
                  86400 => 'day',
                  3600 => 'hr',
                  60 => 'min',
                  1 => 'sec'
            );

            foreach ($tokens as $unit => $text) {
                  if ($time < $unit) continue;
                  $numberOfUnits = floor($time / $unit);
                  return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'s':'');
            }
      };

$$nameSpaceID['fn']['humanDating'] =      function($timestamp,$userLocalTimeZone,$userLanguage="en-GB"){
            //timestamp is a string of a unix timestamp (saved in a string);
            $timestamp = strtotime('@'.$timestamp);
            $date = new DateTime();
            $date->setTimestamp($timestamp);
            $date->setTimezone(new DateTimeZone($userLocalTimeZone));
            
            $timePortion =  $date->format('H:i:s');         
            $datePortion = $date->format('Y-m-d\T00:00:00');
            
            $today = new DateTime("today"); // This object represents current date/time with time set to midnight
            $today->setTimezone(new DateTimeZone($userLocalTimeZone));  

            $match_day = DateTime::createFromFormat( "Y-m-d\\TH:i:s",$datePortion );

            $diff = $today->diff($match_day);
            $diffDays = (integer)$diff->format( "%R%a" ); // Extract days count in interval

            switch( $diffDays ) {
                  case 0:
                        $retval = "at ";
                        break;
                  case -1:
                     $retval = "Yesterday at ";
                        break;
                  case +1:
                        $retval = "Tomorrow at ";
                        break;
                  default:
                        $retval = " on ";
                        if($date->format('Y-m') === $today->format('Y-m')){
                              // years and months match
                              $retval .= $date->format("l jS") ." at  ";
                        }else{
                              $retval .= $date->format("jS M");
                              
                              if($date->format("Y") === $today->format('Y')){
                                    //years match
                                    $retval .= " at ";
                              }else{
                                    $retval .= $date->format("'Y-m-d"). " at "; 
                              }
                        }           
            }
      return $retval. $timePortion;
      };

$$nameSpaceID['fn']['checkwidthPx'] = function($value){
            //check if positive integer > 0
            //used in kloud/getPic.php (image width)
            if((int)$value > 0){
                  return 0;
            }else{
                  return 99;
            }
      };

$$nameSpaceID['fn']['checkheightPx'] = function($value){
            //check if positive integer > 0
            //used in kloud/getPic.php (image height)
            if((int)$value > 0){
                  return 0;
            }else{
                  return 99;
            }
      };


$$nameSpaceID['fn']['checkappID'] = function($value){
            //check if positive integer > 0
            //used in klogin/process_getOTP.php
            if((int)$value > 0){
                  return 0;
            }else{
                  return 99;
            }     
      };

$$nameSpaceID['fn']['checkFileName'] =    function($value){
      $errorCode =0;
      if($value == ""){
            $errorCode = 99;
      }else if (mb_strlen($value,'UTF-8') < 5){
            $errorCode = 99;
      }else if (mb_strlen($value,'UTF-8') > 255){
            $errorCode = 99;
            
      //accepts valid filenames up to 256 characters long with or without a file extension
      }else if (preg_match("/^(?!.{5,255})(?!(aux|clock\$|con|nul|prn|com[1-9]|lpt[1-9])(?:$|\.))[^ ][ \.\w\-$()+=[\];#@~,&amp;']+[^\. ]$/i", $value) != 1){
            $errorCode = 99;
      }
      return $errorCode;      
};

$$nameSpaceID['fn']['checktaskID'] =      function($value){
            $value = (int) $value;
            if(is_int($value) === false || $value <= 0 ){
                  return 99;
            }else{
                  return 0;
            }
      };

$$nameSpaceID['fn']['checkIcon'] =       function ($value){
            
            //checks if it meets critera (64 chars in len exactly and made up of only un-ambiguous 
            //characters a-z, A-Z, 0-9 (so not, for example, 0,O,o or 1,L,l)
            
            
            $pattern = "/^[23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ]{64}$/";
            preg_match($pattern, $value, $matches, PREG_OFFSET_CAPTURE);
            if(count($matches) == 1){
                  $retval = 0;
            }else{
                  $retval = 99;
            }     
            return $retval;
      };

      
      //this could be a number of numbers?
$$nameSpaceID['fn']['checkdataPrivilegeID'] =   function ($value){
            //check if positive integer > 0
            //used in /pride/admin/process_edittaskform.php
            
            
            //ensure that any multi-value name-value pair is correctly stored as an array of values
            //regardless of whether it was received as a string containing a single value, a comma-separated string of values
            //or an array of values, and register a count of how many values there are.
                        
            if(is_array($value) === true){
                  //do nothing
            }else{
                  //set the value aside
                  $tmpVal = $value;
                  //place an empty array ready to receive the value back again.
                  $valArray = array();
                  //return the value to the array
                  if(strpos($tmpVal,",") ===  false){
                        $valArray[] = $tmpVal;
                  }else{
                        $valArray = explode(",", $tmpVal);
                  }
                  //clean up the set-aside value which is now copied into the array.
                  unset($tmpVal);
                  $value = $valArray;
            }
            
            $retval = 0;
        foreach($value as $val){
                  //return error if val is neither 0 nor a positive integer. both string and int format are acceptable
                  if((is_numeric($val) && floor($val) != $val) ||(int)$val < 0 || ((int)$val === 0 && $val !== "0")){
                        $retval = 99;
                        break;
                  }
            }
            return $retval;   
      };

  
  
	$$nameSpaceID['fn']['checkparaTitle'] =   function ($value){
            $errorCode =0;
            if($value == ""){
                  $errorCode = 99;
            }else if (mb_strlen($value,'UTF-8') < 1){
                  $errorCode = 99;
            }else if (mb_strlen($value,'UTF-8') > 255){
                  $errorCode = 99;
                  
            //allows any char in any language except = control chars and separator characters
            //includes a min of 1 and max of 255. this uses multi-byte count thanks to the /u flag, so multi-byte (multi-part) symbols like  like emojii and é which form one symbol/character/glyph are counted as 1 as desired. allows a single space between words
            }else if (preg_match("/^([^=\p{Z}\p{C}]( {1}[^=\p{Z}\p{C}])*){1,255}$/u", $value) == 0){              
                  $errorCode = 98;
            }
            return $errorCode;      
      };


     
	 
	$$nameSpaceID['fn']['checklongDescription'] =    function ($value){
	
			global $nameSpaceID;
			
			global $$nameSpaceID;

		
            $errorCode =0;
            if($value == ""){
                  $errorCode = 99;
            }else if (mb_strlen($value,'UTF-8') < 1){
                  $errorCode = 99;
            }else if (mb_strlen($value,'UTF-8') > 65535){
                  $errorCode = 99;
            }
            
            if($errorCode == 0){
                  $serverSideScriptIndicators = array("<%","%>",".avfp",".asp",".aspx",".cshtml",".vbhtml","@{","@Code","@Html",".cfm",".go","import (","import(",".gs","function()",".php","<?","<?php","?>",".hs",".jsp",".do",".ssjs",".js",".lassoo",".lp",".op",".lua","BEGIN()","END()",".p","Parse.",".cgi",".ipl",".pl","<!--","-->",".php3",".php4",".phtml",".py","import cgi","#!/",".rhtml",".rb",".rbw",".tcl","<@tcl>","</@tcl>",".dna",".tpl",".r", ".w");
                  
                  foreach($serverSideScriptIndicators as $indicator){
                        if (strpos($value, "<%") !== false){
                              $errorCode = 99;
                              break;
                        }
                  }
            }     
            
            if ($errorCode == 0 && strpos($value,"<") !== false){
                  $errorCode = ${$$nameSpaceID}['fn']['naughtyHTML']($value);
				  
            }
            return $errorCode;      
      };


$permittedNodes = array("time","article","section","nav","aside","h1","h2","h3","h4","h5","h6","hgroup","header","footer","address","p","hr","pre
","blockquote","ol","ul","menu","li","dl","dt","dd","figure","figcaption","main","search","div","a","em","strong","small","s","cite
","q","dfn","abbr","ruby","rt","rp","data","code","var","samp","kpd","sub","sup","i","b","u","mark","bdi","bdo","span","br","wbr","area","ins","del
","picture","source","img","object","video","audio","track","map","table","caption","colgroup","col","tbody","thead","tfoot","tr
","td","th","details","summary","noscript","canvas");

$bannedAttributes = array("onhover","onabort","onblur","oncanplay","oncanplaythrough","onchange","onclick","oncontextmenu","oncuechange","ondblclick","ondrag","ondragend","ondragenter","ondragleave","ondragover","ondragstart","ondrop","ondurationchange","onemptied","onended","onerror","onfocus","oninput","oninvalid","onkeydown","onkeypress","onkeyup","onload","onloadeddata","onloadedmetadata","onloadstart","onmousedown","onmousemove","onmouseout","onmouseover","onmouseup","onmousewheel","onpause","onplay","onplaying","onprogress","onratechange","onreadystatechange","onreset","onscroll","onseeked","onseeking","onselect","onshow","onstalled","onsubmit","onsuspend","ontimeupdate","onvolumechange","onwaiting");


      //called in checkParaText returns non-zero error code if unsafe HTML is detected in a string
      //uses naughtyHTMLChecker() which has to be a separate function so that it can call its self to iterate
      //through the HTML DOM tree of the html received.
$$nameSpaceID['fn']['naughtyHTML'] =      function ($string){
	
			global $nameSpaceID;
			global $$nameSpaceID;
	
            $retval = 0;
            $dom = new DOMDocument();

            libxml_use_internal_errors(true); //turn off loadHTML errors to stop poorly constructed HTML (eg no html tag) throwing warnings
            $dom->loadHTML($string, LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD); //arguments allow loadhtml to continue if poorly constructed HTML is encountered
            foreach (libxml_get_errors() as $error) {
                        //var_dump($error); //ignore errors
            }
            libxml_clear_errors();
            libxml_use_internal_errors(false);
            $retval = ${$$nameSpaceID}['fn']['naughtyHTMLChecker']($dom);

            return $retval;
      };

$$nameSpaceID['fn']['naughtyHTMLChecker'] =     function (DOMNode $domNode) {
            
			global $nameSpaceID;
			global $$nameSpaceID;

			$retval = 0;
            global $permittedNodes;
            global $bannedAttributes;
            foreach ($domNode->childNodes as $node){
                  if($node->nodeType == XML_TEXT_NODE){
                        //skip the text
                  }else{
                        //check if the node type is permitted
                        if(in_array($node->nodeName,$permittedNodes,true)){
                              //echo "permitted node ".$node->nodeName."<BR>";
                        }else{
                              //echo "not permitted node ".$node->nodeName."<BR>";
                              $retval = 99;
                              break;
                        }
                        //check if attributes are permitted
                        if($node->hasAttributes()){
                              $attributes = $node->attributes;
                              if(!is_null($attributes)){
                                    foreach ($attributes as $index=>$attr) {
                                          //print $attr->name." = ".$attr->value."<BR>";
                                          if(in_array($attr->name,$bannedAttributes,true)){
                                                //echo "     not permitted attribute ".$attr->name."<BR>";
                                                $retval = 99;
                                                break;
                                          }else{
                                                //echo "     permitted attribute ".$attr->name."<BR>";
                                          }
                                    }
                              }
                        }
                        if($node->hasChildNodes()){
                              ${$$nameSpaceID}['fn']['naughtyHTMLChecker']($node);
                        }
                  }
            }
            return $retval;
      };




$$nameSpaceID['fn']['checkshortName'] =    function ($value){
	$retval = 0;
	return $retval;
};



$$nameSpaceID['fn']['checkmedName'] =    function ($value){
	$retval = 0;
	return $retval;
};
$$nameSpaceID['fn']['checklongName'] =    function ($value){
	$retval = 0;
	return $retval;
};
$$nameSpaceID['fn']['checkshortDescription'] =    function ($value){
	$retval = 0;
	return $retval;
};

$$nameSpaceID['fn']['checkicon'] =    function ($value){
	$retval = 0;
	return $retval;
};

$$nameSpaceID['fn']['checkiconColour'] =    function ($value){
	$retval = 0;
	return $retval;
};

$$nameSpaceID['fn']['checksortOrder'] =    function ($value){
	$retval = 0;
	return $retval;
};

$$nameSpaceID['fn']['checkpublishStatus'] =    function ($value){
	$retval = 0;
	return $retval;
};

$$nameSpaceID['fn']['checkprimaryClient'] =    function ($value){
	$retval = 0;
	return $retval;
};

$$nameSpaceID['fn']['checkprimaryClientUIN'] =    function ($value){
	$retval = 0;
	return $retval;
};

$$nameSpaceID['fn']['checkprotocol'] =    function ($value){
	$retval = 0;
	return $retval;
};

$$nameSpaceID['fn']['checkdevDomain'] =    function ($value){
	$retval = 0;
	return $retval;
};

$$nameSpaceID['fn']['checktestDomain'] =    function ($value){
	$retval = 0;
	return $retval;
};

$$nameSpaceID['fn']['checkprodDomain'] =    function ($value){
	$retval = 0;
	return $retval;
};

$$nameSpaceID['fn']['checkappRoot'] =    function ($value){
	$retval = 0;
	return $retval;
};

$$nameSpaceID['fn']['checkhomePage'] =    function ($value){
	$retval = 0;
	return $retval;
};

$$nameSpaceID['fn']['checklegalAndPolicyLink'] =    function ($value){
	$retval = 0;
	return $retval;
};

$$nameSpaceID['fn']['checksecureAnAccountLink'] =    function ($value){
	$retval = 0;
	return $retval;
};

$$nameSpaceID['fn']['checkallowNewIncidents'] =    function ($value){
	$retval = 0;
	return $retval;
};


$$nameSpaceID['fn']['checkallowNewRFCs'] =    function ($value){
	$retval = 0;
	return $retval;
};

$$nameSpaceID['fn']['checkallowNewRFIs'] =    function ($value){
	$retval = 0;
	return $retval;
};

$$nameSpaceID['fn']['newIncidentLink'] =    function ($value){
	$retval = 0;
	return $retval;
};


$$nameSpaceID['fn']['newRFCLink'] =    function ($value){
	$retval = 0;
	return $retval;
};

$$nameSpaceID['fn']['newRFILink'] =    function ($value){
	$retval = 0;
	return $retval;
};



$$nameSpaceID['fn']['checkadminEmail'] =    function ($value){
	$retval = 0;
	return $retval;
};

$$nameSpaceID['fn']['checkotherSMEs'] =    function ($value){
	$retval = 0;
	return $retval;
};

if(isset($prevNameSpaceID) && $prevNameSpaceID !== ""){
      //if this file has been included, it used its own namespaceID. its parent's id 
      //should now be re-adopted. 
      $nameSpaceID = $prevNameSpaceID;
      $prevNameSpaceID = "";
}
?>
