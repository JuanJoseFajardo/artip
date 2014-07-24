<?php

const ARTINPOCKET_CAT    = true;
const ORIGEN_UPLOADS     = './_obres.cat/'; 
const DESTINACIO_UPLOADS = './wp-content/uploads/'; 
const DB_ORIGEN          = 'wpartinpocket.cat';


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

/////////////////////////////////////////////////////////////////////////

importExport($wpdb);

/////////////////////////////////////////////////////////////////////////


function importExport($wpdb)
{
$res1 = "";

$dades = getDadesDBOrigen();

if ($dades->status != "error")
	{
	foreach ($dades->records as $row)
		{
		print_r($row);
		echo "</br></br>";
		$postValors = assignaDadesPost($row);
		print_r($postValors);
		echo "</br></br>";		
		$post_ids = addPost( $postValors );
		var_dump($post_ids);
		addPostMeta( $post_ids, $postValors, assignaDadesPostMeta($row) );

		// insertarCategoriaoTag($wpdb, assignaDadesCategoria($post_ids[0], $row) );
		// insertarCategoriaoTag($wpdb, assignaDadesTag($post_ids[0], $row) );

		$res = renombraImatge( assignaDadesImatges($row, $postValors->attachment[0]->postName) );
		if ($res != "") $res1 .= $res . ",";
		}
	}


if ($res1 != "") echo '</br>Error en imatges: ' . $res1;
}

/////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////

function assignaDadesPost($row)
{

	$descripcio    = $row->work_description;
	$titol         = $row->work_name;
	$postName[0]   = sanitize_title($titol, $row->work_code);
	$postName[1]   = $postName[0];

	$any[0]        = date("Y", strtotime($row->datetime_upload) );
	$mes[0]        = date("m", strtotime($row->datetime_upload) );
	$filename[0]   = $postName[1] . '.jpg';

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

	return ($postValors);
}

function assignaDadesPostMeta($row)
{
	$pes       = '';
	$ample     = '';

	$postMetaValors = (object)array(
		'post_id' 		 => 0,
		'images_gallery' => '',
		'sku'            => ('W' . (($row->work_code) + 100)),
		'preu'           => $row->work_price,
		'stock'          => 1,
		'pes'            => $pes,
		'llarg'          => $row->work_x_size,
		'ample'          => $ample,
		'alt'            => $row->work_y_size	
		);
	return ($postMetaValors);
}

function assignaDadesImatges($row, $postName)
{
	$arryDadesImatges = (object)array(
		'nomImgOrigen' => $row->work_code,
		'nomImgDesti'  => $postName,
		'anyImgOrigen' => date("Y", strtotime($row->datetime_upload) ),
		'mesImgOrigen' => date("m", strtotime($row->datetime_upload) )
		);
	return ($arryDadesImatges);
}


function assignaDadesCategoria($post_id, $row)
{
	$arryDadesCategoria = (object)array(
		'id_origen' => $id_origen,
		'post_id'   => $post_id,		
		'tipusTerm' => 'product_cat',
		'nomTerm'   => 'nom_categoria',
		'descTerm'  => 'descripcio_categoria'
		);
	return ($arryDadesCategoria);
}

function assignaDadesTag($post_id, $row)
{
	$arryDadesTag = (object)array(
		'id_origen' => $id_origen,
		'post_id'   => $post_id,
		'tipusTerm' => 'product_tag',
		'nomTerm'   => 'nom_tag',
		'descTerm'  => 'descripcio_tag'
		);
	return ($arryDadesTag);
}


function getDadesDBOrigen()
{
	$query = sprintf("SELECT CONCAT(CONCAT(ap_users.user_name,' '),ap_users.user_second_name) AS nom_artista, 
							 ap_collections.title AS titol_coleccio,
							 ap_works.*
						FROM ap_works
							INNER JOIN ap_users       ON ap_works.user_code       = ap_users.user_code
    						LEFT JOIN  ap_collections ON ap_works.collection_code = ap_collections.collection_code
						WHERE artist_profile = '1' LIMIT 1;");
	$response = (object)controlErrorQuery( dbExec(DB_ORIGEN, $query) );
	return ($response);
}

function renombraImatge( $dadesImatges )
{
	$res = "";
	$imgOrigen = ORIGEN_UPLOADS . $dadesImatges->nomImgOrigen . ".jpg";
	$imgDesti  = DESTINACIO_UPLOADS . '/' . 
								$dadesImatges->anyImgOrigen . '/' .
								$dadesImatges->mesImgOrigen  . '/' .
								$dadesImatges->nomImgDesti   . ".jpg";
	if (file_exists($imgOrigen))
		{
		$res = rename($imgOrigen, $imgDesti);
		if (!$res) $res = $dadesImatges->nomImgOrigen . '.jpg';
		else $res = "";
		}
	else
		$res = $dadesImatges->nomImgOrigen . '.jpg';
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
	$response = controlErrorQuery( dbExec(DB_NAME, $query,0) );
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
	$postMetaValors->post_id = $post_ids[1];
	// $aa = implode ( ',', $post_ids);
	// $postMetaValors->images_gallery = substr($aa, strpos($aa, ',')+1, 99);
	$postMeta = getArrayPostMeta( $postMetaValors );
	foreach($postMeta as $key=>$val)
		{
		update_post_meta($post_ids[0], $key, $val);
		echo $key . ' : ' . $val . '<br/>';
		}
	$i = 1;
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


function insertarCategoriaTag($wpdb, $dades)
{
	$tt_array = wp_insert_term(
	  $dades->nomTerm, // the term 
	  $dades->tipusTerm, // the taxonomy
	  array(
	    'description'=> $dades->descTerm,
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

	$wpdb->insert( $wpdb->term_relationships, array( 'object_id'        => $dades->post_id, 
													 'term_taxonomy_id' => $tt )
												   ); 
}

function dbExec($db, $query, $tipusResultat=1) {
    /* 
     * Executa : string amb consulta SQL
     * Retorna : string JSON
     */
         
    $estat = (object)array('error' => false, 'msg' => '' , 'numerr' => 0);
    $dades  = array();

    if (!isset($query) ) die('<h1>No es una consulta correcte !</h1>');

    $link = mysql_connect("localhost", "root", "");

    if (!$link) die('Not connected : ' . mysql_error());

    $db_selected = mysql_select_db($db, $link);

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


// $query = $wpdb_other->prepare('SELECT * FROM wp_postmeta WHERE post_id=%d',2199);
// $result = $wpdb_other->get_results($query);
// echo "<ul>";
// foreach ($result as $obj) :
//    echo "<li>".$obj->meta_key. "-" . $obj->meta_value."</li>";
// endforeach;
// echo "</ul>";


?>
