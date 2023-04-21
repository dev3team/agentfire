<?php

declare( strict_types=1 );

namespace AgentFire\Plugin\Test;

use AgentFire\Plugin\Test\Traits\Singleton;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class Rest
 * @package AgentFire\Plugin\Test
 */
class Rest {
	use Singleton;

	/**
	 * @var string Endpoint namespace
	 */
	const NAMESPACE = 'agentfire/v1/';

	/**
	 * @var string Route base
	 */
	const REST_BASE = 'test';

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'registerRoutes' ] );
	}

	/**
	 * Register endpoints
	 */
	public static function registerRoutes() {
        /** route for getting or adding markers (GET, POST) */
		register_rest_route( self::NAMESPACE, self::REST_BASE . '/markers', [
			'show_in_index' => false,
			'methods'       => [ WP_REST_Server::READABLE, WP_REST_Server::CREATABLE ],
			'callback'      => [ self::class, 'markers' ],
			'args'          => [],

		] );

        /** route for getting tags (GET) */
        register_rest_route( self::NAMESPACE, self::REST_BASE . '/tags', [
            'show_in_index' => false,
            'methods'       => [ WP_REST_Server::READABLE ],
            'callback'      => [ self::class, 'tags' ],
            'args'          => [],

        ] );

        /** route for saving admin form (POST) */
        register_rest_route( self::NAMESPACE, self::REST_BASE . '/key', [
            'show_in_index' => false,
            'methods'       => [ WP_REST_Server::CREATABLE ],
            'callback'      => [ self::class, 'saveKey' ],
            'args'          => [],
        ] );
	}

	/**
     * Callback for /markers routes (create or get markers)
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public static function markers( WP_REST_Request $request ) {
        /** request method */
        $method = $request->get_method();

        /** Checking request method */
        if ( $method == WP_REST_Server::CREATABLE ) {

            $data = $request->get_json_params();

            /** prepare data before save */
            $insert_data = array(
                'post_type'   => 'markers',
                'post_status' => 'publish',
                'post_title'  => $data['name'],
                'post_author' => get_current_user_id(),
                'meta_input'  => array(
                    'lat' => $data['lat'],
                    'lng' => $data['lng']
                )
            );

            $tags = isset($data['tags']) ? explode(',', trim($data['tags'])) : array();

            /** saving marker in DB */
            $post_id = wp_insert_post($insert_data);

            /** saving tags */
            if ( !empty($tags) ) {
                wp_set_object_terms( $post_id, $tags, 'marker-tags' );
            }

            return new WP_REST_Response(
                array(
                    'success' => true,
                )
            );
        } else {
            $filters = $request->get_query_params();

            $posts_data = array(
                'post_type' => 'markers',
                'posts_per_page' => -1
            );

            $my_id = get_current_user_id();

            /** search */
            if ( isset($filters['search']) && $filters['search'] ) {
                $posts_data['s'] = $filters['search'];
            }

            /** filter - own markers only */
            if ( isset($filters['my_only']) && $filters['my_only'] ) {
                $posts_data['author'] = $my_id;
            }

            /** filter by tags */
            if ( isset($filters['tags']) && !empty($filters['tags']) ) {
                $posts_data['tax_query'] = array(
                    array(
                        'taxonomy' => 'marker-tags',
                        'field'    => 'slug',
                        'terms'    => $filters['tags'],
                        'include_children' => true,
                        'operator' => 'IN'
                    )
                );
            }

            /** loading markers from DB */
            $markers_data = get_posts($posts_data);

            $results = array();

            if ( !empty($markers_data) ) {
                foreach ($markers_data as $marker) {
                    /** get marker meta (is need to get coordinates) */
                    $meta = get_post_meta($marker->ID);

                    /** check if meta fields with coordinates exists */
                    if ( !isset($meta['lat'][0], $meta['lng'][0]) ) continue;

                    $res = array(
                        'id'    => $marker->ID,
                        'name'  => $marker->post_title,
                        'lat'   => $meta['lat'][0],
                        'lng'   => $meta['lng'][0],
                        'date'  => $marker->post_date,
                        'is_my' => $marker->post_author == $my_id,
                        'author' => $marker->post_author,
                        'tags'  => ''
                    );

                    /** loading tags */
                    $tags = get_the_terms($marker->ID, 'marker-tags');

                    $tag_names = array();
                    if ( !empty($tags) ) foreach ($tags as $tag) {
                        array_push($tag_names, $tag->name);
                    }
                    $res['tags'] = implode(', ', $tag_names);

                    array_push($results, $res);
                }
            }

            return new WP_REST_Response( $results );
        }
	}

    public static function tags( WP_REST_Request $request ) {
        $tags = get_terms( array(
            'taxonomy'   => 'marker-tags',
            'hide_empty' => false,
        ) );

        $results = array();
        if ( !empty($tags) ) foreach ($tags as $tag) {
            array_push($results, array(
                'id' => $tag->term_id,
                'slug' => $tag->slug,
                'name' => $tag->name,
            ) );
        }

        return new WP_REST_Response( $results );
    }

    /**
     * Processing saving fields from admin form
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function saveKey( WP_REST_Request $request ) {
        /** Restrict access for unauthorized or non-admin users */
        if ( !is_user_logged_in() || !current_user_can('administrator') ) return new WP_REST_Response(
            array(
                'success' => false,
                'message' => 'You have not access'
            )
        );

        $data = $request->get_json_params();

        foreach ($data as $k => $v) {
            /** inputs filtering (only for ACF fields) */
            if ( strpos($k, 'acf[') === false ) continue;

            $field_name = str_replace(['acf[', ']'], '', $k);

            /** save or update ACF field value */
            update_field($field_name, $v, 'options');
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => 'ACF data was saved'
            )
        );
    }

}
