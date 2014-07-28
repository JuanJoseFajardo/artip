<?php

const ARTINPOCKET_CAT      = true;
const ORIGEN_UPLOADS       = './_obres.cat/'; 
const ORIGEN_AUTOR_UPLOADS = './_autors.cat/'; 
const DESTINACIO_UPLOADS   = './wp-content/uploads/'; 
const DESTINACIO_IMG_AUTOR = '/wp-content/uploads/'; 
const DB_ORIGEN            = 'wpartinpocket.cat';

if (ARTINPOCKET_CAT)
	define('DOMINI' , 'http://www.artinpocket.cat');
else
	define('DOMINI' , 'http://www.inpocketart.com');	

// define('IMG_AUTOR'  , 'http://localhost/artinpocket' . '/' . DESTINACIO_UPLOADS);
define('IMG_AUTOR'  , DOMINI . DESTINACIO_IMG_AUTOR);

require_once( $_SERVER['DOCUMENT_ROOT'] . '/artinpocket/wp-config.php' );
require_once( $_SERVER['DOCUMENT_ROOT'] . '/artinpocket/wp-includes/wp-db.php' );
$wpdb_other = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);

// funcions de l'importació de dades de artinpocket.cat to inpocketart.com

set_time_limit(0);
ini_set('memory_limit', '2048M');

function importExport($wpdb)
{
$res1 = $res2 = "";
$comptador = 0;

$dades = getDadesDBOrigen();

if ($dades->status != "error")
	{
	foreach ($dades->records as $row)
		{
		// print_r($row);
		// echo "</br></br>";
		$postValors = assignaDadesPost($row);
		// print_r($postValors);
		// echo "</br></br>";
		// afegeix un post amb el producte + attachment de la imatge
		$post_ids = addPost( $postValors );
		// var_dump($post_ids);
		// afegeix un postMeta amb el producte + attachment de la imatge
		addPostMeta( $post_ids, $postValors, assignaDadesPostMeta($row) );
		// inserta categoria TRADICIONALES
		insertarCategoria($wpdb, assignaDadesCategoria($post_ids[0], $row, "TRADICIONALES") );
		// inserta categoria colecció
		$post_idCol = 0;
		if ($row->titol_coleccio != NULL)
			{
			// inserta categoria = nom autor + coleccio
			$idCategoria        = insertarCategoria($wpdb, assignaDadesCategoria($post_ids[0], $row, "") );
			// si ja la categoria de la colecció s'acaba de crear, inserta els registres de post, postmeta i relaciona
			$resp = getRelacioImatgeColeccio($idCategoria);
			if ( $resp->status == '' and $resp->records == array())
				{
				// crea registres amb imatge de la colecció si es diferent a la imatge de la obra
				// if ($row->img_coleccio > 0  and $row->img_coleccio != $row->work_code)
					// {
					// obté les dades de la colecció per inserrir el post
					$postValorsColeccio = assignaDadesPostColeccio($row);
					// crea l'estructura de dades per inserrir el post
					$post          	    = getArrayPost( $postValorsColeccio );
					// insereix el post
			    	$post_idCol  		= wp_insert_post($post);
					// insereix el link de la imatge de la colecció en el wp_metapost			
					update_post_meta($post_idCol, '_wp_attached_file', $postValorsColeccio->filename);
					// relaciona la imatge de la colecció amb la categoria de la colecció
					insertarRelacioImatgeColeccio($wpdb, $post_idCol, $idCategoria);					
					// copia imatge coleccio origen amb nom desti = postName
					renombraCopiaImatge( assignaDadesImatge($row, $row->img_coleccio, $postValorsColeccio->postName ), false );
					// }
				// else
					// {
					// relaciona la imatge de la colecció amb la categoria de la colecció
					// en el cas que la imatge de la colecció sigui la mateixa que la de la obra
					// insertarRelacioImatgeColeccio($wpdb, $post_ids[1], $idCategoria);											
					// }
				}
			}
		// inserta Tag Autor
		$imgAutordesti = insertarTag($wpdb, assignaDadesTag($post_ids[0], $row) );
		// inserta codi transport
		insertarCategoria($wpdb, assignaDadesTransport($post_ids[0]) );
		// renombra i mou imatge obra origen amb nom desti = postName
		$res = renombraCopiaImatge( assignaDadesImatge($row, $row->work_code, $postValors->attachment[0]->postName ) );
		if ($res != "") $res1 .= $res . ",";		
		// renombra i mou imatge autor amb nom desti = slug
		if ($imgAutordesti != "") 
			{
			$res = renombraImatgeAutor( assignaDadesImatge($row, $row->user_code, $imgAutordesti) );					
			if ($res != "") $res2 .= $res . ",";
			}
		echo '<br/>' . ++$comptador     . ' --> ' . 
					$row->work_code     . ' --> ' .
					$post_ids[0]        . ' --> ' .
					$row->img_coleccio  . ' --> ' .
					$post_idCol ;
		}
	}

if ($res1 != "") $res1 =  '</br>Error en imatges obres: '  . $res1;
if ($res2 != "") $res2 =  '</br>Error en imatges autors: ' . $res2;

$res1 .= ('</br></br>' . $res2);
$res1 .= "FINALLLLLLLLLLLLLLL";

return ($res1);
}

