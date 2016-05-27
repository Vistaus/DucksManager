<?php
require_once('DucksManager_Core.class.php');
require_once('Affichage.class.php');
require_once('Inducks.class.php');
class Liste {
	var $contenu;
	var $nom_fichier;
	var $collection= [];
	var $very_max_centaines=1;
	var $database;
	static $types_listes= [];

	static function set_types_listes() {
		$rep = "Listes/";
		$dir = opendir($rep);
		$prefixe='Liste.';
		$suffixe='.class.php';
		while ($f = readdir($dir)) {
			if (strpos($f,'Debug')!==false)
				continue;
			if(is_file($rep.$f)) {
				if (startsWith($f,$prefixe) && endsWith($f,$suffixe)) {
					$nom=substr($f,strlen($prefixe),strlen($f)-strlen($suffixe)-strlen($prefixe));
					
					include_once('Listes/Liste.'.$nom.'.class.php');
					$a=new ReflectionProperty($nom, 'titre');
					Liste::$types_listes[$nom]=$a->getValue();
				}
			}
		}
		return Liste::$types_listes;
	}
	
	function __construct($texte=false) {
		if (!$texte)
					return;
		$this->texte=$texte;
		$this->lire();
	}

	function ajouter($pays,$magazine,$numero) {
		if (!array_key_exists($pays, $this->collection))
			$this->collection[$pays]= [];
		if (!array_key_exists($magazine,$this->collection[$pays]))
			$this->collection[$pays][$magazine]= [];
		if (in_array($numero, $this->collection[$pays][$magazine]))
			return;
		$this->collection[$pays][$magazine][]=$numero;
	}

	function ListeExemple() {
		$numeros_mp= [[2,'Excellent',false,-1], [273,'Bon',false,-1], [4,'Excellent',false,-1], [92,'Excellent',false,-1]];
		$numeros_mad= [[6,'Indefini',false,-1], [16,'Bon',false,-1]];
		$this->collection= ['fr'=>(['MP'=>$numeros_mp]), 'us'=> ['MAD'=>$numeros_mad]];
	}

	function fusionnerAvec($liste_autre) {
		/*echo 'Fusion de : ';
		echo '<pre>';print_r($this->collection);echo '</pre>';
		echo 'avec : ';
		echo '<pre>';print_r($liste_autre->collection);echo '</pre>';*/
		foreach($liste_autre->collection as $pays=>$numeros_pays) {
			foreach($numeros_pays as $magazine=>$numeros) {
				foreach($numeros as $numero_etat_av_date) {
									$numero=$numero_etat_av_date[0];
					$etat=$numero_etat_av_date[1];
					$av=$numero_etat_av_date[2];
					$date=$numero_etat_av_date[3];
					if (!array_key_exists($pays,$this->collection)) {
						$arr_temp= [$magazine=> [0=> [$numero,$etat,$av,$date]]];
						$this->collection[$pays]=$arr_temp;
					}
					else {
						if (!array_key_exists($magazine,$this->collection[$pays])) {
							$this->collection[$pays][$magazine]= [0=> [$numero,$etat,$av,$date]];
						}
						else
							if (!array_push($this->collection[$pays][$magazine], [$numero,$etat,$av,$date]))
								echo '<b>'.$magazine.$numero.'</b>';
					}
				}
			}
		}
	}


	function sous_liste($pays,$magazine=false) {
		$nouvelle_liste=new Liste();
		if (!$magazine)
					$nouvelle_liste->collection= [$pays=> [$this->collection[$pays]]];
		else
					$nouvelle_liste->collection= [$pays=> [$magazine=>$this->collection[$pays][$magazine]]];
		return $nouvelle_liste;
	}

	function liste_pays() {
		$tab= [];
		$liste=Inducks::get_pays();
		foreach(array_keys($this->collection) as $pays) {
			$pays_trouve=array_key_exists($pays,$liste);
			$tab[$pays_trouve ? $liste[$pays] : $pays]= [$pays,$pays_trouve ? $liste[$pays] : $pays];
		}
		return $tab;
	}
	
