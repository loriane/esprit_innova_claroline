 <?php

$serveur = "localhost"; 
$username = "esprit";
$password = "azerty"; 
$base = "esprit"; 
 
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
		<title>esprit XMLizer</title>
		<meta charset=\"utf-8\"/>
	</head>
	<body>

<?php	
// FORMULAIRE
print "<form action='".$_SERVER['PHP_SELF']."' method='POST'><select name='id'>";

$query = "SELECT * FROM Formation";
$result = mysql_query($query);
while ($row = mysql_fetch_array($result)) {
	print '<option value="'.$row['IdForm'].'">'.$row['NomForm'].'</option>';
}
print"</select><br/><input type='submit' value='XMLize this !'>";
print "<input type='button' value='Retour' onclick=\"self.location.href='../index.php'\">";
print "</form>"; 


// LANCEMENT PROCESS SI SUBMIT FORMULAIRE
if(isset($_POST["id"])){
	$formation_id = 0;
	$activite_id = 0;
	$xml = "<?xml version='1.0' encoding='UTF-8'?>\n<!DOCTYPE parcours SYSTEM \"./../DTD_2.dtd\">\n\n<parcours>\n";
	

	$query = "SELECT * FROM Formation WHERE IdForm = ".$_POST["id"] ;
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		$nom = Nettoyer($row["NomForm"]);
		WriteUser($row["IdPers"]);
	}	
	
	GetFormation($_POST["id"]);
	
	$xml .= "</parcours>\n";
	$handle = fopen("../datas/".$nom.".xml", "w+");
	
	fwrite($handle, $xml);
	fclose($handle);
	
	$xml_data = "../datas/".$nom.".xml";
	$xsl_data = "../xsl/affichage.xsl";

	$xsl = new XSLTProcessor();
	$xsldoc = new DOMDocument();
	$xsldoc->load($xsl_data);
	$xsl->importStyleSheet($xsldoc);

	$xmldoc = new DOMDocument();
	$xmldoc->load($xml_data);
	echo $xsl->transformToXML($xmldoc); 

}



function WriteUser($id){
	global $xml;
	$query = "SELECT * FROM Personne WHERE IdPers = ".$id ;
	$result = mysql_query($query);
	
	//Ecriture de l'user
	while ($row = mysql_fetch_array($result)) {
		$attr["nom"] = $row["Nom"];
		$attr["prenom"] = $row["Prenom"];
		$attr["mail"] = $row["Email"];
		$attr["date_naissance"] = $row["DateNaiss"];
	}	
	$xml .= "\t<user";
	WriteAttribut($attr);
	$xml .= "/>\n\n";
		
}

function Nettoyer($str){

      $str = strtr($str,
      "ÀÁÂÃÄÅàáâãäåÇçÒÓÔÕÖØòóôõöøÈÉÊËèéêëÌÍÎÏìíîïÙÚÛÜùúûü¾ÝÿýÑñ",
      "AAAAAAaaaaaaCcOOOOOOooooooEEEEeeeeIIIIiiiiUUUUuuuuYYyyNn");
      
	  $str = str_replace('Æ','AE',$str);
      $str = str_replace('æ','ae',$str);
      $str = str_replace('¼','OE',$str);
      $str = str_replace('½','oe',$str);
      
      $str = preg_replace('/[^a-z0-9_\s\'\:\/\[\]-]/','',strtolower($str));
      
      $str = preg_replace('/[\s\'\:\/\[\]-]+/',' ',trim($str));
	  
	  $str = str_replace("\n","",$str);
	  $str = str_replace("\r","",$str);
	  $str = strip_tags($str);
	  $str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
   
      $res = str_replace(' ','-',$str);

      
      return $res;
}


function GetTypeRubrique($id){
	$type[1] = "LIEN_PAGE_HTML";
	$type[2] = "LIEN_DOCUMENT_TELECHARGER";
	$type[3] = "LIEN_SITE_INTERNET";
	$type[4] = "LIEN_CHAT";
	$type[5] = "LIEN_FORUM";
	$type[10] = "LIEN_TEXTE_FORMATTE";
	$type[14] = "LIEN_INTITULE_NON_ACTIVABLE";
	return $type[$id];
}

function GetStatut($id){
	$statut[1] = "FERME";
	$statut[2] = "OUVERT";
	$statut[3] = "INVISIBLE";
	$statut[4] = "ARCHIVE";
	$statut[5] = "CLOTURE";
	return $statut[$id];
}

function GetModalite($id){
	$modalite[0] = "INDIVIDUEL";
	$modalite[1] = "INDIVIDUEL";
	$modalite[2] = "COLLECTIF";
	return $modalite[$id];
}


function RecupIntitule($id){
	$int = "";
	$query = "SELECT NomIntitule FROM Intitule WHERE IdIntitule = ".$id;
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		$int = $row['NomIntitule'];
	}
	return $int;
}

function RecupType($id){
	$type = "";
	if ($id !=0){
		$query = "SELECT NomTypeSousActiv FROM TypeSousActiv WHERE IdTypeSousActiv = ".$id;
		$result = mysql_query($query);
		while ($row = mysql_fetch_array($result)) {
			$type = $row['NomTypeSousActiv'];
		}
	}
	else 
		$type = "LIEN_VIDE";
	return $type;
	
}



function WriteAttribut($tab_attr){
	global $xml;
	foreach($tab_attr as $key=>$value){
		$xml .= ' '.$key.'="'.$value.'"';
	}
}

