var auteurs_valides=Array();

function toggle_histogramme() {
	if ($('histogramme').src.endsWith('classement_histogramme.php')) {
		$('histogramme').src='classement_histogramme.php?pcent=true';
		$('lien_toggle_histogramme').update('Afficher en nombre de num&eacute;ros');
		return;
	}
	$('histogramme').src='classement_histogramme.php';
	$('lien_toggle_histogramme').update('Afficher en pourcentages');
}

function vider_pouces()
{
	for (var i=0;;i++) {
		if (!$('pouce'+i+'_1'))
			break;
		if (!auteurs_valides[i]) {
			for (var j=1;j<=10;j++) {
				var src=$('pouce'+i+'_'+j).readAttribute('src');
				if (src.indexOf('blanc')==-1)
					$('pouce'+i+'_'+j).writeAttribute({'src':src.substring(0,src.length-4)+'_blanc.png'});
			}
		}
	}
} 

function init_notations() {
	var num_auteur=0;
	while ($('pouce'+num_auteur+'_1')) {
		if ($('aucune_note'+num_auteur).checked)
			set_aucunenote(num_auteur);
		num_auteur++;
	}
	var myAjax = new Ajax.Request('Database.class.php', {
	   method: 'post',
	   parameters:'database=true&liste_notations=true',
	   onSuccess:function(transport,json) {
	    	var reg=new RegExp("_", "g");
	    	var notations=transport.responseText.split(reg);
	    	for (var i=0;i<notations.length;i++) {
	    		if (notations[i]==1)
	    			auteurs_valides[i]=true;
	    		else
	    			auteurs_valides[i]=false;
	    	}
		}
	});
}

function hover (num_auteur,num_image) {
	if ($('pouces'+num_auteur).hasClassName('desactive')) return;
	if (auteurs_valides[num_auteur]==1) return;
	for (var i=1;i<=num_image;i++) {
		var src=$('pouce'+num_auteur+'_'+i).readAttribute('src');
		if (src.indexOf('blanc')!==-1)
			$('pouce'+num_auteur+'_'+i).writeAttribute({'src':src.substring(0,src.length-10)+'.png'});
	}
	for (i=num_image+1;;i++) {
		if (!$('pouce'+num_auteur+'_'+i))
			break;
		var src=$('pouce'+num_auteur+'_'+i).readAttribute('src');
		if (src.indexOf('blanc')==-1)
			$('pouce'+num_auteur+'_'+i).writeAttribute({'src':src.substring(0,src.length-4)+'_blanc.png'});
	}
}

function valider_note(num_auteur) {
	if ($('pouces'+num_auteur).hasClassName('desactive')) return;
	if (auteurs_valides[num_auteur]==1)
		auteurs_valides[num_auteur]=0;
	else {
		$('aucune_note'+num_auteur).checked=false;
		auteurs_valides[num_auteur]=1;
		var i=1;
		while ($('pouce'+num_auteur+'_'+i)) {
			var src=$('pouce'+num_auteur+'_'+i).readAttribute('src');
			if (src.indexOf('blanc')!==-1) {
				$('notation'+num_auteur).value=i-1;
				return;
			}
			i++;
		}
		$('notation'+num_auteur).value=10;
	}
}

function set_aucunenote(num_auteur) {
	if ($('pouces'+num_auteur).hasClassName('desactive')) {
		$('pouces'+num_auteur).removeClassName('desactive');
		return;
	}
	$('pouces'+num_auteur).addClassName('desactive');
	auteurs_valides[num_auteur]=0;
	var i=1;
	while ($('pouce'+num_auteur+'_'+i)) {
		var src=$('pouce'+num_auteur+'_'+i).readAttribute('src');
		if (src.indexOf('blanc')==-1)
			$('pouce'+num_auteur+'_'+i).writeAttribute({'src':src.substring(0,src.length-4)+'_blanc.png'});
		i++;
	}
}

function supprimer_auteur (nom_auteur) {
	var myAjax = new Ajax.Request('Database.class.php', {
	   method: 'post',
	   parameters:'database=true&supprimer_auteur=true&nom_auteur='+nom_auteur,
	   onSuccess:function(transport,json) {
	    	location.reload();
		}
	});
}

function montrer_magazines(pays) {
    $$('[name="magazine"]')
        .each(function(element) {
            if (element.href) {
                if (element.href.indexOf(pays+'/') == -1)
                    element.up().setStyle({'display':'none'});
                else
                    element.up().setStyle({'display':'block'});
            }
        }
    );
}

function montrer_nom_magazine(element) {
    $('nom_magazine_courant').update(element.readAttribute('id'))
                             .setStyle({'marginLeft':(element.offsetLeft - 5)+'px',
                                        'visibility':'visible'});
}

function cacher_nom_magazine() {
    $('nom_magazine_courant').setStyle({'visibility':'hidden'});
}