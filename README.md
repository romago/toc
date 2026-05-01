# Table of Contents Widget for WordPress

This essential utility plugin is designed to enhance post readability and navigation by automatically generating a dynamic Table of Contents (TOC) for blog posts.

## Key Features

### Server-Side Detection
The plugin utilizes hooks (such as `the_content` and ACF) to parse the post's outputted HTML directly in PHP, creating the TOC before the page is delivered to the client. This bypasses client-side dependencies and ensures high performance.

### Dynamic Headings
It automatically detects and converts available H2 and H3 headings within the post content into navigable anchor links, allowing readers to jump instantly to relevant sections.

### H1 Exclusion
The main page title (H1) is deliberately ignored, ensuring a clean and focused table of contents composed solely of sub-section headings.

### Widget Implementation
The functionality is packaged as a widget, making it easy to place the interactive hyperlink list in the sidebar or any other widgetized area of the blog layout.

## Installation

1. Upload the plugin files to the `/wp-content/plugins/toc-widget/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Add the "Table of Contents" widget to your desired sidebar or widget area

## Usage

### Adding the Widget
1. Go to **Appearance > Widgets** in your WordPress admin
2. Find the "Table of Contents" widget
3. Drag it to your desired sidebar or widget area
4. Configure the widget title if needed
5. Save your changes

### How it Works
- The widget automatically appears on single blog posts and pages
- It scans the content for H2 and H3 headings
- Generates anchor IDs for each heading (if they don't already exist)
- Creates a clickable table of contents with nested structure
- Provides smooth scrolling to sections when links are clicked

## Features

### Automatic Anchor Generation
- Converts heading text to URL-friendly anchor IDs
- Ensures unique IDs even for duplicate headings
- Preserves existing IDs if already present

### Content Filtering
- Hooks into `the_content` filter to add anchors to headings
- Supports ACF (Advanced Custom Fields) content
- Works with both DOMDocument and regex parsing for maximum compatibility

### Responsive Design
- Mobile-friendly responsive CSS
- Smooth scrolling behavior
- Active section highlighting while scrolling

### Performance Optimized
- Server-side processing ensures fast loading
- Minimal JavaScript for enhanced user experience
- Clean, semantic HTML output

## Technical Details

### File Structure
```
toc-widget/
├── loader.php                    # Main plugin file
├── classes/
│   ├── plugin.php               # Core plugin class
│   ├── setup.php                # Installation/activation
│   ├── base.php                 # Helper functions
│   ├── toc-widget.php           # Widget class
│   └── content-parser.php       # Content parsing logic
└── assets/
    ├── toc-widget.css           # Widget styles
    └── toc-widget.js            # Enhancement JavaScript
```

### Hooks Used
- `the_content` - Adds anchor IDs to headings
- `acf/format_value/type=textarea` - ACF textarea support
- `acf/format_value/type=wysiwyg` - ACF WYSIWYG support
- `widgets_init` - Registers the widget
- `wp_enqueue_scripts` - Loads CSS and JS

### Browser Support
- Modern browsers with CSS Grid and Flexbox support
- Graceful degradation for older browsers
- JavaScript enhancement is optional

## Customization

### CSS Classes
- `.toc-widget` - Main widget container
- `.toc-nav` - Navigation wrapper
- `.toc-list` - List container
- `.toc-item` - Individual list items
- `.toc-link` - Anchor links
- `.toc-h2` - H2 heading items
- `.toc-h3` - H3 heading items

### Styling Options
The CSS can be customized to match your theme's design. Key areas for customization:
- Colors and typography
- Spacing and layout
- Hover and active states
- Mobile responsiveness

## Requirements
- WordPress 4.0 or higher
- PHP 5.6 or higher
- jQuery (included with WordPress)

## License
This plugin is licensed under the GPL v2 or later.

## Author
Roman Iglin