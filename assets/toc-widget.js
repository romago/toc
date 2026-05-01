/**
 * Table of Contents Widget JavaScript
 * Adds smooth scrolling and active link highlighting
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Variables to prevent conflicts between click and scroll handlers
        var isScrollingToTarget = false;
        var scrollTimeout;
        
        // Function to update active state
        function updateActiveState(targetItem) {
            if (targetItem.length && !targetItem.hasClass('active')) {
                $('.toc-item').removeClass('active');
                $('.toc-list-nested').removeClass('active-group');
                targetItem.addClass('active');
                if(targetItem.parents('.toc-list-nested').length) {
                    targetItem.parents('.toc-list-nested').addClass('active-group');
                }
            }
        }
        
        // Scrolling to section on link click
        $('.toc-link').off('click').on('click', function(e) {
            var target = $(this.getAttribute('href'));
            
            if (target.length) {
                e.preventDefault();
                
                // Set flag to prevent scroll handler interference
                isScrollingToTarget = true;
                
                // Clear any existing timeout
                if (scrollTimeout) {
                    clearTimeout(scrollTimeout);
                }
                
                // Update active link immediately
                var currentActiveItem = $(this).parents('.toc-item');
                updateActiveState(currentActiveItem);
                
                // Instant scroll to target
                $(window).scrollTop(target.offset().top - 100);
                
                // Reset flag immediately after instant scroll
                setTimeout(function() {
                    isScrollingToTarget = false;
                }, 50);
                
                // Update browser history
                if (history.pushState) {
                    history.pushState(null, null, this.getAttribute('href'));
                }
            }
        });
        
        // Highlight active section while scrolling
        if ($('.toc-link').length > 0) {
            var tocLinks = $('.toc-link');
            var headings = tocLinks.map(function() {
                var href = $(this).attr('href');
                if (href.charAt(0) === '#') {
                    return $(href);
                }
            }).get(); // Convert jQuery object to array
            
            function updateScrollHighlight() {
                // Don't update active state if we're currently scrolling to a target
                if (isScrollingToTarget) {
                    return;
                }
                
                var scrollTop = $(window).scrollTop();
                var windowHeight = $(window).height();
                var documentHeight = $(document).height();
                
                // Handle edge case: if we're at the bottom of the page
                if (scrollTop + windowHeight >= documentHeight - 10) {
                    var lastHeading = headings[headings.length - 1];
                    if (lastHeading && lastHeading.length) {
                        var lastTargetItem = tocLinks.filter('[href="#' + lastHeading[0].id + '"]').parents('.toc-item');
                        updateActiveState(lastTargetItem);
                        return;
                    }
                }
                
                // Find the current active section
                var currentSection = null;
                var offset = 120; // Offset from top for better detection
                
                for (var i = headings.length - 1; i >= 0; i--) {
                    var heading = headings[i];
                    if (heading && heading.length && heading.offset().top <= scrollTop + offset) {
                        currentSection = heading;
                        break;
                    }
                }
                
                // If no section found above the fold, use the first one
                if (!currentSection && headings.length > 0) {
                    currentSection = headings[0];
                }
                
                if (currentSection && currentSection.length) {
                    var id = currentSection[0].id;
                    if (id) {
                        var targetItem = tocLinks.filter('[href="#' + id + '"]').parents('.toc-item');
                        updateActiveState(targetItem);
                    }
                }
            }
            
            // Use both scroll and resize events for better responsiveness
            $(window).on('scroll', function() {
                // Don't process scroll events if we're scrolling to a target
                if (isScrollingToTarget) {
                    return;
                }
                
                // Debounced version for performance and to prevent excessive updates
                if (scrollTimeout) {
                    clearTimeout(scrollTimeout);
                }
                scrollTimeout = setTimeout(updateScrollHighlight, 100);
            });
            
            // Handle window resize
            $(window).on('resize', function() {
                if (scrollTimeout) {
                    clearTimeout(scrollTimeout);
                }
                scrollTimeout = setTimeout(updateScrollHighlight, 100);
            });
            
            // Initial call to set active state on page load
            setTimeout(updateScrollHighlight, 100);
        }
    });
    
})(jQuery);