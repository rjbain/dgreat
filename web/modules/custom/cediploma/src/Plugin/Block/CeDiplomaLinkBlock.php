<?php

/**
 * @file
 */
namespace Drupal\cediploma\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Creates a 'Cediploma Link' Block
 * @Block(
 * id = "block_cediplomalinkblock",
 * admin_label = @Translation("CeDiploma Link Block"),
 * )
 */
class CeDiplomaLinkBlock extends BlockBase {

    /**
     * {@inheritdoc}
     */
    public function build() {

$ENDPOINT = "https://test.secure.cecredentialtrust.com/Account/ERLSSO"; //Test Endpoint
 
// Enter Values from spreadsheet supplied by Paradigm
$CLIENTID = "92442390-6FFE-49C5-9E9B-3DCC22D740BB";
$CLIENTNUMBER = "1709";
$MASK1 = "28=++4>8.$#=2+5-5;)81:1%*><==+9)";
$plainStudentId = "20524550"; //used for testing success
 
 
// Encrypt data and build HEXKEY
// StudentId + pipe symbol + UTC DateTime is used to prevent "replay attacks"
$utcDateTime = gmdate("Y-m-d H:i:s");
$plainTextStudentId = $plainStudentId . "|" . $utcDateTime;
 
// Only use the first 16 chars (16 bytes) of MASK1 for AES128
$privateKey16String = substr($MASK1, 0, 16);
//$encryptedHexString = encrypt_openssl($plainTextStudentId, $privateKey16String);
 
$HEXKEY = $CLIENTID . $encryptedHexString . "|P";
 
// Build example anchor tag to be used for testing
$CeDiplomaLink =  "<a href='$ENDPOINT/$HEXKEY/$CLIENTNUMBER' target='_blank'>Order/Register my CeDiploma</a>";
 
// Build example URL in an HTML table to be used in Hyperlink for testing
//$tdbeg = "<br /><table><tr><td><a href='" . $ENDPOINT . "/";
//$tdend = "/$CLIENTNUMBER' target='_blank'>$plainTextStudentId</a></td></tr></table>";
//$url = $tdbeg . $HEXKEY . $tdend; 


        return array (
            '#type' => 'markup',
            '#markup' => '<h2>' . $CeDiplomaLink . '</h2>',
        );
    }

}