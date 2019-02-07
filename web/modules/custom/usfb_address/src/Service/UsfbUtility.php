<?php

namespace Drupal\usfb_address\Service;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Component\Datetime\Time;

/**
 * Utility Service with common functions.
 *
 * @ingroup usfb_address
 */
class UsfbUtility {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $time;

  /**
   * Constructs a UsfbBannerApi object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The session from the request stack.
   * @param \Drupal\Component\Datetime\Time $time
   *   The time service.
   */
  public function __construct(AccountInterface $current_user, RequestStack $request_stack, Time $time) {
    $this->currentUser = $current_user;
    $this->session = $request_stack->getCurrentRequest() !== NULL ?
      $request_stack->getCurrentRequest()->getSession() : NULL;
    $this->time = $time;
  }

  /**
   * Returns the destination path for users after finishing their address check.
   *
   * This destination was gleaned from reaction rule with machine name
   * "rules_on_login_redirect_to_dashboard".
   *
   * @return \Drupal\Core\Url
   *   The destination path.
   */
  public function postLoginPath() {
    return Url::fromUri("internal:/dashboard");
  }

  /**
   * Clears the session flag and redirects the user to the post-login destination.
   */
  public function abort() {
    if ($this->session !== NULL) {
      $this->session->remove('usfb_address_check');
    }
    $url = $this->postLoginPath()->toString();
    $response = new RedirectResponse($url);
    $response->send();
  }

  /**
   * Returns the formatted address.
   *
   * @param object $address
   *   The address data.
   *
   * @return string
   *   A properly-formatted international address.
   */
  function formatAddress($address) {
    // Add in empty text.
    foreach ($address as $field => $add) {
      if (empty($add) && $field !== 'addressLine2') {
        $address->{$field} = '**EMPTY**';
      }
    }
    $city = [$address->city, $address->stateOrProvince, $address->countryCode];
    $city = array_filter($city);
    $line1 = [$address->addressLine1, $address->addressLine2];
    $line1 = array_filter($line1);
    $complete = array_filter([
        implode(' ', $line1),
        implode(', ', $city),
        $address->zipOrPostalCode,
        $address->cellPhone,
    ]);
    return '<pre>' . implode('<br>', $complete) . '</pre>';
  }

  /**
   * Returns markup for the address confirmation buttons.
   *
   * @param string $button_text
   *   The label for the first button.
   * @param int $uid
   *   The account's user ID.
   *
   * @return string
   *   Button markup for address confirmation.
   */
  function formatButtons($button_text, $uid) {
    $output  = '<div class="" role="group">';

    $output .= Link::fromTextAndUrl(
      t($button_text),
      Url::fromUri("internal:/user/{$uid}/edit/address",
        [
          'attributes' => [
            'class' => ['btn', 'btn-default'],
            'style' => 'margin-right: 1em;',
          ],
        ]
      )
    )->toString();

    $output .= Link::fromTextAndUrl(
      t('Done'),
      Url::fromRoute("<current>", [],
        [
          'attributes' => [
            'onclick' => 'jQuery("button.close").click(); return false;',
            'class' => ['btn', 'btn-primary'],
          ],
        ]
      )
    )->toString();

    $output .= '</div>';
    return $output;
  }

  /**
   * Updates the USFB Address Date field for the given user.
   *
   * @param int $uid
   *   (optional) The user to update. Defaults to the current logged in user.
   * @param int $timestamp
   *   (optional) What date timestamp to save the field to. Defaults to the
   *   current time.
   *
   * @return int|bool
   *   The output from saving the entity. FALSE on failure.
   */
  function updateAddressDate($uid = NULL, $timestamp = NULL) {
    // Load the entity wrapper for the user.
    $id = $uid !== NULL ? $uid : $this->currentUser->id();
    $account = User::load($id);

    // Fail safe.
    if ($account === NULL) {
      return FALSE;
    }

    // Find the time to save.
    $time = isset($timestamp) ? $timestamp : $this->time->getRequestTime();
    $account->set('field_usfb_address_date', $time);
    return $account->save();
  }