	function liste_magazines($pays_magazine_supplementaire=null,$tri_noms_complets=true) {
		$publication_codes= [];
		foreach($this->collection as $pays=>$numeros_pays) {
			foreach(array_keys($numeros_pays) as $magazine) {
				$publication_codes[]=$pays.'/'.$magazine;
			}
		}
		if (!is_null($pays_magazine_supplementaire)) {
			$publication_codes[]=$pays_magazine_supplementaire;
		}
		list($noms_pays,$noms_magazines) = Inducks::get_noms_complets($publication_codes);
		foreach(array_keys($noms_pays) as $nom_abrege) {
			$noms_pays[$noms_pays[$nom_abrege]]= [$nom_abrege, $noms_pays[$nom_abrege]];
			unset($noms_pays[$nom_abrege]);
		}
		foreach(array_keys($noms_magazines) as $nom_abrege) {
			$noms_magazines[$nom_abrege]= [$nom_abrege, $noms_magazines[$nom_abrege]];
		}
		
		if ($tri_noms_complets) {
			uasort($noms_magazines, function($a,$b) {
				if ($a[1] == $b[1]) {
					return 0;
				}
				return ($a[1] < $b[1]) ? -1 : 1; 
			});
		}
		return [$noms_pays,$noms_magazines];
	}

