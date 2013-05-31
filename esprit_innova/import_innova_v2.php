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
		<title>Import Base de données Innova</title>
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

	
	$xml = new DomDocument();
	$xml -> load ('./datas/'.$_POST["id"]);

		
	$element_workspace = $xml->getElementsByTagName('workspace');
	foreach($element_workspace as $workspace){
		getWorkspace($workspace);
	}
	
	print "<p> Enregistrement effectué </p>";
}
	
	
///////////////////////////////////////////////////////////////////////
//////// Fonction d'enregistrement de workspace ////////////////////////
///////////////////////////////////////////////////////////////////////

function getWorkspace ($workspace){
	global $resource_formation;
	global $resource_module;
	global $resource_rubrique;
	global $resource_activ;
	global $resource;
	
	$attr["intitule"] = addslashes($workspace->getAttribute("nom"));
	$attr["description"] = addslashes($workspace->getAttribute("description"));
	$attr["type"] = addslashes($workspace->getAttribute("type"));
	$attr["left"] = 1 ;
	$attr["level"] = 1;
	$attr["right"] = 1;
	$attr["position"] = 1; 
	$attr["is_pattern"] = 0; 	
	$attr["pathStatusType_id"] = 1 ; // correspond à public
	$attr["nodeType_id"] = 2; // 2 correspond à un workspace
	$attr["tree_id"] = 1;
	
	
	//Création de workspace
	$sql = "INSERT INTO `inl_workspace`(`is_pattern`, `name`, `description`) VALUES"; 
	$sql .= "(".$attr["is_pattern"].",'".$attr["intitule"]."','".$attr["description"]."')";
	mysql_query($sql);
	$resource = mysql_insert_id();
	
	
	//Création de inl_path_node 
	$sql = "INSERT INTO `inl_path_node`(`level`, `position`, `nodeType_id`, `lft`, `rgt`, `node_ref_id`, `tree_id`) VALUES"; 
	$sql .= "(".$attr["level"].",".$attr["position"].",".$attr["nodeType_id"].",".$attr["left"].",".$attr["right"].",".$resource.",".$attr["tree_id"].")";
	mysql_query($sql);
	$attr["pathNode_id"] = mysql_insert_id();
	

	
	
	//Création de inl_path 
	$sql = "INSERT INTO `inl_path`(`is_pattern`, `name`, `description`, `pathStatusType_id`, `pathNode_id`) VALUES"; 
	$sql .= "(".$attr["is_pattern"].",'".$attr["intitule"]."','".$attr["description"]."',".$attr["pathStatusType_id"].",".$attr["pathNode_id"].")";
	mysql_query($sql);
		

	set_time_limit(10) ;
	if ($attr["type"] == "FORMATION"){
		$resource_formation = $attr["pathNode_id"];
	}	
	elseif ($attr["type"] == "MODULE"){
		$resource_module = $attr["pathNode_id"]; 
		$resource_activ = 0;
		writeParentPathNode ($resource_module, $resource_formation, 2);
		$abstract_entity = $workspace -> getElementsByTagName('abstract_entity');
		foreach($abstract_entity as $a_entity){
			getAbstractEntity($a_entity);	
		}
	}	
	elseif ($attr["type"] == "RUBRIQUE"){
		$resource_rubrique = $attr["pathNode_id"];
		$resource_activ = 0;
		writeParentPathNode ($resource_rubrique, $resource_module, 3);
	}
	else{
		$resource_activ = $attr["pathNode_id"];
		writeParentPathNode($resource_activ, $resource_rubrique, 4);
		$abstract_entity = $workspace -> getElementsByTagName('abstract_entity');
		foreach($abstract_entity as $a_entity){
			getAbstractEntity($a_entity);	
		}
	}

	

}
	
///////////////////////////////////////////////////////////////////////
//////// Fonction d'enregistrement de abstract_entity //////////////////
///////////////////////////////////////////////////////////////////////	

function getAbstractEntity ($entity){
	global $resource_activ; 
	
	$attr["abstract_entity_type_id"] = 12; 
	$attr["workspace_id"] = 1;
	
	$attr["url"] ="";
	$attr["name"] = addslashes($entity->getAttribute("nom"));
	$attr["description"] = addslashes($entity->getAttribute("description"));
	

					
	if ($resource_activ ==0) //on enregistre dans un module
	{
		//Création de abstract_entity
		$sql = "INSERT INTO `inl_abstract_entity`(`abstractEntityType_id`) VALUES"; 
		$sql .= "(".$attr["abstract_entity_type_id"].")";
		mysql_query($sql);
		$abstract_entity_id = mysql_insert_id();
		
		//Création de workspace_to_abstract_entity
		$sql = "INSERT INTO `inl_workspace_to_abstract_entity`(`workspace_id`, `abstractentity_id`) VALUES"; 
		$sql .= "(".$attr["workspace_id"].",".$abstract_entity_id.")";
		mysql_query($sql);
		
		//Création de concrete_resource
		$sql = "INSERT INTO `inl_concrete_resource`(`name`, `url`, `description`) VALUES "; 
		$sql .= "('".$attr["name"]."','".$attr["url"]."','".$attr["description"]."')";
		mysql_query($sql);
		$concrete_resource_id = mysql_insert_id();
			
		//Création de abstract_entity_to_concrete_resource	
		$sql = "INSERT INTO `inl_abstract_entity_to_concrete_resource`(`abstractentity_id`, `concreteresource_id`) VALUES"; 
		$sql .= "(".$abstract_entity_id.",".$concrete_resource_id.")";
		mysql_query($sql);
					
		$concrete_entity = $entity -> getElementsByTagName('concrete_entity');
		foreach($concrete_entity as $concrete_entity){
			getConcreteEntity($concrete_entity, $concrete_resource_id);	
		}
	}
	else {	//on enregistre dans un groupe d'activité

		$sql = "SELECT A.abstractentity_id FROM inl_concrete_resource C, inl_abstract_entity_to_concrete_resource A WHERE"; 
		$sql .= " C.name = '".$attr["name"]."'";
		$sql .= " AND C.description = '".$attr["description"]."'";
		$sql .= " AND C.id = A.concreteresource_id";
		$result = mysql_query($sql);
		while ($row = mysql_fetch_array($result)) {
			$abstract_entity_id = $row["abstractentity_id"];
		}
				
		$sql = "UPDATE `inl_workspace_to_abstract_entity` SET `workspace_id`= '".$attr["workspace_id"]."' WHERE"; 
		$sql .= "`abstractentity_id` = ".$abstract_entity_id;
		mysql_query($sql);
		
	} 
	
}


///////////////////////////////////////////////////////////////////////
//////// Fonction d'enregistrement de concrete_entity /////////////////
///////////////////////////////////////////////////////////////////////

function getConcreteEntity ($c_entity, $concrete_resource_id){



	$chemin_innova =$c_entity->getAttribute("datas");
	

	$attr["url"] = $chemin_innova;
	
	
	$sql = "UPDATE `inl_concrete_resource` SET `url`= '".$attr["url"]."' WHERE"; 
	$sql .= "`id` = ".$concrete_resource_id;
	mysql_query($sql);

	
}	

///////////////////////////////////////////////////////////////////////
//////// Fonction d'enregistrement de agregation de workspace//////////
///////////////////////////////////////////////////////////////////////	

function writeParentPathNode ($id_workspace, $parent, $level){
	$sql = "UPDATE `inl_path_node` SET `parent_id`= ".$parent.",`level`=".$level." WHERE "; 
	$sql .= " id = ".$id_workspace;
	mysql_query($sql);
}	
	


?>	
	