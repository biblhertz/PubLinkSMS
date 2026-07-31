<?php
namespace Biblhertz\Manifest_Server\api;


/********************************************************************/
/*		REPRESENTATION OF BASE CONTROLLER							*/
/*																	*/
/*																	*/
/********************************************************************/

class ApiUser
{


/********************************************************************/
/*		INSTANCE VARIABLES											*/
/********************************************************************/
private string $serviceURL="";
private string $payload="";
private string $username="";
private string $password="";
private string $apiKey="";
private bool $authenticate=false;    //authenticate this request?

/********************************************************************/
/*		INTERFACE METHODS											*/
/********************************************************************/
public function setServiceURL(string $service){
    $this->serviceURL=$service;
}

public function setPayload(array $payload){
    $this->payload=json_encode($payload);
}

public function setCredentials(string $un, string $pw){
    $this->authenticate=true;
    $this->username=$un;
    $this->password=$pw;
}

public function setApiKey(string $key){
    $this->authenticate=true;
    $this->apiKey=$key;
}




/** 
* post message. 
* 
* @return string output from post message
*/
public function postMessage():string{
    $curl = curl_init();

    curl_setopt($curl,CURLOPT_URL,$this->serviceURL);
    error_log("Trying :: ".$this->serviceURL);
    
    if($this->authenticate){
        if($this->apiKey !== ''){
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['X-API-Key: '.$this->apiKey]);
        } else {
            curl_setopt($curl, CURLOPT_USERPWD, $this->username.":".$this->password);
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        }
    }

    curl_setopt($curl, CURLOPT_POST, 1);
   
    // Should cURL return or print out the data? (true = return, false = print)
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    //include the payload
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array("data"=>$this->payload)));
    curl_setopt($curl, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
    $output = curl_exec($curl);
    
    error_log("Output :: ".$output);
    curl_close($curl);
    return $output;
}
   

/********************************************************************/
/*		STATIC METHODS											    */
/********************************************************************/

/**
 * get error list from request
 * 
 * @param array json errors
 * @param string header
 * 
 * @return mixed html formatted error list or false
 * 
 */

public static function getErrorList(mixed $json, string $header):mixed{
    $error=false;
    $content="<div><hr/><h3>$header</h3><ul>";
    if(is_array($json)&&isset($json['error'])&&strcmp("",$json['error'])){   
             $content.="<li>".$json['error']."</li>";
             $error=true;
        }
    if(isset($json['errorList'])&&count($json['errorList'])){
            foreach($json['errorList'] as  $error){
                $content.="<li>".$error['title']." :: ".$error['detail']."</li>";
                $error=true;
            }
    }

    if($error)return $content."</ul></div>";
    return false;
}


}