	function statistiques($onglet) {
		$id_user=DM_Core::$d->user_to_id($_SESSION['user']);

		$counts= [];
		foreach($this->collection as $pays=>$numeros_pays) {
			$counts[$pays]= [];
			foreach($numeros_pays as $magazine=>$numeros) {
				$counts[$pays][$magazine]=count($numeros);
			}
		}
		$onglets= [PUBLICATIONS=> ['magazines',PUBLICATIONS_COURT],
							   POSSESSIONS=> ['possessions',POSSESSIONS_COURT],
							   ETATS_NUMEROS=> ['etats',ETATS_NUMEROS_COURT],
							   ACHATS=> ['achats',ACHATS_COURT],
							   AUTEURS=> ['auteurs',AUTEURS_COURT]];
		Affichage::onglets($onglet,$onglets,'onglet','?action=stats');

		if (count($counts)==0) {
			echo AUCUN_NUMERO_POSSEDE_1.'<a href="?action=gerer&onglet=ajout_suppr">'.ICI.'</a> '
							.AUCUN_NUMERO_POSSEDE_2;
			return;
		}
		switch($onglet) {
			case 'magazines':
				?>
				<div id="canvas-holder" style=" width:500px; height:500px">
					<canvas id="graph_publications"></canvas>
				</div><?php
			break;
			case 'possessions':
				$types = ['abs' => AFFICHER_VALEURS_REELLES, 'cpt'=> AFFICHER_POURCENTAGES]; ?>

				<span id="chargement_classement_termine"><?=CHARGEMENT?>...</span><br />
				<div id="barre_pct_classement">
					<div id="pct_classement">&nbsp;</div>
				</div>
				<span id="prefixe_message_classement">
					<?=CALCUL?>
				</span>
				<span id="message_classement"></span>
				<div id="fin_classement" class="hidden">
					<?php foreach($types as $type=>$label) {
					?><a class="graph_type noborder <?=$type==='abs' ? 'bold' : ''?>" href="javascript:void(0)"
						 onclick="toggleGraphs('possessions')">
						<?=$label?>
					  </a><?php
					}?>
				</div>
				<br />
				<script type="text/javascript">
					initProgressBar('classement','Stats.class.php', 'graph=true&possessions=true', afficher_histogramme_possessions);
				</script>
				<div id="resultat_classement"></div>

				<div id="canvas-holder" class="hidden">
					<?php foreach($types as $type=>$label) {
						?><canvas class="graph_possessions <?=$type?> <?=$type==='cpt' ? 'hidden' : ''?>"
								  width="100%" height="500px"></canvas><?php
					}?>
				</div>
				<?php
				break;
			case 'etats':
				?>
				<div id="canvas-holder" style="width:500px;">
					<canvas id="graph_conditions"></canvas>
				</div><?php
			break;

			case 'achats':
				$types = ['nouv' => AFFICHER_NOUVELLES_ACQUISITIONS, 'tot'=> AFFICHER_POSSESSIONS_TOTALES]; ?>

				<div id="message_achats"><?=CHARGEMENT?>...</div>
				
				<div id="fin_achats" class="hidden">
					<?php foreach($types as $type=>$label) {
						?><a class="graph_type noborder <?=$type==='nouv' ? 'bold' : ''?>" href="javascript:void(0)"
							 onclick="toggleGraphs('achats')">
						<?=$label?>
						</a><?php
					}?>
				</div>
				
				<br />

				<div id="canvas-holder" class="hidden" style="background: whitesmoke">
					<?php foreach($types as $type=>$label) {
						?><canvas class="graph_achats <?=$type?> <?=$type==='tot' ? 'hidden' : ''?>"
								  width="100%" height="500px"></canvas><?php
					}?>
				</div>
				<?php
			break;
			case 'auteurs':
				$requete_auteurs_surveilles='SELECT NomAuteur, NomAuteurAbrege FROM auteurs_pseudos WHERE ID_User='.$id_user.' AND DateStat = \'0000-00-00\'';
				$resultat_auteurs_surveilles=DM_Core::$d->requete_select($requete_auteurs_surveilles);
				if (count($resultat_auteurs_surveilles)!=0) {
					$requete_calcul_effectue='SELECT Count(NomAuteurAbrege) AS cpt FROM auteurs_pseudos WHERE ID_User='.$id_user;
					$resultat_calcul_effectue=DM_Core::$d->requete_select($requete_calcul_effectue);
					if ($resultat_calcul_effectue[0]['cpt']==0) {
						echo AUCUN_AUTEUR_SURVEILLE;
					}
					else {
						$types = ['abs' => AFFICHER_VALEURS_REELLES, 'pct'=> AFFICHER_POURCENTAGES]; ?>

						<div id="aucun_resultat_stats_auteur" class="hidden">
							<?=CALCULS_PAS_ENCORE_FAITS?>
						</div>
						<div id="chargement_stats_auteur">
							<?=CHARGEMENT?>...
						</div>

						<div id="canvas-holder" class="hidden">
						<?php foreach($types as $type=>$label) {
							?><canvas class="graph_auteurs <?=$type?> <?=$type==='pct' ? 'hidden' : ''?>"
									  width="100%" height="500px"></canvas><?php
						}?>
						</div>
						<br />
						<div id="fin_stats_auteur" class="hidden">
						<?php foreach($types as $type=>$label) {
							?><a class="graph_type noborder <?=$type==='abs' ? 'bold' : ''?>" href="javascript:void(0)"
								 onclick="toggleGraphs('auteurs')">
							<?=$label?>
							</a><?php
						}?>
						</div>
						<?php
					}
				}
				?><br /><br />
				<?=STATISTIQUES_QUOTIDIENNES?>
				<br /><br />
				<hr /><?php
				if (isset($_POST['auteur_nom'])) {
					DM_Core::$d->ajouter_auteur($_POST['auteur_id'],$_POST['auteur_nom']);
				}
				?>
				<br /><br />
				<?=AUTEURS_FAVORIS_INTRO_1?>
				<a target="_blank" href="/?action=agrandir&onglet=auteurs_favoris"><?=AUTEURS_FAVORIS_INTRO_2?></a>
				<br />
				<?=STATISTIQUES_AUTEURS_INTRO?>
				<br /><br />
				<form method="post" action="?action=stats&amp;onglet=auteurs">
					<input type="text" name="auteur_cherche" id="auteur_cherche" value="" size="40"/>
					<div class="update" id="liste_auteurs"></div>
					<input type="hidden" id="auteur_nom" name="auteur_nom" />
					<input type="hidden" id="auteur_id" name="auteur_id" />
					<img alt="Loading" id="loading_auteurs" src="loading.gif" style="display:none" />
					<input type="submit" value="Ajouter" />
				</form>
				<div id="auteurs_ajoutes">
					<br /><br />
					<?=LISTE_AUTEURS_INTRO?>
					<?php
					$requete_auteurs_surveilles="
						SELECT NomAuteur, NomAuteurAbrege, Notation
						FROM auteurs_pseudos
						WHERE ID_User=$id_user AND DateStat = '0000-00-00'";
					DM_Core::$d->afficher_liste_auteurs_surveilles(DM_Core::$d->requete_select($requete_auteurs_surveilles));
					?>
				</div><?php
				break;
			break;
		}
	}

	function add_to_database($id_user) {
			$cpt=0;
			foreach($this->collection as $pays=>$numeros_pays) {
				if ($pays=='country')
					continue;
				foreach($numeros_pays as $magazine=>$numeros) {
					foreach($numeros as $numero) {
						$requete='INSERT INTO numeros (Pays, Magazine, Numero, Etat, ID_Acquisition, AV, ID_Utilisateur) '
								.'VALUES (\''.$pays.'\',\''.$magazine.'\',\''.$numero.'\',\'indefini\',-1,0,'.$id_user.')';
						DM_Core::$d->requete($requete);
						$cpt++;
					}
				}
			}
			return $cpt;
	}

