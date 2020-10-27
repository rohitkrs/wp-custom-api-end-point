<?php 
/* Plugin Name: Custom API End Point's
URI:  
Description: A custom WP plugin to customize the response of default woocommerce API's and some custom API end points to Woo Commerce.&nbsp; <strong>(DO NOT DEACTIVATE OR REMOVE THIS PLUGIN)</strong>.
Version: 1.0 
Author: Rohit Sharma
Author URI: https://apptunix.com
*/ 

register_activation_hook( __FILE__, 'register_my_custom_menu_page' );


# Update CSS within in Admin
function admin_style() {
    wp_enqueue_style('admin-styles', get_template_directory_uri().'/restadmin.css');
  }
  add_action('admin_enqueue_scripts', 'admin_style');


# Registering the routes in here
add_action('rest_api_init', 'register_favorite_routes');
function register_favorite_routes() {
    
    register_rest_route('wp/v2', '/product/favorite/(?P<id>[\d]+)', array(
        'methods' => 'GET',
        'callback' => 'favorite'
    ));
    
}


######################   Note  #######################
###### This endpoint has been changed. use given below endpoint for postmeta ######
function favorite(WP_REST_Request $request) {
    global $wpdb;
    //echo "<pre>ID is ".$request['id'];
    $id = $request['id'];
    $result = $wpdb->get_results( "SELECT * from wp_yith_wcwl where ID='$id'");
    //print_r($result);
    extract($request->get_params());
    if(count($result) > 0) {
        $data = $result;
    } else {
        $data ="Empty data";
    }
    return [
        'favorite' => $data
    ];

}

// This endpoint has been changed. use given below endpoint for postmeta



# Routes for favorite a Product or Article

add_action('rest_api_init', 'make_favorite_routes');
function make_favorite_routes() {
    
    register_rest_route('wp/v2', '/favorite/(?P<postid>[\d]+)/(?P<userid>[\d]+)', array(
        'methods' => 'GET',
        'callback' => 'favorite_post'
    ));
}


function favorite_post(WP_REST_Request $request) {
    global $wpdb;
    $postid = $request['postid'];
    $type = $request['type'];
    $userid = $request['userid'];


    $check_type = get_post_meta( $postid, 'is_favorite');
    $strn = implode(',', $check_type);
    if($check_type) {
        if($strn[0] == 1)
        {
            $check_user = get_post_meta( $postid, 'favorite_by'); 
            if($check_user) {
                if (in_array($userid, $check_user)) {
                    // echo "update";    
                    delete_post_meta($postid, 'favorite_by', $userid);
                    //update_post_meta($postid, 'favorite_by', $userid, false);
                    $message = 'Status updated to unfavorite';
                    $success = "true";
                    $value = "false";
                }
                else {
                    //echo "add";                    
                    add_post_meta($postid, 'favorite_by', $userid);
                    $message = 'Status updated to favorite';
                    $success = "true";
                    $value = "true";
                }
            }
            else {
                // echo "favorite_by Not found";
                add_post_meta($postid, 'favorite_by', $userid);
                $message = 'User added to favorite list';
                $success = "true";
                $value = "true";
            }
            
        }
    }
    else {
        add_post_meta($postid, 'is_favorite', 1);
        add_post_meta($postid, 'favorite_by', $userid);
        $message = 'favorite added';
        $success = "true";
        $value = "true";
    }
    
    extract($request->get_params());
    return [
        'success' => $success,
        'value' => $value,
        'message' => $message,
    ];
    
}


# Routes to fetch post of favorite item by user

add_action('rest_api_init', 'get_favorite_posts');
function get_favorite_posts() {
    
    register_rest_route('wp/v2', '/favorite/(?P<userid>[\d]+)', array(
        'methods' => 'GET',
        'callback' => 'favorite_posts_list'
    ));
}

function favorite_posts_list(WP_REST_Request $request) {
    global $wpdb;
    $userid = $request['userid'];
    if(isset($userid)) {
        $args = array(
            'meta_query' => array(
                array(
                    'key' => 'is_favorite',
                    'value' => 1
                ),
                array(
                    'key' => 'favorite_by',
                    'value' => $userid
                )
            ),
            'post_type'      => array('post', 'product'),
            'post_status'      => array('publish', 'private'),
            'posts_per_page' => -1
        );
        $posts = get_posts($args);
        foreach($posts as $post)
        {
            $postdata["id"] = $post->ID;
            //$postdata["image"] = get_the_post_thumbnail_url($post->ID,'full');
            /* if($post_image) {
            } else { */
                $attachments = get_posts( array(
                    'post_type' => 'attachment',
                    'posts_per_page' => 5,
                    'post_parent' => $post->ID,
                ) );
                //print_r($attachments);
                if ( $attachments ) {
                    foreach ( $attachments as $attachment ) {
                        $thumbimg = wp_get_attachment_url( $attachment->ID);
                        //$images[] = $thumbimg; // use it for images array
                    }
                    $postdata["image"] = $thumbimg;
                    unset($images);
                }
                else {
                    //$post_image = wp_get_attachment_image_src($post->ID, 'full');
                    $post_image = get_the_post_thumbnail_url($post->ID,'full');

                    if($post_image) {
                        $postdata["image"] = $post_image;
                    } else {
                        $postdata["image"] = "";
                    }
                }
            //}
            
            if($post->post_type == "post") {
                $postdata["type"] = "post";           
            } else {
                $postdata["type"] = "simple";                
            }
            $messages[] = $postdata;
        }
        $message = $messages;
        $success = 1;
        $value = "true";
    } else {
        $success = 0;
        $value = "false";
        $message = "User ID is missing";
    }
    
    extract($request->get_params());
    return [
        'success' => $success,
        'value' => $value,
        'message' => $message,
    ];
   
    die;
}


