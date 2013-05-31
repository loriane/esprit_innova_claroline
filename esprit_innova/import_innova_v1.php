 <?php
 
$serveur = "localhost"; 
$username = "innova";
$password = "innova"; 
$base = "innova"; 
 
$link = mysql_connect($serveur,$username,$password); 
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
		<title>Import Base de données</title>
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
	$resource_formation = 0;
	$resource_module = 0;
	$resource_rubrique = 0;
	$resource_activ = 0;
	$resource = 0;
	$resource_sup = 0;
	$id_user = 0;
	$id_userspace = 0;
	$nom_formation ="";
	
	$xml = new DomDocument();
	$xml -> load ('./datas/'.$_POST["id"]);

	writeType();
	
	$element_user = $xml->getElementsByTagName('user');
	foreach($element_user as $user){
		getUser($user);
	}
	
	$element_workspace = $xml->getElementsByTagName('workspace');
	foreach($element_workspace as $workspace){
		getWorkspace($workspace);
	}
	
	print "<p> Enregistrement effectué </p>";
}
	

	
function get_statut($elem){
	$statut_id = "";
	$statut = $elem->getAttribute("statut");
	if ($statut == "OUVERT")
		$statut_id = 1;
	else 
		$statut_id = 0;

	return $statut_id;  
}

///////////////////////////////////////////////////////////////////////
//////// Fonction d'enregistrement de user /////////////////////////////
///////////////////////////////////////////////////////////////////////

function getUser ($elem){
	global $id_userspace;
	global $id_user;

	$attr["nom"] = addslashes($elem->getAttribute("nom"));
	$attr["prenom"] = addslashes($elem->getAttribute("prenom"));
	$attr["mail"] = addslashes($elem->getAttribute("mail"));
	$attr["date_naissance"] = addslashes($elem->getAttribute("date_naissance"));
	$attr["id_role"] = 1;

	$query = "SELECT * FROM inl_user WHERE lastName = '".$attr["nom"]."' AND firstName = '".$attr["prenom"]."' AND birthDate = '".$attr["date_naissance"]."'";
	$result = mysql_query($query);
	$compteur = mysql_num_rows($result);
	if ($compteur ==0){
		$sql_abstract = "INSERT INTO `inl_abstract_user`(`plus`) VALUES (1)";
		mysql_query($sql_abstract);
		$id_user = mysql_insert_id();
		
		$query = "SELECT * FROM inl_generic_role WHERE name = 'createur'";
		$result = mysql_query($query);
		$compteur = mysql_num_rows($result);
		if ($compteur ==0){
			$sql_role = "INSERT INTO `inl_generic_role`(`name`) VALUES ('createur')";
			mysql_query($sql_role);
			$attr["id_role"] = mysql_insert_id(); 
		}
		
		$sql_user = "INSERT INTO `inl_user`( `role_id`,`firstName`, `lastName`, `birthDate`) ";
		$sql_user .= "VALUES (".$attr["id_role"].",'".$attr["prenom"]."','".$attr["nom"]."','".$attr["date_naissance"]."')";
		$id_user = mysql_query($sql_user);
		$sql_userspace = "INSERT INTO `inl_user_space`(`user_id`) VALUES (".$id_user.")";
		mysql_query($sql_userspace);
		$id_userspace = mysql_insert_id();
		


	}
	else {
		while ($row = mysql_fetch_array($result)) {
			$id_user = $row["id"];
			$query = "SELECT * FROM inl_user_space WHERE user_id = ".$id_user;
			$result = mysql_query($query);
			while ($row = mysql_fetch_array($result)) {
				$id_userspace = $row["id"];
			}
		}
	}
}
	
///////////////////////////////////////////////////////////////////////
//////// Fonction d'enregistrement de workspace ////////////////////////
///////////////////////////////////////////////////////////////////////

function getWorkspace ($workspace){
	global $resource_sup;
	global $resource_formation;
	global $resource_module;
	global $resource_rubrique;
	global $resource_activ;
	global $resource;
	global $nom_formation;
	
	$attr["intitule"] = addslashes($workspace->getAttribute("nom"));
	$attr["description"] = addslashes($workspace->getAttribute("description"));
	$attr["statut"] = addslashes($workspace->getAttribute("statut"));
	$attr["path"] = "";
	$attr["type"] = addslashes($workspace->getAttribute("type"));
	$modalite = addslashes($workspace->getAttribute("modalite"));
	if ($modalite == "INDIVIDUEL") 
		$attr["individuel"] = 1;
	else 
		$attr["individuel"] = 0;
	$attr["left"] = 1 ;
	$attr["level"] = 1;
	$attr["right"] = 1;
	$attr["position"] = 1; 
	
	
	$sql = "INSERT INTO `inl_abstract_workspace`(`title`, `path`, `description`, `status`, `type`,`left`, `level`, `right`, `position`,`individuel`) VALUES"; 
	$sql .= "('".$attr["intitule"]."','".$attr["path"]."','".$attr["description"]."','".$attr["statut"]."','".$attr["type"]."',".$attr["left"].",".$attr["level"].",".$attr["right"].",".$attr["position"].",".$attr["individuel"].")";
	mysql_query($sql);
	$resource = mysql_insert_id();
	
	set_time_limit(10) ;
	if ($attr["type"] == "FORMATION"){
		$resource_formation = $resource;
		$nom_formation = $attr["intitule"];
	}	
	elseif ($attr["type"] == "MODULE"){
		$resource_module = $resource; 
		$resource_activ = 0;
		writeParentWorkspace ($resource_module, $resource_formation, 2);
	}	
	elseif ($attr["type"] == "RUBRIQUE"){
		$resource_rubrique = $resource;
		$resource_activ = 0;
		writeParentWorkspace ($resource_rubrique, $resource_module, 3);
	}
	else{
		$resource_activ = $resource;
		writeParentWorkspace($resource_activ, $resource_rubrique, 4);
	}

	
	$abstract_entity = $workspace -> getElementsByTagName('abstract_entity');
	foreach($abstract_entity as $a_entity){
		getAbstractEntity($a_entity);	
	}
	$resource_sup = $resource;
}
	
