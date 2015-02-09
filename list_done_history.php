<!DOCTYPE html>

<html>
<head>
<title>
    <?php
        global $wpdb, $page;
        wp_title( '|', true, 'right' );
        bloginfo( 'name' );
        $site_description = get_bloginfo( 'description', 'display' );
    ?>
</title>
<?php
    $url=get_permalink();
    wp_head();
?>
    <link rel="stylesheet" id="print-css"  href='<?php echo plugins_url( '/history.css', __FILE__ ); ?>' type="text/css" media="all">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.2/jquery.min.js"></script>
</head>

<body>

    <div id="content" class="clearfix">
        <?php
        global $post;
        $args = array(
            'post_id' => $post->ID,
            'meta_query' => array(
                array(
                    'key' => 'cp_todo_item',
                    'value' => 'yes',
                ),
                array(
                    'key' => 'cp_todo_item_done',
                    'value' => 'true',
                ),
            ),
            'meta_key' => 'cp_todo_item_order',
            'meta_type' => 'TIME',
            'orderby' => 'meta_value_num',
            'order' => 'ASC',
        );
        $comments_query = new WP_Comment_Query;
        $comments = $comments_query->query( $args );
        ?>


        <?php 
        foreach($comments as $comment) {
            $com_ID = $comment->comment_ID;
            $cp_todo_item_done = get_comment_meta($com_ID, "cp_todo_item_done", true);
            $done_time_item_cp = get_comment_meta($com_ID, "done_time_item_cp", true); ?>
            <li class="list-group-item">
                <ul class="ifo">
                <li>Пункт: <span><?php echo $comment->comment_content; ?></span></li>
                <li>Дата выполнения: <span class="time_done"><?php echo $done_time_item_cp; ?></span></li>
                </ul>
            </li>
        <?php } ?>
    </div>
</body>
</html>