// Product / blog review list pagination and write form
(function ($) {
	const $review = $('#review');
	const reviewUrl = $review.data('review-url');

	if ($review.length && reviewUrl) {
		$review.on('click', '.pagination a', function (e) {
			e.preventDefault();
			$review.load(this.href);
		});
	}

	$('#form-review').on('submit', function (e) {
		e.preventDefault();
	});

	$('#button-review').on('click', function () {
		const writeUrl = $('#form-review').attr('data-write-url');

		if (!writeUrl) {
			return;
		}

		$.ajax({
			url: writeUrl,
			type: 'post',
			dataType: 'json',
			data: $('#form-review').serialize(),
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

				if (json.success) {
					sendToast({
						title: json.success,
						type: 'success',
						align: 'right-bottom',
						timeout: 4000,
					});

					$('#form-review textarea').val('');
					$('#form-review input[name="rating"]').prop('checked', false);

					if (reviewUrl) {
						$review.load(reviewUrl);
					}
				}
			},
		});
	});
})(jQuery);
