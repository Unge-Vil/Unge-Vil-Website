<?php
/**
 * Server-side rendering for the Erfaringer block.
 *
 * @package UV_Core
 */

declare(strict_types=1);

if ( ! function_exists( 'uv_core_render_experiences_block' ) ) {
    /**
     * Render callback for the experiences block.
     *
     * @param array $attributes Block attributes.
     * @return string
     */
    function uv_core_render_experiences_block( array $attributes ): string {
        $defaults   = [
            'count'      => 3,
            'layout'     => 'grid',
            'pagination' => false,
        ];
        $attributes = wp_parse_args( $attributes, $defaults );

        $count  = max( 1, (int) $attributes['count'] );
        $layout = in_array( $attributes['layout'], [ 'list', 'grid', 'timeline' ], true ) ? $attributes['layout'] : 'grid';

        $paged         = ! empty( $attributes['pagination'] ) ? max( 1, (int) get_query_var( 'paged', 1 ) ) : 1;
        $offset        = max( 0, ( $paged - 1 ) * $count );
        $should_paginate = ! empty( $attributes['pagination'] );

        $query = new WP_Query(
            [
                'post_type'      => 'uv_experience',
                'posts_per_page' => $count,
                'paged'          => $paged,
                'offset'         => $offset,
                'no_found_rows'  => ! $should_paginate,
            ]
        );

        ob_start();

        if ( $query->have_posts() ) {
            $wrapper_classes = [ 'uv-experiences', 'uv-experiences--' . $layout ];
            if ( 'grid' === $layout || 'timeline' === $layout ) {
                $wrapper_classes[] = 'uv-card-list';
            }
            if ( 'grid' === $layout ) {
                $wrapper_classes[] = 'uv-card-grid';
                $wrapper_classes[] = 'columns-3';
            }

            echo '<ul class="' . esc_attr( implode( ' ', $wrapper_classes ) ) . '">';
            while ( $query->have_posts() ) {
                $query->the_post();
                $org       = get_post_meta( get_the_ID(), 'uv_experience_org', true );
                $dates     = get_post_meta( get_the_ID(), 'uv_experience_dates', true );
                $has_thumb = has_post_thumbnail();

                echo '<li class="uv-card uv-card--experience">';
                echo '<a href="' . esc_url( get_permalink() ) . '">';
                if ( $has_thumb ) {
                    echo wp_get_attachment_image(
                        get_post_thumbnail_id(),
                        'uv_card',
                        false,
                        [
                            'alt' => esc_attr( get_the_title() ),
                        ]
                    );
                } else {
                    echo '<div class="uv-card-icon" aria-hidden="true"><svg viewBox="0 0 24 24" role="img" focusable="false" xmlns="http://www.w3.org/2000/svg"><path d="M12 3.75a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9Z" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M5.25 19.5a6.75 6.75 0 0 1 13.5 0" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></div>';
                }

                echo '<div class="uv-card-body">';
                echo '<h3>' . esc_html( get_the_title() ) . '</h3>';
                if ( $org || $dates ) {
                    echo '<div class="uv-card-meta">';
                    if ( $org ) {
                        echo '<div class="uv-card-meta__org">' . esc_html( $org ) . '</div>';
                    }
                    if ( $dates ) {
                        echo '<div class="uv-card-meta__dates">' . esc_html( $dates ) . '</div>';
                    }
                    echo '</div>';
                }
                if ( has_excerpt() ) {
                    echo '<div class="uv-card-excerpt">' . esc_html( get_the_excerpt() ) . '</div>';
                }
                echo '</div></a></li>';
            }
            echo '</ul>';
        } elseif ( is_admin() || wp_is_json_request() ) {
            echo '<div class="uv-block-placeholder">' . esc_html__( 'Ingen erfaringer funnet.', 'uv-core' ) . '</div>';
        }

        wp_reset_postdata();

        return ob_get_clean();
    }
}

