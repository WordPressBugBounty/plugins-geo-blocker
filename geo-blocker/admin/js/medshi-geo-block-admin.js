jQuery(document).ready(function($) {
    console.log('Admin JS loaded. Geo Blocker Admin Object:', medshiGeoBlockerAdmin);
    
    // Master Toggle Button Logic
    const $masterToggleBtn = $('#medshi-geo-block-master-toggle');
    const $masterToggleHiddenInput = $('#medshi_geo_block_enabled_hidden');
    const $masterToggleSpinner = $('.medshi-geo-block-master-toggle-container .spinner');
    const $masterToggleText = $masterToggleBtn.find('.medshi-geo-block-toggle-text');

    if ($masterToggleBtn.length) {
        $masterToggleBtn.on('click', function() {
            const GgIsEnabled = $(this).hasClass('is-enabled');
            const newIsEnabled = !GgIsEnabled;

            $masterToggleSpinner.addClass('is-active');
            $masterToggleBtn.prop('disabled', true);

            $.ajax({
                url: medshiGeoBlockerAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: medshiGeoBlockerAdmin.toggle_status_action,
                    nonce: medshiGeoBlockerAdmin.toggle_status_nonce,
                    is_enabled: newIsEnabled // Send boolean true/false
                },
                success: function(response) {
                    if (response.success) {
                        $masterToggleBtn.toggleClass('is-enabled', newIsEnabled);
                        $masterToggleBtn.attr('aria-pressed', newIsEnabled.toString());
                        $masterToggleText.text(newIsEnabled ? medshiGeoBlockerAdmin.status_enabled_text : medshiGeoBlockerAdmin.status_disabled_text);
                        $masterToggleHiddenInput.val(newIsEnabled ? '1' : '0');
                        
                        // TODO: Implement toast notifications in the future
                        // Example implementation:
                        // showToastNotification({
                        //     message: newIsEnabled ? 'Geo-Blocking has been enabled!' : 'Geo-Blocking has been disabled.',
                        //     type: 'success',
                        //     duration: 3000
                        // });
                        
                        console.log(response.data.message); // Placeholder
                    } else {
                        // TODO: Implement toast notifications for errors
                        // showToastNotification({
                        //     message: response.data.message || medshiGeoBlockerAdmin.status_toggle_error,
                        //     type: 'error',
                        //     duration: 5000
                        // });
                        console.error(response.data.message || medshiGeoBlockerAdmin.status_toggle_error);
                    }
                },
                error: function() {
                    // TODO: Implement toast notifications for network errors
                    // showToastNotification({
                    //     message: medshiGeoBlockerAdmin.status_toggle_error,
                    //     type: 'error',
                    //     duration: 5000
                    // });
                    console.error(medshiGeoBlockerAdmin.status_toggle_error);
                },
                complete: function() {
                    $masterToggleSpinner.removeClass('is-active');
                    $masterToggleBtn.prop('disabled', false);
                }
            });
        });
    }
    
    // Blocking Mode Tabs Logic
    const $modeTabs = $('.medshi-geo-block-mode-tab');
    const $modeHiddenInput = $('#medshi_geo_block_mode_hidden');
    const $countrySelectorContainer = $('#medshi-geo-block-country-selector-container');
    const activeModeClass = 'medshi-geo-block-mode-tab-active';
    const borderClassBlock = 'medshi-geo-block-border-block'; // For red border
    const borderClassAllow = 'medshi-geo-block-border-allow'; // For green border

    function updateModeUI(selectedMode) {
        $modeTabs.removeClass(activeModeClass).attr('aria-selected', 'false');
        $modeTabs.filter('[data-mode="' + selectedMode + '"]').addClass(activeModeClass).attr('aria-selected', 'true');
        $modeHiddenInput.val(selectedMode);

        $countrySelectorContainer.removeClass(borderClassBlock + ' ' + borderClassAllow);
        if (selectedMode === 'block_selected') {
            $countrySelectorContainer.addClass(borderClassBlock);
        } else if (selectedMode === 'allow_selected') {
            $countrySelectorContainer.addClass(borderClassAllow);
        }
    }

    if ($modeTabs.length) {
        // Initial state
        const initialMode = $modeHiddenInput.val();
        updateModeUI(initialMode);

        $modeTabs.on('click keydown', function(e) {
            if (e.type === 'click' || (e.type === 'keydown' && (e.key === 'Enter' || e.key === ' '))) {
                e.preventDefault();
                const selectedMode = $(this).data('mode');
                updateModeUI(selectedMode);
            }
        });
    }
    
    // Blocked Action Fields Toggle Logic
    function toggleBlockedActionFields() {
        var action = $('#medshi_geo_block_blocked_action_select_ui').val();
        var $messageRow = $('.medshi-geo-block-custom-message-row');
        var $redirectRow = $('.medshi-geo-block-redirect-url-row');
        
        // Hide both rows initially
        $messageRow.hide();
        $redirectRow.hide();
        
        // Show the appropriate row based on selected action
        if (action === 'show_message') {
            $messageRow.show();
        } else if (action === 'redirect') {
            $redirectRow.show();
        }
    }

    // Initialize blocked action fields visibility
    if ($('#medshi_geo_block_blocked_action_select_ui').length) {
        toggleBlockedActionFields(); // Call on page load
        $('#medshi_geo_block_blocked_action_select_ui').on('change', toggleBlockedActionFields); // Call on change
    }
    
    // Ensure the element exists before trying to initialize Select2
    if ($('#medshi_geo_block_countries_select').length) {
        $('#medshi_geo_block_countries_select').select2({
            placeholder: "Select countries",
            width: 'style',
            allowClear: true
        });
    }
    
    // Handle copy button click for login bypass URL
    $('body').on('click', '.medshi-geo-block-copy-button', function() {
        var $this = $(this);
        var targetSelector = $this.data('copytarget');
        var $targetElement = $(targetSelector);
        var feedbackElement = $this.siblings('.medshi-geo-block-copy-feedback');

        if ($targetElement.length) {
            var textToCopy = $targetElement.text().trim();
            
            // Try to use the newer clipboard API first
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(textToCopy)
                    .then(function() {
                        // Show and animate feedback
                        feedbackElement.text('Copied!')
                            .addClass('visible')
                            .attr('aria-live', 'polite');
                            
                        // Remove visible class after delay
                        setTimeout(function() {
                            feedbackElement.removeClass('visible');
                        }, 2000);
                        
                        // Change button text temporarily
                        var originalText = $this.text();
                        $this.text('✓ Copied!')
                             .attr('aria-label', 'URL copied to clipboard');
                             
                        setTimeout(function() {
                            $this.text(originalText)
                                 .attr('aria-label', 'Copy login bypass URL to clipboard');
                        }, 2000);
                    })
                    .catch(function(err) {
                        feedbackElement.text('Failed to copy.').fadeIn().delay(2000).fadeOut();
                        console.error('Could not copy text: ', err);
                    });
            } else {
                // Fallback for older browsers
                try {
                    // Create temporary element
                    var tempElement = document.createElement('textarea');
                    tempElement.value = textToCopy;
                    document.body.appendChild(tempElement);
                    
                    // Select the text and copy
                    tempElement.select();
                    document.execCommand('copy');
                    document.body.removeChild(tempElement);
                    
                    // Show success feedback
                    feedbackElement.text('Copied!')
                        .addClass('visible')
                        .attr('aria-live', 'polite');
                        
                    // Remove visible class after delay
                    setTimeout(function() {
                        feedbackElement.removeClass('visible');
                    }, 2000);
                    
                    // Change button text temporarily
                    var originalText = $this.text();
                    $this.text('✓ Copied!')
                         .attr('aria-label', 'URL copied to clipboard');
                         
                    setTimeout(function() {
                        $this.text(originalText)
                             .attr('aria-label', 'Copy login bypass URL to clipboard');
                    }, 2000);
                } catch (err) {
                    feedbackElement.text('Failed to copy.').fadeIn().delay(2000).fadeOut();
                    console.error('Could not copy text: ', err);
                }
            }
        }
    });
    
    // Handle clear logs button click
    $('#medshi-geo-block-clear-logs-button').on('click', function(e) {
        e.preventDefault();
        
        // Show confirmation prompt
        if (confirm(medshiGeoBlockerAdmin.messages.confirm_clear)) {
            // Send AJAX request to clear logs
            $.ajax({
                url: medshiGeoBlockerAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: medshiGeoBlockerAdmin.clear_logs_action,
                    nonce: medshiGeoBlockerAdmin.clear_logs_nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message || medshiGeoBlockerAdmin.messages.cleared);
                        // After clearing logs, reload the page to refresh everything including the summary cards
                        window.location.reload();
                    } else {
                        alert(response.data.message || medshiGeoBlockerAdmin.messages.error);
                    }
                },
                error: function() {
                    alert(medshiGeoBlockerAdmin.messages.error);
                }
            });
        }
    });
    
    // Store the current chart instance for later destruction/replacement
    let currentChartInstance = null;
    
    // Load chart if the canvas element exists
    if ($('#medshiGeoBlockLogChart').length) {
        console.log('Chart canvas #medshiGeoBlockLogChart found. Attempting to load chart.');
        loadLogChart();
        
        // Add event listeners to all chart filters
        $('#medshi_chart_filter_date_range, #medshi_chart_filter_data_type, #medshi_chart_filter_state, #medshi_chart_filter_chart_type').on('change', function() {
            loadLogChart();
        });
    }
    
    // Add event listeners for table filters
    if ($('#medshi_table_log_date_range').length) {
        $('#medshi_table_log_date_range').on('change', function() {
            refreshLogTable(1); // Reset to page 1 when filter changes
        });
        
        $('#medshi_table_log_search').on('input', function() {
            // Use a debounce approach for the search field
            clearTimeout($(this).data('timeout'));
            $(this).data('timeout', setTimeout(function() {
                refreshLogTable(1); // Reset to page 1 when filter changes
            }, 500)); // Debounce for 500ms
        });
        
        // Also trigger search on Enter key
        $('#medshi_table_log_search').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                refreshLogTable(1);
            }
        });
        
        // Handle pagination via AJAX
        $('body').on('click', '.medshi-geo-block-table-wrapper .tablenav-pages a', function(e) {
            e.preventDefault();
            const urlParams = new URLSearchParams($(this).attr('href').split('?')[1]);
            const paged = urlParams.get('paged') || 1;
            const orderby = urlParams.get('orderby') || 'timestamp';
            const order = urlParams.get('order') || 'DESC';
            refreshLogTable(paged, orderby, order);
        });
        
        // Handle sortable column headers
        $('body').on('click', '.medshi-geo-block-table-wrapper .wp-list-table th.sortable a, .medshi-geo-block-table-wrapper .wp-list-table th.sorted a', function(e) {
            e.preventDefault();
            
            // Extract sorting parameters from URL
            const href = $(this).attr('href');
            const urlParams = new URLSearchParams(href.split('?')[1]);
            
            // Get parameters with defaults
            const paged = urlParams.get('paged') || 1;
            const orderby = urlParams.get('orderby') || 'timestamp';
            const order = urlParams.get('order') || 'DESC';
            
            console.log('Sort clicked. Orderby:', orderby, 'Order:', order);
            
            // Call refresh with sorting parameters
            refreshLogTable(paged, orderby, order);
        });
        
        // Initialize table event handlers on document ready
        $(document).ready(function() {
            attachTableEventHandlers();
        });
    }
    
    /**
     * Refresh the log table via AJAX
     * 
     * @param {number} paged - The page number to load (default: 1)
     * @param {string} orderby - The column to sort by (default: 'timestamp')
     * @param {string} order - The sort order (default: 'DESC')
     */
    function refreshLogTable(paged = 1, orderby = 'timestamp', order = 'DESC') {
        const $tableWrapper = $('.medshi-geo-block-table-wrapper');
        const selectedDateRange = $('#medshi_table_log_date_range').val() || 'all_time';
        const searchTerm = $('#medshi_table_log_search').val() || '';
        
        console.log('Refreshing table with:', {
            paged: paged,
            orderby: orderby,
            order: order,
            date_range: selectedDateRange,
            search_term: searchTerm
        });
        
        // Show loading overlay
        $tableWrapper.append('<div class="medshi-chart-loading-overlay"><span class="spinner is-active"></span></div>');
        
        $.ajax({
            url: medshiGeoBlockerAdmin.ajax_url,
            type: 'POST',
            data: {
                action: medshiGeoBlockerAdmin.refresh_log_table_action,
                nonce: medshiGeoBlockerAdmin.refresh_log_table_nonce,
                date_range: selectedDateRange,
                search_term: searchTerm,
                paged: paged,
                orderby: orderby,
                order: order
            },
            success: function(response) {
                // Remove loading overlay
                $tableWrapper.find('.medshi-chart-loading-overlay').remove();
                
                if (response.success) {
                    // Replace table content
                    $('#medshi-geo-block-logs-table-container').html(response.data.table_html);
                    
                    // Update URL parameters to reflect current state (optional, helps with page reloads)
                    if (window.history && window.history.pushState) {
                        const currentUrl = new URL(window.location.href);
                        currentUrl.searchParams.set('orderby', orderby);
                        currentUrl.searchParams.set('order', order);
                        currentUrl.searchParams.set('paged', paged);
                        currentUrl.searchParams.set('medshi_table_log_date_range', selectedDateRange);
                        currentUrl.searchParams.set('medshi_table_log_search', searchTerm);
                        window.history.pushState({}, '', currentUrl);
                    }

                    // Re-attach event handlers for pagination and sorting
                    attachTableEventHandlers();
                } else {
                    alert(response.data.message || medshiGeoBlockerAdmin.error_refreshing_table);
                }
            },
            error: function() {
                // Remove loading overlay
                $tableWrapper.find('.medshi-chart-loading-overlay').remove();
                
                alert(medshiGeoBlockerAdmin.error_refreshing_table);
            }
        });
    }
    
    /**
     * Attach event handlers to table elements (pagination, sorting)
     */
    function attachTableEventHandlers() {
        // Handle pagination
        $('.medshi-geo-block-table-wrapper .tablenav-pages a').off('click').on('click', function(e) {
            e.preventDefault();
            const href = $(this).attr('href');
            if (!href) return;
            
            const urlParams = new URLSearchParams(href.split('?')[1]);
            const paged = urlParams.get('paged') || 1;
            const orderby = urlParams.get('orderby') || 'timestamp';
            const order = urlParams.get('order') || 'DESC';
            
            refreshLogTable(paged, orderby, order);
        });
        
        // Handle sorting columns
        $('.medshi-geo-block-table-wrapper .wp-list-table th.sortable a, .medshi-geo-block-table-wrapper .wp-list-table th.sorted a').off('click').on('click', function(e) {
            e.preventDefault();
            const href = $(this).attr('href');
            if (!href) return;
            
            const urlParams = new URLSearchParams(href.split('?')[1]);
            const paged = 1; // Reset to page 1 when sorting changes
            const orderby = urlParams.get('orderby') || 'timestamp';
            const order = urlParams.get('order') || 'DESC';
            
            refreshLogTable(paged, orderby, order);
        });
    }
    
    /**
     * Load log chart data and render the chart
     */
    function loadLogChart() {
        console.log('loadLogChart called with filters:', {
            date_range: $('#medshi_chart_filter_date_range').val(),
            data_type: $('#medshi_chart_filter_data_type').val(),
            state: $('#medshi_chart_filter_state').val(),
            chart_type: $('#medshi_chart_filter_chart_type').val()
        });
        
        // Get the chart container for loading indicator
        const $chartContainer = $('.medshi-geo-block-chart-container');
        
        // Show loading indicator
        $chartContainer.append('<div class="medshi-chart-loading-overlay"><span class="spinner is-active"></span></div>');
        
        $.ajax({
            url: medshiGeoBlockerAdmin.ajax_url,
            type: 'POST',
            data: {
                action: medshiGeoBlockerAdmin.get_chart_data_action,
                nonce: medshiGeoBlockerAdmin.get_chart_data_nonce,
                date_range: $('#medshi_chart_filter_date_range').val() || 'today',
                data_type: $('#medshi_chart_filter_data_type').val() || 'access_attempts',
                state: $('#medshi_chart_filter_state').val() || 'all_states',
                chart_type: $('#medshi_chart_filter_chart_type').val() || 'bar'
            },
            success: function(response) {
                console.log('Chart AJAX response received:', response);
                
                // Remove loading indicator
                $chartContainer.find('.medshi-chart-loading-overlay').remove();
                
                if (response.success && response.data.labels && response.data.labels.length > 0) {
                    renderChart(response.data);
                } else {
                    console.log('No chart data available or error:', response);
                    // Destroy current chart if it exists
                    if (currentChartInstance) {
                        currentChartInstance.destroy();
                        currentChartInstance = null;
                    }
                    
                    // Show no data message
                    $chartContainer.append('<div class="medshi-geo-block-no-data">No data available for the selected filters</div>');
                }
            },
            error: function() {
                // Remove loading indicator
                $chartContainer.find('.medshi-chart-loading-overlay').remove();
                
                console.error('Chart AJAX call failed.');
                alert(medshiGeoBlockerAdmin.chart_data_error);
            }
        });
    }
    
    /**
     * Render the chart with the provided data
     * 
     * @param {Object} data The chart data containing labels, counts, colors, and chart type
     */
    function renderChart(data) {
        console.log('renderChart called with data:', data);
        
        const ctx = document.getElementById('medshiGeoBlockLogChart').getContext('2d');
        
        // Decode HTML entities in title if needed
        const decodeHtmlEntities = (str) => {
            if (typeof str !== 'string') return '';
            const tempElement = document.createElement('div');
            tempElement.innerHTML = str;
            return tempElement.textContent || tempElement.innerText || '';
        };
        
        // Clean the title
        const chartTitle = decodeHtmlEntities(data.title) || 'Chart Data';
        
        // Destroy previous chart instance if it exists
        if (currentChartInstance) {
            currentChartInstance.destroy();
        }
        
        // Remove any no-data message
        $('.medshi-geo-block-no-data').remove();
        
        // Default chart type (fallback to 'bar' if not specified)
        const chartType = data.chart_type || 'bar';
        
        // Prepare default colors for different chart types
        let backgroundColor, borderColor, hoverBackgroundColor;
        
        if (chartType === 'pie') {
            backgroundColor = data.colors || ['#4A90E2', '#FF6384', '#36A2EB', '#FFCE56'];
            borderColor = 'white';
            hoverBackgroundColor = data.colors ? data.colors.map(color => adjustBrightness(color, 20)) : null;
        } else if (chartType === 'line') {
            backgroundColor = 'rgba(74, 144, 226, 0.2)';
            borderColor = '#4A90E2';
            hoverBackgroundColor = 'rgba(74, 144, 226, 0.4)';
        } else { // bar
            backgroundColor = '#4A90E2';
            borderColor = '#2171b1';
            hoverBackgroundColor = '#5AA0F2';
        }
        
        // Prepare chart configuration
        const chartConfig = {
            type: chartType,
            data: {
                labels: data.labels,
                datasets: [{
                    label: chartTitle,
                    data: data.counts,
                    backgroundColor: backgroundColor,
                    borderColor: borderColor,
                    borderWidth: 1,
                    hoverBackgroundColor: hoverBackgroundColor,
                    hoverBorderColor: borderColor,
                    fill: chartType === 'line', // Fill only for line charts
                    tension: chartType === 'line' ? 0.4 : 0 // Curve line charts slightly
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        display: chartType === 'pie'
                    },
                    title: {
                        display: true,
                        text: chartTitle,
                        font: {
                            size: 16
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        display: chartType !== 'pie',
                        title: {
                            display: chartType !== 'pie',
                            text: chartType === 'line' ? 'Time Period' : 'Categories'
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 0
                        }
                    },
                    y: {
                        display: chartType !== 'pie',
                        title: {
                            display: chartType !== 'pie',
                            text: 'Count'
                        },
                        beginAtZero: true
                    }
                }
            }
        };
        
        // Create new chart instance
        currentChartInstance = new Chart(ctx, chartConfig);
        console.log('Chart.js instance created. Type:', chartType);
    }
    
    /**
     * Adjust color brightness for hover effects
     * 
     * @param {string} color - The color to adjust in hex format
     * @param {number} percent - Percentage to brighten (positive) or darken (negative)
     * @returns {string} The adjusted color in hex format
     */
    function adjustBrightness(color, percent) {
        // If color is not in hex format, return as is
        if (!color || color[0] !== '#') {
            return color;
        }
        
        let R = parseInt(color.substring(1, 3), 16);
        let G = parseInt(color.substring(3, 5), 16);
        let B = parseInt(color.substring(5, 7), 16);

        R = Math.max(0, Math.min(255, R + percent));
        G = Math.max(0, Math.min(255, G + percent));
        B = Math.max(0, Math.min(255, B + percent));

        const RR = ((R.toString(16).length === 1) ? "0" + R.toString(16) : R.toString(16));
        const GG = ((G.toString(16).length === 1) ? "0" + G.toString(16) : G.toString(16));
        const BB = ((B.toString(16).length === 1) ? "0" + B.toString(16) : B.toString(16));

        return "#" + RR + GG + BB;
    }

    // Initialize all components when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        initClearLogsButton();
        initChartFilters();
        initLogsTableFilters(); // Initialize live filtering
    });

    // Country Selector Implementation
    const countryListSelector = '#' + medshiGeoBlockerAdmin.country_list_selector_id;
    const countrySearchInput = '#' + medshiGeoBlockerAdmin.country_search_input_id;
    const hiddenInputsContainer = '#' + medshiGeoBlockerAdmin.hidden_inputs_container_id;
    const countryToggleClass = '.' + medshiGeoBlockerAdmin.country_toggle_class;
    
    // Track current filter state
    let currentRegionFilter = 'all';
    let currentSearchTerm = '';
    
    // Initialize Set for efficient tracking of selected countries
    let selectedCountries = new Set(medshiGeoBlockerAdmin.saved_countries || []);
    
    // Blocking Mode Tabs Functionality
    const initBlockingModeTabs = function() {
        const $modeTabs = $('.medshi-geo-block-mode-tab');
        const $modeHiddenInput = $('#medshi_geo_block_mode_hidden');
        const $countrySelectorContainer = $('#medshi-geo-block-country-selector-container');
        const activeModeClass = 'medshi-geo-block-mode-tab-active';
        const borderClassBlock = 'medshi-geo-block-border-block';
        const borderClassAllow = 'medshi-geo-block-border-allow';
        
        // Function to update the UI based on selected mode
        function updateModeUI(selectedMode) {
            // Remove active class from all tabs
            $modeTabs.removeClass(activeModeClass);
            
            // Add active class to the selected tab
            $modeTabs.filter(`[data-mode="${selectedMode}"]`).addClass(activeModeClass);
            
            // Update hidden input value
            $modeHiddenInput.val(selectedMode);
            
            // Update border color class
            $countrySelectorContainer.removeClass(`${borderClassBlock} ${borderClassAllow}`);
            
            if (selectedMode === 'block_selected') {
                $countrySelectorContainer.addClass(borderClassBlock);
            } else if (selectedMode === 'allow_selected') {
                $countrySelectorContainer.addClass(borderClassAllow);
            }
        }
        
        // Initialize UI based on current value
        const initialMode = $modeHiddenInput.val() || 'block_selected';
        updateModeUI(initialMode);
        
        // Add click event handler for tabs
        $modeTabs.on('click', function() {
            const newMode = $(this).data('mode');
            updateModeUI(newMode);
        });
        
        // Add keyboard support for accessibility
        $modeTabs.on('keydown', function(e) {
            // Enter or Space key
            if (e.which === 13 || e.which === 32) {
                e.preventDefault();
                $(this).trigger('click');
            }
        }).attr('tabindex', '0')
          .attr('role', 'tab');
    };
    
    /**
     * Generates region filter buttons based on unique regions in the country data
     */
    function generateRegionFilterButtons() {
        const $regionFiltersContainer = $('#medshi-geo-block-region-filters-container');
        $regionFiltersContainer.empty();
        
        // Extract unique regions from country data
        const regions = new Set();
        medshiGeoBlockerAdmin.all_countries.forEach(function(country) {
            if (country.region) {
                regions.add(country.region);
            }
        });
        
        // Create "All Regions" button (active by default)
        const allRegionsBtn = $('<button type="button" class="button medshi-geo-block-region-filter-btn active" data-region="all">All Regions</button>');
        $regionFiltersContainer.append(allRegionsBtn);
        
        // Create a button for each unique region
        const sortedRegions = Array.from(regions).sort();
        sortedRegions.forEach(function(region) {
            const regionBtn = $('<button type="button" class="button medshi-geo-block-region-filter-btn" data-region="' + region + '">' + region + '</button>');
            $regionFiltersContainer.append(regionBtn);
        });
        
        // Add click event listeners to region buttons
        $('.medshi-geo-block-region-filter-btn').on('click', function() {
            // Update active button styling
            $('.medshi-geo-block-region-filter-btn').removeClass('active');
            $(this).addClass('active');
            
            // Update current region filter
            currentRegionFilter = $(this).data('region');
            
            // Apply filters and render
            applyFiltersAndRender();
        });
    }
    
    /**
     * Creates the select/deselect all checkbox control
     */
    function createSelectAllControl() {
        const $selectAllContainer = $('#medshi-geo-block-select-all-container');
        $selectAllContainer.empty();
        
        const selectAllControl = $(`
            <label>
                <input type="checkbox" id="medshi-geo-block-select-all-visible-toggle" class="medshi-geo-block-select-all-toggle" />
                Select/Deselect All Visible
            </label>
        `);
        
        $selectAllContainer.append(selectAllControl);
        
        // Add change event listener
        $('#medshi-geo-block-select-all-visible-toggle').on('change', function() {
            const isChecked = $(this).prop('checked');
            
            // Find all visible country toggles
            $(countryListSelector + ' ' + countryToggleClass + ':visible').each(function() {
                const countryCode = $(this).val();
                $(this).prop('checked', isChecked);
                
                // Update selectedCountries Set
                if (isChecked) {
                    selectedCountries.add(countryCode);
                } else {
                    selectedCountries.delete(countryCode);
                }
            });
            
            // Update hidden inputs
            updateHiddenInputs();
        });
    }
    
    /**
     * Applies current filters and renders the filtered country list
     */
    function applyFiltersAndRender() {
        // Get current search term from input
        currentSearchTerm = $(countrySearchInput).val().toLowerCase();
        
        // Filter countries based on both region and search term
        const filteredCountries = medshiGeoBlockerAdmin.all_countries.filter(function(country) {
            // Check region filter
            const regionMatches = currentRegionFilter === 'all' || country.region === currentRegionFilter;
            
            // Check search term
            const searchMatches = currentSearchTerm === '' || 
                country.name.toLowerCase().includes(currentSearchTerm) || 
                country.code.toLowerCase().includes(currentSearchTerm);
            
            return regionMatches && searchMatches;
        });
        
        // Render the filtered list
        renderCountryList(filteredCountries);
        
        // Update select all checkbox state
        updateSelectAllCheckboxState();
    }
    
    /**
     * Updates the select all checkbox state based on visible countries
     */
    function updateSelectAllCheckboxState() {
        const $selectAllCheckbox = $('#medshi-geo-block-select-all-visible-toggle');
        if (!$selectAllCheckbox.length) return;
        
        const $visibleToggles = $(countryListSelector + ' ' + countryToggleClass + ':visible');
        const $checkedToggles = $(countryListSelector + ' ' + countryToggleClass + ':visible:checked');
        
        if ($visibleToggles.length === 0) {
            // No visible toggles, uncheck and disable
            $selectAllCheckbox.prop('checked', false).prop('disabled', true);
        } else if ($checkedToggles.length === 0) {
            // None checked, clear the checkbox
            $selectAllCheckbox.prop('checked', false).prop('disabled', false);
        } else if ($checkedToggles.length === $visibleToggles.length) {
            // All checked, fill the checkbox
            $selectAllCheckbox.prop('checked', true).prop('disabled', false);
        } else {
            // Some checked, some not - uncheck but keep enabled
            $selectAllCheckbox.prop('checked', false).prop('disabled', false);
        }
    }
    
    /**
     * Renders the country list based on provided countries array
     * @param {Array} countriesToRender - Array of country objects to render
     */
    function renderCountryList(countriesToRender) {
        const $countryList = $(countryListSelector);
        $countryList.empty();
        
        if (!countriesToRender || countriesToRender.length === 0) {
            $countryList.html('<p>' + medshiGeoBlockerAdmin.no_countries_found_text + '</p>');
            // Update select all checkbox state
            updateSelectAllCheckboxState();
            return;
        }
        
        countriesToRender.forEach(function(country) {
            const isSelected = selectedCountries.has(country.code);
            const countryItem = `
                <div class="medshi-geo-block-country-item">
                    <label class="medshi-geo-block-country-toggle-label">
                        <input type="checkbox" class="${medshiGeoBlockerAdmin.country_toggle_class}" 
                               value="${country.code}" ${isSelected ? 'checked' : ''}>
                        <span class="medshi-geo-block-toggle-slider"></span>
                        <span class="medshi-geo-block-country-flag">${country.flag}</span>
                        <span class="medshi-geo-block-country-name">${country.name}</span>
                    </label>
                </div>
            `;
            $countryList.append(countryItem);
        });
        
        // Update hidden inputs after rendering
        updateHiddenInputs();
        
        // Update select all checkbox state
        updateSelectAllCheckboxState();
    }
    
    /**
     * Updates the hidden input fields based on selectedCountries Set
     * These hidden inputs are submitted with the form to the WordPress Settings API
     * which will be processed by PHP sanitize_countries_list() method
     */
    function updateHiddenInputs() {
        const $container = $(hiddenInputsContainer);
        
        // Safety check to ensure container exists
        if (!$container.length) {
            console.error('Hidden inputs container not found:', hiddenInputsContainer);
            return;
        }
        
        // Clear existing hidden inputs
        $container.empty();
        
        // Create a hidden input for each selected country
        selectedCountries.forEach(function(code) {
            $container.append(`<input type="hidden" name="medshi_geo_block_countries[]" value="${code}">`);
        });
        
        // Log for debugging (in development only)
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            console.log('Updated hidden inputs with', selectedCountries.size, 'selected countries');
        }
    }
    
    // Event delegation for country toggle changes
    $(document).on('change', countryToggleClass, function() {
        const countryCode = $(this).val();
        const isChecked = $(this).prop('checked');
        
        if (isChecked) {
            selectedCountries.add(countryCode);
        } else {
            selectedCountries.delete(countryCode);
        }
        
        updateHiddenInputs();
        
        // Update select all checkbox state after toggling
        updateSelectAllCheckboxState();
    });
    
    // Live search functionality
    $(countrySearchInput).on('input', function() {
        currentSearchTerm = $(this).val().toLowerCase();
        applyFiltersAndRender();
    });
    
    // Initial setup of country selector
    if ($(countryListSelector).length) {
        // Generate region filter buttons
        generateRegionFilterButtons();
        
        // Create select all control
        createSelectAllControl();
        
        // Initialize blocking mode tabs
        initBlockingModeTabs();
        
        // Render initial country list
        applyFiltersAndRender();
    }
}); 