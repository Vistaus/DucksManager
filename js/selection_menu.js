// <![CDATA[
var prochain_traitement=null;
var image_ouverte=false;
var sous_menu_deroule=null;
function sel_traitement(traitement) {
	if (!traitement_possible(traitement))
		return false;
		
	if (prochain_traitement)
		document.getElementById(prochain_traitement).style.color='black'; // La police du dernier traitement redevient noire
	document.getElementById(traitement).style.color='red'; //... Et la nouvelle devient rouge
	document.getElementById(traitement).blur=true; // Permet de faire perdre le focus au bouton, pour un bel effet graphique !
	prochain_traitement=traitement;
	return true;
	
}

function traitement_possible(traitement) {
	return true;
}
function montrer_cacher(element) {
	var id=element.id;
	if (document.getElementById(id+'_sous_menu').style.display=='none') { // Si le sous-menu est cach�, l'afficher...
		if (id.charAt(0) ==id.charAt(0).toUpperCase()) {// Les boutons comme "rotation" ou "rectangle" ont un id en caract�res minuscules, ce qui permet de faire la diff�rence avec les menus principaux qui agissent diff�remment lors du clic
			if (sous_menu_deroule)
				document.getElementById(sous_menu_deroule).style.display='none';
			sous_menu_deroule=id+'_sous_menu';
			
		}
		document.getElementById(id+'_sous_menu').style.display='inline'; // Afficher le sous-menu
		if (document.getElementById(id+'_0'))
			sel_traitement(id+'_0'); // S�lectionner le 1er �l�ment du sous-menu s'il existe
		return;
	}
	else {
		document.getElementById(id+'_sous_menu').style.display='none';
		if (prochain_traitement && prochain_traitement.indexOf(id)!=-1) // Si le traitement �tait sur un sous-item de cet �l�ment, on annule ce traitement
			prochain_traitement=null;
		if (sous_menu_deroule && id.charAt(0) ==id.charAt(0).toUpperCase())
			sous_menu_deroule=null;
	}
}
// ]]>