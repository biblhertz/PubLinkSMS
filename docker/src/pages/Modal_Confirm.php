<?php
namespace Biblhertz\Publink\pages;

/********************************************************************/
/*		REPRESENTATION OF Modal Dialog FOR Tissue Bank WEBSITE      */
/*                                                                  */
/*		Author 	: 	Chris Tomlinson                             	*/
/*      Date	:	4th Jamuary 2021                               	*/
/*                                                                  */
/********************************************************************/

class Modal_Confirm {

	private $page=null;			//ICHTB_Content_Page that this modal will be inserted into
	private $name=null;			//name of component, referenced from JS
	private $message="";		//message to be displayed in dialog
	private $okAddress="";		//address that will be forwarded if ok is pressed
 	
	/****************************************************************/
	/*	CLASS CONSTRUCTOR											*/
	/****************************************************************/
 	public function __construct($page,$name,$message){
 		$this->page=$page;
 		$this->name=$name;
 		$this->message=$message;
 	}
 
	
 	public function setPage($p){
 		$this->page=$p;
 	}
 	
 	public function setName($name){
 		$this->name=$name;
 	}
 	
	public function setMessage($message){
 		$this->message=$message;
 	}
 	
 	public function setOKAddress($add){
 		$this->okAddress=$add;
 	}
 	
	/****************************************************************/
	/*	PAGE TEMPLATING ITEMS										*/
	/*  Methods related to page templates - html markup is here		*/
	/****************************************************************/
	
	public function getConfirmMessageBody(){
 			return "
		<!-- Modal -->
		  <div class=\"modal fade\" id=\"".$this->name."\">
		    <div class=\"modal-dialog\">
		      <!-- Modal content-->
		      <div class=\"modal-content\">
		        <div class=\"modal-header\">
		          ".$this->page->getLogo()."
                  <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">
                     	<span aria-hidden=\"true\">&times;</span>
                   </button>
		        </div>
		        <div class=\"modal-body\">
		          <p>".$this->message."</p>
		        </div>
		        <div class=\"modal-footer\">
		          <button id=\"".$this->name."_ok\" type=\"button ok\" class=\"btn btn-primary btn-flat pull-left\" data-dismiss=\"modal\">OK</button>
		          <button id=\"".$this->name."_cancel\" type=\"button cancel\" class=\"btn btn-primary btn-flat pull-right\" data-dismiss=\"modal\">Cancel</button>
		        </div>
		      </div>
		      
		    </div>
		  </div>";
 			
 	}
 	
 	

 	
 	public function getJavaScript(){
 		return "<script type=\"text/javascript\">function ".$this->name."_func(){
												$('#".$this->name."').modal({
	    			 								backdrop: 'static',
	    											keyboard: false
												}, 'show'
	    											);		
	        		 							}
	        		 							
	        		 							$( document ).on('click', '#".$this->name."_ok', function() {location.href = \"".$this->okAddress."\";});
	        		 							
	        		 							</script>";
 	}
 	
 	
	public function getNonForwardJavaScript($script){
 		return "<script type=\"text/javascript\">
						function ".$this->name."_func(){
												$('#".$this->name."').modal({
	    			 								backdrop: 'static',
	    											keyboard: false
												}, 'show'
	    											);		
	        		 							}
												
	        		 							
	        		 							$( document ).on('click', '#".$this->name."_ok', function(){ $script; });
	        		 							
	        		 							</script>";
 	}
 	
 	
 	/**
 	 * You must call this method for the dialog to work as this inserts it into the page
 	 * normal use will be; instantiate; set OK address (action on OK press); setConfirmDialog
 	 */
 	public function setConfirmDialog(){
 		$this->page->addToModalHead($this->getJavaScript());
 		$this->page->addToOtherModalMessage($this->getConfirmMessageBody());
 	}
 	
 	
	private function getReturnTrueFalseJS($formID){
 		return "<script type=\"text/javascript\">function ".$this->name."_func(){
												$('#".$this->name."').modal({
	    			 								backdrop: 'static',
	    											keyboard: false
												}, 'show'
	    											);		
	        		 							}
	        		 							
	        		 							var ".$this->name."_submit=false;
	        		 							$( document ).on('click', '#".$this->name."_ok', function()  {   ".$this->name."_submit=true; $('#$formID').submit();});
	        		 							$( document ).on('click', '#".$this->name."_cancel', function() {return false;});
	        		 							
	        		 							$(document).on('submit','#$formID', function( event ) {
	  												if(".$this->name."_submit==false)event.preventDefault();
												});
	        		 							
	        		 							</script>";
 	}
 	
	/**
 	 * You must call this method for the dialog to work as a true / false returning dialog
 	 * this inserts it into the page
 	 * normal use will be; instantiate; setConfirmDialog
 	 */
 	public function setConfirmTrueFalseDialog($formID){
 		$this->page->addToModalHead($this->getReturnTrueFalseJS($formID));
 		$this->page->addToOtherModalMessage($this->getConfirmMessageBody());
 	}

}
?>
