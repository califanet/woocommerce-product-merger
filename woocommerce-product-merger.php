<?php
/**
 * Plugin Name: WooCommerce Product Merger
 * Plugin URI: https://example.com/woocommerce-product-merger
 * Description: Find similar products and convert them to variable products with recommendations
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: wc-product-merger
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 *
 * @package WooCommerce_Product_Merger
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define plugin constants.
define( 'WCPM_VERSION', '1.0.0' );
define( 'WCPM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCPM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WCPM_PLUGIN_FILE', __FILE__ );

/**
 * Main plugin class.
 */
class WC_Product_Merger {
	
	/**
	 * Instance of this class.
	 *
	 * @var WC_Product_Merger
	 */
	private static $instance = null;
	
	/**
	 * Get instance of this class.
	 *
	 * @return WC_Product_Merger
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}
	
	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Check if WooCommerce is active.
		add_action( 'plugins_loaded', array( $this, 'check_woocommerce' ), 20 );
		
		// Admin hooks - only register after WooCommerce check.
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
			// Register AJAX handlers after plugins_loaded to ensure classes are available.
			add_action( 'wp_loaded', array( $this, 'register_ajax_handlers' ) );
		}
	}
	
	/**
	 * Register AJAX handlers.
	 */
	public function register_ajax_handlers() {
		if ( class_exists( 'WCPM_Similarity_Detector' ) && class_exists( 'WCPM_Product_Merger' ) ) {
			add_action( 'wp_ajax_wcpm_get_recommendations', array( $this, 'ajax_get_recommendations' ) );
			add_action( 'wp_ajax_wcpm_merge_products', array( $this, 'ajax_merge_products' ) );
		}
	}
	
	/**
	 * Check if WooCommerce is active.
	 */
	public function check_woocommerce() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}
		
		// Include required files.
		$similarity_file = WCPM_PLUGIN_DIR . 'includes/class-wcpm-similarity-detector.php';
		$merger_file = WCPM_PLUGIN_DIR . 'includes/class-wcpm-product-merger.php';
		
		if ( file_exists( $similarity_file ) ) {
			require_once $similarity_file;
		}
		
		if ( file_exists( $merger_file ) ) {
			require_once $merger_file;
		}
	}
	
	/**
	 * WooCommerce missing notice.
	 */
	public function woocommerce_missing_notice() {
		?>
		<div class="error">
			<p><?php esc_html_e( 'WooCommerce Product Merger requires WooCommerce to be installed and active.', 'wc-product-merger' ); ?></p>
		</div>
		<?php
	}
	
	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		// Only add menu if WooCommerce is active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		
		add_submenu_page(
			'woocommerce',
			__( 'Product Merger', 'wc-product-merger' ),
			__( 'Product Merger', 'wc-product-merger' ),
			'manage_woocommerce',
			'wc-product-merger',
			array( $this, 'render_admin_page' )
		);
	}
	
	/**
	 * Enqueue admin scripts and styles.
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'woocommerce_page_wc-product-merger' !== $hook ) {
			return;
		}
		
		wp_enqueue_style(
			'wcpm-admin-style',
			WCPM_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			WCPM_VERSION
		);
		
		wp_enqueue_script(
			'wcpm-admin-script',
			WCPM_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			WCPM_VERSION,
			true
		);
		
		wp_localize_script(
			'wcpm-admin-script',
			'wcpmData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wcpm_nonce' ),
				'i18n'    => array(
					'loading'        => __( 'Loading...', 'wc-product-merger' ),
					'noRecommendations' => __( 'No recommendations found.', 'wc-product-merger' ),
					'mergeSuccess'   => __( 'Products merged successfully!', 'wc-product-merger' ),
					'mergeError'     => __( 'Error merging products.', 'wc-product-merger' ),
					'confirmMerge'   => __( 'Are you sure you want to merge the selected products?', 'wc-product-merger' ),
				),
			)
		);
	}
	
	/**
	 * Render admin page.
	 */
	public function render_admin_page() {
		$template_file = WCPM_PLUGIN_DIR . 'templates/admin-page.php';
		if ( file_exists( $template_file ) ) {
			include $template_file;
		} else {
			echo '<div class="wrap"><h1>Error</h1><p>Template file not found.</p></div>';
		}
	}
	
	/**
	 * AJAX handler for getting recommendations.
	 */
	public function ajax_get_recommendations() {
		check_ajax_referer( 'wcpm_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wc-product-merger' ) ) );
		}
		
		if ( ! class_exists( 'WCPM_Similarity_Detector' ) ) {
			wp_send_json_error( array( 'message' => __( 'Plugin classes not loaded. Please ensure WooCommerce is active.', 'wc-product-merger' ) ) );
		}
		
		$detector = new WCPM_Similarity_Detector();
		$recommendations = $detector->get_recommendations();
		
		wp_send_json_success( array( 'recommendations' => $recommendations ) );
	}
	
	/**
	 * AJAX handler for merging products.
	 */
	public function ajax_merge_products() {
		check_ajax_referer( 'wcpm_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wc-product-merger' ) ) );
		}
		
		if ( ! class_exists( 'WCPM_Product_Merger' ) ) {
			wp_send_json_error( array( 'message' => __( 'Plugin classes not loaded. Please ensure WooCommerce is active.', 'wc-product-merger' ) ) );
		}
		
		$product_ids = isset( $_POST['product_ids'] ) ? array_map( 'intval', $_POST['product_ids'] ) : array();
		
		if ( empty( $product_ids ) || count( $product_ids ) < 2 ) {
			wp_send_json_error( array( 'message' => __( 'Please select at least 2 products to merge.', 'wc-product-merger' ) ) );
		}
		
		$merger = new WCPM_Product_Merger();
		$result = $merger->merge_products( $product_ids );
		
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		
		wp_send_json_success( array( 'message' => __( 'Products merged successfully!', 'wc-product-merger' ), 'product_id' => $result ) );
	}
}

// Initialize the plugin.
WC_Product_Merger::get_instance();
