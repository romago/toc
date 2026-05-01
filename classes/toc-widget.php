<?php
/**
 * Table of Contents Widget Class
 * 
 * Displays a dynamic table of contents for blog posts
 * based on H2 and H3 headings found in the post content.
 */

class TOC_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'toc_widget',
            __('Table of Contents', 'toc'),
            array(
                'description' => __('Displays a table of contents for the current post based on H2 and H3 headings.', 'toc'),
                'classname' => 'toc-widget'
            )
        );
    }

    /**
     * Frontend display of the widget.
     */
    public function widget($args, $instance) {
        // Only show on single posts and pages
        if (!is_single() && !is_page()) {
            return;
        }

        global $post;
        
        // Make sure we have a post
        if (!$post) {
            return;
        }
        
        // Always get ALL content sources and process them fresh
        $all_content = $this->get_all_post_content();
        $toc_data = $this->extract_headings_from_content($all_content);
        
        // If no headings found, don't display the widget
        if (empty($toc_data)) {
            return;
        }

        $title = !empty($instance['title']) ? $instance['title'] : __('Table of Contents', 'toc');
        $title = apply_filters('widget_title', $title);

        echo $args['before_widget'];
        
        if (!empty($title)) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        $this->render_toc($toc_data);
        
        // Add JSON-LD structured data for SEO
        $this->render_schema($toc_data);

        echo $args['after_widget'];
    }

    /**
     * Backend widget form.
     */
    public function form($instance) {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : __('Table of Contents', 'toc');
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'toc'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
        </p>
        <?php
    }

    /**
     * Update widget settings.
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        return $instance;
    }

    /**
     * Get all content from all sources (main content + ACF fields)
     */
    private function get_all_post_content() {
        global $post;
        
        // Get main content and apply filters
        $main_content = get_the_content();
        $all_content = apply_filters('the_content', $main_content);
        
        // Add ACF fields content - including repeater/flexible content fields
        if (function_exists('get_fields')) {
            $this->process_acf_fields($all_content);
        }
        
        return $all_content;
    }

    /**
     * Process ACF fields recursively to handle repeater/flexible content fields
     */
    private function process_acf_fields(&$all_content) {
        global $post;
        
        // Try to get all field data including repeaters using get_field_objects
        if (function_exists('get_field_objects')) {
            $field_objects = get_field_objects($post->ID);
            if ($field_objects) {
                foreach ($field_objects as $field_key => $field_object) {
                    if (isset($field_object['value'])) {
                        $this->extract_content_from_field_value($field_key, $field_object['value'], $all_content);
                    }
                }
                return;
            }
        }
        
        // Fallback: Direct database lookup for repeater fields
        $this->find_repeater_fields_in_meta($all_content);
    }

    /**
     * Extract content from a field value (recursive for arrays)
     */
    private function extract_content_from_field_value($field_key, $field_value, &$all_content) {
        if (is_string($field_value)) {
            // Check if this field contains headings
            if (strpos($field_value, '<h2') !== false || strpos($field_value, '<h3') !== false) {
                $all_content .= $field_value;
            }
        } elseif (is_array($field_value)) {
            // Recursively process array fields (repeaters, flexible content)
            foreach ($field_value as $sub_key => $sub_value) {
                $full_key = $field_key . '_' . $sub_key;
                $this->extract_content_from_field_value($full_key, $sub_value, $all_content);
            }
        }
    }

    /**
     * Find repeater fields directly in post meta (last resort)
     */
    private function find_repeater_fields_in_meta(&$all_content) {
        global $post;
        
        // Look for meta keys matching the pattern layout_options_N_body
        $all_meta = get_post_meta($post->ID);
        foreach ($all_meta as $meta_key => $meta_values) {
            // Check if this looks like a repeater field with 'body' content
            if (preg_match('/^layout_options_\d+_body$/', $meta_key)) {
                $meta_value = is_array($meta_values) ? $meta_values[0] : $meta_values;
                if (is_string($meta_value) && (strpos($meta_value, '<h2') !== false || strpos($meta_value, '<h3') !== false)) {
                    $all_content .= $meta_value;
                }
            }
        }
    }

    /**
     * Extract headings directly from processed content
     */
    private function extract_headings_from_content($content) {
        $headings = array();
        $used_ids = array();
        
        if (class_exists('DOMDocument')) {
            $dom = new DOMDocument();
            
            // Suppress warnings for malformed HTML
            libxml_use_internal_errors(true);
            
            // Load content as HTML
            $dom->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            
            // Clear libxml errors
            libxml_clear_errors();
            
            // Find H2 and H3 elements
            $xpath = new DOMXPath($dom);
            $heading_elements = $xpath->query('//h2 | //h3');
            
            foreach ($heading_elements as $element) {
                $text = trim($element->textContent);
                if (!empty($text)) {
                    // Use existing ID if available, otherwise generate one
                    $anchor_id = '';
                    if ($element->hasAttribute('id')) {
                        $anchor_id = $element->getAttribute('id');
                    } else {
                        $anchor_id = $this->generate_unique_anchor_id($text, $used_ids);
                    }
                    $used_ids[] = $anchor_id;
                    
                    $headings[] = array(
                        'level' => (int) substr($element->tagName, 1),
                        'text' => $text,
                        'anchor' => $anchor_id
                    );
                }
            }
        } else {
            // Fallback to regex if DOMDocument is not available
            $pattern = '/<h([23])([^>]*?)>(.*?)<\/h[23]>/i';
            preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                $level = (int) $match[1];
                $attributes = $match[2];
                $text = trim(strip_tags($match[3]));
                
                if (!empty($text)) {
                    // Extract existing ID from attributes
                    $existing_id = '';
                    if (preg_match('/id=["\']([^"\']*)["\']/', $attributes, $id_match)) {
                        $existing_id = $id_match[1];
                    }
                    
                    $anchor_id = !empty($existing_id) ? $existing_id : $this->generate_unique_anchor_id($text, $used_ids);
                    $used_ids[] = $anchor_id;
                    
                    $headings[] = array(
                        'level' => $level,
                        'text' => $text,
                        'anchor' => $anchor_id
                    );
                }
            }
        }
        
        return $headings;
    }

    /**
     * Generate unique anchor ID from heading text.
     */
    private function generate_unique_anchor_id($text, &$used_ids) {
        // Convert to lowercase
        $anchor = strtolower($text);
        
        // Replace spaces and special characters with hyphens
        $anchor = preg_replace('/[^a-z0-9]+/', '-', $anchor);
        
        // Remove leading/trailing hyphens
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
     * Render the table of contents HTML.
     */
    private function render_toc($headings) {
        if (empty($headings)) {
            return;
        }

        echo '<nav class="toc-nav">';
        echo '<ul class="toc-list">';
        
        // Find the minimum heading level to avoid unnecessary nesting
        $min_level = min(array_column($headings, 'level'));
        $current_level = $min_level;
        
        foreach ($headings as $heading) {
            $level = $heading['level'];
            $text = esc_html($heading['text']);
            $anchor = esc_attr($heading['anchor']);
            
            // Handle nested structure
            if ($level > $current_level) {
                // Opening nested list
                echo '<li class="toc-list-nested"><ul>';
            } elseif ($level < $current_level) {
                // Closing nested list
                echo '</ul></li>';
            }
            
            echo '<li class="toc-item toc-h' . $level . '">';
            echo '<a href="#' . $anchor . '" class="toc-link">' . $text . '</a>';
            echo '</li>';
            
            $current_level = $level;
        }
        
        // Close any remaining nested lists
        if ($current_level > $min_level) {
            echo '</ul></li>';
        }
        
        echo '</ul>';
        echo '</nav>';
    }

    /**
     * Render JSON-LD structured data for SEO
     */
    private function render_schema($headings) {
        if (empty($headings)) {
            return;
        }

        global $post;
        
        // Build the table of contents schema
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'mainEntityOfPage' => array(
                '@type' => 'WebPage',
                '@id' => get_permalink($post->ID)
            ),
            'headline' => get_the_title($post->ID),
            'tableOfContents' => array()
        );

        // Add each heading to the table of contents
        foreach ($headings as $heading) {
            $schema['tableOfContents'][] = array(
                '@type' => 'HowToSection',
                'name' => $heading['text'],
                'url' => get_permalink($post->ID) . '#' . $heading['anchor'],
                'position' => count($schema['tableOfContents']) + 1
            );
        }

        // Output the JSON-LD script
        echo '<script type="application/ld+json">';
        echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        echo '</script>';
    }
}