function register_favorite_status() {
    register_rest_field( 'post',
        'favorite_meta', // Add it to the response
        array(
            'get_callback'    => 'get_favorite_status', // Callback function - returns the value
            'update_callback' => null,
            'schema'          => null,
        )
    );
}
add_action( 'rest_api_init', 'register_favorite_status' );

function register_product_favorite_status() {
    register_rest_field( 'product',
        'favorite_meta', // Add it to the response
        array(
            'get_callback'    => 'get_favorite_status', // Callback function - returns the value
            'update_callback' => null,
            'schema'          => null,
        )
    );
}
add_action( 'rest_api_init', 'register_product_favorite_status' );



function get_favorite_status( $object, $field_name, $request ) {
   $userid = $request["userid"];
    $check_type = get_post_meta( $object['id'], 'is_favorite');
    if($check_type) {
        $check_user = get_post_meta( $object['id'], 'favorite_by');
        if (in_array($userid, $check_user)) {
            //$getfav = array("favorite" => true, "favorite_by"=> $check_user);
            $getfav = array("favorite" => true);
            return $getfav;
            } else 
            { 
                //$getfav = array("favorite" => false, "favorite_by"=> $check_user);
                $getfav = array("favorite" => false);
                return $getfav;
            }
    }
    else {
        $getfav = array("favorite" => false);
        return $getfav;
    }
   
}

add_action('rest_api_init', function () {
    register_rest_route( 'sb/v1', '/home',array(
                  'methods'  => 'GET',
                  'callback' => 'get_home_feed'
        ));
  });


function get_home_feed($request) {

    $url = "https://shortboxed-landing-page.firebaseapp.com/feed.json";
    $response = json_decode(file_get_contents($url));
    return $response;

}



// Add product categories for filter endpoint

add_action('rest_api_init', function () {
    register_rest_route( 'wc/v3', '/custom/products/filters',array(
                  'methods'  => 'GET',
                  'callback' => 'get_products_categories'
        ));
  });

function get_products_categories($request) {

    $categories = get_categories(
        array(
            'hide_empty' =>  0, // by default get get_category will only return non-empty categories. in this case we may want to show all regardless.
            'taxonomy'   =>  'product_cat'
        )
    );

    //fix-me
    //error_reporting(0);

    $final_categories = array();
    $heading_ids = array();
    $unmodified_categories = json_decode($categories);
    
    // get the top level comics parent id
    if (categories) {
        foreach($categories as $category){
            $slug = $category->{"slug"};
            if ($slug == 'comics') {
            $comics_id = $category->{"term_id"};
            }
            
        }
    }
    else {
    }

    // get all the ids that are direct children of comics
    foreach($categories as $category){
        $parent_id = $category->{"parent"};
        if ($parent_id == $comics_id) {
            $header_id = $category->{"term_id"};
            array_push($heading_ids, $header_id);
        }
    }
    
    // now fetch the childen(sub_heading) of the children($heading_ids)
    foreach ($heading_ids as $heading) {
    
        # setting heading id
        foreach ($categories as $category) {
    
            if ($category->{"term_id"} == $heading) {
                unset($sub_heading_obj);
                $sub_heading_obj->heading = $category->{"name"};
                $sub_heading_obj->sub_heading = array();
    
                foreach ($categories as $category) {
                    $sub_heading_parent = $category->{"parent"};
                    if ($sub_heading_parent == $heading) {
                        unset($field_obj);
                        $field_obj->id = $category->{"term_id"};
                        $field_obj->name = $category->{"name"};
                        $field_obj->slug = $category->{"slug"};
                        $field_obj->count = $category->{"count"};
    
                        array_push($sub_heading_obj->sub_heading, $field_obj);
    
                    }
                }
                array_push($final_categories, $sub_heading_obj);
            }
        }
    }
    
    $final_categories_json = json_encode($final_categories);

    return $final_categories;
}

add_action('rest_api_init', function () {
    register_rest_route( 'wc/v3', '/custom/home',array(
                    'methods'  => 'GET',
                    'callback' => 'get_home_feed_v2'
        ));
    });


