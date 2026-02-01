<?php
/**
 * Similarity Detector Class
 *
 * @package WooCommerce_Product_Merger
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WCPM_Similarity_Detector
 */
class WCPM_Similarity_Detector {
	
	/**
	 * Get product recommendations based on similarity.
	 *
	 * @return array Array of recommendation groups.
	 */
	public function get_recommendations() {
		 = $this->get_simple_products();
		 = array();
		 = array();
		
		foreach ( $products as $product_id => $product_data ) {
			if ( in_array( $product_id, $processed, true ) ) {
				continue;
			}
			
			$similar_products = $this->find_similar_products( $product_id, $product_data, $products );
			
			if ( ! empty( $similar_products ) ) {
				$group = array(
					'products' => array_merge( array( $product_id => $product_data ), $similar_products ),
					'similarity_score' => $this->calculate_group_similarity( array_merge( array( $product_id => $product_data ), $similar_products ) ),
					'suggested_attributes' => $this->suggest_attributes( array_merge( array( $product_id => $product_data ), $similar_products ) ),
				);
				
				$groups[] = $group;
				
				// Mark all products in this group as processed.
				$processed = array_merge( $processed, array_keys( $group['products'] ) );
			}
		}
		
		// Sort by similarity score (highest first).
		usort( $groups, function( $a, $b ) {
			return $b['similarity_score'] <=> $a['similarity_score'];
		} );
		
		return $groups;
	}
