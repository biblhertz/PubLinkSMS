<?php
namespace Biblhertz\Manifest_Server\pages;

use Biblhertz\Manifest_Server\pages\htmlPage;
use Biblhertz\Manifest_Server\Config;
use Biblhertz\Manifest_Server\utilities\Encryption;

/********************************************************************/
/*		REPRESENTATION OF PAGE FOR Bibliotheca                      */
/*                                                                  */
/*		Author 	: 	Chris Tomlinson                             	*/
/*      Date	:	March 2023                              	    */
/*                                                                  */
/********************************************************************/

class Manifest_Server_Page extends htmlPage{

 	/****************************************************************/
	/*	INSTANCE VARIABLES											*/
	/****************************************************************/
	 private string $centralContent="";						//content of central part of the page
	 private string $longTitle="Max Planck Institute for Art History : Manifest_Server";	//title of this page
	 private string $title="Bibliotheca Hertziana : Manifest_Server";	//long title used in text
	 private string $shortTitle="BH Manifest_Server";	                //short title used in text
	 private string $heading="";									//heading text of this page
	 
	 protected string $errorMessage="";						   //error message slot
	
	
	
	/****************************************************************/
	/*	CONSTANTS													*/
	/****************************************************************/
	 
	
	/****************************************************************/
	/*	CLASS CONSTRUCTOR											*/
	/****************************************************************/
 	public function __construct(){
 		date_default_timezone_set('UTC');
		Config::setup(); //set environment variables
 	}
 
 	/****************************************************************/
	/*	INTERFACE METHODS											*/
	/****************************************************************/
	public function setErrorMessage(string $s){
		$this->errorMessage=$s;
	}

	public function getErrorMessage():string{
		return $this->errorMessage;
	}

	public function setCentralContent(string $s){
		$this->centralContent=$s;
	}
	
	public function getCentralContent():string {
		return $this->centralContent;
	}
	
	public function setTitle(string $s){
		$this->title=$s;
	}
	
	public function getTitle():string{
		return $this->title;
	}
	
	public function getLongTitle():string{
		return $this->longTitle;
	}
	
	public function getShortTitle():string{
	    return $this->shortTitle;
	}
	
	public function setHeading(string $s){
		$this->heading=$s;
	}
	
	public function getHeading():string{
		return ($this->heading);
	}

	
	public function getLogo():string{
	    return "<img src=\"".Manifest_Server_Page::getImageRoot().Config::$LOGO."\" width=256 height=55 />";
	}

	
	/****************************************************************/
	/*	OTHER METHODS												*/
	/****************************************************************/
	public function handleException(Error $e){
		$this->setHeading("!! An Error has occurred");
		$this->setCentralContent($e->getMessage());
		echo $this->getPage();
		exit;
	}
	
 
 	
/****************************************************************/
/*	UTILITY METHODS												*/
/****************************************************************/

/****************************************************************/
/*	MISC METHODS												*/
/****************************************************************/

	/****************************************************************/
	/*	PAGE TEMPLATING ITEMS										*/
	/*  Methods related to page templates - html markup is here		*/
	/****************************************************************/
	/**	
	RENDER THE PAGE TEMPLATE WITH THE CONTENT INCLUDED 
	THIS IS EXECUTED BY THE CALLING SCRIPT USING ECHO (OR PRINT) TO RENDER
	A PAGE FOR RETURN TO THE CLIENT BROWSER
	**/
	
	

public function getPage(){
		
	return "<?xml version=\"1.0\" encoding=\"windows-1252\"?><!DOCTYPE html SYSTEM \"about:legacy-compat\">
		<html lang=\"en\" xml:lang=\"en\"  xmlns=\"http://www.w3.org/1999/xhtml\">
		<head>
		<title>".$this->getTitle()."</title>
		<link rel=\"shortcut icon\" type=\"image/png\" href=\"https://foto.biblhertz.it/images/favicon.png\" />
		<link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">
		<link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>
		<link href=\"https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap\" rel=\"stylesheet\">
		<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/bootstrap.min.css\" />
		<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/customer.css\" />
		<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/tables.css\" />
		<script src=\"/js/jquery.min.js\"></script>
		<script src=\"/js/bootstrap.min.js\"></script>
		<script src=\"/js/bootstrap.bundle.min.js\"></script>


		</head><body>
		<div style=\"height:0px;width:0px;overflow:hidden;\" xmlns=\"\">
		<span class=\"fa fa-spinner\">font-awesome-load</span>
		</div>
		<div style=\"height:0px;width:0px;overflow:hidden;\" xmlns=\"\">
		<span class=\"mdi mdi-loading\">material-design-icons-load</span>
		</div>
		<div class=\"wrapper\">
		<!--fragment inserted, id=Header-->
		<header id=\"header\">
		<div class=\"header-top\">
		<div class=\"logo\">
		<a href=\"https://biblhertz.it/\">Bibliotheca Hertziana – Max Planck Institute for Art History</a>
		</div>

		<section id=\"section\">
		<div id=\"section_left\"></div>
		<div>
		<h3>".$this->getHeading()."</h3>

		<div style=\"width: 800px;\">".$this->getCentralContent()."</div>
		</div>
		</section>
		</header>

		<footer id=\"footer\">
		</footer>
		</div>
		</body>
		</html>";

}


}
?>
