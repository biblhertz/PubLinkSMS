<?php
namespace Biblhertz\Manifest_Server\api;

use Biblhertz\Manifest_Server\api\BaseController;
use Biblhertz\Manifest_Server\api\IIIFValidator;
use Biblhertz\Manifest_Server\Config;



/********************************************************************/
/*		REPRESENTATION OF Manifest_Server ANNOTATION CONTROLLER		*/
/********************************************************************/

class ApiController extends BaseController{

/********************************************************************/
/*		INSTANCE VARIABLES											*/
/********************************************************************/
private string $queryStringParams="";
private string $responseText="";
private string $responseHeader="";
private bool $error=false;

/****************************************************************/
/*	CLASS CONSTRUCTOR											*/
/****************************************************************/
public function __construct(){
 	Config::setup(); //set environment variables from config 
}

/********************************************************************/
/*		STATIC VARIABLES								            */
/********************************************************************/

/** allowed endpoints that this API will handle */
public static $allowedEndPoints=array("putManifest","removeManifest");

/********************************************************************/
/*		INTERFACE METHODS											*/
/********************************************************************/


/********************************************************************/
/*		UTILITY METHODS	 - called from endpoints					*/
/********************************************************************/

/**
 * set internal error message
 * 
 * @param Error the error
 * 
 */
private function setInternalErrorMessage(Error $e){
    $this->responseText = $e->getMessage().'Something went wrong! Please contact support.';
    $this->responseHeader = 'HTTP/1.1 500 Internal Server Error';
    $this->error=true;
}


/**
 * if method is not supported
 */
private function setMethodNotSupportedMessage(){
    $this->responseText = 'Method not supported';
    $this->responseHeader = 'HTTP/1.1 422 Unprocessable Entity';
    $this->error=true;
}


/**
 * send response
 * 
 * @param string json encoded data to send
 * 
 */
private function sendResponse(string $data){
    if (!$this->error) {
        $this->sendOutput(
            $data,
            array('Content-Type: application/json', 'HTTP/1.1 200 OK')
        );
    } else {
        $this->sendOutput(
            json_encode(array('error' => $this->responseText)), 
            array('Content-Type: application/json', $this->responseHeader)
        );
    }
}



/********************************************************************/
/*		ENDPOINTS								                    */
/********************************************************************/

/********************************************************************/
/*		/putManifest								                */
/********************************************************************/

/**
 * put manifest action
 * 
 * Method called when API method is called
 */
public function putManifestAction(){
        $strErrorDesc = '';
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $responseData=array();
       
        if (strtoupper($requestMethod) == 'GET') {
            try {
                $responseData=json_encode(array("success"=>false,
                                                "message"=>"Error : GET is not supported for putManifest ::  must use POST",
                                                "timestamp"=>date('c')
                                            ));
            } catch (Error $e) {
                $this->setInternalErrorMessage($e);
            }
        } 
        else if (strtoupper($requestMethod) == 'POST') {
            try {
                $body = $_POST['data'];
                $decoded=json_decode($body,true);

                //add .json extension to file name if it doesn't already exist
                $parts=explode(".",$decoded['manifest_name']);
                if(strcmp($parts[count($parts)-1],"json"))$decoded['manifest_name'].=".json";
                
                /**
                 * validate incoming data
                 */
                $responseData=self::parseInput($decoded);
                //if validation fails
                if(count($responseData)){
                    $responseData['success']=false;
                    $responseData['message']="Validation error(s)";
                    $responseData['timestamp']=date('c');
                    $responseData['data_received']=$decoded;
                    $responseData=json_encode($responseData,true);
                 }
                //else store the manifest
                else {
                    //manifest must be stored for validation to take place
                    $responseData=self::storeAsManifest($decoded);
                    //validate the manifest
                    $url=$responseData['url'];
                    if(Config::isRemoteDocker() || Config::isLocalDocker()){
                        $url=$responseData['internal_url'];
                        unset($responseData['internal_url']);
                    }
                    error_log("Config is Remote :: ".Config::isRemoteDocker());
                    error_log("URL is set to $url before calling validator");

                    //now validate the manifest using IIIF validator
                    //this is running in a seperate container in docker 
                    //internal url above ids the internal network address of the validator
                    $valid=self::validateManifest($url);
                    //add validation result to response
                    $responseData['iiif_validation_result']=json_decode($valid,true); 

                    //set json response
                    $responseData=json_encode($responseData,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                }
 
            } catch (Error $e) {
                $this->setInternalErrorMessage($e);
            }
        }
        else {
           $this->setMethodNotSupportedMessage();
        }
        // send output 
       $this->sendResponse($responseData);
    }


/********************************************************************/
/*		HELPER METHODS								                */
/********************************************************************/

/**
 * isValidSeries
 * 
 * @return bool true if valid series or false
 */
private static function isValidSeries(string $series):bool {
        return in_array($series, Config::$validSeries);
    }

/**
 * isValidVolume
 * 
 * @return bool if is valid volume or false
 */
private static function isValidVolume(string $volume) {
        return preg_match('/^\d{2}$/', $volume);
    }

/**
* parseInput received
* return array contains errors encountered
* empty return array means that validation is successful
* 
* @param array input
* 
* @return array
*/
public static function parseInput(array $decoded){
    $responseData=array();
     if(!isset($decoded['series'])||!strcmp($decoded['series'],""))
            $responseData["errorList"][]=["title"=>"Series Error","detail"=>"Error : the series field was not found in the attached json"];
    else if(!self::isValidSeries($decoded['series'])){
            $str="";
            foreach(Config::$validSeries as $series){
                        $str.="$series, ";
            }
            $str=substr($str,0,strlen($str)-2);
            $responseData["errorList"][]=["title"=>"Series Error","detail"=>"Error : the series field given is not one of the set [$str]"];
        }
    if(!isset($decoded['volume'])||!strcmp($decoded['volume'],""))
            $responseData["errorList"][]=["title"=>"Volume Error","detail"=>"Error : the volume field not found in the attached json"];
    else if(!self::isValidVolume($decoded['volume']))
            $responseData["errorList"][]=["title"=>"Volume Error","detail"=>"Error : the volume field is not in the correct format it should be :: NN"];
    if(!isset($decoded['manifest_name'])||!strcmp($decoded['manifest_name'],""))
            $responseData["errorList"][]=["title"=>"Manifest Name","detail"=>"Error : the manifest_name field not found in the attached json"];
    if(!isset($decoded['manifest'])||!strcmp($decoded['manifest'],""))
            $responseData["errorList"][]=["title"=>"Manifest Error","detail"=>"Error : the manifest field not found in the attached json"];
    if(!$decoded['ignore_mismatch']&&isset($decoded['manifest'])&&strcmp($decoded['manifest'],"")){
            $manifest=json_decode($decoded['manifest'],true);
            if(isset($manifest['id'])){
                if(strcmp(self::getManifestURL($decoded),$manifest['id']))
                    $responseData["errorList"][]=["title"=>"Manifest Error","detail"=>"Error :: Manifest URL and id URL (given inside manifest) do not match"];
                }
                else $responseData["errorList"][]=["title"=>"Manifest Error","detail"=>"Error :: ID field is missing from the manifest"];
         }
    if(!$decoded['ignore_overwrite']&&file_exists(self::getManifestPath($decoded))){
        $responseData["errorList"][]=["title"=>"Manifest Error","detail"=>"Manifest File Already Exists in Filestore"];
    }

    //$responseData=self::parseCredentials($decoded,$responseData);
    return $responseData;
    }


    /**private static function parseCredentials($decoded,$responseData){
         if(!isset($decoded['username'])||!strcmp($decoded['username'],""))
                    $responseData["errorList"][]=["title"=>"Username Error","detail"=>"Error : the username is not set"];
          if(!isset($decoded['password'])||!strcmp($decoded['password'],""))
                    $responseData["errorList"][]=["title"=>"Password Error","detail"=>"Error : the password field is not set"];
        
          return $responseData;
    }**/

    /**
     * get Manifest URL
     * 
     * @param array decoded params
     * 
     * @return string
     */
    private static function getManifestURL(array $decoded):string{
       return Config::$FILE_STORE_URL."/".$decoded['series']."/".$decoded['volume']."/".$decoded['manifest_name'];
    }

    /**
     * get Manifest URL
     * 
     * @param array decoded params
     * 
     * @return string
     */
    private static function getStoreDirectory(array $decoded):string{
         return Config::$FILE_STORE_PATH.DIRECTORY_SEPARATOR.$decoded['series'].DIRECTORY_SEPARATOR.$decoded['volume'];
    }

    /**
     * get Manifest Path
     * 
     * @param array decoded params
     * 
     * @return string
     */
    private static function getManifestPath(array $decoded):string{
        return self::getStoreDirectory($decoded).DIRECTORY_SEPARATOR.$decoded['manifest_name'];
    }

    /**
     * Store Manifest in file store
     * 
     * @param array decoded input data
     * 
     * @return array response data
     */
    public static function storeAsManifest(array $decoded):array{
        $responseData=array();
        $dir=self::getStoreDirectory($decoded);
        if (!file_exists($dir)) {
            $oldmask = umask(0);
            mkdir($dir, 0775, true);
            umask($oldmask);
        }

        $fullPath=self::getManifestPath($decoded);
        $exists=file_exists($fullPath);
        $myfile = fopen($fullPath, "w");
        fwrite($myfile,trim($decoded['manifest']));
        fclose($myfile);
        chmod($fullPath, 0664);

        $responseData['success']=true;
        if(!$exists)$responseData['message']="IIIF manifest stored successfully : new file created";
        else $responseData['message']="IIIF manifest stored successfully : existing file overwritten";
        $responseData['url']=self::getManifestURL($decoded);
        $responseData['timestamp']=date('c');
        $responseData["size"]=filesize($fullPath);

        //remote docker setting for internal network validation
        if(Config::isRemoteDocker()||Config::isLocalDocker()){
            $responseData['internal_url']=Config::$INTERNAL_FILE_STORE_URL."/".
                                            $decoded['series']."/".
                                            $decoded['volume']."/".
                                            $decoded['manifest_name'];
        }

                    
        return $responseData;
    }

    /**
     * Validate the manifest using iiif manifest validation service
     * 
     * @param string manifest
     * 
     * @return mixed response
     */
    public static function validateManifest(string $manifest):mixed{
        error_log("Manifest :: $manifest");
        $query = "&url=$manifest&accept=true";
        $url=Config::$IIIF_VALIDATOR.$query;
        
        //echo $url."<hr/>";
        error_log($url);
        // Build API URL
        // Make the request
        $curl = curl_init();
        curl_setopt($curl,CURLOPT_URL,$url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPGET, 1);
        // Include header in result? (0 = yes, 1 = no)
        //curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
        
       
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($httpCode !== 200) {
            //throw new Exception("HTTP Error: " . $httpCode);
        }

       
        //echo $url."<hr/>";
        return $response;
    }


/********************************************************************/
/*		/removeManifest								                */
/********************************************************************/
public function removeManifestAction(){
        $strErrorDesc = '';
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $responseData=array();

        //echo "Remove Manifest Action\n";
       
        if (strtoupper($requestMethod) == 'GET') {
            try {
                $responseData=json_encode(array("success"=>false, 
                                                "message"=>"Error : GET is not supported for removeManifest ::  must use POST",
                                                'timestamp'=>date('c')
                                            ));
            } catch (Error $e) {    
                $this->setInternalErrorMessage($e);
            }
        } 
        else if (strtoupper($requestMethod) == 'POST') {
            try {
                $body = $_POST['data'];
                $decoded=json_decode($body,true);
                
                /**
                 * validate incoming data
                 */
                $responseData=self::parseRemoveInput($decoded);
                //if validation fails
                if(count($responseData)){
                    $responseData['data_received']=$decoded;
                    $responseData=json_encode($responseData,true);
                 }
                //else store the manifest
                else {
                    $responseData=self::removeManifest($decoded);
                    $responseData=json_encode($responseData,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                }
 
            } catch (Error $e) {
                $this->setInternalErrorMessage($e);
            }
        }
        else {
           $this->setMethodNotSupportedMessage();
        }
        // send output 
       $this->sendResponse($responseData);
    }


    /**
     * parseInput received
     * return array contains errors encountered
     * empty return array means that validation is successful
     * 
     * @param array input
     * 
     * @return array
     */
    public static function parseRemoveInput(array $decoded){
        $responseData=array();
        if(!isset($decoded['url'])||!strcmp($decoded['url'],""))
                    $responseData["errorList"][]=array("title"=>"Bad API Request", "detail"=>"Error : the URL field was not found in the attached json");
        
        //$responseData=self::parseCredentials($decoded,$responseData);
        return $responseData;
    }


    /**
     * Store Manifest in file store
     * 
     * @param array decoded json array
     * 
     * @return array as a response
     */
    public static function removeManifest(array $decoded):array{
        $responseData=array();
         $parts=explode(".",$decoded['url']);
        if(strcmp($parts[count($parts)-1],"json"))$decoded['url'].=".json";
        $path=Config::$FILE_STORE_PATH.DIRECTORY_SEPARATOR.str_replace(Config::$FILE_STORE_URL,"",$decoded['url']);

        if (file_exists($path)){
            unlink($path);
            $responseData['success']=true;
            $responseData['message']="Manifest file at ".$decoded['url']." has been deleted";
        }
        else{
            $responseData['success']=false;
            $responseData['message']="Manifest file at ".$decoded['url']." does not exist";
        }

        $responseData['url']=$decoded['url'];
        $responseData['timestamp']=date('c');
        return $responseData;
    }




}
