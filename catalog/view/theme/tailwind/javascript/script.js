// Language

$('#form-language .dropdown-item').on('click', function (e) {
    e.preventDefault();

    $('#form-language input[name="code"]').val($(this).attr('name'));

    $('#form-language').submit();
});

// Language

// Theme

const icon = $('#theme-icon');

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

$(document).on('click', '#dropdown-button', function (e) {
    const $dropdown = $(this).closest('.dropdown');
    const $menu = $dropdown.find('.dropdown-menu');
    const type = $dropdown.data('type');

    // Если это hover dropdown на desktop — клик не нужен
    if (type === 'hover' && !isTouchDevice()) {
        return;
    }

    e.stopPropagation();

    $('.dropdown-menu').not($menu).removeClass('show');
    $menu.toggleClass('show');
});

$(document).on('mouseenter', '.dropdown[data-type="hover"]', function () {
    if (isTouchDevice()) return;

    const $menu = $(this).find('.dropdown-menu');

    $('.dropdown-menu').not($menu).removeClass('show');
    $menu.addClass('show');
});

$(document).on('mouseleave', '.dropdown[data-type="hover"]', function () {
    if (isTouchDevice()) return;

    $(this).find('.dropdown-menu').removeClass('show');
});

$(document).on('click', function () {
    $('.dropdown-menu').removeClass('show');
});

