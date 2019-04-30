<?php

    $result = [];

    if($_FILES["x-files"]["error"] > 0){
        echo "Error: " . $_FILES["x-files"]["error"] . "<br>";
    } else{
        if ($_FILES["x-files"]) {

            $is_posted = 1;
            // Validate XML
            require_once('./lib/nusoap.php');

            libxml_use_internal_errors(true);
            // This is your Web service server WSDL URL address
            $wsdl = "http://localhost/new_instructions/service.php?wsdl";

            // Create client object
            $client = new nusoap_client($wsdl, 'wsdl');

            $err = $client->getError();
            if ($err) {
                // Display the error
                echo '<h2>Constructor error</h2>' . $err;
                // At this point, you know the call that follows will fail
                exit();
            }

            // temporary copy files to tmp folder
            $t_path = './tmp/'.basename( $_FILES["x-files"]["name"]);
            if (move_uploaded_file($_FILES["x-files"]["tmp_name"], $t_path)) {
                // echo $t_path;
            }
            $file_name = basename( $_FILES["x-files"]["name"]);

            // get Xml content as String
            $xml_txt = file_get_contents($t_path);

            //Call the validation method
            $result_string = $client->call('validate_xml', array('x_filename'=> $file_name,'x_content'=>$xml_txt));            

            // Save result String to already existing XML file in tmp folder
            file_put_contents('./tmp/'.$file_name, $result_string);
            
            // get json format array from xml to display in body(html)
            $xml = new DOMDocument(); 
            $xml->loadXML($result_string);
            
            $result = [ 'ResStatus' => 'Failure',
                    'MessageType' => $xml->getElementsByTagName('MessageType')->item(0)->nodeValue,
                    'MessageSubType' => $xml->getElementsByTagName('MessageSubType')->item(0)->nodeValue,
                    'Status' => $xml->getElementsByTagName('Status')->item(0)->nodeValue, 
                    'ResDescription' => $xml->getElementsByTagName('Description')->item(0)->nodeValue,
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
            $result['TransactionInformation'] = $transactionInformation;

            //delete a temp file
            unlink('./tmp/'.$file_name);
        }        
    }
?>
<html>
    <head>
        <title>XML Validator</title>
        <style>

        .blank_row
        {
            height: 20px !important; /* overwrites any other rules */
            background-color: #FFFFFF;
        }
        </style>
    </head>
    <body>
        <form class="form-horizontal" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="x-files">Import Files</label>
                <div class="col-md-6">
                    <input type="file" id='x-files' class="form-control" name="x-files" accept=".xml">
                </div>
            </div>
            <div class="form-group">
                <button type="submit">Select</button>
            </div>
        </form>
        <?php 
        if ($is_posted){
        ?>
        
        <table class="table-bordered" style="width:700px;">
            <tr>
                <td>Header</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td>MessageType</td>
                <td><?php echo $result['MessageType']; ?></td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td>MessageSubType</td>
                <td><?php echo $result['MessageSubType']; ?></td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td>Status</td>
                <td><?php echo $result['Status']; ?></td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td>Description</td>
                <td><?php echo $result['ResDescription']; ?></td>
                
            </tr>
            
            <tr>
                <td>MessageSentDate</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td>SentDate</td>
                <td><?php echo $result['MessageSentDate']; ?></td>
            </tr>
            <tr>
                <td>TransactionInformation</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td>InstructionDate</td>
                <td><?php echo $result['InstructionDate']; ?></td>
            </tr>
            <?php
                for($i=0;$i<count($result['TransactionInformation']); $i++) {
                    $t_reference = $result['TransactionInformation'][$i];
            ?>
                <tr class="blank_row">
                    <td colspan="3"></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>Reference</td>
                    <td><?php echo $t_reference['Reference']; ?></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>AllocatedBy</td>
                    <td><?php echo $t_reference['AllocatedBy']; ?></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>Description</td>
                    <td><?php echo $t_reference['Description']; ?></td>
                </tr>
            <?php
                }
            ?>            
        </table>
        <?php 
        }
        ?>
    </body>
</html>