<?php
@session_start();
$erreur=CONNEXION;
$user=$_POST['user'] ?? ($_SESSION['user'] ?? null);
$pass=isset($_POST['pass']) ? sha1($_POST['pass']) : ($_SESSION['pass'] ?? null);
if (!is_null($user)) {
	$requete_identifiants_valides='SELECT 1 FROM users WHERE username=\''.$user.'\' AND password=\''.$pass.'\'';
	$identifiants_valides=count(DM_Core::$d->requete($requete_identifiants_valides)) === 1;
	if ($identifiants_valides) {
		$requete_permission='SELECT 1 FROM users_permissions WHERE username=\''.$user.'\' AND role=\'EdgeCreator\' AND privilege=\'Admin\'';
		$permission_valide=count(DM_Core::$d->requete($requete_permission)) === 1;
		if ($permission_valide) {
			$_SESSION['user']=$user;
			$_SESSION['pass']=$pass;
			$_SESSION['id_user']=DM_Core::$d->user_to_id($user);
            setcookie('user',$user,time()+3600, '','ducksmanager.net');
            setcookie('pass',$pass,time()+3600, '','ducksmanager.net');
            setcookie('is_sha1','true',time()+3600, '','ducksmanager.net');
			$erreur='';
		}
		else {
			$erreur = PERMISSION_NON_ACCORDEE;
		}
	}
	else {
		$erreur = IDENTIFIANTS_INCORRECTS;
	}
}
if (!empty($erreur)) {
	?>
	<html>
		<body>
			<?=$erreur?>
			<form method="post" action="">
				<table border="0">
					<tr><td><?=NOM_UTILISATEUR?> :</td><td><input type="text" name="user" /></td></tr>
					<tr><td><?=MOT_DE_PASSE?> :</td><td><input type="password" name="pass" /></td></tr>
					<tr><td align="center" colspan="2"><input type="submit" value="<?=CONNEXION?>"/></td></tr>
				</table>
			</form>
		</body>
	</html>
	<?php exit(0);
}
?>