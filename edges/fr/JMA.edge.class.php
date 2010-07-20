<?php
class JMA extends Edge {
    var $pays='fr';
    var $magazine='JMA';
    var $intervalles_validite=array(array('debut'=>1 , 'fin'=>7));
    static $largeur_defaut=9.5;
    static $hauteur_defaut=214;

    function JMA ($numero) {
        $this->numero=$numero;
        $this->hauteur=214*Edge::$grossissement;
        $this->largeur=8*Edge::$grossissement;
        $this->image=imagecreatetruecolor(intval($this->largeur),intval($this->hauteur));
    }

    function dessiner() {
        $image_texte=imagecreatetruecolor($this->hauteur,$this->largeur);
        $noir=imagecolorallocate($image_texte, 0, 0, 0);
        $couleur_texte=imagecolorallocate ($image_texte, 208, 23, 33);

        $titre=new Texte('MICKEY AVENTURE',0,8*Edge::$grossissement,
							 8*Edge::$grossissement,0,$couleur_texte,'Boton Bold.ttf');
        $titre->dessiner($image_texte);
        $image_texte=imagerotate($image_texte, 90, $couleur_texte);
        $this->placer_image($image_texte, 'haut', array(0,$this->hauteur/4), 1, 0.75);

        $image_numero=imagecreatetruecolor(2.5*$this->largeur,$this->largeur);
        $numero=new Texte('N�'.$this->numero,0,8*Edge::$grossissement,
							 8*Edge::$grossissement,0,$couleur_texte,'Boton Bold.ttf');
        $numero->dessiner($image_numero);
        $image_numero=imagerotate($image_numero, 90, $couleur_texte);
        $this->placer_image($image_numero, 'haut', array(0,$this->largeur/2), 1, 0.75);

        return $this->image;
    }
}
?>