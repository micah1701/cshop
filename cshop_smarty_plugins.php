<?php

function smarty_modifier_cleanforurl($str) {
    return rawurlencode( strtolower( substr( preg_replace('/[^\d\w_-]+/', '', preg_replace('/\s+/', '-', trim($str)) ), 0, 32) ) );
}


function smarty_modifier_tabindex($str, $ti) {
    return preg_replace('/<(input|textarea|select) ([^>]+)>/', "<$1 tabindex=\"$ti\" $2>", $str);
}



/* takes an email address and breaks it up into an insane Javascripty 
 * document.write thing and then writes it out with ASCII codes using ord(). 
 * This all keeps spambots from harvesting the email addr, we hope. */
function smarty_modifier_obfuscate_email($str) {
    $parts = split('@', $str);
    $res = '<script type="text/javascript">
              <!--       // custom sp*m-bot defeater
                document.write(\'<a \'+\'hr\'+\'ef="mai\');
                document.write(\'lto:'.$parts[0].'&#64;\' + \''. $parts[1] . '">\');
              // -->
            </script>';
    foreach (str_split($str) as $c) {
        $res .= '&#'.ord($c).';';
    }
    $res .= '<script type="text/javascript"> <!--
            document.write(\'</a>\'); // xxx
          // -->
          </script>'; 
    return $res;
}

if (isset($smarty) and is_object($smarty)) {
    $smarty->register_modifier('obfuscate_email', 'smarty_modifier_obfuscate_email');

    /* add a custom Smarty modifier to add tabindex attribs to inputs */
    $smarty->register_modifier('tabindex', 'smarty_modifier_tabindex');

    /* add a custom Smarty modifier to clean up strings to avoid ugluy URLs. */
    $smarty->register_modifier('cleanforurl', 'smarty_modifier_cleanforurl');
}

