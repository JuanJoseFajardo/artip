<?php


// require("./wp-load.php");

require_once( $_SERVER['DOCUMENT_ROOT'] . '/artinpocket/wp-config.php' );
require_once( $_SERVER['DOCUMENT_ROOT'] . '/artinpocket/wp-includes/wp-db.php' );
$wpdb_other = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);

set_time_limit(0);
ini_set('memory_limit', '2048M');

// $query = $wpdb->prepare('SELECT * FROM wp_postmeta WHERE post_id=%d',2199);
// $result = $wpdb->get_results($query);
// echo "<ul>";
// foreach ($result as $obj) :
//    echo "<li>".$obj->meta_key. "-" . $obj->meta_value."</li>";
// endforeach;
// echo "</ul>";

$descripcio = "descripcio OBRA";
$titol      = "titol OBRA";
$postName   = "Nom del Post";

$postValors = (object)array(
	'descripcio' => $descripcio,
	'titol'      => $titol,
	'postName'   => $postName
	);

$sku       = 'W999';
$preu      = 999;
$stock     = 99;
$voucherId = 1805;

$postMetaValors = (object)array(
	'sku' => $sku,
	'preu' => $preu,
	'stock' => $stock,
	'voucherId' => $voucherId
	);


addPost( $postValors, $postMetaValors );

$bigArray = (array)maybe_unserialize(get_post_meta(2261, '_wp_attachment_metadata', true));
var_dump($bigArray);
foreach($bigArray as $key=>$val) 
	{
	echo $key . ' : ' . $val . '<br/>';
	if (gettype($val) == 'array')
		foreach($val as $key1=>$val1)
			{
			echo $key1 . ' : ' . $val1 . '<br/>';
			if (gettype($val1) == 'array')
				foreach($val1 as $key2=>$val2)
					echo $key2 . ' : ' . $val2 . '<br/>';
			}
	}
echo '<br/><br/><br/>';

my_attachment_all_images(2260);

// $sellerArray=$sellerBigArray['billing-seller'];

// $seller=$sellerArray['value'];


/////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////


function my_attachment_all_images($postid=0, $size='full', $attributes='') {
    if ($postid<1) $postid = get_the_ID();
    if ($images = get_children(array(
        'post_parent' => $postid,
        'post_type' => 'attachment',
        'numberposts' => -1,
        'orderby' => 'menu_order',
        'post_mime_type' => 'image',)))
        foreach($images as $image) {
            $attachment=wp_get_attachment_image_src($image->ID, $size);
            var_dump($attachment);
            ?><img src="<?php echo $attachment[0]; ?>" alt="" class="imarge" /><?php
        }
}



function addPost( $postValors, $postMetaValors )
{
    $post = getArrayPost( $postValors );
    if($post_id = wp_insert_post($post))
    	{
    	print_r($post_id);
		$myvals = get_post($post_id);
		echo "<pre>";
		foreach($myvals as $key=>$val) echo $key . ' : ' . $val . '<br/>';    	
    	echo '<br/><br/><br/>';


    	addPostMeta( $post_id, $postMetaValors);
		}
}

function getArrayPost( $postValors )
{
	$dateTime = date("Y-n-j H:i:s");
	$post = array(
		'post_author'           => 1,
		'post_date'             => $dateTime,
		'post_date_gmt'         => $dateTime,
        'post_content'          => $postValors->descripcio,
        'post_title'            => $postValors->titol,
        'post_excerpt'          => 'excerpt',
        'post_status'           => 'publish', 
        'comment_status'        => 'open', 
        'ping_status'           => 'open', 
		'post_password'         => '',
		'post_name'             => $postValors->postName,
		'to_ping'               => '',
		'pinged'                => '',
		'post_modified'         => $dateTime,
		'post_modified_gmt'     => $dateTime,
		'post_content_filtered' => '',
		'post_parent'           => 0,
		'guid'                  => '??????????',
		'menu_order'            => 0,
        'post_type'             => 'product',
        'post_mime_type'        => '',
        'comment_count'         => 0
    );
	return ($post);
}


function addPostMeta($post_id, $postMetaValors)
{
	$postMeta = (object)getArrayPostMeta( $postMetaValors );
	foreach($postMeta as $key=>$val)
		{
		update_post_meta($post_id, $key, $key->val);
		echo $key . ' : ' . $key->val . '<br/>';
		}

}

function getArrayPostMeta( $postMetaValors )
{
	$postMeta = array(
		'total_sales'            => 0,
		'_edit_lock'             => '99999999999',
		'_edit_last'             => '1',
		'_thumbnail_id'          => '999999999',
		'_visibility'            => 'visible',
		'_stock_status'          => 'instock',
		'_downloadable'          => 'yes',
		'_virtual'               => 'yes',
		'_regular_price'         => $postMetaValors->preu,
		'_sale_price'            => '',
		'_purchase_note'         => '',
		'_featured'              => 'no',
		'_weight'                => '',
		'_length'                => '',
		'_width'                 => '',
		'_height'                => '',
		'_sku'                   => $postMetaValors->sku,
		'_product_attributes'    => "a:0:{}",
		'_sale_price_dates_from' => '',
		'_sale_price_dates_to'   => '',
		'_price'                 => $postMetaValors->preu,
		'_sold_individually'     => 'yes',
		'_manage_stock'          => 'yes',
		'_backorders'            => 'no',
		'_stock'                 => $postMetaValors->stock,
		'_downloadable_files'    => '',
		'_download_limit'        => '',
		'_download_expiry'       => '',
		'_download_type'         => '',
		'_download_type'         => '',
		'_voucher_id'            => $postMetaValors->voucherId,
		'_product_image_gallery' => ''
		);

	return ($postMeta);
}


?>
