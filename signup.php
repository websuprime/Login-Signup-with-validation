<?php
/* Template Name: Sign Up */ 
get_header() ?>
<!-- Main Section Here -->
<main class="main">

    <section class="signup_section">
        <div class="container">
            <div class="signup_form">

                <form id="signup-form" method="post">
                    <h3>Create your Account</h3>

                    <button class="google_btn">
                        <img src="<?php echo get_template_directory_uri(); ?>/assets-chic/images/google-icon-logo.svg" alt=""> Signup with Google
                    </button>
                    <span class="second_option">or</span>
                    <div class="form-group">
                        <label for="name">Name<span>*</span></label>
                        <input type="text" name="name" id="name" class="form-control" placeholder="johndoe" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email<span>*</span></label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="johndoe790@gmail.com" required>
                    </div>
                    <div class="form-group password">
                        <label for="password">Password<span>*</span></label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="******************" required>
                    </div>
                    <div class="form-group agree">
                        <input type="checkbox" name="agree" id="terms" class="form-control" required>
                        <label for="agree">I agree to all Terms, Privacy Policy</label>
                    </div>
                    <button type="submit" class="submit_btn" data-ajax-url="<?php echo admin_url('admin-ajax.php'); ?>"id="cst-signup">SIGN UP</button>
                    <p class="login_page">Already have an account? <a href="/login">Login</a></p>
                </form>
            </div>
        </div>
    </section>
</main>

<?php get_footer(); ?>