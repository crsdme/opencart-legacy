// Theme

const icon = $('[data-theme-icon]');

function toggleTheme() {
	const isDark = $('html').toggleClass('dark').hasClass('dark');
	icon.attr('href', isDark ? '/assets/icons/sprite.svg#icon-moon' : '/assets/icons/sprite.svg#icon-sun');
	document.cookie = 'theme=' + (isDark ? 'dark' : '') + '; path=/; max-age=31536000; samesite=Lax';
}

// Theme

// Dropdown

function isTouchDevice() {
	return window.matchMedia('(hover: none), (pointer: coarse)').matches;
}

let dropdownCloseTimer = null;

function getDropdownMenu($dropdown) {
	return $dropdown.find('[data-dropdown-menu]').first();
}

function isDropdownOpen($menu) {
	return $menu.attr('data-state') === 'open';
}

function openDropdown($dropdown) {
	const $menu = getDropdownMenu($dropdown);

	if (!$menu.length) return;

	clearTimeout(dropdownCloseTimer);

	$('[data-dropdown-menu]').not($menu).removeAttr('data-state');
	$menu.attr('data-state', 'open');
}

function closeDropdown($dropdown) {
	const $menu = getDropdownMenu($dropdown);

	if (!$menu.length) return;

	dropdownCloseTimer = setTimeout(function () {
		$menu.removeAttr('data-state');
	}, 150);
}

function closeAllDropdowns() {
	$('[data-dropdown-menu]').removeAttr('data-state');
}

function toggleDropdown($dropdown) {
	const $menu = getDropdownMenu($dropdown);

	if (!$menu.length) return;

	$('[data-dropdown-menu]').not($menu).removeAttr('data-state');

	if (isDropdownOpen($menu)) {
		$menu.removeAttr('data-state');
	} else {
		$menu.attr('data-state', 'open');
	}
}

$(document).on('click', '[data-dropdown-button]', function (e) {
	const $dropdown = $(this).closest('[data-dropdown]');
	const type = $dropdown.data('type');

	if (!$dropdown.length) return;

	if (type === 'hover' && !isTouchDevice()) {
		return;
	}

	e.stopPropagation();

	toggleDropdown($dropdown);
});

$(document).on('mouseenter', '[data-dropdown][data-type="hover"]', function () {
	if (isTouchDevice()) return;

	openDropdown($(this));
});

$(document).on('mouseleave', '[data-dropdown][data-type="hover"]', function () {
	if (isTouchDevice()) return;

	closeDropdown($(this));
});

$(document).on('mouseenter', '[data-dropdown-menu]', function () {
	clearTimeout(dropdownCloseTimer);
});

$(document).on('click', function () {
	closeAllDropdowns();
});

$(document).on('click', '[data-dropdown-menu]', function (e) {
	e.stopPropagation();
});

$(document).on('keydown', function (e) {
	if (e.key === 'Escape') {
		closeAllDropdowns();
	}
});

// Dropdown

// Utils

function debounce(func, wait) {
	let timeout;
	return function () {
		const context = this,
			args = arguments;
		clearTimeout(timeout);
		timeout = setTimeout(() => func.apply(context, args), wait);
	};
}

// Utils

// Cart

let cartRequest = null;

function renderCartSkeleton() {
	$('#cart-modal-products').html(`
		<div class="space-y-2">
			<div class="skeleton h-25 w-full rounded-md"></div>
			<div class="skeleton h-25 w-full rounded-md"></div>
			<div class="skeleton h-25 w-full rounded-md"></div>
			<div class="skeleton h-25 w-full rounded-md"></div>
		</div>
	`);

	$('#cart-modal-totals').html(`
		<div class="space-y-2">
			<div class="skeleton h-25 w-full rounded-md"></div>
		</div>
	`);
}

function getHtmlPart(html, selector) {
	const response = $('<div>').append($.parseHTML(html, document, true));
	const element = response.find(selector);

	return element.length ? element.first().html() : '';
}

function updateCartModal(html) {
	const productsHtml = getHtmlPart(html, '#cart-modal-products');
	const totalsHtml = getHtmlPart(html, '#cart-modal-totals');

	if (productsHtml) {
		$('#cart-modal-products').html(productsHtml);
	}

	if (totalsHtml) {
		$('#cart-modal-totals').html(totalsHtml);
	}
}

