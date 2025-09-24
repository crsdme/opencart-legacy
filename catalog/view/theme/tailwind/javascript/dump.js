// Theme

const icon = document.getElementById('theme-icon');

function changeTheme() {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    icon.setAttribute('href', isDark ? '#icon-moon' : '#icon-sun');
}

document.addEventListener('DOMContentLoaded', () => {
    const savedTheme = localStorage.getItem('theme');

    if (savedTheme === 'dark') {
        document.documentElement.classList.add('dark');
        icon?.setAttribute('href', '#icon-moon');
    } else {
        document.documentElement.classList.remove('dark');
        icon?.setAttribute('href', '#icon-sun');
    }
});

// Theme

// Cart

const cart = $('#cart-modal');
const cartOverlay = $('#cart-overlay');
let cartToastTimeout = null;
let language = $('html').attr('lang').slice(0, 2);

initCart();

function initCart() {
    $.ajax({
        type: 'get',
        url: 'index.php?route=extension/module/cart_popup/initcart&language=' + language,
        dataType: 'json',
        cache: false,
        success: function (json) {
            $('#cart-count').html(json['total']);
        },
    });
}

function updateCart() {
    $.ajax({
        url: 'index.php?route=checkout/cart/update',
        type: 'post',
        dataType: 'json',
        cache: false,
        success: function (json) {
            $('#cart-count').html(json['total']);
        },
    });
}

function addToCart(product_id, quantity = 1, button) {
    $.ajax({
        url: 'index.php?route=checkout/cart/add&language=' + language,
        type: 'post',
        data: 'product_id=' + product_id + '&quantity=' + quantity,
        dataType: 'json',
        cache: false,
        success: function (json) {
            button.setAttribute('disabled', true);
            button.innerHTML = button.getAttribute('data-added-text');

            if (json['error']) {
                sendToast({ message: json['error'], type: 'error', align: 'right-bottom', timeout: 4000 });
                return;
            }

            $('#cart-count').html(json['total']);
            $('#cart-toast').html(json['totalPrice']);
            $('#cart-toast').addClass('show');

            if (cartToastTimeout) clearTimeout(cartToastTimeout);
            cartToastTimeout = setTimeout(function () {
                $('#cart-toast').removeClass('show');
                cartToastTimeout = null;
            }, 2500);
            sendToast({ message: json['success'], type: 'success', align: 'right-bottom', timeout: 4000 });
        },
    });
}

function openCart() {
    cart.toggleClass('hidden');
    cartOverlay.toggleClass('hidden');
    $('body').toggleClass('overflow-hidden');

    $.ajax({
        url: 'index.php?route=extension/module/cart_popup&language=' + language,
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
        success: function (html) {
            cart.html(html);
        },
    });
}

function closeCart() {
    cart.toggleClass('hidden');
    cartOverlay.toggleClass('hidden');
    $('body').toggleClass('overflow-hidden');
}

function debounce(func, wait) {
    let timeout;
    return function () {
        const context = this,
            args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), wait);
    };
}

function removeCartProduct(target) {
    const productKey = $(target).next().val();
    $.ajax({
        url: `index.php?route=extension/module/cart_popup&remove=${productKey}&language=${language}`,
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
            updateCart();
        },
    });
}

