

N�cessite 2 bases de donn�es :
-"esprit"  : dump de la base de donn�es de la plateforme esprit situ� dans le dossier sql/
-"loriane" : base cr��e avec le fichier sql/create.sql 



Les fonctions permettent :
-php/export_esprit.php : l'export de la base de donn�es de la plateforme Esprit (base de donn�es "esprit") vers des documents 
						 XML d�fini par la DTD pr�sente dans le dossier dtd/
						 Les documents XML sont stock�s dans le dossier datas/.
						 Un document XML correspond � une formation dans Esprit.
	

-php/import_bd.php : l'enregistrement des donn�es contenues dans les XML du dossier datas/ dans la base de donn�es "loriane"
					 Le XML est supprim� apr�s l'enregistrement dans la base.





Les fonctions sont accessibles depuis : 
http://loriane.innovalangues.net/esprit/index.php