function get_home_feed_v2($request) {

    $userid = $request["userid"];

    $url = "https://shortboxed-landing-page.firebaseapp.com/feed.json";
    $original_home_object = json_decode(file_get_contents($url));
    $new_home_object = array();

    foreach ($original_home_object as $item){

        unset($field_obj);
        
        $ftype = $item->{"type"};
        $fid = $item->{"id"};
        $fname = $item->{"name"};
        $fprice = $item->{"price"};
        $fimages = $item->{"images"};

        $check_type = get_post_meta( $fid, 'is_favorite');
        if($check_type) {
            $check_user = get_post_meta( $fid, 'favorite_by');
            if (in_array($userid, $check_user)) {
                //$getfav = array("favorite" => true, "favorite_by"=> $check_user);
                $getfav = array("favorite" => true);
                } else 
                { 
                    //$getfav = array("favorite" => false, "favorite_by"=> $check_user);
                    $getfav = array("favorite" => false);
                }
            }
            else {
                $getfav = array("favorite" => false);
        }

        $field_obj->type = $ftype;
        $field_obj->id = $fid;
        $field_obj->name = $fname;
        $field_obj->price = $fprice;
        $field_obj->images = $fimages;
        $field_obj->favorite_meta= $getfav;

        array_push($new_home_object, $field_obj);
    }
    return $new_home_object;
}





// Add user_id to JWT Api response
// https://wordpress.org/support/topic/how-get-id-user/
// https://github.com/Tmeister/wp-api-jwt-auth/issues/153

function jwt_auth_function($data, $user) { 

    $data['user_id'] = $user->ID; 
    return $data; 
} 

add_filter( 'jwt_auth_token_before_dispatch', 'jwt_auth_function', 10, 2 );


// https://cocart.xyz/removing-the-cart-item-key/
// https://gist.github.com/seb86/9461d6fdad9367246ad9fb87d52a9891

function remove_parent_cart_item_key( $cart_contents ) {
    $new_cart_contents = array();
    foreach ( $cart_contents as $item_key => $cart_item ) {
        $new_cart_contents[] = $cart_item;
        //array_push($new_cart_contents, $cart_item);
    }
    return $new_cart_contents;
}
add_filter( 'cocart_return_cart_contents', 'remove_parent_cart_item_key', 0 );




// Taxes

add_action('rest_api_init', function () {
    register_rest_route( 'wc/v3', '/custom/taxes',array(
                    'methods'  => 'GET',
                    'callback' => 'get_custom_taxes'
        ));
    });


function get_custom_taxes($request) {

    $userid = $request["userid"];
    $shipping = $request["shipping"];
    $amount = $request["amount"];

    if ($userid && $shipping && $amount) { 


        $state_to_zip = array (
            'AL' => "35004",
            'AK' => "99503",
            'AZ' => "85002",
            'AR' => "71602",
            'CA' => "95020",
            'CO' => "80019",
            'CT' => "06002",
            'DE' => "19702",
            'FL' => "32003",
            'GA' => "30002",
            'HI' => "96818",
            'ID' => "83211",
            'IL' => "60415",
            'IN' => "46011",
            'IA' => "50002",
            'KS' => "66102",
            'KY' => "40004",
            'LA' => "70002",
            'ME' => "04006",
            'MD' => "21224",
            'MA' => "01002",
            'MI' => "48033",
            'MN' => "55111",
            'MS' => "38602",
            'MO' => "63102",
            'MT' => "59003",
            'NE' => "68004",
            'NV' => "89030",
            'NH' => "03033",
            'NJ' => "07002",
            'NM' => "87004",
            'NY' => "10004",
            'NC' => "27006",
            'ND' => "58201",
            'OH' => "43235",
            'OK' => "73111",
            'OR' => "97006",
            'PA' => "15001",
            'RI' => "02802",
            'SC' => "29404",
            'SD' => "57002",
            'TN' => "37011",
            'TX' => "75006",
            'UT' => "84121",
            'VT' => "05001",
            'VA' => "20124",
            'WA' => "98004",
            'WV' => "24898",
            'WI' => "53004",
            'WY' => "82718",
            'DC' => "20001",
        );

        $customer = new WC_Customer ($userid);

        $customer_obj = $customer->get_shipping();

        $to_zip = $customer_obj["postcode"];
        $to_state = $customer_obj["state"];
        
        if (array_key_exists($to_state, $state_to_zip)) {
            $to_zip = $state_to_zip[$to_state];
        }

        #$API_KEY = getenv('TAXJAR_TOKEN');
    $API_KEY = 'd65030005be5cd450908becfb36d89af';

        $taxjar_url = 'https://api.taxjar.com/v2/taxes';

        $fields = array (   
            'to_country' => 'US',
            'to_zip' => $to_zip,
            'to_state' => $to_state,
            //'to_city' => $to_city,
            //'to_street' => $to_street,
            'shipping' => $shipping,
            'amount' => $amount
        );

        $fields_json = json_encode($fields);
        
        try {

            //open connection
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Authorization: Bearer $API_KEY",
                'Content-Type: application/json'
            ));
            
            //set the url, number of POST vars, POST data
            curl_setopt($ch,CURLOPT_URL, $taxjar_url);
            curl_setopt($ch,CURLOPT_POST, count($fields));
            curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_json);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
            
            //execute post
            $result = curl_exec($ch);

            $result_obj = json_decode($result);

            //close connection
            curl_close($ch);

            return $result_obj;

        }
        catch (exception $e) {
            return [
                'status' => 500,
                'error' => "Tax api failed",
                'detail' => "Custom - Exception"
            ];
        }

    } else {
        return [
            'status' => 500,
            'error' => "Missing parameters",
            'detail' => "Custom - Missing parameters"

        ];

    }

}



