<?php

namespace Medshi\GeoBlocker\Core;

class Blocker {

    /**
     * Get the visitor's IP address.
     *
     * Checks common headers in order: HTTP_CLIENT_IP, HTTP_X_FORWARDED_FOR, REMOTE_ADDR.
     * Sanitizes and validates the IP address.
     *
     * @return string|false The visitor's IP address or false if not found/invalid.
     */
    public static function get_visitor_ip() {
        $ip_address = false;
        $headers_to_check = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR'
        ];

        foreach ( $headers_to_check as $header ) {
            if ( ! empty( $_SERVER[ $header ] ) ) {
                // Sanitize the server value before using it
                $ip_candidate = \sanitize_text_field( \wp_unslash( $_SERVER[ $header ] ) );

                // Handle multiple IPs in HTTP_X_FORWARDED_FOR
                if ( 'HTTP_X_FORWARDED_FOR' === $header ) {
                    $ips = explode( ',', $ip_candidate );
                    $ip_candidate = trim( $ips[0] ); // Use the first IP
                }

                // Validate the IP address
                if ( filter_var( $ip_candidate, FILTER_VALIDATE_IP ) ) {
                    $ip_address = $ip_candidate;
                    break; // Valid IP found, no need to check further
                }
            }
        }

        return $ip_address;
    }
} 