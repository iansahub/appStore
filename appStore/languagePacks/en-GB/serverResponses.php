<?php
/************************************************************
PROJECT NAME:  phpSched
FILE NAME   :  serverResponses.php

FILE DESCRIPTION: 
languagepack content to localize international web page with
specific language content. Server responses are limited to
error/success messages passed in URL/Arguments, and are not
the content of the page like paragraphs/sentences which are
localized by DOMContent.php instead.

VER:   DATE:     INITIALS:  DESCRIPTION OF CHANGE:
1.0    18/08/21  AB         Initial Version

Php Version:   5.3.3
Creative Commons License
**************************************************************/

if(!isset($appID)){$appID = 1;} //dont put this in session. apps may be embedded in apps and we might want the parent one

$errors = array();
$serverResponses = array();

$errors[0] = array();
$errors[0]['forTranslator'] = "";
$errors[0]['msg'] = "";  

$errors[1] = array();
$errors[1]['forTranslator'] = "you must be logged in to do that";
$errors[1]['msg'] = "you must be logged in to do that";

$errors[2] = array();
$errors[2]['forTranslator'] = "a valid email address is required";
$errors[2]['msg'] = "a valid email address is required";

$errors[3] = array();
$errors[3]['forTranslator'] = "Password or Verification Code is missing";
$errors[3]['msg'] = "Password or Verification Code is missing";

$errors[4] = array();
$errors[4]['forTranslator'] = "Account Disabled";
$errors[4]['msg'] = "Account Disabled";

$errors[5] = array();
$errors[5]['forTranslator'] = "0 database records changed";
$errors[5]['msg'] = "0 database records changed";

$errors[6] = array();
$errors[6]['forTranslator'] = "Incorrect Verification Code";
$errors[6]['msg'] = "Incorrect Verification Code. Try again";

$errors[7] = array();
$errors[7]['forTranslator'] = "Incorrect. Try Again";
$errors[7]['msg'] = "Incorrect. Try Again";

$errors[8] = array();
$errors[8]['forTranslator'] = "your secure session has timed out, you will need to sign in again";
$errors[8]['msg'] = "your secure session has timed out, you will need to sign in again";

$errors[9] = array();
$errors[9]['forTranslator'] = "could not query users table";
$errors[9]['msg'] = "could not query users table";

$errors[10] = array();
$errors[10]['forTranslator'] = "Email not Verified";
$errors[10]['msg'] = "Email not Verified"; 

$errors[11] = array();
$errors[11]['forTranslator'] = "Email already in use";
$errors[11]['msg'] = "Email already in use"; //checkEmail()

$errors[12] = array();
$errors[12]['forTranslator'] = "Email already in use (unverified)";
$errors[12]['msg'] = "Email already in use (unverified)"; //checkEmail()

$errors[13] = array();
$errors[13]['forTranslator'] = "Email not in database";
$errors[13]['msg'] = "Email not in database";

$errors[14] = array();
$errors[14]['forTranslator'] = "An email has been sent to you";
$errors[14]['msg'] = "An email has been sent to you";

$errors[15] = array();
$errors[15]['forTranslator'] = "Full Name is missing";
$errors[15]['msg'] = "Full Name is missing";

$errors[16] = array();
$errors[16]['forTranslator'] = "Full Name is too short. needs x characters";
$errors[16]['msg'] = "Full Name is too short. ";
$errors[16]['msg'] .= isset($nameform)? "needs ". $nameform['fullName']['minlength'] : "needs more ";
$errors[16]['msg'] .= " characters";

$errors[17] = array();
$errors[17]['forTranslator'] = "Full Name is too long. needs x characters";
$errors[17]['msg'] = "Full Name is too long. ";
$errors[17]['msg'] .= isset($nameform)? "max ". $nameform['fullName']['maxlength'] : " needs fewer ";
$errors[17]['msg'] .= " characters";

$errors[18] = array();
$errors[18]['forTranslator'] = "Full Name can only contain letters. One hyphen is also allowed mid-way";
$errors[18]['msg'] = "Full Name can only contain letters. One hyphen is also allowed mid-way";

$errors[19] = array();
$errors[19]['forTranslator'] = "Preferred Name is missing";
$errors[19]['msg'] = "Preferred Name is missing";

