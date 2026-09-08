// Catalog actions — cart, wishlist, compare. Loaded on every page.
(function ($) {
	let cartRequest = null;
	let pendingCartQuantities = {};

	function startCartRequest(options) {
		if (cartRequest) {
			cartRequest.abort();
		}

		const request = $.ajax(options);

		cartRequest = request;

		request.always(function () {
			if (cartRequest === request) {
				cartRequest = null;
			}
		});

		return request;
	}

	function renderCartSkeleton() {
		$('#cart-modal-products').html(
			'<div class="space-y-2">' +
				'<div class="skeleton h-25 w-full rounded-md"></div>' +
				'<div class="skeleton h-25 w-full rounded-md"></div>' +
				'<div class="skeleton h-25 w-full rounded-md"></div>' +
				'<div class="skeleton h-25 w-full rounded-md"></div>' +
				'</div>',
		);

		$('#cart-modal-totals').html(
			'<div class="space-y-2">' + '<div class="skeleton h-25 w-full rounded-md"></div>' + '</div>',
		);
	}

	function getHtmlPart(html, selector) {
		const response = $('<div>').append($.parseHTML(html, document, true));
		const element = response.find(selector);

		return element.length ? element.first().html() : '';
	}

	function trackEcommerce(name, payload) {
		if (!payload || !window.Ecommerce || typeof window.Ecommerce.event !== 'function') {
			return;
		}

		Ecommerce.event(name, payload);
	}

	function ecommerceFromHtml(html) {
		const raw = $('<div>').append($.parseHTML(html, document, true)).find('#cart-ecommerce').text();

		if (!raw) {
			return null;
		}

		try {
			return JSON.parse(raw);
		} catch (e) {
			return null;
		}
	}

	function updateCartModal(html) {
		$('#cart-modal-products').html(getHtmlPart(html, '#cart-modal-products'));
		$('#cart-modal-totals').html(getHtmlPart(html, '#cart-modal-totals'));
	}

	function handleCartModalError(xhr, status) {
		if (status === 'abort') return;

		$('#cart-modal-products').html('<p>Не удалось обновить корзину</p>');
		$('#cart-modal-totals').empty();
	}

	function applyCartJson(json) {
		$('#cart-badge').text(json.total);

		if (json.html) {
			updateCartModal(json.html);
		}
	}

	function loadCartModal() {
		pendingCartQuantities = {};

		startCartRequest({
			url: 'index.php?route=common/cart/info',
			type: 'get',
			dataType: 'html',
			cache: false,
			beforeSend: renderCartSkeleton,
			success: function (html) {
				updateCartModal(html);
				trackEcommerce('view_cart', ecommerceFromHtml(html));
			},
			error: function (xhr, status) {
				if (status === 'abort') return;

				$('#cart-modal-products').html('<p>Не удалось загрузить корзину</p>');
				$('#cart-modal-totals').empty();
			},
		});
	}

	function handleCartResponse(json, button) {
		if (json.error) {
			if (button) {
				button.removeAttribute('disabled');
			}

			sendToast({
				title: typeof json.error === 'string' ? json.error : 'Could not add to cart',
				type: 'error',
				align: 'right-bottom',
				timeout: 4000,
			});

			return;
		}

		sendToast({
			title: 'Product added to cart',
			description: 'The cart has been updated',
			align: 'right-bottom',
			timeout: 4000,
		});

		$('#cart-badge').text(json.total);
		trackEcommerce('add_to_cart', json.ecommerce);
	}

	function addToCart(product_id, quantity, button) {
		quantity = quantity === undefined ? 1 : quantity;
		button = button || null;

		$.ajax({
			url: 'index.php?route=common/cart/add',
			type: 'post',
			data: {
				product_id: product_id,
				quantity: quantity,
			},
			dataType: 'json',
			cache: false,
			success: function (json) {
				handleCartResponse(json, button);
			},
		});
	}

	function addToCartFromForm(formSelector, button) {
		const form = $(formSelector);

		if (!form.length) return;

		$.ajax({
			url: 'index.php?route=common/cart/add',
			type: 'post',
			data: form.serialize(),
			dataType: 'json',
			cache: false,
			success: function (json) {
				handleCartResponse(json, button || null);
			},
		});
	}

	function removeCartProduct(productKey) {
		pendingCartQuantities = {};

		startCartRequest({
			url: 'index.php?route=common/cart/remove',
			type: 'post',
			data: {
				key: productKey,
			},
			dataType: 'json',
			cache: false,
			beforeSend: renderCartSkeleton,
			success: function (json) {
				applyCartJson(json);
				trackEcommerce('remove_from_cart', json.ecommerce);
			},
			error: handleCartModalError,
		});
	}

	function sendCartQuantityUpdate() {
		const quantities = $.extend({}, pendingCartQuantities);

		if (!Object.keys(quantities).length) return;

		startCartRequest({
			url: 'index.php?route=common/cart/edit',
			type: 'post',
			data: {
				quantity: quantities,
			},
			dataType: 'json',
			cache: false,
			success: function (json) {
				Object.keys(quantities).forEach(function (key) {
					if (pendingCartQuantities[key] === quantities[key]) {
						delete pendingCartQuantities[key];
					}
				});

				applyCartJson(json);
			},
			error: handleCartModalError,
		});
	}

	function addToWishlist(product_id, button) {
		button = button || null;

		$.ajax({
			url: 'index.php?route=account/wishlist/add',
			type: 'post',
			data: {
				product_id: product_id,
			},
			dataType: 'json',
			cache: false,
			success: function (json) {
				if (json.error) {
					sendToast({
						title: typeof json.error === 'string' ? json.error : json.title || 'Could not add to wishlist',
						type: 'error',
						align: 'right-bottom',
						timeout: 4000,
					});

					return;
				}

				if (button) {
					button.setAttribute('data-active', 'true');
				}

				sendToast({
					title: json.title,
					actionText: json.action_text || '',
					onAction: json.href
						? function () {
								location = json.href;
							}
						: null,
					align: 'right-bottom',
					timeout: 4000,
				});
			},
		});
	}

	function addToCompare(product_id, button) {
		button = button || null;

		$.ajax({
			url: 'index.php?route=product/compare/add',
			type: 'post',
			data: {
				product_id: product_id,
			},
			dataType: 'json',
			cache: false,
			success: function (json) {
				if (json.error) {
					sendToast({
						title: typeof json.error === 'string' ? json.error : json.title || 'Could not add to comparison',
						type: 'error',
						align: 'right-bottom',
						timeout: 4000,
					});

					return;
				}

				if (button) {
					button.setAttribute('data-active', 'true');
				}

				sendToast({
					title: json.title,
					actionText: json.action_text || '',
					onAction: json.href
						? function () {
								location = json.href;
							}
						: null,
					align: 'right-bottom',
					timeout: 4000,
				});
			},
		});
	}

	window.addToCart = addToCart;
	window.addToCartFromForm = addToCartFromForm;
	window.removeCartProduct = removeCartProduct;
	window.addToWishlist = addToWishlist;
	window.addToCompare = addToCompare;
	window.wishlist = {
		add: addToWishlist,
	};
	window.compare = {
		add: addToCompare,
	};

	$(document).on('modal:open', '#cart-modal', function () {
		loadCartModal();
	});

	const debouncedSendCartQuantityUpdate = debounce(sendCartQuantityUpdate, 350);

	$(document).on('change', '[data-cart-qty]', function () {
		const $input = $(this);
		const cartId = $input.attr('data-cart-qty');
		const min = parseInt($input.attr('min') || '1', 10);
		let quantity = parseInt($input.val(), 10);

		if (quantity === 0) {
			removeCartProduct(cartId);
			return;
		}

		if (isNaN(quantity) || quantity < min) {
			quantity = min;
			$input.val(quantity);
		}

		pendingCartQuantities[cartId] = quantity;
		debouncedSendCartQuantityUpdate();
	});

	$(document).on('click', '[data-qty]', function () {
		const input = $(this).closest('.product-qty').find('input').first();

		if (!input.length) return;

		const min = parseInt(input.attr('min') || '1', 10);
		let value = parseInt(input.val(), 10) || min;
		const isMinus = $(this).data('qty') === 'minus';
		const cartId = input.attr('data-cart-qty');

		if (isMinus && cartId && value <= 1) {
			removeCartProduct(cartId);
			return;
		}

		value += isMinus ? -1 : 1;
		input.val(Math.max(min, value)).trigger('change');
	});
})(jQuery);
