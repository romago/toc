<?php
/**
 * The core plugin class.
 *
 */
require_once plugin_dir_path(dirname(__FILE__)) . 'classes/setup.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'classes/toc-widget.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'classes/content-parser.php';

class toc_Plugin extends toc_Setup {
	public $config;
	private $content_parser;
	
	public function __construct($config) {
		$this->config = $config;
		add_action('init', array(&$this, 'init'));
		add_action('widgets_init', array(&$this, 'register_widgets'));
		add_action('wp_enqueue_scripts', array(&$this, 'enqueue_styles'));
	}

	public function init() {
		// Initialize the content parser
		$this->content_parser = new TOC_Content_Parser();
	}

	/**
	 * Register the TOC widget.
	 */
	public function register_widgets() {
		register_widget('TOC_Widget');
	}

	/**
	 * Enqueue CSS styles for the TOC widget.
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			'toc-widget-style',
			plugin_dir_url(dirname(__FILE__)) . 'assets/toc-widget.css',
			array(),
			$this->config['version']
		);
		
		wp_enqueue_script(
			'toc-widget-script',
			plugin_dir_url(dirname(__FILE__)) . 'assets/toc-widget.js',
			array('jquery'),
			$this->config['version'],
			true
		);
	}

}