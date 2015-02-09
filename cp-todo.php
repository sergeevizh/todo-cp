<?php
/*
Plugin Name:           CasePress. ToDo
Plugin URI:            http://casepress.org
Description:           Плагин позволяет добавлять поле для создания пунктов списка шорткодом cp_todo, порядок пунктов можно менять перетаскиванием элементов списка (порядок сохраняется). У каждого комментария есть кнопки "Редактировать", "Удалить".
Author:                CasePress
Author URI:            http://casepress.org
Author email:          ab@casepress.org
Version:               0.0.1
*/

function view_visits_cp() {
    if(empty($_REQUEST['view'])) return;
    if ( $_REQUEST['view'] == 'list_done_history' ) {
            include( plugin_dir_path(__FILE__) . 'list_done_history.php' );
            exit();
    }
} add_action( 'template_redirect', 'view_visits_cp', 0, 5);

add_shortcode('cp_todo', 'add_cp_todo_list');
function add_cp_todo_list(){
    global $post;
    ob_start();
    $user = get_current_user_id();
    $args = array(
        'post_id' => $post->ID,
        'meta_query' => array(
            array(
                'key' => 'cp_todo_item',
                'value' => 'yes',
            ),
        ),
        'meta_key' => 'cp_todo_item_order',
        'orderby' => 'meta_value_num',
        'order' => 'ASC',
    );
    $comments_query = new WP_Comment_Query;
    $comments = $comments_query->query( $args );
    $url = add_query_arg( array('view' => 'list_done_history')); ?>
    <div class="cp-todo-container" id="cp-todo-container">
        <div class="cp-todo-controls">
            <button class="btn btn-default" type="button" id="toggle-cp-todo-done-items">Скрыть/показать выполненные пункты</button>
            <a href="<?php echo $url; ?>&keepThis=true&TB_iframe=true&height=300&width=500" title="История закрытий" class="thickbox btn btn-default" id="cp-todo-done-history">История закрытия</a>
        </div>
        <ul class="cp-todo" id="cp-todo">
            <?php do_action('add_cp_todo_items', $comments); ?>
        </ul>
        <div class="add-cp-todo-container">
            <input type="text" id="new-cp-todo-item" class="form-control">
            <button class="btn btn-default" type="button" id="add-cp-todo-item">Добавить пункт</button>
        </div>
    </div>
    <script>
        var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
        jQuery(document).ready(function($){
            //отправка ajax`ом порядка вывода коментов
            var todogroup = jQuery("ul#cp-todo").sortable_a({
                placeholder: '<li class="placeholder" />',
                handle: 'div.icon-move',                                //дергалка за которую перетаскивать
                onDrop: function (item, container, _super) {
                    var serialize_data = todogroup.sortable_a("serialize").get();
                    var data = {
                        serialize_data: serialize_data,
                        action: 'cp_todo_item_order_change'
                    };
                    jQuery.post(ajaxurl, data, function(response) {
                        if (response.type == "success") {
                           //console.log (response)
                        } else {
                            console.log("Ошибка")
                        }
                    });
                    _super(item, container)
                }
            });
            //удаление меты cp_todo_item, запрос ajax
            jQuery("#cp-todo").on("click", ".delete_todo_item", function(e) {
                e.preventDefault();
                comment_id = jQuery(this).attr("data-comment_id");
                var data = {
                    comment_id: comment_id,
                    action: 'cp_todo_item_delete'
                };
                jQuery.post(ajaxurl, data, function(response) {
                    if (response.type == "success") {
                        jQuery("#controlcommentid_"+comment_id).remove()
                    } else {
                        console.log("Ошибка удаления")
                    }
                });
            });
            //изменение меты cp_todo_item_done, отправка данных ajax
            jQuery("#cp-todo").on("change", ".done_item", function() {
                comment_id = jQuery(this).attr("data-comment_id");
                elem = jQuery(this).closest('.row');
                
                var data = {
                    comment_id: comment_id,
                    action: 'cp_todo_change'
                };
                jQuery.post(ajaxurl, data, function(response) {
                    if (response.type == "success") {
                        // console.log("все прошло хоррошо")
                        elem.find('.done-time').text(response.done_time_item_cp)
                    } else {
                        console.log("Ошибка сохранения результата")
                    }
                });
            });
            //добавление нового пункта списка
            function add_item() {
                var item_text = jQuery('#new-cp-todo-item');
                var data = {
                    item_text: item_text.val(),
                    post_ID: <?php echo $post->ID; ?>,
                    user_ID: <?php echo $user; ?>,
                    action: 'cp_todo_add_item'
                };
                jQuery.post(ajaxurl, data, function(response) {
                    if (response.type == "success") {
                        // console.log("все прошло хоррошо")
                        item_text.val("")
                        jQuery(response.comment).appendTo('#cp-todo');
                    } else {
                        console.log("Ошибка добавления пункта")
                    }
                });
            };
            jQuery("#add-cp-todo-item").click(add_item);
            jQuery("#new-cp-todo-item").keypress(function(e) {
                if(e.keyCode == 13) {
                    add_item();
                };
            });
            jQuery("#toggle-cp-todo-done-items").click(function() {
                jQuery(".cp-todo-li:has(.done_item[checked])").toggle();
            });
        });
    </script>
    <?php
    return ob_get_clean();
}

