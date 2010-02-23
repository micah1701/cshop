<?php

    
    $file = '/tmp/100-off';
    $pct = 100;

    $c = 0;

    $sth = $pdb->prepare("INSERT INTO cm_coupons (code, descrip, percent_off) VALUES (?, ?, ?)");

    $fh = fopen ($file, 'r');
    if ($fh) {
       while (!feof($fh)) {
           $buf = chop ( fgets($fh, 4096) );
           $buf = preg_replace('/\s*/', '', $buf);
           if ($buf) {
               $pdb->execute($sth, array($buf, "$pct OFF", $pct));
               $c++;
           }
       }
       fclose($fh);
    }

    echo "inserts: $c";







?>