# Add to Cart API

add_action('rest_api_init', 'custom_wc_add_to_cart');
function custom_wc_add_to_cart() {
    register_rest_route('wc/v3', '/add_cart/(?P<productid>[\d]+)/(?P<userid>[\d]+)', array(
        'methods' => 'GET',
        'callback' => 'add_to_cart_api_custom'
    ));
}

function add_to_cart_api_custom(WP_REST_Request $request) {
    global $woocommerce, $wpdb;
    $product_id = $request["productid"];
    $userid = $request["userid"];
    $array = $wpdb->get_results("select meta_value from ".$wpdb->prefix."usermeta where meta_key = '_woocommerce_persistent_cart_".$product_id."' and user_id = ".$userid);

    if(count($array) !=0 ) {
    $data =$array[0]->meta_value;
    $cart_data=unserialize($data);
    
    $flag = 0;
    foreach($cart_data['cart'] as $key => $val) {
        
        $qty = $cart_data['cart'][$key]['quantity'];
        $product = wc_get_product( $product_id );
        $stock = $product->get_stock_quantity();
        
        if($qty >= $stock) {
            $message = "Product is out of Stock!"; $status = 1;
        }
        else {
            
            $cart_data['cart'][$key] = array(
                'key' => $cart_data['cart'][$key]["key"],
                'product_id' => $product_id,
                'quantity' => $qty+1,
                'line_total' => $cart_data['cart'][$key]['line_total'],
            );
    
            $upd = update_user_meta($userid,'_woocommerce_persistent_cart_'.$product_id, $cart_data);
            if($upd) { $message = "Cart is updated!"; $status = 1; } else { $message = "Failed to update cart"; $status = 0; }
        }
        
      
    }
}
else {
    $variation_id   = 0;
    $variation      = array();
    $cart_item_data = array();
    $variations = 0;
    $product = wc_get_product( $product_id );
    $stock = $product->get_stock_quantity();
    $array_prod = array( 'product_id'=>$product_id, 'uid'=>$userid, 'variation_id'=>$variation_id, 'variation'=>$variation, 'stock'=>$stock);
    $string = md5(serialize($array_prod));
    $product = wc_get_product( $product_id );
        
    $cart_data['cart'][$string] = array(
        'key' => $string,
        'product_id' => $product_id,
        'quantity' => 1,
        'line_total' => $product->get_price(),
    );
    
    $upd = add_user_meta($userid,'_woocommerce_persistent_cart_'.$product_id,$cart_data);
    if($upd) { $message = "Added to cart"; $status = 1; } else { $message = "Failed to add into cart"; $status = 0; }
    }
    echo json_encode(array("status" => $status, "message" => $message));
    //echo $product_id;
    die;
   
}



# Get Cart API

add_action('rest_api_init', 'get_cart_response');
function get_cart_response() {
    
    register_rest_route('wc/v3', '/get-cart/(?P<userid>[\d]+)', array(
        'methods' => 'GET',
        'callback' => 'get_cart_list'
    ));
}

function get_cart_list(WP_REST_Request $request) {
    //echo "hellooss";
    global $wpdb, $woocommerce;
    $user_id = $request["userid"];
    if(isset($user_id)) {
        
        $cart_items = $wpdb->get_results("select meta_value from ".$wpdb->prefix."usermeta where meta_key like '%_woocommerce_persistent_cart_%' and user_id = ".$user_id);
        
        if(count($cart_items) !=0 ) {
            foreach( $cart_items as $cart_key => $cartitems ) {
                
                $cartitem = unserialize($cartitems->meta_value);
                foreach($cartitem["cart"] as $cart_item) {
                    $_product =  wc_get_product( $cart_item['product_id'] );
                    $featured_image = wp_get_attachment_image_url( get_post_thumbnail_id( $cart_item['product_id'] ), 'full');
                    $stock = $_product->get_stock_quantity();
                    $product = array(
                        "product_id"   => $cart_item['product_id'],
                        "title"        => $_product->get_title(),
                        'image'        => $featured_image,
                        'quantity'     => $cart_item['quantity'],
                        'stock'        => $stock,
                        'total'        => $_product->get_price()
                    );
                    $products[] = $product;
                }               
            }
            $message = "Data found";
            $status = 1;
        }
        else {
            $message = "Data not found";
            $status = 1;
            $products = array();
        }
    }
    else {
        $message = "User id is missing";
        $status = 0;
        $products = array();
    }
    echo json_encode(array("status" => $status, "message" => $message, "data"=>$products));
    die;
}