add_action('add_cp_todo_items', 'add_cp_todo_func');
function add_cp_todo_func($comments) {
    function add_cp_todo_item($comments, $iter_count, $order) {
        $i = $order;
        for($j = 0; $j < $iter_count && $i < count($comments); $j ++) {
            $com_ID = $comments[$i]->comment_ID;
            $cp_todo_item_done = get_comment_meta($com_ID, "cp_todo_item_done", true);
            $done_time_item_cp = get_comment_meta($com_ID, "done_time_item_cp", true);
            $child_count = intval(get_comment_meta($com_ID, "cp_todo_item_children_count", true));
            $link = get_comment_link( $com_ID );
            $anchor = preg_split('/#/', $link);
            $anchor = '#'.$anchor[1]; //anchor link to comment
            ?>
            <li class="cp-todo-li" data-comment_id="<?php echo $com_ID?>" id="controlcommentid_<?php echo $com_ID?>">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-1">
                                <input type="checkbox" data-comment_id="<?php echo $com_ID?>" class="done_item" name="done" <?php if ($cp_todo_item_done == 'true') echo 'checked'; ?>>
                                <div class="icon-move">
                                    <span class="glyphicon glyphicon-sort"></span>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <?php
                                $done = $done_time_item_cp != "" ? "(Выполнено: $done_time_item_cp)" : "";
                                ?>
                                <span class="done-time"><?=$done; ?></span> <?=$comments[$i]->comment_content; ?>
                            </div>
                            <div class="col-md-2">
                                <div class="hide-hover">
                                    <div class="dropdown">
                                        <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-expanded="true">
                                            Действие
                                            <span class="caret"></span>
                                        </button>
                                        <ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu1">
                                            <li role="presentation"><a role="menuitem" tabindex="-1" href="#" data-comment_id="<?php echo $com_ID?>" class="delete_todo_item">Удалить</a></li>
                                            <li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo $anchor;?>">Перейти к комментарию </a></li>
                                            <?php if( current_user_can('moderate_comments') ):?>
                                                <li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo get_edit_comment_link( $comments[$i]->comment_ID );?>">Редактировать</a></li>
                                            <?php endif;?>
                                        </ul>
                                    </div>
                                </div>
                            </div><!--end col-md-2-->
                        </div><!--end row-->
                    </div><!--end panel-body-->
                </div><!--end panel panel-default-->
                <ul class="subitems">
                <?php 
                    $i++;
                    if ($child_count > 0) {
                        $i += add_cp_todo_item($comments, $child_count, $i);
                    };
                ?>
                </ul>
            </li>
            <?php
        }
        return $i - $order;
    }
    add_cp_todo_item($comments, count($comments), 0);
};

//подключения js-плагинов сортировки и css
add_action('wp_enqueue_scripts', 'load_necessary_scripts');
function load_necessary_scripts() {
    global $post;
    if (!isset ($post)) return; 
    if (has_shortcode($post->post_content, 'cp_todo') or is_single() ) {
        wp_enqueue_style('thickbox', plugin_dir_url(__FILE__) . '/thickbox.css');
        wp_enqueue_style('cp-todo', plugin_dir_url(__FILE__) . '/cp-todo.css');
        wp_enqueue_script('jquery-sortable-johny', plugin_dir_url(__FILE__) . '/jquery-sortable-min.js', array('jquery'));
        wp_enqueue_script('thickbox', plugin_dir_url(__FILE__) . '/thickbox.js', array('jquery'));
    }
}

//отключает jquery ui sortable, у плагина jquery-sortable by johny конфликт с ним
add_action( 'wp_print_scripts', 'deactivate_ui_sortable_script', 100 );
function deactivate_ui_sortable_script() {
    global $post;
    if (!isset ($post)) return;
    if (has_shortcode($post->post_content, 'cp_todo') or is_single() ){
        wp_dequeue_script( 'jquery-ui-sortable' );
        wp_deregister_script( 'jquery-ui-sortable' );
    }
}