  /**
   * Retrieve all the international phone data.
   *
   * @return array
   */
  public function getPhoneCodes() {
    return [
      'US' => ['value' => '1', 'text' => 'United States'],
      'DZ' => ['value' => '213', 'text' => 'Algeria'],
      'AD' => ['value' => '376', 'text' => 'Andorra'],
      'AO' => ['value' => '244', 'text' => 'Angola'],
      'AI' => ['value' => '1264', 'text' => 'Anguilla'],
      'AG' => ['value' => '1268', 'text' => 'Antigua &amp; Barbuda'],
      'AR' => ['value' => '54', 'text' => 'Argentina'],
      'AM' => ['value' => '374', 'text' => 'Armenia'],
      'AW' => ['value' => '297', 'text' => 'Aruba'],
      'AU' => ['value' => '61', 'text' => 'Australia'],
      'AT' => ['value' => '43', 'text' => 'Austria'],
      'AZ' => ['value' => '994', 'text' => 'Azerbaijan'],
      'BS' => ['value' => '1242', 'text' => 'Bahamas'],
      'BH' => ['value' => '973', 'text' => 'Bahrain'],
      'BD' => ['value' => '880', 'text' => 'Bangladesh'],
      'BB' => ['value' => '1246', 'text' => 'Barbados'],
      'BY' => ['value' => '375', 'text' => 'Belarus'],
      'BE' => ['value' => '32', 'text' => 'Belgium'],
      'BZ' => ['value' => '501', 'text' => 'Belize'],
      'BJ' => ['value' => '229', 'text' => 'Benin'],
      'BM' => ['value' => '1441', 'text' => 'Bermuda'],
      'BT' => ['value' => '975', 'text' => 'Bhutan'],
      'BO' => ['value' => '591', 'text' => 'Bolivia'],
      'BA' => ['value' => '387', 'text' => 'Bosnia Herzegovina'],
      'BW' => ['value' => '267', 'text' => 'Botswana'],
      'BR' => ['value' => '55', 'text' => 'Brazil'],
      'BN' => ['value' => '673', 'text' => 'Brunei'],
      'BG' => ['value' => '359', 'text' => 'Bulgaria'],
      'BF' => ['value' => '226', 'text' => 'Burkina Faso'],
      'BI' => ['value' => '257', 'text' => 'Burundi'],
      'KH' => ['value' => '855', 'text' => 'Cambodia'],
      'CM' => ['value' => '237', 'text' => 'Cameroon'],
      'CA' => ['value' => '1', 'text' => 'Canada'],
      'CV' => ['value' => '238', 'text' => 'Cape Verde Islands'],
      'KY' => ['value' => '1345', 'text' => 'Cayman Islands'],
      'CF' => ['value' => '236', 'text' => 'Central African Republic'],
      'CL' => ['value' => '56', 'text' => 'Chile'],
      'CN' => ['value' => '86', 'text' => 'China'],
      'CO' => ['value' => '57', 'text' => 'Colombia'],
      'KM' => ['value' => '269', 'text' => 'Comoros'],
      'CG' => ['value' => '242', 'text' => 'Congo'],
      'CK' => ['value' => '682', 'text' => 'Cook Islands'],
      'CR' => ['value' => '506', 'text' => 'Costa Rica'],
      'HR' => ['value' => '385', 'text' => 'Croatia'],
      'CU' => ['value' => '53', 'text' => 'Cuba'],
      'CY' => ['value' => '90392', 'text' => 'Cyprus North'],
      'CY' => ['value' => '357', 'text' => 'Cyprus South'],
      'CZ' => ['value' => '42', 'text' => 'Czech Republic'],
      'DK' => ['value' => '45', 'text' => 'Denmark'],
      'DJ' => ['value' => '253', 'text' => 'Djibouti'],
      'DM' => ['value' => '1809', 'text' => 'Dominica'],
      'DO' => ['value' => '1809', 'text' => 'Dominican Republic'],
      'EC' => ['value' => '593', 'text' => 'Ecuador'],
      'EG' => ['value' => '20', 'text' => 'Egypt'],
      'SV' => ['value' => '503', 'text' => 'El Salvador'],
      'GQ' => ['value' => '240', 'text' => 'Equatorial Guinea'],
      'ER' => ['value' => '291', 'text' => 'Eritrea'],
      'EE' => ['value' => '372', 'text' => 'Estonia'],
      'ET' => ['value' => '251', 'text' => 'Ethiopia'],
      'FK' => ['value' => '500', 'text' => 'Falkland Islands'],
      'FO' => ['value' => '298', 'text' => 'Faroe Islands'],
      'FJ' => ['value' => '679', 'text' => 'Fiji'],
      'FI' => ['value' => '358', 'text' => 'Finland'],
      'FR' => ['value' => '33', 'text' => 'France'],
      'GF' => ['value' => '594', 'text' => 'French Guiana'],
      'PF' => ['value' => '689', 'text' => 'French Polynesia'],
      'GA' => ['value' => '241', 'text' => 'Gabon'],
      'GM' => ['value' => '220', 'text' => 'Gambia'],
      'GE' => ['value' => '7880', 'text' => 'Georgia'],
      'DE' => ['value' => '49', 'text' => 'Germany'],
      'GH' => ['value' => '233', 'text' => 'Ghana'],
      'GI' => ['value' => '350', 'text' => 'Gibraltar'],
      'GR' => ['value' => '30', 'text' => 'Greece'],
      'GL' => ['value' => '299', 'text' => 'Greenland'],
      'GD' => ['value' => '1473', 'text' => 'Grenada'],
      'GP' => ['value' => '590', 'text' => 'Guadeloupe'],
      'GU' => ['value' => '671', 'text' => 'Guam'],
      'GT' => ['value' => '502', 'text' => 'Guatemala'],
      'GN' => ['value' => '224', 'text' => 'Guinea'],
      'GW' => ['value' => '245', 'text' => 'Guinea - Bissau'],
      'GY' => ['value' => '592', 'text' => 'Guyana'],
      'HT' => ['value' => '509', 'text' => 'Haiti'],
      'HN' => ['value' => '504', 'text' => 'Honduras'],
      'HK' => ['value' => '852', 'text' => 'Hong Kong'],
      'HU' => ['value' => '36', 'text' => 'Hungary'],
      'IS' => ['value' => '354', 'text' => 'Iceland'],
      'IN' => ['value' => '91', 'text' => 'India'],
      'ID' => ['value' => '62', 'text' => 'Indonesia'],
      'IR' => ['value' => '98', 'text' => 'Iran'],
      'IQ' => ['value' => '964', 'text' => 'Iraq'],
      'IE' => ['value' => '353', 'text' => 'Ireland'],
      'IL' => ['value' => '972', 'text' => 'Israel'],
      'IT' => ['value' => '39', 'text' => 'Italy'],
      'JM' => ['value' => '1876', 'text' => 'Jamaica'],
      'JP' => ['value' => '81', 'text' => 'Japan'],
      'JO' => ['value' => '962', 'text' => 'Jordan'],
      'KZ' => ['value' => '7', 'text' => 'Kazakhstan'],
      'KE' => ['value' => '254', 'text' => 'Kenya'],
      'KI' => ['value' => '686', 'text' => 'Kiribati'],
      'KP' => ['value' => '850', 'text' => 'Korea North'],
      'KR' => ['value' => '82', 'text' => 'Korea South'],
      'KW' => ['value' => '965', 'text' => 'Kuwait'],
      'KG' => ['value' => '996', 'text' => 'Kyrgyzstan'],
      'LA' => ['value' => '856', 'text' => 'Laos'],
      'LV' => ['value' => '371', 'text' => 'Latvia'],
      'LB' => ['value' => '961', 'text' => 'Lebanon'],
      'LS' => ['value' => '266', 'text' => 'Lesotho'],
      'LR' => ['value' => '231', 'text' => 'Liberia'],
      'LY' => ['value' => '218', 'text' => 'Libya'],
      'LI' => ['value' => '417', 'text' => 'Liechtenstein'],
      'LT' => ['value' => '370', 'text' => 'Lithuania'],
      'LU' => ['value' => '352', 'text' => 'Luxembourg'],
      'MO' => ['value' => '853', 'text' => 'Macao'],
      'MK' => ['value' => '389', 'text' => 'Macedonia'],
      'MG' => ['value' => '261', 'text' => 'Madagascar'],
      'MW' => ['value' => '265', 'text' => 'Malawi'],
      'MY' => ['value' => '60', 'text' => 'Malaysia'],
      'MV' => ['value' => '960', 'text' => 'Maldives'],
      'ML' => ['value' => '223', 'text' => 'Mali'],
      'MT' => ['value' => '356', 'text' => 'Malta'],
      'MH' => ['value' => '692', 'text' => 'Marshall Islands'],
      'MQ' => ['value' => '596', 'text' => 'Martinique'],
      'MR' => ['value' => '222', 'text' => 'Mauritania'],
      'YT' => ['value' => '269', 'text' => 'Mayotte'],
      'MX' => ['value' => '52', 'text' => 'Mexico'],
      'FM' => ['value' => '691', 'text' => 'Micronesia'],
      'MD' => ['value' => '373', 'text' => 'Moldova'],
      'MC' => ['value' => '377', 'text' => 'Monaco'],
      'MN' => ['value' => '976', 'text' => 'Mongolia'],
      'MS' => ['value' => '1664', 'text' => 'Montserrat'],
      'MA' => ['value' => '212', 'text' => 'Morocco'],
      'MZ' => ['value' => '258', 'text' => 'Mozambique'],
      'MN' => ['value' => '95', 'text' => 'Myanmar'],
      'NA' => ['value' => '264', 'text' => 'Namibia'],
      'NR' => ['value' => '674', 'text' => 'Nauru'],
      'NP' => ['value' => '977', 'text' => 'Nepal'],
      'NL' => ['value' => '31', 'text' => 'Netherlands'],
      'NC' => ['value' => '687', 'text' => 'New Caledonia'],
      'NZ' => ['value' => '64', 'text' => 'New Zealand'],
      'NI' => ['value' => '505', 'text' => 'Nicaragua'],
      'NE' => ['value' => '227', 'text' => 'Niger'],
      'NG' => ['value' => '234', 'text' => 'Nigeria'],
      'NU' => ['value' => '683', 'text' => 'Niue'],
      'NF' => ['value' => '672', 'text' => 'Norfolk Islands'],
      'NP' => ['value' => '670', 'text' => 'Northern Marianas'],
      'NO' => ['value' => '47', 'text' => 'Norway'],
      'OM' => ['value' => '968', 'text' => 'Oman'],
      'PW' => ['value' => '680', 'text' => 'Palau'],
      'PA' => ['value' => '507', 'text' => 'Panama'],
      'PG' => ['value' => '675', 'text' => 'Papua New Guinea'],
      'PY' => ['value' => '595', 'text' => 'Paraguay'],
      'PE' => ['value' => '51', 'text' => 'Peru'],
      'PH' => ['value' => '63', 'text' => 'Philippines'],
      'PL' => ['value' => '48', 'text' => 'Poland'],
      'PT' => ['value' => '351', 'text' => 'Portugal'],
      'PR' => ['value' => '1787', 'text' => 'Puerto Rico'],
      'QA' => ['value' => '974', 'text' => 'Qatar'],
      'RE' => ['value' => '262', 'text' => 'Reunion'],
      'RO' => ['value' => '40', 'text' => 'Romania'],
      'RU' => ['value' => '7', 'text' => 'Russia'],
      'RW' => ['value' => '250', 'text' => 'Rwanda'],
      'SM' => ['value' => '378', 'text' => 'San Marino'],
      'ST' => ['value' => '239', 'text' => 'Sao Tome &amp; Principe'],
      'SA' => ['value' => '966', 'text' => 'Saudi Arabia'],
      'SN' => ['value' => '221', 'text' => 'Senegal'],
      'CS' => ['value' => '381', 'text' => 'Serbia'],
      'SC' => ['value' => '248', 'text' => 'Seychelles'],
      'SL' => ['value' => '232', 'text' => 'Sierra Leone'],
      'SG' => ['value' => '65', 'text' => 'Singapore'],
      'SK' => ['value' => '421', 'text' => 'Slovak Republic'],
      'SI' => ['value' => '386', 'text' => 'Slovenia'],
      'SB' => ['value' => '677', 'text' => 'Solomon Islands'],
      'SO' => ['value' => '252', 'text' => 'Somalia'],
      'ZA' => ['value' => '27', 'text' => 'South Africa'],
      'ES' => ['value' => '34', 'text' => 'Spain'],
      'LK' => ['value' => '94', 'text' => 'Sri Lanka'],
      'SH' => ['value' => '290', 'text' => 'St. Helena'],
      'KN' => ['value' => '1869', 'text' => 'St. Kitts'],
      'SC' => ['value' => '1758', 'text' => 'St. Lucia'],
      'SD' => ['value' => '249', 'text' => 'Sudan'],
      'SR' => ['value' => '597', 'text' => 'Suriname'],
      'SZ' => ['value' => '268', 'text' => 'Swaziland'],
      'SE' => ['value' => '46', 'text' => 'Sweden'],
      'CH' => ['value' => '41', 'text' => 'Switzerland'],
      'SI' => ['value' => '963', 'text' => 'Syria'],
      'TW' => ['value' => '886', 'text' => 'Taiwan'],
      'TJ' => ['value' => '7', 'text' => 'Tajikstan'],
      'TH' => ['value' => '66', 'text' => 'Thailand'],
      'TG' => ['value' => '228', 'text' => 'Togo'],
      'TO' => ['value' => '676', 'text' => 'Tonga'],
      'TT' => ['value' => '1868', 'text' => 'Trinidad &amp; Tobago'],
      'TN' => ['value' => '216', 'text' => 'Tunisia'],
      'TR' => ['value' => '90', 'text' => 'Turkey'],
      'TM' => ['value' => '7', 'text' => 'Turkmenistan'],
      'TM' => ['value' => '993', 'text' => 'Turkmenistan'],
      'TC' => ['value' => '1649', 'text' => 'Turks &amp; Caicos Islands'],
      'TV' => ['value' => '688', 'text' => 'Tuvalu'],
      'UG' => ['value' => '256', 'text' => 'Uganda'],
      'GB' => ['value' => '44', 'text' => 'UK'],
      'UA' => ['value' => '380', 'text' => 'Ukraine'],
      'AE' => ['value' => '971', 'text' => 'United Arab Emirates'],
      'UY' => ['value' => '598', 'text' => 'Uruguay'],
      'US' => ['value' => '1', 'text' => 'USA'],
      'UZ' => ['value' => '7', 'text' => 'Uzbekistan'],
      'VU' => ['value' => '678', 'text' => 'Vanuatu'],
      'VA' => ['value' => '379', 'text' => 'Vatican City'],
      'VE' => ['value' => '58', 'text' => 'Venezuela'],
      'VN' => ['value' => '84', 'text' => 'Vietnam'],
      'VG' => ['value' => '84', 'text' => 'Virgin Islands - British'],
      'VI' => ['value' => '84', 'text' => 'Virgin Islands - US'],
      'WF' => ['value' => '681', 'text' => 'Wallis &amp; Futuna'],
      'YE' => ['value' => '969', 'text' => 'Yemen (North]'],
      'YE' => ['value' => '967', 'text' => 'Yemen (South]'],
      'ZM' => ['value' => '260', 'text' => 'Zambia'],
      'ZW' => ['value' => '263', 'text' => 'Zimbabwe'],
    ];
  }

