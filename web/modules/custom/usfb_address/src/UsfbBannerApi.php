<?php

namespace Drupal\usfb_address;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Wrapper class for calling the ws.usfca.edu API.
 *
 * @ingroup usfb_address
 */
class UsfbBannerApi {

  /**
   * The base URL for API requests.
   */
  const BASE_URL = 'https://ws.usfca.edu/address/';

  /**
   * The url we are checking.
   *
   * @var string
   */
  protected $url;

  /**
   * The Request Response.
   *
   * @var object
   */
  protected $request;

  /**
   * The data we are using in a PUT Request.
   *
   * @var mixed
   */
  protected $data;

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * A logger instance.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Constructs a UsfbBannerApi object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   File system service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   A logger instance.
   */
  public function __construct(FileSystemInterface $file_system, LoggerChannelFactoryInterface $logger) {
    $this->fileSystem = $file_system;
    $this->logger = $logger->get('USFB Address');
  }

  /**
   * Set the url we are checking.
   *
   * @param string $url
   *   The url string we are checking.
   */
  protected function setUrl($url) {
    $this->url = $url;
  }

  /**
   * Get the url we are checking.
   *
   * @return string
   *   The url string we are checking.
   */
  protected function getUrl() {
    return $this->url;
  }

  /**
   * Set the data we are using in a PUT request.
   *
   * @param mixed $data
   *   The data we are using.
   */
  protected function setData($data) {
    $this->data = $data;
  }

  /**
   * Get the data we are using in a PUT request.
   *
   * @param mixed $data
   *   The data we are using.
   */
  protected function getData() {
    return $this->data;
  }

  /**
   * Mechanism to call the API.
   *
   * @param string $name
   *   The username we will be using.
   * @param mixed $data
   *   Data that will be used during a PUT request.
   * @param string $type
   *   The type of request (ie GET, PUT, etc).
   *
   * @return null|object
   *   The API response or NULL.
   */
  public function callApi($name, $data = NULL, $type = 'GET') {

    // Assigning our data to use later.
    if ($type === 'PUT' && $data !== NULL) {
      $this->setData($data);
    }

    // Build the url and call the API.
    $this->setUrl(self::BASE_URL . $name);
    $this->requestResponse($type);

    // Exit if Empty Response.
    if ($this->request === NULL) {
      return NULL;
    }

    // Grab the Body and return as needed.
    $contents = json_decode($this->request->getBody()->getContents());
    return !empty($contents) ? $contents : NULL;
  }

  /**
   * Gets the response of a page.
   *
   * @param string $type
   *   The type of request (ie GET, POST, etc).
   */
  protected function requestResponse($type) {
    $client = new Client();

    // Grab the private file path.
    $path = $this->fileSystem->realpath("private://certs");

    // Set the options for the request.
    // @see http://docs.guzzlephp.org/en/latest/request-options.html
    $options = [
      'http_errors' => FALSE,
      'timeout' => 3,
      'connect_timeout' => 3,
      'synchronous' => TRUE,
      'cert' => "$path/client-cert.pem",
      'ssl_key' => "$path/client-key.pem",
      'verify' => "$path/ca-cert.pem",
    ];

    // Set the data body via JSON.
    if ($type === 'PUT') {
      $options['json'] = $this->getData();
    }

    try {
      // Try the request.
      $response = $client->request($type, $this->getUrl(), $options);
      $t = 1;
      // Check the Status code and return.
      switch ($response->getStatusCode()) {
        // All good, send back response.
        case '200':
          $this->request = $response;
          break;

        // Something else is amiss.
        default:
          $issue = json_decode($response->getBody()->getContents(), TRUE);
          if ($issue === NULL) {
            $message  = 'The request to the USF Banner API resulted in a ' . $response->getStatusCode() . ' Response ';
          }
          else {
            $message  = 'The request to the USF Banner API resulted in a ' . $issue["errorCode"] . ' Response ';
            $message .= 'with a message of ' . $issue["errorMessage"];
          }
          $this->logger->error($message);
          $this->request = NULL;
          break;
      }
    }
    catch (TransferException $e) {
      $this->request = NULL;
    }
  }
}