/////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////

function assignaDadesPost($row)
{
	if ($row->nom_artista == NULL)
		$nomArtista = $row->artist_name;
	else
		$nomArtista = $row->nom_artista;

	$descripcio    = $row->work_description;
	$titol         = $row->work_name . '.' . $nomArtista . ',' . $row->work_year;
	$postName[0]   = sanitize_title($titol, $row->work_code) . '-' . $row->work_code;
	$postName[1]   = $postName[0];

	$any[0]        = date("Y", strtotime($row->datetime_upload) );
	$mes[0]        = date("m", strtotime($row->datetime_upload) );
	$filename[0]   = $postName[1] . '.jpg';

	$postValors = (object)array(
		'postType' 	  => '',
		'postParent'  => 0,
		'descripcio'  => $descripcio,
		'titol'       => $titol,
		'excerpt'	  => '',
		'postName'    => $postName[0],
		'mimeType'	  => '',
		'guid'		  => '',
		'attachment'  => array(),
		'postStatus' => 'publish',
		'pingStatus' => 'closed'
		);

	$postValors->attachment[0] = (object)array(
		'postName'    => $postName[1],
		'any'		  => $any[0],
		'mes'		  => $mes[0],
		'filename'    => $filename[0],
		'postStatus' => 'inherit',
		'pingStatus' => 'open'
		);

	return ($postValors);
}

function assignaDadesPostColeccio($row)
{
	$postName   = sanitize_title($row->titol_coleccio, $row->img_coleccio);
	$postName  .= '-' . $row->collection_code . '-' . $row->img_coleccio;	
	$any        = date("Y", strtotime($row->datetime_upload) );
	$mes        = date("m", strtotime($row->datetime_upload) );
	$filename   = $postName . '.jpg';
	$attachment = DOMINI .'/wp-content/uploads/' . $any .  '/' . $mes .  '/' . $filename;
	$postValorsColeccio = (object)array(
		'postType' 	  => 'attachment',
		'postParent'  => 0,
		'descripcio'  => $row->descripcio_coleccio,
		'titol'       => $row->titol_coleccio,
		'excerpt'	  => $row->titol_coleccio,
		'postName'    => $postName,
		'mimeType'	  => 'image/jpeg',
		'guid'		  => $attachment,
		'attachment'  => array(),
		'postStatus'  => 'inherit',
		'pingStatus'  => 'open',
		'filename'    => $any .  '/' . $mes .  '/' . $filename
		);
	return ($postValorsColeccio);
}

function assignaDadesPostMeta($row)
{
	$postMetaValors = (object)array(
		'post_id' 		 => 0,
		'images_gallery' => '',
		'sku'            => ('W' . (($row->work_code) + 100)),
		'preu'           => $row->work_price,
		'stock'          => 1,
		'pes'            => '',
		'llarg'          => $row->work_x_size,
		'ample'          => '',
		'alt'            => $row->work_y_size,
		'estilObra'		 => $row->work_style,
		'vendesTotals'   => $row->success_on_sale_process,
		'stockStatus'    => $row->success_on_sale_process ? "outofstock" : "instock"
		);
	return ($postMetaValors);
}

