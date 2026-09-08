// Phone login / intl-tel-input — loaded with Account::addPhoneAssets()
(function ($) {
	function phoneCountryLocale() {
		const lang = (document.documentElement.getAttribute('lang') || 'uk').toLowerCase();

		if (lang === 'ua' || lang.indexOf('uk') === 0) {
			return 'uk';
		}

		if (lang.indexOf('ru') === 0) {
			return 'ru';
		}

		return 'en';
	}

	function getIntlTelInput() {
		const lib = window.intlTelInput;

		if (typeof lib === 'function') {
			return lib;
		}

		if (lib && typeof lib.default === 'function') {
			return lib.default;
		}

		return null;
	}

	function phoneIsoFromDial(dial) {
		const code = String(dial || '').replace(/\D/g, '');
		const iti = getIntlTelInput();

		if (!code || !iti || !iti.getAllCountries) {
			return 'ua';
		}

		const match = iti.getAllCountries().find(function (country) {
			return country.dialCode === code;
		});

		return match ? match.iso2 : 'ua';
	}

	function phoneItiOptions(prefix) {
		return {
			initialCountry: phoneIsoFromDial(prefix),
			countryNameLocale: phoneCountryLocale(),
			formatAsYouType: true,
			autoPlaceholder: 'aggressive',
			separateDialCode: true,
			strictMode: true,
			countrySearch: true,
			dropdownParent: document.body,
		};
	}

	function phoneDigitsFromInput(input) {
		if (!input) {
			return '';
		}

		const itiLib = getIntlTelInput();
		const iti = itiLib && itiLib.getInstance ? itiLib.getInstance(input) : null;
		let digits = String(input.value || '').replace(/\D/g, '');

		if (!iti) {
			return digits;
		}

		const country = iti.getSelectedCountry();
		const dial = country && country.dialCode ? String(country.dialCode) : '';

		if (!dial) {
			return digits;
		}

		if (digits.indexOf(dial) === 0) {
			return digits;
		}

		return dial + digits.replace(/^0+/, '');
	}

	function syncPhoneLoginNumber(input) {
		if (!input) {
			return '';
		}

		const telephone = phoneDigitsFromInput(input);
		const $item = $(input).closest('.form-item, [data-phone-login]');

		$item.find('input[type="hidden"][name="telephone"]').val(telephone);

		return telephone;
	}

	function initPhoneInput(input, prefix) {
		const itiLib = getIntlTelInput();

		if (!input) {
			return true;
		}

		if (!itiLib) {
			return false;
		}

		if (itiLib.getInstance(input)) {
			syncPhoneLoginNumber(input);
			return true;
		}

		const iti = itiLib(input, phoneItiOptions(prefix || '380'));
		const wrap = input.closest('.form-item, [data-phone-login]') || input.parentNode;
		const hidden = wrap.querySelector('input[name="telephone"]');

		if (hidden && hidden.value) {
			iti.setNumber('+' + String(hidden.value).replace(/\D/g, ''));
		}

		syncPhoneLoginNumber(input);

		return true;
	}

	function initPhoneLoginInputs(root) {
		const itiLib = getIntlTelInput();

		if (!itiLib) {
			return false;
		}

		$(root)
			.find('[data-phone-input]')
			.each(function () {
				const prefix =
					$(this).closest('[data-phone-prefix]').attr('data-phone-prefix') ||
					$(this).closest('[data-phone-login]').attr('data-phone-prefix') ||
					'380';

				initPhoneInput(this, prefix);
			});

		return true;
	}

	function startPhoneLoginCooldown($button, seconds) {
		const label = $button.data('label') || $button.text();
		const resend = $button.data('resend') || label;
		let left = parseInt(seconds, 10) || 60;

		$button.prop('disabled', true);
		$button.text(resend + ' (' + left + ')');

		const timer = setInterval(function () {
			left -= 1;

			if (left <= 0) {
				clearInterval(timer);
				$button.prop('disabled', false);
				$button.text(resend);
				return;
			}

			$button.text(resend + ' (' + left + ')');
		}, 1000);
	}

	$(function () {
		if (initPhoneLoginInputs(document)) {
			return;
		}

		let attempts = 0;
		const timer = setInterval(function () {
			attempts += 1;

			if (initPhoneLoginInputs(document) || attempts > 20) {
				clearInterval(timer);
			}
		}, 50);
	});

	$(document).ajaxSuccess(function () {
		initPhoneLoginInputs(document);
	});

	$(document).on('input countrychange', '[data-phone-input]', function () {
		syncPhoneLoginNumber(this);
	});

	$(document).on('submit', '[data-phone-login]', function () {
		syncPhoneLoginNumber($(this).find('[data-phone-input]')[0]);
	});

	document.addEventListener(
		'click',
		function (e) {
			const button = e.target.closest('[data-phone-login-submit]');

			if (!button) {
				return;
			}

			const root = button.closest('[data-phone-login]');

			if (!root) {
				return;
			}

			syncPhoneLoginNumber(root.querySelector('[data-phone-input]'));
		},
		true,
	);

	$(document).on('click', '[data-phone-login-send]', function (e) {
		e.preventDefault();

		const $button = $(this);
		const $root = $button.closest('[data-phone-login]');
		const url = $root.attr('data-send-url');
		const telephone = syncPhoneLoginNumber($root.find('[data-phone-input]')[0]);

		if (!url || $button.prop('disabled')) {
			return;
		}

		const $error = $root.find('[data-phone-login-error]');
		const $success = $root.find('[data-phone-login-success]');

		$error.attr('hidden', true).find('span').text('');
		$success.attr('hidden', true).find('span').text('');

		$.ajax({
			url: url,
			type: 'post',
			data: { telephone: telephone },
			dataType: 'json',
			beforeSend: function () {
				$button.prop('disabled', true);
			},
			success: function (json) {
				if (json.error) {
					$error.removeAttr('hidden').find('span').text(json.error);
					$button.prop('disabled', false);
					return;
				}

				$root.find('[data-phone-login-code]').removeAttr('hidden');
				$root.find('[data-phone-login-submit]').removeAttr('hidden');
				$root.find('[name="code"]').trigger('focus');

				if (json.success) {
					$success.removeAttr('hidden').find('span').text(json.success);
				}

				startPhoneLoginCooldown($button, json.retry_after || 60);
			},
			error: function () {
				$button.prop('disabled', false);
			},
		});
	});
})(jQuery);
