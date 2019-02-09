<?php
@session_start();
if (isset($_GET['lang'])) {
	$_SESSION['lang']=$_GET['lang'];
}
include_once 'locales/lang.php';
require_once 'Format_liste.php';
class debug extends Format_liste {
	static $titre='Liste de débug';
	function __construct() {
		$this->les_plus= [DEBUG_PLUS_1];
		$this->les_moins= [DEBUG_MOINS_1,DEBUG_MOINS_2,DEBUG_MOINS_3,DEBUG_MOINS_4];
		$this->description=DEBUG_DESCRIPTION;
	}

	function afficher($liste) {
		foreach($liste as $pays=>$numeros_pays) {
			echo '<u>'.$pays.':</u><br /><pre>';print_r($numeros_pays);echo '</pre>';
		}
	}
}