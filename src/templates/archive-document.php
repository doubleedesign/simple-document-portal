<?php
get_header();

if(!is_user_logged_in()) {
	wp_login_form();
}

// TODO: Populate archive page

get_footer();
