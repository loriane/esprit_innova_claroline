<?xml version="1.0" encoding="ISO-8859-1"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output 
  method="html"
  encoding="iso"
  doctype-public="-//W3C//DTD HTML 4.01//EN"
  doctype-system="http://www.w3.org/TR/html4/strict.dtd"
  indent="yes" />
<xsl:template match="parcours">
    <h1 > Resultats :</h1>
	<hr/>
	<p><xsl:apply-templates select="user" /></p>
	<hr/>
	<p><xsl:apply-templates select="workspace" /></p>


</xsl:template>
<xsl:template match="user">
     <h2> 	User : </h2> <ul><li>Nom =  
    <xsl:value-of select="@nom" />
    </li><li> <xsl:text> Prenom =   </xsl:text>
    <xsl:value-of select="@prenom" />
	</li><li> <xsl:text>  Mail =  </xsl:text>
    <xsl:value-of select="@mail" />
	</li><li> <xsl:text> Date de naissance =  </xsl:text>
    <xsl:value-of select="@date_naissance" /> 	
      </li></ul> 
	  
</xsl:template>

<xsl:template match="workspace">
     <h3> 	Workspace : </h3> 
	 <ul><li>
	 <xsl:apply-templates select="abstract_entity" />
	</li><li>ID =  
    <xsl:value-of select="@id" />
    </li><li> <xsl:text> Nom =   </xsl:text>
    <xsl:value-of select="@nom" />
	</li><li> <xsl:text>  Description =  </xsl:text>
    <xsl:value-of select="@description" />
	</li><li> <xsl:text> Statut =  </xsl:text>
    <xsl:value-of select="@statut" />
    </li><li> <xsl:text> Type =   </xsl:text>
    <xsl:value-of select="@type" />
	</li><li> <xsl:text> Modalite =  </xsl:text>
 	<xsl:value-of select="@modalite" />
    </li><li>
	 <xsl:apply-templates select="workspace" />
	</li></ul> 
</xsl:template>


<xsl:template match="abstract_entity">
     <p> Abstract_entity : </p> <ul><li>ID =  
    <xsl:value-of select="@id" />
    </li><li> <xsl:text> Nom =   </xsl:text>
    <xsl:value-of select="@nom" />
	</li><li> <xsl:text>  Description =  </xsl:text>
    <xsl:value-of select="@description" />
	</li><li> <xsl:text> Type Digital =  </xsl:text>
    <xsl:value-of select="@type_digital" />
    </li><li> <xsl:text> Type =   </xsl:text>
    <xsl:value-of select="@type" />
	</li></ul>	  
	<p> <xsl:apply-templates select="concrete_entity" />
	</p> 
</xsl:template>

<xsl:template match="concrete_entity">
    <p> Concrete_entity : </p> 
	<ul><li>Datas =  
    <xsl:value-of select="@datas" />
    </li></ul> 
</xsl:template>
</xsl:stylesheet>


