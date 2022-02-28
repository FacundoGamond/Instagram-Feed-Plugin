<?php

/**
 * Plugin Name: Instagram Feed
 * Description: A Simple plugin to get your instagram feed without token expiraton.
 * Version: 1.0.0
 * Author: Facundo Gamond
 * Author URI: https://facundogamond.com.ar
 * Text Domain: instagramfeed
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class InstagramFeed
{
    function __construct()
    {
        add_action('admin_menu', array($this, 'ourMenu'));
        add_shortcode('instagram-feed', array($this, 'getInstagramFeed'));

        add_action('instagram_regenerate_time', 'get_new_token');
        add_filter('cron_schedules', array($this, 'instagram_regenerate_time'));

        // Schedule an action if it's not already scheduled
        if (!wp_next_scheduled('instagram_regenerate_time')) {
            wp_schedule_event(time(), 'regenerate_instagram_weekly', 'instagram_regenerate_time');
        }

        // Hook into that action that'll fire every week
        add_action('instagram_regenerate_time', array($this, 'get_new_token'));
    }

    function ourMenu()
    {
        add_menu_page('Instagram Feed', 'Instagram Feed', 'manage_options', 'instagramfeed', array($this, 'instagramFeedPage'), 'dashicons-instagram', 100);
        add_submenu_page('instagramfeed', 'Instagram Feed Settings', 'General Settings', 'manage_options', 'instagramfeed', array($this, 'instagramFeedPage'));
    }

    function instagramFeedPage()
    { ?>
        <div class="wrap">
            <h1>Instagram Feed</h1>
            <p>Simple plugin for display easily your Instagram Feed and regenerate your IG Token automatically</p>
            <br>
            <h2>Instagram Token</h2>
            <?php if ($_POST['justsubmitted'] == "true") $this->handleForm(); ?>
            <form method="POST" class="instagram-feed">
                <input type="hidden" name="justsubmitted" value="true">
                <?php wp_nonce_field('saveIgToken', 'ourNonce'); ?>
                <label for="plugin-instagram-feed">
                    <p>Paste you instagram token here.</p>
                </label>
                <textarea name="plugin-instagram-feed" id="plugin-instagram-feed" cols="30" rows="1"><?= esc_textarea(get_option('plugin-instagram-feed')); ?></textarea>
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
            </form>

            <h3>Usage</h3>
            <p>Just paste in your post/page text editor this shortcode: [instagram-feed]</p>
            <p>You can enable the slider feature adding the "slider" arg</p>
            <span>example: [instagram-feed slider="true"]</span>
        </div>

        <style>
            .instagram-feed{
                width: 100%;
                display: flex;
                flex-direction: column;
            }

            .instagram-feed input{
                margin-top: 16px !important;
                width: max-content;
            }
        </style>
    <?php }

    function handleForm()
    {
        if (wp_verify_nonce($_POST['ourNonce'], 'saveIgToken') and current_user_can('manage_options')) {
            update_option('plugin-instagram-feed', sanitize_text_field($_POST['plugin-instagram-feed']));
            echo "<div class='updated'>
        <p>Your Filtered Words were save.</p>
        </div>";
        } else {
            echo "<div class='error'>
        <p>Sorry, you do not have permissions to perform thah action.</p>
        </div>";
        }
    }

    function instagram_regenerate_time($schedules)
    {
        $schedules['regenerate_instagram_weekly'] = array(
            'interval'  => 604800,
            'display'   => __('Every Week', 'textdomain')
        );
        return $schedules;
    }

    /**
     * Refresh Instagram App Token
     */
    function get_new_token()
    {
        $token = get_option('plugin-instagram-feed');
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://graph.instagram.com/refresh_access_token?grant_type=ig_refresh_token&access_token=$token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        $tokenres = json_decode($response, true)['access_token'];
        update_option('plugin-instagram-feed', $tokenres);
    }

    function getInstagramFeed($atts)
    {
        if (!is_admin() && isset($atts['slider']) == 'true') {
            wp_enqueue_script('instagram-feed-functions', plugins_url('/assets/build/instagram-feed.min.js', __FILE__), array());
            wp_enqueue_style('instagram-feed-styles', plugins_url('/assets/build/instagram-feed.min.css', __FILE__));
        }
        $token = get_option('plugin-instagram-feed');

        /**
         * Get Instagram Feed
         */
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://graph.instagram.com/me/media?access_token=" . $token . "&fields=media_url,media_type,caption,permalink",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        $igFeed = json_decode($response, true); ?>
        <div class="instagram-feed js-instagram-feed">
            <div class="swiper-wrapper">
                <?php
                foreach ($igFeed['data'] as $value) :
                    $img = $value['media_url'];
                    $caption = $value['caption'];
                ?>
                    <?php if ($value['media_type'] != "VIDEO") : ?>
                        <div class="instagram-feed__post swiper-slide">
                            <img src="<?= esc_url($img); ?>" alt="<?= esc_attr($caption) ?>">
                        </div>
                    <?php
                    endif; ?>
                <?php
                endforeach; ?>
            </div>

            <button class='swiper-button-next js-swiper-button-next'></button>
            <button class='swiper-button-prev js-swiper-button-prev'></button>
            <div class='swiper-pagination js-swiper-pagination'></div>
        </div>
<?php }
}

$instagramFeed = new InstagramFeed();

?>