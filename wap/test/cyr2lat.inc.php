<?php /**/ ?><?
function transliterate( $text )
{
 $cyrlet = '�����Ũ��������������������������'.
           '��������������������������������';
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
     case '�':
       $ct = 'Je';
       break;
     case '�':
       $ct = 'e';
       break;
     case '�':
       $ct = 'Jo';
       break;
     case '�':
       $ct = 'jo';
       break;
     case '�':
       $ct = 'Zh';
       break;
     case '�':
       $ct = 'zh';
       break;
     case '�':
       $ct = 'Ch';
       break;
     case '�':
       $ct = 'ch';
       break;
     case '�':
       $ct = 'Sh';
       break;
     case '�':
       $ct = 'sh';
       break;
     case '�':
       $ct = 'Sht';
       break;
     case '�':
       $ct = 'sht';
       break;
     case '�':
       $ct = 'Yu';
       break;
     case '�':
       $ct = 'yu';
       break;
     case '�':
       $ct = 'Ya';
       break;
     case '�':
       $ct = 'ya';
       break;
     default:
       $ct = '?';
   }
   $result .= $ct;
 }
 return $result;
}
