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
	<p><xsl:apply-templates select="uo" /></p>

</xsl:template>

<xsl:template match="uo">
     <h3> 	Unité d'organisation : </h3> 
	 <ul><li>
	 <xsl:apply-templates select="action" />
	</li><li>ID =  
    <xsl:value-of select="@id" />
    </li><li> <xsl:text> Nom =   </xsl:text>
    <xsl:value-of select="@nom" />
	</li><li> <xsl:text> Intitule =   </xsl:text>
    <xsl:value-of select="@intitule" />
	</li><li> <xsl:text>  Description =  </xsl:text>
    <xsl:value-of select="@description" />
	</li><li> <xsl:text> Statut =  </xsl:text>
    <xsl:value-of select="@statut" />
    </li><li> <xsl:text> Type =   </xsl:text>
    <xsl:value-of select="@type" />
	</li><li> <xsl:text> Modalite =  </xsl:text>
 	<xsl:value-of select="@modalite" />
    </li><li>
	 <xsl:apply-templates select="uo" />
	</li></ul> 
</xsl:template>


<xsl:template match="action">
     <p> Action : </p> <ul><li>ID =  
    <xsl:value-of select="@id" />
    </li><li> <xsl:text> Nom =   </xsl:text>
    <xsl:value-of select="@nom" />
	</li><li> <xsl:text>  Description =  </xsl:text>
    <xsl:value-of select="@description" />
	</li><li> <xsl:text> Statut =  </xsl:text>
    <xsl:value-of select="@statut" />
    </li><li> <xsl:text> Type =   </xsl:text>
    <xsl:value-of select="@type" />
	</li><li><xsl:text> Datas = </xsl:text> 
    <xsl:value-of select="@datas" />
    </li><li> <xsl:text> Modalite =   </xsl:text>
    <xsl:value-of select="@modalite" /></li></ul>	  
</xsl:template>

</xsl:stylesheet>
