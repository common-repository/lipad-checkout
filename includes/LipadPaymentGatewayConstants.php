<?php
if (!defined('ABSPATH')) {
    exit;
}

class LipadWordPressConstants
{
    const BRAND_NAME = 'lipad';
       const LIPAD_ICON = WP_PLUGIN_URL . '/lipad-checkout/assets/lipad-checkout-logo.svg';

    const PAYMENT_GATEWAY = self::BRAND_NAME . '_checkout';
    const PAYMENT_GATEWAY_DESCRIPTION = (self::BRAND_NAME) . ' Allows you to collect payments on your website, mobile app or get paid instantly using our payment request links.';

    const CHECKOUT_REDIRECT_LINKS = [
        "live" => "https://checkout.lipad.io/",
        "uat" => "https://checkout.uat.lipad.io/"
    ];

    //supported countries
    const COUNTRIES = [
        "kenya" => [
            "currency_code" => "KES",
            "country_code" => "KE",
            "iso3_country_code" => 'KEN'
        ],
        "tanzania" => [
            "currency_code" => "TZS",
            "country_code" => "TZ",
            "iso3_country_code" => 'TZA'
        ],
        "uganda" => [
            "currency_code" => "UGX",
            "country_code" => "UG",
            "iso3_country_code" => 'TZA'
        ],
        "ghana" => [
            "currency_code" => "GHS",
            "country_code" => "GH",
            "iso3_country_code" => 'GHA'
        ],
        "zambia" => [
            "currency_code" => "ZMW",
            "country_code" => "ZM",
            "iso3_country_code" => 'ZMB'
        ],
        "zimbabwe" => [
            "currency_code" => "USD",
            "country_code" => "ZW",
            "iso3_country_code" => 'ZWE'
        ],
        "mozambique" => [
            "currency_code" => "MZN",
            "country_code" => "MZ",
            "iso3_country_code" => 'MOZ'
        ],
        "nigeria" => [
            "currency_code" => "NGN",
            "country_code" => "NG",
            "iso3_country_code" => 'NGA'
        ],
        "south-africa" => [
            "currency_code" => "ZAR",
            "country_code" => "ZA",
            "iso3_country_code" => 'ZAF'
        ],
        "senegal" => [
            "currency_code" => "XOF",
            "country_code" => "SN",
            "iso3_country_code" => 'SEN'
        ],
        "egypt" => [
            "currency_code" => "EGP",
            "country_code" => "EG",
            "iso3_country_code" => 'EGY'
        ],
        "botswana" => [
            "currency_code" => "BWP",
            "country_code" => "BW",
            "iso3_country_code" => 'BWA'
        ],
        "ivory coast" => [
            "currency_code" => "XOF",
            "country_code" => "CI",
            "iso3_country_code" => 'CIV'
        ],
        "rwanda" => [
            "currency_code" => "RWF",
            "country_code" => "RW",
            "iso3_country_code" => 'RWA'
        ],
        "malawi" => [
            "currency_code" => "MWK",
            "country_code" => "MW",
            "iso3_country_code" => 'MWI'
        ],
    ];
    const AIRTEL_REGEX = '/^(254)(([7]([38]{1}([0-9]{7})|(([5][0]|[5][1]|[5][2]|[5][3]|[5][4]|[5][5]|[5][6]|[6][0]|[6][2]){1})([0-9]{6}))|((100)|(101)|(102)|(103)|(104)|(105)|(106))([0-9]{6})))$/';
    const MPESA_REGEX = '/^(254)((([7])([0|1|2|9])([0-9]{7}))|(74)([0123456]{1})([0-9]{6})|(75)([789]{1})([0-9]{6})|(76)([89]{1})([0-9]{6})|((110)|(111)|(112)|(113)|(114)|(115))([0-9]{6}))$/';
}
// Regular expressions

