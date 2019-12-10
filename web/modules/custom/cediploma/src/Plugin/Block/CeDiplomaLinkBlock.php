<?php
/**
 * @file
 */
namespace Drupal\cediploma\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\user\Entity\User;


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

		$uid = \Drupal::currentUser()->id();
      	$user = User::load($uid);
		$cwid = $user->get('field_user_cwid')->getValue();
		foreach($cwid as $value) {
			$plainStudentId = [];
			foreach($value as $cwids) {
				$plainStudentId[] = $cwids;
			}	

		}
		$plainStudentId = $plainStudentId[0];

		$ENDPOINT = "https://secure.cecredentialtrust.com/Account/ERLSSO"; //Test Endpoint
 		// Enter Values from spreadsheet supplied by Paradigm
		$CLIENTID = "92442390-6FFE-49C5-9E9B-3DCC22D740BB";
		$CLIENTNUMBER = "1709";
		$MASK1 = "28=++4>8.$#=2+5-5;)81:1%*><==+9)";
		// Encrypt data and build HEXKEY
		// StudentId + pipe symbol + UTC DateTime is used to prevent "replay attacks"
		$utcDateTime = gmdate("Y-m-d H:i:s");
		$plainTextStudentId = $plainStudentId . "|" . $utcDateTime;
 		// Only use the first 16 chars (16 bytes) of MASK1 for AES128
		$privateKey16String = substr($MASK1, 0, 16);
		//$key should have been previously generated in a cryptographically safe way, like openssl_random_pseudo_bytes
		$cipher = "AES-128-CBC";

		if (in_array($cipher, openssl_get_cipher_methods())) {
    		$ivlen = openssl_cipher_iv_length($cipher);
    		$iv = openssl_random_pseudo_bytes($ivlen);
    		$encryptedMessage = openssl_encrypt($plainTextStudentId, $cipher, $privateKey16String, OPENSSL_RAW_DATA, $iv);
   			$cipherHexString = bin2hex($encryptedMessage);
			$ivHexString = bin2hex($iv);
			$encryptedHexString = $ivHexString . $cipherHexString;

		}

		$HEXKEY = $CLIENTID . $encryptedHexString . "|P";

		 return array (
            "#type" => "markup",
            "#markup" => "<a href='$ENDPOINT/$HEXKEY/$CLIENTNUMBER' target='_blank'>Order/Download</a>",
        );

	}



	/**
     * {@inheritdoc}
     */
    public function getCacheMaxAge() {
        return 0;
    }



}