		function remove_from_database($id_user) {
			$cpt=0;
			foreach($this->collection as $pays=>$numeros_pays) {
				if ($pays=='country')
					continue;
				foreach($numeros_pays as $magazine=>$numeros) {
					foreach($numeros as $numero) {
						$num_final=is_array($numero) && array_key_exists(0,$numero) ? $numero[0] : $numero;
						$requete='DELETE FROM numeros WHERE (ID_Utilisateur ='.$id_user.' AND PAYS = \''.$pays.'\' AND Magazine = \''.$magazine.'\' AND Numero = \''.$num_final.'\')';
						DM_Core::$d->requete($requete);
						$cpt++;
					}
				}
			}
			return $cpt;
	}

	function synchro_to_database($ajouter_numeros=true,$supprimer_numeros=false) {
			$id_user=DM_Core::$d->user_to_id($_SESSION['user']);
			$l_ducksmanager=DM_Core::$d->toList($id_user);
			$l_ducksmanager->compareWith($this,$ajouter_numeros,$supprimer_numeros);
	}

	function lire() {
		$id_fichier_tmp=rand(0,10000);
		$nom_fichier_tmp='rawdata_'.$id_fichier_tmp.'.txt';
		Util::ecrire_dans_fichier('_tmp/'.$nom_fichier_tmp,$this->texte);
		
		$serveur='serveur_virtuel';
		$reponse=Util::get_page(ServeurDb::getRemoteUrl('parse_coa_rawdata.php') .'?rawData_file='.$id_fichier_tmp.'&mdp='.sha1(ServeurDb::getProfil($serveur)->password));
		$this->collection=unserialize($reponse);
		@unlink('_tmp/'.$nom_fichier_tmp);
	}

	function compareWith($other_list,$ajouter_numeros=false,$supprimer_numeros=false) {
		$id_user=DM_Core::$d->user_to_id($_SESSION['user']);
		$numeros_a_ajouter=$numeros_a_supprimer=$numeros_communs=0;

		$liste_a_supprimer=new Liste();

		foreach($this->collection as $pays=>$numeros_pays) {
			foreach($numeros_pays as $magazine=>$numeros) {
				sort($numeros);
				foreach($numeros as $numero) {
					if (array_key_exists($pays, $other_list->collection)
					 && array_key_exists($magazine, $other_list->collection[$pays])
					 && in_array($numero[0], $other_list->collection[$pays][$magazine]))
						$numeros_communs++;
					else {
						$liste_a_supprimer->ajouter($pays, $magazine, $numero[0]);
						$numeros_a_supprimer++;
					 }
				}
			}
		}
		if ($supprimer_numeros)
			$liste_a_supprimer->remove_from_database ($id_user);
		$liste_a_ajouter=new Liste();
		foreach($other_list->collection as $pays=>$numeros_pays) {
			foreach($numeros_pays as $magazine=>$numeros) {
				if ($pays=='country')
					continue;
				foreach($numeros as $numero) {
					$trouve=false;
					if (array_key_exists($pays,$this->collection)
					&& array_key_exists($magazine,$this->collection[$pays])) {
						$numeros_possedes_magazine=count($this->collection[$pays][$magazine]);
						for ($i=0;$i<$numeros_possedes_magazine;$i++)
							if ($numero==$this->collection[$pays][$magazine][$i][0])
								$trouve=true;
					}
					if (!$trouve) {
						$liste_a_ajouter->ajouter($pays, $magazine, $numero);
						$numeros_a_ajouter++;
					}
				}
			}
		}
		if ($ajouter_numeros)
			$liste_a_ajouter->add_to_database ($id_user);
		if (!$ajouter_numeros && !$supprimer_numeros) {
			?>
			<ul>
				<li style="margin-top:10px"><?=$numeros_a_ajouter?> <?=NUMEROS_A_AJOUTER?> :
					<?php $liste_a_ajouter->afficher('Classique'); ?>
				</li>
				<li style="margin-top:10px"><?=$numeros_a_supprimer?> <?=NUMEROS_A_SUPPRIMER?> :
					<?php $liste_a_supprimer->afficher('Classique'); ?>
				</li>
				<li style="margin-top:10px"><?=$numeros_communs?> <?=NUMEROS_COMMUNS?>
				</li>
			</ul>
			<?php
			return [$numeros_a_ajouter, $numeros_a_supprimer];
		}
		else {
			echo OPERATIONS_EXECUTEES.' <br />';
		}
	}

