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
                
                if (response.success) {
                    $progressBar.css('width', '100%').text('100%');
                    $progressMessage.text(wpSiteAnalyzer.strings.scan_complete);
                    
                    // Redirect to results page after 2 seconds
                    setTimeout(function() {
                        window.location.href = window.location.href + '&scan_complete=1';
                    }, 2000);
                } else {
                    alert(response.data || wpSiteAnalyzer.strings.scan_error);
                    resetScanButton();
                }
            },
            error: function() {
                clearInterval(progressInterval);
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
                    
                    $progressBar.css('width', percentage + '%').text(percentage + '%');
                    $progressMessage.text(progress.message || wpSiteAnalyzer.strings.scanning);
                }
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