$errors[20] = array();
$errors[20]['forTranslator'] = "Preferred Name is too short (min x characters)";
$errors[20]['msg'] = "Preferred Name is too short ";
$errors[20]['msg'] .= isset($nameform)? "min ". $nameform['fullName']['minlength'] : " needs additional ";
$errors[20]['msg'] .= " characters";

$errors[21] = array();
$errors[21]['forTranslator'] = "Preferred Name is too long (max x characters)";
$errors[21]['msg'] = "Preferred Name is too long "; 
$errors[21]['msg'] .= isset($nameform)? "max ". $nameform['preferredName']['maxlength'] : " needs fewer ";
$errors[21]['msg'] .= " characters";

$errors[22] = array();
$errors[22]['forTranslator'] = "Preferred Name can only contain letters. One hyphen is also allowed mid-way";
$errors[22]['msg'] = "Preferred Name can only contain letters. One hyphen is also allowed mid-way";

$errors[23] = array();
$errors[23]['forTranslator'] = "password is required";
$errors[23]['msg'] = "password is required";

$errors[24] = array();
$errors[24]['forTranslator'] = "password is too short (min 8 characters)";
$errors[24]['msg'] = "password is too short (min 8 characters)";

$errors[25] = array();
$errors[25]['forTranslator'] = "password is too long (max 128 characters)";
$errors[25]['msg'] = "password is too long (max 128 characters)";

$errors[26] = array();
$errors[26]['forTranslator'] = "password contains unpermitted characters.";
$errors[26]['msg'] = "password contains unpermitted characters.";

$errors[27] = array();
$errors[27]['forTranslator'] = "passwords do not match";
$errors[27]['msg'] = "passwords do not match";

$errors[28] = array();
$errors[28]['forTranslator'] = "verification code is required";
$errors[28]['msg'] = "verification code is required";

$errors[29] = array();
$errors[29]['forTranslator'] = "verification code must be 10 characters long";
$errors[29]['msg'] = "verification code must be 10 characters long";

$errors[30] = array();
$errors[30]['forTranslator'] = "process script encountered unexpected mode";
$errors[30]['msg'] = "process script encountered unexpected mode";

$errors[31] = array();
$errors[31]['forTranslator'] = "invalid project id";
$errors[31]['msg'] = "invalid project id";

$errors[32] = array();
$errors[32]['forTranslator'] = "Your account has automatically expired because its expiry date has passed.";
$errors[32]['msg'] = "Your account has automatically expired because its expiry date has passed.";

$errors[33] = array();
$errors[33]['forTranslator'] = "too soon. wait %d seconds";
$errors[33]['msg'] = "too soon. wait %d seconds";

$errors[34] = array();
$errors[34]['forTranslator'] = "bad input";
$errors[34]['msg'] = "bad input";

$errors[35] = array();
$errors[35]['forTranslator'] = "unhandled error encountered";
$errors[35]['msg'] =  "unhandled error encountered";

$errors[36] = array();
$errors[36]['forTranslator'] = "range overflow";
$errors[36]['msg'] = "range overflow";

$errors[37] = array();
$errors[37]['forTranslator'] = "range underflow";
$errors[37]['msg'] = "range underflow";

$errors[38] = array();
$errors[38]['forTranslator'] = "step mismatch";
$errors[38]['msg'] = "step mismatch";

$errors[39] = array();
$errors[39]['forTranslator'] = "can't use that email as it is in use by another account";
$errors[39]['msg'] = "can't use that email as it is in use by another account";

$errors[40] = array();
$errors[40]['forTranslator'] = '"no more than "+ that.getAttribute("maxlength") +" characters please"';
$errors[40]['msg'] = '"no more than "+ that.getAttribute("maxlength") +" characters please"';

$errors[41] = array();
$errors[41]['forTranslator'] = "can't start with a dot";
$errors[41]['msg'] = "can't start with a dot";

$errors[42] = array();
$errors[42]['forTranslator'] = "can't end with a dot";
$errors[42]['msg'] = "can't end with a dot";

$errors[43] = array();
$errors[43]['forTranslator'] = "can't start with an @ symbol";
$errors[43]['msg'] = "can't start with an @ symbol";

$errors[44] = array();
$errors[44]['forTranslator'] = "can't end with an @ symbol";
$errors[44]['msg'] = "can't end with an @ symbol";

$errors[45] = array();
$errors[45]['forTranslator'] = '"no fewer than "+that.getAttribute("minlength") +" characters please"';
$errors[45]['msg'] =  '"no fewer than "+that.getAttribute("minlength") +" characters please"';