function loadCartModal() {
	if (cartRequest) {
		cartRequest.abort();
	}

	cartRequest = $.ajax({
		url: 'index.php?route=common/cart/info',
		type: 'get',
		dataType: 'html',
		cache: false,
		beforeSend: renderCartSkeleton,
		success: function (html) {
			updateCartModal(html);
		},
		error: function (xhr, status) {
			if (status === 'abort') return;

			$('#cart-modal-products').html('<p>Не удалось загрузить корзину</p>');
			$('#cart-modal-totals').empty();
		},
		complete: function () {
			cartRequest = null;
		},
	});
}

$(document).on('modal:open', '#cart-modal', function () {
	loadCartModal();
});

function addToCart(product_id, quantity = 1, button = null) {
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
			if (json['error']) {
				if (button) {
					button.removeAttribute('disabled');
				}

				return;
			}

			sendToast({
				title: 'Product added to cart',
				description: 'The cart has been updated',
				align: 'right-bottom',
				timeout: 4000,
			});

			$('#cart-badge').text(json['total']);
		},
	});
}

function removeCartProduct(productKey) {
	$.ajax({
		url: 'index.php?route=common/cart/remove',
		type: 'post',
		data: {
			key: productKey,
		},
		dataType: 'json',
		cache: false,
		beforeSend: renderCartSkeleton,
		success: function (json) {
			$('#cart-badge').text(json['total']);

			if (json['html']) {
				updateCartModal(json['html']);
			}
		},
		error: function () {
			$('#cart-modal-products').html('<p>Не удалось обновить корзину</p>');
			$('#cart-modal-totals').empty();
		},
	});
}

// Cart

// Sheet

let activeSheet = null;

function openSheet(name) {
	activeSheet = $(`[data-sheet="${name}"]`);

	if (!activeSheet.length) return;

	activeSheet.addClass('open');
	$('[data-sheet-overlay]').addClass('open');
	$('#viewport').addClass('disable-scroll');
}

function closeSheet() {
	if (!activeSheet || !activeSheet.length) return;

	activeSheet.removeClass('open');
	$('[data-sheet-overlay]').removeClass('open');
	$('#viewport').removeClass('disable-scroll');

	activeSheet = null;
}

$(document).on('click', '[data-sheet-open]', function () {
	openSheet($(this).data('sheet-open'));
});

$(document).on('click', '[data-sheet-close], [data-sheet-overlay]', function () {
	closeSheet();
});

$(document).on('keydown', function (e) {
	if (e.key === 'Escape') {
		closeSheet();
	}
});

// Sheet

// Modal

let activeModal = null;

function openModal(selector) {
	const modal = $(selector);

	if (!modal.length) return;

	activeModal = modal;

	activeModal.addClass('open');
	$('[data-modal-overlay]').addClass('open');
	$('#viewport').addClass('disable-scroll');

	activeModal.trigger('modal:open');
}

function closeModal() {
	if (!activeModal || !activeModal.length) return;

	activeModal.trigger('modal:close');

	activeModal.removeClass('open');
	$('[data-modal-overlay]').removeClass('open');
	$('#viewport').removeClass('disable-scroll');

	activeModal = null;
}

$(document).on('click', '[data-modal-open]', function () {
	openModal($(this).attr('data-modal-open'));
});

$(document).on('click', '[data-modal-close], [data-modal-overlay]', function () {
	closeModal();
});

$(document).on('keydown', function (e) {
	if (e.key === 'Escape') {
		closeModal();
	}
});

// Modal

// Search

$('#search-button').on('click', function () {
	const searchValue = $('#search-input').val();
	const prefix = $('#search-input').data('language');
	let url = `${prefix}/search`;
	if (searchValue) url += `?query=${encodeURIComponent(searchValue)}`;
	location.href = url;
});

$('#search-input').on('keydown', function (e) {
	if (e.key === 'Enter') {
		$('#search-button').trigger('click');
	}
});

// Search

