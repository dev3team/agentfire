<?php

declare( strict_types=1 );

namespace AgentFire\Plugin\Test;

use AgentFire\Plugin\Test\Traits\Singleton;

/**
 * Class Rest
 * @package AgentFire\Plugin\Test
 */
class Shortcode {
    use Singleton;

    public function __construct() {

        $this->cssLoader();
        $this->jsLoader();
        $this->registerTaxonomy();

        add_shortcode( 'agentfire_test', [ $this, 'agentfireShortcode' ] );
    }

    /**
     * Register endpoints
     */
    public static function agentfireShortcode() {
        /** current user (for auth checking) */
        $current_user_id = get_current_user_id();

        /** REST API auth parameter */
        $nonce = wp_create_nonce( 'wp_rest' );

        /** Mapbox token */
        $gl_key = get_field('mapbox_token', 'options');

        Template::getInstance()->display( 'main.twig', array( 'current_user_id' => $current_user_id, 'nonce' => $nonce, 'gl_key' => $gl_key ) );
    }

    /**
     * Create markers post type and tags taxonomy
     */
    private function registerTaxonomy(){
        add_action('init', function () {
            /** markers post type */
            register_post_type( 'markers',
                array(
                    'labels' => array(
                        'name' => __( 'Markers' ),
                        'singular_name' => __( 'Marker' )
                    ),
                    'public' => true,
                    'has_archive' => false,
                    'publicly_queryable' => true,
                    'query_var' => true,
                )
            );

            /** tags taxonomy (marker-tags, because tags is reserved) */
            register_taxonomy(
                'marker-tags',
                'markers',
                array(
                    'label' => __( 'Tags' ),
                    'hierarchical' => false,
                )
            );
        });
    }

    /**
     * Loading CSS
     */
    private function cssLoader() {

        wp_register_style( 'bootstrap',  AGENTFIRE_BASE_NAME . 'bower_components/bootstrap/dist/css/bootstrap.min.css' );
        wp_register_style( 'mapbox',  AGENTFIRE_BASE_NAME . 'assets/css/mapbox-gl.css', false, '2.9.1');
        wp_register_style( 'style',  AGENTFIRE_BASE_NAME . 'assets/css/style.css', false, time());
        wp_register_style( 'chosen',  AGENTFIRE_BASE_NAME . 'bower_components/chosen/chosen.min.css');
        wp_enqueue_style('bootstrap');
        wp_enqueue_style('chosen');
        wp_enqueue_style('mapbox');
        wp_enqueue_style('style');
    }

    /**
     * Loading JS
     */
    private function jsLoader(){
        wp_register_script( 'bootstrap', AGENTFIRE_BASE_NAME . 'bower_components/bootstrap/dist/js/bootstrap.js', ['jquery']);
        wp_register_script( 'mapbox',  AGENTFIRE_BASE_NAME . 'assets/js/mapbox-gl.js', false, '2.9.1');
        wp_register_script( 'script',  AGENTFIRE_BASE_NAME . 'assets/js/script.js', false, time());
        wp_register_script( 'chosen',  AGENTFIRE_BASE_NAME . 'bower_components/chosen/chosen.jquery.min.js', ['jquery']);
        wp_enqueue_script('bootstrap');
        wp_enqueue_script('mapbox');
        wp_enqueue_script('script');
        wp_enqueue_script('chosen');
    }
}
