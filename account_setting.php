<?php
// Account setting(Login, Signup, Forgot password)

// Signup
function handle_custom_user_signup()
{
    if (isset($_POST['name'], $_POST['email'], $_POST['password'])) {
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $password = sanitize_text_field($_POST['password']);

        $base_username = sanitize_user(str_replace(' ', '', strtolower($name)));
        $random_string = bin2hex(random_bytes(3));
        $username = $base_username . '_' . $random_string;

        if (email_exists($email)) {
            wp_send_json_error(array('message' => 'This email is already registered. Please log in.'));
            return;
        }

        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
            return;
        }

        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $name,
            'first_name' => $name,
        ));

        update_user_meta($user_id, 'full_name', $name);

        $user = new WP_User($user_id);
        $user->set_role('customer');

        wp_set_auth_cookie($user_id, true);
        wp_set_current_user($user_id);
        do_action('wp_login', $username, $user);

        wp_send_json_success(array(
            'message' => 'Registration successful! You are now logged in.',
            'redirect_url' => home_url()
        ));
    } else {
        wp_send_json_error(array('message' => 'Missing fields.'));
    }
}
add_action('wp_ajax_custom_user_signup', 'handle_custom_user_signup');
add_action('wp_ajax_nopriv_custom_user_signup', 'handle_custom_user_signup');



// Login    
function handle_custom_user_login()
{
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) ? true : false;

        $user = wp_authenticate($email, $password);

        if (is_wp_error($user)) {
            wp_send_json_error(array(
                'message' => 'Invalid email or password.',
            ));
        } else {
            wp_set_auth_cookie($user->ID, $remember);

            wp_send_json_success(array(
                'redirect_url' => home_url(),
            ));
        }
    } else {
        wp_send_json_error(array(
            'message' => 'Please fill in all fields.',
        ));
    }

    wp_die(); 
}

add_action('wp_ajax_custom_user_login', 'handle_custom_user_login');
add_action('wp_ajax_nopriv_custom_user_login', 'handle_custom_user_login');


// Forgot Password
function handle_forgot_password()
{
    if (isset($_POST['email'])) {
        $email = sanitize_email($_POST['email']);

        if (email_exists($email)) {
            $user = get_user_by('email', $email);

            $new_password = wp_generate_password(12, false);

            wp_set_password($new_password, $user->ID);

            $subject = 'Password Reset Request';
            $message = "Hello " . esc_html($user->display_name) . ",\n\n";
            $message .= "Your password has been reset. Here is your new password:\n\n";
            $message .= "** " . esc_html($new_password) . " **\n\n";
            $message .= "Please log in and change your password as soon as possible.\n\n";
            $message .= "Thank you,\n" . get_bloginfo('name');

            $headers = array('Content-Type: text/plain; charset=UTF-8');

            if (wp_mail($email, $subject, $message, $headers)) {
                wp_send_json_success(array('message' => 'A new password has been sent to your email.'));
            } else {
                wp_send_json_error(array('message' => 'Email could not be sent. Please try again later.'));
            }
        } else {
            wp_send_json_error(array('message' => 'No account found with this email.'));
        }
    } else {
        wp_send_json_error(array('message' => 'Please enter a valid email address.'));
    }

    exit;
}

add_action('wp_ajax_forgot_password', 'handle_forgot_password');
add_action('wp_ajax_nopriv_forgot_password', 'handle_forgot_password');


// Delete the account

function handle_delete_account()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in to delete your account.'));
        exit;
    }

    $user_id = get_current_user_id(); 

    if ($user_id) {
        require_once(ABSPATH . 'wp-admin/includes/user.php'); 

        wp_logout();

        wp_delete_user($user_id);

        wp_send_json_success(array(
            'message' => 'Your account has been successfully deleted.',
            'redirect_url' => home_url() 
        ));
    } else {
        wp_send_json_error(array('message' => 'Error: User not found.'));
    }

    exit;
}

