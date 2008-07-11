<?php
/**
 * UPS realtime shipping calculator
 
Wed Mar 22 08:20:26 EST 2006
 *
 * $Id: cmShipMethod_USPS.class.php,v 1.5 2006/11/30 02:27:57 sbeam Exp $
 */
require_once("HTTP/Request.php");
require_once('circusShop/cmShipMethod.class.php');

class cmShipMethod_USPS extends cmShipMethod {

    /** using USPS live server, or not? */
    var $testmode = false;

    /** will log API transactions to debug_log file */
    var $debug = false;
    var $debug_log = '/tmp/cmShipMethod.log';

    /** user id assigned for this site bu USPS Web Tools site */
    var $usps_api_username = '002CIRCU5691';

    /** usps package size code, REGULAR, LARGE or OVERSIZE */
    var $_package_size = 'REGULAR';

    /** USPS Container type [envelope, box] - only matters for priority mail */
    var $_container_type = 'box';

    /** types of shipping this shipper provides, CODE => 'Name' - i.e. '2DA' =>
     * '2nd Day Air' */
    /* note, in USPS case they don't have codes, they return a verbose service
     * name in the XML. So here we are actually shortening it for display */
    var $ship_types = array(
       'Express Mail PO to Addressee' => 'Express Mail',
       'First-Class Mail' => 'First-Class Mail',
       'Priority Mail' => 'Priority Mail',
       'Parcel Post' => 'Parcel Post',
       'Bound Printer Matter' => 'Bound Printer Matter Mail',
       'Library Mail' => 'Library Mail',
       'Media Mail' => 'Media Mail',
       'Global Express Guaranteed (GXG) Non-Document Service' => 'Global Express Guaranteed',
       'Global Express Guaranteed (GXG) Document Service' => 'Global Express Guaranteed Document',
       'Global Express Guaranteed Non-Document Service' => 'Global Express Guaranteed',
       'Global Express Guaranteed Document Service' => 'Global Express Guaranteed Document',
       'Global Express Guaranteed Non-Document Service' => 'Global Express Guaranteed',
       'Global Express Mail (EMS)' => 'Global Express Mail',
       'Global Priority Mail - Flat-rate Envelope (Large)' => 'Global Priority Mail',
       'Global Priority Mail - Flat-rate Envelope (Small)' => 'Global Priority Mail',
       'Global Priority Mail - Variable Weight (Single)' => 'Global Priority Mail',
       'Airmail Letter Post' => 'Airmail Letter Post',
       'Airmail Parcel Post' => 'Airmail Parcel Post',
       'Economy (Surface) Parcel Post' => 'Economy (Surface) Parcel Post',
       'Economy (Surface) Letter Post' => 'Economy Letter Post',
       );


    /** keys from the above that we are actually using in this implementation */
    var $allowed_ship_types = array('Express Mail PO to Addressee', 
                                    'Priority Mail', 
                                    'Parcel Post',
                                    'Global Express Mail (EMS)',
                                    'Global Priority Mail - Variable Weight (Single)',
                                    'Global Express Guaranteed (GXG) Non-Document Service',
                                    'Global Express Guaranteed Non-Document Service',
                                    'Airmail Parcel Post',
                                    );


    var $_service_url = 'http://production.shippingapis.com/shippingapi.dll';
    var $_service_url_test = 'http://testing.shippingapis.com/ShippingAPITest.dll';

    var $_name = 'U.S. Postal Service';



    /** pulling in quotes from all avail types and putting into a nice array
     */
    function get_all_quotes(&$cart, $adder=0) {
        $res = array();

        $allquotes = $this->quote();
        if (!is_array($allquotes)) {
            $msg = $this->_name . ": Shipping calculation error! ";
            $msg .= (PEAR::isError($allquotes))? $allquotes->getMessage() : $allquotes;
            return $this->raiseError($msg);
        }
        // allquotes = ('Express Mail' => (rate => 14.40, time => '2 - 3 days'), etc...
        asort($allquotes); // sort the quotes by cost
        foreach ($allquotes as $type => $q) {
            if (in_array($type, $this->allowed_ship_types)) {
                $q += $adder;

                if (isset($q['time'])) {
                    $opt = sprintf("%s: %s [%s] (%.02f)", 
                                    $this->get_name(), 
                                    $this->ship_types[$type], 
                                    $q['time'],
                                    $q['rate']);
                }
                else {
                    $opt = sprintf("%s: %s (%.02f)", 
                                   $this->get_name(), 
                                   $this->ship_types[$type], 
                                   $q['rate']);
                }
                $res[$opt] = $opt;
            }
        }
        return $res;
    }