	function afficher($type,$parametres=null) {
		$type=strtolower($type);
		if (@require_once('Listes/Liste.'.$type.'.class.php')) {
			$o=new $type();
			if (!is_null($parametres)) {
				foreach($parametres as $nom_parametre=>$parametre)
					$o->parametres->$nom_parametre=$parametre;
			}
			$o->afficher($this->collection);
		}
		else
			echo ERREUR_TYPE_LISTE_INVALIDE;
	}

	function est_possede($pays,$magazine,$numero) {
		if (array_key_exists($pays,$this->collection)) {
			if (array_key_exists($magazine,$this->collection[$pays])) {
				foreach($this->collection[$pays][$magazine] as $numero_liste) {
					if (nettoyer_numero($numero_liste[0])==$numero) {
						return true;
					}
				}
			}
		}
		return false;
	}
	function infos_numero($pays,$magazine,$numero) {
		if (array_key_exists($pays,$this->collection)) {
			if (array_key_exists($magazine,$this->collection[$pays])) {
				foreach($this->collection[$pays][$magazine] as $numero_liste) {
					if ($numero_liste[0]==$numero) {
						return [$numero_liste[0],
									 array_key_exists($numero_liste[1],Database::$etats) ? $numero_liste[1] : 'indefini',
									 $numero_liste[2],
									 $numero_liste[3]];
					}
				}
			}
		}
		return null;
	}
	
	function nettoyer_collection() {
		$collection2= [];
		foreach($this->collection as $pays=>$liste_magazines) {
			$collection2[$pays]= [];
			foreach($liste_magazines as $magazine=>$liste_numeros) {
				$collection2[$pays][$magazine]= [];
				foreach($liste_numeros as $numero_liste) {
					$collection2[$pays][$magazine][]= [nettoyer_numero($numero_liste[0]),$numero_liste[1],$numero_liste[2],$numero_liste[3]];
				}
			}	
		}
		$this->collection=$collection2;
	}
		
		function get_liste_auto($pays,$magazine) {
			$nb_non_numeriques=0;
			self::set_types_listes();
			foreach($this->collection[$pays][$magazine] as $numero_et_etat) {
				$numero = $numero_et_etat[0];
				if (!(collectable::est_listable($numero)))
					$nb_non_numeriques++;
			}
			return 'dmspiral';
		}

		static function import($liste_texte) {
			$l=new Liste($liste_texte);
			if ($l->collection == []) {
				echo AUCUN_NUMERO_INDUCKS;
				return [false,0,0];
			}
			else {
				if (isset($_SESSION['user'])) {
					$id_user = DM_Core::$d->user_to_id($_SESSION['user']);
					$l_ducksmanager = DM_Core::$d->toList($id_user);
					list($ajouts,$suppressions) = $l_ducksmanager->compareWith($l);
					if ($ajouts==0 && $suppressions==0) {
						echo LISTES_IDENTIQUES;
						return [true,0,0];
					}
				}
				else {
					echo RESULTAT_NUMEROS_INDUCKS;
					$l->afficher('Classique');
				}
				return [true,$ajouts, $suppressions];
			}
		}
		
		static function init_parametres_boite($pays,$magazine,$type_liste,$position_liste) {
			@session_start();
			$id_user=DM_Core::$d->user_to_id($_SESSION['user']);
			if (file_exists('Listes/Liste.'.$type_liste.'.class.php'))
				include_once('Listes/Liste.'.$type_liste.'.class.php');

			$o_tmp=new $type_liste;
			if ($o_tmp->getListeParametresModifiables() == new stdClass()) {
				$requete_ajouter_boite='INSERT INTO parametres_listes(`ID_Utilisateur`,`Pays`,`Magazine`,`Type_Liste`,`Position_Liste`,`Parametre`,`Valeur`) VALUES '
									  .'('.$id_user.',\''.$pays.'\',\''.$magazine.'\',\''.$type_liste.'\','.$position_liste.',NULL,NULL)';
				DM_Core::$d->requete($requete_ajouter_boite);
			}
			else {
				foreach($o_tmp->getListeParametresModifiables() as $nom_parametre=>$parametre) {
					$requete_ajouter_boite='INSERT INTO parametres_listes(`ID_Utilisateur`,`Pays`,`Magazine`,`Type_Liste`,`Position_Liste`,`Parametre`,`Valeur`) VALUES '
										  .'('.$id_user.',\''.$pays.'\',\''.$magazine.'\',\''.$type_liste.'\','.$position_liste.',\''.$nom_parametre.'\',\''.$parametre->valeur_defaut.'\')';
					DM_Core::$d->requete($requete_ajouter_boite);
				}
			}
		}
	
