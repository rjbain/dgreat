<?php

namespace Drupal\cas\Service;

use Drupal\cas\Exception\CasValidateException;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\cas\CasPropertyBag;
use Psr\Log\LogLevel;

/**
 * Class CasValidator.
 */
class CasValidator {

  /**
   * Stores the Guzzle HTTP client used when validating service tickets.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Stores CAS helper.
   *
   * @var \Drupal\cas\Service\CasHelper
   */
  protected $casHelper;

  /**
   * Stores settings object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;

  /**
   * Stores URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Constructor.
   *
   * @param \GuzzleHttp\Client $http_client
   *   The HTTP Client library.
   * @param CasHelper $cas_helper
   *   The CAS Helper service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator.
   */
  public function __construct(Client $http_client, CasHelper $cas_helper, ConfigFactoryInterface $config_factory, UrlGeneratorInterface $url_generator) {
    $this->httpClient = $http_client;
    $this->casHelper = $cas_helper;
    $this->settings = $config_factory->get('cas.settings');
    $this->urlGenerator = $url_generator;
  }

  /**
   * Validate the service ticket parameter present in the request.
   *
   * This method will return the username of the user if valid, and raise an
   * exception if the ticket is not found or not valid.
   *
   * @param string $ticket
   *   The CAS authentication ticket to validate.
   * @param array $service_params
   *   An array of query string parameters to add to the service URL.
   *
   * @return \Drupal\cas\CasPropertyBag
   *   Contains user info from the CAS server.
   *
   * @throws CasValidateException
   *   Thrown if there was a problem making the validation request or
   *   if there was a local configuration issue.
   */
  public function validateTicket($ticket, array $service_params = array()) {
    $options = array();
    $verify = $this->settings->get('server.verify');
    switch ($verify) {
      case CasHelper::CA_CUSTOM:
        $cert = $this->settings->get('server.cert');
        $options['verify'] = $cert;
        break;

      case CasHelper::CA_NONE:
        $options['verify'] = FALSE;
        break;

      case CasHelper::CA_DEFAULT:
      default:
        // This triggers for CasHelper::CA_DEFAULT.
        $options['verify'] = TRUE;
    }

    $options['timeout'] = $this->settings->get('advanced.connection_timeout');

    $validate_url = $this->getServerValidateUrl($ticket, $service_params);
    $this->casHelper->log(
      LogLevel::DEBUG,
      'Attempting to validate service ticket %ticket by making request to URL %url',
      ['%ticket' => $ticket, '%url' => $validate_url]
    );

    try {
      $response = $this->httpClient->get($validate_url, $options);
      $response_data = $response->getBody()->__toString();
      $this->casHelper->log(LogLevel::DEBUG, "Validation response received from CAS server: %data", ['%data' => $response_data]);
    }
    catch (RequestException $e) {
      throw new CasValidateException("Error with request to validate ticket: " . $e->getMessage());
    }

    $protocol_version = $this->settings->get('server.version');
    switch ($protocol_version) {
      case "1.0":
        return $this->validateVersion1($response_data);

      case "2.0":
      case "3.0":
        return $this->validateVersion2($response_data);
    }

    throw new CasValidateException('Unknown CAS protocol version specified: ' . $protocol_version);
  }

  /**
   * Validation of a service ticket for Version 1 of the CAS protocol.
   *
   * @param string $data
   *   The raw validation response data from CAS server.
   *
   * @return \Drupal\cas\CasPropertyBag
   *   Contains user info from the CAS server.
   *
   * @throws CasValidateException
   *   Thrown if there was a problem parsing the validation data.
   */
  private function validateVersion1($data) {
    if (preg_match('/^no\n/', $data)) {
      throw new CasValidateException("Ticket did not pass validation.");
    }
    elseif (!preg_match('/^yes\n/', $data)) {
      throw new CasValidateException("Malformed response from CAS server.");
    }

    // Ticket is valid, need to extract the username.
    $arr = preg_split('/\n/', $data);
    $user = trim($arr[1]);
    $this->casHelper->log(
      LogLevel::DEBUG,
      "Extracted username %user from validation response data.",
      ['%user' => $user]
    );
    return new CasPropertyBag($user);
  }