function assignaDadesImatge($row, $nomImgOrigen, $nomImgDesti)
{
	$arryDadesImatges = (object)array(
 		'nomImgOrigen' => $nomImgOrigen,
		'nomImgDesti'  => $nomImgDesti,
		'anyImgOrigen' => date("Y", strtotime($row->datetime_upload) ),
		'mesImgOrigen' => date("m", strtotime($row->datetime_upload) )
		);
	return ($arryDadesImatges);
}


function assignaDadesCategoria($post_id, $row, $tipusCategoria)
{
	if ($tipusCategoria == "")
		{
		if ($row->nom_artista == NULL)
			$nomArtista = $row->artist_name;
		else
			$nomArtista = $row->nom_artista;

		$arryDadesCategoria = (object)array(
			'post_id'   => $post_id,
			'tipusTerm' => 'product_cat',
			'nomTerm'   => $nomArtista . '. ' . $row->titol_coleccio,
			'descTerm'  => $row->descripcio_coleccio
			);
		}
	else
		$arryDadesCategoria = (object)array(
			'post_id'   => $post_id,
			'tipusTerm' => 'product_cat',
			'nomTerm'   => $tipusCategoria,
			'descTerm'  => 'Obras de arte ' . $tipusCategoria
			);
	return ($arryDadesCategoria);
}

function assignaDadesTag($post_id, $row)
{
	if ($row->nom_artista == NULL)
		$nomArtista = $row->artist_name;
	else
		$nomArtista = $row->nom_artista;

	$desc_tag  = "";
	$imgAutor = ORIGEN_AUTOR_UPLOADS . $row->user_code . ".jpg";
	if ($row->user_city != NULL)
		$desc_tag .= sprintf("<em>%s </em>",$row->user_city);	
	if ($row->pais != NULL)
		$desc_tag .= sprintf("<em>(%s) </em>",$row->pais);	
	if ($row->any_naixement != NULL)
		$desc_tag .= sprintf("<em>, %d</em>",$row->any_naixement);
	if ($row->user_city != NULL or $row->pais != NULL or $row->any_naixement != NULL)
		$desc_tag .= "</br>";
	if ($row->artist_web != NULL)
		{
		$linkAutor = substr($row->artist_web, 0, strpos($row->artist_web, ' '));
		$desc_tag .= sprintf("Web artista: <a href='%s'>%s</a></br>",$linkAutor, $linkAutor);
		}
	if ($row->artist_about != NULL)
		$desc_tag .= sprintf("%s</br>",$row->artist_about);
	if ($row->artist_cv != NULL)
		$desc_tag .= sprintf("%s</br>",$row->artist_cv);	

	$arryDadesTag = (object)array(
		'post_id'      => $post_id,
		'tipusTerm'    => 'product_tag',
		'nomTerm'      => $nomArtista,
		'descTerm'     => $desc_tag,
		'imgAutor'     => $imgAutor,
		'anyImgOrigen' => date("Y", strtotime($row->datetime_upload) ),
		'mesImgOrigen' => date("m", strtotime($row->datetime_upload) )
		);
	return ($arryDadesTag);
}

function assignaDadesTransport($post_id)
{
	$arryDadesTransport = (object)array(
		'post_id'      => $post_id,
		'tipusTerm'    => 'product_shipping_class',
		'nomTerm'      => 'Transport',
		'descTerm'     => 'transport',
		);
	return ($arryDadesTransport);
}