$errors[46] = array();
$errors[46]['forTranslator'] = '"wrong type of data entered. "+ that.getAttribute("type") +" required"';
$errors[46]['msg'] =  '"wrong type of data entered. "+ that.getAttribute("type") +" required"';

$errors[47] = array();
$errors[47]['forTranslator'] = "spaces aren't permited here";
$errors[47]['msg'] =  "spaces aren't permited here";

$errors[48] = array();
$errors[48]['forTranslator'] = "only one @ allowed";
$errors[48]['msg'] =  "only one @ allowed";

$errors[49] = array();
$errors[49]['forTranslator'] = "at least one dot needed between the @ and the end of the email";
$errors[49]['msg'] =  "at least one dot needed between the @ and the end of the email";

$errors[50] = array();
$errors[50]['forTranslator'] = "can't have 2+ dots in a row";
$errors[50]['msg'] =  "can't have 2+ dots in a row";

$errors[51] = array();
$errors[51]['forTranslator'] = "requires an @ with some characters before and after. Also at least one dot after the @, with some characters before and after the dot.";
$errors[51]['msg'] =  "requires an @ with some characters before and after. Also at least one dot after the @, with some characters before and after the dot.";

$errors[52] = array();
$errors[52]['forTranslator'] = "contains characters which are not allowed";
$errors[52]['msg'] =  "contains characters which are not allowed";

$errors[53] = array();
$errors[53]['forTranslator'] = "please enter your email address";
$errors[53]['msg'] =  "please enter your email address";

$errors[54] = array();
$errors[54]['forTranslator'] = "please enter your password";
$errors[54]['msg'] =  "please enter your password";

$errors[55] = array();
$errors[55]['forTranslator'] = "please enter a value";
$errors[55]['msg'] =  "please enter a value";

$errors[56] = array();
$errors[56]['forTranslator'] = "please enter a new password";
$errors[56]['msg'] =  "please enter a new password";

$errors[57] = array();
$errors[57]['forTranslator'] = "Unrecognized Error Code";
$errors[57]['msg'] =  "Unrecognized Error Code";

$errors[58] = array();
$errors[58]['forTranslator'] = "verification code contains unpermitted characters.";
$errors[58]['msg'] = "verification code contains unpermitted characters.";

$errors[59] = array();
$errors[59]['forTranslator'] = "Unrecognized response from server";
$errors[59]['msg'] =  "Unrecognized response from server";

$errors[60] = array();
$errors[60]['forTranslator'] = "mandatory value is missing";
$errors[60]['msg'] =  "mandatory value is missing";

$errors[61] = array();
$errors[61]['forTranslator'] = "locale code does not conform to the IETF BCP 47 standard";
$errors[61]['msg'] =  "locale code does not conform to the IETF BCP 47 standard";

$errors[62] = array();
$errors[62]['forTranslator'] = "the local time zone specified is not recognized";
$errors[62]['msg'] =  "the local time zone specified is not recognized";

$serverResponses = array(); //these are non-error messages from the server (eg. success messages).

$serverResponses[123] = array();
$serverResponses[123]['forTranslator'] = 'Server replied with sr code 123 which was translated into this message by serverResponses.php in the relevant langaugePack. sr codes carry non-error (positive or neutral) replies from the server';
$serverResponses[123]['msg'] = 'Server replied with sr code 123 which was translated into this message by serverResponses.php in the relevant langaugePack. sr codes carry non-error (positive or neutral) replies from the server.';

$serverResponses[1] = array();
$serverResponses[1]['forTranslator'] = 'successfully saved';
$serverResponses[1]['msg'] = 'successfully saved';
$serverResponses[1]['format'] = 'd-m-Y H:i:s';

$serverResponses[2] = array();
$serverResponses[2]['forTranslator'] = 'check your email. a verification code has been emailed to you';
$serverResponses[2]['msg'] = 'check your email. a verification code has been emailed to you';

$serverResponses[3] = array();
$serverResponses[3]['forTranslator'] = 'Your email address has been changed. Use the new address as your username next time you log in';
$serverResponses[3]['msg'] = 'Your email address has been changed. Use the new address as your username next time you log in';

$serverResponses[4] = array();
$serverResponses[4]['forTranslator'] = 'Your password has been changed. Use the new password next time you log in';
$serverResponses[4]['msg'] = 'Your password has been changed. Use the new password next time you log in';

?>