<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:template match="/">
<html>
<body>
	<ul>
		<xsl:for-each select="recordset/row">
			<li><xsl:value-of select="id" />|<xsl:value-of select="name" />|<xsl:value-of select="remark" /></li>
		</xsl:for-each>
	</ul>
</body>
</html>
</xsl:template>
</xsl:stylesheet>