$(document).on('click', '.dropdown-menu', function (e) {
    e.stopPropagation();
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

const cartButton = $('#cart-button');
const cartBadge = $('#cart-badge');
const cartModal = $('#cart-modal');
const cartProducts = $('#cart-modal-products');
const cartTotals = $('#cart-modal-totals');
const cartOverlay = $('#cart-overlay');
let cartToastTimeout = null;

function openCart() {
    cartModal.removeClass('hidden');
    cartOverlay.removeClass('hidden');
    $('body').addClass('disable-scroll');

    $.ajax({
        url: 'index.php?route=common/cart/info',
        cache: false,
        beforeSend: function () {
            cartProducts.html(`
          <div class="space-y-2">
              <div class="skeleton h-25 w-full rounded-md"></div>
              <div class="skeleton h-25 w-full rounded-md"></div>
              <div class="skeleton h-25 w-full rounded-md"></div>
              <div class="skeleton h-25 w-full rounded-md"></div>
          </div>
        `);
            cartTotals.html(`
            <div class="space-y-2">
                <div class="skeleton h-25 w-full rounded-md"></div>
            </div>
        `);
        },
        success: function (html) {
            const response = $(html);
            cartTotals.html(response.filter('#cart-modal-totals').html());
            cartProducts.html(response.filter('#cart-modal-products').html());
        },
    });
}

function closeCart() {
    cartModal.addClass('hidden');
    cartOverlay.addClass('hidden');
    $('body').removeClass('disable-scroll');
}

function addToCart(product_id, quantity = 1, button) {
    $.ajax({
        url: 'index.php?route=common/cart/add',
        type: 'post',
        data: 'product_id=' + product_id + '&quantity=' + quantity,
        dataType: 'json',
        cache: false,
        beforeSend: function () {
            // button.setAttribute('disabled', true);
        },
        success: function (json) {
            cartBadge.text(json['total']);
            // button.innerHTML = button.getAttribute('data-added-text');

            if (json['error']) {
                button.setAttribute('disabled', false);
                return;
            }

            // $('#cart-toast').html(json['totalPrice']);
            // $('#cart-toast').addClass('show');

            // if (cartToastTimeout) clearTimeout(cartToastTimeout);
            // cartToastTimeout = setTimeout(function () {
            //     $('#cart-toast').removeClass('show');
            //     cartToastTimeout = null;
            // }, 2500);
            // sendToast({ message: json['success'], type: 'success', align: 'right-bottom', timeout: 4000 });
        },
    });
}

function removeCartProduct(productKey) {
    $.ajax({
        url: `index.php?route=common/cart/remove`,
        type: 'post',
        data: `key=${productKey}`,
        cache: false,
        beforeSend: function () {
            cartProducts.html(`
            <div class="space-y-2">
                <div class="skeleton h-25 w-full rounded-md"></div>
                <div class="skeleton h-25 w-full rounded-md"></div>
                <div class="skeleton h-25 w-full rounded-md"></div>
                <div class="skeleton h-25 w-full rounded-md"></div>
            </div>
          `);
            cartTotals.html(`
              <div class="space-y-2">
                  <div class="skeleton h-25 w-full rounded-md"></div>
              </div>
          `);
        },
        success: function (json) {
            const response = $(json['html']);
            cartBadge.text(json['total']);
            cartTotals.html(response.filter('#cart-modal-totals').html());
            cartProducts.html(response.filter('#cart-modal-products').html());
        },
    });
}

function addCartProduct(target) {
    $.ajax({
        url: `index.php?route=common/cart&add=${target}&quantity=1`,
        type: 'get',
        dataType: 'html',
        cache: false,
        beforeSend: function () {
            $('#cart-products').html(`
      <div class="space-y-2">
        <div class="skeleton h-25 w-full rounded-md"></div>
        <div class="skeleton h-25 w-full rounded-md"></div>
        <div class="skeleton h-25 w-full rounded-md"></div>
        <div class="skeleton h-25 w-full rounded-md"></div>
      </div>
    `);
        },
        success: function (data) {
            data = `<div>${data}</div>`;
            $('#cart-products').children().remove();
            $('#cart-products').append($(data).find('#cart-products').html());
            $('#cart-total').html($(data).find('#cart-total').children());
        },
    });
}

function updateCartProduct(target) {
    const product_id = $(target).parent().children('input[name=product_id]').val();
    const quantity = $(target).parent().children('input[name=quantity]').val();

    if (isNaN(quantity)) {
        $(target).parent().children('input[name=quantity]').val(1);
        return;
    }

    $.ajax({
        url: `index.php?route=common/cart&update=${product_id}&quantity=${quantity}`,
        type: 'get',
        dataType: 'html',
        cache: false,
        beforeSend: function () {
            $('#cart-products').html(`
                <div class="space-y-2">
                    <div class="skeleton h-25 w-full rounded-md"></div>
                    <div class="skeleton h-25 w-full rounded-md"></div>
                    <div class="skeleton h-25 w-full rounded-md"></div>
                    <div class="skeleton h-25 w-full rounded-md"></div>
                </div>
            `);
        },
        success: function (data) {
            data = `<div>${data}</div>`;
            $('#cart-products').children().remove();
            $('#cart-products').append($(data).find('#cart-products').html());
            $('#cart-total').html($(data).find('#cart-total').children());
        },
    });
}

const addCartProductDebounced = debounce(addCartProduct, 500);

const updateCartProductDebounced = debounce(updateCartProduct, 500);

// Cart

// Menu

function openMenu() {
    $('#menu-sheet').toggleClass('open');
    $('#sheet-overlay').toggleClass('hidden');
    $('body').toggleClass('disable-scroll');
}

function closeMenu() {
    $('#menu-sheet').toggleClass('open');
    $('#sheet-overlay').toggleClass('hidden');
    $('body').toggleClass('disable-scroll');
}

// Menu

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

// Collapse Text

$(function () {
    $('.collapse-toggle').on('click', function () {
        const $btn = $(this);
        const $wrapper = $btn.closest('.text-block').find('.text-collapse-wrapper');
        const $content = $wrapper.find('.text-collapse-content');

        if (!$wrapper.length || !$content.length) return;

        const isExpanded = $wrapper.hasClass('expanded');

        if (isExpanded) {
            $wrapper.removeClass('expanded').css('max-height', '300px');
            $btn.text('Читать полностью');
        } else {
            const fullHeight = $content.outerHeight(true);
            $wrapper.addClass('expanded').css('max-height', fullHeight + 'px');
            $btn.text('Скрыть');
        }
    });
});

// Collapse Text
