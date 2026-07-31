<?php
namespace Biblhertz\Manifest_Server\pages;

use Biblhertz\Manifest_Server\utilities\PDODatabase;
use PDOStatement;

/********************************************************************/
/*	class.htmlPage.php                                              */
/*                                                                  */
/* generalised html page object	                                    */
/* static methods in this class do lots of simple functions 	    */
/*																	*/
/*	Author 	: 	Chris Tomlinson										*/
/*  Date	:	March 2023										    */
/********************************************************************/	

abstract class htmlPage{

/****************************************************************/
/*	STATIC VARIABLES											*/
/****************************************************************/
private static string $siteRoot="";							//the root of the site
private static string $creator="";	                        //creator of the page
private static string $imageRoot="";						//root of images
private static string $xslRoot="";							//root of xsl style sheets
private static string $cssRoot="";							//root of css style sheets
private static string $jsRoot="";							//root of javascripts

/****************************************************************/
/*	INTERFACE METHODS											*/
/****************************************************************/
public static function setSiteRoot(string $r){
	self::$siteRoot=$r;
}

public static function getSiteRoot():string{
	return self::$siteRoot;
}

public static function setCreator(string $r){
	self::$creator=$r;
}

public static function getCreator():string{
	return self::$creator;
}

public static function getParagraph(string $text):string{
	return "<p>$text</p>";
}

public static function setXSLRoot(string $r){
	self::$xslRoot=$r;
}

public static function getXSLRoot():string{
	return self::$xslRoot;
}

public static function setImageRoot(string $r){
	self::$imageRoot=$r;
}

public static function getImageRoot():string{
	return self::$imageRoot;
}

public static function setCssRoot(string $r){
	self::$cssRoot=$r;
}

public static function getCssRoot():string{
	return self::$cssRoot;
}

public static function setJSRoot(string $r){
	self::$jsRoot=$r;
}

public static function getJSRoot():string{
	return self::$jsRoot;
}

/** render text using style sheet **/
public static function getText(string $text):string{
	return "<p>$text</p>";
}
	
/** render header text for page **/
public static function getHeaderText(string $text):string{
	return "<h3>$text</h3>";
}

/****************************************************************/
/*	OTHER METHODS												*/
/* 	THESE METHODS RENDER SOME PAGE COMPONENTS					*/
/****************************************************************/

/** Make an image component
 * 
 * @param string image address
 * @param int display width
 * @param in display height
 * @param string mouse over text
 * @param string align setting
 * @param int border width
 * 
 * @return string the image
*/
public static function makeImage(string $address,int $width,int $height,string $alt="",string $align="baseline",int $border=0):string{
return "<IMG SRC=\"".$address."\" WIDTH=\"".$width."\" HEIGHT=\"".$height."\" ALT=\"".$alt."\" ALIGN=\"".$align."\" BORDER=\"".$border."\"></IMG>\n";
}

/** Make a http link 
 * 
 * @param string link address
 * @param string display text
 * @param string targetr window name if used
 * 
 * @return string the link 
*/
public static function makeLink(string $address,string $text,string $target=""):string
	{
	if($target=="")return "<A HREF=\"".$address."\">".$text."</A>";
	else return "<A HREF=\"".$address."\" TARGET=\"".$target."\">".$text."</A>";
	}

/****************************************************************/
/*	FORM METHODS												*/
/* 	methods for rendering form components						*/
/* in html pages												*/
/****************************************************************/

/** make Form Head 
 * 
 * @param string form action
 * @param string form method
 * @param string form anme
 * 
 * @return string the form
 **/
public static function makeFormHead(string $action,string $method="POST",string $name=""):string{
	if(!empty($name))return "<FORM NAME=$name ACTION=".$action." METHOD=".$method.">";
	else return "<FORM ACTION=".$action." METHOD=".$method.">";
}

/** make Form Foot 
 * 
 * @return string form foot tag
 */
public static function makeFormFoot():string{
	return "</FORM>";
}

/** make form Input form component
 * 
 * @param string hidden input name
 * @param int size of input (num chars)
 * @param string type of input
 * @param int max length
 * @param string value of input
 * 
 * @return string the hidden input
*/
public static function makeInput(string $name,int $size,string $type="EDIT",int $maxlength=0,string $text=""):string{
	return "<INPUT NAME=\"".$name."\" ID=\"".$name."\" TYPE=\"".$type."\" SIZE=".($maxlength?$maxlength:$size)." MAXLENGTH=".$size." VALUE=\"".$text."\">";
	}

/** make Hidden Input form component
 * 
 * @param string hidden input name
 * @param string hidden input value
 * 
 * @return string the hidden input
*/
public static function makeHiddenInput(string $name,string $value):string{
	return "<INPUT NAME=\"".$name."\" ID=\"".$name."\" TYPE=\"HIDDEN\" VALUE=\"".$value."\">";
}

/** make text area form component
 * 
  * @param string hidden input name
 *  @param int umber of rows
 *  @param int number of columns
 *  @param string value in text area
 *  @param bool readonly
 * 
 * @return string the text area
 * **/
public static function makeTextArea(string $name,int $rows,int $cols,string $value="",bool $readonly=false):string{
	if($readonly)$read="readonly";
	else $read="";
	return "<TEXTAREA NAME=\"".$name."\" ID=\"".$name."\" ROWS=\"".$rows."\" COLS=".$cols."\"  $read>".$value."</TEXTAREA>";
}

/** make button form component 
 * 
 * @param string name
 * @param string text on button
 * @param string button type
 * @param mixed onClick java script action, false for nothing
 * 
 * @return string the button
 * **/
public static function makeButton(string $name,string $text,string $type="SUBMIT",mixed $onclick=0):string{
	if(!$onclick)return "<INPUT class=\"btn btn-primary btn-sm\" NAME=\"".$name."\" TYPE=\"".$type."\" VALUE=\"".$text."\">";
	else return "<INPUT class=\"btn btn-primary btn-sm\" NAME=\"".$name."\" TYPE=\"".$type."\" VALUE=\"".$text."\" ONCLICK=\"".$onclick."\">";
}

/** make radio button form component 
 * 
 * @param string button name
 * @param string value of radio button
 * @param string checked
 * 
 * 
 * @return string radio button
 * **/
public static function makeRadioButton(string $name,string $value,string $checked=""):string{
	return "<INPUT NAME=\"".$name."\" TYPE=\"RADIO\" VALUE=\"".$value."\" ".($checked?"CHECKED":"").">";
}

/** make check box form component 
 * 
 * @param string button name
 * @param string value of radio button
 * @param string checked
 * @param string onclick java script action 
 * 
 * 
 * @return string check box
 * **/
public static function makeCheckBox(string $name,string $value,string $checked="",string $onclick=""):string{
	return "<INPUT NAME=\"".$name."\" id=\"".$name."\" TYPE=\"CHECKBOX\" VALUE=\"".$value."\" ".($checked?"CHECKED":"").($onclick?" ONCLICK=\"".$onclick."\"":"").">";
}


/** make pull down option component from a result set object
*  @param string name of option box
*  @param PDOStatement resultSet object
*  @param string name of key in result set
*  @param string name of display text field in result set
*  @param string selected value if needed
* 
*  @return string the pulldown option
**/
public static function makeOption(string $name,PDOStatement $resultSet,string $key,string $display,mixed $selected=0):string{
		$returnString="<SELECT NAME=".$name." ID=".$name.">";
		while($row = $resultSet->fetch())
			{
			$returnString=$returnString."<OPTION VALUE=";
			$returnString=$returnString.$row[$key];
			if($row[$key]==$selected)$returnString=$returnString." SELECTED";
			$returnString=$returnString.">";
			$returnString=$returnString.$row[$display]."</OPTION>";
			}
 		$returnString=$returnString."</SELECT>";
		return $returnString;
}

/** make multiple option component from a result set object
*  @param string name of option box
*  @param PDOStatement resultSet object
*  @param string name of key in result set
*  @param string name of display text field in result set
*  @param int size of multiple option
* 
*  @return string the pulldown option
**/
public static function makeOptionMutiple(string $name,PDOStatement $resultSet,string $key,string $display,int $size):string{
		$returnString="<SELECT NAME=\"".$name."\" SIZE=\"".$size."\" MULTIPLE>";
		while($row = $resultSet->fetch())
			{
				
			$returnString=$returnString."<OPTION VALUE=\"";
			$returnString=$returnString.$row[$key]."\"";
			$returnString=$returnString.">";
			$returnString=$returnString.$row[$display]."</OPTION>";
			}
 		$returnString=$returnString."</SELECT>";
		return $returnString;
}


/** make pull down option form component 
*	use 2D array as input this time, column one contains the keys, column 2 contains the display text
* 
* @param string name of component
* @param array 2D array containing values
* @param mixed value selected item or 0
* @param mixed javascript to run on change if required
*
* @return string the component
**/
public static function makeOptionFromArray(string $name,array $arr,mixed $selected=0,mixed $onChange=0):string{
		if($onChange)$ocs="onChange=$onChange";else $ocs="";
		$returnString="<SELECT NAME=\"".$name."\" id=\"".$name."\" $ocs>";
		$n=count($arr);
		$c=0;
		while($c<$n){
			$returnString.="<OPTION VALUE=\"";
			$returnString.=$arr[$c][0]."\"";
			if($arr[$c][0]==$selected)$returnString.=" SELECTED";
			$returnString.=">";
			$returnString.=$arr[$c][1];
			$returnString.="</OPTION>";
			$c++;
			}
 		$returnString.="</SELECT>";
		return $returnString;
}


/****************************************************************/
/*	UTILITY METHODS												*/
/****************************************************************/

/** get current time
 * 
 * @return string date
 */
public static function getTime():string{
	date_default_timezone_set('UTC');
	return date('H:i:s',mkTime(date("H"),date("i"),date("s"),date("m"),date("d"),date("Y")));
}

/** get current date
 * 
 * @return string today's date
 */
public static function getToday():string{
	return date('jS M Y',mkTime(0,0,0,date("m"),date("d"),date("Y")));
}

/** get date in sql format
 * 
 * @param int days
 * @param int months
 * @param int years
 * 
 * @return string date in sql format
 */
public static function getSQLDate(int $days,int $months,int $years):string{
	return $years."-".($months<10?"0".$months:$months)."-".($days<10?"0".$days:$days);
}

/** get today's date in sql format
 * 
 * @return string today's date in sql format
 */
public static function getTodayAsSQLDate():string{
	return date('Y-m-d',mkTime(0,0,0,date("m"),date("d"),date("Y")));
}

/** get now as timrstamp
 * 
 * @return string get now
 */
public static function getNowAsSQLTimeStamp():string{
	return date("Y-m-d H:i:s");
}

/** get formatted date object from an SQL date 
 * 
 * @param string the date in sql format
 * 
 * @return string date formated in shortened format
 * **/
public static function getShortDateFromSQL(string $date):string{
	 $parts=explode("-",$date);
	 //var_dump($parts);
	 return date('j/m/y',mkTime(0,0,0,$parts[1],$parts[2],$parts[0]));
}

/** get formatted date object from an SQL date
 * 
 * @param string date in sql format
 * 
 * @return string the formateted date
 */
public static function getDateFromSQL(string $date):string{
	 $parts=explode("-",$date);
	 //var_dump($parts);
	 if(count($parts)<3)return "";
	 return date('jS F Y',mkTime(0,0,0,$parts[1],$parts[2],$parts[0]));
}


/** get SQL formatted date object from a slash format date
 * 
 * @param string the slash date
 * 
 * @return string the return date
 */
public static function getSQLDateFromSlashFormat(string $date):string{
	 $parts=explode("/",$date);
	 //var_dump($parts);
	 return $parts[2]."-".$parts[1]."-".$parts[0];
}


/** 
 * get timestamp as an array of time and date 
 * 
 * @param string timestamp
 * 
 * 
 * @return array date and time
 */
public static function getTimeStampAsDateTimeArray(string $timestamp):array{
	$timestamp = strtotime($timestamp);
	$date = date('d-m-Y', $timestamp);
	$time = date('G:i:s', $timestamp);
	return array($date,$time);
}

	
	/**
	 * is an email valid
	 * 
	 * @param string email address
	 * 
	 * @return bool true if email is valid
	 */
	public static function  isValidEmail(string $email):bool{
 		if (!filter_var($email, FILTER_VALIDATE_EMAIL))return false;
		return true;
	}
	