# Delete Cart API

add_action('rest_api_init', 'delete_cart_response');
function delete_cart_response() {
    
    register_rest_route('wc/v3', '/delete-cart-item/(?P<productid>[\d]+)/(?P<userid>[\d]+)', array(
        'methods' => 'GET',
        'callback' => 'delete_cart_api'
    ));
}

function delete_cart_api(WP_REST_Request $request) {
    global $wpdb, $woocommerce;
    $productid = $request["productid"];
    $user_id = $request["userid"];
    if(isset($productid) && isset($user_id)) {
        
        $cart_items = get_user_meta( $user_id, '_woocommerce_persistent_cart_'.$productid);
        if(count($cart_items) !=0 ) {
            $upd = delete_user_meta( $user_id, '_woocommerce_persistent_cart_'.$productid); 
            if($upd) {
                $message = "Success ! Item delete from cart.";
                $status = 1;
            } else {
                $message = "Failed to delete from cart.";
                $status = 0;
            }
        }
        else {
            $message = "Found no Product in cart!";
            $status = 0;
        }
    }
    else {
        $message = "Product ID and User ID are missing";
        $status = 0;
    }
    echo json_encode(array("status" => $status, "message"=> $message));
    die;
}



# Count Cart Total Items API

add_action('rest_api_init', 'get_cart_total');
function get_cart_total() {
    
    register_rest_route('wc/v3', '/count-items/(?P<userid>[\d]+)', array(
        'methods' => 'GET',
        'callback' => 'cart_total_items'
    ));
}

function cart_total_items(WP_REST_Request $request) {
    global $wpdb, $woocommerce;
    $user_id = $request["userid"];
    if(isset($user_id)) {
        $cart_items = $wpdb->get_results("select meta_value from ".$wpdb->prefix."usermeta where meta_key like '%_woocommerce_persistent_cart_%' and user_id = ".$user_id);
        
        if(count($cart_items) !=0 ) {
            foreach( $cart_items as $cart_key => $cartitems ) {
                
                $cartitem = unserialize($cartitems->meta_value);
                foreach($cartitem["cart"] as $key => $cart_item) {
                    $totals[] = $key;
                }               
            }
            $total = count($totals);
            $message = "Data found";
            $status = 1;
        } else {
            $message = "Success";
            $status = 1;
            $total = count($total);
        }
    }
    else {
        $message = "User id is missing";
        $status = 0;
        $total = array();
    }
    echo json_encode(array("status" => $status, "message" => $message, "total"=>$total));
    //echo "count : ".count($keys);
    die;
}

# Calculation API

add_action('rest_api_init', 'get_calculation_api');
function get_calculation_api() {
    
    register_rest_route('wc/v3', '/checkout-detail/', array(
        'methods' => 'POST',
        'callback' => 'calculation_details'
    ));
}

