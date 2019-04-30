<?php
require_once('./lib/nusoap.php');

libxml_use_internal_errors(true);
// Create the server instance
$server = new nusoap_server();
$server->configureWSDL('validate_xml');

// Register the "hello" method to expose it
$server->register('validate_xml',
        array('x_content' => 'xsd:string'),   // parameter
        array('return' => 'xsd:string'),     // output
        'urn:server',                        // namespace
        'urn:server#helloServer',            // soapaction
        'rpc',                               // style
        'encoded',                           // use
        'XML validation');                   // description


function validate_xml($x_content)
{
    $xml = new DOMDocument(); 
    $xml->loadXML($x_content); 
    
    if (!$xml->schemaValidate('./ConveyancerInstructionSend.xsd')) {
        return libxml_display_failure($x_content);
    }    

    return libxml_display_success($x_content);
}


function libxml_display_failure($x_content)
{
    $resDescription = "<br />";
    $errors = libxml_get_errors();
    foreach ($errors as $error) {
        switch ($error->level) {
            case LIBXML_ERR_WARNING:
                $resDescription .= "<b>Warning $error->code</b>: ";
                break;
            case LIBXML_ERR_ERROR:
                $resDescription .= "<b>Error $error->code</b>: ";
                break;
            case LIBXML_ERR_FATAL:
                $resDescription .= "<b>Fatal Error $error->code</b>: ";
                break;
        }
        $resDescription .= trim($error->message);
        if ($error->file) {
            $resDescription .= " in <b>$error->file</b>";
        }
        $resDescription .= " on line <b>$error->line</b>";
    }
    libxml_clear_errors();

    // $xml_txt = file_get_contents($path);
    $xml = DOMDocument::loadXML($x_content); 
    $xml_string = makeXML($xml, 'Failure', $resDescription);

    return $xml_string;

    $xml_result = [ 'ResStatus' => 'Failure',
                    'MessageType' => $xml->getElementsByTagName('MessageType')->item(0)->nodeValue,
                    'MessageSubType' => $xml->getElementsByTagName('MessageSubType')->item(0)->nodeValue,
                    'Status' => 'Failure', 
                    'ResDescription' => $resDescription,
                    'MessageSentDate' => $xml->getElementsByTagName('Date')->item(0)->nodeValue,
                    'InstructionDate' => $xml->getElementsByTagName('InstructionDate')->item(0)->nodeValue
                   ];
    $nodes = $xml->getElementsByTagName('TransactionReference');
    $transactionInformation = [];
    $i = 0;
    foreach($nodes as $node) {
        $transactionInformation[$i]['Reference'] = $node->getElementsByTagName('Reference')->item(0)->nodeValue;
        $transactionInformation[$i]['AllocatedBy'] = $node->getElementsByTagName('AllocatedBy')->item(0)->nodeValue;
        $transactionInformation[$i]['Description'] = $node->getElementsByTagName('Description')->item(0)->nodeValue;
        $i++;
    }
    $xml_result['TransactionInformation'] = $transactionInformation;

    return json_encode($xml_result);
}

function libxml_display_success($x_content)
{
    $xml = DOMDocument::loadXML($x_content); 
    $xml_string = makeXML($xml, 'Success');

    return $xml_string;
    // $xml_result = [ 'ResStatus' => 'Success',
    //                 'MessageType' => $xml->getElementsByTagName('MessageType')->item(0)->nodeValue,
    //                 'MessageSubType' => $xml->getElementsByTagName('MessageSubType')->item(0)->nodeValue,
    //                 'Status' => 'Success', //$xml->getElementsByTagName('Status')->item(0)->nodeValue,
    //                 'MessageSentDate' => $xml->getElementsByTagName('Date')->item(0)->nodeValue,
    //                 'InstructionDate' => $xml->getElementsByTagName('InstructionDate')->item(0)->nodeValue
    //                ];
    // $nodes = $xml->getElementsByTagName('TransactionReference');
    // $transactionInformation = [];
    // $i = 0;
    // foreach($nodes as $node) {
    //     $transactionInformation[$i]['Reference'] = $node->getElementsByTagName('Reference')->item(0)->nodeValue;
    //     $transactionInformation[$i]['AllocatedBy'] = $node->getElementsByTagName('AllocatedBy')->item(0)->nodeValue;
    //     $transactionInformation[$i]['Description'] = $node->getElementsByTagName('Description')->item(0)->nodeValue;
    //     $i++;
    // }
    // $xml_result['TransactionInformation'] = $transactionInformation;

    // return json_encode($xml_result);
    // return $xml->saveXML();
}

function makeXML($xml, $status, $error='') {
    $xml_string = '<ConveyancerInstructionSend xmlns="http://www.oscre.org/ns/LC-version-1.1/OS/ConveyancerInstructionSend">
                    <Header xmlns="http://www.oscre.org/ns/v1/Header">
                    <MessageType>'.$xml->getElementsByTagName('MessageType')->item(0)->nodeValue.'</MessageType>
                    <MessageSubType>'.$xml->getElementsByTagName('MessageSubType')->item(0)->nodeValue.'</MessageSubType>
                    <Status>'.$status.'</Status>
                    <Description>'.$error.'</Description>
                    </Header>
                    <MessageSentDate>
                    <Date>'.$xml->getElementsByTagName('Date')->item(0)->nodeValue.'</Date>
                    </MessageSentDate>
                    <TransactionInformation>
                    <InstructionDate>'.$xml->getElementsByTagName('InstructionDate')->item(0)->nodeValue.'</InstructionDate>';
    $nodes = $xml->getElementsByTagName('TransactionReference');
    $i = 0;
    foreach($nodes as $node) {
        $xml_string .= '<TransactionReference>
                            <Reference xmlns="http://www.oscre.org/ns/v1/ExtensionCommon">'.$node->getElementsByTagName('Reference')->item(0)->nodeValue.'</Reference>
                            <AllocatedBy xmlns="http://www.oscre.org/ns/v1/ExtensionCommon">'.$node->getElementsByTagName('AllocatedBy')->item(0)->nodeValue.'</AllocatedBy>
                            <Description xmlns="http://www.oscre.org/ns/v1/ExtensionCommon">'.$node->getElementsByTagName('Description')->item(0)->nodeValue.'</Description>
                        </TransactionReference>';
    }
    $xml_string .= '</TransactionInformation>
                </ConveyancerInstructionSend>';
    return $xml_string;
    
}
    // Use the request to invoke the service
    $HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
    $server->service(file_get_contents("php://input"));

?>