<?php

	define( 'REDAKAICARDREF_VERSION', '1.0.0' );
	define( 'REDAKAICARDREF_PLUGIN_NAME', 'redakaicardref' );
	define( 'REDAKAICARDREF_TABLE_NAME', REDAKAICARDREF_PLUGIN_NAME . '_card_names' );
	define( 'REDAKAICARDREF_DIRECTORY', dirname(__FILE__) . '/' );
	define( 'REDAKAICARDREF_WEB_DIRECTORY', dirname($_SERVER['PHP_SELF']) . '/' );
	define( 'REDAKAICARDREF_TINYMCE_PLUGIN_DIRECTORY', 'wp-includes/js/tinymce/plugins/' . REDAKAICARDREF_PLUGIN_NAME . '/' );
	define( 'REDAKAICARDREF_OPTION_NAME', REDAKAICARDREF_PLUGIN_NAME . '_version' );
	define( 'REDAKAICARDREF_NONCE_NAME', 'redakaicardref_nonce' );
	define( 'REDAKAICARDREF_BASE_URL', '<a class="redakaicardref_rollover" href="http://store.tcgplayer.com/Products.aspx?GameName=Redakai&name=' );
	define( 'REDAKAICARDREF_GETTER_URL', '/redakai-card-links/getter.php?n=' );
	define( 'REDAKAICARDREF_SQL_FILE', 'redakai_cards.sql');

?>