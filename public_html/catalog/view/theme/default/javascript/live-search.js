// Live product search — loaded from common/search when product pages are enabled
(function ($) {
	const config = {
		root: '[data-live-search]',
		input: '[data-live-search-input]',
		results: '[data-live-search-results]',
		minLength: 2,
		delay: 300,
	};

	$(config.root).each(function () {
		const $component = $(this);
		const $input = $component.find(config.input);
		const $results = $component.find(config.results);
		const url = $input.data('live-search-url');
		const textLoading = $component.data('text-loading') || 'Searching…';
		const textError = $component.data('text-error') || 'Could not load results';

		if (!url) return;

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

		function skeletonItem() {
			return (
				'<div class="live-search-skeleton-item" aria-hidden="true">' +
				'<span class="live-search-skeleton-image skeleton"></span>' +
				'<span class="live-search-skeleton-info">' +
				'<span class="live-search-skeleton-line skeleton"></span>' +
				'<span class="live-search-skeleton-line skeleton is-short"></span>' +
				'</span></div>'
			);
		}

		function renderLoading() {
			$results.html(
				'<div class="live-search-skeleton" role="status" aria-live="polite" aria-label="' +
					textLoading +
					'">' +
					skeletonItem() +
					skeletonItem() +
					skeletonItem() +
					'</div>',
			);
			openResults();
		}

		function renderError() {
			$results.html(
				'<div class="live-search-state" data-type="error" role="alert">' +
					'<span class="live-search-state-title">' +
					textError +
					'</span></div>',
			);
			openResults();
		}

		function search(query) {
			if (request) {
				request.abort();
			}

			renderLoading();

			request = $.ajax({
				url: url,
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
})(jQuery);
