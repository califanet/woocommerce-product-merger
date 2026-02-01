=== WooCommerce Product Merger ===
Contributors: yourname
Tags: woocommerce, products, variable products, merge
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Find similar products and convert them to variable products with recommendations.

== Description ==

WooCommerce Product Merger helps you identify similar simple products and convert them into variable products. The plugin analyzes your products based on:

* Product names (with intelligent base name extraction)
* SKU similarity
* Category matching
* Attribute matching

**Features:**

* Automatic similarity detection using multiple criteria
* Visual recommendations interface with similarity scores
* Suggested attributes for variable products
* Batch selection and merging
* Safe product merging with variation creation

**How it works:**

1. Go to WooCommerce > Product Merger
2. Click "Load Recommendations" to scan your products
3. Review the recommended product groups
4. Select products you want to merge using checkboxes
5. Click "Merge Selected Products" to convert them to a variable product

The plugin will:
* Create a new variable product with a common name
* Detect and set appropriate attributes (size, color, etc.)
* Create variations from the original simple products
* Preserve pricing, stock, and other product data

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/woocommerce-product-merger` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Make sure WooCommerce is installed and activated.
4. Navigate to WooCommerce > Product Merger to start using the plugin.

== Frequently Asked Questions ==

= Does this plugin delete my original products? =

Yes, after successfully creating the variable product and variations, the original simple products are deleted. Make sure to backup your site before using this plugin.

= Can I undo a merge? =

No, the merge operation cannot be undone. Always backup your site before merging products.

= What similarity threshold is used? =

The plugin uses a 60% similarity threshold by default. Products must meet this threshold to be recommended for merging.

= How are attributes detected? =

The plugin analyzes existing product attributes and also detects common patterns in product names (like size and color variations).

== Changelog ==

= 1.0.0 =
* Initial release
* Similarity detection based on name, SKU, categories, and attributes
* Recommendation interface with visual feedback
* Product merging functionality
* Automatic attribute detection

== Upgrade Notice ==

= 1.0.0 =
Initial release of WooCommerce Product Merger.
