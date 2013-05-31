 <?php
$link = mysql_connect('localhost','root',''); 
if (!$link) { die('Pb de connexion à la BD: ' . mysql_error()); 
} 
else{
	mysql_query("USE test");
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

	$element_user = $xml->getElementsByTagName('user');
	foreach($element_user as $user){
		get_user($user);
	}
	
	$element_workspace = $xml->getElementsByTagName('workspace');
	foreach($element_workspace as $workspace){
		get_workspace($workspace);
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

function get_user ($elem){
	global $id_userspace;
	global $id_user;

	$attr["nom"] = addslashes($elem->getAttribute("nom"));
	$attr["prenom"] = addslashes($elem->getAttribute("prenom"));
	$attr["mail"] = addslashes($elem->getAttribute("mail"));
	$attr["date_naissance"] = addslashes($elem->getAttribute("date_naissance"));
	$attr["id_role"] = 1;

	$query = "SELECT * FROM user WHERE nom = '".$attr["nom"]."' AND prenom = '".$attr["prenom"]."' AND mail = '".$attr["mail"]."'";
	$result = mysql_query($query);
	$compteur = mysql_num_rows($result);
	if ($compteur ==0){
		$sql_abstract = "INSERT INTO `abstract_user`(`plus`) VALUES (1)";
		mysql_query($sql_abstract);
		$id_user = mysql_insert_id();
		
		$query = "SELECT * FROM generic_role WHERE intitule = 'createur'";
		$result = mysql_query($query);
		$compteur = mysql_num_rows($result);
		if ($compteur ==0){
			$sql_role = "INSERT INTO `generic_role`(`intitule`) VALUES ('createur')";
			mysql_query($sql_role);
			$attr["id_role"] = mysql_insert_id(); 
		}
		
		$sql_user = "INSERT INTO `user`(`id_abstract_user`, `id_generic_role`, `nom`, `prenom`, `date_naissance`, `mail`) ";
		$sql_user .= "VALUES (".$id_user.",".$attr["id_role"].",'".$attr["nom"]."','".$attr["prenom"]."','".$attr["date_naissance"]."','".$attr["mail"]."')";
		mysql_query($sql_user);
		
		$sql_userspace = "INSERT INTO `userspace`(`id_user`) VALUES (".$id_user.")";
		mysql_query($sql_userspace);
		$id_userspace = mysql_insert_id();
	}
	else {
		while ($row = mysql_fetch_array($result)) {
			$id_user = $row["id_abstract_user"];
			$query = "SELECT * FROM userspace WHERE id_user = ".$id_user;
			$result = mysql_query($query);
			while ($row = mysql_fetch_array($result)) {
				$id_userspace = $row["id_userspace"];
			}
		}
	}
}
	
///////////////////////////////////////////////////////////////////////
//////// Fonction d'enregitrement de workspace ////////////////////////
///////////////////////////////////////////////////////////////////////

function get_workspace ($workspace){
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
	$attr["chemin"] = "";
	$attr["type"] = addslashes($workspace->getAttribute("type"));
	$attr["modalite"] = addslashes($workspace->getAttribute("modalite"));
	
	
	$sql = "INSERT INTO `workspace`(`intitule`, `chemin`, `description`, `statut`, `type`, `modalite`) VALUES"; 
	$sql .= "('".$attr["intitule"]."','".$attr["chemin"]."','".$attr["description"]."','".$attr["statut"]."','".$attr["type"]."','".$attr["modalite"]."')";
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
		write_agrega_workspace ($resource_formation,$resource_module);
	}	
	elseif ($attr["type"] == "RUBRIQUE"){
		$resource_rubrique = $resource;
		$resource_activ = 0;
		write_agrega_workspace ($resource_module, $resource_rubrique);
	}
	else{
		$resource_activ = $resource;
		write_agrega_workspace($resource_rubrique, $resource_activ);
	}

	
	$abstract_entity = $workspace -> getElementsByTagName('abstract_entity');
	foreach($abstract_entity as $a_entity){
		get_abstract_entity($a_entity);	
	}
	$resource_sup = $resource;
}
	
///////////////////////////////////////////////////////////////////////
//////// Fonction d'enregitrement de abstract_entity //////////////////
///////////////////////////////////////////////////////////////////////	

function get_abstract_entity ($entity){
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
	$query = "SELECT id_type FROM type WHERE intitule = '".$type[1]."'";
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		$attr["id_type"] = $row["id_type"];
	}

	
	$attr["id_local_role"] = 1;
	//write_local_role

	
	$query = "SELECT A.id_abstract_entity, W.id_workspace_entity FROM abstract_entity A , workspace_entity_role W WHERE A.intitule = '".$attr["intitule"]."'";
	$query .= " AND A.id_abstract_entity = W.id_abstract_entity";
	$query .= " AND W.id_workspace = ".$resource_module;
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
				$sql = "INSERT INTO `abstract_entity`(`intitule`, `id_type`,  `description`, `type_digital`, `modalite`, `chemin_icon`) VALUES"; 
				$sql .= "('".$attr["intitule"]."','".$attr["id_type"]."','".$attr["description"]."','".$attr["type_digital"]."','".$attr["modalite"]."','".$attr["chemin_icon"]."')";
				mysql_query($sql);
				$id_entity = mysql_insert_id();
				
				$sql = "INSERT INTO `workspace_entity_role`(`id_workspace`, `id_abstract_entity`, `intitule`) VALUES"; 
				$sql .= "(".$resource_module.",".$id_entity.",'Ressource')";
				$id_workspace_entity = mysql_query($sql);
				
				
				$concrete_entity = $entity -> getElementsByTagName('concrete_entity');
				foreach($concrete_entity as $c_entity){
					get_concrete_entity($c_entity, $entity, $id_entity, $id_workspace_entity);	
				}
			}
			
		}
		else {
			while ($row = mysql_fetch_array($result)) {
				$id_workspace = $row["id_workspace_entity"];
			}
			if ($resource_activ !=0){
				$sql = "UPDATE `workspace_entity_role` SET `id_workspace`=".$resource_activ ;
				$sql .= " WHERE `id_workspace_entity` =".$id_workspace ;
				mysql_query($sql);
			}
		}
	}
}


