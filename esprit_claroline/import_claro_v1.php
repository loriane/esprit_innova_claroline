 <?php
 
$hote = "localhost";
$username = "root";
$password = "";
$base = "claro"; 
 
$link = mysql_connect($hote,$username,$password); 
if (!$link) { die('Pb de connexion à la BD: ' . mysql_error()); 
} 
else{
	mysql_query("USE ".$base);
	mysql_query("SET NAMES 'utf8'");
}
?>

<!DOCTYPE html>
 <html>
	<head>
		<title>Import Claroline</title>
		<meta charset="utf-8"/>
	</head>
	<body>
	
<?php

	// FORMULAIRE
print "<form action='".$_SERVER['PHP_SELF']."' method='POST'><select name='id'>";

if($dossier = opendir('./datas')){
	while(false !== ($fichier = readdir($dossier))){
		if($fichier != '.' && $fichier != '..' ){
			print '<option value="'.$fichier.'">'.$fichier.'</option>';
		}		
	}	
}
closedir($dossier);
print"</select><input type='submit' value='Import'></form>";
	
// LANCEMENT PROCESS SI SUBMIT FORMULAIRE	
if(isset($_POST["id"])){

	$workspace_id = 21;
	$resource_formation = 0;
	$resource_module = 0;
	$resource_rubrique = 0;
	$resource_activ = 0;
	$resource = 0;
	$parent = 0;
	$lvl = 2;
	$path = "Espace personnel - JohnDoe-21";
    $user = 21;
	
	
	$xml = new DomDocument();
	$xml -> load ('./datas/'.$_POST["id"]);


	$element_uo = $xml->getElementsByTagName('uo');
	foreach($element_uo as $uo){
		if ($uo->hasAttribute("type")) {
			$type = $uo->getAttribute("type");
			$ancien_parent = $parent;
			switch ($type){
				case 'FORMATION' : 
					$lvl = 2;
					GetFormation($uo);
					$formation = $parent;
					$path_formation = $path;
					break;
				case 'MODULE' :
					$lvl = 3;
					$parent = $formation;
					$path = $path_formation;
					GetModule($uo);
					$module = $parent;
					$path_module = $path;
					break;
				case 'RUBRIQUE' : 
					$lvl = 4;
					$parent = $module;
					$path = $path_module;
					GetRubrique($uo);
					$rubrique = $parent;
					$path_rubrique = $path;
					break;
				case 'GROUPE_ACTIONS' : 
					$parent = $rubrique;
					$path = $path_rubrique;
					GetGroupeAction($uo);
					break;
			}	
			$parent = $ancien_parent;
		}
	}
}
	

	
function GetStatut($elem){
	$statut_id = "";
	$statut = $elem->getAttribute("statut");
	if ($statut == "OUVERT")
		$statut_id = 1;
	else 
		$statut_id = 0;

	return $statut_id;  
}
	
	
function GetFormation ($elem){
	global $workspace_id;
	global $resource_formation;
	global $resource;
	global $parent;
	global $lvl;
	global $user;
	global $path;
	
	//WriteWorkspace ($elem); 

	$attr["license_id"] = 3;
	$attr["resource_type"] = 2; //directory
		//user = administrateur John Doe
	$attr["user_id"] = $user;	
	$attr["icon_id"] = 5; //directory
	$attr["workspace_id"] = $workspace_id;
	$attr["created"] = $elem->getAttribute("date");
	$attr["updated"] = $elem->getAttribute("date");
	$attr["name"] = $elem->getAttribute("nom");
	$attr["path"] = $path;	
	$attr["parent_id"] = $parent;
	$attr["lvl"] = $lvl;
	$attr["previous_id"] = 1;
	$attr["next_id"] = 1;
	$attr["discr"] = "directory";
	
	WriteResource($attr);
	$resource_formation = $resource;
	WriteDirectory($resource_formation);
	
	WriteResourceRight ($resource_formation);
	
	$parent = $resource_formation;

}
	