function calculation_details(WP_REST_Request $request) {
    global $wpdb, $woocommerce;
    
    $user_id = $request["userid"];
    if(isset($user_id)) {
        $cart_items = $wpdb->get_results("select meta_value from ".$wpdb->prefix."usermeta where meta_key like '%_woocommerce_persistent_cart_%' and user_id = ".$user_id);
        

        if(count($cart_items) !=0 ) {

            $user_data = get_user_meta($user_id);
            $get_user_email = get_user_by( 'id', $user_id );
            $user_email = $get_user_email->data->user_email;

            $billing_detail['first_name'] = isset($user_data["billing_first_name"]) ? $user_data["billing_first_name"][0] : '';
            $billing_detail['last_name'] = isset($user_data["billing_last_name"]) ? $user_data["billing_last_name"][0] : '';
            $billing_detail['address_1'] = isset($user_data["billing_address_1"]) ? $user_data["billing_address_1"][0] : '';
            $billing_detail['email'] = isset($user_data["billing_email"]) ? $user_data["billing_email"][0] : $user_email;
            $billing_detail['city'] = isset($user_data["billing_city"]) ? $user_data["billing_city"][0] : '';
            $billing_detail['state'] = isset($user_data["billing_state"]) ? $user_data["billing_state"][0] : '';
            $billing_detail['postcode'] = isset($user_data["billing_postcode"]) ? $user_data["billing_postcode"][0] : '';
            $billing_detail['phone'] = isset($user_data["billing_phone"]) ? $user_data["billing_phone"][0] : '';

            $shipping_detail['first_name'] = isset($user_data["shipping_first_name"]) ? $user_data["shipping_first_name"][0] : '';
            $shipping_detail['last_name'] = isset($user_data["shipping_last_name"]) ? $user_data["shipping_last_name"][0] : '';
            $shipping_detail['address_1'] = isset($user_data["shipping_address_1"]) ? $user_data["shipping_address_1"][0] : '';
            $billing_detail['email'] = isset($user_data["shipping_email"]) ? $user_data["shipping_email"][0] : $user_email;
            $shipping_detail['city'] = isset($user_data["shipping_city"]) ? $user_data["shipping_city"][0] : '';
            $shipping_detail['state'] = isset($user_data["shipping_state"]) ? $user_data["shipping_state"][0] : '';
            $shipping_detail['postcode'] = isset($user_data["shipping_postcode"]) ? $user_data["shipping_postcode"][0] : '';

            foreach( $cart_items as $cart_key => $cartitems ) {
                
                $cartitem = unserialize($cartitems->meta_value);
                foreach($cartitem["cart"] as $cart_item) {
                    //$totals[] = $key;
                    $checkQty[] = $cart_item['quantity'];
                    $product =  wc_get_product( $cart_item['product_id'] );
                    $pro_price = $product->get_price();
                    $totals[] = $cart_item['quantity'] * $pro_price;
                }               
            }

            if(!empty($checkQty))
            {

                if(array_sum($checkQty) > 1)
                {
                    $check_qty = (array_sum($checkQty) - 1) * 5;
                    $shipping[] = $check_qty + 15;
                }
                else
                {
                    $shipping[] = 15;
                }
            }

            $total = array_sum($totals);
            $shipping = array_sum($shipping);

            if ($user_id && $shipping && $total) { 
                $state_to_zip = array (
                    'AL' => "35004",
                    'AK' => "99503",
                    'AZ' => "85002",
                    'AR' => "71602",
                    'CA' => "95020",
                    'CO' => "80019",
                    'CT' => "06002",
                    'DE' => "19702",
                    'FL' => "32003",
                    'GA' => "30002",
                    'HI' => "96818",
                    'ID' => "83211",
                    'IL' => "60415",
                    'IN' => "46011",
                    'IA' => "50002",
                    'KS' => "66102",
                    'KY' => "40004",
                    'LA' => "70002",
                    'ME' => "04006",
                    'MD' => "21224",
                    'MA' => "01002",
                    'MI' => "48033",
                    'MN' => "55111",
                    'MS' => "38602",
                    'MO' => "63102",
                    'MT' => "59003",
                    'NE' => "68004",
                    'NV' => "89030",
                    'NH' => "03033",
                    'NJ' => "07002",
                    'NM' => "87004",
                    'NY' => "10004",
                    'NC' => "27006",
                    'ND' => "58201",
                    'OH' => "43235",
                    'OK' => "73111",
                    'OR' => "97006",
                    'PA' => "15001",
                    'RI' => "02802",
                    'SC' => "29404",
                    'SD' => "57002",
                    'TN' => "37011",
                    'TX' => "75006",
                    'UT' => "84121",
                    'VT' => "05001",
                    'VA' => "20124",
                    'WA' => "98004",
                    'WV' => "24898",
                    'WI' => "53004",
                    'WY' => "82718",
                    'DC' => "20001",
                );

                $customer = new WC_Customer ($user_id);

                $customer_obj = $customer->get_shipping();

                $to_zip = $customer_obj["postcode"];
                $to_state = $customer_obj["state"];
                
                if (array_key_exists($to_state, $state_to_zip)) {
                    $to_zip = $state_to_zip[$to_state];
                }

                #$API_KEY = getenv('TAXJAR_TOKEN');
                $API_KEY = 'd65030005be5cd450908becfb36d89af';

                $taxjar_url = 'https://api.taxjar.com/v2/taxes';

                $fields = array (   
                    'to_country' => 'US',
                    'to_zip' => $to_zip,
                    'to_state' => $to_state,
                    //'to_city' => $to_city,
                    //'to_street' => $to_street,
                    'shipping' => $shipping,
                    'amount' => $total
                );

                $fields_json = json_encode($fields);
                    
                try {

                    $ch = curl_init();

                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        "Authorization: Bearer $API_KEY",
                        'Content-Type: application/json'
                    ));
                    
                    curl_setopt($ch,CURLOPT_URL, $taxjar_url);
                    curl_setopt($ch,CURLOPT_POST, count($fields));
                    curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_json);
                    curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
                    
                    $result = curl_exec($ch);

                    $result_obj = json_decode($result);

                    foreach ($result_obj as $result_object) {
                       $tax = $result_object->amount_to_collect;
                    }

                    curl_close($ch);

                }
                catch (exception $e) {
                    $tax = '';
                }

            } else {
                $tax = '';
            }


            $checkout_data['total'] = $total;
            $checkout_data['shipping'] = $shipping; 
            $checkout_data['tax'] = $tax;
            $checkout_data['billing'] = $billing_detail;
            $checkout_data['shipping_detail'] = $shipping_detail;
            $message = "Data found";
            $status = 1;
        }
        else
        {
            $message = "Success";
            $status = 1;
            $checkout_data['total'] = '';
            $checkout_data['shipping'] = '';
            $checkout_data['tax'] = '';
            $checkout_data['billing'] = '';
            $checkout_data['shipping_detail'] = ''; 
        } 

    }
    else {
        $message = "User id is missing";
        $status = 0;
        $checkout_data['total'] = '';
        $checkout_data['shipping'] = '';
        $checkout_data['tax'] = '';
        $checkout_data['billing'] = '';
        $checkout_data['shipping_detail'] = '';
    }
    echo json_encode(array("status" => $status, "message" => $message, "data"=>$checkout_data));
    //echo "count : ".count($keys);
    die;
}