    /* get a quote for the given type */
    function quote() {
        return $this->send_api_query();
    }

    /* convert decimal weight (in pounds!) into lbs/oz
     * @param float $wt the weight
     * @return array(int pounds, int ounces)
     */
    function _weight_to_lbs_oz($wt) {
        // usps doesnt accept zero weight
        if ($wt == 0) return array(0, 1);
        $lbs = floor($wt);
        $oz = round(16 * ($wt - floor($wt)));
        return array($lbs, $oz);
    }

    /** USPS has 3 package sizes, REGULAR, LARGE, OVERSIZE
     * determine which one
     * TODO need some way to figure it to do this correctly. But we don't know
     * the physical size of any products as yet. So we guess for now that
     * things are reasonably small. Could prob. tie into cm_categories Shipping
     * Class somehow, as a next step.

     * Regular: package length plus girth  (84 inches or less)
     * Large: package length plus girth (Priority Mail and Parcel Post parcels
     *      that weigh less than 15 pounds but measure more than 84 inches (but less
     *      than 108 inches) in combined length and girth are charged the applicable
     *      rate for a 15-pound parcel.) 
     * Oversize: package length plus girth (more than 108 but not more than 130
     *      inches)
     */
    function _get_package_size() {
        if (isset($this->_package_size)) {
            return $this->_package_size;
        }
        else {
            return 'REGULAR';
        }
    }

    /** get USPS container type.
    * The <Container> tag is only applicable for Express Mail and Priority
    * Mail.  The tag will be ignored if specified with any other service type.
    * When used, the <Container> field must contain one of the following valid
    * packaging type names:
    *
    * Flat Rate Envelope Express Mail Flat Rate Envelope, 12.5" x 9.5
    * Flat Rate Box Priority Mail Flat Rate Box, 14" x 12" x 3.5"
    *               Priority Mail Flat Rate Box, 11.25" x 8.75" x 6"
    */
    function _get_container_type() {
        switch ($this->_container_type) {
            case 'envelope':
                return 'Flat Rate Envelope';
                break;
            case 'box':
                return 'Flat Rate Box';
                break;
        }
    }
   
    function _get_machinable_flag() {
        return 'TRUE';
    }


    /** sends a GET request to a server. see what happends */
    function send_api_query() {
        if ($this->debug) {
            $log = "==\n".get_class($this) . "::send_api_query()\n" . date('r');
            $log .= "\nIP: " . $_SERVER['REMOTE_ADDR'];
            error_log($log, 3, $this->debug_log);
        }

        list($pounds, $ounces) = $this->_weight_to_lbs_oz($this->_weight);

        if ($this->_origin['country'] != 'US') { // sanity check
            return;
        }

        if ($this->_dest['country'] == 'US') { // domestic mail
            $api_name = 'RateV2';
            $req_tag = 'RateV2Request';
            $params = array('Service' => 'All',
                            'ZipOrigination' => $this->_origin['postcode'],
                            'ZipDestination' => $this->_dest['postcode'],
                            'Pounds' => $pounds,
                            'Ounces' => $ounces,
                            'Container' => $this->_get_container_type(),
                            'Size' => $this->_get_package_size(),
                            'Machinable' => $this->_get_machinable_flag(),
                            );


        }
        else { // Int'l
            $api_name = 'IntlRate';
            $req_tag = 'IntlRateRequest';
            $params = array('Pounds' => $pounds,
                            'Ounces' => $ounces,
                            'MailType'=>'Package',
                            'Country'=> $this->get_country_by_code($this->_dest['country']), 
                            );
        }

        $xml = "<$req_tag" . ' USERID="'.$this->usps_api_username.'"><Package ID="0">';
        $xml .= "\n";
        foreach ($params as $k => $v) {
            $xml .= sprintf("<%s>%s</%s>\n", $k, htmlentities($v), $k);
        }
        $xml .= '</Package></'.$req_tag.'>';

        /* contruct a URL to send */
        $url = ($this->testmode)? $this->_service_url_test : $this->_service_url;
        $url .= '?API='.$api_name;
        if ($this->debug) {
            error_log("\n== REQUEST to $url ==\n$xml", 3, $this->debug_log);
        }
        $url .= '&XML=' . urlencode($xml);

        $http =& new HTTP_Request($url);

        if (PEAR::isError($http->sendRequest())) {
            return $http;
        }
        $res = $http->getResponseBody();
        return $this->parse_response($res);
    }