//обработчик ajax изменения порядка
add_action("wp_ajax_cp_todo_item_order_change", "cp_todo_item_order_change");
add_action("wp_ajax_nopriv_cp_todo_item_order_change", "cp_todo_item_order_change");
function cp_todo_item_order_change(){
    function set_item_order($comment_array, $i) {
        foreach ($comment_array as $val){ //такая струтура из-за входящего массива данных
            $children = $val["children"][0];
            update_comment_meta($val["comment_id"], "cp_todo_item_order", $i);
            update_comment_meta($val["comment_id"], "cp_todo_item_children_count", count($children));
            $i += 1;
            if (count($children) != 0) $i = set_item_order($children, $i);
        }
        return $i;
    }
    $order = $_POST['serialize_data'];
    set_item_order($order[0], 0);
    $result['type'] = "success";
    wp_send_json($result);
}

//обработчик ajax изменения статуса выполнен пункт или нет
add_action("wp_ajax_cp_todo_change", "cp_todo_item_change");
add_action("wp_ajax_nopriv_cp_todo_change", "cp_todo_item_change");
function cp_todo_item_change(){
    /*
    // проверка nonce
    * if ( !wp_verify_nonce( $_REQUEST['nonce'], "my_user_vote_nonce")) {
    exit("No naughty business please");
    }*/

    $comment_id = $_REQUEST["comment_id"];
    $cp_todo_item_done = get_comment_meta($comment_id, "cp_todo_item_done", true);
    $done_time_item_cp = get_comment_meta($comment_id, "done_time_item_cp", true);
    //если мета cp_todo_item_done не объявлена создать не заблокированную

    if ($cp_todo_item_done === 'false') {
        $cp_todo_item_done = 'true'; // смена типа
        $done_time_item_cp = current_time('d-m-Y');
    }
    elseif ($cp_todo_item_done === 'true'){
        $cp_todo_item_done = 'false'; // смена типа
        $done_time_item_cp = '';
    }
    else{
        $cp_todo_item_done = 'true'; // в первый раз
        $done_time_item_cp = current_time('d-m-Y');
    }

    $res = update_comment_meta($comment_id, "cp_todo_item_done", $cp_todo_item_done);
    $res = update_comment_meta($comment_id, "done_time_item_cp", $done_time_item_cp);

    if ($res === false) {
        $result['type'] = "error";
    } else {
        $result['type'] = "success";
        $result['done_time_item_cp'] = $done_time_item_cp != "" ? "(Выполнено: $done_time_item_cp)" : "";
    }

    wp_send_json($result);
}

//обработчик для неавторизированных юзеров
function must_login(){
    echo "You must log in to vote";
    die();
}

//обработчик удаления cp_todo_item
add_action("wp_ajax_cp_todo_item_delete", "my_cp_todo_item_delete");
add_action("wp_ajax_nopriv_cp_todo_item_delete", "my_cp_todo_item_delete");
function my_cp_todo_item_delete(){
    $res = delete_comment_meta($_REQUEST["comment_id"], "cp_todo_item");
    if ($res === false) {
        $result['type'] = "error";
    } else {
        $result['type'] = "success";
        $result['comment_id'] = $_REQUEST["comment_id"];
    }
    wp_send_json($result);
}

add_action("wp_ajax_cp_todo_add_item", "my_cp_todo_add_item");
add_action("wp_ajax_nopriv_cp_todo_add_item", "my_cp_todo_add_item");
function my_cp_todo_add_item(){
    $user = get_userdata(intval($_POST['user_ID']));

    # Задаем массив входных параметров:
    $data = array(
      'comment_post_ID' => $_POST['post_ID'],
      'comment_author' => $user->display_name,
      'comment_author_email' => $user->user_email,
      'comment_content' => $_POST['item_text'],
      'user_id' => $user->ID,
      'comment_date' => current_time('mysql'),
      'comment_approved' => 1,
    );

    # Вставляем информацию в базу данных:
    $comment_id = wp_insert_comment($data);

    if (intval($comment_id) < 0) {
        $result['type'] = "error";
    } else {
        $result['type'] = "success";
    }

    //добавляет мета поля к форме комментирования
    add_comment_meta($comment_id, 'cp_todo_item_order', $comment_id); // для сортировки
    add_comment_meta ($comment_id, 'cp_todo_item' ,'yes');

    //получем новый элемент для вставки в список
    ob_start();
    add_cp_todo_func(array(get_comment($comment_id))); // такая форма передачи, так как в add_cp_todo_func используется проход по массиву комментариев
    $result['comment'] = ob_get_clean();

    wp_send_json($result);
};