 <?php
$link = mysql_connect('localhost','root',''); 
if (!$link) { die('Pb de connexion à la BD: ' . mysql_error()); 
} 
else{
	mysql_query("USE esprit_flodi");
	mysql_query("SET NAMES 'utf8'");
}
?>
<!DOCTYPE html>
 <html>
	<head>
		<title>esprit XMLizer</title>
		<meta charset="utf-8"/>
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
print"</select><input type='submit' value='XMLize this !'></form>";


// LANCEMENT PROCESS SI SUBMIT FORMULAIRE
if(isset($_POST["id"])){
	$last_statut_grpe_actions = 0;
	$formation_id = 0;
	$activite_id = 0;
	$xml = "<?xml version='1.0' encoding='UTF-8'?>\n<!DOCTYPE parcours SYSTEM \"./../DTD_claroline.dtd\">\n<parcours>\n";
	get_infos_formation($_POST["id"]);
	$query = "SELECT * FROM formation WHERE IdForm = ".$_POST["id"] ;
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		$nom = str2url($row["NomForm"]);
	}	
	$xml .= "</parcours>";
	$handle = fopen("datas/".$nom.".xml", "w+");
	fwrite($handle, $xml);
	fclose($handle);
		
	$xml_data = "datas/".$nom.".xml";
	$xsl_data = "affichage_claroline.xsl";

	$xsl = new XSLTProcessor();
	$xsldoc = new DOMDocument();
	$xsldoc->load($xsl_data);
	$xsl->importStyleSheet($xsldoc);

	$xmldoc = new DOMDocument();
	$xmldoc->load($xml_data);
	echo $xsl->transformToXML($xmldoc); 
}

function str2url($str)
   {

      $str = strtr($str,
      "ÀÁÂÃÄÅàáâãäåÇçÒÓÔÕÖØòóôõöøÈÉÊËèéêëÌÍÎÏìíîïÙÚÛÜùúûü¾ÝÿýÑñ",
      "AAAAAAaaaaaaCcOOOOOOooooooEEEEeeeeIIIIiiiiUUUUuuuuYYyyNn");
      
      $str = str_replace('Æ','AE',$str);
      $str = str_replace('æ','ae',$str);
      $str = str_replace('¼','OE',$str);
      $str = str_replace('½','oe',$str);
      
      $str = preg_replace('/[^a-z0-9_\s\'\:\/\[\]-]/','',strtolower($str));
      
      $str = preg_replace('/[\s\'\:\/\[\]-]+/',' ',trim($str));
   
      $res = str_replace(' ','-',$str);
      
      return $res;
   }

function nettoyer($string){
	$string = str_replace("\n","",$string);
	$string = str_replace("\r","",$string);
	$string = strip_tags($string);
	$string = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
	return $string;
}

function get_type_rubrique($id){
	$type[1] = "LIEN_PAGE_HTML";
	$type[2] = "LIEN_DOCUMENT_TELECHARGER";
	$type[3] = "LIEN_SITE_INTERNET";
	$type[4] = "LIEN_CHAT";
	$type[5] = "LIEN_FORUM";
	$type[10] = "LIEN_TEXTE_FORMATTE";
	$type[14] = "LIEN_INTITULE_NON_ACTIVABLE";
	return $type[$id];
}

function get_statut($id){
	$statut[1] = "FERME";
	$statut[2] = "OUVERT";
	$statut[3] = "INVISIBLE";
	$statut[4] = "ARCHIVE";
	$statut[5] = "CLOTURE";
	return $statut[$id];
}

function get_modalite($id){
	$modalite[0] = "INDIVIDUEL";
	$modalite[1] = "INDIVIDUEL";
	$modalite[2] = "COLLECTIF";
	return $modalite[$id];
}


function recup_int($id){
	$int = "";
	$query = "SELECT NomIntitule FROM intitule WHERE IdIntitule = ".$id;
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		$int = $row['NomIntitule'];
	}
	return $int;
}