    /** parse shippingAPI XML response - could be either the domestic or Int'l version */
    function parse_response($body) {

        if ($this->debug) {
            error_log("\n== RESPONSE ==\n$body", 3, $this->debug_log);
        }

        $quotes = array();
        // rid of xml prolog for efficiency
        $body = preg_replace('/^<\?xml [^>]+>[\n\r\s]*/', '', $body);

        if (preg_match('/<Error>(.*?)<\/Error>/', $body, $errchunk)) {
            preg_match('/<Description>(.*?)<\/Description>/', $errchunk[1], $m_err);
            return $this->raiseError($m_err[1]);
        }

        /* handle an International response */
        if (preg_match('/^<IntlRateResponse>/', $body)) {
            if (!preg_match_all('/<Service ID="\d+">(.*?)<\/Service>/', $body, $m)) {
                return $this->raiseError('Could not parse USPS server response');
            }
            else {
                foreach ($m[1] as $postage) {
                    if (preg_match('/<SvcDescription>(.*?)<\/SvcDescription>/', $postage, $service)) {
                        $k = $service[1]; // service descript. string becomes array key
                        preg_match('/<Postage>(.*?)<\/Postage>/', $postage, $rate);
                        $quotes[$k] = array('rate' => $rate[1]);
                        /** add ship time if USPS gave it to us we put it in */
                        if (preg_match('/<SvcCommitments>(.*?)<\/SvcCommitments>/', $postage, $scm)) {
                            $quotes[$k]['time'] = $scm[1];
                        }
                    }
                }
            }
        }
        else { // domestic mail is totally different
            if (!preg_match_all('/<Postage\s*>(.*?)<\/Postage>/', $body, $m)) {
                return $this->raiseError('Could not parse USPS server response');
            }
            else {
                foreach ($m[1] as $postage) {
                    if (preg_match('/<MailService>(.*?)<\/MailService>/', $postage, $service)) {
                        preg_match('/<Rate>(.*?)<\/Rate>/', $postage, $rate);
                        $quotes[$service[1]] = array('rate' => $rate[1]);
                    }
                }
            }
        }

        if ($this->debug) {
            $log = '';
            foreach ($quotes as $k => $v) {
                $log .= "$k => {$v['rate']} ";
                if (isset($v['time'])) $log .= ", " . $v['time'];
                $log .= "\n";
            }
            error_log("\n== QUOTES ==\n$log", 3, $this->debug_log);
        }
        
        return $quotes;
    }


