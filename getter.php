<?php

	if( !isset($_GET['n']) )
		return;
	
	if( get_magic_quotes_gpc() )
		$_GET['n'] = stripslashes( $_GET['n'] );
		
	echo file_get_contents( "http://www.tcgplayer.com/db/redakai-hover.asp?CN=" . urlencode( $_GET['n'] ) );

?>