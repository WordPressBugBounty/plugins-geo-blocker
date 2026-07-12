<?php
/**
 * Admin view for the "Blocking Rules" tab
 *
 * @since 1.0.0
 * @package Medshi\GeoBlocker
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="medshi-geo-block-rules-content-wrapper">
    <div class="medshi-geo-block-master-toggle-container">
        <button type="button" id="medshi-geo-block-master-toggle" class="medshi-geo-block-master-toggle <?php echo \get_option('medshi_geo_block_enabled', '0') === '1' ? 'is-enabled' : ''; ?>" aria-pressed="<?php echo \get_option('medshi_geo_block_enabled', '0') === '1' ? 'true' : 'false'; ?>">
            <span class="dashicons dashicons-admin-generic medshi-geo-block-toggle-icon"></span>
            <span class="medshi-geo-block-toggle-text">
                <?php echo \get_option('medshi_geo_block_enabled', '0') === '1' ? \esc_html__('Geo-Blocking: Enabled', 'geo-blocker') : \esc_html__('Geo-Blocking: Disabled', 'geo-blocker'); ?>
            </span>
        </button>
        <span class="spinner medshi-geo-block-spinner"></span>
    </div>
    
    <form method="post" action="options.php">
        <?php \settings_fields('medshi_geo_block_settings_group'); ?>
        
        <input type="hidden" id="medshi_geo_block_enabled_hidden" name="medshi_geo_block_enabled" value="<?php echo esc_attr(\get_option('medshi_geo_block_enabled', '0')); ?>">
        
        <div id="medshi-geo-block-mode-tabs-container" class="medshi-geo-block-mode-tabs-container">
            <?php $current_mode = \get_option('medshi_geo_block_mode', 'block_selected'); ?>
            <div class="medshi-geo-block-mode-tab <?php echo $current_mode === 'block_selected' ? 'medshi-geo-block-mode-tab-active' : ''; ?>" 
                data-mode="block_selected" 
                title="<?php \esc_attr_e('Block only the countries selected in the list below. All other countries will be allowed access.', 'geo-blocker'); ?>"
                tabindex="0" role="tab" aria-selected="<?php echo $current_mode === 'block_selected' ? 'true' : 'false'; ?>">
                <?php \esc_html_e('Block Selected Countries', 'geo-blocker'); ?>
            </div>
            <div class="medshi-geo-block-mode-tab <?php echo $current_mode === 'allow_selected' ? 'medshi-geo-block-mode-tab-active' : ''; ?>" 
                data-mode="allow_selected" 
                title="<?php \esc_attr_e('Allow only the countries selected in the list below. All other countries will be blocked from accessing the site.', 'geo-blocker'); ?>"
                tabindex="0" role="tab" aria-selected="<?php echo $current_mode === 'allow_selected' ? 'true' : 'false'; ?>">
                <?php \esc_html_e('Allow Selected Countries', 'geo-blocker'); ?>
            </div>
            <input type="hidden" id="medshi_geo_block_mode_hidden" name="medshi_geo_block_mode" value="<?php echo esc_attr(\get_option('medshi_geo_block_mode', 'block_selected')); ?>" />
        </div>
        
        <div id="medshi-geo-block-country-selector-container" class="medshi-geo-block-country-selector-container">
            <div class="medshi-geo-block-country-selector-header">
                <h4><?php \esc_html_e('Select Countries', 'geo-blocker'); ?></h4>
            </div>
            
            <div class="medshi-geo-block-region-filters" id="medshi-geo-block-region-filters-container">
                <!-- Region filter buttons will be populated by JavaScript -->
                <span class="medshi-geo-block-region-filters-loading"><?php \esc_html_e('Loading regions...', 'geo-blocker'); ?></span>
            </div>
            
            <div class="medshi-geo-block-country-tools">
                <div class="medshi-geo-block-country-search-container">
                    <input type="search" id="medshi-geo-block-country-search" class="medshi-geo-block-country-search" placeholder="<?php \esc_attr_e('Search countries...', 'geo-blocker'); ?>" />
                    <span class="dashicons dashicons-search"></span>
                </div>
                
                <div class="medshi-geo-block-select-all-controls" id="medshi-geo-block-select-all-container">
                    <!-- Select/Deselect All controls will be populated by JavaScript -->
                </div>
            </div>
            
            <div id="medshi-geo-block-country-list" class="medshi-geo-block-country-list">
                <!-- Country list will be populated by JavaScript -->
                <div class="medshi-geo-block-countries-loading">
                    <span class="spinner is-active"></span>
                    <p><?php \esc_html_e('Loading countries...', 'geo-blocker'); ?></p>
                </div>
            </div>
            
            <div id="medshi-geo-block-selected-countries-hidden-inputs">
                <!-- Hidden inputs for selected countries will be populated by JavaScript -->
            </div>
        </div>
        
        <div class="medshi-geo-block-action-settings-section">
            <h4><?php \esc_html_e('When a Visitor is Blocked:', 'geo-blocker'); ?></h4>
            <table class="form-table medshi-geo-block-action-settings-table">
                <tr valign="top">
                    <th scope="row">
                        <label for="medshi_geo_block_blocked_action_select_ui"><?php \esc_html_e('Action to Take', 'geo-blocker'); ?></label>
                    </th>
                    <td>
                        <select id="medshi_geo_block_blocked_action_select_ui" name="medshi_geo_block_blocked_action">
                            <?php $current_blocked_action = \get_option('medshi_geo_block_blocked_action', 'show_message'); ?>
                            <option value="show_message" <?php \selected('show_message', $current_blocked_action); ?>><?php \esc_html_e('Show Custom Message', 'geo-blocker'); ?></option>
                            <option value="redirect" <?php \selected('redirect', $current_blocked_action); ?>><?php \esc_html_e('Redirect to URL', 'geo-blocker'); ?></option>
                            <option value="send_403" <?php \selected('send_403', $current_blocked_action); ?>><?php \esc_html_e('Send 403 Forbidden', 'geo-blocker'); ?></option>
                        </select>
                        <p class="description"><?php \esc_html_e('Choose the action to take when a visitor is blocked.', 'geo-blocker'); ?></p>
                    </td>
                </tr>

                <tr valign="top" class="medshi-geo-block-custom-message-row" style="display:none;"> <!-- Initially hidden by JS -->
                    <th scope="row">
                        <label for="medshi_geo_block_custom_message_ui"><?php \esc_html_e('Custom Block Message', 'geo-blocker'); ?></label>
                    </th>
                    <td>
                        <textarea id="medshi_geo_block_custom_message_ui" name="medshi_geo_block_custom_message" rows="4" class="large-text"><?php echo \esc_textarea(\get_option('medshi_geo_block_custom_message', \esc_html__('Access from your location is currently restricted.', 'geo-blocker'))); ?></textarea>
                        <p class="description"><?php \esc_html_e('This message will be shown if "Show Custom Message" is selected. Basic HTML is allowed.', 'geo-blocker'); ?></p>
                    </td>
                </tr>

                <tr valign="top" class="medshi-geo-block-redirect-url-row" style="display:none;"> <!-- Initially hidden by JS -->
                    <th scope="row">
                        <label for="medshi_geo_block_redirect_url_ui"><?php \esc_html_e('Redirect URL', 'geo-blocker'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="medshi_geo_block_redirect_url_ui" name="medshi_geo_block_redirect_url" value="<?php echo \esc_url(\get_option('medshi_geo_block_redirect_url', '')); ?>" class="regular-text code"> <!-- Added 'code' class for monospace if desired -->
                        <p class="description"><?php \esc_html_e('Enter a full URL (e.g., https://example.com/blocked). Used if "Redirect to URL" is selected.', 'geo-blocker'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="medshi-geo-block-save-actions">
            <?php \submit_button(\esc_html__('Save All Settings', 'geo-blocker')); ?>
        </div>
    </form>
</div> 