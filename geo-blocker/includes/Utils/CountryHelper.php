<?php

namespace Medshi\GeoBlocker\Utils;

/**
 * Helper class for country-related operations.
 *
 * @since 1.0.0
 */
class CountryHelper {
    /**
     * Get a list of all countries with their codes, names, flags, and regions from a JSON file.
     *
     * @since 1.0.0
     * @return array Array of country objects. Returns empty array on failure.
     */
    public static function get_all_countries() {
        $json_file_path = MEDSHI_GEO_BLOCK_PLUGIN_DIR . 'admin/data/countries.json';

        if ( ! file_exists( $json_file_path ) ) {
            // Optionally, log an error here if a logging mechanism is available.
            // error_log( 'Geo Blocker: countries.json file not found at ' . $json_file_path );
            return [];
        }

        $json_data = @file_get_contents( $json_file_path ); // Suppress warning if file not readable
        if ( false === $json_data ) {
            // Optionally, log an error.
            // error_log( 'Geo Blocker: Could not read countries.json file.' );
            return [];
        }

        $countries = json_decode( $json_data );

        // Check for JSON decoding errors
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            // Optionally, log an error.
            // error_log( 'Geo Blocker: Error decoding countries.json: ' . json_last_error_msg() );
            return [];
        }

        return is_array( $countries ) ? $countries : [];
    }
} 