<?php
/**
 * Content Parser Class
 * 
 * Handles parsing post content and injecting anchor IDs into headings.
 */

class TOC_Content_Parser {
    
    public function __construct() {
        // Hook into content filter to add anchor IDs to headings
        add_filter('the_content', array($this, 'process_content_and_store_headings'), 5);
        
        // Also hook into ACF content if ACF is active
        add_filter('acf/format_value/type=textarea', array($this, 'add_heading_anchors_acf'), 10, 3);
        add_filter('acf/format_value/type=wysiwyg', array($this, 'add_heading_anchors_acf'), 10, 3);
    }

    /**
     * Reset heading storage for new post
     */
    public function reset_current_headings() {
        self::$current_headings = array();
        self::$main_content_processed = false;
    }

    /**
     * Process content: add anchor IDs AND store heading info for widget
     */
    public function process_content_and_store_headings($content) {
        // Only process on single posts/pages
        if (!is_single() && !is_page()) {
            return $content;
        }

        // Skip if content is empty
        if (empty($content)) {
            return $content;
        }

        // Simply process content and add anchors - don't worry about complex timing
        // The widget will handle getting all content sources
        if (class_exists('DOMDocument')) {
            return $this->add_anchors_dom($content);
        } else {
            return $this->add_anchors_regex($content);
        }
    }

    /**
     * Add anchors using DOMDocument (simplified version)
     */
    private function add_anchors_dom($content) {
        $dom = new DOMDocument();
        
        // Suppress warnings for malformed HTML
        libxml_use_internal_errors(true);
        
        // Load content as HTML fragment
        $dom->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        // Clear libxml errors
        libxml_clear_errors();
        
        // Find H2 and H3 elements
        $xpath = new DOMXPath($dom);
        $heading_elements = $xpath->query('//h2 | //h3');
        
        $used_ids = array();
        
        foreach ($heading_elements as $element) {
            $text = trim($element->textContent);
            if (!empty($text) && !$element->hasAttribute('id')) {
                // Generate new anchor ID
                $anchor_id = $this->generate_unique_anchor_id($text, $used_ids);
                $element->setAttribute('id', $anchor_id);
                $used_ids[] = $anchor_id;
            }
        }
        
        // Return the modified HTML
        return $dom->saveHTML();
    }

    /**
     * Add anchors using regex (simplified version)
     */
    private function add_anchors_regex($content) {
        $used_ids = array();
        
        $pattern = '/<h([23])([^>]*?)>(.*?)<\/h[23]>/i';
        
        return preg_replace_callback($pattern, function($matches) use (&$used_ids) {
            $level = $matches[1];
            $attributes = $matches[2];
            $text_html = $matches[3];
            $text = trim(strip_tags($text_html));
            
            if (empty($text)) {
                return $matches[0]; // Return unchanged if no text
            }
            
            // Check if ID already exists
            if (strpos($attributes, 'id=') !== false) {
                return $matches[0]; // Return unchanged if ID already exists
            }
            
            // Generate new anchor ID
            $anchor_id = $this->generate_unique_anchor_id($text, $used_ids);
            $id_attr = ' id="' . esc_attr($anchor_id) . '"';
            $used_ids[] = $anchor_id;
            
            return '<h' . $level . $attributes . $id_attr . '>' . $text_html . '</h' . $level . '>';
        }, $content);
    }

    /**
     * Generate unique anchor ID with proper uniqueness handling
     */
    private function generate_unique_anchor_id($text, &$used_ids) {
        // Convert to lowercase and clean
        $anchor = strtolower($text);
        $anchor = preg_replace('/[^a-z0-9]+/', '-', $anchor);
        $anchor = trim($anchor, '-');
        
        // Ensure it's not empty
        if (empty($anchor)) {
            $anchor = 'heading-' . uniqid();
        }
        
        // Make it unique
        $original_anchor = $anchor;
        $counter = 1;
        
        while (in_array($anchor, $used_ids)) {
            $anchor = $original_anchor . '-' . $counter;
            $counter++;
        }
        
        return $anchor;
    }

    /**
     * Handle ACF content fields.
     */
    public function add_heading_anchors_acf($value, $post_id, $field) {
        // Only process if we're in a single post context
        if (!is_single() && !is_page()) {
            return $value;
        }

        // Only process if the field contains HTML content with headings
        if (is_string($value) && (strpos($value, '<h2') !== false || strpos($value, '<h3') !== false)) {
            // Simply add anchors - the widget will handle extraction
            if (class_exists('DOMDocument')) {
                return $this->add_anchors_dom($value);
            } else {
                return $this->add_anchors_regex($value);
            }
        }
        
        return $value;
    }
}