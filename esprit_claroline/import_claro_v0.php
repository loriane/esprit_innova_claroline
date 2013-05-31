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

	$workspace_id = 0;
	$resource_formation = 0;
	$resource_module = 0;
	$resource_rubrique = 0;
	$resource_activ = 0;
	$resource = 0;
	
	$xml = new DomDocument();
	$xml -> load ('./datas/'.$_POST["id"]);


	$element_uo = $xml->getElementsByTagName('uo');
	foreach($element_uo as $uo){
		if ($uo->hasAttribute("type")) {
			$type = $uo->getAttribute("type");
			switch ($type){
				case 'FORMATION' : 
					GetFormation($uo);
					break;
				case 'MODULE' :
					GetModule($uo);
					break;
				case 'RUBRIQUE' : 
					GetRubrique($uo);
					break;
				case 'GROUPE_ACTIONS' : 
					GetGroupeAction($uo);
					break;
			}				
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
	
	//WriteWorkspace ($elem); 

	$workspace_id = 21;
	$attr["license_id"] = 3;
	$attr["resource_type"] = 2; //directory
		//user = administrateur John Doe
	$attr["user_id"] = 21;	
	$attr["icon_id"] = 5; //directory
	$attr["workspace_id"] = $workspace_id;
	$attr["created"] = $elem->getAttribute("date");
	$attr["updated"] = $elem->getAttribute("date");
	$attr["path"] = "Formation ";
	$attr["name"] = $elem->getAttribute("nom");
	$attr["parent_id"] = 0;
	$attr["lvl"] = 1;
	$attr["previous_id"] = 1;
	$attr["next_id"] = 1;
	$attr["discr"] = "directory";
	WriteResource($attr);
	$resource_formation = $resource;
	WriteDirectory($resource_formation);
	
	WriteResourceRight ($resource_formation);

}
	
function GetModule ($elem){
	global $workspace_id;
	global $resource_formation;
	global $resource_module;
	global $resource;

	$attr["license_id"] = 3;
	$attr["resource_type"] = 2; //directory

	
	$attr["user_id"] = 21;
	$attr["icon_id"] = 5; //directory
	$attr["workspace_id"] = $workspace_id;
	$attr["created"] = $elem->getAttribute("date");
	$attr["updated"] = $elem->getAttribute("date");
	$attr["path"] = "Formation ".$resource_formation." - Module ";
	$attr["name"] = $elem->getAttribute("nom");
	$attr["parent_id"] = 0;
	$attr["lvl"] = 1;
	$attr["previous_id"] = 1;
	$attr["next_id"] = 1;
	$attr["discr"] = "directory";
	WriteResource($attr);
	$resource_module = $resource; 
	WriteDirectory($resource_module);
	
	$element_action = $elem -> getElementsByTagName('action');
	foreach($element_action as $action){
		GetAction($action);	
	}
	
}
	
function GetRubrique ($elem){
	global $workspace_id;
	global $resource_formation;
	global $resource_module; 
	global $resource_rubrique;
	global $resource;


	$attr["license_id"] = 3;
	$attr["resource_type"] = 2; //directory

	
	$attr["user_id"] = 21;
	$attr["icon_id"] = 5; //directory
	$attr["workspace_id"] = $workspace_id;
	$attr["created"] = $elem->getAttribute("date");
	$attr["updated"] = $elem->getAttribute("date");
	$attr["path"] = "Formation ".$resource_formation." - Module ".$resource_module." - Rubrique ";
	$attr["name"] = $elem->getAttribute("nom");
	$attr["parent_id"] = 0;
	$attr["lvl"] = 1;
	$attr["previous_id"] = 1;
	$attr["next_id"] = 1;
	$attr["discr"] = "directory";
	WriteResource($attr);
	$resource_rubrique = $resource; 
	WriteDirectory($resource_rubrique);
}
	
function GetGroupeAction ($elem){
	global $workspace_id;
	global $resource_formation;
	global $resource_module;
	global $resource_rubrique;
	global $resource_activ;
	global $resource;


	$attr["license_id"] = 3;
	$attr["resource_type"] = 5; //activity

	
	$attr["user_id"] = 21;
	$attr["icon_id"] = 1; //activity
	$attr["workspace_id"] = $workspace_id;
	$attr["created"] = $elem->getAttribute("date");
	$attr["updated"] = $elem->getAttribute("date");
	$attr["path"] = "Formation ".$resource_formation." - Module ".$resource_module." - Rubrique ".$resource_rubrique." - Activité ";
	$attr["name"] = $elem->getAttribute("nom");
	$attr["parent_id"] = 0;
	$attr["lvl"] = 1;
	$attr["previous_id"] = 1;
	$attr["next_id"] = 1;
	$attr["discr"] = "activity";
	WriteResource($attr);
	$resource_activ = $resource; 
	
	$attr["id"] = $resource_activ;
	$attr["instruction"] = $elem->getAttribute("description");
	WriteActivity($attr);
	
	$element_action = $elem -> getElementsByTagName('action');
	foreach($element_action as $action){
		GetAction($action);	
	}
	
	//Fin du parcours du groupe d'action
	$resource_activ = 0;
	
}


function GetAction ($elem){
	global $workspace_id;
	global $resource_formation;
	global $resource_module;
	global $resource_rubrique;
	global $resource_activ;
	global $resource;


	$attr["license_id"] = 3;
	$attr["resource_type"] = 1; //file

	
	$attr["user_id"] = 21;
	$attr["icon_id"] = 7; //activity
	$attr["workspace_id"] = $workspace_id;
	$attr["created"] = $elem->getAttribute("date");
	$attr["updated"] = $elem->getAttribute("date");
	if ($resource_rubrique ==0) 
		$attr["path"] = "Formation ".$resource_formation." - Module ".$resource_module." -".$elem->getAttribute("nom");
	else 	
		$attr["path"] = "Formation ".$resource_formation." - Module ".$resource_module." - Rubrique ".$resource_rubrique." - Activité ".$resource_activ." -".$elem->getAttribute("nom");
	$attr["name"] = $elem->getAttribute("nom");
	$attr["parent_id"] = 0;
	$attr["lvl"] = 1;
	$attr["previous_id"] = 1;
	$attr["next_id"] = 1;
	$attr["discr"] = "";
	$type = $elem->getAttribute("type");
	if ($type == "LIEN_FORUM")
			$attr["discr"] = "Claroline\ForumBundle\Entity\Forum";
	elseif ($type == "LIEN_SITE_INTERNET")
			$attr["discr"] = "link";
	else 
			$attr["discr"] = "file";

	WriteResource($attr);
	
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

	$sql = "INSERT INTO `claro_resource`(`license_id`, `resource_type_id`, `user_id`, `icon_id`, `workspace_id`, `creation_date`, `modification_date`, `path`, `name`, `parent_id`, `lvl`, `previous_id`, `next_id`, `discr`) VALUES  (";
	$sql .= $attr["license_id"]." , ".$attr["resource_type"]." ,".$attr["user_id"].",".$attr["icon_id"].",".$attr["workspace_id"].",'".$attr["created"]."','".$attr["updated"]."','";
	$sql .= addslashes($attr["path"])."', '".addslashes($attr["name"])."',".$attr["parent_id"].",".$attr["lvl"].",";
	$sql .= $attr["previous_id"].",".$attr["next_id"].",'".$attr["discr"]."' )";
	mysql_query($sql);
	echo $sql;
	$resource = mysql_insert_id(); 

	
	if ( $attr["discr"] == "directory" || $attr["discr"] == "activity" ){ 
		$sql = "UPDATE `claro_resource` SET `path`='".addslashes($attr["path"]).$resource."' WHERE id = ".$resource;
		mysql_query($sql);
	}	
}


function WriteDirectory($id){

	$sql = "INSERT INTO `claro_directory`(`id`) VALUES  (".$id.")";

	if (mysql_query($sql))
		echo "okkkkkkkkkkkkkkkkkkkkkkkkkkkk";
	else echo "tooooooooooooooo bad";	


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
	