///////////////////////////////////////////////////////////////////////
//////// Fonction d'enregitrement de abstract_entity //////////////////
///////////////////////////////////////////////////////////////////////	

function getAbstractEntity ($entity){
	global $resource;
	global $resource_module;
	global $resource_activ;
	
	$attr["intitule"] = addslashes($entity->getAttribute("nom"));
	$attr["description"] = addslashes($entity->getAttribute("description"));
	$attr["type_digital"] = 1;
	$attr["modalite"] = addslashes($entity->getAttribute("modalite"));
	$attr["chemin_icon"] = "";

	
	//pour obtenir id du type
	$type = explode("_",$entity->getAttribute("type"),2);
	$query = "SELECT id FROM inl_type WHERE name = '".$type[1]."'";
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		$attr["id_type"] = $row["id"];
	}

	
	$attr["id_local_role"] = 1;
	//write_local_role

	
	$query = "SELECT A.id, W.roleentity_id FROM inl_abstract_entity A , inl_roleentity_abstractentity R, inl_roleentity_abstractworkspace W WHERE A.name = '".$attr["intitule"]."'";
	$query .= " AND A.id = R.abstractentity_id";
	$query .= " AND R.roleentity_id = W.roleentity_id";
	$query .= " AND W.abstractworkspace_id = ".$resource_module;
	$result = mysql_query($query);
	if ($result !=false){
		$compteur = mysql_num_rows($result);
		if ($compteur ==0){
			//avec le local_role 
			/*
			$attr["id_local_role"] = ;
			$sql = "INSERT INTO `abstract_entity`(`intitule`, `id_type`, `id_local_role`, `description`, `type_digital`, `modalite`) VALUES"; 
			$sql .= "('".$attr["intitule"]."','".$attr["id_type"]."','".$attr["id_local_role"]."','".$attr["description"]."','".$attr["type_digital"]."','".$attr["modalite"]."')";
			$query = $sql;
			mysql_query($query);*/
			
			//sans le local_role
			set_time_limit(10) ;
			if ($resource_module !=0){
				$sql = "INSERT INTO `inl_abstract_entity`(`name`, `type_id`,  `description`, `digital`, `pathIcon`) VALUES"; 
				$sql .= "('".$attr["intitule"]."','".$attr["id_type"]."','".$attr["description"]."','".$attr["type_digital"]."','".$attr["chemin_icon"]."')";
				mysql_query($sql);
				$id_entity = mysql_insert_id();
				
				$sql = "INSERT INTO `inl_role_entity`(`description`) VALUES"; 
				$sql .= "('Ressource')";
				mysql_query($sql);
				$id_role_entity = mysql_insert_id();
				
				$sql = "INSERT INTO `inl_roleentity_abstractentity`(`roleentity_id`, `abstractentity_id`) VALUES"; 
				$sql .= "(".$id_role_entity.",".$id_entity.")";
				mysql_query($sql);
				
				
				$sql = "INSERT INTO `inl_roleentity_abstractworkspace`(`roleentity_id`, `abstractworkspace_id`) VALUES"; 
				$sql .= "(".$id_role_entity.",".$resource_module.")";
				mysql_query($sql);
				
				
				$concrete_entity = $entity -> getElementsByTagName('concrete_entity');
				foreach($concrete_entity as $c_entity){
					getConcreteEntity($c_entity, $entity, $id_entity, $id_role_entity);	
				}
			}
			
		}
		else {
			while ($row = mysql_fetch_array($result)) {
				$id_role_entity = $row["roleentity_id"];
			}
			if ($resource_activ !=0){
				$sql = "UPDATE `inl_roleentity_abstractworkspace` SET `abstractworkspace` = ".$resource_activ ;
				$sql .= " WHERE `roleentity_id` =".$id_role_entity ;
				mysql_query($sql);
			}
		}
	}
}


///////////////////////////////////////////////////////////////////////
//////// Fonction d'enregistrement de concrete_entity //////////////////
///////////////////////////////////////////////////////////////////////