function GetModule ($elem){
	global $workspace_id;
	global $resource_formation;
	global $resource_module;
	global $resource;
	global $parent;
	global $lvl;
	global $user;
	global $path;

	$attr["license_id"] = 3;
	$attr["resource_type"] = 2; //directory

	
	$attr["user_id"] = $user;
	$attr["icon_id"] = 5; //directory
	$attr["workspace_id"] = $workspace_id;
	$attr["created"] = $elem->getAttribute("date");
	$attr["updated"] = $elem->getAttribute("date");
	$attr["path"] = $path;
	$attr["name"] = $elem->getAttribute("nom");
	$attr["parent_id"] = $parent;
	$attr["lvl"] = $lvl;
	$attr["previous_id"] = 1;
	$attr["next_id"] = 1;
	$attr["discr"] = "directory";
	WriteResource($attr);
	$resource_module = $resource; 
	WriteDirectory($resource_module);
	
	$lvl_actuel = $lvl;
	$lvl = $lvl+1;
	$parent = $resource_module; 
	
	$element_action = $elem -> getElementsByTagName('action');
	foreach($element_action as $action){
		GetAction($action);	
		$lvl = $lvl_actuel;
		$path = $attr["path"];
	}

}
	
function GetRubrique ($elem){
	global $workspace_id;
	global $resource_rubrique;
	global $resource;
	global $parent;
	global $lvl;
	global $user;
	global $path;	


	$attr["license_id"] = 3;
	$attr["resource_type"] = 2; //directory

	
	$attr["user_id"] = $user;
	$attr["icon_id"] = 5; //directory
	$attr["workspace_id"] = $workspace_id;
	$attr["created"] = $elem->getAttribute("date");
	$attr["updated"] = $elem->getAttribute("date");
	$attr["path"] = $path;
	$attr["name"] = $elem->getAttribute("nom");
	$attr["parent_id"] = $parent;
	$attr["lvl"] = $lvl;
	$attr["previous_id"] = 1;
	$attr["next_id"] = 1;
	$attr["discr"] = "directory";
	WriteResource($attr);
	$resource_rubrique = $resource; 
	WriteDirectory($resource_rubrique);
	
	$parent = $resource_rubrique;
}
	
function GetGroupeAction ($elem){
	global $workspace_id;
	global $resource_activ;
	global $resource;
	global $parent;
	global $lvl;
	global $user;
	global $path;

	$attr["license_id"] = 3;
	$attr["resource_type"] = 2; //activity

	
	$attr["user_id"] = $user;
	$attr["icon_id"] = 5; //activity
	$attr["workspace_id"] = $workspace_id;
	$attr["created"] = $elem->getAttribute("date");
	$attr["updated"] = $elem->getAttribute("date");
	$attr["path"] = $path;
	$attr["name"] = $elem->getAttribute("nom");
	$attr["parent_id"] = $parent;
	$attr["lvl"] = $lvl;
	$attr["previous_id"] = 1;
	$attr["next_id"] = 1;
	$attr["discr"] = "directory";
	WriteResource($attr);
	$resource_activ = $resource; 
	
	$attr["id"] = $resource_activ;
	$attr["instruction"] = $elem->getAttribute("description");
	WriteActivity($attr);
	

	$lvl = $lvl+1;
	$lvl_actuel = $lvl;
	$ancien_parent = $parent;
	$parent = $resource_activ;
	
	$element_action = $elem -> getElementsByTagName('action');
	foreach($element_action as $action){
		GetAction($action);	
		$lvl = $lvl_actuel;
		$path = $attr["path"];		
	}
	
	$parent = $ancien_parent;
	
	//Fin du parcours du groupe d'action
	$resource_activ = 0;
	
}


