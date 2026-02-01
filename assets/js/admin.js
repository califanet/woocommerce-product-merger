(function($) {
	'use strict';

	var WCPM = {
		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			$(document).on('click', '#wcpm-load-recommendations', this.loadRecommendations);
			$(document).on('click', '#wcpm-merge-selected', this.mergeSelected);
			$(document).on('change', '.wcpm-product-checkbox', this.updateMergeButton);
		},

		loadRecommendations: function(e) {
			e.preventDefault();

			var $button = $(this);
			var $loading = $('#wcpm-loading');
			var $recommendations = $('#wcpm-recommendations');
			var $messages = $('#wcpm-messages');

			$button.prop('disabled', true);
			$loading.show();
			$recommendations.empty();
			$messages.empty();

			$.ajax({
				url: wcpmData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wcpm_get_recommendations',
					nonce: wcpmData.nonce
				},
				success: function(response) {
					$loading.hide();
					$button.prop('disabled', false);

					if (response.success && response.data.recommendations) {
						WCPM.renderRecommendations(response.data.recommendations);
					} else {
						WCPM.showMessage('error', response.data && response.data.message ? response.data.message : wcpmData.i18n.noRecommendations);
						WCPM.showNoRecommendations();
					}
				},
				error: function() {
					$loading.hide();
					$button.prop('disabled', false);
					WCPM.showMessage('error', wcpmData.i18n.noRecommendations);
					WCPM.showNoRecommendations();
				}
			});
		},

		renderRecommendations: function(recommendations) {
			var $container = $('#wcpm-recommendations');
			$container.empty();

			if (!recommendations || recommendations.length === 0) {
				WCPM.showNoRecommendations();
				return;
			}

			recommendations.forEach(function(group, index) {
				var $group = WCPM.renderGroup(group, index);
				$container.append($group);
			});
		},

		renderGroup: function(group, index) {
			var $group = $('<div>').addClass('wcpm-recommendation-group');
			var $header = $('<div>').addClass('wcpm-recommendation-group-header');
			
			var score = Math.round(group.similarity_score * 100);
			var scoreClass = score >= 80 ? 'high' : score >= 60 ? 'medium' : '';
			var $score = $('<span>')
				.addClass('wcpm-similarity-score ' + scoreClass)
				.text(score + '% Match');

			var $title = $('<h3>')
				.addClass('wcpm-recommendation-group-title')
				.text('Group ' + (index + 1) + ' - ' + group.products.length + ' products');

			$header.append($title).append($score);
			$group.append($header);

			// Suggested attributes
			if (group.suggested_attributes && Object.keys(group.suggested_attributes).length > 0) {
				var $attributes = $('<div>').addClass('wcpm-suggested-attributes');
				var $attrTitle = $('<div>').addClass('wcpm-suggested-attributes-title')
					.text('Suggested Attributes:');
				var $attrList = $('<ul>').addClass('wcpm-suggested-attributes-list');

				$.each(group.suggested_attributes, function(name, attr) {
					var $li = $('<li>');
					var attrName = attr.name || name;
					var attrValues = Array.isArray(attr.values) ? attr.values.join(', ') : attr.values;
					$li.html('<strong>' + attrName + ':</strong> ' + attrValues);
					$attrList.append($li);
				});

				$attributes.append($attrTitle).append($attrList);
				$group.append($attributes);
			}

			// Products list
			var $productsList = $('<div>').addClass('wcpm-products-list');
			
			$.each(group.products, function(productId, product) {
				var $productItem = WCPM.renderProduct(productId, product);
				$productsList.append($productItem);
			});

			$group.append($productsList);
			return $group;
		},

		renderProduct: function(productId, product) {
			var $item = $('<div>').addClass('wcpm-product-item');
			var $checkbox = $('<input>')
				.attr('type', 'checkbox')
				.attr('id', 'wcpm-product-' + productId)
				.attr('value', productId)
				.addClass('wcpm-product-checkbox')
				.data('product-id', productId);

			var $content = $('<div>').addClass('wcpm-product-item-content');
			var $name = $('<div>').addClass('wcpm-product-item-name');
			var editUrl = wcpmData.ajaxUrl.replace('admin-ajax.php', 'post.php?post=' + productId + '&action=edit');
			var $link = $('<a>')
				.attr('href', editUrl)
				.attr('target', '_blank')
				.text(product.name || 'Product #' + productId);

			$name.append($link);
			$content.append($name);

			var $meta = $('<div>').addClass('wcpm-product-item-meta');
			if (product.sku) {
				$meta.append($('<span>').html('<strong>SKU:</strong> ' + product.sku));
			}
			if (product.price) {
				$meta.append($('<span>').html('<strong>Price:</strong> ' + WCPM.formatPrice(product.price)));
			}

			$content.append($meta);
			$item.append($checkbox).append($content);

			$checkbox.on('change', function() {
				if ($(this).is(':checked')) {
					$item.addClass('selected');
				} else {
					$item.removeClass('selected');
				}
			});

			return $item;
		},

		formatPrice: function(price) {
			if (!price) return '';
			// Simple price formatting - you might want to use WooCommerce's formatting
			return '$' + parseFloat(price).toFixed(2);
		},

		showNoRecommendations: function() {
			var $container = $('#wcpm-recommendations');
			var $noResults = $('<div>').addClass('wcpm-no-recommendations');
			$noResults.append($('<div>').addClass('wcpm-no-recommendations-icon').text('📦'));
			$noResults.append($('<div>').addClass('wcpm-no-recommendations-title')
				.text('No Recommendations Found'));
			$noResults.append($('<div>').addClass('wcpm-no-recommendations-text')
				.text('No similar products were found that meet the similarity criteria.'));
			$container.append($noResults);
		},

		updateMergeButton: function() {
			var $checked = $('.wcpm-product-checkbox:checked');
			var $button = $('#wcpm-merge-selected');
			
			if ($checked.length >= 2) {
				$button.show().text('Merge Selected Products (' + $checked.length + ')');
			} else {
				$button.hide();
			}
		},

		mergeSelected: function(e) {
			e.preventDefault();

			if (!confirm(wcpmData.i18n.confirmMerge)) {
				return;
			}

			var $checked = $('.wcpm-product-checkbox:checked');
			var productIds = [];

			$checked.each(function() {
				productIds.push($(this).val());
			});

			if (productIds.length < 2) {
				WCPM.showMessage('error', 'Please select at least 2 products to merge.');
				return;
			}

			var $button = $(this);
			var $loading = $('#wcpm-loading');
			var $messages = $('#wcpm-messages');

			$button.prop('disabled', true);
			$loading.show();
			$messages.empty();

			$.ajax({
				url: wcpmData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wcpm_merge_products',
					nonce: wcpmData.nonce,
					product_ids: productIds
				},
				success: function(response) {
					$loading.hide();
					$button.prop('disabled', false);

					if (response.success) {
						WCPM.showMessage('success', response.data.message);
						
						// Reload recommendations after a short delay
						setTimeout(function() {
							$('#wcpm-load-recommendations').click();
						}, 2000);
					} else {
						WCPM.showMessage('error', response.data && response.data.message ? response.data.message : wcpmData.i18n.mergeError);
					}
				},
				error: function() {
					$loading.hide();
					$button.prop('disabled', false);
					WCPM.showMessage('error', wcpmData.i18n.mergeError);
				}
			});
		},

		showMessage: function(type, message) {
			var $messages = $('#wcpm-messages');
			var $message = $('<div>')
				.addClass('wcpm-message ' + type)
				.text(message);
			
			$messages.append($message);

			// Auto-hide success messages after 5 seconds
			if (type === 'success') {
				setTimeout(function() {
					$message.fadeOut(function() {
						$(this).remove();
					});
				}, 5000);
			}
		}
	};

	$(document).ready(function() {
		WCPM.init();
	});

})(jQuery);
