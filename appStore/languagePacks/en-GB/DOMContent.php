<?php

if(!isset($appID)){$appID = 1;} //dont put this in session. apps may be embedded in apps and we might want the parent one

//example 
${"NS_appStore_index_php"}['DOM'] = array(
	"title"=>array(
		"innerHTML" => "title App Store"
	),
	"H1MainTitle"=>array(
		"innerHTML" => "Main App Store"
	),
	
	"tab1Heading"=>array(
		"innerHTML" => "Web Apps from Analysis Digital"
	),
	
	"tab1AddMissingApp"=>array(
		"innerHTML" => "Add Missing App"
	),
	
	"tab2Heading"=>array(
		"innerHTML" => "Corporate Off-The-Shelf (COTS)"
	),
	
	"tab2AddMissingApp"=>array(
		"innerHTML" => "Add Missing App"
	),
	
	"tab3Heading"=>array(
		"innerHTML" => "Corporate Off-The-Shelf (COTS)"
	),
	
	"tab3AddMissingApp"=>array(
		"innerHTML" => "Add Missing App"
	),
	

);

${'NS_appStore_addMissingAppForm_php'}['DOM'] = array(
	
	"title" => array(
		"innerHTML" => "Add Missing App"
	),
	
	"H1MainTitle"=>array(
		"innerHTML" => "Add an existing app to the appStore"
	),
	
	"prose"=>array(
		"innerHTML" => "use this form if an app already exists, but is not present in the appStore"
	),


	"shortNameLabel" => array(
		"innerHTML" => "App's short name"
	),
	"shortNameTip" => array(
		"innerHTML" => "enter the shortest possible version of the app's name for use where space is tight"
	),
	
	"shortName" => array(
		"accesskey" => "s",
		"placeholder" => "",
		"minlength" => "3",
		"maxlength" => "32",
		"pattern" => "([^\p{Z}\p{C}]( {1}[^\p{Z}\p{C}])*){3,32}"
	),
	
	"medNameLabel" => array(
		"innerHTML" => "App's normal name"
	),
	"medNameTip" => array(
		"innerHTML" => "enter the name of the app as it is commonly spoken. May be the same as its short or long name. "
	),
	
	"medName" => array(
		"accesskey" => "m",
		"placeholder" => "",
		"minlength" => "3",
		"maxlength" => "32",
		"pattern" => "([^\p{Z}\p{C}]( {1}[^\p{Z}\p{C}])*){3,32}"
	),
	
	"longNameLabel" => array(
		"innerHTML" => "App's long name"
	),
	"longNameTip" => array(
		"innerHTML" => "enter the full name of the app, with absolutely no acronyms or Initialisms"
	),
	
	"longName" => array(
		"accesskey" => "l",
		"placeholder" => "",
		"minlength" => "3",
		"maxlength" => "32",
		"pattern" => "([^\p{Z}\p{C}]( {1}[^\p{Z}\p{C}])*){3,32}"
	),
	
	"shortDescriptionLabel" => array(
		"innerHTML" => "Short description"
	),
	"shortDescriptionTip" => array(
		"innerHTML" => "the purpose of the app in sentence"
	),
	
	"shortDescription" => array(
		"accesskey" => "d",
		"placeholder" => "",
		"minlength" => "3",
		"maxlength" => "32",
		"pattern" => "([^\p{Z}\p{C}]( {1}[^\p{Z}\p{C}])*){3,32}"
	),

	"longDescriptionLabel" => array(
		"innerHTML" => "Long description"
	),		
	"longDescriptionTip" => array(
		"innerHTML" => "a lengthy description of the app describing what it does and more. use full sentences and paragraphs. basic html tags are supported"
	),
	"longDescription" => array(
		"accesskey" => "A",
		"placeholder" => "",
		"minlength" => "",
		"maxlength" => "",
		"pattern" => ""
	),
		
	
	"icon" => array(
		"accesskey" => "i"
	),
	
	"iconLabel" => array(
		"innerHTML" => "icon image file"
	),
	
	"iconTip" => array(
		"innerHTML" => "this control is not currently functional"
	),
	

	"iconColour" => array(
		"accesskey" => "b"
	),
	"iconColourLabel" => array(
		"innerHTML" => "icon colour"
	),		
	"iconColourTip" => array(
		"innerHTML" => "used when icon picture is unavailable"
	),
	

	
	"publishStatusLabel" => array(
		"innerHTML" => "App Life Cycle Status"
	),	
	
	"publishStatusTip" => array(
		"innerHTML" => ""
	),
	
	"publishStatus-1" => array(
		"value" => "proposed",
		"required" => "required"
	),
	"publishStatusLabel-1" => array(
		"innerHTML" => "proposed"
	),
	"publishStatusTip-1" => array(
		"innerHTML" => ""
	),
	
	
	"publishStatus-2" => array(
		"value" => "unresourced",
		"required" => "required"
	),
	"publishStatusLabel-2" => array(
		"innerHTML" => "project is unresourced"
	),
	"publishStatusTip-2" => array(
		"innerHTML" => ""
	),
	
	"publishStatus-3" => array(
		"value" => "design (in design environment)",
		"required" => "required"
	),
	"publishStatusLabel-3" => array(
		"innerHTML" => "in Design (resouced)"
	),
	"publishStatusTip-3" => array(
		"innerHTML" => ""
	),
	
	"publishStatus-4" => array(
		"value" => "coding",
		"required" => "required"
	),
	"publishStatusLabel-4" => array(
		"innerHTML" => "Coding under way"
	),
	"publishStatusTip-4" => array(
		"innerHTML" => ""
	),
	
	"publishStatus-5" => array(
		"value" => "testing",
		"required" => "required"
	),
	"publishStatusLabel-5" => array(
		"innerHTML" => "testing (in test environment)"
	),
	"publishStatusTip-5" => array(
		"innerHTML" => ""
	),
	
	"publishStatus-6" => array(
		"value" => "live",
		"required" => "required"
	),
	"publishStatusLabel-6" => array(
		"innerHTML" => "live (in production environment)"
	),
	"publishStatusTip-6" => array(
		"innerHTML" => ""
	),
	
	
	"publishStatus-7" => array(
		"value" => "retired",
		"required" => "required"
	),
	"publishStatusLabel-7" => array(
		"innerHTML" => "Formally Retired (environments are cleaned)"
	),
	"publishStatusTip-7" => array(
		"innerHTML" => ""
	),
	
	"publishStatus-8" => array(
		"value" => "Abandonware",
		"required" => "required"
	),
	"publishStatusLabel-8" => array(
		"innerHTML" => "Abandoned"
	),
	"publishStatusTip-8" => array(
		"innerHTML" => ""
	),
	
	
	"sortOrderLabel" => array(
		"innerHTML" => "Sort Order for app's position in app store grid"
	),
	"sortOrderTip" => array(
		"innerHTML" => "a +/- integer or decimal number by which to order apps in the app store (high to low)"
	),
	
	"sortOrder" => array(
		"accesskey" => "o",
		"placeholder" => "",
		"minlength" => "1",
		"maxlength" => "32",
		"pattern" => "-?\d+(\.\d+)?" /*^-?\d+(\.\d+)?$ any positive or negative integer or decimal number*/
	),
	
	"primaryClient" => array(
		"accesskey" => "c",
		"placeholder" => "",
		"minlength" => "3",
		"maxlength" => "32",
		"pattern" => "([^\p{Z}\p{C}]( {1}[^\p{Z}\p{C}])*){3,32}"
	),
	
	"primaryClientLabel" => array(
		"innerHTML" => "Primary Client"
	),
	"primaryClientTip" => array(
		"innerHTML" => "e.g. name, rank, job title of the person responsible to deliver the outcomes which are achieved with this app"
	),
	
	"primaryClientUIN" => array(
		"accesskey" => "c",
		"placeholder" => "",
		"minlength" => "3",
		"maxlength" => "32",
		"pattern" => "([^\p{Z}\p{C}]( {1}[^\p{Z}\p{C}])*){3,32}"
	),
	
	
	"primaryClientUINLabel" => array(
		"innerHTML" => "primary Client UIN"
	),
	"primaryClientUINTip" => array(
		"innerHTML" => "the UIN which pays for this app if/when we charge for it"
	),
	

	
	"protocolLabel" => array(
		"innerHTML" => "protocol"
	),
	"protocolTip" => array(
		"innerHTML" => "the protocol used to load the app's home page e.g 'http://'"
	),
	
	"protocol" => array(
		"accesskey" => "c",
		"placeholder" => "",
		"minlength" => "3",
		"maxlength" => "32",
		"pattern" => "([^\p{Z}\p{C}]( {1}[^\p{Z}\p{C}])*){3,32}"
	),
	
	
	"devDomain" => array(
		"accesskey" => "c",
		"placeholder" => "",
		"minlength" => "3",
		"maxlength" => "32",
		"pattern" => "([^\p{Z}\p{C}]( {1}[^\p{Z}\p{C}])*){3,32}"
	),
	
	
	"devDomainLabel" => array(
		"innerHTML" => "dev host server (domain)"
	),
	
	"devDomainTip" => array(
		"innerHTML" => "enter a domain e.g 'echo.dasa.r.mil.uk without a trailing slash"
	),
	
	"testDomain" => array(
		"accesskey" => "c",
		"placeholder" => "",
		"minlength" => "3",
		"maxlength" => "32",
		"pattern" => "([^\p{Z}\p{C}]( {1}[^\p{Z}\p{C}])*){3,32}"
	),
	
	
	"testDomainLabel" => array(
		"innerHTML" => "test host server (domain)"
	),
	
	"testDomainTip" => array(
		"innerHTML" => "enter a domain e.g 'echo.dasa.r.mil.uk without a trailing slash"
	),
	
	"prodDomain" => array(
		"accesskey" => "c",
		"placeholder" => "",
		"minlength" => "3",
		"maxlength" => "32",
		"pattern" => "([^\p{Z}\p{C}]( {1}[^\p{Z}\p{C}])*){3,32}"
	),
	
	
	"prodDomainLabel" => array(
		"innerHTML" => "production host server (live app's domain)"
	),
	
	"prodDomainTip" => array(
		"innerHTML" => "enter a domain e.g 'bravo.dasa.r.mil.uk without a trailing slash"
	),
	

	"appRootLabel" => array(
		"innerHTML" => "app's root folder"
	),
	
	"appRootTip" => array(
		"innerHTML" => "the top level webserver folder within DOCUMENT_ROOT. Don't use leading or trailing slashes"
	),
	
	"appRoot" => array(
		"accesskey" => "c",
		"placeholder" => "",
		"minlength" => "3",
		"maxlength" => "32",
		"pattern" => "([^\p{Z}\p{C}]( {1}[^\p{Z}\p{C}])*){3,32}"
	),
	
	"homePageLabel" => array(
		"innerHTML" => "homepage filename"
	),
	
	"homePageTip" => array(
		"innerHTML" => "just the filename (no path) of a file within the app's root folder e.g 'index.php' "
	),
	
	"homePage" => array(
		"accesskey" => "c",
		"placeholder" => "",
		"minlength" => "3",
		"maxlength" => "32",
		"pattern" => "([^\p{Z}\p{C}]( {1}[^\p{Z}\p{C}])*){3,32}"
	),
	
	"legalAndPolicyLinkLabel" => array(
		"innerHTML" => "legal & Policy link"
	),
	
	"legalAndPolicyLinkTip" => array(
		"innerHTML" => "a full URL to a page which describes any legal (eg. privacy) policy covering the app"
	),
	
	"legalAndPolicyLink" => array(
		"accesskey" => "c",
		"placeholder" => "",
		"minlength" => "3",
		"maxlength" => "32",
		"pattern" => "([^\p{Z}\p{C}]( {1}[^\p{Z}\p{C}])*){3,32}"
	),
	
	"secureAnAccountLinkLabel" => array(
		"innerHTML" => "security link"
	),
	
	"secureAnAccountLinkTip" => array(
		"innerHTML" => "a full URL to a page which allows a user to take security steps like reporting an issue or reset their password"
	),
	
	"secureAnAccountLink" => array(
		"accesskey" => "c",
		"placeholder" => "",
		"minlength" => "3",
		"maxlength" => "32",
		"pattern" => "([^\p{Z}\p{C}]( {1}[^\p{Z}\p{C}])*){3,32}"
	),
	
	
	"allowNewIncidents" => array(
		"accesskey" => "c",
		"value" => "1"
	),
	
	"allowNewIncidentsLabel" => array(
		"innerHTML" => "allow users to raise incidents?"
	),
	
	"allowNewIncidentsTip" => array(
		"innerHTML" => ""
	),
	
	"newIncidentLabel" => array(
		"innerHTML" => "Incident Link"
	),
	
	"newIncidentLinkTip" => array(
		"innerHTML" => "a full URL to a form (or instructions on how) to raise a new Incident"
	),
	
	"newIncidentLink" => array(
		"accesskey" => "l",
		"placeholder" => "",
		"minlength" => "3",
		"maxlength" => "32",
		"pattern" => "([^\p{Z}\p{C}]( {1}[^\p{Z}\p{C}])*){3,32}"
	),
	

	"allowNewRFCs" => array(
		"accesskey" => "i",
		"value" => "1"
	),
	
	"allowNewRFCsLabel" => array(
		"innerHTML" => "allow users to raise requests for change (RFCs)?"
	),
	
	"allowNewRFCsTip" => array(
		"innerHTML" => ""
	),
	
	"newRFCLinkLabel" => array(
		"innerHTML" => "RFC Link"
	),
	
	"newRFCLinkTip" => array(
		"innerHTML" => "a full URL to a form (or instructions on how) to raise a new RFC"
	),
	
	"newRFCLink" => array(
		"accesskey" => "l",
		"placeholder" => "",
		"minlength" => "3",
		"maxlength" => "32",
		"pattern" => "([^\p{Z}\p{C}]( {1}[^\p{Z}\p{C}])*){3,32}"
	),
	
	
	"allowNewRFIs" => array(
		"accesskey" => "i",
		"value" => "1"
	),

	
	"allowNewRFIsLabel" => array(
		"innerHTML" => "allow users to raise requests for information (RFIs)?"
	),
	
	"allowNewRFIsTip" => array(
		"innerHTML" => ""
	),
	
	"newRFILinkLabel" => array(
		"innerHTML" => "RFI Link"
	),
	
	"newRFILinkTip" => array(
		"innerHTML" => "a full URL to a form (or instructions on how) to raise a new RFI"
	),
	
	"newRFILink" => array(
		"accesskey" => "l",
		"placeholder" => "",
		"minlength" => "3",
		"maxlength" => "32",
		"pattern" => "([^\p{Z}\p{C}]( {1}[^\p{Z}\p{C}])*){3,32}"
	),
	
	// ********************** START: ICS, 17/12/2025 *****************
	"newIncidentLinkLabel" => array(
		"innerHTML" => "Incident Link"
	),
	
	"newIncidentLinkTip" => array(
		"innerHTML" => "a full URL to a form (or instructions on how) to raise a new Incident"
	),
	
	"newIncidentLinkLink" => array(
		"accesskey" => "l",
		"placeholder" => "",
		"minlength" => "3",
		"maxlength" => "32",
		"pattern" => "([^\p{Z}\p{C}]( {1}[^\p{Z}\p{C}])*){3,32}"
	),
	// ********************** END: ICS, 17/12/2025 *****************

	"adminEmailLabel" => array(
		"innerHTML" => "primary developer's email"
	),
	
	"adminEmailTip" => array(
		"innerHTML" => "for digital eyes only. not visible to users"
	),
	
	"adminEmail" => array(
		"accesskey" => "m",
		"placeholder" => "",
		"minlength" => "3",
		"maxlength" => "32",
		"pattern" => "([^\p{Z}\p{C}]( {1}[^\p{Z}\p{C}])*){3,32}"
	),
	
	
	"otherSMEsLabel" => array(
		"innerHTML" => "Other digital people with knowledge"
	),
	
	"otherSMEsTip" => array(
		"innerHTML" => "enter a comma-separated list of email addresses. not visible outside of digital"
	),
	
	"otherSMEs" => array(
		"accesskey" => "m",
		"placeholder" => "",
		"pattern" => "([^\p{Z}\p{C}]( {1}[^\p{Z}\p{C}])*){3,32}"
	),
		
		
	// ICS Start (16/12/2025)
	"maturityLabel" => array(
		"innerHTML" => "Maturity"
	),	
	
	"maturityTip" => array(
		"innerHTML" => "ZZ"
	),
	
	"maturity-1" => array(
		"value" => "very",
		"required" => "required"
	),
	"maturityLabel-1" => array(
		"innerHTML" => "very"
	),
	"maturityTip-1" => array(
		"innerHTML" => "very3"
	),
    // ICS End (16/12/2025)
		
	"NS_appStore_addMissingAppForm_php_formCancel" => array(
		"id" => "Cancel",
		"accesskey" => "c",
		"innerHTML" => "Cancel"
	),
	
	"NS_appStore_addMissingAppForm_php_formSave" => array(
		"id" => "Submit",
		"accesskey" => "s",
		"innerHTML" => "<span class=\"underline\">S</span>ave Changes</span>"
	),
	
	"selectCustomErrorMessageJS" => array(	
		"badInput" => 'that.setCustomValidity("badInput");',
		"rangeOverflow" => 'that.setCustomValidity("range overflow");',
		"rangeUnderflow" => 'that.setCustomValidity("range underflow");',
		"stepMismatch" => 'that.setCustomValidity("step mismatch");',
		"tooLong" => 'that.setCustomValidity("no more than "+ that.getAttribute("maxlength") +" characters please");',
		"tooShort" => 'that.setCustomValidity("no fewer than "+that.getAttribute("minlength") +" characters please");',
		"typeMismatch"=> 'that.setCustomValidity("wrong type of data entered. "+ that.getAttribute("type") +" required");',
		"valueMissing"=>'that.setCustomValidity("a value is required here");',
		"patternMismatch"=> 'that.setCustomValidity("invalid characters");'
	)

);
?>