// Live Search
(function () {
	const config = {
		root: '[data-live-search]',
		input: '[data-live-search-input]',
		results: '[data-live-search-results]',
		url: 'index.php?route=common/search/searchProducts',
		minLength: 2,
		delay: 300,
	};

	const $root = $(config.root);

	if (!$root.length) return;

	$root.each(function () {
		const $component = $(this);
		const $input = $component.find(config.input);
		const $results = $component.find(config.results);

		let timer = null;
		let request = null;

		function openResults() {
			$results.prop('hidden', false);
			$component.attr('data-open', 'true');
		}

		function closeResults() {
			$results.prop('hidden', true);
			$component.removeAttr('data-open');
		}

		function renderLoading() {
			$results.html('<div class="live-search-state">Поиск...</div>');
			openResults();
		}

		function renderError() {
			$results.html('<div class="live-search-state">Ошибка загрузки</div>');
			openResults();
		}

		function search(query) {
			if (request) {
				request.abort();
			}

			renderLoading();

			request = $.ajax({
				url: config.url,
				type: 'get',
				dataType: 'html',
				data: {
					filter_name: query,
				},
				success: function (html) {
					$results.html(html);
					openResults();
				},
				error: function (xhr, status) {
					if (status === 'abort') return;
					renderError();
				},
				complete: function () {
					request = null;
				},
			});
		}

		$input.on('input', function () {
			const query = $.trim($input.val());

			clearTimeout(timer);

			if (query.length < config.minLength) {
				closeResults();

				if (request) {
					request.abort();
					request = null;
				}

				return;
			}

			timer = setTimeout(function () {
				search(query);
			}, config.delay);
		});

		$input.on('focus', function () {
			const query = $.trim($input.val());

			if (query.length >= config.minLength && $results.html().trim()) {
				openResults();
			}
		});

		$(document).on('click', function (event) {
			if (!$(event.target).closest($component).length) {
				closeResults();
			}
		});

		$(document).on('keydown', function (event) {
			if (event.key === 'Escape') {
				closeResults();
				$input.blur();
			}
		});
	});
})();

// Live Search

// Toast

function createToastViewport(align) {
	let viewport = document.querySelector(`[data-toast-viewport="${align}"]`);

	if (!viewport) {
		viewport = document.createElement('div');
		viewport.className = 'toast-viewport';
		viewport.setAttribute('data-toast-viewport', align);
		document.body.appendChild(viewport);
	}

	return viewport;
}

function closeToast(toast) {
	toast.classList.remove('show');
	toast.classList.add('hide');

	setTimeout(function () {
		toast.remove();
	}, 220);
}

function sendToast({
	title = '',
	description = '',
	actionText = '',
	onAction = null,
	type = 'default',
	align = 'right-top',
	timeout = 3500,
}) {
	const template = document.getElementById('toast-template');
	const viewport = createToastViewport(align);

	if (!template || !viewport) return;

	const toast = template.content.firstElementChild.cloneNode(true);
	const titleElement = toast.querySelector('.toast-title');
	const descriptionElement = toast.querySelector('.toast-description');
	const actionButton = toast.querySelector('.toast-action');
	const closeButton = toast.querySelector('.toast-close');

	toast.setAttribute('data-type', type);

	titleElement.textContent = title;
	descriptionElement.textContent = description;

	if (!description) {
		descriptionElement.remove();
	}

	if (actionText) {
		actionButton.textContent = actionText;

		actionButton.addEventListener('click', function () {
			if (typeof onAction === 'function') {
				onAction();
			}

			closeToast(toast);
		});
	} else {
		actionButton.remove();
	}

	closeButton.addEventListener('click', function () {
		closeToast(toast);
	});

	viewport.appendChild(toast);

	requestAnimationFrame(function () {
		toast.classList.add('show');
	});

	if (timeout) {
		toast._timeout = setTimeout(function () {
			closeToast(toast);
		}, timeout);
	}

	toast.addEventListener('mouseenter', function () {
		clearTimeout(toast._timeout);
	});

	toast.addEventListener('mouseleave', function () {
		if (timeout) {
			toast._timeout = setTimeout(function () {
				closeToast(toast);
			}, 1200);
		}
	});
}

// Toast