function getDadesDBOrigen()
{
	$query = sprintf("SELECT CONCAT(CONCAT(ap_users.user_name,' '),ap_users.user_second_name) AS nom_artista, 
		 					 ap_users.artist_name,
							 ap_collections.title       AS titol_coleccio,
							 ap_collections.description AS descripcio_coleccio,
							 ap_users.user_code,
							 ap_users.artist_web,
							 ap_users.artist_about,
							 ap_users.artist_cv,
							 country_t.short_name       AS pais,
							 ap_users.user_city,
							 ap_users.year              AS any_naixement,
							 ap_collections.cover_work  AS img_coleccio,
							 ap_works.*
						FROM ap_works
							INNER JOIN ap_users       ON ap_works.user_code       = ap_users.user_code
    						LEFT JOIN  ap_collections ON ap_works.collection_code = ap_collections.collection_code
    						LEFT JOIN country_t on (ap_users.user_country = country_t.country_id)
						WHERE artist_profile = '1'
						-- and (cover_work = 222 or cover_work = 228 or cover_work = 232)
						-- and (cover_work = 155 or cover_work = 160 or cover_work = 217)						
						-- and ap_works.user_code = 418
						ORDER BY cover_work,work_code
						;");
	$response = (object)controlErrorQuery( dbExec(DB_ORIGEN, $query) );
	return ($response);
}

function renombraCopiaImatge( $dadesImatges, $renombra=true )
{
	$res = "";
	$imgOrigen = ORIGEN_UPLOADS . $dadesImatges->nomImgOrigen . ".jpg";
	$imgDesti  = DESTINACIO_UPLOADS . '/' . 
								$dadesImatges->anyImgOrigen . '/' .
								$dadesImatges->mesImgOrigen  . '/' .
								$dadesImatges->nomImgDesti   . ".jpg";
	if (file_exists($imgOrigen))
		{
		if ($renombra) $res = rename($imgOrigen, $imgDesti);
		else  $res = copy($imgOrigen, $imgDesti);
		if (!$res) $res = $dadesImatges->nomImgOrigen . '.jpg';
		else $res = "";
		}
	else
		$res = $dadesImatges->nomImgOrigen . '.jpg';
	return ($res);
}

function renombraImatgeAutor( $dadesImatges )
{
	$res = "";
	$imgOrigen = ORIGEN_AUTOR_UPLOADS . $dadesImatges->nomImgOrigen . ".jpg";
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
	    	// print_r($post_ids[0]);
			$myvals = get_post($post_ids[0]);
			// echo "<pre>";
			// foreach($myvals as $key=>$val) echo $key . ' : ' . $val . '<br/>';    	
	    	// echo '<br/><br/><br/>';

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
				$postValors->postStatus = $attData->postStatus;
				$postValors->pingStatus = $attData->pingStatus;				
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
        'post_status'           => $postValors->postStatus, 
        'comment_status'        => 'open', 
        'ping_status'           => $postValors->pingStatus,
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
		// $query = sprintf("INSERT INTO wp_postmeta VALUES (NULL,'%d','%s','%s')",$post_ids[0], $key, $val);
		// $response = (object)controlErrorQuery( dbExec(DB_NAME, $query,2) );
		// var_dump($response);
		update_post_meta($post_ids[0], $key, $val);
		// echo $key . ' : ' . $val . '<br/>';
		}
	$i = 1;
	foreach ($postValors->attachment as $key => $attData)
		{
		$attachment = $attData->any .  '/' . $attData->mes .  '/' . $attData->filename;
		// $query = sprintf("INSERT INTO wp_postmeta VALUES (NULL,'%d','%s','%s')",$post_ids[$i++],'_wp_attached_file', $attachment);
		// $response = (object)controlErrorQuery( dbExec(DB_NAME, $query,2) );		
		// var_dump($response);		
		update_post_meta($post_ids[$i++], '_wp_attached_file', $attachment);
		}
}