  /**
   * Validation of a service ticket for Version 2 of the CAS protocol.
   *
   * @param string $data
   *   The raw validation response data from CAS server.
   *
   * @return \Drupal\cas\CasPropertyBag
   *   Contains user info from the CAS server.
   *
   * @throws CasValidateException
   *   Thrown if there was a problem parsing the validation data.
   */
  private function validateVersion2($data) {
    $dom = new \DOMDocument();
    $dom->preserveWhiteSpace = FALSE;
    $dom->encoding = "utf-8";

    // Suppress errors from this function, as we intend to throw our own
    // exception.
    if (@$dom->loadXML($data) === FALSE) {
      throw new CasValidateException("XML from CAS server is not valid.");
    }

    $failure_elements = $dom->getElementsByTagName('authenticationFailure');
    if ($failure_elements->length > 0) {
      // Failed validation, extract the message and throw exception.
      $failure_element = $failure_elements->item(0);
      $error_code = $failure_element->getAttribute('code');
      $error_msg = $failure_element->nodeValue;
      throw new CasValidateException("Error Code " . trim($error_code) . ": " . trim($error_msg));
    }

    $success_elements = $dom->getElementsByTagName("authenticationSuccess");
    if ($success_elements->length === 0) {
      // All responses should have either an authenticationFailure
      // or authenticationSuccess node.
      throw new CasValidateException("XML from CAS server is not valid.");
    }

    // There should only be one success element, grab it and extract username.
    $success_element = $success_elements->item(0);
    $user_element = $success_element->getElementsByTagName("user");
    if ($user_element->length == 0) {
      throw new CasValidateException("No user found in ticket validation response.");
    }
    $username = $user_element->item(0)->nodeValue;
    $this->casHelper->log(
      LogLevel::DEBUG,
      "Extracted username %user from validation response.",
      ['%user' => $username]
    );
    $property_bag = new CasPropertyBag($username);

    // If the server provided any attributes, parse them out into the property
    // bag.
    $attribute_elements = $dom->getElementsByTagName("attributes");
    if ($attribute_elements->length > 0) {
      $property_bag->setAttributes($this->parseAttributes($attribute_elements));
    }

    // Look for a proxy chain, and if it exists, validate it against config.
    $proxy_chain = $success_element->getElementsByTagName("proxy");
    if ($this->settings->get('proxy.can_be_proxied') && $proxy_chain->length > 0) {
      $this->verifyProxyChain($proxy_chain);
    }

    if ($this->settings->get('proxy.initialize')) {
      // Extract the PGTIOU from the XML.
      $pgt_element = $success_element->getElementsByTagName("proxyGrantingTicket");
      if ($pgt_element->length == 0) {
        throw new CasValidateException("Proxy initialized, but no PGTIOU provided in response.");
      }
      $pgt = $pgt_element->item(0)->nodeValue;
      $this->casHelper->log(
        LogLevel::DEBUG,
        "Extracted PGT %pgt from validation response.",
        ['%pgt' => $pgt]
      );
      $property_bag->setPgt($pgt);
    }
    return $property_bag;
  }

  /**
   * Verify a proxy chain from the CAS Server.
   *
   * Proxy chains from CAS Server responses are compared against the config
   * to ensure only allowed proxy chains are validated.
   *
   * @param \DOMNodeList $proxy_chain
   *   An XML element containing proxy values, from most recent to first.
   *
   * @throws CasValidateException
   *   Thrown if the proxy chain did not match the allowed list from settings.
   */
  private function verifyProxyChain(\DOMNodeList $proxy_chain) {
    $allowed_proxy_chains_raw = $this->settings->get('proxy.proxy_chains');
    $allowed_proxy_chains = $this->parseAllowedProxyChains($allowed_proxy_chains_raw);
    $server_chain = $this->parseServerProxyChain($proxy_chain);
    $this->casHelper->log(LogLevel::DEBUG, "Attempting to verify supplied proxy chain: %chain", ['%chain' => print_r($server_chain, TRUE)]);

    // Loop through the allowed chains, checking the supplied chain for match.
    foreach ($allowed_proxy_chains as $chain) {
      // If the lengths mismatch, cannot be a match.
      if (count($chain) != count($server_chain)) {
        continue;
      }

      // Loop through regex in the chain, matching against supplied URL.
      $flag = TRUE;
      foreach ($chain as $index => $regex) {
        if (preg_match('/^\/.*\/[ixASUXu]*$/s', $regex)) {
          if (!(preg_match($regex, $server_chain[$index]))) {
            $flag = FALSE;
            $this->casHelper->log(
              LogLevel::DEBUG,
              "Failed to match %regex with supplied %chain",
              ['%regex' => $regex, '%chain' => $server_chain[$index]]
            );
            break;
          }
        }
        else {
          if (!(strncasecmp($regex, $server_chain[$index], strlen($regex)) == 0)) {
            $flag = FALSE;
            $this->casHelper->log(
              LogLevel::DEBUG,
              "Failed to match %regex with supplied %chain",
              ['%regex' => $regex, '%chain' => $server_chain[$index]]
            );
            break;
          }
        }
      }

      // If we have a match, return.
      if ($flag == TRUE) {
        $this->casHelper->log(
          LogLevel::DEBUG,
          "Matched allowed chain: %chain",
          ['%chain' => print_r($chain, TRUE)]
        );
        return;
      }
    }

    // If we've reached this point, no chain was validated, so throw exception.
    throw new CasValidateException("Proxy chain did not match allowed list.");
  }