///////////////////////////////////////////////////////////////////////
//////// Fonction d'enregitrement de concrete_entity //////////////////
///////////////////////////////////////////////////////////////////////

function get_concrete_entity ($c_entity, $a_entity, $id_a_entity, $id_workspace_entity){
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
	$query = "SELECT * FROM folder WHERE nom = \"".$nom_formation."\" AND id_userspace = ".$id_userspace;
	$result = mysql_query($query);
	$compteur = mysql_num_rows($result);
	if ($compteur ==0){
		$sql = "INSERT INTO `folder`(`nom`, `chemin`, `id_userspace`) VALUES ";
		$sql .= "('".$nom_formation."','".$chemin_claro."',".$id_userspace.")";
		mysql_query($sql);
		$id_folder = mysql_insert_id();  
	}
	else {
		while ($row = mysql_fetch_array($result)) {
			$id_folder = $row["id_folder"];
		}
	}
	
	
	//création de concrete_entity 
	$attr["nom"] = addslashes($a_entity->getAttribute("nom"));
	$attr["description"] = addslashes($a_entity->getAttribute("description")); ;
	$attr["chemin"] = $chemin_claro;
	
	
	$sql = "INSERT INTO `concrete_entity`(`nom`, `chemin`, `description`) VALUES"; 
	$sql .= "('".$attr["nom"]."','".$attr["chemin"]."','".$attr["description"]."')";
	mysql_query($sql);
	$id_c_entity = mysql_insert_id();
	
	
	//création de concrete_abstract_entity
	$sql = "INSERT INTO `concrete_abstract_entity`(`id_concrete_entity`, `id_workspace_entity`) VALUES"; 
	$sql .= "('".$id_c_entity."','".$id_workspace_entity."')";
	mysql_query($sql);
	
	
	//création de concrete_entity_userspace
	$sql = "INSERT INTO `concrete_entity_userspace`(`id_userspace`, `id_concrete_entity`) VALUES"; 
	$sql .= "('".$id_userspace."','".$id_c_entity."')";
	mysql_query($sql);
	
	
	//création de folder_entity 
	$sql = "INSERT INTO `folder_entity`(`id_folder`, `id_concrete_entity`) VALUES"; 
	$sql .= "('".$id_folder."','".$id_c_entity."')";
	mysql_query($sql);
	
}	

///////////////////////////////////////////////////////////////////////
//////// Fonction d'enregitrement de agregation de workspace //////////
///////////////////////////////////////////////////////////////////////	

function write_agrega_workspace ($sup, $inf){
	$sql = "INSERT INTO `workspace_workspace`(`id_workspace_sup`, `id_workspace_inf`) VALUES"; 
	$sql .= "(".$sup.",".$inf.")";
	mysql_query($sql);
}	





?>	
	