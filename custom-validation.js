jQuery(document).ready(function ($) {
    // Add custom validation methods
    $.validator.addMethod("customEmail", function (value, element) {
        return /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(value);
    }, "Enter a valid email address.");


    $.validator.addMethod("passwordComplexityUppercase", function (value, element) {
        return /[A-Z]/.test(value);
    }, "Password must contain at least one uppercase letter.");

    $.validator.addMethod("passwordComplexityLowercase", function (value, element) {
        return /[a-z]/.test(value);
    }, "Password must contain at least one lowercase letter.");

    $.validator.addMethod("passwordComplexityDigit", function (value, element) {
        return /\d/.test(value);
    }, "Password must contain at least one digit.");

    $.validator.addMethod("passwordComplexitySpecial", function (value, element) {
        return /[\W_]/.test(value);
    }, "Password must contain at least one special character.");

    // Initialize form validation
    $("#signup-form").validate({
        rules: {
            name: {
                required: true,
                minlength: 3
            },
            email: {
                required: true,
                customEmail: true
            },
            password: {
                required: true,
                minlength: 12,
                passwordComplexityUppercase: true,
                passwordComplexityLowercase: true,
                passwordComplexityDigit: true,
                passwordComplexitySpecial: true
            },
            terms: {
                required: true
            }
        },
        messages: {
            name: {
                required: "Please enter your name",
                minlength: "Your name must be at least 3 characters long"
            },
            email: {
                required: "Please enter your email",
                email: "Please enter a valid email address"
            },
            password: {
                required: "Please provide a password",
                minlength: "Password must be at least 12 characters long"
            },
            terms: {
                required: "You must agree to the terms"
            }
        },
        submitHandler: function (form) {
            var formData = $(form).serialize();
            $("#signup-message").text("Processing...").show();
            $("#cst-signup").prop("disabled", true).text("Processing...");



            $.ajax({
                type: "POST",
                url: ajax_object.ajaxurl,
                data: formData + "&action=custom_user_signup",

                success: function (response) {
                    if (response.success) {
                        $("#signup-message").text("Registration successful! Redirecting...").show();
                        setTimeout(function () {
                            window.location.href = response.data.redirect_url;
                        }, 2000);
                        toastr.success(response.data.message, 'Success');
                    } else {
                        var errorMessage = response.data.message;
                        var fieldMatched = false;
                        $("#signup-form input").each(function () {
                            var fieldId = $(this).attr('id');
                            var fieldName = $(this).attr('name');
                            $("#" + fieldId).next(".error-message").remove();

                            if (errorMessage.toLowerCase().includes(fieldName.toLowerCase())) {
                                fieldMatched = true;
                                $("#" + fieldId).after('<p class="error-message">' + errorMessage + '</p>');
                            }
                        });

                        if (!fieldMatched) {
                            alert(errorMessage);
                        }
                    }
                },
                complete: function () {
                    $("#cst-signup").prop("disabled", false).text("Sign Up");
                }
            });
        }
    });

    // Force email validation on blur
    $("#email").on("blur", function () {
        $(this).valid();
    });
});



// Login Ajax
jQuery(document).ready(function ($) {
    // Add custom email validation method (same as signup)
    $.validator.addMethod("customEmail", function (value, element) {
        return /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(value);
    }, "Enter a valid email address.");

    // Initialize validation
    $('#custom-login-form1').validate({
        rules: {
            email: {
                required: true,
                customEmail: true
            },
            password: {
                required: true
            }
        },
        messages: {
            email: {
                required: "Please enter your email",
                customEmail: "Please enter a valid email address"
            },
            password: {
                required: "Please enter your password"
            }
        },
        submitHandler: function (form) {
            // AJAX login only if form is valid
            var email = $('#email').val();
            var password = $('#password').val();
            var remember = $('#remember').prop('checked') ? 1 : 0;

            $("#login-error-message").text('').hide();
            $("#custom-login-form1 input").each(function () {
                $(this).next(".error-message").remove();
            });

            $.ajax({
                url: ajax_object.ajaxurl,
                type: 'POST',
                data: {
                    action: 'custom_user_login',
                    email: email,
                    password: password,
                    remember: remember
                },
                beforeSend: function () {
                    $('#cst-login').attr('disabled', 'disabled').text('Logging in...');
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.data.message, 'Success');
                        var redirectUrl = response.data.redirect_url || '/';
                        setTimeout(function () {
                            window.location.href = redirectUrl;
                        }, 1000);
                    } else {
                        $("#login-error-message").text(response.data.message).show().css("color", "red");
                    }
                },
                error: function () {
                    $("#login-error-message").text('An error occurred. Please try again.').show().css("color", "red");
                },
                complete: function () {
                    $('#cst-login').removeAttr('disabled').text('Log in');
                }
            });
        }
    });

    // Remove error messages on input
    $("#custom-login-form1 input").on('input', function () {
        $(this).next(".error-message").remove();
    });
});


