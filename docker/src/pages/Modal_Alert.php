<?php
namespace Biblhertz\Manifest_Server\pages;

/********************************************************************/
/*		REPRESENTATION OF Modal Dialog FOR BH Publink		        */
/*                                                                  */
/*		Author 	: 	Chris Tomlinson                             	*/
/*      Date	:	March 2023                               	    */
/*                                                                  */
/********************************************************************/

class Modal_Alert {

	private $page=null;			//Page that this modal will be inserted into
	private $name=null;			//name of component, referenced from JS
	private $message="";		//message to be displayed in dialog
	private $onPageLoad=false;  //set to true if you want modal to appear on page load
	
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
 	
 	public function setOnPageLoad($bool){
 	    $this->onPageLoad=$bool;
 	}
 	
	/****************************************************************/
	/*	PAGE TEMPLATING ITEMS										*/
	/*  Methods related to page templates - html markup is here		*/
	/****************************************************************/
	
	
 	
 	private function getConfirmMessageBody(){
 	    return "
        <div class=\"modal fade\" id=\"".$this->name."\" role=\"dialog\">
         	<div class=\"modal-dialog\">
             	<div class=\"modal-content\">
                 	<div class=\"modal-header\">"
                        .$this->page->getLogo()."
                     	<button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">
                     	<span aria-hidden=\"true\">&times;</span>
                     	</button>
                 	</div>
                 	<div class=\"modal-body\" id=\"".$this->name."_body\">
                     	<p>".$this->message."</p>
                     	</div>
                 	</div>
             	<!-- /.modal-content -->
             </div>
             <!-- /.modal-dialog -->
         </div>
         <!-- /.modal -->";
 	}
 	
 	private function getJavaScript(){
 		$str="<script type=\"text/javascript\">function ".$this->name."_func(){
												$('#".$this->name."').modal({
	    			 								backdrop: 'static',
	    											keyboard: false
												}, 'show'
	    											);		
	        		 							}";
 		if($this->onPageLoad){
 		    $str.=" $(document).ready(function(){".
                    $this->name."_func();
                });";
 		    }
	    $str.="</script>";
	    
	    return $str;
 	}
 	
 	
 	/**
 	 * You must call this method for the dialog to work as this inserts it into the page
 	 * normal use will be; instantiate; set OK address (action on OK press); setConfirmDialog
 	 */
 	public function setConfirmDialog(){
 		$this->page->addToModalHead($this->getJavaScript());
 		$this->page->addToModalMessage($this->getConfirmMessageBody());
 	}
 	
 	
	

}
?>
