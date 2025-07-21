/**
 * WP Site Analyzer Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Start scan button
        $('#start-scan').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm(wpSiteAnalyzer.strings.confirm_scan)) {
                return;
            }
            
            startScan();
        });

    });

    /**
     * Start the site scan
     */
    function startScan() {
        console.log('WP Site Analyzer: Starting scan...');
        
        var $button = $('#start-scan');
        var $progress = $('#scan-progress');
        var $progressBar = $('.progress-bar-fill');
        var $progressMessage = $('.progress-message');
        
        // Disable button and show progress
        $button.prop('disabled', true).addClass('scanning');
        $progress.show();
        
        // Start progress monitoring
        var progressInterval = setInterval(function() {
            checkProgress($progressBar, $progressMessage);
        }, 1000);
        
        // Make AJAX request
        console.log('WP Site Analyzer: Sending AJAX request...', {
            action: 'wp_site_analyzer_scan',
            nonce: wpSiteAnalyzer.nonce
        });
        
        $.ajax({
            url: wpSiteAnalyzer.ajax_url,
            type: 'POST',
            data: {
                action: 'wp_site_analyzer_scan',
                nonce: wpSiteAnalyzer.nonce,
                options: JSON.stringify({
                    scanners: [] // All scanners
                })
            },
            success: function(response) {
                clearInterval(progressInterval);
                console.log('WP Site Analyzer: Scan response received:', response);
                
                if (response.success) {
                    $progressBar.css('width', '100%').text('100%');
                    $progressMessage.text(wpSiteAnalyzer.strings.scan_complete);
                    
                    console.log('WP Site Analyzer: Scan successful, redirecting...');
                    
                    // Redirect to results page after 2 seconds
                    setTimeout(function() {
                        window.location.href = window.location.href + '&scan_complete=1';
                    }, 2000);
                } else {
                    console.error('WP Site Analyzer: Scan failed:', response.data);
                    alert(response.data || wpSiteAnalyzer.strings.scan_error);
                    resetScanButton();
                }
            },
            error: function(xhr, status, error) {
                clearInterval(progressInterval);
                console.error('WP Site Analyzer: AJAX error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                alert(wpSiteAnalyzer.strings.scan_error);
                resetScanButton();
            }
        });
    }

    /**
     * Check scan progress
     */
    function checkProgress($progressBar, $progressMessage) {
        $.ajax({
            url: wpSiteAnalyzer.ajax_url,
            type: 'POST',
            data: {
                action: 'wp_site_analyzer_get_progress',
                nonce: wpSiteAnalyzer.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    var progress = response.data;
                    var percentage = progress.total > 0 ? Math.round((progress.current / progress.total) * 100) : 0;
                    
                    console.log('WP Site Analyzer: Progress update:', progress);
                    
                    $progressBar.css('width', percentage + '%').text(percentage + '%');
                    $progressMessage.text(progress.message || wpSiteAnalyzer.strings.scanning);
                }
            },
            error: function(xhr, status, error) {
                console.error('WP Site Analyzer: Progress check error:', {
                    status: status,
                    error: error
                });
            }
        });
    }

    /**
     * Reset scan button
     */
    function resetScanButton() {
        $('#start-scan').prop('disabled', false).removeClass('scanning');
        $('#scan-progress').hide();
        $('.progress-bar-fill').css('width', '0%').text('');
    }


})(jQuery);