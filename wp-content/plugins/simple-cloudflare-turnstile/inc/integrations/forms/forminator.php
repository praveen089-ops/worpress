<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if(get_option('cfturnstile_forminator')) {

	// Get turnstile field: Forminator Forms
	add_filter( 'forminator_render_form_submit_markup', 'cfturnstile_field_forminator_form', 10, 4 );
	function cfturnstile_field_forminator_form( $html, $form_id, $post_id, $nonce ) {

        if(!cfturnstile_form_disable($form_id, 'cfturnstile_forminator_disable')) {

            ob_start();

            // if cfturnstile script doesnt exist, enqueue it
            if(!wp_script_is('cfturnstile', 'enqueued')) {
                wp_register_script("cfturnstile", "https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit", array(), '', 'true');
                wp_print_scripts('cfturnstile');
            }
            echo "<style>#cf-turnstile-fmntr-".esc_html($form_id)." { margin-left: 0px !important; }</style>";

            cfturnstile_field_show('.forminator-button-submit', 'turnstileForminatorCallback', 'forminator-form-' . esc_html($form_id), '-fmntr-' . esc_html($form_id));
            ?>
            <script>
            // On ajax.complete run turnstile.render if element is empty
            jQuery(document).ajaxComplete(function() {
                setTimeout(function() {
                    if (document.getElementById('cf-turnstile-fmntr-<?php echo esc_html($form_id); ?>')) {
                        if(!document.getElementById('cf-turnstile-fmntr-<?php echo esc_html($form_id); ?>').innerHTML.trim()) {
                                turnstile.remove('#cf-turnstile-fmntr-<?php echo esc_html($form_id); ?>');
                                turnstile.render('#cf-turnstile-fmntr-<?php echo esc_html($form_id); ?>');
                        }
                    }
                }, 1000);
            });
            // Enable Submit Button Function
            function turnstileForminatorCallback() {
                document.querySelectorAll('.forminator-button, .forminator-button-submit').forEach(function(el) {
                    el.style.pointerEvents = 'auto';
                    el.style.opacity = '1';
                });
            }
            // On submit re-render
            jQuery(document).ready(function() {
                jQuery('.forminator-custom-form').on('submit', function() {
                    if(document.getElementById('cf-turnstile-fmntr-<?php echo esc_html($form_id); ?>')) {
                        setTimeout(function() {
                            turnstile.remove('#cf-turnstile-fmntr-<?php echo esc_html($form_id); ?>');
                            turnstile.render('#cf-turnstile-fmntr-<?php echo esc_html($form_id); ?>');
                        }, 1000);
                    }
                });
            });
            </script>
            <?php
            $cfturnstile = ob_get_contents();
            ob_end_clean();
            wp_reset_postdata();

            if(!empty(get_option('cfturnstile_forminator_pos')) && get_option('cfturnstile_forminator_pos') == "after") {
                return $html . $cfturnstile;
            } else {
                return $cfturnstile . $html;
            }

        } else {
            return $html;
        }

	}

	// Forminator Forms Check
	add_action('forminator_custom_form_submit_errors', 'cfturnstile_forminator_check', 10, 3);
	function cfturnstile_forminator_check($submit_errors, $form_id, $field_data_array){
        if(!cfturnstile_form_disable($form_id, 'cfturnstile_forminator_disable')) {
            $check = cfturnstile_check();
            $success = $check['success'];
            if($success != true) {
                $submit_errors[]['submit'] = cfturnstile_failed_message();
            }
        }
        return $submit_errors;
	}

}