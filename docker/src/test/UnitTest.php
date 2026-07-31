<?php
namespace Biblhertz\Manifest_Server\test;

use PHPUnit\Framework\TestCase;
use Biblhertz\Manifest_Server\Config;

class UnitTest extends TestCase
{
    /**
     * tests will create, download and then delete a IIIF manifest file
     * File will be stored in first entry in the Config::$validSeries array variable in volume 99 with the filename manifest.json
     * 
     */
 
private static mixed $decoded;
private static $series;
private static $manifest;
private static $volume='99';
private static $url;
private static $externalURL;
    
   
/**
 * Put manifest on server
 */
    public function testPutManifest(){
        Config::setup();
        self::$series=Config::$validSeries[0];
        self::$manifest=$this->getManifest();
        $payload=json_encode(
            array(
                'series'=> self::$series,
                'volume'=> self::$volume,
                'ignore_mismatch'=>true,
                'ignore_overwrite'=>true,
                'manifest_name'=>'manifest.json',
                'manifest' => self::$manifest
            ));

            $url = Config::$PUT_MANIFEST;
            
            
            $curl = curl_init();
            // Set the url to authenticate
            curl_setopt($curl,CURLOPT_USERPWD, Config::$API_USERNAME.":".Config::$API_PASSWORD);
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl,CURLOPT_URL,$url);
            // Set the authentication options
            curl_setopt($curl, CURLOPT_POST, 1);
            // Include header in result? (0 = yes, 1 = no)
            //curl_setopt($curl, CURLOPT_HEADER, 1);
            // Should cURL return or print out the data? (true = return, false = print)
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            //include the payload
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array("data"=>$payload)));
            curl_setopt($curl, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
                
            // Download the given URL, and return output
            $output = curl_exec($curl);

            // Close the cURL resource, and free system resources
            curl_close($curl);

            self::$decoded=json_decode($output,true);
            error_log("Decoded Success :: ".self::$decoded['success']);
            error_log(print_r(self::$decoded,true));
            self::$url = self::$decoded['iiif_validation_result']['url'];
            self::$externalURL = self::$decoded['url'];
            error_log("URL :: ".self::$url." :: external :: ".self::$externalURL);
            
            $message="IIIF manifest stored successfully : new file created";
            $this->assertTrue(self::$decoded['success']);
            $this->assertSame(self::$decoded['message'],$message);  
    } 

    /**
     * get manifest check it is the same as the one that was posted
     */
     public function testGetManifest(){
            $curl = curl_init();
            // Set the url to authenticate
            curl_setopt($curl,CURLOPT_URL,self::$url);
            // Should cURL return or print out the data? (true = return, false = print)
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
                
            // Download the given URL, and return output
            $output = curl_exec($curl);

            // Close the cURL resource, and free system resources
            curl_close($curl);

            error_log("Getting :: ".self::$url);
            //error_log("Output is $output");
            $this->assertSame($output, self::$manifest);
    }

    /**
     * test delete manifest
     */
    public function testDeleteManifest(){

       $payload=json_encode(
            array(
                'url'=>self::$externalURL
            ));

            $url = Config::$REMOVE_MANIFEST;        
            
            $curl = curl_init();
            // Set the url to authenticate
            curl_setopt($curl,CURLOPT_USERPWD, Config::$API_USERNAME.":".Config::$API_PASSWORD);
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl,CURLOPT_URL,$url);
            // Set the authentication options
            curl_setopt($curl, CURLOPT_POST, 1);
            // Should cURL return or print out the data? (true = return, false = print)
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            //include the payload
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array("data"=>$payload)));
            curl_setopt($curl, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
                
            // Download the given URL, and return output
            $output = curl_exec($curl);

            // Close the cURL resource, and free system resources
            curl_close($curl);

            self::$decoded=json_decode($output,true);
            error_log("Decoded Success :: ".self::$decoded['success']);
            error_log(print_r(self::$decoded,true));
            $message="Manifest file at ".self::$externalURL." has been deleted";

            $this->assertTrue(self::$decoded['success']);
            $this->assertSame(self::$decoded['message'],$message);  

    } 

    


    private function getManifest(){
        return file_get_contents("/var/www/src/test/manifest.txt");
    }
    
}

?>
