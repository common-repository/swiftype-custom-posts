<?php
global $page_error, $page_message;

$id = $_GET['id'];

$saved_post = Swifposts_Loader::get_swifposts($id);

$saved_fields = Swifposts_Loader::get_swifposts_custom_fields($id);
$saved_taxonomies = Swifposts_Loader::get_swifposts_taxonomies($id);


$custom_fields = Swifposts_Loader::get_custom_fields();
$field_types = Swifposts_Loader::get_field_types();

$add_url = add_query_arg('id', $id, Swifposts_Loader::get_setting_url());
$add_url = wp_nonce_url($add_url, 'swiffield-add-action', 'swiffield-add-nonce');

?>
<div class="wrap">
    <h2><?php echo Swifposts_Config::HEADING ?></h2>
    <?php
    Swifposts_Loader::display_errors($page_error);
    Swifposts_Loader::display_messages($page_message);
    ?>

    <h3>Custom Fields for <?php echo $saved_post['post_type'] ?></h3>
    <form method="post">
        <?php wp_nonce_field('swiffield-action', 'swiffield-nonce') ?>

        <table class="wp-list-table widefat fixed striped swifposts-table">
            <thead>
                <tr>
                    <th>Field</th>
                    <th style="width: 250px;">Type</th>
                    <th style="width: 100px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php if($saved_fields): ?>
                <?php
                foreach($saved_fields as $row):
                    $row_id = $row['id'];
                    $saved_key = $row['field_key'];
                    $saved_type = $row['data_type'];

                    $delete_url = add_query_arg('id', $id, Swifposts_Loader::get_setting_url());
                    $delete_url = add_query_arg('field-id', $row_id, $delete_url);
                    $delete_url = wp_nonce_url($delete_url, 'swiffield-delete-action', 'swiffield-delete-nonce');

                    ?>
                <tr>
                    <td>
                        <select name="field[<?php echo $row_id ?>][field-key]">
                            <option value="">Select Existing Custom Field</option>
                            <?php

                            $selected_field_key = '';
                            foreach($custom_fields as $field_key):
                                $selected_field_key = $field_key==$saved_key?$saved_key:'';
                                ?>
                                <option value="<?php echo $field_key ?>" <?php echo $selected_field_key?'selected':'' ?>><?php echo $field_key ?></option>
                            <?php endforeach; ?>
                            <option value="">New Custom Field</option>
                        </select>
                        <input type="text" name="field[<?php echo $row_id ?>][field-key-new]" value="<?php echo $selected_field_key?'':$saved_key ?>" placeholder="Enter New Custom Field">
                    </td>
                    <td>
                        <select name="field[<?php echo $row_id ?>][data-type]">
                            <?php foreach($field_types as $type): ?>
                                <option value="<?php echo $type ?>" <?php echo $saved_type == $type?'selected':'' ?>><?php echo $type ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <a href="<?php echo $delete_url ?>" class="button-secondary">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">
                            <p>You have no custom fields, <a href="<?php echo $add_url ?>">create one?</a></p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php if($saved_fields): ?>
        <p>
            <button class="button-primary">Save All Fields</button>
        </p>
        <?php endif; ?>
        <p>
            <span><strong>NOTE:</strong> Please save all fields before add new one.</span><br/>
            <a href="<?php echo $add_url ?>" class="button-secondary">Add New Custom Field</a>
        </p>
        <p>
            <a href="<?php echo Swifposts_Loader::get_setting_url(); ?>">&lt;&lt;Back to list</a>
        </p>
    </form>

    <p><br/></p>
    <form method="post">
        <?php wp_nonce_field('swifpost-custom-action', 'swifpost-custom-nonce'); ?>

        <h3>Exclude Post IDs</h3>
        <p>
            <label for="exclude-ids">Enter post ID, separated by comma:</label><br/>
            <input type="text" name="exclude-ids" id="exclude-ids" value="<?php echo isset($_POST['exclude-ids'])?$_POST['exclude-ids']:$saved_post['exclude_ids'] ?>" placeholder="" size="50">
        </p>
        <p><br/></p>

        <h3>Build Custom Title & URL</h3>
        <p>Do you want to add more information into the search result title?</p>
        <p><img src="<?php echo SWIFPOSTS_URI ?>/images/screenshot_329.jpg" style="max-width: 100%;"></p>
        <p>You can build a flexible title base on the syntax below:</p>
        <ul style="list-style: inside none disc;">
            <li><strong>{ID}</strong>: the ID of post</li>
            <li><strong>{post_title}</strong>: the title of post</li>
            <li><strong>{post_name}</strong>: the slug of post</li>
            <li><strong>{permalink}</strong>: the URL of post</li>
            <li><strong>{field:</strong>custom_field_key<strong>}</strong>: the custom field, for example you want to use the field "event_date", it should be {field:event_date}</li>
            <li><strong>{tax:</strong>taxonomy_name<strong>}</strong>: the taxonomy of post, for example you want to use the taxonomy "category", it should be {tax:category}</li>
            <li><strong>{tax_slug:</strong>taxonomy_name<strong>}</strong>: the taxonomy of post, for example you want to use the taxonomy "category", it should be {tax_slug:category}</li>
        </ul>



        <p>
            <label for="custom-title">Enter your custom title:</label><br/>
            <input type="text" name="custom-title" id="custom-title" value="<?php echo isset($_POST['custom-title'])?$_POST['custom-title']:$saved_post['custom_title'] ?>" placeholder="{post_title}" size="50">
            <br/>
            <strong>Eg: {post_title}, {field:event_date}, {field:location}</strong>
        </p>
        <br/>
        <p>You can also build a custom URL for post instead of use the default URL of post.</p>
        <p>For example: you may want to use like this: <strong><?php echo home_url('/events/#caloundra-new-years-eve') ?></strong></p>
        <p>
            <label for="custom-url">Enter your custom post URL:</label><br/>
            <input type="text" name="custom-url" id="custom-url" value="<?php echo isset($_POST['custom-url'])?$_POST['custom-url']:$saved_post['custom_url'] ?>" placeholder="{permalink}" size="50">
            <br/>
            <strong>Eg: <?php echo home_url('/events/#') ?>{post_name}</strong>
        </p>
        <p>
            <input type="submit" value="Save Custom Setting" class="button-primary">
        </p>
    </form>

    <?php include 'credit.php' ?>
</div>