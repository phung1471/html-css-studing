<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <header class="entry-header">
        <?php thachpham_thumbnail( 'large' ); ?>
        <?php thachpham_entry_header(); ?>
        <?php
        /*
        * Đếm số lượng attachment có trong post
        */
        $attachments = get_children( array( 'post_parent'=>$post->ID ) );
        $attachment_number = count($attachments);
        printf( __('This image post contains %1$s photos', 'thachpham'), $attachment_number );
        ?>
        <?php thachpham_entry_meta() ?>
    </header>
    <div class="entry-content">
        <?php thachpham_entry_content(); ?>
        <?php ( is_single() ? thachpham_entry_tag() : '' ); ?>
    </div>
</article>