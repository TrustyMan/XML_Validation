<?php
    header('Content-type: text/xml');

    require_once('./lib/nusoap.php');

    // This is your Web service server WSDL URL address
    $wsdl = "http://localhost/new_instrunctions/service.php?wsdl";

    // Create client object
    $client = new nusoap_client($wsdl, 'wsdl');
    
    $err = $client->getError();
    if ($err) {
        // Display the error
        echo '<h2>Constructor error</h2>' . $err;
        // At this point, you know the call that follows will fail
        exit();
    }

    // Call the hello method
    $result = $client->call('validate_xml');
    print_r($result).'\n';

?>