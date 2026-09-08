// UI primitives — every page. Page scripts: catalog.js, product.js, live-search.js, phone-login.js, checkout.js
(function ($) {
	function debounce(fn, wait) {
		let timeout;

		return function () {
			const context = this;
			const args = arguments;

			clearTimeout(timeout);
			timeout = setTimeout(function () {
				fn.apply(context, args);
			}, wait);
		};
	}

	function isTouchDevice() {
		return window.matchMedia('(hover: none), (pointer: coarse)').matches;
	}

	window.debounce = debounce;

	// Theme

	function toggleTheme() {
		const icon = $('[data-theme-icon]');
		const isDark = $('html').toggleClass('dark').hasClass('dark');

		icon.attr('href', isDark ? '/assets/icons/sprite.svg#icon-moon' : '/assets/icons/sprite.svg#icon-sun');
		document.cookie = 'theme=' + (isDark ? 'dark' : '') + '; path=/; max-age=31536000; samesite=Lax';
	}

	window.toggleTheme = toggleTheme;

	// Dropdown

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
		if (type === 'hover' && !isTouchDevice()) return;

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

	// Sheet

	let activeSheet = null;

	function openSheet(name) {
		activeSheet = $('[data-sheet="' + name + '"]');

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

	$(document).on('click', '[data-nav-toggle]', function (e) {
		e.preventDefault();

		const $item = $(this).closest('[data-nav-item]');
		const isOpen = $item.attr('data-open') === 'true';

		$item.attr('data-open', isOpen ? 'false' : 'true');
		$(this).attr('aria-expanded', isOpen ? 'false' : 'true');
	});

	$(document).on('click', '#form-currency .currency-select', function (e) {
		e.preventDefault();
		$('#form-currency input[name="code"]').val($(this).attr('name'));
		$('#form-currency').submit();
	});

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

	window.openModal = openModal;
	window.closeModal = closeModal;

	$(document).on('click', '[data-modal-open]', function () {
		openModal($(this).attr('data-modal-open'));
	});

	$(document).on('click', '[data-modal-close], [data-modal-overlay]', function () {
		closeModal();
	});

	$(document).on('keydown', function (e) {
		if (e.key !== 'Escape') return;

		closeAllDropdowns();
		closeSheet();
		closeModal();
	});

	// Search

	$('#search-button').on('click', function () {
		const searchValue = $('#search-input').val();
		const prefix = $('#search-input').data('language');
		let url = prefix + '/search';

		if (searchValue) {
			url += '?query=' + encodeURIComponent(searchValue);
		}

		location.href = url;
	});

	$('#search-input').on('keydown', function (e) {
		if (e.key === 'Enter') {
			$('#search-button').trigger('click');
		}
	});

	// Toast

	function createToastViewport(align) {
		let viewport = document.querySelector('[data-toast-viewport="' + align + '"]');

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

	function sendToast(options) {
		options = options || {};

		const title = options.title || '';
		const description = options.description || '';
		const actionText = options.actionText || '';
		const onAction = options.onAction || null;
		const type = options.type || 'default';
		const align = options.align || 'right-top';
		const timeout = options.timeout === undefined ? 3500 : options.timeout;
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

	window.sendToast = sendToast;

	// Shared UI

	$(document).on('click', '[data-scroll]', function (e) {
		const selector = $(this).data('scroll') || $(this).attr('href');
		const target = selector ? document.querySelector(selector) : null;
		const viewport = document.getElementById('viewport');

		if (!target || !viewport) return;

		e.preventDefault();

		viewport.scrollTo({
			top: target.getBoundingClientRect().top - viewport.getBoundingClientRect().top + viewport.scrollTop - 16,
			behavior: 'smooth',
		});
	});

	$('[data-collapse]').each(function () {
		const root = $(this);
		const content = root.find('.text-collapse-content');
		const toggle = root.find('[data-collapse-toggle]');

		if (content.outerHeight() <= 300) {
			root.addClass('is-expanded');
			toggle.remove();
		}
	});

	$(document).on('click', '[data-collapse-toggle]', function () {
		const root = $(this).closest('[data-collapse]');
		const expanded = root.toggleClass('is-expanded').hasClass('is-expanded');

		$(this).text(expanded ? $(this).data('text-less') : $(this).data('text-more'));
	});
})(jQuery);