// Forgot password
jQuery(document).ready(function ($) {
    $('#forgot-password-form').on('submit', function (e) {
        e.preventDefault();

        var email = $('#email').val();

        $("#forgot-password-message").text('').hide();

        $.ajax({
            url: ajax_object.ajaxurl,
            type: 'POST',
            data: {
                action: 'forgot_password',
                email: email
            },
            beforeSend: function () {
                $('#forgot-password-message').text('Processing...').css('color', 'blue').show();
            },
            success: function (response) {
                if (response.success) {
                    $('#forgot-password-message').text(response.data.message).css('color', 'green').show();
                } else {
                    $('#forgot-password-message').text(response.data.message).css('color', 'red').show();
                }
            },
            error: function () {
                $('#forgot-password-message').text('An error occurred. Please try again later.').css('color', 'red').show();
            }
        });
    });
});

//  Delete account ajax query
jQuery(document).ready(function ($) {
    $('#delete-account-btn-nav').on('click', function (e) {
        e.preventDefault();

        if (!confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
            return;
        }

        $.ajax({
            url: ajax_object.ajaxurl,
            type: 'POST',
            data: { action: 'delete_account' },
            beforeSend: function () {
                $('#delete-account-btn-nav').text('Deleting...');
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.data.message, 'Success');
                    window.location.href = response.data.redirect_url; // Redirect to homepage after successful account deletion
                } else {
                    toastr.error(response.data.message, 'Error');
                }
            },
            error: function () {
                toastr.error('An error occurred. Please try again.', 'Error');
            }
        });
    });
});



// Logout
jQuery(document).ready(function ($) {
    $('#logout-btn-nav').on('click', function (e) {
        e.preventDefault();

        $.ajax({
            url: ajax_object.ajaxurl, // The URL where the AJAX request is sent
            type: 'POST',
            data: { action: 'user_logout' }, // Action hook to trigger logout functionality
            beforeSend: function () {
                $('#logout-btn-nav').text('Logging out...'); // Change button text while processing
            },
            success: function (response) {
                if (response.success) {
                    // Show success message with Toastr
                    toastr.success(response.data.message, 'Success');

                    // Redirect after logout
                    setTimeout(function () {
                        window.location.href = response.data.redirect_url; // Redirect to homepage after logout
                    }, 2000); // 2-second delay for user to see the success message
                } else {
                    // Show error message with Toastr
                    toastr.error(response.data.message, 'Error');
                }
            },
            error: function () {
                // Show error message if something goes wrong with the AJAX request
                toastr.error('An error occurred. Please try again.', 'Error');
            },
            complete: function () {
                $('#logout-btn-nav').text('Logout'); // Reset the button text after the AJAX request completes
            }
        });
    });
});




// // Buttons Hide and show
// jQuery(document).ready(function ($) {
//     if (user_status.is_logged_in) {
//         $('[data-user-status="guest"]').hide();
//     } else {
//         $('[data-user-status="logged-in"]').hide();
//     }
// });

// filter ajax
jQuery(document).ready(function ($) {
    function loadFilteredProducts(filters = {}) {
        $.ajax({
            url: ajax_object.ajaxurl,
            type: 'POST',
            data: {
                action: 'filter_products',
                filters: filters
            },
            beforeSend: function () {
                $('#product-list').addClass('loading');
            },
            success: function (response) {
                $('#product-list').removeClass('loading');
                if (response.success) {
                    $('#product-list').html(response.data.html);
                } else {
                    $('#product-list').html('<p>No products found.</p>');
                }
            },
            error: function () {
                $('#product-list').removeClass('loading');
                $('#product-list').html('<p>There was an error processing your request.</p>');
            }
        });
    }

$('.filter-list input[type="checkbox"]').on('change', function () {
    let filters = {};

    $('.filter-list input[type="checkbox"]:checked').each(function () {
        const taxonomy = $(this).attr('name').replace(/^filter_|\[\]/g, '');
        if (!filters[taxonomy]) filters[taxonomy] = [];
        filters[taxonomy].push($(this).val());
    });
console.log(filters);
    // If no filters are checked, default to current category
    // if (Object.keys(filters).length === 0) {
        const cusID = $("#current-term-id-cus").val();
        if (cusID) {
            filters['product_cat'] = [cusID];
        }
    // }

    loadFilteredProducts(filters);
});


});