# Create Order

add_action('rest_api_init', 'create_order_api');
function create_order_api() {
    
    register_rest_route('wc/v3', '/create-order/', array(
        'methods' => 'POST',
        'callback' => 'create_order'
    ));
}

function create_order(WP_REST_Request $request) {
    global $wpdb, $woocommerce;
    
    $user_id = $request["userid"];
    $shipping_same_as_billing = $request["shipping_same_as_billing"];
    $tax = $request['tax'];


    if(isset($user_id)) {
        $user_data = get_user_meta($user_id);
        $get_user_email = get_user_by( 'id', $user_id );
        $user_email = $get_user_email->data->user_email;
        
        if($shipping_same_as_billing === true)
        {
            $address['first_name'] = isset($user_data["shipping_first_name"]) ? $user_data["shipping_first_name"][0] : '';
            $address['last_name'] = isset($user_data["shipping_last_name"]) ? $user_data["shipping_last_name"][0] : '';
            $address['company'] = isset($user_data["shipping_last_name"]) ? $user_data["shipping_last_name"][0] : '';
            $address['email'] = isset($user_data["shipping_email"]) ? $user_data["shipping_email"][0] : $user_email;
            $address['phone'] = isset($user_data["shipping_phone"]) ? $user_data["shipping_phone"][0] : '';
            $address['address_1'] = isset($user_data["shipping_address_1"]) ? $user_data["shipping_address_1"][0] : '';
            $address['address_2'] = isset($user_data["shipping_address_2"]) ? $user_data["shipping_address_2"][0] : '';
            $address['city'] = isset($user_data["shipping_city"]) ? $user_data["shipping_city"][0] : '';
            $address['state'] = isset($user_data["shipping_state"]) ? $user_data["shipping_state"][0] : '';
            $address['postcode'] = isset($user_data["shipping_postcode"]) ? $user_data["shipping_postcode"][0] : ''; 
            $address['country'] = isset($user_data["shipping_country"]) ? $user_data["shipping_country"][0] : ''; 
        }else {
            $billing_address['first_name'] = isset($user_data["billing_first_name"]) ? $user_data["billing_first_name"][0] : '';
            $billing_address['last_name'] = isset($user_data["billing_last_name"]) ? $user_data["billing_last_name"][0] : '';
            $billing_address['company'] = isset($user_data["billing_company"]) ? $user_data["billing_company"][0] : '';
            $billing_address['email'] = isset($user_data["billing_email"]) ? $user_data["billing_email"][0] : $user_email;
            $billing_address['phone'] = isset($user_data["billing_phone"]) ? $user_data["billing_phone"][0] : '';
            $billing_address['address_1'] = isset($user_data["billing_address_1"]) ? $user_data["billing_address_1"][0] : '';
            $billing_address['address_2'] = isset($user_data["billing_address_2"]) ? $user_data["billing_address_2"][0] : '';
            $billing_address['city'] = isset($user_data["billing_city"]) ? $user_data["billing_city"][0] : '';
            $billing_address['state'] = isset($user_data["billing_state"]) ? $user_data["billing_state"][0] : '';
            $billing_address['postcode'] = isset($user_data["billing_postcode"]) ? $user_data["billing_postcode"][0] : '';
            $billing_address['country'] = isset($user_data["billing_country"]) ? $user_data["billing_country"][0] : '';


            $address['first_name'] = isset($user_data["shipping_first_name"]) ? $user_data["shipping_first_name"][0] : '';
            $address['last_name'] = isset($user_data["shipping_last_name"]) ? $user_data["shipping_last_name"][0] : '';
            $address['company'] = isset($user_data["shipping_last_name"]) ? $user_data["shipping_last_name"][0] : '';
            $address['email'] = isset($user_data["shipping_email"]) ? $user_data["shipping_email"][0] : $user_email;
            $address['phone'] = isset($user_data["shipping_phone"]) ? $user_data["shipping_phone"][0] : '';
            $address['address_1'] = isset($user_data["shipping_address_1"]) ? $user_data["shipping_address_1"][0] : '';
            $address['address_2'] = isset($user_data["shipping_address_2"]) ? $user_data["shipping_address_2"][0] : '';
            $address['city'] = isset($user_data["shipping_city"]) ? $user_data["shipping_city"][0] : '';
            $address['state'] = isset($user_data["shipping_state"]) ? $user_data["shipping_state"][0] : '';
            $address['postcode'] = isset($user_data["shipping_postcode"]) ? $user_data["shipping_postcode"][0] : ''; 
            $address['country'] = isset($user_data["shipping_country"]) ? $user_data["shipping_country"][0] : ''; 
        }
        

        $cart_items = $wpdb->get_results("select meta_value from ".$wpdb->prefix."usermeta where meta_key like '%_woocommerce_persistent_cart_%' and user_id = ".$user_id);
        

        if(count($cart_items) !=0 ) {

            $order = wc_create_order();

            foreach( $cart_items as $cart_key => $cartitems ) {
                
                $cartitem = unserialize($cartitems->meta_value);
                foreach($cartitem["cart"] as $cart_item) {
                    $checkQty[] = $cart_item['quantity'];
                    $order->add_product( get_product( $cart_item['product_id']), $cart_item['quantity'] );
                }               
            }

            if(!empty($checkQty))
            {

                if(array_sum($checkQty) > 1)
                {
                    $check_qty = (array_sum($checkQty) - 1) * 5;
                    $shipping_cost[] = $check_qty + 15;
                }
                else
                {
                    $shipping_cost[] = 15;
                }
            }

            $shippingprice = array_sum($shipping_cost);
            if($shipping_same_as_billing === true)
            {
                $order->set_address( $address, 'billing' );
                $order->set_address( $address, 'shipping' );
            }
            else
            {
                $order->set_address( $billing_address, 'billing' );
                $order->set_address( $address, 'shipping' );
            }
            
            update_post_meta($order->get_id(), '_customer_user', $user_id);
            add_post_meta($order->get_id(), 'order_tax', $tax);

            $country_code = $order->get_shipping_country();
            $calculate_tax_for = array(
                'country' => $country_code,
            );
            $item = new WC_Order_Item_Shipping();

            $item->set_method_title( "Custom Shipping Rate" );
            $item->set_method_id( "custom_rate" ); 
            $item->set_total( $shippingprice );
            $item->calculate_taxes($calculate_tax_for);

            $order->add_item( $item );

            if($tax == 0)
            {
                $tax_status = 'none';
            }
            else
            {
                $tax_status = 'taxable';
            }

            $item_fee = new WC_Order_Item_Fee();

            $item_fee->set_name( "Tax" );
            $item_fee->set_amount( $tax ); 
            $item_fee->set_tax_class( '' ); 
            $item_fee->set_tax_status( $tax_status );
            $item_fee->set_total( $tax );
            $item_fee->calculate_taxes( $calculate_tax_for );

            $order->add_item( $item_fee );
            $order_key = get_post_meta($order->get_id(), '_order_key', $user_id);

            $order->calculate_totals();
            $order->save();

            //print_r($order);
            $message = "Order Created";
            $status = 1;
            $data['order_key'] = $order_key;
        }
        else
        {
            $message = "Success";
            $status = 1; 
            $data =array();

        } 

    }
    else {
        $message = "User id is missing";
        $status = 0;
        $data =array();
    }
    
    echo json_encode(array("status" => $status, "message" => $message, "data" => $data));
    die;
}


