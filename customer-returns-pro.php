<?php
/*
Plugin Name: Customer Returns Pro
Description: Streamline and optimize the management of customer returns.
Version: 1.0
Author: The WooEcom Team
Text Domain: customer-returns-pro
*/

// Activation hook to create the database table


register_activation_hook(__FILE__, 'crp_create_table');

// Function to create the database table on plugin activation
function crp_create_table()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'blacklist_customers';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        type varchar(255) NOT NULL,
        detail varchar(255) NOT NULL,
        status varchar(20) DEFAULT 'block',
        updated_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Function to add menu and submenus
function crp_add_menu()
{
    add_menu_page('Customer Returns Pro', 'Customer Returns Pro', 'manage_options', 'customer-returns-pro', 'crp_blacklist_page', 'dashicons-welcome-widgets-menus');
}

add_action('admin_menu', 'crp_add_menu');

// Function to enqueue scripts
function crp_enqueue_scripts()
{
    wp_enqueue_script('jquery');
    // Enqueue your plugin's script
    wp_enqueue_script('crp_script', plugins_url('/js/crp_script.js', __FILE__), array('jquery'), '1.0', true);

    // Localize the script to make the admin-ajax.php path available
    wp_localize_script('crp_script', 'crp_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}

add_action('admin_enqueue_scripts', 'crp_enqueue_scripts');

// Function to display blacklist page
function crp_blacklist_page()
{
    global $wpdb;
    $customer_pro_blacklist  = get_option('customer_pro_blacklist');
    $customer_pro_order_limit = get_option('customer_pro_order_limit');
    if (!$customer_pro_blacklist) {
        $customer_pro_blacklist = 'off';
        update_option('customer_pro_blacklist', $customer_pro_blacklist);
    }
    if (!$customer_pro_order_limit) {
        $customer_pro_order_limit = 'off';
        update_option('customer_pro_order_limit', $customer_pro_order_limit);
    }
?>

    <head>
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
        <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    </head>
    <h2>Customer Return Pro</h2>
    <div class="parent_pro">
        <div class="data_form">
            <form id="form">
                <h3>Add To Blacklist</h3>
                <label for="detail-type">Detail Type:</label>
                <select id="detail-type" name="detail_type">
                    <option value="email">Email</option>
                    <option value="phone">Phone</option>
                </select>
                <label for="detail">Detail:</label>
                <input type="text" id="detail" name="detail" required>
                <button type="button" id="submit-blacklist" class="action-buttons add-action">Add</button>
            </form>
        </div>
        <div class="data_form">
            <form id="form">
                <h3>Delete Black List Orders</h3>
                <input type="checkbox" id="customer_pro_blacklist" name="customer_pro_blacklist" style="display: none !important;" <?php echo ($customer_pro_blacklist === 'on') ? 'checked' : ''; ?>>
                <label class="switch" for="customer_pro_blacklist"></label>
                <h3>Allow Only 1 Order In 24 Hours</h3>
                <input type="checkbox" id="customer_pro_order_limit" name="customer_pro_order_limit" style="display: none !important;" <?php echo ($customer_pro_order_limit === 'on') ? 'checked' : ''; ?>>
                <label class="switch2" for="customer_pro_order_limit"></label>
            </form>
        </div>
    </div>
    <div class="wrap">
        <table id="form_data">
            <thead>
                <tr>
                    <th>S. No</th>
                    <th>Type</th>
                    <th>Details</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $blacklist_data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}blacklist_customers", ARRAY_A);

                foreach ($blacklist_data as $index => $row) {
                ?>
                    <tr data-id="<?php echo esc_attr($row['id']); ?>">
                        <td><?php echo esc_html($index + 1); ?></td>
                        <td><?php echo esc_html($row['type']); ?></td>
                        <td><?php echo esc_html($row['detail']); ?></td>
                        <td>
                            <button class="delete action-buttons delete-action" data-id="<?php echo esc_attr($row['id']); ?>">Delete</button>
                            <button class="toggle-status  action-buttons block-action" data-id="<?php echo esc_attr($row['id']); ?>">
                                <?php echo ($row['status'] === 'block') ? 'Unblock' : 'Block'; ?>
                            </button>
                        </td>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
    </div>
    <!-- call datatables -->
    <script>
        new DataTable('#form_data', {
            responsive: true
        });
    </script>

<?php
    wp_enqueue_style('form-style', plugin_dir_url(__FILE__) . 'style/style.css', array(), '1.0');
}

// Add Ajax handlers to your main plugin file

add_action('wp_ajax_crp_add_to_blacklist', 'crp_add_to_blacklist');
add_action('wp_ajax_crp_delete_from_blacklist', 'crp_delete_from_blacklist');
add_action('wp_ajax_crp_toggle_blacklist_status', 'crp_toggle_blacklist_status');

// Function to add customer to blacklist
function crp_add_to_blacklist()
{
    global $wpdb;

    $detail_type = $_POST['detailType'];
    $detail = sanitize_text_field($_POST['detail']);

    $wpdb->insert(
        $wpdb->prefix . 'blacklist_customers',
        array(
            'type' => $detail_type,
            'detail' => $detail,
            'created_at' => current_time('mysql')
        )
    );

    wp_die(); // This is required to terminate immediately and return a proper response
}

// Function to delete customer from blacklist
function crp_delete_from_blacklist()
{
    global $wpdb;

    $id = intval($_POST['id']);

    $wpdb->delete($wpdb->prefix . 'blacklist_customers', array('id' => $id));

    wp_die();
}

// Function to toggle blacklist status
function crp_toggle_blacklist_status()
{
    global $wpdb;

    $id = intval($_POST['id']);

    $current_status = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$wpdb->prefix}blacklist_customers WHERE id = %d", $id));
    $new_status = ($current_status == 'block') ? 'unblock' : 'block';

    $wpdb->update(
        $wpdb->prefix . 'blacklist_customers',
        array('status' => $new_status, 'updated_at' => current_time('mysql')),
        array('id' => $id)
    );

    wp_die();
}


// WordPress AJAX action hook for updating switch state
add_action('wp_ajax_update_switch_state', 'update_switch_state');

function update_switch_state()
{
    // Check if the request is coming from a logged-in user
    if (is_user_logged_in()) {
        // Retrieve the switch state and checkbox name from the AJAX request
        $switch_state = isset($_POST['switch_state']) ? sanitize_text_field($_POST['switch_state']) : '';
        $checkbox_name = isset($_POST['checkbox_name']) ? sanitize_text_field($_POST['checkbox_name']) : '';

        // Update the option in the WordPress database
        update_option($checkbox_name, $switch_state);

        // Return a response
        echo json_encode(array('success' => true));
    } else {
        // Return an error response if the user is not logged in
        echo json_encode(array('success' => false, 'message' => 'User is not logged in.'));
    }

    // Always exit to avoid further execution
    wp_die();
}


add_action('admin_head', 'highlight_blacklisted_orders');

function highlight_blacklisted_orders()
{
    global $pagenow, $wpdb;
    $main_url = home_url();
    // Check if we are on the WooCommerce orders page
    if (isset($_GET['page']) && $_GET['page'] == 'wc-orders') {

        // Retrieve blacklisted customers from the database
        $blacklist_customers = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}blacklist_customers WHERE status = 'block'", ARRAY_A);

        // Loop through each order and check if it's placed by a blacklisted customer
        $orders = $wpdb->get_results("SELECT id FROM {$wpdb->prefix}wc_orders WHERE type = 'shop_order'", ARRAY_A);
        foreach ($orders as $order) {
            $order_obj = wc_get_order($order['id']);
            $customer_email = $order_obj->get_billing_email();
            $customer_phone = $order_obj->get_billing_phone();

            $cancel_order = false;

            foreach ($blacklist_customers as $customer) {
                if (($customer['type'] === 'email' && $customer['detail'] === $customer_email) ||
                    ($customer['type'] === 'phone' && $customer['detail'] === $customer_phone)
                ) {
                    echo '<style>#order-' . $order['id'] . ' { background-color: #ffcccc; }; </style>';
                    $cancel_order = true;
                    break;
                }
            }
            if ($cancel_order) {
                // Cancel the order if needed
                $order_status = $order_obj->get_status();
                if ($order_status == 'processing') {
                    $customer_pro_blacklist = get_option('customer_pro_blacklist');
                    if ($customer_pro_blacklist == 'on') {
                        $order_obj->update_status('trash', __('Order trashed due to blacklisted customer.', $main_url . '/'));
                    } else {
                        // Cancel the order and add a note
                        $order_obj->update_status('cancelled', __('Order cancelled due to blacklisted customer.', $main_url . '/'));
                    }
                }
            }
        }
    }
}


// Hook into the checkout process to prevent order creation
add_action('woocommerce_checkout_process', 'check_blacklist_on_order_creation');

function check_blacklist_on_order_creation()
{
    // Get the customer's IP address
    $customer_ip = $_SERVER['REMOTE_ADDR'];
    $customer_pro_order_limit = get_option('customer_pro_order_limit');
    if ($customer_pro_order_limit == 'on') {
        // Check if the customer has placed an order within the past 24 hours
        $last_order_timestamp = get_last_order_timestamp_by_ip($customer_ip);
        if ($last_order_timestamp !== false && time() - $last_order_timestamp < 24 * 60 * 60) {
            // Display an error message and prevent order processing
            wc_add_notice(__('Only one order is allowed per IP address in a 24-hour period.', 'customer-returns-pro'), 'error');
            return;
        }
    }
}

// Function to get the timestamp of the last order placed by an IP address
function get_last_order_timestamp_by_ip($ip)
{
    return get_transient('last_order_timestamp_' . $ip);
}

// Function to update the timestamp of the last order placed by an IP address
function update_last_order_timestamp_by_ip($ip)
{
    set_transient('last_order_timestamp_' . $ip, time(), 24 * 60 * 60); // Store for 24 hours
}
