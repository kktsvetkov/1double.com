<?php /**/ ?><?
function transliterate( $text )
{
 $cyrlet = 'ÀÁÂÃÄÅ¨ÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞ‗'.
           'אבגדהו¸זחטיךכלםמןנסעףפץצקרשתûü‎‏ÿ';
 $englet = 'ABVGD   ZIJKLMNOPRSTUFHC   YY`E  '.
           'abvgd   zijklmnoprstufhc   yy`e  ';
 $result = '';
 for ( $i=0; $i<strlen($text); $i++ ) {
   $c1 = $text[ $i ];
   $p1 = strpos( $cyrlet, $c1 );
   if ( $p1 === FALSE ) { $result .= $c1; continue; }
   $ct = $englet[ $p1 ];
   if ( $ct != ' ' ) { $result .= $ct; continue; }
   switch ( $c1 )
   {
     case 'Å':
       $ct = 'Je';
       break;
     case 'ו':
       $ct = 'e';
       break;
     case '¨':
       $ct = 'Jo';
       break;
     case '¸':
       $ct = 'jo';
       break;
     case 'Æ':
       $ct = 'Zh';
       break;
     case 'ז':
       $ct = 'zh';
       break;
     case '×':
       $ct = 'Ch';
       break;
     case 'ק':
       $ct = 'ch';
       break;
     case 'Ø':
       $ct = 'Sh';
       break;
     case 'ר':
       $ct = 'sh';
       break;
     case 'Ù':
       $ct = 'Sht';
       break;
     case 'ש':
       $ct = 'sht';
       break;
     case 'Þ':
       $ct = 'Yu';
       break;
     case '‏':
       $ct = 'yu';
       break;
     case '‗':
       $ct = 'Ya';
       break;
     case 'ÿ':
       $ct = 'ya';
       break;
     default:
       $ct = '?';
   }
   $result .= $ct;
 }
 return $result;
}