     /** get country name by ISO 2-letter code. cShop stores countries by code,
      * USPS expects one of the standard country names they made up
      *
      * this list was cribbed from osCommerce and seems to make the USPS server
      * happy. Our default list from onset::formex() failed for some of the smaller countries
      *
      * @see http://pe.usps.gov/text/Imm/Immctry.html (even though not all those work either) 
     */
     function get_country_by_code($code)
     {

        $cc = array('AF' => 'Afghanistan',
                    'AL' => 'Albania',
                    'DZ' => 'Algeria',
                    'AD' => 'Andorra',
                    'AO' => 'Angola',
                    'AI' => 'Anguilla',
                    'AG' => 'Antigua and Barbuda',
                    'AR' => 'Argentina',
                    'AM' => 'Armenia',
                    'AW' => 'Aruba',
                    'AU' => 'Australia',
                    'AT' => 'Austria',
                    'AZ' => 'Azerbaijan',
                    'BS' => 'Bahamas',
                    'BH' => 'Bahrain',
                    'BD' => 'Bangladesh',
                    'BB' => 'Barbados',
                    'BY' => 'Belarus',
                    'BE' => 'Belgium',
                    'BZ' => 'Belize',
                    'BJ' => 'Benin',
                    'BM' => 'Bermuda',
                    'BT' => 'Bhutan',
                    'BO' => 'Bolivia',
                    'BA' => 'Bosnia-Herzegovina',
                    'BW' => 'Botswana',
                    'BR' => 'Brazil',
                    'VG' => 'British Virgin Islands',
                    'BN' => 'Brunei Darussalam',
                    'BG' => 'Bulgaria',
                    'BF' => 'Burkina Faso',
                    'MM' => 'Burma',
                    'BI' => 'Burundi',
                    'KH' => 'Cambodia',
                    'CM' => 'Cameroon',
                    'CA' => 'Canada',
                    'CV' => 'Cape Verde',
                    'KY' => 'Cayman Islands',
                    'CF' => 'Central African Republic',
                    'TD' => 'Chad',
                    'CL' => 'Chile',
                    'CN' => 'China',
                    'CX' => 'Christmas Island (Australia)',
                    'CC' => 'Cocos Island (Australia)',
                    'CO' => 'Colombia',
                    'KM' => 'Comoros',
                    'CG' => 'Congo (Brazzaville),Republic of the',
                    'ZR' => 'Congo, Democratic Republic of the',
                    'CK' => 'Cook Islands (New Zealand)',
                    'CR' => 'Costa Rica',
                    'CI' => 'Cote d\'Ivoire (Ivory Coast)',
                    'HR' => 'Croatia',
                    'CU' => 'Cuba',
                    'CY' => 'Cyprus',
                    'CZ' => 'Czech Republic',
                    'DK' => 'Denmark',
                    'DJ' => 'Djibouti',
                    'DM' => 'Dominica',
                    'DO' => 'Dominican Republic',
                    'TP' => 'East Timor (Indonesia)',
                    'EC' => 'Ecuador',
                    'EG' => 'Egypt',
                    'SV' => 'El Salvador',
                    'GQ' => 'Equatorial Guinea',
                    'ER' => 'Eritrea',
                    'EE' => 'Estonia',
                    'ET' => 'Ethiopia',
                    'FK' => 'Falkland Islands',
                    'FO' => 'Faroe Islands',
                    'FJ' => 'Fiji',
                    'FI' => 'Finland',
                    'FR' => 'France',
                    'GF' => 'French Guiana',
                    'PF' => 'French Polynesia',
                    'GA' => 'Gabon',
                    'GM' => 'Gambia',
                    'GE' => 'Georgia, Republic of',
                    'DE' => 'Germany',
                    'GH' => 'Ghana',
                    'GI' => 'Gibraltar',
                    'GB' => 'Great Britain and Northern Ireland',
                    'GR' => 'Greece',
                    'GL' => 'Greenland',
                    'GD' => 'Grenada',
                    'GP' => 'Guadeloupe',
                    'GT' => 'Guatemala',
                    'GN' => 'Guinea',
                    'GW' => 'Guinea-Bissau',
                    'GY' => 'Guyana',
                    'HT' => 'Haiti',
                    'HN' => 'Honduras',
                    'HK' => 'Hong Kong',
                    'HU' => 'Hungary',
                    'IS' => 'Iceland',
                    'IN' => 'India',
                    'ID' => 'Indonesia',
                    'IR' => 'Iran',
                    'IQ' => 'Iraq',
                    'IE' => 'Ireland',
                    'IL' => 'Israel',
                    'IT' => 'Italy',
                    'JM' => 'Jamaica',
                    'JP' => 'Japan',
                    'JO' => 'Jordan',
                    'KZ' => 'Kazakhstan',
                    'KE' => 'Kenya',
                    'KI' => 'Kiribati',
                    'KW' => 'Kuwait',
                    'KG' => 'Kyrgyzstan',
                    'LA' => 'Laos',
                    'LV' => 'Latvia',
                    'LB' => 'Lebanon',
                    'LS' => 'Lesotho',
                    'LR' => 'Liberia',
                    'LY' => 'Libya',
                    'LI' => 'Liechtenstein',
                    'LT' => 'Lithuania',
                    'LU' => 'Luxembourg',
                    'MO' => 'Macao',
                    'MK' => 'Macedonia, Republic of',
                    'MG' => 'Madagascar',
                    'MW' => 'Malawi',
                    'MY' => 'Malaysia',
                    'MV' => 'Maldives',
                    'ML' => 'Mali',
                    'MT' => 'Malta',
                    'MQ' => 'Martinique',
                    'MR' => 'Mauritania',
                    'MU' => 'Mauritius',
                    'YT' => 'Mayotte (France)',
                    'MX' => 'Mexico',
                    'MD' => 'Moldova',
                    'MC' => 'Monaco (France)',
                    'MN' => 'Mongolia',
                    'MS' => 'Montserrat',
                    'MA' => 'Morocco',
                    'MZ' => 'Mozambique',
                    'NA' => 'Namibia',
                    'NR' => 'Nauru',
                    'NP' => 'Nepal',
                    'NL' => 'Netherlands',
                    'AN' => 'Netherlands Antilles',
                    'NC' => 'New Caledonia',
                    'NZ' => 'New Zealand',
                    'NI' => 'Nicaragua',
                    'NE' => 'Niger',
                    'NG' => 'Nigeria',
                    'KP' => 'North Korea (Korea, Democratic People\'s Republic of)',
                    'NO' => 'Norway',
                    'OM' => 'Oman',
                    'PK' => 'Pakistan',
                    'PA' => 'Panama',
                    'PG' => 'Papua New Guinea',
                    'PY' => 'Paraguay',
                    'PE' => 'Peru',
                    'PH' => 'Philippines',
                    'PN' => 'Pitcairn Island',
                    'PL' => 'Poland',
                    'PT' => 'Portugal',
                    'QA' => 'Qatar',
                    'RE' => 'Reunion',
                    'RO' => 'Romania',
                    'RU' => 'Russia',
                    'RW' => 'Rwanda',
                    'SH' => 'Saint Helena',
                    'KN' => 'Saint Kitts (St. Christopher and Nevis)',
                    'LC' => 'Saint Lucia',
                    'PM' => 'Saint Pierre and Miquelon',
                    'VC' => 'Saint Vincent and the Grenadines',
                    'SM' => 'San Marino',
                    'ST' => 'Sao Tome and Principe',
                    'SA' => 'Saudi Arabia',
                    'SN' => 'Senegal',
                    'YU' => 'Serbia-Montenegro',
                    'SC' => 'Seychelles',
                    'SL' => 'Sierra Leone',
                    'SG' => 'Singapore',
                    'SK' => 'Slovak Republic',
                    'SI' => 'Slovenia',
                    'SB' => 'Solomon Islands',
                    'SO' => 'Somalia',
                    'ZA' => 'South Africa',
                    'GS' => 'South Georgia (Falkland Islands)',
                    'KR' => 'South Korea (Korea, Republic of)',
                    'ES' => 'Spain',
                    'LK' => 'Sri Lanka',
                    'SD' => 'Sudan',
                    'SR' => 'Suriname',
                    'SZ' => 'Swaziland',
                    'SE' => 'Sweden',
                    'CH' => 'Switzerland',
                    'SY' => 'Syrian Arab Republic',
                    'TW' => 'Taiwan',
                    'TJ' => 'Tajikistan',
                    'TZ' => 'Tanzania',
                    'TH' => 'Thailand',
                    'TG' => 'Togo',
                    'TK' => 'Tokelau (Union) Group (Western Samoa)',
                    'TO' => 'Tonga',
                    'TT' => 'Trinidad and Tobago',
                    'TN' => 'Tunisia',
                    'TR' => 'Turkey',
                    'TM' => 'Turkmenistan',
                    'TC' => 'Turks and Caicos Islands',
                    'TV' => 'Tuvalu',
                    'UG' => 'Uganda',
                    'UA' => 'Ukraine',
                    'AE' => 'United Arab Emirates',
                    'UY' => 'Uruguay',
                    'UZ' => 'Uzbekistan',
                    'VU' => 'Vanuatu',
                    'VA' => 'Vatican City',
                    'VE' => 'Venezuela',
                    'VN' => 'Vietnam',
                    'WF' => 'Wallis and Futuna Islands',
                    'WS' => 'Western Samoa',
                    'YE' => 'Yemen',
                    'ZM' => 'Zambia',
                    'ZW' => 'Zimbabwe');
        return (isset($cc[$code]))? $cc[$code] : null;
    }


}
?>
