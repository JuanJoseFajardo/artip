<?php

const ARTINPOCKET_CAT    = true;
const ORIGEN_UPLOADS     = './_obres.cat/'; 
const DESTINACIO_UPLOADS = './wp-content/uploads/'; 

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



/////////////////////////////////////////////////////////////////////////


function importExport()
{


$res = "";

// iteracio 1

$dades = getDadesOrigen();

if ($dades->status) != "error")
	{
	foreach ($variable as $key => $value)
		{
		$arrayDades = getDades($dades->records);

		$id_origen = 999;
		$nomImgOrigen = $id_origen;
		$nomImgDesti  = "?????????????????";
		$any = "2015";
		$mes = "07";

		$post_ids = addPost( $postValors );

		var_dump($post_ids);

		addPostMeta( $post_ids, $postValors, $postMetaValors );

		insertarCategoriaTag($wpdb, $post_ids[0], 'product_cat', "nom categoria", "descripcio categoria");

		$res = renombraImatge($nomImgOrigen, $nomImgDesti, $any, $mes);
		if ($res != "") $res1 .= $res . ",";
		}


if ($res1 != "") echo '</br>Error en imatges: ' . $res1;


/////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////

function getDades()
{

$descripcio = "descripcio OBRA";
$titol      = "titol OBRA";
$postName[0]   = "Nom del Post-product";
$postName[1]   = "Nom del Post-attachment-1";
$postName[2]   = "Nom del Post-attachment-2";
$postName[3]   = "Nom del Post-attachment-3";

$any[0] = 2014;
$any[1] = 2014;
$any[2] = 2014;

$mes[0] = '07';
$mes[1] = '07';
$mes[2] = '07';

$filename[0] = $postName[1] . '.jpg';
$filename[1] = $postName[2] . '_1.jpg';
$filename[2] = $postName[3] . '_2.jpg';


$postValors = (object)array(
	'postType' 	 => '',
	'postParent' => 0,
	'descripcio' => $descripcio,
	'titol'      => $titol,
	'excerpt'	 => '',
	'postName'   => $postName[0],
	'mimeType'	 => '',
	'guid'		 => '',
	'attachment' => array(),
	);

$postValors->attachment[0] = (object)array(
	'postName'   => $postName[1],
	'any'		 => $any[0],
	'mes'		 => $mes[0],
	'filename'   => $filename[0]
	);

$postValors->attachment[1] = (object)array(
	'postName'   => $postName[2],	
	'any'		 => $any[1],
	'mes'		 => $mes[1],
	'filename'   => $filename[1]
	);

$postValors->attachment[2] = (object)array(
	'postName'   => $postName[3],	
	'any'		 => $any[2],
	'mes'		 => $mes[2],
	'filename'   => $filename[2]
	);


$sku       = 'W999';
$preu      = 999;
$stock     = 99;
$voucherId = 1805;
$pes       = '';
$llarg     = '';
$ample     = '';
$alt       = '';

$postMetaValors = (object)array(
	'post_id' 		 => 0,
	'images_gallery' => '',
	'sku'            => $sku,
	'preu'           => $preu,
	'stock'          => $stock,
	'pes'            => $pes,
	'llarg'          => $llarg,
	'ample'          => $ample,
	'alt'            => $alt	
	);

}

function getDadesOrigen()
{
	$query = sprintf("",);
	$response = (object)controlErrorQuery( dbExec($query) );
	return ($response);
}

function renombraImatge($img_id, $nomImg, $any, $mes)
{
	$res = "";
	$imgOrigen = ORIGEN_UPLOADS . $img_id . ".jpg";
	$imgDesti  = DESTINACIO_UPLOADS . '/' . $any . '/' . $mes . '/' . $nomImg . ".jpg";
	if (file_exists($imgOrigen))
		{
		$res = rename($imgOrigen, $imgDesti);
		if ($res==0) $res = $img_id . '.jpg';
		}
	else
		$res = $img_id . '.jpg';
	return ($res);
}

function addPost( $postValors )
{
	$post_ids = array();
	$postValors->postType = 'product';
    $post = getArrayPost( $postValors);
    if($post_ids[] = wp_insert_post($post))
    	{
    	if (updateGuid( $post_ids[0]) != 'error')
    		{
	    	print_r($post_ids[0]);
			$myvals = get_post($post_ids[0]);
			echo "<pre>";
			foreach($myvals as $key=>$val) echo $key . ' : ' . $val . '<br/>';    	
	    	echo '<br/><br/><br/>';

			$postValors->postType   = 'attachment';
			$postValors->excerpt    = $postValors->titol;
			$postValors->mimeType   = 'image/jpeg';
			$postValors->postParent = $post_ids[0];
			foreach ($postValors->attachment as $key => $attData)
				{
	    		$attachment = DOMINI .'/wp-content/uploads/' .
	    								$attData->any .  '/' .
	    								$attData->mes .  '/' .
	    								$attData->filename;
		    	$postValors->postName = $attData->postName;
				$postValors->guid = $attachment;	    	
			    $post        = getArrayPost( $postValors );
	    		$post_ids[]  = wp_insert_post($post);
				}
	    	}
		}
	return ($post_ids);
}

function updateGuid($post_id)
{
	$attachment =  DOMINI . '?post_type=product&#038;p=' . $post_id;
	$query = sprintf("UPDATE wp_posts SET guid = '%s'
							WHERE ID='%d';",$attachment,
											$post_id);
	$response = controlErrorQuery( dbExec($query,0) );
	return($response['status']);
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


function addPostMeta($post_ids, $postValors, $postMetaValors)
{
	$postMetaValors->post_id = $post_ids[0];
	$aa = implode ( ',', $post_ids);
	$postMetaValors->images_gallery = substr($aa, strpos($aa, ',')+1, 99);
	$postMeta = getArrayPostMeta( $postMetaValors );
	foreach($postMeta as $key=>$val)
		{
		update_post_meta($post_ids[0], $key, $val);
		echo $key . ' : ' . $val . '<br/>';
		}
	$i = 0;
	foreach ($postValors->attachment as $key => $attData)
		{
		$attachment = $attData->any .  '/' . $attData->mes .  '/' . $attData->filename;
		update_post_meta($post_ids[$i++], '_wp_attached_file', $attachment);
		}
}

function getArrayPostMeta( $postMetaValors )
{
	$postMeta = array(
		'total_sales'            => 0,
		'_edit_lock'             => (time().':1'),
		'_edit_last'             => '1',
		'_thumbnail_id'          => $postMetaValors->post_id,
		'_visibility'            => 'visible',
		'_stock_status'          => 'instock',
		'_downloadable'          => 'no',
		'_virtual'               => 'no',
		'_regular_price'         => $postMetaValors->preu,
		'_sale_price'            => '',
		'_purchase_note'         => '',
		'_featured'              => 'no',
		'_weight'                => $postMetaValors->pes,
		'_length'                => $postMetaValors->llarg,
		'_width'                 => $postMetaValors->ample,
		'_height'                => $postMetaValors->alt,
		'_sku'                   => $postMetaValors->sku,
		'_product_attributes'    => array(),
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
		'_voucher_id'            => '',
		'_product_image_gallery' => $postMetaValors->images_gallery
		);
	return ($postMeta);
}

function insertarCategoriaTag($wpdb, $post_id, $tipusTerm, $term, $descTerm)
{
$tt_array = wp_insert_term(
  $term, // the term 
  $tipusTerm, // the taxonomy
  array(
    'description'=> $descTerm,
    'slug' => '',
    'parent'=> 0
  )
);

if ($tt_array->error_data)
	{
	$tt = $tt_array->error_data;
	$tt = $tt['term_exists'];
	}
else
	$tt = $tt_array['term_id'];

$wpdb->insert( $wpdb->term_relationships, array( 'object_id' => $post_id, 'term_taxonomy_id' => $tt ) ); 

}


function dbExec($query, $tipusResultat=1) {
    /* 
     * Executa : string amb consulta SQL
     * Retorna : string JSON
     */
         
    $estat = (object)array('error' => false, 'msg' => '' , 'numerr' => 0);
    $dades  = array();

    if (!isset($query) ) die('<h1>No es una consulta correcte !</h1>');

    $link = mysql_connect("localhost", "root", "");

    if (!$link) die('Not connected : ' . mysql_error());

    $db_selected = mysql_select_db(DB_NAME, $link);

    if (!$db_selected) {
        die ('No es possible utilitzar la bd: ' . mysql_error());
    }

    $result = mysql_query($query);

    if (!$result)
        $estat = (object)array('error' => true, 'msg' => mysql_error(), 'numerr' => mysql_errno());
    else
        {
        switch($tipusResultat)
            {
            // retorna un array associatiu (SELECT)
            case 1:
                while ($obj = mysql_fetch_assoc($result)) 
                    $dades[] = (object)array_map('utf8_encode', $obj) ;
                break;
            // insert
            case 2:
                $dades = mysql_insert_id();
                break;
            // update
            case 3:
                $dades = mysql_affected_rows();
                break;
            default:
                $dades = true;
                break;
            }
        }
   
    $retorn = array();
    array_push($retorn, $estat);
    array_push($retorn, $dades);    
    
    return $retorn;
    
    // Alliberar resultat
    mysql_free_result($result);

    // Tancar connexió
    mysql_close($link);

}

function controlErrorQuery($response)
{
    $estat = $response[0];
    if ( $estat->error )
        $response = array("status" => "error", "message" => "Error " . $estat->numerr . "." . $estat->msg);
    else
        $response = array("status" => "" , 'total' => count($response[1]), 'page' => 0, 'records' => $response[1]);
    return ($response);
}


// renombra les imatges amb el nom de la obra.
// $imgs = explode(",","123,148,149,150,151,152,153");
// $res = "";

// foreach ($imgs as $key => $value)
// {
// 	$nomImg = "imatge" . $value;
// 	$any = "2015";
// 	$mes = "07";
// 	$res = renombraImatge($value, $nomImg, $any, $mes);
// 	if ($res != "") $res1 .= $res . ",";
// }
// if ($res1 != "") echo '</br>Error en imatges: ' . $res1;



?>
