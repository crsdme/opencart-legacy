(function ($) {
	function titleOf(el) {
		return String($(el).attr('data-title') || '').toLowerCase();
	}

	function filterComponents(query) {
		query = (query || '').toLowerCase().trim();

		$('[data-docs-section]').each(function () {
			$(this).toggle(!query || titleOf(this).indexOf(query) !== -1);
		});

		$('[data-docs-group]').each(function () {
			const $group = $(this);
			let any = false;

			$group.find('[data-docs-link]').each(function () {
				const match = !query || titleOf(this).indexOf(query) !== -1;
				$(this).toggle(match);
				if (match) any = true;
			});

			$group.toggle(any);
		});
	}

	function scrollToHash(hash) {
		if (!hash || hash === '#') return;

		const id = hash.replace(/^#/, '');
		const target = document.getElementById(id);
		const scroller = document.querySelector('.docs-page-body');

		if (!target || !scroller) return;

		const top = target.getBoundingClientRect().top - scroller.getBoundingClientRect().top + scroller.scrollTop - 16;

		scroller.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
	}

	$(document).on('input', '[data-docs-filter]', function () {
		filterComponents($(this).val());
	});

	$(document).on('click', '[data-docs-menu]', function () {
		$('[data-docs-sidebar]').toggleClass('is-open');
	});

	$(document).on('click', '[data-docs-link]', function (e) {
		const href = $(this).attr('href') || '';
		$('[data-docs-sidebar]').removeClass('is-open');

		if (href.charAt(0) === '#') {
			e.preventDefault();
			if (history.replaceState) {
				history.replaceState(null, '', href);
			}
			scrollToHash(href);
		}
	});

	function markMissingFigure(img) {
		const $fig = $(img).closest('.docs-figure');

		if (!$fig.length) return;

		$fig.addClass('is-placeholder');
	}

	$(function () {
		if (window.location.hash) {
			scrollToHash(window.location.hash);
		}

		$('.docs-figure img').on('error', function () {
			markMissingFigure(this);
		}).each(function () {
			if (this.complete && this.naturalWidth === 0) {
				markMissingFigure(this);
			}
		});
	});

	$(document).on('click', '[data-docs-copy]', function () {
		const $button = $(this);
		const text = $button.closest('[data-docs-code]').find('code').text();

		if (!text || !navigator.clipboard) return;

		navigator.clipboard.writeText(text).then(function () {
			$button.attr('data-copied', 'true');
			setTimeout(function () {
				$button.removeAttr('data-copied');
			}, 1200);
		});
	});

	$(document).on('click', '[data-product-image]', function () {
		const src = $(this).attr('data-product-image');

		$('#product-image').attr('src', src);
		$('[data-product-image]').removeAttr('data-active');
		$(this).attr('data-active', 'true');
	});
})(jQuery);