function recup_type($id){
	$type = "";
	if ($id !=0){
		$query = "SELECT NomTypeSousActiv FROM typesousactiv WHERE IdTypeSousActiv = ".$id;
		$result = mysql_query($query);
		while ($row = mysql_fetch_array($result)) {
			$type = $row['NomTypeSousActiv'];
		}
	}
	else 
		$type = "LIEN_VIDE";
	return $type;
	
}



function write_attr($tab_attr){
	global $xml;
	foreach($tab_attr as $key=>$value){
		$xml .= ' '.$key.'="'.$value.'"';
	}
}

function get_infos_sousactivite($id){
	global $xml;
	global $last_statut_grpe_actions;
	global $formation_id;
	global $activite_id;

	$query = "SELECT * FROM sousactiv WHERE IdActiv = ".$id." ORDER BY OrdreSousActiv ASC" ;
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		$attr["id"] = $row["IdSousActiv"];
		$attr["nom"] = nettoyer($row["NomSousActiv"]);
		$attr["description"] = nettoyer($row["DescrSousActiv"]);
		$attr["date"] = $row["DateDebSousActiv"];
		if($row["StatutSousActiv"] == 6){$attr["statut"] = $last_statut_grpe_actions;}
		else{$attr["statut"] = get_statut($row["StatutSousActiv"]);}
		$attr["type"] = recup_type($row["IdTypeSousActiv"]);
		$attr["createur"] = $row["IdPers"];
		$attr["modalite"] = get_modalite($row["ModaliteSousActiv"]);
		$attr["ordre"] = $row["OrdreSousActiv"];
		$attr["infobulle"] = nettoyer($row["InfoBulleSousActiv"]);
		if(preg_match("/^(;\d?;)*(.*?)( ?;.*;?)*$/",$row["DonneesSousActiv"],$match)){
			if ($attr["type"] == "LIEN_SITE_INTERNET")
				$attr["datas"] = nettoyer($match[2]);
			else 	
				$attr["datas"] = "formation/f".$formation_id."/activ_".$activite_id."/".nettoyer($match[2]);
		}
		else{$attr["datas"] = $row["DonneesSousActiv"];}
		
		
		$xml .= "\t\t\t\t\t<action";
		write_attr($attr);
		$xml .= " />\n";

	}
}

function get_infos_activite($id){
	global $last_statut_grpe_actions;
	global $xml;
	global $activite_id;
	$query = "SELECT * FROM activ WHERE IdRubrique = ".$id." ORDER BY OrdreActiv ASC" ;
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		$attr["id"] = $row["IdActiv"];
		$activite_id = $attr["id"];
		$attr["nom"] = nettoyer($row["NomActiv"]);
		$attr["description"] = nettoyer($row["DescrActiv"]);
		$attr["date"] = $row["DateDebActiv"];
		$attr["statut"] = get_statut($row["StatutActiv"]);
		$last_statut_grpe_actions = $attr["statut"];
		$attr["inscription_auto"] = "";
		$attr["type"] = "GROUPE_ACTIONS";
		$attr["visiteur"] = "1";
		$attr["createur"] = "";
		$attr["intitule"] = "";
		$attr["modalite"] = get_modalite($row["ModaliteActiv"]);
		$attr["ordre"] = $row["OrdreActiv"];
  
		$xml .= "\t\t\t\t<uo";
		write_attr($attr);
		$xml .= " >\n";
		get_infos_sousactivite($attr["id"]);
		$xml .= "\t\t\t\t</uo>\n";
	}
}