add_action('wp_ajax_delete_account', 'handle_delete_account');



// Button 
function pass_login_status_to_js()
{
    wp_localize_script('custom-validation', 'user_status', array(
        'is_logged_in' => is_user_logged_in() ? true : false
    ));
}
add_action('wp_enqueue_scripts', 'pass_login_status_to_js');



function handle_user_logout()
{

    if (is_user_logged_in()) {
        wp_logout();
        wp_send_json_success(array(
            'message' => 'You have been logged out.',
            'redirect_url' => home_url()
        ));
    } else {
        wp_send_json_error(array(
            'message' => 'You are not logged in.'
        ));
    }
    exit;
}

add_action('wp_ajax_user_logout', 'handle_user_logout');


// Filter
add_action('wp_ajax_filter_products', 'custom_filter_products_ajax');
add_action('wp_ajax_nopriv_filter_products', 'custom_filter_products_ajax');

function custom_filter_products_ajax() {
    $filters = $_POST['filters'] ?? [];

    $tax_query = [];

    foreach ($filters as $taxonomy => $term_ids) {
        if (!empty($term_ids)) {
            $tax_query[] = [
                'taxonomy' => $taxonomy,
                'field' => 'term_id',
                'terms' => array_map('intval', $term_ids),
            ];
        }
    
    if (empty($tax_query)) {
        $current_cat = intval($_POST['current_term_id'] ?? 0);
        if ($current_cat) {
            $tax_query[] = [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => [$current_cat],
            ];
        }
    }
    }
    $query_args = [
        'post_type' => 'product',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'tax_query' => $tax_query,
    ];

    $query = new WP_Query($query_args);

    ob_start();

    if ($query->have_posts()) :
        while ($query->have_posts()) : $query->the_post(); ?>
            <div class="product_item" data-aos="fade-up">
                <a href="<?php the_permalink(); ?>">
                    <figure>
                        <?php
                        if (has_post_thumbnail()) :
                            the_post_thumbnail('full');
                        else :
                            echo '<img src="' . wc_placeholder_img_src() . '" alt="' . esc_attr(get_the_title()) . '">';
                        endif;
                        ?>
                    </figure>
                    <h4><?php the_title(); ?></h4>
                </a>
            </div>
        <?php endwhile;
    else :
        echo '<p>No products found for the selected filters.</p>';
    endif;

    wp_reset_postdata();

    $html = ob_get_clean();

    wp_send_json_success(['html' => $html]);
}




// price update

// Enqueue script & localize gold price data
add_action('wp_footer', function () {
    if (!is_product()) return;

    global $product;
    if (!$product) return;

    $product_id = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
    $gold_price = floatval(get_field('gold_price', 'option'));
    $labour_cost = floatval(get_field('labor_cost', $product_id) ?: 0);
    $enabled = get_field('enable_gold_pricing', 'option');

    if (!$enabled || !$gold_price) return;

    echo '<script>var goldData = ' . wp_json_encode([
        'gold_price'        => $gold_price,
        'labour_cost'       => $labour_cost,
        'currency_symbol'   => get_woocommerce_currency_symbol(),
        'currency_position' => get_option('woocommerce_currency_pos'),
        'price_decimals'    => wc_get_price_decimals(),
        'gold_enabled'      => true,
    ]) . ';</script>';
}, 100);

// Calculate gold-based price
function calculate_gold_price($price, $product) {
    if (!$product || !$product->is_type('variation')) return $price;

    $desc = $product->get_description();
    if (preg_match('/(\d+(\.\d+)?)\s*gram[s]*/i', $desc, $matches)) {
        $weight = floatval($matches[1]);
        $gold_price = floatval(get_field('gold_price', 'option'));
        $product_id = $product->get_parent_id();
        $labour_cost = floatval(get_field('labor_cost', $product_id) ?: 0);
        return ( $labour_cost +  $gold_price) * $weight;
    }

    return $price;
}

