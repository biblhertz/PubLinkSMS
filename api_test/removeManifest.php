<?php 

require '../vendor/autoload.php';

use Biblhertz\Manifest_Server\Config;

//$SERVICE_URL="https://annotation.biblhertz.it/api/v1/removeManifest";

$SERVICE_URL="http://localhost/api/v1/removeManifest";

$payload=json_encode(
    array(
        'url'=>'http://localhost/iiif_manifests/ARTB/2025-04/manifest.json'
        //'url'=>'https://annotation.biblhertz.it/iiif_manifests/annotations/01/manifest.json'
        //'url'=>'https://annotation.biblhertz.it/iiif_manifests/HSAH/03/manifest.json'
    ));

printLn();
print_r($payload."\n");
printLn();


try{  
   
    printLn();
    // Get cURL resource
    $curl = curl_init();

    echo "Initialised \n";
    printLn();
    curl_setopt($curl, CURLOPT_URL, $SERVICE_URL);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['X-API-Key: ' . Config::$API_KEY]);
    // Set the authentication options
    curl_setopt($curl, CURLOPT_POST, 1);
    // Include header in result? (0 = yes, 1 = no)
    curl_setopt($curl, CURLOPT_HEADER, 1);
    // Should cURL return or print out the data? (true = return, false = print)
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    //include the payload
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array("data"=>$payload)));
    curl_setopt($curl, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
    
    echo "Set Options :: $SERVICE_URL\n";
    printLn();
    // Download the given URL, and return output
    $output = curl_exec($curl);
    //print_r($output);
    echo $output."\n";
    printLn();
    //echo "\nExecuted \n";
    printLn();

    // Close the cURL resource, and free system resources
    curl_close($curl);
}
catch(Exception $e){
    println();
    echo "EXCEPTION TRIGGERED\n";
	echo $e->getMessage()."\n";
    printLn();
}





function printLn(){
	echo "+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++\n";
}


?>