function GetSousActivite($id){
	global $xml;
	global $formation_id;
	global $activite_id;

	$query = "SELECT * FROM SousActiv WHERE IdActiv = ".$id." ORDER BY OrdreSousActiv ASC" ;
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		$attr["id"] = $row["IdSousActiv"];
		$attr["nom"] = Nettoyer($row["NomSousActiv"]);
		$attr["description"] = Nettoyer($row["DescrSousActiv"]);
		$attr["type_digital"] = "digital";
		$attr["type"] = RecupType($row["IdTypeSousActiv"]);
		$attr["modalite"] = GetModalite($row["ModaliteSousActiv"]);
		
		$xml .= "\t\t\t\t\t<abstract_entity";
		WriteAttribut($attr);

		
		if(preg_match("/^(;\d?;)*(.*?)( ?;.*;?)*$/",$row["DonneesSousActiv"],$match)){
			$datas = Nettoyer($match[2]);	
		}
		else{$attr_concret["datas"] = $row["DonneesSousActiv"];}
		
		if ($datas != ""){
			$xml .= " >\n";
			if ($attr["type"] == "LIEN_SITE_INTERNET")
				$attr_concret["datas"] = $datas;
			else 	
				$attr_concret["datas"] = "formation/f".$formation_id."/activ_".$activite_id."/".$datas;
			
			$xml .= "\t\t\t\t\t\t<concrete_entity";
			WriteAttribut($attr_concret);
			$xml .= " />\n";	
			$xml .= "\t\t\t\t\t</abstract_entity>\n";
		}	
		else 
			$xml .= " />\n";

	}
}


function GetActivite($id){
	global $xml;
	global $activite_id;
	$query = "SELECT * FROM Activ WHERE IdRubrique = ".$id." ORDER BY OrdreActiv ASC" ;
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		$attr["id"] = $row["IdActiv"];
		$activite_id = $attr["id"];
		$attr["nom"] = Nettoyer($row["NomActiv"]);
		$attr["description"] = Nettoyer($row["DescrActiv"]);
		$attr["statut"] = GetStatut($row["StatutActiv"]);
		$attr["type"] = "GROUPE_ACTIONS";
		$attr["modalite"] = GetModalite($row["ModaliteActiv"]);
  
		$xml .= "\t\t\t\t<workspace";
		WriteAttribut($attr);
		$xml .= " >\n";
		GetSousActivite($attr["id"]);
		$xml .= "\t\t\t\t</workspace>\n";
	}
}

function GetRubrique($id){
	global $xml;
	global $formation_id;
	$query = "SELECT * FROM Module_Rubrique WHERE IdMod = ".$id." ORDER BY OrdreRubrique ASC" ;
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		if($row["TypeRubrique"] == 8 ){
			$attr_uo["id"] = $row["IdRubrique"];
			$attr_uo["nom"] = Nettoyer($row["NomRubrique"]);
			$attr_uo["description"] = Nettoyer($row["DescrRubrique"]);
			$attr_uo["statut"] = GetStatut($row["StatutRubrique"]);
			$attr_uo["type"] = "RUBRIQUE";
			$attr_uo["modalite"] = "INDIVIDUEL";
			
			$xml .= "\t\t\t<workspace";
			WriteAttribut($attr_uo);
			$xml .= " >\n";
			GetActivite($attr_uo["id"]);
			$xml .= "\t\t\t</workspace>\n";
		}
		else{
			$attr["id"] = $row["IdRubrique"];
			$attr["nom"] = Nettoyer($row["NomRubrique"]);
			$attr["description"] = Nettoyer($row["DescrRubrique"]);
			$attr["type_digital"] = "digital";
			$attr["type"] = GetTypeRubrique($row["TypeRubrique"]);
			
			$xml .= "\t\t\t<abstract_entity";
			WriteAttribut($attr);




			if ($row["DonneesRubrique"] != ""){
				if(preg_match("/^(.*):\d;?$/",$row["DonneesRubrique"],$match)){
					$attr_concret["datas"] = "formation/f".$formation_id."/rubriques/".Nettoyer($match[1]);
				}
				else{$attr_concret["datas"] = $row["DonneesRubrique"];}

				$xml .= " >\n";
				$xml .= "\t\t\t\t<concrete_entity";
				WriteAttribut($attr_concret);
				$xml .= " />\n";
				$xml .= "\t\t\t</abstract_entity>\n";
			}
			else {
				$xml .= " />\n";
			}
		
		}
	}
}

function GetModule($id){
	global $xml;
	$query = "SELECT * FROM Module WHERE IdForm = ".$id." ORDER BY OrdreMod ASC" ;
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		$attr["id"] = $row["IdMod"];
		$attr["nom"] = Nettoyer($row["NomMod"]);
		$attr["description"] = Nettoyer($row["DescrMod"]);
		$attr["statut"] = GetStatut($row["StatutMod"]);
		$attr["type"] = "MODULE";
		$attr["modalite"] = "INDIVIDUEL";
  
		$xml .= "\t\t<workspace";
		WriteAttribut($attr);
		$xml .= " >\n";
		GetRubrique($attr["id"]);
		$xml .= "\t\t</workspace>\n";
	}
}

function GetFormation($id){
	global $xml;
	global $formation_id;
	$query = "SELECT * FROM Formation WHERE IdForm = ".$id;
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		$attr["id"] = $id;
		$formation_id = $id;
		$attr["nom"] = Nettoyer($row["NomForm"]);
		$attr["description"] = Nettoyer($row["DescrForm"]);
		$attr["statut"] = GetStatut($row["StatutForm"]);
		$attr["type"] = "FORMATION";
		$attr["modalite"] = "INDIVIDUEL";
	}
	
	$xml .= "\t<workspace";
	WriteAttribut($attr);
	$xml .= " >\n";
	GetModule($id);
	$xml .= "\t</workspace>\n";
}
?>
	</body>
</html>