add_filter('woocommerce_product_get_price', 'calculate_gold_price', 20, 2);
add_filter('woocommerce_product_get_regular_price', 'calculate_gold_price', 20, 2);
add_filter('woocommerce_product_variation_get_price', 'calculate_gold_price', 20, 2);
add_filter('woocommerce_product_variation_get_regular_price', 'calculate_gold_price', 20, 2);

// Cache calculated gold price on variation save
add_action('woocommerce_update_product_variation', function ($variation_id) {
    if (!get_field('enable_gold_pricing', 'option')) return;

    $product = wc_get_product($variation_id);
    if (!$product || !$product->is_type('variation')) return;

    $description = $product->get_description();
    if (preg_match('/(\d+(\.\d+)?)\s*gram[s]*/i', $description, $matches)) {
        $weight = floatval($matches[1]);
        $gold_price = floatval(get_field('gold_price', 'option'));
        $product_id = $product->get_parent_id();
        $labour_cost = floatval(get_field('labor_cost', $product_id) ?: 0);
        $calculated_price = ( $labour_cost +  $gold_price) * $weight;
        update_post_meta($variation_id, '_calculated_gold_price', $calculated_price);
    }
});

// Price override using cached value
function override_gold_price($price, $product) {
    if (!get_field('enable_gold_pricing', 'option')) return $price;
    if (!$product->is_type('variation')) return $price;

    $cached_price = get_post_meta($product->get_id(), '_calculated_gold_price', true);
    return $cached_price !== '' ? floatval($cached_price) : $price;
}

add_filter('woocommerce_product_get_price', 'override_gold_price', 20, 2);
add_filter('woocommerce_product_get_regular_price', 'override_gold_price', 20, 2);
add_filter('woocommerce_product_variation_get_price', 'override_gold_price', 20, 2);
add_filter('woocommerce_product_variation_get_regular_price', 'override_gold_price', 20, 2);

// Set correct gold price in cart
add_action('woocommerce_before_calculate_totals', function ($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;
    if (!get_field('enable_gold_pricing', 'option')) return;

    $gold_price = floatval(get_field('gold_price', 'option'));

    foreach ($cart->get_cart() as $item) {
        if (!isset($item['data']) || !is_a($item['data'], 'WC_Product_Variation')) continue;

        $product = $item['data'];
        $description = $product->get_description();

        if (preg_match('/(\d+(\.\d+)?)\s*gram[s]*/i', $description, $matches)) {
            $weight = floatval($matches[1]);
            $product_id = $product->get_parent_id();
            $labour_cost = floatval(get_field('labor_cost', $product_id) ?: 0);
            $new_price = ( $labour_cost + $gold_price) * $weight;
            $product->set_price($new_price);
        }
    }
});

// Simple product

// Add gold data and price calculation script to the footer
add_action('wp_footer', function () {
    if (!is_product()) return;

    global $product;
    if (!$product) return;

    $product_id = $product->get_id();
    $gold_price = floatval(get_field('gold_price', 'option'));
    $labour_cost = floatval(get_field('labor_cost', $product_id) ?: 0);
    $enabled = get_field('enable_gold_pricing', 'option');

    if (!$enabled || !$gold_price) return;

    // Extract weight from product description
    $desc = $product->get_description();
    $weight = 0;
    if (preg_match('/(\d+(\.\d+)?)\s*gram[s]*/i', $desc, $matches)) {
        $weight = floatval($matches[1]);
    }

    // Calculate the price based on weight, gold price, and labor cost
    $calculated_price = ($labour_cost + $gold_price) * $weight;

    // Pass gold data and calculated price to JavaScript
    echo '<script>
        var goldData = ' . wp_json_encode([
            'gold_price'        => $gold_price,
            'labour_cost'       => $labour_cost,
            'currency_symbol'   => get_woocommerce_currency_symbol(),
            'currency_position' => get_option('woocommerce_currency_pos'),
            'price_decimals'    => wc_get_price_decimals(),
            'gold_enabled'      => true,
            'calculated_price'  => $calculated_price, // Pass calculated price
        ]) . ';
    </script>';
}, 100);


