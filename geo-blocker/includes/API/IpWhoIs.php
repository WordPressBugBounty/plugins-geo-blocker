<?php

namespace Medshi\GeoBlocker\API;

/**
 * Wrapper for the ipwho.is API.
 *
 * @since 1.0.0
 */
class IpWhoIs {

    /**
     * Get geolocation data for a given IP address.
     *
     * @param string $ip_address The IP address to geolocate.
     * @return string|false The country code (e.g., 'US', 'GB') on success, or false on failure.
     */
    public static function get_geolocation( $ip_address ) {
        // Validate the IP address format first.
        if ( ! filter_var( $ip_address, FILTER_VALIDATE_IP ) ) {
            return false;
        }

        $api_url = "https://ipwho.is/{$ip_address}";
        $response = \wp_remote_get( $api_url, [ 'timeout' => 5 ] );

        if ( \is_wp_error( $response ) ) {
            return false;
        }

        $body = \wp_remote_retrieve_body( $response );
        $data = json_decode( $body );

        if ( null === $data && json_last_error() !== JSON_ERROR_NONE ) {
            return false;
        }

        // Check for API success flag and country_code
        if ( isset( $data->success ) && $data->success === true && isset( $data->country_code ) ) {
            // Sanitize the country code - should be 2 uppercase letters
            $country_code = \sanitize_text_field( $data->country_code );
            if ( preg_match( '/^[A-Z]{2}$/', $country_code ) ) {
                return $country_code;
            } else {
                return false;
            }
        } elseif ( isset( $data->success ) && $data->success === false && isset( $data->message ) ) {
            return false;
        } else {
            // Catch-all for unexpected successful response structure or other errors
            return false;
        }
    }
} 