function getArrayPostMeta( $postMetaValors )
{
	$postMeta = array(
		'total_sales'            => $postMetaValors->vendesTotals,
		'style'					 => $postMetaValors->estilObra,		
		'_edit_lock'             => (time().':1'),
		'_edit_last'             => '1',
		'_thumbnail_id'          => $postMetaValors->post_id,
		'_visibility'            => 'visible',
		'_stock_status'          => $postMetaValors->stockStatus,
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


function insertarCategoria($wpdb, $dades)
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
	// crea la relació entre la categoria i el producte
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
	return($tt);
}

// inserta la relació entre la categoria de la colecció el post a on está especificada la imatge de la colecció.
function insertarRelacioImatgeColeccio($wpdb, $post_idCol, $idCategoria)
{
	$wpdb->insert( $wpdb->wp_prefix .'wp_woocommerce_termmeta', array( 'woocommerce_term_id' => $idCategoria,
													 'meta_key' => 'order', 
							 						 'meta_value' => 0)
												   ); 
	$wpdb->insert( $wpdb->wp_prefix .'wp_woocommerce_termmeta', array( 'woocommerce_term_id' => $idCategoria,
													 'meta_key' => 'display_type',
							 						 'meta_value' => '')
												   );
	$wpdb->insert( $wpdb->wp_prefix .'wp_woocommerce_termmeta', array( 'woocommerce_term_id' => $idCategoria,
													 'meta_key' => 'thumbnail_id', 
							 						 'meta_value' => $post_idCol)
												   );												   	
}

// obté la relació de la colecció amb la imatge de la colecció
function getRelacioImatgeColeccio($idCategoria)
{
	$query = sprintf("SELECT * 
						FROM wp_woocommerce_termmeta
						WHERE woocommerce_term_id = '%d' AND
							  meta_key            = 'thumbnail_id';",$idCategoria);
	$response = (object)controlErrorQuery( dbExec(DB_NAME, $query) );	
	return($response);
}

// retorna el nom de la imatge desti generat al WP com un slug
function insertarTag($wpdb, $dades)
{
	$nomArtista = $dades->nomTerm;
	// inserta el tag sense descipció ja que necessitem el slug que genera per formar el nom de la imatge del autor
	$tt_array = wp_insert_term(
	  $dades->nomTerm, // the term 
	  $dades->tipusTerm, // the taxonomy
	  array(
	    'description'=> $dades->descTerm,
	    'slug' => '',
	    'parent'=> 0
	  )
	);
	// crea la relació entre el tag i el producte
	if ($tt_array->error_data)
		{
		$tt = $tt_array->error_data;
		$tt = $tt['term_exists'];
		}
	else
		{
		$tt = $tt_array['term_id'];
		$aa = array();
		$aa[] = $tt;
		wp_update_term_count_now( $aa , $dades->tipusTerm );		
		}

	$wpdb->insert( $wpdb->term_relationships, array( 'object_id'        => $dades->post_id, 
													 'term_taxonomy_id' => $tt )); 
	$imgAutordesti = "";
	// en el cas que l'autor tingui una imatge la afegeix al tag
	if (file_exists($dades->imgAutor) )
	{
		$term = get_term(
		  $tt, // the term 
		  $dades->tipusTerm // the taxonomy
		);

		// var_dump($term->slug );
		$imgAutor = $dades->anyImgOrigen . '/' . $dades->mesImgOrigen . '/' . $term->slug          . ".jpg";
		$imgAutordesti = DESTINACIO_UPLOADS . $imgAutor;
		$imgAutor      = IMG_AUTOR . $imgAutor;
		$descImgAutor = sprintf("<img src='%s' alt='%s' width='150' height='150' style='margin-right:10px;margin-bottom:10px;'/>",$imgAutor, $nomArtista);
		$dades->descTerm = $descImgAutor . $dades->descTerm;
		$imgAutordesti = $term->slug;
		// afegeix la descripció del tag
		wp_update_term($tt,  $dades->tipusTerm, array('description'=> $dades->descTerm));
	}

	return($imgAutordesti);
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


function assignaCatDigital($wpdb)
{
	$query = sprintf("SELECT * 
						FROM wp_posts
						WHERE post_type = 'product';");
	$response = (object)controlErrorQuery( dbExec(DB_NAME, $query) );	
	if ($response->status != "error")
		{
		foreach ($response->records as $row)
			{
			echo ++$i . " - " .$row->ID . "</br>";
			// inserta categoria DIGITALES
			insertarCategoria($wpdb, assignaDadesCategoria($row->ID, "", "DIGITALES") );			
			}
		}
	return ("final");
}



///////////////////////////////////////////////////////////////////////////////////////////////////


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
