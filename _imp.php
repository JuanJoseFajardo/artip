<?php

const ORIGEN_UPLOADS       = './_obres.cat/'; 
const ORIGEN_AUTOR_UPLOADS = './_autors.cat/'; 
const DESTINACIO_UPLOADS   = './wp-content/uploads/'; 
const DESTINACIO_IMG_AUTOR = '/wp-content/uploads/'; 
const DB_ORIGEN            = 'wpartinpocket.cat';
const obres_tradicionals   = "-ANALOGIC";
const obres_digitals       = "-DIGITAL";
const DOMINI 			   = 'http://www.artinpocket.cat';

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

assignaCatDigital($wpdb);

$dades = getDadesDBOrigen();

if ($dades->status != "error")
	{
	foreach ($dades->records as $row)
		{
		$userId = importaUsuari($row);
		// print_r($row);
		// echo "</br></br>";
		$postValors = assignaDadesPost($row);
		// print_r($postValors);
		// echo "</br></br>";
		// afegeix un post amb el producte + attachment de la imatge
		$post_ids = addPost( $postValors, $userId );
		// var_dump($post_ids);
		// afegeix un postMeta amb el producte + attachment de la imatge
		addPostMeta( $post_ids, $postValors, assignaDadesPostMeta($row), $userId );
		// inserta categoria TRADICIONALES
		insertarCategoria($wpdb, assignaDadesCategoria($post_ids[0], $row, obres_tradicionals) );
		// inserta categoria colecció
		$post_idCol = 0;
		if ($row->titol_coleccio != NULL)
			{
			// inserta categoria = nom autor + coleccio
			$idCategoria = insertarCategoria($wpdb, assignaDadesCategoria($post_ids[0], $row, "") );
			// si ja la categoria de la colecció s'acaba de crear, inserta els registres de post, postmeta i relaciona
			$resp = getRelacioImatgeColeccio($idCategoria);
			if ( $resp->status == '' and $resp->records == array())
				{
				// crea registres amb imatge de la colecció si es diferent a la imatge de la obra
				// obté les dades de la colecció per inserrir el post
				$postValorsColeccio = assignaDadesPostColeccio($row);
				// crea l'estructura de dades per inserrir el post
				$post          	    = getArrayPost( $postValorsColeccio, $userId );
				// insereix el post
		    	$post_idCol  		= wp_insert_post($post);
				// insereix el link de la imatge de la colecció en el wp_metapost			
				update_post_meta($post_idCol, '_wp_attached_file', $postValorsColeccio->filename);
				// relaciona la imatge de la colecció amb la categoria de la colecció
				insertarRelacioImatgeColeccio($wpdb, $post_idCol, $idCategoria);					
				// copia imatge coleccio origen amb nom desti = postName
				renombraCopiaImatge( assignaDadesImatge($row, $row->img_coleccio, $postValorsColeccio->postName ), false );
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
	$titol         = $row->work_name . '. ' . $nomArtista . ', ' . $row->work_year;
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
			'descTerm'  => ''
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
	if ($row->artist_web != NULL and $row->artist_web != "")
		{
		// si troba més d'un web separat per espai
		if (strpos($row->artist_web, ' ') > 0)
			$linkAutor = substr($row->artist_web, 0, strpos($row->artist_web, ' '));
		else
			$linkAutor = $row->artist_web;
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
							 ap_users.user_id,
							 ap_users.user_pw,
							 ap_users.user_mail,
							 ap_users.user_name,
							 ap_users.user_second_name,
							 ap_users.user_nif,
							 ap_users.user_adress,
							 ap_users.user_cp,							 
							 ap_users.last_modified_datetime,
							 ap_users.artist_web,
							 ap_users.artist_about,
							 ap_users.artist_cv,
							 ap_users.phone,
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

function addPost( $postValors, $userId )
{
	$post_ids = array();
	$postValors->postType = 'product';
    $post = getArrayPost( $postValors, $userId);
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
			    $post        = getArrayPost( $postValors, $userId );
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

function getArrayPost( $postValors, $userId )
{
	$dateTime = date("Y-n-j H:i:s");
	$post = array(
		'post_author'           => $userId,
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


function addPostMeta($post_ids, $postValors, $postMetaValors, $userId)
{
	$postMetaValors->post_id = $post_ids[1];
	// $aa = implode ( ',', $post_ids);
	// $postMetaValors->images_gallery = substr($aa, strpos($aa, ',')+1, 99);
	$postMeta = getArrayPostMeta( $postMetaValors, $userId );
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

function getArrayPostMeta( $postMetaValors, $userId )
{
	$postMeta = array(
		'total_sales'            => $postMetaValors->vendesTotals,
		'style'					 => $postMetaValors->estilObra,		
		'_edit_lock'             => (time().':'.$userId),
		'_edit_last'             => $userId,
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
	if ($tt_array->error_data)
		{
		$tt = $tt_array->error_data;
		$tt = $tt['term_exists'];
		}
	else
		$tt = $tt_array['term_id'];
	// crea la relació entre la categoria i el producte
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
		$imgAutor      = DESTINACIO_IMG_AUTOR . $imgAutor;
		$descImgAutor = sprintf("<div class='divAutor'><img class='imgAutor' src='%s' alt='%s' width='150' height='150'/>",$imgAutor, $nomArtista);
		$dades->descTerm = $descImgAutor . $dades->descTerm . "</div>";
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
			insertarCategoria($wpdb, assignaDadesCategoria($row->ID, "", obres_digitals) );			
			}
		}
	return ("final");
}

function importaUsuari($row)
{
	$aa    = array();
	$aa['artist'] = 1;
	if ($row->artist_web != NULL and $row->artist_web != "")
		$link = substr($row->artist_web, 0, strpos($row->artist_web, ' '));
	$userdata = array(
	    'user_login'          =>  $row->user_id,
	    'user_pass'           =>  wp_hash_password( $row->user_pw ),
		'user_nicename'       =>  $row->user_name,
		'user_email'          =>  $row->user_mail,
		'user_url'            =>  $link,
		'user_registered'     =>  $row->last_modified_datetime,
		'user_activation_key' =>  '',
		'user_status'         =>  0,
		'display_name'        =>  $row->user_name . " " . $row->user_second_name
	);

	$user_id = wp_insert_user( $userdata ) ;
	//On success
	if( !is_wp_error($user_id) )
		{
		if ( $row->artist_about != "" or $row->artist_cv != "")
			$descripcio = $row->artist_about .' . '. $row->artist_cv;
		else
			$descripcio = "";
		echo "</br>User created : ". $user_id;
		update_user_meta( $user_id, 'first_name', $row->user_name);
		update_user_meta( $user_id, 'last_name', $row->user_second_name);
		update_user_meta( $user_id, 'nick_name', $row->user_id);
		update_user_meta( $user_id, 'description', $descripcio);
		update_user_meta( $user_id, 'rich_editing', true);
		update_user_meta( $user_id, 'comment_shortcuts', false);
		update_user_meta( $user_id, 'admin_color', fresh);
		update_user_meta( $user_id, 'use_ssl', 0);
		update_user_meta( $user_id, 'show_admin_bar_front', true);
		update_user_meta( $user_id, 'wp_capabilities', $aa);
		update_user_meta( $user_id, 'wp_user_level', 10);
		update_user_meta( $user_id, 'dismissed_wp_pointers', 'wp350_media,wp360_revisions,wp360_locks,wp390_widgets');
		update_user_meta( $user_id, 'billing_phone', $row->phone);
		}
	else
		{
		$tt_array = get_user_by( 'login', $row->user_id );
		$user_id = $tt_array->ID;
		}
	return ($user_id);
}

?>