function get_infos_rubrique($id){
	global $xml;
	global $formation_id;
	$query = "SELECT * FROM module_rubrique WHERE IdMod = ".$id." ORDER BY OrdreRubrique ASC" ;
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		if($row["TypeRubrique"] == 8 ){
			$attr_uo["id"] = $row["IdRubrique"];
			$attr_uo["nom"] = nettoyer($row["NomRubrique"]);
			$attr_uo["description"] = nettoyer($row["DescrRubrique"]);
			$attr_uo["date"] = "";
			$attr_uo["statut"] = get_statut($row["StatutRubrique"]);
			$attr_uo["inscription_auto"] = "";
			$attr_uo["type"] = "RUBRIQUE";
			$attr_uo["visiteur"] = "1";
			$attr_uo["createur"] = $row["IdPers"];
			$attr_uo["intitule"] = recup_int($row["IdIntitule"]);
			$attr_uo["modalite"] = "INDIVIDUEL";
			$attr_uo["ordre"] = $row["OrdreRubrique"];
			
			$xml .= "\t\t\t<uo";
			write_attr($attr_uo);
			$xml .= " >\n";
			get_infos_activite($attr_uo["id"]);
			$xml .= "\t\t\t</uo>\n";
		}
		else{
			$attr["id"] = $row["IdRubrique"];
			$attr["nom"] = nettoyer($row["NomRubrique"]);
			$attr["description"] = nettoyer($row["DescrRubrique"]);
			$attr["date"] = "";
			$attr["statut"] = get_statut($row["StatutRubrique"]);
			$attr["type"] = get_type_rubrique($row["TypeRubrique"]);
			$attr["createur"] = $row["IdPers"];
			$attr["ordre"] = $row["OrdreRubrique"];
			$attr["infobulle"] = "";
			if(preg_match("/^(.*):\d;?$/",$row["DonneesRubrique"],$match)){
				$attr["datas"] = "formation/f".$formation_id."/rubriques/".nettoyer($match[1]);
			}
			else{$attr["datas"] = $row["DonneesRubrique"];}
			$xml .= "\t\t\t<action";
			write_attr($attr);
			$xml .= " />\n";
		}
	}
}

function get_infos_module($id){
	global $xml;
	$query = "SELECT * FROM module WHERE IdForm = ".$id." ORDER BY OrdreMod ASC" ;
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		$attr["id"] = $row["IdMod"];
		$attr["nom"] = nettoyer($row["NomMod"]);
		$attr["description"] = nettoyer($row["DescrMod"]);
		$attr["date"] = $row["DateDebMod"];
		$attr["statut"] = get_statut($row["StatutMod"]);
		$attr["inscription_auto"] = "";
		$attr["type"] = "MODULE";
		$attr["visiteur"] = "1";
		$attr["createur"] = $row["IdPers"];
		$attr["intitule"] = recup_int($row["IdIntitule"]);
		$attr["modalite"] = "INDIVIDUEL";
		$attr["ordre"] = $row["OrdreMod"];
  
		$xml .= "\t\t<uo";
		write_attr($attr);
		$xml .= " >\n";
		get_infos_rubrique($attr["id"]);
		$xml .= "\t\t</uo>\n";
	}
}

function get_infos_formation($id){
	global $xml;
	global $formation_id;
	$query = "SELECT * FROM formation WHERE IdForm = ".$id;
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		$attr["id"] = $id;
		$formation_id = $id;
		$attr["nom"] = nettoyer($row["NomForm"]);
		$attr["description"] = nettoyer($row["DescrForm"]);
		$attr["date"] = $row["DateDebForm"];
		$attr["statut"] = get_statut($row["StatutForm"]);
		$attr["inscription_auto"] = $row["InscrAutoModules"];
		//$attr["type"] = $row["TypeForm"];
		$attr["type"] = "FORMATION";
		$attr["visiteur"] = $row["VisiteurAutoriser"];
		$attr["createur"] = $row["IdPers"];
		$attr["intitule"] = "";
		$attr["modalite"] = "INDIVIDUEL";
		$attr["ordre"] = "";
	}
	
	$xml .= "\t<uo";
	write_attr($attr);
	$xml .= " >\n";
	get_infos_module($id);
	$xml .= "\t</uo>\n";
}
?>
	</body>
</html>