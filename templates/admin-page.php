<?php
/**
 * Admin Page Template
 *
 * @package WooCommerce_Product_Merger
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap wcpm-admin-page">
	<h1><?php esc_html_e( 'Product Merger', 'wc-product-merger' ); ?></h1>
	<p class="description">
		<?php esc_html_e( 'Find similar products and convert them to variable products. Select products you want to merge and click the merge button.', 'wc-product-merger' ); ?>
	</p>

	<div class="wcpm-controls">
		<button type="button" id="wcpm-load-recommendations" class="button button-primary">
			<?php esc_html_e( 'Load Recommendations', 'wc-product-merger' ); ?>
		</button>
		<button type="button" id="wcpm-merge-selected" class="button button-secondary" style="display: none;">
			<?php esc_html_e( 'Merge Selected Products', 'wc-product-merger' ); ?>
		</button>
	</div>

	<div id="wcpm-loading" class="wcpm-loading" style="display: none;">
		<span class="spinner is-active"></span>
		<span><?php esc_html_e( 'Loading recommendations...', 'wc-product-merger' ); ?></span>
	</div>

	<div id="wcpm-recommendations" class="wcpm-recommendations"></div>

	<div id="wcpm-messages" class="wcpm-messages"></div>
</div>