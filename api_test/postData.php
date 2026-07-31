<?php 

require '../vendor/autoload.php';

use Biblhertz\Manifest_Server\Config;



$payload=json_encode(
    array(
        'series'=>'annotations',
        'volume'=>'01',
        'ignore_mismatch'=>true,
        'ignore_overwrite'=>true,
        'manifest_name'=>'manifest.json',
        'manifest' => getManifest()
    ));

printLn();
//print_r($payload);
printLn();


try{  

    Config::setup();

    $url = Config::$FILE_STORE_URL;
    $parts = parse_url($url);
    $url = $parts['scheme'] . '://' . $parts['host'] . '/api/v1/putManifest';
   
    printLn();
    // Get cURL resource
    $curl = curl_init();

    echo "Initialised  :: X-API-Key: ".Config::$API_KEY."\n";
    printLn();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['X-API-Key: ' . Config::$API_KEY]);
    // Set the authentication options
    curl_setopt($curl, CURLOPT_POST, 1);
    // Include header in result? (0 = yes, 1 = no)
    //curl_setopt($curl, CURLOPT_HEADER, 1);
    // Should cURL return or print out the data? (true = return, false = print)
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    //include the payload
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array("data"=>$payload)));
    curl_setopt($curl, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
    
    echo "Set Options :: $url\n";
    printLn();
    // Download the given URL, and return output
    $output = curl_exec($curl);
    //print_r($output);
    echo print_r($output,true)."\n";
    //printLn();
    echo "\nExecuted \n";
    printLn();

    $decoded=json_decode($output,true);
    printLn();
    print_r($decoded);
    printLn();
    echo $decoded['success'];

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

function getManifest(){
    return file_get_contents("manifest.txt");
}


?>