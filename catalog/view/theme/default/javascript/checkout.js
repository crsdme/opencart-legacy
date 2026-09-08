(function () {
	var root = document.getElementById('checkout');

	if (!root) {
		return;
	}

	var cartBox = document.getElementById('checkout-cart');
	var summaryBox = document.getElementById('checkout-summary');
	var shippingBox = document.getElementById('checkout-shipping-type');
	var extraBox = document.getElementById('checkout-shipping-extra');
	var addressBox = document.getElementById('checkout-shipping-address');
	var paymentBox = document.getElementById('checkout-payment-type');
	var extraMethod = '';
	var pending = {};
	var timer;
	var addressTimer;

	function debounce(fn, wait) {
		return function () {
			var args = arguments;
			clearTimeout(timer);
			timer = setTimeout(function () {
				fn.apply(null, args);
			}, wait);
		};
	}

	function boxes() {
		return $(cartBox).add(summaryBox).add(shippingBox).add(paymentBox);
	}

	function headerOffset() {
		var header = document.querySelector('.header');

		return (header ? header.getBoundingClientRect().height : 0) + 16;
	}

	function syncStickyOffset() {
		document.documentElement.style.setProperty('--header-offset', headerOffset() + 'px');
	}

	function setSectionHidden(el, hidden) {
		if (!el) {
			return;
		}

		el.hidden = hidden;

		var fieldset = el.querySelector('fieldset');

		if (fieldset) {
			fieldset.disabled = hidden;
		}

		el.querySelectorAll('input, select, textarea').forEach(function (input) {
			input.disabled = hidden;
		});
	}

	function selectedHidesAddress() {
		var selected = $('[data-checkout-shipping]:checked', root);

		if (!selected.length) {
			return false;
		}

		return selected.attr('data-hide-address') === '1' || !!(selected.attr('data-shipping-extra') || '');
	}

	function syncShippingAddress(show) {
		if (!addressBox) {
			return;
		}

		if (typeof show === 'undefined') {
			show = !selectedHidesAddress();
		} else if (show && selectedHidesAddress()) {
			show = false;
		}

		setSectionHidden(addressBox, !show);
	}

	function setExtraVisible(show) {
		if (!extraBox) {
			return;
		}

		extraBox.hidden = !show;
		extraBox.querySelectorAll('input, select, textarea').forEach(function (input) {
			input.disabled = !show;
		});
	}

	function loadShippingExtra() {
		if (!extraBox) {
			return;
		}

		var selected = $('[data-checkout-shipping]:checked', root);
		var code = selected.val() || '';
		var url = selected.attr('data-shipping-extra') || '';
		var hasWidget = !!extraBox.querySelector('[data-carrier-extra]');

		if (!url) {
			extraMethod = code;
			extraBox.innerHTML = '';
			setExtraVisible(false);
			return;
		}

		if (code === extraMethod && hasWidget) {
			setExtraVisible(true);
			return;
		}

		extraMethod = code;
		setExtraVisible(true);

		$.get(url, function (html) {
			if (($('[data-checkout-shipping]:checked', root).val() || '') !== code) {
				return;
			}

			extraBox.innerHTML = html;
			setExtraVisible(true);
		});
	}

	function closeSuggestLists(except) {
		root.querySelectorAll('.checkout-suggest-list').forEach(function (list) {
			if (except && list === except) {
				return;
			}

			list.hidden = true;
			list.innerHTML = '';
		});
	}

	function renderSuggest(input, items) {
		var list = input.parentNode.querySelector('.checkout-suggest-list');

		if (!list) {
			return;
		}

		list.innerHTML = '';

		if (!items || !items.length) {
			list.hidden = true;
			return;
		}

		items.forEach(function (item) {
			var button = document.createElement('button');
			button.type = 'button';
			button.className = 'checkout-suggest-item';
			button.textContent = item.label || item.value;
			button.setAttribute('data-value', item.value || '');
			button.setAttribute('data-id', item.id || '');
			list.appendChild(button);
		});

		list.hidden = false;
	}

	function fetchSuggest(input) {
		var extra = input.closest('[data-carrier-extra]');

		if (!extra) {
			return;
		}

		var action = input.getAttribute('data-carrier-search');
		var url = extra.getAttribute('data-search');
		var city = extra.querySelector('[data-carrier-search="cities"]');
		var ref = extra.querySelector('[data-carrier-ref]');

		$.getJSON(url, {
			action: action,
			q: input.value || '',
			city: city ? city.value : '',
			city_ref: ref ? ref.value : '',
			type: extra.getAttribute('data-type') || '',
		}, function (items) {
			renderSuggest(input, items);
		});
	}

	function apply(json) {
		if (!json) {
			return;
		}

		if (json.empty) {
			window.location.reload();
			return;
		}

		if (typeof json.total !== 'undefined') {
			$('#cart-badge').text(json.total);
		}

		if (json.html) {
			$(cartBox).html(json.html);
		}

		if (json.summary) {
			$(summaryBox).html(json.summary);
		}

		if (shippingBox) {
			if (typeof json.shipping_required !== 'undefined') {
				setSectionHidden(shippingBox, !json.shipping_required);
			}

			if (json.shipping) {
				$(shippingBox).html(json.shipping);
				setSectionHidden(shippingBox, shippingBox.hidden);
			}
		}

		if (json.payment && paymentBox) {
			$(paymentBox).html(json.payment);
		}

		syncShippingAddress(json.show_shipping_address);
		loadShippingExtra();
	}

	function request(url, data) {
		boxes().addClass('is-loading');

		$.ajax({
			url: url,
			type: 'post',
			data: data,
			dataType: 'json',
			cache: false,
			complete: function () {
				boxes().removeClass('is-loading');
			},
			success: apply,
		});
	}

	function sendQuantities() {
		var quantities = $.extend({}, pending);

		if (!Object.keys(quantities).length) {
			return;
		}

		pending = {};
		request('index.php?route=checkout/cart/edit', { quantity: quantities });
	}

	var sendDebounced = debounce(sendQuantities, 350);

	function queueQuantity(cartId, quantity) {
		pending[cartId] = quantity;
		sendDebounced();
	}

	function addressPayload() {
		var data = {};

		$('[data-checkout-address]', root).each(function () {
			if (this.disabled || !this.name) {
				return;
			}

			data[this.name] = this.value;
		});

		return data;
	}

	function saveAddress(quiet) {
		var extraOpen = extraBox && !extraBox.hidden;
		var addressOpen = addressBox && !addressBox.hidden;

		if (!extraOpen && !addressOpen) {
			return;
		}

		var data = addressPayload();

		if (quiet || (extraOpen && !addressOpen)) {
			data.quiet = 1;
			$.post('index.php?route=checkout/address/save', data);
			return;
		}

		request('index.php?route=checkout/address/save', data);
	}

	function queueAddress() {
		clearTimeout(addressTimer);
		addressTimer = setTimeout(saveAddress, 400);
	}

	function syncCheckoutPhone(input) {
		if (!input || typeof phoneDigitsFromInput !== 'function') {
			return;
		}

		$(input).closest('.form-item').find('input[name="telephone"]').val(phoneDigitsFromInput(input));
	}

	function initCheckoutPhone() {
		var input = root.querySelector('[data-phone-input], [data-checkout-phone]');

		if (!input) {
			return true;
		}

		if (typeof initPhoneInput === 'function') {
			return initPhoneInput(input, root.getAttribute('data-phone-prefix') || '380');
		}

		var itiLib = typeof getIntlTelInput === 'function' ? getIntlTelInput() : null;

		if (!itiLib) {
			return false;
		}

		if (itiLib.getInstance(input)) {
			syncCheckoutPhone(input);
			return true;
		}

		var prefix = root.getAttribute('data-phone-prefix') || '380';
		var locale = typeof phoneCountryLocale === 'function' ? phoneCountryLocale() : 'en';
		var iso = typeof phoneIsoFromDial === 'function' ? phoneIsoFromDial(prefix) : 'ua';
		var hidden = input.parentNode.querySelector('input[name="telephone"]');
		var iti = itiLib(input, {
			initialCountry: iso,
			countryNameLocale: locale,
			formatAsYouType: true,
			autoPlaceholder: 'aggressive',
			separateDialCode: true,
			strictMode: true,
			countrySearch: true,
			dropdownParent: document.body,
		});

		if (hidden && hidden.value) {
			iti.setNumber('+' + String(hidden.value).replace(/\D/g, ''));
		}

		input.addEventListener('input', function () {
			syncCheckoutPhone(input);
		});
		input.addEventListener('countrychange', function () {
			syncCheckoutPhone(input);
		});
		syncCheckoutPhone(input);

		return true;
	}

	function startPhone() {
		if (initCheckoutPhone()) {
			return;
		}

		var attempts = 0;
		var phoneTimer = setInterval(function () {
			attempts += 1;

			if (initCheckoutPhone() || attempts > 20) {
				clearInterval(phoneTimer);
			}
		}, 50);
	}

	function openAgreeModal(link) {
		if (typeof openModal !== 'function') {
			return;
		}

		$.ajax({
			url: link.href,
			type: 'get',
			dataType: 'html',
			success: function (html) {
				$('[data-checkout-agree-title]').text($(link).text());
				$('[data-checkout-agree-body]').html(html);
				openModal('#checkout-agree-modal');
			},
		});
	}

	function clearErrors() {
		root.querySelectorAll('.form-message, .checkout-field-error').forEach(function (el) {
			el.remove();
		});
		root.querySelectorAll('.is-error').forEach(function (el) {
			el.classList.remove('is-error');
		});
		root.querySelectorAll('[data-error]').forEach(function (el) {
			el.removeAttribute('data-error');
			el.removeAttribute('aria-invalid');
		});

		var box = document.getElementById('checkout-error');

		if (box) {
			box.hidden = true;
			box.innerHTML = '';
		}
	}

	function fieldControl(name) {
		if (name === 'telephone') {
			return root.querySelector('[data-phone-input], [data-checkout-phone]') || root.querySelector('[name="telephone"]');
		}

		if (name === 'agree') {
			return root.querySelector('[name="agree"]');
		}

		return root.querySelector('[name="' + name + '"]');
	}

	function markError(input, message) {
		if (!input) {
			return null;
		}

		var item =
			input.closest('.form-item') ||
			input.closest('.checkout-agree') ||
			input.closest('.checkout-methods') ||
			input.parentNode;
		item.classList.add('is-error');
		input.setAttribute('data-error', 'true');
		input.setAttribute('aria-invalid', 'true');

		var visual = input.hasAttribute('data-phone-input') || input.hasAttribute('data-checkout-phone')
			? input
			: item.querySelector('.input, [data-phone-input], [data-checkout-phone], .checkbox, .radio');

		if (visual && visual !== input) {
			visual.setAttribute('data-error', 'true');
			visual.setAttribute('aria-invalid', 'true');
		}

		var p = document.createElement('p');
		p.className = 'form-message';
		p.textContent = message;
		item.appendChild(p);

		return item;
	}

	function scrollToEl(el) {
		if (!el) {
			return;
		}

		var viewport = document.getElementById('viewport');
		var offset = headerOffset();

		if (!viewport) {
			el.scrollIntoView({ block: 'center' });
			return;
		}

		var top =
			el.getBoundingClientRect().top - viewport.getBoundingClientRect().top + viewport.scrollTop - offset - 12;

		viewport.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
	}

	function showAlert(message) {
		var box = document.getElementById('checkout-error');

		if (!box || !message) {
			return;
		}

		box.hidden = false;
		box.innerHTML = box.innerHTML ? box.innerHTML + '<br>' + message : message;
	}

	function showErrors(errors) {
		clearErrors();

		if (!errors) {
			return;
		}

		var first;

		Object.keys(errors).forEach(function (name) {
			var message = errors[name];

			if (name === 'warning' || name === 'shipping_method' || name === 'payment_method') {
				showAlert(message);

				var group = fieldControl(name);
				var block = group
					? group.closest('.checkout-methods') || group.closest('.checkout-block')
					: null;

				if (block) {
					block.classList.add('is-error');

					if (!first) {
						first = block;
					}
				}

				return;
			}

			var input = fieldControl(name);
			var item = markError(input, message);

			if (!input) {
				showAlert(message);
				return;
			}

			if (!first) {
				first = item || input;
			}
		});

		if (first) {
			scrollToEl(first);
		} else {
			scrollToEl(document.getElementById('checkout-error'));
		}
	}

	function parseJson(response) {
		if (!response) {
			return null;
		}

		if (typeof response === 'object') {
			return response;
		}

		try {
			return JSON.parse(response);
		} catch (e) {
			return null;
		}
	}

	function serializeCheckout() {
		var restore = [];

		function enable(el) {
			if (el && el.disabled) {
				restore.push(el);
				el.disabled = false;
			}
		}

		root.querySelectorAll('[data-checkout-shipping]:checked, [data-checkout-payment]:checked').forEach(enable);

		if (extraBox && !extraBox.hidden) {
			extraBox.querySelectorAll('input, select, textarea').forEach(enable);
		}

		var data = $(root).serialize();

		restore.forEach(function (el) {
			el.disabled = true;
		});

		return data;
	}

	function submitOrder() {
		clearErrors();
		root.classList.add('is-confirming');

		$.ajax({
			url: 'index.php?route=checkout/confirm/save',
			type: 'post',
			data: serializeCheckout(),
			dataType: 'text',
			cache: false,
			complete: function () {
				root.classList.remove('is-confirming');
			},
			success: function (raw) {
				var json = parseJson(raw);

				if (!json) {
					showAlert(root.getAttribute('data-error-confirm') || '');
					scrollToEl(document.getElementById('checkout-error'));
					return;
				}

				if (json.errors) {
					showErrors(json.errors);
					return;
				}

				if (json.redirect) {
					window.location = json.redirect;
					return;
				}

				if (json.payment) {
					var slot = document.getElementById('checkout-payment-form');

					if (slot) {
						slot.innerHTML = json.payment;
						scrollToEl(slot);
					}
				}
			},
			error: function () {
				showAlert(root.getAttribute('data-error-confirm') || '');
				scrollToEl(document.getElementById('checkout-error'));
			},
		});
	}

	$(root).on('click', '[data-checkout-remove]', function () {
		pending = {};
		request('index.php?route=checkout/cart/remove', { key: $(this).attr('data-checkout-remove') });
	});

	$(root).on('click', '[data-checkout-step]', function () {
		var $input = $(this).closest('.product-qty').find('[data-checkout-qty]');
		var min = parseInt($input.attr('min') || '1', 10);
		var value = parseInt($input.val(), 10) || min;
		var minus = $(this).data('checkout-step') === 'minus';
		var cartId = $input.attr('data-checkout-qty');

		if (minus && value <= 1) {
			pending = {};
			request('index.php?route=checkout/cart/remove', { key: cartId });
			return;
		}

		value += minus ? -1 : 1;
		$input.val(Math.max(min, value)).trigger('change');
	});

	$(root).on('keydown', '[data-checkout-qty], [data-checkout-coupon]', function (e) {
		if (e.key === 'Enter') {
			e.preventDefault();

			if ($(this).is('[data-checkout-coupon]')) {
				$('[data-checkout-coupon-apply]', root).trigger('click');
				return;
			}

			$(this).trigger('change');
		}
	});

	$(root).on('change', '[data-checkout-qty]', function () {
		var $input = $(this);
		var cartId = $input.attr('data-checkout-qty');
		var min = parseInt($input.attr('min') || '1', 10);
		var quantity = parseInt($input.val(), 10);

		if (quantity === 0) {
			pending = {};
			request('index.php?route=checkout/cart/remove', { key: cartId });
			return;
		}

		if (isNaN(quantity) || quantity < min) {
			quantity = min;
			$input.val(quantity);
		}

		queueQuantity(cartId, quantity);
	});

	$(root).on('click', '[data-checkout-coupon-apply]', function () {
		request('index.php?route=checkout/cart/coupon', {
			coupon: $('[data-checkout-coupon]', summaryBox).val() || '',
		});
	});

	$(root).on('change', '[data-checkout-shipping]', function () {
		syncShippingAddress();
		loadShippingExtra();
		request('index.php?route=checkout/shipping/save', {
			shipping_method: $(this).val(),
		});

		if (window.Ecommerce) {
			Ecommerce.event('add_shipping_info', {
				shipping_tier: $(this).val(),
			});
		}
	});

	$(root).on('change', '[data-checkout-payment]', function () {
		request('index.php?route=checkout/payment/save', {
			payment_method: $(this).val(),
		});

		if (window.Ecommerce) {
			Ecommerce.event('add_payment_info', {
				payment_type: $(this).val(),
			});
		}
	});

	$(root).on('input', '[data-checkout-address]', function () {
		if (this.hasAttribute('data-carrier-search')) {
			return;
		}

		if (this.closest('[data-carrier-extra]')) {
			clearTimeout(addressTimer);
			addressTimer = setTimeout(function () {
				saveAddress(true);
			}, 400);
			return;
		}

		queueAddress();
	});

	$(root).on('change', '[data-checkout-address]', function () {
		if (this.hasAttribute('data-carrier-search') || this.closest('[data-carrier-extra]')) {
			saveAddress(true);
			return;
		}

		saveAddress();
	});

	$(root).on('keydown', '[data-carrier-search]', function (e) {
		if (e.key === 'Enter') {
			e.preventDefault();
		}
	});

	$(root).on('click', 'a.agree', function (e) {
		e.preventDefault();
		openAgreeModal(this);
	});

	$(root).on('submit', function (e) {
		e.preventDefault();

		var phone = root.querySelector('[data-phone-input], [data-checkout-phone]');

		if (phone) {
			syncCheckoutPhone(phone);
		}

		submitOrder();
	});

	$(root).on('input focus', '[data-carrier-search]', function () {
		var input = this;
		clearTimeout(input._suggestTimer);
		input._suggestTimer = setTimeout(function () {
			fetchSuggest(input);
		}, 200);
	});

	$(root).on('click', '.checkout-suggest-item', function () {
		var item = this;
		var wrap = item.closest('.checkout-suggest');
		var extra = item.closest('[data-carrier-extra]');
		var input = wrap ? wrap.querySelector('[data-carrier-search], .input') : null;

		if (!input) {
			return;
		}

		input.value = item.getAttribute('data-value') || item.textContent;

		if (input.getAttribute('data-carrier-search') === 'cities') {
			var ref = extra ? extra.querySelector('[data-carrier-ref]') : null;

			if (ref) {
				ref.value = item.getAttribute('data-id') || '';
			}

			$(extra)
				.find('[data-carrier-search="warehouses"], [data-carrier-search="streets"]')
				.val('');
		}

		closeSuggestLists();
		saveAddress(true);
	});

	$(document).on('click', function (e) {
		if (!e.target.closest('.checkout-suggest')) {
			closeSuggestLists();
		}
	});

	syncStickyOffset();
	syncShippingAddress();
	loadShippingExtra();

	if (shippingBox) {
		setSectionHidden(shippingBox, shippingBox.hidden);
	}

	startPhone();

	if (typeof ResizeObserver === 'function') {
		var header = document.querySelector('.header');
		var observer = new ResizeObserver(syncStickyOffset);

		if (header) {
			observer.observe(header);
		}
	}

	window.addEventListener('resize', syncStickyOffset);
})();
