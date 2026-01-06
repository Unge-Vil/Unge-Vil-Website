<?php
/**
 * Server-side rendering for the Erfaringer block.
 *
 * @package UV_Core
 */

declare(strict_types=1);

if ( ! function_exists( 'uv_core_render_experiences_block' ) ) {
    /**
     * Extract a year from the experience dates meta value.
     *
     * @param string $dates   Experience dates.
     * @param int    $post_id Post ID.
     * @return string
     */
    function uv_core_get_experience_year( string $dates, int $post_id ): string {
        if ( preg_match( '/\b(\d{4})\b/', $dates, $matches ) ) {
            return $matches[1];
        }

        return get_the_date( 'Y', $post_id );
    }

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
            'year'       => '',
        ];
        $attributes = wp_parse_args( $attributes, $defaults );

        $max_count = 100;
        $count     = max( 1, min( $max_count, (int) $attributes['count'] ) );
        $layout = in_array( $attributes['layout'], [ 'list', 'grid', 'timeline' ], true ) ? $attributes['layout'] : 'grid';
        $year   = sanitize_text_field( (string) $attributes['year'] );

        $paged         = ! empty( $attributes['pagination'] ) ? max( 1, (int) get_query_var( 'paged', 1 ) ) : 1;
        $offset        = max( 0, ( $paged - 1 ) * $count );
        $should_paginate = ! empty( $attributes['pagination'] );

        $query_args = [
            'post_type'      => 'uv_experience',
            'posts_per_page' => $count,
            'paged'          => $paged,
            'offset'         => $offset,
            'no_found_rows'  => ! $should_paginate,
        ];

        if ( ! empty( $year ) ) {
            $query_args['date_query'] = [
                [
                    'after'     => sprintf( '%s-01-01 00:00:00', $year ),
                    'before'    => sprintf( '%s-12-31 23:59:59', $year ),
                    'inclusive' => true,
                ],
            ];
        }

        $query = new WP_Query( $query_args );

        $has_more_pages = $should_paginate && ( $query->max_num_pages > $paged );
        $wrapper_attributes = get_block_wrapper_attributes(
            [
                'data-count'         => (string) $count,
                'data-layout'        => $layout,
                'data-year'          => $year,
                'data-pagination'    => $should_paginate ? '1' : '0',
                'data-page'          => (string) $paged,
                'data-total-pages'   => (string) max( 1, (int) $query->max_num_pages ),
                'data-rest-url'      => esc_url_raw( rest_url( 'wp/v2/uv_experience' ) ),
                'data-load-more-text' => esc_attr__( 'Last inn flere', 'uv-core' ),
                'data-loading-text'   => esc_attr__( 'Laster…', 'uv-core' ),
                'data-error-text'     => esc_attr__( 'Kunne ikke laste flere erfaringer.', 'uv-core' ),
            ]
        );

        ob_start();

        echo '<div ' . $wrapper_attributes . '>';

        if ( $query->have_posts() ) {
            $wrapper_classes    = [ 'uv-experiences', 'uv-experiences--' . $layout ];
            $group_list_classes = [];

            if ( 'grid' === $layout || 'timeline' === $layout ) {
                $group_list_classes[] = 'uv-card-list';
            }
            if ( 'grid' === $layout ) {
                $group_list_classes[] = 'uv-card-grid';
                $group_list_classes[] = 'columns-3';
            }

            $experiences_by_year = [];

            while ( $query->have_posts() ) {
                $query->the_post();
                $org       = get_post_meta( get_the_ID(), 'uv_experience_org', true );
                $dates     = get_post_meta( get_the_ID(), 'uv_experience_dates', true );
                $year      = uv_core_get_experience_year( (string) $dates, get_the_ID() );

                ob_start();
                echo '<li class="uv-card uv-card--experience">';
                echo '<a href="' . esc_url( get_permalink() ) . '">';
                echo '<div class="uv-card-body">';
                echo '<h4>' . esc_html( get_the_title() ) . '</h4>';
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
                $experiences_by_year[ $year ][] = ob_get_clean();
            }

            krsort( $experiences_by_year );

            echo '<ul class="' . esc_attr( implode( ' ', $wrapper_classes ) ) . '">';
            foreach ( $experiences_by_year as $year => $cards ) {
                echo '<li class="uv-experiences__year-group" data-year="' . esc_attr( (string) $year ) . '">';
                echo '<h3 class="uv-experiences__year-heading">' . esc_html( (string) $year ) . '</h3>';
                $group_classes = 'uv-experiences__year-list';

                if ( ! empty( $group_list_classes ) ) {
                    $group_classes .= ' ' . implode( ' ', $group_list_classes );
                }

                echo '<ul class="' . esc_attr( $group_classes ) . '">';
                foreach ( $cards as $card_html ) {
                    echo $card_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                }
                echo '</ul>';
                echo '</li>';
            }
            echo '</ul>';
        } elseif ( is_admin() || wp_is_json_request() ) {
            echo '<div class="uv-block-placeholder">' . esc_html__( 'Ingen erfaringer funnet.', 'uv-core' ) . '</div>';
        }

        if ( $should_paginate && $has_more_pages ) {
            echo '<div class="uv-block-pagination">';
            echo '<button class="uv-button uv-experiences__load-more" type="button" data-action="load-more"';
            echo '>';
            echo esc_html( __( 'Last inn flere', 'uv-core' ) );
            echo '</button>';
            echo '</div>';
        }

        echo '</div>';

        wp_reset_postdata();

        return ob_get_clean();
    }
}
