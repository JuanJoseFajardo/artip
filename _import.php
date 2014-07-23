<?php

const ARTINPOCKET_CAT = true;

if (ARTINPOCKET_CAT)
	define('DOMINI' , 'http://www.artinpocket.cat');
else
	define('DOMINI' , 'http://www.inpocketart.com');	

// require("./wp-load.php");

require_once( $_SERVER['DOCUMENT_ROOT'] . '/artinpocket/wp-config.php' );
require_once( $_SERVER['DOCUMENT_ROOT'] . '/artinpocket/wp-includes/wp-db.php' );
$wpdb_other = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);

set_time_limit(0);
ini_set('memory_limit', '2048M');

// $query = $wpdb_other->prepare('SELECT * FROM wp_postmeta WHERE post_id=%d',2199);
// $result = $wpdb_other->get_results($query);
// echo "<ul>";
// foreach ($result as $obj) :
//    echo "<li>".$obj->meta_key. "-" . $obj->meta_value."</li>";
// endforeach;
// echo "</ul>";

$descripcio = "descripcio OBRA";
$titol      = "titol OBRA";
$postName   = "Nom del Post";

$postValors = (object)array(
	'postType' 	 => 'attachment',
	'postParent' => 0,
	'descripcio' => $descripcio,
	'titol'      => $titol,
	'excerpt'	 => '',
	'postName'   => $postName,
	'mimeType'	 => '',
	'guid'		 => '',
	'attachment' => array(),
	);

$postValors->attachment[0] = (object)array(
	'any'		 => 2014,
	'mes'		 => '07',
	'filename'   => $titol . '.jpg'
	);

$postValors->attachment[1] = (object)array(
	'any'		 => 2014,
	'mes'		 => '07',
	'filename'   => $titol . '_1.jpg'
	);

$postValors->attachment[2] = (object)array(
	'any'		 => 2014,
	'mes'		 => '07',
	'filename'   => $titol . '_2.jpg'
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


/////////////////////////////////////////////////////////////////////////


$post_ids = addPost( $wpdb_other, $postValors );

// var_dump($post_ids);

// addPostMeta( $post_ids, $postMetaValors );

/////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////


function addPost( $wpdb_other, $postValors )
{
	$post_ids = array();
    $post = getArrayPost( $postValors);
    if($post_ids[] = wp_insert_post($post))
    	{
    	updateGuid( $wpdb_other, $post_ids[0]);
    	print_r($post_ids[0]);
		$myvals = get_post($post_ids[0]);
		echo "<pre>";
		foreach($myvals as $key=>$val) echo $key . ' : ' . $val . '<br/>';    	
    	echo '<br/><br/><br/>';

		// $postValors->postType   = 'attachment';
		// $postValors->excerpt    = $postValors->titol;
		// $postValors->mimeType   = 'image/jpeg';
		// $postValors->postParent = $post_ids[0];
		// foreach ($postValors->attachment as $key => $attData)
		// 	{
		// 	foreach ($attData as $key1)
	 //    		$attachment = DOMINI .'/wp-content/uploads/' .
	 //    								$attData->any .  '/' .
	 //    								$attData->mes .  '/' .
	 //    								$attData->filename;
		// 	$postValors->guid = $attachment;	    	
		//     $post        = getArrayPost( $postValors );
  //   		$post_ids[]  = wp_insert_post($post);
		// 	}
		}
	return ($post_ids);
}

function updateGuid($wpdb_other, $post_id)
{
	$attachment =  DOMINI . '?post_type=product&#038;p=' . $post_id;
	var_dump($attachment);

	$postGuid = array(
  				'ID'   => $post_id,
  				'guid' => $attachment,
  				);
	wp_update_post($postGuid);


// 	$rows = $wpdb_other->update( 
//     'wp_post', 
//     array( 
//         'post_type' => 'product'
//     ), 
//     array( 'ID' => $post_id ), 
//     array( 
//         '%s'
//     ), 
//     array( '%d' ) 
// );



	$query = $wpdb_other->prepare("UPDATE $wpdb_other->posts SET post_type = '%s' WHERE ID='%d';",'product', $post_id);
	// $query = 'UPDATE $wpdb_other>posts SET post_title = "' . $attachment . '" WHERE ID=' . $post_id .";";
	$result = $wpdb_other->get_results($query);
	var_dump($query);
	var_dump($result);


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
        'post_excerpt'          => $postValors->excerpt,
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
		'post_parent'           => $postValors->postParent,
		'guid'                  => $postValors->guid,
		'menu_order'            => 0,
        'post_type'             => $postValors->postType,
        'post_mime_type'        => $postValors->mimeType,
        'comment_count'         => 0
    );
	return ($post);
}


function addPostMeta($post_ids, $postMetaValors)
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
		'_downloadable'          => 'no',
		'_virtual'               => 'no',
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