function GetAction ($elem){
	global $workspace_id;
	global $resource_rubrique;
	global $resource;
	global $parent;
	global $lvl;
	global $user;	
	global $path;


	$attr["license_id"] = 3;
	$attr["resource_type"] = 1; //file

	
	$attr["user_id"] = $user;

	$attr["workspace_id"] = $workspace_id;
	$attr["created"] = $elem->getAttribute("date");
	$attr["updated"] = $elem->getAttribute("date");
	$attr["path"] = $path;
	$attr["name"] = $elem->getAttribute("nom");
	$attr["parent_id"] = $parent;
	$attr["lvl"] = $lvl;
	$attr["previous_id"] = 1;
	$attr["next_id"] = 1;
	$attr["discr"] = "";
	$type = $elem->getAttribute("type");
	if ($type == "LIEN_FORUM"){
			$attr["discr"] = "Claroline\\\\ForumBundle\\\\Entity\\\\Forum";
			$attr["icon_id"] = 33;
			$attr["resource_type"] = 7; 
	}
	elseif ($type == "LIEN_SITE_INTERNET"){
			$attr["discr"] = "link";
			$attr["icon_id"] = 13;
			$attr["resource_type"] = 9; 			
	}
	else {
			$attr["discr"] = "file";
			$attr["icon_id"] = 9;
			$attr["resource_type"] = 1; 
	}		

			
			
			
	$query = "SELECT * FROM `claro_resource` WHERE `name` = '".$attr["name"]."'";
	$query .= " AND `creation_date` = '".$attr["created"]."'";
	$query .= " AND `workspace_id` = ".$workspace_id;
	$result = mysql_query($query);
	if ($result !=false){
		$compteur = mysql_num_rows($result);
		if ($compteur ==0){	
			WriteResource($attr);
		}
		else {
			while ($row = mysql_fetch_array($result)) {
				$id = $row["id"];
			}
			$resource = $id;
			$sql = "UPDATE `claro_resource` SET `lvl`=".$lvl.",`parent_id` =".$parent;
			$sql .= " WHERE `id` =".$id ;
			mysql_query($sql);
		}
	}

	
	$lvl = $lvl+1;
	
	$attr["datas"] = $elem->getAttribute("datas");
	$attr["ordre"] = $elem->getAttribute("ordre");
	$attr["resource"] = $resource;
	//si forum pas WriteFile mais WriteForum
	if ($type == "LIEN_FORUM")
		WriteForum($resource);
	else 
		WriteFile($attr);
	
}
	
function WriteWorkspace($elem){
	global $workspace_id;

	$attr["discr"] = "Claroline\\CoreBundle\\Entity\\Workspace\\SimpleWorkspace";
	$attr["name"] = $elem->getAttribute("nom");
	$attr["is_public"] = GetStatut($elem);
	$attr["lft"] = 1;
	$attr["rgt"] = 2;
	$attr["lvl"] = 0;
	$attr["root"] = $elem->getAttribute("id"); 
	$attr["parent_id"] = 0; 
	$attr["code"] = "F".$elem->getAttribute("id");
	
	$sql = "INSERT INTO `claro_workspace` (`discr`,`name`,`is_public`,`lft`,`rgt`,`lvl`,`root`,`parent_id`,`code`) VALUES ('";
	$sql .= addslashes($attr["discr"])."' , '".addslashes($attr["name"])."' ,".$attr["is_public"].",".$attr["lft"].",".$attr["rgt"].",".$attr["lvl"].",".$attr["root"].",";
	$sql .= $attr["parent_id"].", '".$attr["code"]."' )";
	mysql_query($sql);
	$workspace_id = mysql_insert_id(); 

}

