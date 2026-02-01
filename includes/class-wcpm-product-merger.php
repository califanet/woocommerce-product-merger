<?php
/**
 * Product Merger Class
 *
 * @package WooCommerce_Product_Merger
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WCPM_Product_Merger
 */
class WCPM_Product_Merger {
	
	/**
	 * Merge multiple simple products into a variable product.
	 *
	 * @param array $product_ids Array of product IDs to merge.
	 * @return int|WP_Error New variable product ID or error.
	 */
	public function merge_products( $product_ids ) {
		if ( count( $product_ids ) < 2 ) {
			return new WP_Error( 'insufficient_products', __( 'At least 2 products are required to merge.', 'wc-product-merger' ) );
		}
		
		// Get all products.
		$products = array();
		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product || 'simple' !== $product->get_type() ) {
				continue;
			}
			$products[] = $product;
		}
		
		if ( count( $products ) < 2 ) {
			return new WP_Error( 'invalid_products', __( 'Not enough valid simple products to merge.', 'wc-product-merger' ) );
		}
		
		// Use the first product as the base for the variable product.
		$base_product = $products[0];
		
		// Create new variable product.
		$variable_product = new WC_Product_Variable();
		$variable_product->set_name( $this->get_common_name( $products ) );
		$variable_product->set_description( $base_product->get_description() );
		$variable_product->set_short_description( $base_product->get_short_description() );
		$variable_product->set_status( $base_product->get_status() );
		$variable_product->set_catalog_visibility( $base_product->get_catalog_visibility() );
		$variable_product->set_featured( $base_product->get_featured() );
		$variable_product->set_sku( $this->generate_sku( $base_product ) );
		
		// Set categories.
		$all_categories = array();
		foreach ( $products as $product ) {
			$all_categories = array_merge( $all_categories, $product->get_category_ids() );
		}
		$variable_product->set_category_ids( array_unique( $all_categories ) );
		
		// Set tags.
		$all_tags = array();
		foreach ( $products as $product ) {
			$all_tags = array_merge( $all_tags, $product->get_tag_ids() );
		}
		$variable_product->set_tag_ids( array_unique( $all_tags ) );
		
		// Set images.
		$image_id = $base_product->get_image_id();
		if ( $image_id ) {
			$variable_product->set_image_id( $image_id );
		}
		
		// Detect and set attributes.
		$attributes = $this->detect_attributes( $products );
		$variable_product->set_attributes( $attributes );
		
		// Save the variable product.
		$variable_product_id = $variable_product->save();
		
		if ( is_wp_error( $variable_product_id ) || ! $variable_product_id ) {
			return new WP_Error( 'save_failed', __( 'Failed to create variable product.', 'wc-product-merger' ) );
		}
		
		// Create variations from simple products.
		$variation_ids = array();
		foreach ( $products as $product ) {
			$variation = $this->create_variation( $variable_product_id, $product, $attributes );
			if ( $variation && ! is_wp_error( $variation ) ) {
				$variation_ids[] = $variation;
			}
		}
		
		if ( empty( $variation_ids ) ) {
			// If no variations were created, delete the variable product.
			wp_delete_post( $variable_product_id, true );
			return new WP_Error( 'no_variations', __( 'Failed to create variations.', 'wc-product-merger' ) );
		}
		
		// Delete original simple products (optional - you might want to keep them).
		foreach ( $product_ids as $product_id ) {
			wp_delete_post( $product_id, true );
		}
		
		return $variable_product_id;
	}
	
	/**
	 * Get common name from products.
	 *
	 * @param array $products Array of WC_Product objects.
	 * @return string Common name.
	 */
	private function get_common_name( $products ) {
		$names = array();
		foreach ( $products as $product ) {
			$names[] = $product->get_name();
		}
		
		// Extract base name from first product.
		$first_name = $names[0];
		
		// Remove common variation patterns.
		$patterns = array(
			'/\s*-\s*(small|medium|large|xl|xxl|xxxl|xs|s|m|l)$/i',
			'/\s*-\s*(red|blue|green|yellow|black|white|gray|grey|brown|pink|purple|orange)$/i',
			'/\s*\([^)]*\)\s*$/',
			'/\s*\[[^\]]*\]\s*$/',
		);
		
		$base_name = $first_name;
		foreach ( $patterns as $pattern ) {
			$base_name = preg_replace( $pattern, '', $base_name );
		}
		
		return trim( $base_name ) ?: $first_name;
	}
	
	/**
	 * Generate SKU for variable product.
	 *
	 * @param WC_Product $base_product Base product.
	 * @return string SKU.
	 */
	private function generate_sku( $base_product ) {
		$base_sku = $base_product->get_sku();
		if ( $base_sku ) {
			// Remove variation suffix if exists.
			$base_sku = preg_replace( '/-[^-]+$/', '', $base_sku );
			return $base_sku . '-VAR';
		}
		return '';
	}
	
	/**
	 * Detect attributes from products.
	 *
	 * @param array $products Array of WC_Product objects.
	 * @return array Attributes array.
	 */
	private function detect_attributes( $products ) {
		$attributes = array();
		$all_attributes = array();
		$attribute_types = array(); // Track if attribute is taxonomy or not.
		
		// Collect all attributes from all products.
		foreach ( $products as $product ) {
			$product_attributes = $product->get_attributes();
			foreach ( $product_attributes as $attribute_name => $attribute ) {
				if ( ! isset( $all_attributes[ $attribute_name ] ) ) {
					$all_attributes[ $attribute_name ] = array();
					$attribute_types[ $attribute_name ] = $attribute->is_taxonomy();
				}
				
				if ( $attribute->is_taxonomy() ) {
					$terms = $attribute->get_options(); // Returns term IDs for taxonomy.
					$all_attributes[ $attribute_name ] = array_merge( $all_attributes[ $attribute_name ], $terms );
				} else {
					$options = $attribute->get_options(); // Returns option names for non-taxonomy.
					$all_attributes[ $attribute_name ] = array_merge( $all_attributes[ $attribute_name ], $options );
				}
			}
		}
		
		// Create attribute objects for attributes that vary.
		foreach ( $all_attributes as $attribute_name => $values ) {
			$unique_values = array_unique( $values, SORT_REGULAR );
			
			// Only include attributes that have different values across products.
			if ( count( $unique_values ) > 1 ) {
				$attribute = new WC_Product_Attribute();
				$is_taxonomy = isset( $attribute_types[ $attribute_name ] ) && $attribute_types[ $attribute_name ];
				
				if ( $is_taxonomy ) {
					$attribute->set_id( $this->get_attribute_id( $attribute_name ) );
				} else {
					$attribute->set_id( 0 );
				}
				
				$attribute->set_name( $attribute_name );
				$attribute->set_options( array_values( $unique_values ) );
				$attribute->set_visible( true );
				$attribute->set_variation( true );
				
				$attributes[ $attribute_name ] = $attribute;
			}
		}
		
		// If no attributes found, try to detect from product names.
		if ( empty( $attributes ) ) {
			$attributes = $this->detect_attributes_from_names( $products );
		}
		
		return $attributes;
	}
	
	/**
	 * Get attribute ID by name.
	 *
	 * @param string $attribute_name Attribute name.
	 * @return int Attribute ID.
	 */
	private function get_attribute_id( $attribute_name ) {
		global $wpdb;
		
		$attribute_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT attribute_id FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s",
			sanitize_title( $attribute_name )
		) );
		
		return $attribute_id ? (int) $attribute_id : 0;
	}
	
	/**
	 * Detect attributes from product names.
	 *
	 * @param array $products Array of WC_Product objects.
	 * @return array Attributes array.
	 */
	private function detect_attributes_from_names( $products ) {
		$attributes = array();
		$names = array();
		
		foreach ( $products as $product ) {
			$names[] = $product->get_name();
		}
		
		// Check for size variations.
		$size_pattern = '/\b(small|medium|large|xl|xxl|xxxl|xs|s|m|l)\b/i';
		$sizes = array();
		foreach ( $names as $name ) {
			if ( preg_match( $size_pattern, $name, $matches ) ) {
				$sizes[] = ucfirst( strtolower( $matches[1] ) );
			}
		}
		$sizes = array_unique( $sizes );
		if ( count( $sizes ) > 1 ) {
			$attribute = new WC_Product_Attribute();
			$attribute->set_id( 0 );
			$attribute->set_name( 'Size' );
			$attribute->set_options( $sizes );
			$attribute->set_visible( true );
			$attribute->set_variation( true );
			$attributes['size'] = $attribute;
		}
		
		// Check for color variations.
		$color_pattern = '/\b(red|blue|green|yellow|black|white|gray|grey|brown|pink|purple|orange)\b/i';
		$colors = array();
		foreach ( $names as $name ) {
			if ( preg_match( $color_pattern, $name, $matches ) ) {
				$colors[] = ucfirst( strtolower( $matches[1] ) );
			}
		}
		$colors = array_unique( $colors );
		if ( count( $colors ) > 1 ) {
			$attribute = new WC_Product_Attribute();
			$attribute->set_id( 0 );
			$attribute->set_name( 'Color' );
			$attribute->set_options( $colors );
			$attribute->set_visible( true );
			$attribute->set_variation( true );
			$attributes['color'] = $attribute;
		}
		
		return $attributes;
	}
	
	/**
	 * Create a variation from a simple product.
	 *
	 * @param int         $variable_product_id Variable product ID.
	 * @param WC_Product  $simple_product Simple product.
	 * @param array       $attributes Variable product attributes.
	 * @return int|WP_Error Variation ID or error.
	 */
	private function create_variation( $variable_product_id, $simple_product, $attributes ) {
		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $variable_product_id );
		$variation->set_status( 'publish' );
		$variation->set_sku( $simple_product->get_sku() );
		$variation->set_price( $simple_product->get_price() );
		$variation->set_regular_price( $simple_product->get_regular_price() );
		$variation->set_sale_price( $simple_product->get_sale_price() );
		$variation->set_stock_status( $simple_product->get_stock_status() );
		$variation->set_manage_stock( $simple_product->get_manage_stock() );
		$variation->set_stock_quantity( $simple_product->get_stock_quantity() );
		$variation->set_weight( $simple_product->get_weight() );
		$variation->set_length( $simple_product->get_length() );
		$variation->set_width( $simple_product->get_width() );
		$variation->set_height( $simple_product->get_height() );
		
		// Set variation attributes.
		$variation_attributes = array();
		foreach ( $attributes as $attribute_name => $attribute ) {
			$value = $this->get_attribute_value_from_product( $simple_product, $attribute_name, $attribute );
			if ( $value ) {
				$variation_attributes[ $attribute_name ] = $value;
			}
		}
		$variation->set_attributes( $variation_attributes );
		
		// Set image.
		$image_id = $simple_product->get_image_id();
		if ( $image_id ) {
			$variation->set_image_id( $image_id );
		}
		
		$variation_id = $variation->save();
		
		return $variation_id;
	}
	
	/**
	 * Get attribute value from product.
	 *
	 * @param WC_Product           $product Product object.
	 * @param string               $attribute_name Attribute name.
	 * @param WC_Product_Attribute $attribute Attribute object.
	 * @return string|null Attribute value.
	 */
	private function get_attribute_value_from_product( $product, $attribute_name, $attribute ) {
		// Try to get from product attributes.
		$product_attributes = $product->get_attributes();
		if ( isset( $product_attributes[ $attribute_name ] ) ) {
			$attr = $product_attributes[ $attribute_name ];
			if ( $attr->is_taxonomy() ) {
				$terms = $attr->get_options();
				return ! empty( $terms ) ? $terms[0] : null;
			} else {
				$options = $attr->get_options();
				return ! empty( $options ) ? $options[0] : null;
			}
		}
		
		// Try to extract from product name.
		$product_name = $product->get_name();
		$attribute_options = $attribute->get_options();
		
		foreach ( $attribute_options as $option ) {
			if ( stripos( $product_name, $option ) !== false ) {
				return $option;
			}
		}
		
		return null;
	}
}
