// Product page — gallery, sticky columns, options (reviews: review.js)
(function ($) {
	const hero = document.querySelector('.product-hero');

	if (hero) {
		const cols = hero.querySelectorAll('.product-hero-col');
		const desktop = window.matchMedia('(min-width: 1024px)');

		function headerOffset() {
			const header = document.querySelector('.header');

			return (header ? header.getBoundingClientRect().height : 0) + 16;
		}

		function syncProductSticky() {
			const viewport = document.getElementById('viewport');
			const top = headerOffset();
			const available = (viewport ? viewport.clientHeight : window.innerHeight) - top;

			document.documentElement.style.setProperty('--product-sticky-top', top + 'px');

			cols.forEach(function (col) {
				col.classList.remove('is-sticky');
			});

			if (!desktop.matches) return;

			cols.forEach(function (col) {
				if (col.offsetHeight <= available) {
					col.classList.add('is-sticky');
				}
			});
		}

		syncProductSticky();

		if (typeof ResizeObserver === 'function') {
			const observer = new ResizeObserver(syncProductSticky);

			cols.forEach(function (col) {
				observer.observe(col);
			});

			const header = document.querySelector('.header');
			const viewport = document.getElementById('viewport');

			if (header) observer.observe(header);
			if (viewport) observer.observe(viewport);
		}

		desktop.addEventListener('change', syncProductSticky);
		window.addEventListener('resize', syncProductSticky);

		hero.querySelectorAll('img').forEach(function (img) {
			if (!img.complete) {
				img.addEventListener('load', syncProductSticky, { once: true });
			}
		});
	}

	$(document).on('click', '[data-product-image]', function () {
		const src = $(this).data('product-image');

		$('#product-image').attr('src', src);
		$('[data-product-image]').removeAttr('data-active');
		$(this).attr('data-active', 'true');
	});

	$('select[name="recurring_id"], #input-quantity').on('change', function () {
		if (!$('select[name="recurring_id"]').length) return;

		$.ajax({
			url: 'index.php?route=product/product/getRecurringDescription',
			type: 'post',
			data: $('input[name="product_id"], input[name="quantity"], select[name="recurring_id"]'),
			dataType: 'json',
			success: function (json) {
				$('#recurring-description').html(json.success || '');
			},
		});
	});

	$(document).on('click', '[data-option-upload]', function () {
		const optionId = $(this).data('option-upload');
		const input = $('#input-option' + optionId);

		$('#form-upload').remove();
		$('body').prepend(
			'<form enctype="multipart/form-data" id="form-upload" class="hidden"><input type="file" name="file" /></form>',
		);
		$('#form-upload input[name="file"]').trigger('click');

		$('#form-upload input[name="file"]').on('change', function () {
			$.ajax({
				url: 'index.php?route=tool/upload',
				type: 'post',
				dataType: 'json',
				data: new FormData($('#form-upload')[0]),
				cache: false,
				contentType: false,
				processData: false,
				success: function (json) {
					if (json.error) {
						sendToast({
							title: json.error,
							type: 'error',
							align: 'right-bottom',
							timeout: 4000,
						});
						return;
					}

					if (json.code) {
						input.val(json.code);
					}
				},
			});
		});
	});
})(jQuery);
