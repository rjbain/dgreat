<?php

namespace Drupal\cediploma\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Custom CeDiploma Form.
 */
class CeDiplomaForm extends FormBase {

  /**
   * {@inheritdoc}
   */
   public function getFormId() {
     return "cediploma_validate";
   }

   /**
    * {@inheritdoc}
    */
   public function buildForm(array $form, FormStateInterface $form_state) {

    $form['cedidtitle'] = [
       '#type' => 'markup',
       '#markup' => '<h1 class="display-title">Credential Validation</h1>',
     ];


     $form['cedid'] = [
       '#type' => 'textfield',
       '#title' => $this->t('Please Enter CeDID (not case sensitive)'),
       '#attributes' => array('data-masked-input' => 'wwww-wwww-wwww', 'data-val' => 'true', 'data-val-regex' => '____-____-____ format required.', 'data-val-regex-pattern' => '(([a-zA-Z0-9]{4})[-]([a-zA-Z0-9]{4})[-]([a-zA-Z0-9]{4}))', 'placeholder' => '____-____-____', 'data-val-required'=> 'The CeDiD field is required.', 'id' => 'CeDiD', 'maxlength' => '14'),
       //'#required' => TRUE,
     ];


    $form['cedidkey'] = [
       '#type' => 'markup',
       '#markup' => '<div class="cedidkey"><img src="/themes/custom/myusf/images/src/cedid_key_image.png"></div>',
     ];



     $form['cename'] = [
       '#type' => 'textfield',
       '#title' => $this->t('First two letters of the name as it appears on the credential'),
       '#attributes' => array( 'maxlength' => '2','placeholder' => '__'),
       //'#attributes' => array('data-masked-input' => '99', 'data-val' => 'true', 'data-val-length' => 'Must be 2 characters.', 'data-val-length-max' => '2', 'data-val-length-min' => '2', 'data-val-required' => 'The UserName field is required.', 'id' => 'UserFirstTwoLetters', 'maxlength' => '2', 'name' => 'UserFirstTwoLetters', 'placeholder' => '__'),
       //'#required' => TRUE,
     ];

     $form['submit'] = [
       '#type' => 'button',
       '#value' => $this->t('Validate'),
       '#ajax' => [
         'callback' => '::setMessage',
       ]
     ];


    $form['cedidtrustlogo'] = [
       '#type' => 'markup',
       '#markup' => '<div class="cedidtrustlogo"><img src="/themes/custom/myusf/images/src/poweredbyCeCredentialTrustLogo_180x34.png"></div>',
     ];


     $form['message'] = [
       '#type' => 'markup',
       '#markup' => '<div class="result_message"></div>'
     ];


     return $form;
   }



  public function validate(array $form, FormStateInterface $form_state) {
  // Validate the postal code is entered when Ireland is not selected.
  if (empty(trim($form_state['values']['cename']))) {
    form_set_error('cename', t('The cename is required.'));
  }
}


   /**
    * Check parameters and provide a custom response.
    */
   public function setMessage(array &$form, FormStateInterface $form_state) {
     
      $uri = 'https://secure.cecredentialtrust.com:8086/api/webapi/clientvalidate';
      // ClientId supplied by Paradigm
      //Test Client ID 
      //Old $clientId = '80DBC6A0-6CCF-4BA3-AAD8-89B2AE22FFA9';
      $clientId = '92442390-6FFE-49C5-9E9B-3DCC22D740BB';
      // Init TLS 1.2 var
      if (!defined('CURL_SSLVERSION_TLSv1_2')) {
          define('CURL_SSLVERSION_TLSv1_2', 6); // 6 = TLS 1.2
      }
      // Set default and init parameters
      $jsondefault = preg_replace('/\s+/', ' ', utf8_encode('{
              "result_table": ""
          }'));

      $output = json_decode($jsondefault);

      //if (!empty($_GET['ceDiD']) && !empty($_GET['nm2'])) {

          //Get Query Parameters    
          //$ceDiD = $_GET['ceDiD'];
          //$nm2 = $_GET['nm2'];

          //$ceDiD = "16D1-2C33-S8T4";
          //$nm2 = "SA";

          $cedid = $form_state->getValue('cedid');
          $cename = $form_state->getValue('cename');

          if (empty($cedid)) {
            $cedid = 0;
          }
          else {
            $cedid = $cedid;
          }

          if (empty($cename)) {
            $cename = 0;
          }
          else {
            $cename = $cename;
          }

          //Formulate query
          //$url = $uri . '/' . $clientId . '/' . $ceDiD . '/' . $nm2;
          $url = $uri . '/' . $clientId . '/' . $cedid . '/' . $cename;
          $ch = curl_init($url);
          curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_HTTPHEADER, array(
              'Content-Type: application/json',
              'Accept: application/json'
          ));
          $result = curl_exec($ch);
          $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          curl_close($ch);

          if ($httpCode === 200) {
              $item = json_decode($result);
                    $now = gmdate("D, jS F Y h:i:s A");
                if ($item[0]->ValidStatus === "VALID") {
                    $major = $item[0]->Major1 == "" ? "" : "<tr><td>" . "&nbsp;" . "</td><td>" . $item[0]->Major1 . "</td></tr>";
                    $honor = $item[0]->Honor1 == "" ? "" : "<tr><td>" . "&nbsp;" . "</td><td>" . $item[0]->Honor1 . "</td></tr>";
                    $tbody = "<tbody>" .
                            "<tr><td colspan='2'>" . "<b>This is a " . $item[0]->ValidStatus ." credential</td></tr>" .
                            "<tr><td colspan='2'>" . "Validated:  " . $now . " GMT</td></tr>" .
                            "<tr><td style='width:22%'>" . "<b>CeDiD:</b>" . "</td><td style='width:78%'>" . $item[0]->CeDiplomaID . "</td></tr>" .
                            "<tr><td>" . "<b>Name:</b>" . "</td><td>" . $item[0]->Name . "</td></tr>" .
                            "<tr><td>" . "<b>Conferral Date:</b>" . "</td><td>" . $item[0]->ConferralDate . "</td></tr>" .
                            "<tr><td>" . "<b>Credential:</b>" . "</td><td>" . $item[0]->Degree1 . "</td></tr>" .
                            $major .
                            $honor .
                            "</tbody>"
                    ;
                    $tbodyHtml = preg_replace('/\s+/', ' ', $tbody);
                    $output->result_table = $tbodyHtml;
                }
                elseif ($item[0]->ValidStatus != "VALID") {
                    if (($cename == "0")||($cedid == "0")) {
                      $tbody = "<tbody><tr><td colspan='2'><b>Please enter a value</td></tr></tbody>";
                    }
                    elseif (($cename != "0")||($cedid != "0")) {
                      $tbody = "<tbody><tr><td colspan='2'><b>We cannot validate the Credential at this time.</b><br>The information provided does not match the information on record, or there was a connection error.<br>Please contact gradcenter@usfca.edu for assistance. When you do, please provide the student name and CeDiD to inquire further.</td></tr></tbody>";
                    }
                      $tbodyHtml = preg_replace('/\s+/', ' ', $tbody);
                      $output->result_table = $tbodyHtml;
                  }
          }

      $json_output = json_encode($output, JSON_UNESCAPED_SLASHES); //JSON_UNESCAPED_SLASHES Available since PHP 5.4

      $response = new AjaxResponse();
      $response->addCommand(
       new HtmlCommand(
         '.result_message',
          $json_output
         )
      );

     return $response;
   }

   public function submitForm(array &$form, FormStateInterface $form_state) {}
}
