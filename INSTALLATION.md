# WooCommerce Product Merger - Installation Guide

## Requirements

- WordPress 5.0 or higher
- WooCommerce 5.0 or higher
- PHP 7.2 or higher

## Installation

1. **Upload the plugin:**
   - Upload the `woocommerce-product-merger` folder to `/wp-content/plugins/`
   - Or install via WordPress admin: Plugins > Add New > Upload Plugin

2. **Activate the plugin:**
   - Go to Plugins in WordPress admin
   - Find "WooCommerce Product Merger" and click "Activate"

3. **Access the plugin:**
   - Navigate to WooCommerce > Product Merger in your WordPress admin

## Usage

### Step 1: Load Recommendations
- Click the "Load Recommendations" button
- The plugin will scan all your simple products and find similar ones
- Similarity is calculated based on:
  - Product names (30% weight)
  - Description similarity (30% weight)
  - SKU similarity (15% weight)
  - Category matching (15% weight)
  - Price similarity (10% weight)

### Step 2: Review Recommendations
- Each recommendation group shows:
  - Similarity score (percentage match)
  - Suggested attributes for the variable product
  - List of similar products with details

### Step 3: Select Products to Merge
- Check the boxes next to products you want to merge
- You must select at least 2 products
- The "Merge Selected Products" button will appear when 2+ products are selected

### Step 4: Merge Products
- Click "Merge Selected Products"
- Confirm the merge operation
- The plugin will:
  - Create a new variable product with a common name
  - Detect and set appropriate attributes
  - Create variations from the original products
  - Delete the original simple products

## Important Notes

⚠️ **Backup First!** The merge operation deletes the original products. Always backup your site before using this plugin.

⚠️ **Cannot Undo** The merge operation cannot be undone. Make sure you've selected the correct products.

⚠️ **Test First** Consider testing with a few products first before merging large batches.

## How Similarity Works

The plugin uses a 60% similarity threshold. Products must meet this threshold to be recommended. The similarity calculation considers:

1. **Name Similarity (30%)**: Compares product names, extracting base names and ignoring common variations
2. **Description Similarity (30%)**: Compares product descriptions using word-based analysis
3. **SKU Similarity (15%)**: Compares SKU codes if both products have them
4. **Category Similarity (15%)**: Checks if products share categories
5. **Price Similarity (10%)**: Compares product prices based on percentage difference

## Troubleshooting

### No Recommendations Found
- Your products may not meet the 60% similarity threshold
- Try adjusting product names or attributes to be more similar
- Check that products are in the same categories

### Merge Fails
- Ensure you have sufficient permissions (manage_woocommerce capability)
- Check that products are not in use (orders, carts, etc.)
- Verify WooCommerce is properly configured

### Attributes Not Detected
- The plugin tries to detect attributes from product attributes first
- If none found, it attempts to extract from product names (size, color patterns)
- You may need to manually set attributes after merging

## Support

For issues or questions, please check:
- Plugin documentation
- WooCommerce documentation
- WordPress support forums