function WriteResource($attr){
	global $resource;
	global $path;
	
	set_time_limit(0);
	if ($attr["parent_id"]==0){
	$sql = "INSERT INTO `claro_resource`(`license_id`, `resource_type_id`, `user_id`, `icon_id`, `workspace_id`, `creation_date`, `modification_date`, `path`, `name`, `lvl`, `previous_id`, `next_id`, `discr`) VALUES  (";
	$sql .= $attr["license_id"]." , ".$attr["resource_type"]." ,".$attr["user_id"].",".$attr["icon_id"].",".$attr["workspace_id"].",'".$attr["created"]."','".$attr["updated"]."','";
	$sql .= $attr["path"]."', '".addslashes($attr["name"])."',".$attr["lvl"].",";
	$sql .= $attr["previous_id"].",".$attr["next_id"].",'".$attr["discr"]."' )";
	}
	else
	{
	$sql = "INSERT INTO `claro_resource`(`license_id`, `resource_type_id`, `user_id`, `icon_id`, `workspace_id`, `creation_date`, `modification_date`, `path`, `name`, `parent_id`, `lvl`, `previous_id`, `next_id`, `discr`) VALUES  (";
	$sql .= $attr["license_id"]." , ".$attr["resource_type"]." ,".$attr["user_id"].",".$attr["icon_id"].",".$attr["workspace_id"].",'".$attr["created"]."','".$attr["updated"]."','";
	$sql .= addslashes($attr["path"])."', '".addslashes($attr["name"])."',".$attr["parent_id"].",".$attr["lvl"].",";
	$sql .= $attr["previous_id"].",".$attr["next_id"].",'".$attr["discr"]."' )";
	}
	mysql_query($sql);
	$resource = mysql_insert_id(); 

	
	if ( $attr["discr"] == "directory" || $attr["discr"] == "activity" ){ 
		$path = $attr["path"]."`".addslashes($attr["name"])."-".$resource."`";
		$sql = "UPDATE `claro_resource` SET `path`='".$path."' WHERE id = ".$resource;
		mysql_query($sql);
	}	
}


function WriteDirectory($id){

	$sql = "INSERT INTO `claro_directory`(`id`) VALUES  (".$id.")";

	mysql_query($sql);


}

function WriteActivity($attr){

	$sql = "INSERT INTO `claro_activity`(`id`, `instruction`, `start_date`, `end_date`) VALUES  (".$attr["id"].",'".addslashes($attr["instruction"])."','";
	$sql .= $attr["created"]."','".$attr["updated"]."')";

	mysql_query($sql);


}


function WriteFile($attr){ 
	global $resource_activ;
	
	$chemin_esprit = $attr["datas"];
	$chemin_claro = "";

	/*Déplacement du fichier d'esprit vers Claroline
	
	rename ($chemin_esprit, $chemin_claro);
	
	$size = filesize($chemin_claro);
	//Conversion en Ko
	$size = round($size / 1024 * 100) / 100  ; 
	*/ 
	
	
	/*Récupération du fichier sur le serveur

	$biblio_magic = "c:/wamp/apache/conf/magic";
	$finfo = finfo_open(FILEINFO_MIME, $biblio_magic); 

	$mime_type = finfo_file($finfo, $chemin_claro);

	finfo_close($finfo);*/

	
	$mime_type = "application/pdf";
	$size = 10 ;
	
	$resource_action = $attr["resource"];
	
	$data = explode(".", $chemin_esprit);
	$data_sans_extension = $data[0];
	$data_extension = "";

	if ( count($data)>1)
		$data_extension = $data[1];
	else 
		$data_sans_extension = $attr["path"];

	//Connaitre le mode de hachage 
	$sql_file = "INSERT INTO `claro_file`(`id`, `size`, `hash_name`, `mime_type`) VALUES (".$resource_action.",".$size.",'".hash('md5',$data_sans_extension).".".$data_extension."','".$mime_type."')";
		
	mysql_query($sql_file);
	$resource_action_id = mysql_insert_id();

	
	
	if ($resource_activ != 0){
		$ordre = $attr["ordre"];
		$sql_resource_activity = "INSERT INTO `claro_resource_activity`(`resource_id`, `activity_id`, `sequence_order`) VALUES (".$resource_action_id.",".$resource_activ.",".$ordre.")";
		mysql_query($sql_resource_activity);

	}

}

function WriteForum($id){
	$sql = "INSERT INTO `claro_forum`(`id`) VALUES (".$id.")";
	mysql_query($sql);
}

function WriteResourceRight ($id_resource) {
	global $workspace_id;
	$sql = "INSERT INTO `claro_resource_rights`(`resource_id`, `role_id`, `workspace_id`, `can_delete`, `can_open`, `can_edit`, `can_copy`, `can_export`) VALUES";
	$sql .= "(".$id_resource.",4,".$workspace_id.",1,1,1,1,1)";
	mysql_query($sql);

}
	
?>	
	