// Dynmic gold price
// This is for variation

jQuery(function($) {
    if (!goldData.gold_enabled) return;

    function formatPrice(price) {
        price = parseFloat(price).toFixed(goldData.price_decimals);
        switch (goldData.currency_position) {
            case 'left': return goldData.currency_symbol + price;
            case 'right': return price + goldData.currency_symbol;
            case 'left_space': return goldData.currency_symbol + ' ' + price;
            case 'right_space': return price + ' ' + goldData.currency_symbol;
            default: return price;
        }
    }

    function updateVariationPrice(variation) {
        if (!variation || !variation.description) return;

        let match = variation.description.match(/(\d+(\.\d+)?)\s*gram[s]*/i);
        if (!match) return;

        let weight = parseFloat(match[1]);
        let goldPrice = parseFloat(goldData.gold_price);
        let labourCost = parseFloat(goldData.labour_cost); // Added this line for labour cost
        let finalPrice = (labourCost + goldPrice) * weight ; // Updated this line to add labour cost
        let formattedPrice = formatPrice(finalPrice);

        // Update the price dynamically on the product page
        $('.woocommerce-variation-price .price, .woocommerce-Price-amount').text(formattedPrice);
    }

    // Use event delegation to bind to any variation form on the page
    $(document).on('found_variation', 'form.variations_form', function(e, variation) {
        updateVariationPrice(variation);
    });

    // Trigger the price update when the page is initially loaded
    $(document).ready(function() {
        // Manually trigger the price update for the default variation (if selected)
        var initialVariation = $('form.variations_form').data('variation');
        if (initialVariation) {
            updateVariationPrice(initialVariation);
        }
    });
});
// For single product
jQuery(function($) {
    // Exit if gold pricing is not enabled
    if (!goldData.gold_enabled) return;

    // Function to format the price based on currency settings
    function formatPrice(price) {
        price = parseFloat(price).toFixed(goldData.price_decimals);
        switch (goldData.currency_position) {
            case 'left': return goldData.currency_symbol + price;
            case 'right': return price + goldData.currency_symbol;
            case 'left_space': return goldData.currency_symbol + ' ' + price;
            case 'right_space': return price + ' ' + goldData.currency_symbol;
            default: return price;
        }
    }

    // Function to update the product price based on weight and gold pricing
    function updateProductPrice() {
        var weight = parseFloat($('.product-description').text().match(/(\d+(\.\d+)?)\s*gram[s]*/i)?.[1]);

        // If weight is found, calculate the price based on weight
        if (weight) {
            var finalPrice = (goldData.labour_cost + goldData.gold_price) * weight;
            var formattedPrice = formatPrice(finalPrice);

            // Update the price in the product page
            $('.single_product_price_updated').text(formattedPrice);
        }
    }

    // Trigger the price update when the page is initially loaded
    $(document).ready(function() {
        updateProductPrice();
    });
});



// Replace SKU text
jQuery(document).ready(function($) {
    var skuElement = $('.product_meta .sku'); // Selects the SKU span
    if (skuElement.length) {
        skuElement.parent().html('<p>Sold by weight based on the current gold price.</p>');
    }
});

// Referesh cart count
jQuery(function($) {
    $('body').on('added_to_cart removed_from_cart', function() {
        var cartCount = $('#cart-count');
        var newCount = parseInt(cartCount.text());

        $.get('/?wc-ajax=get_cart_contents_count', function(response) {
            if (response) {
                cartCount.text(response);
            }
        });
    });
});


// Referesh cart count

 

// Search disable
 document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search-input');
        const searchForm = document.getElementById('search-form');
        
        // Disable the submit if the search input is empty
        searchForm.addEventListener('submit', function(event) {
            if (searchInput.value.trim() === '') {
                event.preventDefault(); // Prevent form submission
            }
        });
    });