		/**
		 * @param $parametres
		 * @param $id_user
		 * @param $pays
		 * @param $magazine
		 */
		static function maj_parametres($parametres, $id_user, $pays, $magazine)
		{
			foreach ($parametres as $parametre => $valeur) {
				$requete_modifier_parametre = 'UPDATE parametres_listes SET Valeur=\'' . $valeur . '\' '
					. 'WHERE ID_Utilisateur=' . $id_user . ' AND Pays = \'' . $pays . '\' AND Magazine = \'' . $magazine . '\' AND Parametre = \'' . $parametre . '\'';
				DM_Core::$d->requete($requete_modifier_parametre);
			}
		}
}
if (isset($_POST['parametres']))
	$_POST['parametres'] = str_replace('\"', '"', $_POST['parametres']);

if (isset($_POST['types_listes'])) {
	header("X-JSON: " . json_encode(Liste::set_types_listes()));
}
elseif(isset($_POST['sous_liste'])) {
	@session_start();
	$id_user=DM_Core::$d->user_to_id($_SESSION['user']);
	$l=DM_Core::$d->toList($id_user);
	if (isset($_POST['pays'])) {
		$pays=$_POST['pays'];
		$magazine=$_POST['magazine'];
		$type_liste=$_POST['type_liste'];
		$sous_liste=$l->sous_liste($pays,$magazine);
	}
	else
		$sous_liste=new Liste();
	if (isset($_POST['parametres']))
		$parametres=json_decode($_POST['parametres']);
	else
		$parametres=new stdClass();
	if (isset($_POST['type_liste'])) {
		if (isset($_POST['fusions'])) {
			$fusions=explode('-',$_POST['fusions']);
			foreach($fusions as $fusion) {
				$pays_et_magazine_fusion=explode('_',$fusion);
				$requete_get_type_liste='SELECT Type_Liste FROM parametres_listes WHERE Pays = \''.$pays_et_magazine_fusion[0].'\' AND Magazine = \''.$pays_et_magazine_fusion[1].'\' AND ID_Utilisateur='.$id_user;
				$resultat_get_type_liste=DM_Core::$d->requete_select($requete_get_type_liste);
				if (count($resultat_get_type_liste) > 0) {
					$type_liste=$resultat_get_type_liste[0]['Type_Liste'];

					if (isset($_POST['type_liste']) && $_POST['type_liste'] != $type_liste) {
						if (isset($_POST['confirmation_remplacement'])) {
							$requete_effacer_parametres_courants='DELETE FROM parametres_listes WHERE Pays = \''.$pays_et_magazine_fusion[0].'\' AND Magazine = \''.$pays_et_magazine_fusion[1].'\' AND ID_Utilisateur='.$id_user;
							DM_Core::$d->requete($requete_effacer_parametres_courants);
						}
						else {	
							header("X-JSON: " . json_encode(['message'=>'Les parametres de la boite seront reinitialises si vous changez son type d\'affichage. Confirmer ?']));
							exit(0);
						}
					}
				}
			}
			$type_liste=$_POST['type_liste'];
			$parametres= [];
		}
	}
	else {
		$requete_get_type_liste='SELECT Type_Liste FROM parametres_listes WHERE Pays = \''.$pays.'\' AND Magazine = \''.$magazine.'\'';
		$resultat_get_type_liste=DM_Core::$d->requete_select($requete_get_type_liste);
		if (count($resultat_get_type_liste) > 0) {
			$type_liste=$resultat_get_type_liste[0]['Type_Liste'];
		}
		else 
			$type_liste='dmspiral';
	}
	if (isset($_POST['fusions'])) {
		$fusions=explode('-',$_POST['fusions']);
		foreach($fusions as $fusion) {
			$pays_et_magazine_fusion=explode('_',$fusion);
			$sous_liste->fusionnerAvec($l->sous_liste($pays_et_magazine_fusion[0],$pays_et_magazine_fusion[1]));
		}
	}
	$sous_liste->afficher($type_liste,$parametres);
}
elseif(isset($_GET['liste_exemple'])) {
	$l=new Liste();
	$l->ListeExemple();
		if (file_exists('Listes/Liste.'.$_GET['type_liste'].'.class.php'))
			include_once('Listes/Liste.'.$_GET['type_liste'].'.class.php');
	$objet =new $_GET['type_liste']();
		?>
		<html>
			<head>
				<meta content="text/html; charset=ISO-8859-1"
					  http-equiv="content-type">
				<title><?php echo TITRE;?></title>
				<link rel="stylesheet" type="text/css" href="style.css">
			</head>
			<body>
			<?php $objet->afficher($l->collection).'</font>';?>
			</body>
		</html>
		<?php
}
elseif (isset($_POST['get_description'])) {
	 if (file_exists('Listes/Liste.'.$_POST['type_liste'].'.class.php'))
		include_once('Listes/Liste.'.$_POST['type_liste'].'.class.php');
	$a=new ReflectionProperty($_POST['type_liste'], 'titre');
	$b=new $_POST['type_liste'];
	header("X-JSON: " . json_encode(['titre'=>$a->getValue(),'contenu'=>$b->description]));
}
elseif (isset($_POST['update_list'])) {
	@session_start();
	$parametres=json_decode($_POST['parametres']);
	list($pays,$magazine)=explode('_',$_POST['pays_magazine']);
	$id_user=DM_Core::$d->user_to_id($_SESSION['user']);
	$l = DM_Core::$d->toList($id_user);
	Liste::maj_parametres($parametres, $id_user, $pays, $magazine);
	
	$sous_liste = new Liste();
	$sous_liste = $l->sous_liste($pays, $magazine);
	echo $sous_liste->afficher($_POST['type_liste'],$parametres);
}
elseif (isset($_POST['update_parametres_generaux'])) {
	@session_start();
	$parametres=json_decode($_POST['parametres']);
	$id_user=DM_Core::$d->user_to_id($_SESSION['user']);
	foreach($parametres as $parametre=>$valeur) {
		$requete_modifier_parametre='UPDATE parametres_listes SET Valeur=\''.$valeur.'\' '
								   .'WHERE ID_Utilisateur='.$id_user.' AND Position_Liste=-1 AND Parametre = \''.$parametre.'\'';
		DM_Core::$d->requete($requete_modifier_parametre);
	}
}
elseif (isset($_POST['parametres'])) {
	@session_start();
	$id_user=DM_Core::$d->user_to_id($_SESSION['user']);
	list($pays,$magazine)=explode('_',$_POST['id_magazine']);
	$requete_get_parametres='SELECT Type_Liste,Parametre,Valeur FROM parametres_listes WHERE Pays = \''.$pays.'\' AND Magazine = \''.$magazine.'\' AND ID_Utilisateur='.$id_user;
	$resultat_get_parametres=DM_Core::$d->requete_select($requete_get_parametres);
	if (count($resultat_get_parametres) == 0) {
		$type_liste=$_POST['type_liste'];
		$position_liste=$_POST['position_liste'];
		Liste::init_parametres_boite($pays, $magazine, $type_liste,$position_liste);
		$resultat_get_parametres=DM_Core::$d->requete_select($requete_get_parametres);
	}
	$type_liste=$resultat_get_parametres[0]['Type_Liste'];
	
	$parametres=new stdClass();
	foreach($resultat_get_parametres as $parametre) {
		$nom_parametre=$parametre['Parametre'];
		$parametres->$nom_parametre=$parametre['Valeur'];
	}
	if (file_exists('Listes/Liste.'.$type_liste.'.class.php'))
		include_once('Listes/Liste.'.$type_liste.'.class.php');
	$liste_courante=new $type_liste;
	if (count($resultat_get_parametres) > 0) {
		foreach($parametres as $nom_parametre=>$parametre)
			$liste_courante->parametres->$nom_parametre->valeur=$parametre;
	}
	Liste::maj_parametres($parametres, $id_user, $pays, $magazine);
	header("X-JSON: " . json_encode($liste_courante->getListeParametresModifiables()));
}

function startswith($hay, $needle) { // From http://sunfox.org/blog/2007/03/21/startswith-et-endswith-en-php/
	return $needle === $hay or strpos($hay, $needle) === 0;
}

function endswith($hay, $needle) {
	return $needle === $hay or strpos(strrev($hay), strrev($needle)) === 0;
}
?>