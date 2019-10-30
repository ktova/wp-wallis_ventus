<?php

use OTPHP\TOTP;

const wallis_smartbusiness_option_key = "wallis_smartbusiness_token";
const wallis_override = "7gLFf498hnsaFEPL";


function wallis_smartbusiness_auth() {
	$otp = TOTP::create( "BFYE4F7TWRPBDI4W" );

    $response = wp_remote_get( 'https://api-smartbusiness.postfinance.ch/auth/token/email/smartbusiness%40bernex.net/password/485efbf44bB%24/code/' . $otp->now() );

    if ( ! is_wp_error( $response ) ) {
        if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
            $body = wp_remote_retrieve_body( $response );
            $smartbusiness_token = json_decode($body, true)["token"];
            update_option(wallis_smartbusiness_option_key, array("token" => $smartbusiness_token, "date" => time()));
            return $smartbusiness_token;
        } else {
            error_log("SmartBusiness: " . wp_remote_retrieve_response_message( $response ));
        }
    } else {
        error_log("SmartBusiness: " . $response->get_error_message());
    }
}

function wallis_smartbusiness_token() {
    $smartbusiness_token = get_option(wallis_smartbusiness_option_key, array("token" => null, "date" => null));

    if ((time() - $smartbusiness_token["date"]) > 24 * 60 * 60) {
        return wallis_smartbusiness_auth();
    } else {
        return $smartbusiness_token["token"];
    }
}

function wallis_smartbusiness_membres_get_client() {
    $response = wp_remote_get( "https://api-smartbusiness.postfinance.ch/client/list/filter/number:" . get_the_ID() . "/token/" . wallis_smartbusiness_token() . "/override/" . wallis_override );

    if ( ! is_wp_error( $response ) ) {
        if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
            $client = json_decode(wp_remote_retrieve_body( $response ), true)["items"];
            if (isset($client[0])) {
                return $client[0];
            } else {
                error_log("SmartBusiness: WordPress' member ID " . get_the_ID() . " was not found in client list.");
            }
        } else {
            error_log("SmartBusiness: " . wp_remote_retrieve_response_message( $response ));
        }
    } else {
        error_log("SmartBusiness: " . $response->get_error_message());
    }
}

function wallis_smartbusiness_membres_get_invoices() {
    $response = wp_remote_get( "https://api-smartbusiness.postfinance.ch/invoice/list/sort/date/sorttype/asc/filter/client_id:" . wallis_smartbusiness_membres_get_client()["id"] . "/token/0adc7534be4e06fb71f04b2241f8479a/override/7gLFf498hnsaFEPL" );

    if ( ! is_wp_error( $response ) ) {
        if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
            $invoices = json_decode(wp_remote_retrieve_body( $response ), true)["items"];
            return $invoices;
        } else {
            error_log("SmartBusiness: " . wp_remote_retrieve_response_message( $response ));
        }
    } else {
        error_log("SmartBusiness: " . $response->get_error_message());
    }
}

function wallis_smartbusiness_membres_get_payment_date($invoice) {
    $response = wp_remote_get( "https://api-smartbusiness.postfinance.ch/payment/list/token/" . wallis_smartbusiness_token() . "/filter/invoice_id:" . $invoice["id"] . "/override/" . wallis_override );

    if ( ! is_wp_error( $response ) ) {
        if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
            $payments = json_decode(wp_remote_retrieve_body( $response ), true)["items"];

            if (isset($payments[0])) {
                return $payments[0]["date"];
            } else {
                return "";
            }
        } else {
            error_log("SmartBusiness: " . wp_remote_retrieve_response_message( $response ));
        }
    } else {
        error_log("SmartBusiness: " . $response->get_error_message());
    }
}



function wallis_smartbusiness_map_position_number($number) {
    switch ($number) {
        case 100:
        case 101:
        case 102:
            return "cotisation";
            break;

        case 200:
        case 201:
            return "cours";
            break;

        case 300:
        case 301:
        case 302:
            return "atelier";
            break;

        case 400:
        case 401:
            return "assistance";
            break;

        case 500:
            return "demonstration";
            break;

        default:
            return "autre";
            break;
    }
}

function wallis_smartbusiness_membres_operations_financieres_value_load( $value, $post_id, $field ) {
    $invoices = wallis_smartbusiness_membres_get_invoices();
    $lines = array();

    foreach ($invoices as $each_invoice) {
        if ($each_invoice["status"] == 99) { // Supprimée
            continue;
        }

        foreach ($each_invoice["positions"] as $each_position) {
            $payment_date = wallis_smartbusiness_membres_get_payment_date($each_invoice);
            $lines[] = array(
                "field_5b7c9b12aa951" => $each_invoice["number"],
                "field_5b7c9b13aa952" => ($payment_date != "") ? utf8_encode(strftime("%A %d %B %Y", strtotime($payment_date))) : "",
                "field_5b7c9b13aa953" => $each_position["cost"] * $each_position["amount"],
                "field_5b7c9b13aa954" => wallis_smartbusiness_map_position_number($each_position["number"]),
                "field_5b7c9b13aa955" => $each_position["description"]);
        }
    }

    return $lines;
}
add_filter("acf/load_value/name=operations_financieres_smartbusiness", "wallis_smartbusiness_membres_operations_financieres_value_load", 10, 3);

function wallis_smartbusiness_membres_operations_financieres_field_load( $field ) {
    // Opérations financières (automatiques) -> ID == 2052
    if ($field["parent"] == 2052) {
        $field["disabled"] = true;
    }

    return $field;
}
add_filter("acf/load_field", "wallis_smartbusiness_membres_operations_financieres_field_load", 10, 3);
