<!DOCTYPE html>
<html>
<body>
<?php if ( isset($_REQUEST['title']) ) : ?>
	<h1><?php echo $_REQUEST['title']; ?></h1>
<?php else : ?>
	(no title)
<?php endif; ?>
<hr>
<?php if ( isset($_REQUEST['content']) ) : ?>
	<p><?php echo $_REQUEST['content']; ?></p>
<?php else : ?>
	(no content)
<?php endif; ?>
<br />
<u>METHOD:<?php echo $_SERVER['REQUEST_METHOD']; ?></u>
</body>
</html>