<?php
/**
 * Dashboard tab view for Geo Blocker plugin
 *
 * @package Medshi\GeoBlocker
 */

// If accessed directly, exit
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="medshi-geo-block-dashboard-content">
    
    <!-- Top Tier: Login Bypass URL Card (Full Width) -->
    <div class="medshi-geo-block-section medshi-geo-block-full-width-card" role="region" aria-label="<?php echo \esc_attr__('Login Bypass Access', 'geo-blocker'); ?>">
        <div class="medshi-geo-block-section-header">
            <span class="dashicons dashicons-admin-network"></span>
            <h3><?php echo \esc_html__('Login Bypass URL', 'geo-blocker'); ?></h3>
            <span class="dashicons dashicons-editor-help medshi-geo-block-tooltip" title="<?php echo \esc_attr__('Use this URL to log in if your location is blocked.', 'geo-blocker'); ?>"></span>
        </div>
        <div class="medshi-geo-block-section-content">
            <code class="medshi-geo-block-bypass-url-display" aria-label="<?php echo \esc_attr__('Login bypass URL', 'geo-blocker'); ?>"><?php echo \esc_url($bypass_url); ?></code>
            <button type="button" class="button button-small medshi-geo-block-copy-button" data-copytarget=".medshi-geo-block-bypass-url-display" aria-label="<?php echo \esc_attr__('Copy login bypass URL to clipboard', 'geo-blocker'); ?>"><?php echo \esc_html__('Copy URL', 'geo-blocker'); ?></button>
            <span class="medshi-geo-block-copy-feedback" style="display:none; margin-left:5px; color:green;" aria-live="polite"></span>
        </div>
    </div>

    <!-- Middle Tier: Summary Cards -->
    <div class="medshi-geo-block-summary-cards-container" role="region" aria-label="<?php echo \esc_attr__('Dashboard Summary Cards', 'geo-blocker'); ?>">
        <!-- Card: Plugin Status -->
        <div class="medshi-geo-block-summary-card medshi-geo-block-card-<?php echo \esc_attr($status_class); ?>">
            <div class="medshi-geo-block-card-header">
                <span class="dashicons <?php echo $is_plugin_enabled === '1' ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                <span class="medshi-geo-block-card-title"><?php echo \esc_html__('Plugin Status', 'geo-blocker'); ?></span>
                <span class="dashicons dashicons-editor-help medshi-geo-block-tooltip" title="<?php echo \esc_attr__('Current operational status of the Geo Blocker plugin.', 'geo-blocker'); ?>"></span>
            </div>
            <div class="medshi-geo-block-card-body">
                <div class="medshi-geo-block-card-value"><?php echo \esc_html($status_text); ?></div>
                <?php if ($is_plugin_enabled !== '1'): ?>
                <div class="medshi-geo-block-card-desc">
                    <a href="<?php echo \esc_url(\admin_url('options-general.php?page=medshi-geo-blocker-settings&tab=blocking_rules')); ?>"><?php echo \esc_html__('Enable in Blocking Rules', 'geo-blocker'); ?></a>
                </div>
                <?php else: ?>
                <div class="medshi-geo-block-card-desc"><?php echo \esc_html__('fully operational', 'geo-blocker'); ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Card 1: Countries Blocked/Allowed -->
        <div class="medshi-geo-block-summary-card">
            <div class="medshi-geo-block-card-header">
                <span class="dashicons dashicons-location-alt"></span>
                <span class="medshi-geo-block-card-title">
                    <?php echo $blocking_mode === 'block_selected' ? \esc_html__('Countries Blocked', 'geo-blocker') : \esc_html__('Countries Allowed', 'geo-blocker'); ?>
                </span>
                <span class="dashicons dashicons-editor-help medshi-geo-block-tooltip" title="<?php echo \esc_attr__('Number of countries targeted by current blocking rules.', 'geo-blocker'); ?>"></span>
            </div>
            <div class="medshi-geo-block-card-body">
                <div class="medshi-geo-block-card-value"><?php echo \esc_html(\number_format_i18n($count_target_countries)); ?></div>
                <div class="medshi-geo-block-card-desc"><?php echo \esc_html__('currently targeted', 'geo-blocker'); ?></div>
            </div>
        </div>

        <!-- Card 2: Allowed Visits (Today) -->
        <div class="medshi-geo-block-summary-card medshi-geo-block-card-allowed">
            <div class="medshi-geo-block-card-header">
                <span class="dashicons dashicons-yes-alt"></span>
                <span class="medshi-geo-block-card-title"><?php echo \esc_html__('Allowed Visits', 'geo-blocker'); ?></span>
                <span class="dashicons dashicons-editor-help medshi-geo-block-tooltip" title="<?php echo \esc_attr__('Number of visitors allowed through today.', 'geo-blocker'); ?>"></span>
            </div>
            <div class="medshi-geo-block-card-body">
                <div class="medshi-geo-block-card-value"><?php echo \esc_html(\number_format_i18n($allowed_today)); ?></div>
                <div class="medshi-geo-block-card-desc"><?php echo \esc_html__('today', 'geo-blocker'); ?></div>
            </div>
        </div>

        <!-- Card 3: Blocked Visits (Today) -->
        <div class="medshi-geo-block-summary-card medshi-geo-block-card-blocked">
            <div class="medshi-geo-block-card-header">
                <span class="dashicons dashicons-shield-alt"></span>
                <span class="medshi-geo-block-card-title"><?php echo \esc_html__('Blocked Visits', 'geo-blocker'); ?></span>
                <span class="dashicons dashicons-editor-help medshi-geo-block-tooltip" title="<?php echo \esc_attr__('Number of visitors blocked today.', 'geo-blocker'); ?>"></span>
            </div>
            <div class="medshi-geo-block-card-body">
                <div class="medshi-geo-block-card-value"><?php echo \esc_html(\number_format_i18n($blocked_today)); ?></div>
                <div class="medshi-geo-block-card-desc"><?php echo \esc_html__('today', 'geo-blocker'); ?></div>
            </div>
        </div>
    </div>

    <!-- Bottom Tier: Recent Activity Section (Full Width) -->
    <div class="medshi-geo-block-section medshi-geo-block-full-width-section" role="region" aria-labelledby="recent-activity-title">
        <div class="medshi-geo-block-section-header">
            <span class="dashicons dashicons-visibility"></span>
            <h3 id="recent-activity-title"><?php echo \esc_html__('Recent Activity', 'geo-blocker'); ?></h3>
        </div>
        <div class="medshi-geo-block-section-content">
            <?php if (!empty($recent_logs)): ?>
            <div class="medshi-geo-block-table-wrapper">
                <table class="widefat striped" role="table" aria-label="<?php echo \esc_attr__('Recent activity logs', 'geo-blocker'); ?>">
                    <thead>
                        <tr>
                            <th scope="col"><?php echo \esc_html__('Time', 'geo-blocker'); ?></th>
                            <th scope="col"><?php echo \esc_html__('IP Address', 'geo-blocker'); ?></th>
                            <th scope="col"><?php echo \esc_html__('Country', 'geo-blocker'); ?></th>
                            <th scope="col"><?php echo \esc_html__('Status', 'geo-blocker'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_logs as $log): ?>
                        <tr>
                            <td data-label="<?php echo \esc_attr__('Time', 'geo-blocker'); ?>"><?php echo \esc_html(\date_i18n(\get_option('date_format') . ' ' . \get_option('time_format'), strtotime($log->timestamp))); ?></td>
                            <td data-label="<?php echo \esc_attr__('IP Address', 'geo-blocker'); ?>"><?php echo \esc_html($log->ip_address); ?></td>
                            <td data-label="<?php echo \esc_attr__('Country', 'geo-blocker'); ?>"><?php echo \esc_html($log->country_code); ?></td>
                            <td data-label="<?php echo \esc_attr__('Status', 'geo-blocker'); ?>">
                                <span class="log-status <?php echo strtolower($log->status) === 'blocked' ? 'error' : 'success'; ?>" aria-label="<?php echo \esc_attr(ucfirst($log->status)); ?> status">
                                    <?php echo \esc_html(ucfirst($log->status)); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="medshi-geo-block-actions-container">
                <a href="<?php echo \esc_url(\admin_url('options-general.php?page=medshi-geo-blocker-settings&tab=logs_display')); ?>" class="button">
                    <?php echo \esc_html__('View All Logs', 'geo-blocker'); ?>
                </a>
            </p>
            <?php else: ?>
            <p><?php echo \esc_html__('No log entries found.', 'geo-blocker'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div> 