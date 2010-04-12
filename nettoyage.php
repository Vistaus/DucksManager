<?php

require_once('Database.class.php');
$d=new Database();
if (!$d) {
	echo 'Probl&egrave;me avec la base de donn&eacute;es !';
	exit(-1);
}

$requete='SELECT Num�ro FROM numeros WHERE Num�ro LIKE \'0%\'';
$resultat=$d->requete_select($requete);
foreach($resultat as $numero) {
	$num=$numero['Num�ro'];
	echo $num;
	if ($num!='0') {
		$num_change=preg_replace('#[0]+([^0]+)#is','$1',$num);
		$requete_update='UPDATE numeros SET Num�ro='.$num_change.' WHERE Num�ro LIKE \''.$num.'\'';
		echo $requete_update;
		$d->requete($requete_update);
	} 
}