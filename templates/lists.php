<?php
global $page_error, $page_message;

$swifposts = Swifposts_Loader::get_swifposts();
$post_types = Swifposts_Loader::get_post_types();

?>
<div class="wrap">
    <h2><?php echo Swifposts_Config::HEADING ?></h2>
    <?php
    Swifposts_Loader::display_errors($page_error);
    Swifposts_Loader::display_messages($page_message);
    ?>
    <h3><?php echo __('Post types', SWIFPOSTS_DOMAIN) ?></h3>
    <?php if(count($swifposts)): ?>
    <table class="wp-list-table widefat fixed striped swifposts-table">
        <thead>
            <tr>
                <th width="50%"><?php echo __('Post type', SWIFPOSTS_DOMAIN) ?></th>
                <th width="20%"><?php echo __('Status', SWIFPOSTS_DOMAIN) ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach($swifposts as $row):
                $row_id = $row['id'];
                $edit_url = add_query_arg(array('view'=>'edit', 'id'=>$row_id), Swifposts_Loader::get_setting_url());

                $delete_url = add_query_arg('id', $row_id, Swifposts_Loader::get_setting_url());
                $delete_url = wp_nonce_url($delete_url, 'swifpost-delete-action', 'swifpost-delete-nonce');

                $enable_url = add_query_arg('id', $row_id, Swifposts_Loader::get_setting_url());
                if($row['status'] == 1)
                    $enable_url = wp_nonce_url($enable_url, 'swifpost-disable-action', 'swifpost-disable-nonce');
                else
                    $enable_url = wp_nonce_url($enable_url, 'swifpost-enable-action', 'swifpost-enable-nonce');
                ?>
            <tr>
                <td><?php echo $row['post_type'] ?></td>
                <td>
                    <a href="<?php echo $enable_url ?>" class="swifpost-status <?php echo $row['status'] == 1 ? 'enabled':'disabled' ?>" title="<?php echo $row['status'] == 1 ? __('Enabled', SWIFPOSTS_DOMAIN):__('Disabled', SWIFPOSTS_DOMAIN) ?>">
                        <?php echo $row['status'] == 1 ? __('E', SWIFPOSTS_DOMAIN):__('D', SWIFPOSTS_DOMAIN) ?>
                    </a>
                </td>
                <td>
                    <a href="<?php echo $edit_url ?>" class="button-secondary">Edit</a>
                    <a href="<?php echo $delete_url ?>" class="button-secondary" onclick="return confirm('This cannot be undo, are you sure?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th><?php echo __('Post type', SWIFPOSTS_DOMAIN) ?></th>
                <th><?php echo __('Status', SWIFPOSTS_DOMAIN) ?></th>
                <th></th>
            </tr>
        </tfoot>
    </table>
    <?php endif ?>
    <br/>
    <div class="new-swifpost-section">
        <form method="post">
            <?php wp_nonce_field('swifpost-action', 'swifpost-nonce'); ?>
            <select name="swifpost">
                <option value="">Select Post Type</option>
                <?php foreach($post_types as $type): ?>
                    <option value="<?php echo $type->name ?>"><?php echo $type->label ?></option>
                <?php endforeach ?>
            </select>
            <button class="button-primary">Add Post Type</button>
        </form>

    </div>

    <?php include 'credit.php' ?>
    <?php include 'ads.php' ?>
</div>