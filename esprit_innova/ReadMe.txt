

Nécessite 2 bases de données :
-"esprit"  : dump de la base de données de la plateforme esprit situé dans le dossier sql/
-"loriane" : base créée avec le fichier sql/create.sql 



Les fonctions permettent :
-php/export_esprit.php : l'export de la base de données de la plateforme Esprit (base de données "esprit") vers des documents 
						 XML défini par la DTD présente dans le dossier dtd/
						 Les documents XML sont stockés dans le dossier datas/.
						 Un document XML correspond à une formation dans Esprit.
	

-php/import_bd.php : l'enregistrement des données contenues dans les XML du dossier datas/ dans la base de données "loriane"
					 Le XML est supprimé après l'enregistrement dans la base.





Les fonctions sont accessibles depuis : 
http://loriane.innovalangues.net/esprit/index.php



