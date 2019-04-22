<?php
require_once('./lib/nusoap.php');

// Create the server instance
$server = new nusoap_server();
$server->configureWSDL('validate_xml');

// Register the "hello" method to expose it
$server->register('validate_xml',
        array('username' => 'xsd:string'),   // parameter
        array('return' => 'xsd:string'),     // output
        'urn:server',                        // namespace
        'urn:server#helloServer',            // soapaction
        'rpc',                               // style
        'encoded',                           // use
        'XML validation');                   // description


function validate_xml($username)
{
    $xml = new DOMDocument(); 
    $xml->load('./ForwardPanelInstruction_Sale.xml'); 
    
    if (!$xml->schemaValidate('./ConveyancerInstructionSend.xsd')) {

        return libxml_display_failure();
    }    

    return libxml_display_success();
}


function libxml_display_failure()
{
    $xml = new DOMDocument(); 
    $xml->load('./ForwardPanelInstructionAcknowledgement_Sale[Failure].xml');
    // return 'Failure';
    return $xml->saveXML();
}


function libxml_display_success()
{
    $xml = new DOMDocument(); 
    $xml->load('./ForwardPanelInstructionAcknowledgement_Sale[Success].xml');
    // return 'Success';
    return $xml->saveXML();
}

    // Use the request to invoke the service
    $HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
    $server->service(file_get_contents("php://input"));

?>


