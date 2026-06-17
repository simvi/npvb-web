
var ChampsVides = new Array();

function videChamp(champ)
{
	//champ.value="";
	if (!(eval("ChampsVides['"+champ.name+"']")==true))
	{
		champ.value="";
		eval("ChampsVides['"+champ.name+"']=true");
	}
}


//Fonctions pour la bulle

/* Script "z'experts" : http://perso.wanadoo.fr/coin.des.experts/
   delivre sans aucune garantie, ni des auteurs, ni du gouvernement. 
   Diffusion libre, mais merci de conserver cette signature :-) */
 
 /* La fonction bulle() qui ouvre la bulle d'aide a 3 arguments possibles:
   - le premier est le message a faire apparaitre. 
   - LE DEUXIEME EST OBLIGATOIREMENT "event" (sans les guillemets) 
   c.a.d. un mot cle du javascript.
   - Le 3eme argument est facultatif. Il permet d'ajuster 
   le decalage vertical afin de ne pas tronquer les bulles trop 
   longues ouvertes vers le bas de l'ecran; partez de
       hauteur=1,2 x taille police x nombre de lignes +10
   
   Enfin, mettre le bloc <DIV id="Bulle">...</DIV> en tete du bloc BODY. 
   NE PAS CHANGER LE NOM "Bulle";  sinon, vous pouvez modifier le style 
   qui suit ou le message d'erreur à votre gré (mais laissez le
   position:absolute et un z-index tres grand)
  */

var bulleStyle=null
if (!document.layers && !document.all && !document.getElementById)
   event="chut";  //pour apaiser NN3 et autres antiquites

function bulle(msg,evt,hauteur){
 
     
 var xfenetre,yfenetre,xpage,ypage,element=null;
 var offset= 15;           // decalage par defaut
 var bulleWidth=330;       // largeur par defaut 
 if (!hauteur) hauteur=40; // hauteur par défaut

  if (document.layers) {
    bulleStyle=document.layers['Bulle'];
    bulleStyle.document.write('<layer bgColor="#ffffdd" '
       +'style="width:150px;border:1px solid black;color:black">'
       + msg + '</layer>' );
    bulleStyle.document.close();
    xpage = evt.pageX ; ypage  = evt.pageY;
    xfenetre = xpage ;yfenetre = ypage ;		
  } else if (document.all) {
    element=document.all['Bulle']
    xfenetre = evt.x ;yfenetre = evt.y ;		
    xpage=xfenetre ; ypage=yfenetre	;	
    if (document.body.scrollLeft) xpage = xfenetre + document.body.scrollLeft ; 
    if (document.body.scrollTop) ypage = yfenetre + document.body.scrollTop;
  } else if (document.getElementById) {
	element=document.getElementById('Bulle')
    xfenetre = evt.clientX ;yfenetre = evt.clientY ;
    xpage=xfenetre ; ypage=yfenetre	;	
    if(evt.pageX) xpage = evt.pageX ;
    if(evt.pageY) ypage  = evt.pageY ;
  }
    
  if(element) {
     bulleStyle=element.style;
		 element.innerHTML=msg;}
		 	
  if(bulleStyle) {
     /* on met la bulle à gauche du pointeur (si c'est possible) 
        et en haut du pointeur si on est assez bas dans l'écran */
	 
     if (xfenetre > bulleWidth+offset) xpage=xpage-bulleWidth-offset;
     else xpage=xpage+15;
     if ( yfenetre > hauteur+offset ) ypage=ypage-hauteur-offset;
     bulleStyle.width=bulleWidth;  
		 if(typeof(bulleStyle.left)=='string') {
				 bulleStyle.left=xpage+'px'; bulleStyle.top=ypage+'px';  
		} else {
				bulleStyle.left=xpage     ; bulleStyle.top=ypage ; }
     bulleStyle.visibility="visible"; }
}
 
function couic(){
  if(bulleStyle)  bulleStyle.visibility="hidden";
}

//Fonctions pour la page des jours

function Deroule(Partie){
	document.getElementById("Rouleau"+Partie).style.display="block"; 
	document.getElementById("Enroule"+Partie).style.display="block"; 
	document.getElementById("Deroule"+Partie).style.display="none"; 
	eval("document.forms['formulaire'].estDeroule"+Partie+".value=\"o\";"); 
}

function Enroule(Partie){
	document.getElementById("Rouleau"+Partie).style.display="none"; 
	document.getElementById("Enroule"+Partie).style.display="none"; 
	document.getElementById("Deroule"+Partie).style.display="block"; 
	eval("document.forms['formulaire'].estDeroule"+Partie+".value=\"n\";"); 
}