  /**
   * Parse the proxy chain config into a usable data structure.
   *
   * @param string $proxy_chains
   *   A newline-delimited list of allowed proxy chains.
   *
   * @return array
   *   An array of allowed proxy chains, each containing an array of regular
   *   expressions for a URL in the chain.
   */
  private function parseAllowedProxyChains($proxy_chains) {
    $chain_list = array();

    // Split configuration string on vertical whitespace.
    $chains = preg_split('/\v/', $proxy_chains, NULL, PREG_SPLIT_NO_EMPTY);

    // Loop through chains, splitting out each URL.
    foreach ($chains as $chain) {
      // Split chain string on any whitespace character.
      $list = preg_split('/\s/', $chain, NULL, PREG_SPLIT_NO_EMPTY);

      $chain_list[] = $list;
    }
    return $chain_list;
  }

  /**
   * Parse the XML proxy list from the CAS Server.
   *
   * @param \DOMNodeList $xml_list
   *   An XML element containing proxy values, from most recent to first.
   *
   * @return array
   *   An array of proxy values, from most recent to first.
   */
  private function parseServerProxyChain(\DOMNodeList $xml_list) {
    $proxies = array();
    // Loop through the DOMNodeList, adding each proxy to the list.
    foreach ($xml_list as $node) {
      $proxies[] = $node->nodeValue;
    }
    return $proxies;
  }

  /**
   * Parse the attributes list from the CAS Server into an array.
   *
   * @param \DOMNodeList $xml_list
   *   An XML element containing attributes.
   *
   * @return array
   *   An associative array of attributes.
   */
  private function parseAttributes(\DOMNodeList $xml_list) {
    $attributes = array();
    $node = $xml_list->item(0);
    foreach ($node->childNodes as $child) {
      $name = $child->localName;
      $value = $child->nodeValue;
      $attributes[$name][] = $value;
    }
    $this->casHelper->log(
      LogLevel::DEBUG,
      "Parsed the following attributes from the validation response: %attributes",
      ['%attributes' => print_r($attributes, TRUE)]
    );
    return $attributes;
  }

  /**
   * Return the validation URL used to validate the provided ticket.
   *
   * @param string $ticket
   *   The ticket to validate.
   * @param array $service_params
   *   An array of query string parameters to add to the service URL.
   *
   * @return string
   *   The fully constructed validation URL.
   */
  public function getServerValidateUrl($ticket, array $service_params = array()) {
    $validate_url = $this->casHelper->getServerBaseUrl();
    $path = '';
    switch ($this->settings->get('server.version')) {
      case "1.0":
        $path = 'validate';
        break;

      case "2.0":
        if ($this->settings->get('proxy.can_be_proxied')) {
          $path = 'proxyValidate';
        }
        else {
          $path = 'serviceValidate';
        }
        break;

      case "3.0":
        if ($this->settings->get('proxy.can_be_proxied')) {
          $path = 'p3/proxyValidate';
        }
        else {
          $path = 'p3/serviceValidate';
        }
        break;
    }
    $validate_url .= $path;

    $params = array();
    $params['service'] = $this->urlGenerator->generate('cas.service', $service_params, UrlGeneratorInterface::ABSOLUTE_URL);
    $params['ticket'] = $ticket;
    if ($this->settings->get('proxy.initialize')) {
      $params['pgtUrl'] = $this->formatProxyCallbackUrl();
    }
    return $validate_url . '?' . UrlHelper::buildQuery($params);
  }

  /**
   * Format the pgtCallbackURL parameter for use with proxying.
   *
   * We have to do a str_replace to force https for the proxy callback URL,
   * because it must use https, and setting the option 'https => TRUE' in the
   * options array won't force https if the user accessed the login route over
   * http and mixed-mode sessions aren't allowed.
   *
   * @return string
   *   The pgtCallbackURL, fully formatted.
   */
  private function formatProxyCallbackUrl() {
    return str_replace('http://', 'https://', $this->urlGenerator->generateFromRoute('cas.proxyCallback', array(), array(
      'absolute' => TRUE,
    )));
  }

}