  /**
   * List of blacklisted Residence Hall or Campus addresses.
   *
   * @return array
   *   An array of all the residences.
   */
  function residenceCampusAddresses() {
    $addresses = [];
    $addresses['Fromm'] = [
      'thoroughfare' => '2497 Golden Gate',
      'locality' => 'San Francisco',
      'country' => 'US',
      'postal_code' => '94118',
    ];
    $addresses['Gillson'] = [
      'thoroughfare' => '2325 Golden Gate',
      'locality' => 'San Francisco',
      'country' => 'US',
      'postal_code' => '94118',
    ];
    $addresses['Hayes-Healy'] = [
      'thoroughfare' => '2305 Golden Gate',
      'locality' => 'San Francisco',
      'country' => 'US',
      'postal_code' => '94118',
    ];
    $addresses['Lone Mountain'] = [
      'thoroughfare' => '330 Parker',
      'locality' => 'San Francisco',
      'country' => 'US',
      'postal_code' => '94118',
    ];
    $addresses['Loyola Village'] = [
      'thoroughfare' => '331 Anza Street',
      'locality' => 'San Francisco',
      'country' => 'US',
      'postal_code' => '94118',
    ];
    $addresses['Pedro Arrupe'] = [
      'thoroughfare' => '490 6th',
      'locality' => 'San Francisco',
      'country' => 'US',
      'postal_code' => '94118',
    ];
    $addresses['Phelan'] = [
      'thoroughfare' => '2345 Golden Gate',
      'locality' => 'San Francisco',
      'country' => 'US',
      'postal_code' => '94118',
    ];
    $addresses['Main Campus'] = [
      'thoroughfare' => '2130 Fulton',
      'locality' => 'San Francisco',
      'country' => 'US',
      'postal_code' => '94117',
    ];

    $addresses['Downtown Campus'] = [
      'thoroughfare' => '101 Howard',
      'premise' => '500',
      'locality' => 'San Francisco',
      'country' => 'US',
      'postal_code' => '94105',
    ];
    $addresses['Branch : Orange County'] = [
      'thoroughfare' => '480 South Batavia',
      'locality' => 'San Francisco',
      'country' => 'US',
      'postal_code' => '92868',
    ];
    $addresses['Branch : Sacramento'] = [
      'thoroughfare' => '1 Capitol Mall',
      'premise' => '100',
      'locality' => 'San Francisco',
      'country' => 'US',
      'postal_code' => '95814',
    ];
    $addresses['Branch : Santa Rosa'] = [
      'thoroughfare' => '416 B',
      'locality' => 'San Francisco',
      'country' => 'US',
      'postal_code' => '95401',
    ];
    $addresses['Branch : Pleasanton'] = [
      'thoroughfare' => '6120 Stoneridge Mall',
      'premise' => '150',
      'locality' => 'San Francisco',
      'country' => 'US',
      'postal_code' => '94588',
    ];
    $addresses['Branch : San Jose'] = [
      'thoroughfare' => '125 S. Market',
      'premise' => '200',
      'locality' => 'San Francisco',
      'country' => 'US',
      'postal_code' => '95113',
    ];
    return $addresses;
  }
}