	/**
	 * get a Random string that represents a password
	 * @param minimum length
	 * @param maximum length
	 *
	 * @return a random string
	 */
	public static function getRandomPassword($min,$max){
		$chars=array(	'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z',
					'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
					'1','2','3','4','5','6','7','8','9','0');
		$str="";
		for($i=0;$i<($min+rand(0,($max-$min)));$i++)
			{
			$ind=rand(0,count($chars)-1);
			$str.=$chars[$ind];
			//echo $ind." :: ".$chars[$ind]." :: ".$str."<br>";
			}
		
		return $str;
	}

	
	/**
	 * DEALING WITH ENUMS
	 * get option pull down from database enuim fields
	 * 
	 * @param PDODatabase database handle
	 * @param string component name
	 * @param string table name
	 * @param string enum field name
	 * @param mixed selected field
	 * 
	 * @return string the component
	 */
 	public static function getEnumAsPullDown(PDODatabase $objDB, string $name,string $table,string $field,mixed $selected=0):string{
		$vals=htmlPage::getEnumVals($objDB, $table,$field);
		$opts=array();$c=0;
		foreach($vals as $val){
			$opts[$c][0]=$opts[$c][1]=$val;
			$c++;
		}
		return htmlPage::makeOptionFromArray($name,$opts,$selected);
	}
	
	/**
	 * get field values from a mysql enum field
	 * 
	 * @param PDODatabase database handle
	 * @param string table name
	 * @param string field name
	 * 
	 * @return array values as ana array
	 */ 
	public static function getEnumVals(PDODatabase $objDB, string $table ,string $field ):array{
		$sql = " SHOW COLUMNS FROM $table LIKE '$field'";
		$fields=$objDB->select($sql);
		$row = $fields->fetch();
		#extract the values
		#the values are enclosed in single quotes
		#and separated by commas
		//echo $row[1];
		$regex = "/'(.*?)'/";
		preg_match_all( $regex , $row["Type"], $enum_array );
		$enum_fields = $enum_array[1];
		$vals=array();
		foreach($enum_fields as $enum){
			array_push($vals,str_replace("'","",$enum));
		}
		return( $vals );
	} 
	
}