// Calculate gold-based price for single product
if (!function_exists('calculate_gold_price_single_product')) {
    // Calculate gold-based price for single product
    function calculate_gold_price_single_product($price, $product) {
        if (!$product) return $price;

        // Extract the weight from the product description
        $desc = $product->get_description();
        if (preg_match('/(\d+(\.\d+)?)\s*gram[s]*/i', $desc, $matches)) {
            $weight = floatval($matches[1]);
            $gold_price = floatval(get_field('gold_price', 'option'));
            $labour_cost = floatval(get_field('labor_cost', $product->get_id()) ?: 0);

            // Return the price calculated based on the weight
            return ($labour_cost + $gold_price) * $weight;
        }

        // If no weight found in description, return the default price
        return $price;
    }
}

add_filter('woocommerce_product_get_price', 'calculate_gold_price_single_product', 20, 2);
add_filter('woocommerce_product_get_regular_price', 'calculate_gold_price_single_product', 20, 2);



// Display sku below the title
add_action('woocommerce_single_product_summary', 'display_sku_below_title', 6);
 
function display_sku_below_title() {
    global $product;
 
    if (!$product->get_sku()) {
        return; // Skip if no SKU
    }
 
    echo '<p class="product-sku">SKU: ' . esc_html($product->get_sku()) . '</p>';
}

// Plus minus next to quantity
add_action( 'woocommerce_before_quantity_input_field', 'bbloomer_display_quantity_minus' );
 
function bbloomer_display_quantity_minus() {
   if ( ! is_product() ) return;
   echo '<button type="button" class="minus" >-</button>';
}
 
add_action( 'woocommerce_after_quantity_input_field', 'bbloomer_display_quantity_plus' );
 
function bbloomer_display_quantity_plus() {
   if ( ! is_product() ) return;
   echo '<button type="button" class="plus" >+</button>';
}
 
add_action( 'woocommerce_before_single_product', 'bbloomer_add_cart_quantity_plus_minus' );
 
function bbloomer_add_cart_quantity_plus_minus() {
   wc_enqueue_js( "
      $('form.cart').on( 'click', 'button.plus, button.minus', function() {
            var qty = $( this ).closest( 'form.cart' ).find( '.qty' );
            var val   = parseFloat(qty.val());
            var max = parseFloat(qty.attr( 'max' ));
            var min = parseFloat(qty.attr( 'min' ));
            var step = parseFloat(qty.attr( 'step' ));
            if ( $( this ).is( '.plus' ) ) {
               if ( max && ( max <= val ) ) {
                  qty.val( max );
               } else {
                  qty.val( val + step );
               }
            } else {
               if ( min && ( min >= val ) ) {
                  qty.val( min );
               } else if ( val > 1 ) {
                  qty.val( val - step );
               }
            }
         });
   " );
}

// Shop page redirect
function custom_shop_page_redirect() {
    if( is_shop() ){
        wp_redirect( home_url( '/' ) );
        exit();
    }
}
add_action( 'template_redirect', 'custom_shop_page_redirect' );

// Limit cart product
/**
 * When an item is added to the cart, check total cart quantity
 */
function so_21363268_limit_cart_quantity( $valid, $product_id, $quantity ) {

    $max_allowed = 6;
    $current_cart_count = WC()->cart->get_cart_contents_count();

    if( ( $current_cart_count > $max_allowed || $current_cart_count + $quantity > $max_allowed ) && $valid ){
        wc_add_notice( sprintf( __( 'Whoa hold up. You can only have %d items in your cart', 'your-plugin-textdomain' ), $max_allowed ), 'error' );
        $valid = false;
    }

    return $valid;

}
add_filter( 'woocommerce_add_to_cart_validation', 'so_21363268_limit_cart_quantity', 10, 3 );