function getConcreteEntity ($c_entity, $a_entity, $id_a_entity, $id_role_entity){
	global $id_userspace;
	global $nom_formation;

	//déplacement du fichier
	$chemin_esprit = addslashes($a_entity->getAttribute("nom"));
	$chemin_claro = "datas/".$id_userspace."/".$nom_formation;
	/*
	$chemin_esprit = $c_entity->getAttribute("datas");

	rename ($chemin_esprit, $chemin_claro);
	*/

	//récupére les folder et créer si besoin 
	$query = "SELECT * FROM inl_folder WHERE name = \"".$nom_formation."\" AND userspace_id = ".$id_userspace;
	$result = mysql_query($query);
	$compteur = mysql_num_rows($result);
	if ($compteur ==0){
		$sql = "INSERT INTO `inl_folder`(`name`, `path`, `userspace_id`) VALUES ";
		$sql .= "('".$nom_formation."','".$chemin_claro."',".$id_userspace.")";
		mysql_query($sql);
		$id_folder = mysql_insert_id();  
	}
	else {
		while ($row = mysql_fetch_array($result)) {
			$id_folder = $row["id"];
		}
	}
	
	
	//création de concrete_resource
	$attr["nom"] = addslashes($a_entity->getAttribute("nom"));
	$attr["description"] = addslashes($a_entity->getAttribute("description")); ;
	$attr["chemin"] = $chemin_claro;
	
	
	$sql = "INSERT INTO `inl_concrete_resource`(`name`, `path`, `description`) VALUES"; 
	$sql .= "('".$attr["nom"]."','".$attr["chemin"]."','".$attr["description"]."')";
	mysql_query($sql);
	$id_c_entity = mysql_insert_id();
	
	
	//création de concrete_entity_userspace
	$sql = "INSERT INTO `inl_concreteresource_userspace`(`concreteresource_id`, `userspace_id`) VALUES"; 
	$sql .= "(".$id_c_entity.",".$id_userspace.")";
	mysql_query($sql);
	
	
	//création de folder_entity 
	$sql = "INSERT INTO `inl_concreteresource_folder`(`concreteresource_id`, `folder_id`) VALUES"; 
	$sql .= "('".$id_c_entity."','".$id_folder."')";
	mysql_query($sql);
	
}	

///////////////////////////////////////////////////////////////////////
//////// Fonction d'enregistrement de agregation de workspace//////////
///////////////////////////////////////////////////////////////////////	

function writeParentWorkspace ($id_workspace, $parent, $level){
	$sql = "UPDATE `inl_abstract_workspace` SET `parent_id`= ".$parent.",`level`=".$level." WHERE "; 
	$sql .= " id = ".$id_workspace;
	mysql_query($sql);
}	

///////////////////////////////////////////////////////////////////////
//////// Fonction d'enregistrement de types préenregistrés   //////////
///////////////////////////////////////////////////////////////////////	

function writeType (){
	$sql = "SELECT * FROM `inl_type` WHERE "; 
	$sql .= " name = 'TEXTE_FORMATTE'";
	mysql_query($sql);
	$result = mysql_query($sql);
	$compteur = mysql_num_rows($result);
	if ($compteur ==0){
		$sql = "INSERT INTO inl_type(`name`) VALUES ('VIDE')";
		mysql_query($sql);

		$sql = "INSERT INTO inl_type(`name`) VALUES (INTITULE_NON_ACTIVABLE)";
		mysql_query($sql);		
	
		$sql = "INSERT INTO inl_type(`name`) VALUES ('PAGE_HTML')";
		mysql_query($sql);		
	
		$sql = "INSERT INTO inl_type(`name`) VALUES ('DOCUMENT_TELECHARGER')";
		mysql_query($sql);	
		
		$sql = "INSERT INTO inl_type(`name`) VALUES ('SITE_INTERNET')";
		mysql_query($sql);	

		$sql = "INSERT INTO inl_type(`name`) VALUES ('CHAT')";
		mysql_query($sql);			
	
		$sql = "INSERT INTO inl_type(`name`) VALUES ('FORUM')";
		mysql_query($sql);	
		
		$sql = "INSERT INTO inl_type(`name`) VALUES ('GALERIE')";
		mysql_query($sql);			
		
		$sql = "INSERT INTO inl_type(`name`) VALUES ('COLLECTICIEL')";
		mysql_query($sql);	
		
		$sql = "INSERT INTO inl_type(`name`) VALUES ('UNITE')";
		mysql_query($sql);			
		
		$sql = "INSERT INTO inl_type(`name`) VALUES ('FORMULAIRE')";
		mysql_query($sql);	

		$sql = "INSERT INTO inl_type(`name`) VALUES ('TEXTE_FORMATTE')";
		mysql_query($sql);			

		$sql = "INSERT INTO inl_type(`name`) VALUES ('GLOSSAIRE')";
		mysql_query($sql);	
		
		$sql = "INSERT INTO inl_type(`name`) VALUES ('TABLEAU_DE_BORD')";
		mysql_query($sql);			

		$sql = "INSERT INTO inl_type(`name`) VALUES ('HOT_POTATOES')";
		mysql_query($sql);			
		
	}
}	


?>	
	