function addCartProduct(target) {
    $.ajax({
        url: `index.php?route=extension/module/cart_popup&add=${target}&quantity=1&language=${language}`,
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
            updateCart();
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
        url: `index.php?route=extension/module/cart_popup&update=${product_id}&quantity=${quantity}&language=${language}`,
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
            updateCart();
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
    $('body').toggleClass('overflow-hidden');
}

function closeMenu() {
    $('#menu-sheet').toggleClass('open');
    $('#sheet-overlay').toggleClass('hidden');
    $('body').toggleClass('overflow-hidden');
}

// Menu

// Toast

function sendToast({ message = 'Success!', type = 'success', align = 'right-top', timeout = 2500 }) {
    const toast = document.getElementById('toast');
    const icon = document.getElementById('toast-icon');
    const msg = toast.querySelector('.toast-message');

    const icons = {
        success: '#icon-check',
        error: '#icon-x',
        warning: '#icon-alert-triangle',
    };

    toast.setAttribute('data-type', type);
    toast.setAttribute('data-align', align);
    msg.textContent = message;
    icon.setAttribute('href', icons[type] || icons.success);

    toast.classList.remove('hidden', 'animate-out');
    toast.classList.add('show', 'animate-in');

    clearTimeout(toast._timeout);
    toast._timeout = setTimeout(() => {
        toast.classList.remove('animate-in');
        toast.classList.add('animate-out');

        setTimeout(() => {
            toast.classList.remove('show');
            toast.classList.add('hidden');
        }, 200);
    }, timeout);
}

function closeToast() {
    const toast = document.getElementById('toast');
    toast.classList.remove('animate-in');
    toast.classList.add('animate-out');

    setTimeout(() => {
        toast.classList.remove('show');
        toast.classList.add('hidden');
    }, 200);
}

// Toast

// Popup

const productModal = $('#product-modal');
const productModalOverlay = $('#product-modal-overlay');
const productModalImage = $('#product-modal-image');

function openProductModal(image) {
    productModal.toggleClass('hidden');
    productModalOverlay.toggleClass('hidden');
    productModalImage.attr('src', image);
    $('body').toggleClass('overflow-hidden');
}

function closeProductModal(target) {
    productModal.toggleClass('hidden');
    productModalOverlay.toggleClass('hidden');
    productModalImage.attr('src', '');
    $('body').toggleClass('overflow-hidden');
}

// Popup

// Contact Modal

const contanctModal = $('#contact-modal');
const contactModalOverlay = $('#contact-modal-overlay');

document.addEventListener('DOMContentLoaded', () => {
    const input = document.querySelector('#phone-input');
    window.intlTelInput(input, {
        initialCountry: 'auto',
        formatOnDisplay: true,
        nationalMode: true,
        strictMode: true,
        separateDialCode: true,
        utilsScript: '/intl-tel-input/js/utils.js',
        hiddenInput: () => ({ phone: 'full_phone', country: 'country_code' }),
        geoIpLookup: (callback) => {
            fetch('https://ipapi.co/json')
                .then((res) => res.json())
                .then((data) => callback(data.country_code))
                .catch(() => callback('us'));
        },
    });
});

function openContactModal() {
    contanctModal.toggleClass('hidden');
    contactModalOverlay.toggleClass('hidden');
    $('body').toggleClass('overflow-hidden');
}

function closeContactModal() {
    contanctModal.toggleClass('hidden');
    contactModalOverlay.toggleClass('hidden');
    $('body').toggleClass('overflow-hidden');
}

$('#contact-form').on('submit', function (e) {
    e.preventDefault();

    const formData = $(this).serialize();

    const phone = $('#phone-input').val().trim();

    if (!phone || phone.length < 6) {
        sendToast({
            message: 'Please enter a valid phone number.',
            type: 'error',
            align: 'right-bottom',
            timeout: 3000,
        });
        return;
    }
    console.log(formData);
    $.ajax({
        url: `index.php?route=common/home/sendmessage`,
        type: 'post',
        dataType: 'html',
        data: formData,
        cache: false,
        beforeSend: function () {
            $('#contact-form').find('input, textarea, button').prop('disabled', true);
        },
        success: function () {
            contanctModal.toggleClass('hidden');
            contactModalOverlay.toggleClass('hidden');
            $('body').toggleClass('overflow-hidden');
            $('#contact-form').trigger('reset');
            sendToast({ message: 'Success!', type: 'success', align: 'right-bottom', timeout: 2500 });
        },
        complete: function () {
            $('#contact-form').find('input, textarea, button').prop('disabled', false);
        },
    });
});

// Contact Modal

// Load More

const button = $('#load-more-products-button');

function loadMoreProducts() {
    const limit = button.data('limit');
    const category = button.data('category');
    const url = button.data('url');
    const path = button.data('path');
    const sort = button.data('sort');
    const search = button.data('search');
    const order = button.data('order');
    const step = button.data('step');
    const total = button.data('total-pages');
    const page = +step + 1;

    $.ajax({
        type: 'POST',
        url: url,
        data: {
            limit: limit,
            category: category,
            path: path,
            sort: sort,
            search: search,
            order: order,
            step: step,
        },
        beforeSend: function () {
            button.prop('disabled', true).addClass('disabled');
        },
        success: function (response) {
            if (response || page > total) {
                $('#products-list').append(response);
                button.prop('disabled', false).removeClass('disabled').data('step', page);
            } else {
                button.hide();
            }
        },
    });
}

// Load More

// Checkout
function reinitFormInput(savedPhone) {
    const input = document.querySelector('#customer_telephone');
    if (!input) return;
    console.log(savedPhone);
    if (savedPhone) input.value = savedPhone;

    window.intlTelInput(input, {
        initialCountry: 'auto',
        formatOnDisplay: true,
        nationalMode: true,
        strictMode: true,
        separateDialCode: true,
        utilsScript: '/intl-tel-input/js/utils.js',
        hiddenInput: () => ({ phone: 'full_phone', country: 'country_code' }),
        geoIpLookup: (callback) => {
            fetch('https://ipapi.co/json')
                .then((res) => res.json())
                .then((data) => callback(data.country_code || 'US'))
                .catch(() => callback('US'));
        },
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const $customerPhone = $('#customer_telephone');
    let phoneState = '';

    if ($customerPhone.length > 0) {
        phoneState = $customerPhone.val();
    }

    reinitFormInput(phoneState);
});
// Checkout

// Dropdown

$('#dropdownButton, .dropdown-button').on('click', function (e) {
    e.stopPropagation();

    const $button = $(this);
    const $menu = $button.closest('.dropdown').find('.dropdown-menu');

    $('.dropdown-menu').not($menu).removeClass('show');
    $menu.toggleClass('show');
});

$(document).on('click', function () {
    $('.dropdown-menu').removeClass('show');
});

$('.dropdown-menu').on('click', function (e) {
    e.stopPropagation();
});

// Dropdown

// window.onload = function () {
//   // Animation
//   $(document).on("click", ".animateCart", function () {
//     let productImage = $(this)
//       .closest(".productCard")
//       .find(".productImage img");

//     if (productImage.length === 0) {
//       productImage = $(".productImages img")
//         .filter(function () {
//           return $(this).parent().hasClass("is-visible");
//         })
//         .first();
//     }

//     if (productImage.length === 0) {
//       productImage = $(this)
//         .parent()
//         .parent()
//         .find(".productTableImages a img");
//     }

//     var animatedImage = productImage
//       .clone()
//       .addClass("animatedImage")
//       .appendTo("body");

//     var productPos = productImage[0].getBoundingClientRect();
//     var targetPos = $(".cartButton")[0].getBoundingClientRect();

//     animatedImage.css({
//       position: "fixed",
//       top: productPos.top,
//       left: productPos.left,
//       width: productImage.width(),
//       height: productImage.height(),
//       borderRadius: 4,
//       zIndex: 10,
//     });

//     animatedImage.animate(
//       {
//         top: targetPos.top + 7,
//         left: targetPos.left + 20,
//         width: 5,
//         height: 5,
//         opacity: 0,
//       },
//       {
//         duration: 700,
//         complete: () => animatedImage.remove(),
//       }
//     );
//   });

//   function closeAll() {
//     $(".catalogList, .catalogButton, .catalogOverlay").removeClass("active");
//     $(".searchContent").removeClass("active");
//     $(".burgerButton, .burgerOverlay, .burgerMenu").removeClass("active");
//     $("body").removeClass("closeScroll");
//   }
//   // Animation

//   // Cookie
//   if (!localStorage.getItem("cookiesAccepted")) {
//     $("#cookie-modal").show();
//   }

//   $("#accept-cookies").on("click", function () {
//     localStorage.setItem("cookiesAccepted", "true");
//     $("#cookie-modal").hide();
//   });

//   setTimeout(function () {
//     localStorage.setItem("cookiesAccepted", "true");
//   }, 30000);
//   // Cookie

//   // Search

//   $(".searchForm button").on("click", function () {
//     const searchValue = $(".searchForm input[name='search']").val();
//     const langMatch = window.location.pathname.match(/^\/(ua|ru|en)(\/|$)/);
//     const langPrefix = langMatch ? `/${langMatch[1]}/` : "/";
//     let url = `${langPrefix}search`;
//     if (searchValue) url += `?search=${encodeURIComponent(searchValue)}`;
//     location.href = url;
//   });

//   $(".searchForm input[name='search']").on("keydown", function (e) {
//     if (e.key === "Enter") {
//       $(".searchForm button").trigger("click");
//     }
//   });

//   $(".searchButton").on("click", function () {
//     if (!$(".searchContent").hasClass("active")) closeAll();
//     $(".searchContent").toggleClass("active");
//     $("body").toggleClass("closeScroll");
//   });
//   // Search

//   // Search Page
//   $("#button-search").on("click", function () {
//     const searchValue = $("#content input[name='search']").val();
//     const langMatch = window.location.pathname.match(/^\/(ua|ru|en)(\/|$)/);
//     const langPrefix = langMatch ? `/${langMatch[1]}/` : "/";
//     let url = `${langPrefix}search`;
//     if (searchValue) url += `?search=${encodeURIComponent(searchValue)}`;
//     location.href = url;
//   });

//   $("#content input[name='search']").on("keydown", function (e) {
//     if (e.key === "Enter") {
//       $("#button-search").trigger("click");
//     }
//   });
//   // Search Page

//   // Ajax Search
//   var liveSearch = {
//     selector: ".searchForm input",
//     height: "50px",
//   };

//   var html = '<div class="liveSearch"><div class="liveSearchList"></div></div>';

//   $(liveSearch.selector).after(html);

//   $(liveSearch.selector).autocomplete({
//     source: function (request, response) {
//       var filter_name = $(liveSearch.selector).val();
//       var cat_id = 0;
//       if (filter_name.length < 1) {
//         $(".liveSearch").hide();
//       } else {
//         var live_search_href =
//           "index.php?route=extension/module/live_search&filter_name=" +
//           encodeURIComponent(filter_name);
//         if (cat_id > 0) {
//           live_search_href += "&cat_id=" + Math.abs(cat_id);
//         }
//         $(".liveSearch").show();

//         $.ajax({
//           url: live_search_href,
//           dataType: "json",
//           success: function (result) {
//             var products = result.products;
//             console.log(products);
//             $(".liveSearch .liveSearchList").empty();
//             if (products?.length) {
//               $.each(products, function (index, product) {
//                 var html =
//                   '<a href="' + product.url + '" title="' + product.name + '">';
//                 html +=
//                   "<img onerror=\"this.onerror=null;this.src='" +
//                   product.placeholder +
//                   '\';" width="100" height="100" alt="' +
//                   product.name +
//                   '" src="' +
//                   product.image +
//                   '">';
//                 html += '<div class="liveSearchProductDescription">';
//                 html += '<p class="name">' + product.name + "</p>";
//                 html += '<p class="price">';
//                 html += "" + product.price + "";
//                 if (product.special) {
//                   "<span>" + product.special + "</span>";
//                 }
//                 html += "</p>";
//                 html += '<div class="liveSearchBadge">';
//                 if (product.attributes) {
//                   product.attributes.forEach((item) => {
//                     html += "<p>" + item + "</p>";
//                   });
//                 }
//                 html += "</div>";
//                 html += "</div>";
//                 html += "</a>";

//                 $(".liveSearch .liveSearchList").append(html);
//               });
//             }
//             $(".liveSearch").show();
//           },
//         });
//       }
//     },
//     select: function (event, ui) {
//       $(live_search.selector).val(ui.item.name);
//     },
//   });
//   // Ajax Search
// };

// (function ($) {
//   $.fn.autocomplete = function (option) {
//     return this.each(function () {
//       let timer = null;
//       let source = option.source;
//       let select = option.select;

//       $(this).attr("autocomplete", "off");

//       $(this).on("input", function () {
//         clearTimeout(timer);
//         let query = $(this).val().trim();

//         if (query.length) {
//           timer = setTimeout(() => {
//             source(query, function (results) {
//               if (results.length && select) {
//                 select(results[0]);
//               }
//             });
//           }, 200);
//         }
//       });
//     });
//   };
// })(window.jQuery);

// // Autocomplete
// let cartTooltipTimeout = null;

// function maskPhoneNo(event) {
//   const element = event.target;
//   const rawValue = element.value;
//   const cursorPosition = element.selectionStart;

//   let pnum = rawValue.replace(/\D/g, "");

//   pnum = pnum.slice(2);

//   let digitsBeforeCursor = 0;
//   for (
//     let i = 0, digitCount = 0;
//     i < cursorPosition && i < rawValue.length;
//     i++
//   ) {
//     if (/\d/.test(rawValue[i])) digitCount++;
//     if (digitCount > 2) digitsBeforeCursor++;
//   }

//   let formattedNum = "+38";
//   let formattedCursorPos = 3;

//   if (pnum.length > 0) {
//     formattedNum += "(" + pnum.slice(0, 3);
//     if (digitsBeforeCursor > 0)
//       formattedCursorPos += Math.min(digitsBeforeCursor, 3) + 1;
//   }
//   if (pnum.length > 3) {
//     formattedNum += ")" + pnum.slice(3, 6);
//     if (digitsBeforeCursor > 3)
//       formattedCursorPos += Math.min(digitsBeforeCursor - 3, 3) + 1;
//   }
//   if (pnum.length > 6) {
//     formattedNum += "-" + pnum.slice(6, 10);
//     if (digitsBeforeCursor > 6)
//       formattedCursorPos += Math.min(digitsBeforeCursor - 6, 4) + 1;
//   }
//   if (pnum.length > 10) {
//     formattedNum += "-" + pnum.slice(10, 14);
//     if (digitsBeforeCursor > 10)
//       formattedCursorPos += Math.min(digitsBeforeCursor - 10, 4) + 1;
//   }
//   if (pnum.length > 14) {
//     formattedNum += pnum.slice(14);
//     if (digitsBeforeCursor > 14) formattedCursorPos += digitsBeforeCursor - 14;
//   }

//   element.value = formattedNum;
//   element.setSelectionRange(formattedCursorPos, formattedCursorPos);
// }