# Product Search

add_action('rest_api_init', 'product_search_api');
function product_search_api() {
    
    register_rest_route('wc/v3', '/product-search/', array(
        'methods' => 'POST',
        'callback' => 'search_product'
    ));
}

function search_product(WP_REST_Request $request) {
    global $wpdb, $woocommerce;
    
    $product_title = $request["title"];

    $args = array("post_type" => "product", "s" => $product_title);
    $query = new WP_Query( $args );

    if( $query->have_posts() ):
        $aa = 0;
        while( $query->have_posts() ): $query->the_post();

            $pro_image = wp_get_attachment_image_src(get_post_thumbnail_id(), 'full');

            $product_detail[$aa]['id'] = get_the_ID();
            $product_detail[$aa]['name'] = get_the_title();

            $product =  wc_get_product( get_the_ID() );
            $pro_price = $product->get_price();

            $product_detail[$aa]['price'] = $pro_price;
            $product_detail[$aa]['date_created'] = get_the_date('Y-m-d').'T'.get_the_date('h:i:s');
            $product_detail[$aa]['image'] = $pro_image[0];
           $aa++; 
        endwhile;
        $message = "Product Found";
        $status = 1;
        $data = $product_detail;
    else:
        $message = "No Product Found";
        $status = 0;
        $data =array();
    endif;

    
    
    echo json_encode(array("status" => $status, "message" => $message, "data" => $data));
    die;
}
