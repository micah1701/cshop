<?php

require_once(CONFIG_DIR . 'cshop.config.php');


$mdb = MDB2::connect(PEAR_DSN, array('seqcol_name'=>'id'));
$mdb->setOption('portability', MDB2_PORTABILITY_ALL ^ MDB2_PORTABILITY_EMPTY_TO_NULL);

$code = 'spiny99Baw';


$tables = array('cm_orders', 'cm_order_items', 'cm_order_history', 'cm_order_transactions', 'cm_cart', 'cm_cart_items', 'auth_user', 'cm_address_book', 'cm_cart_extra_totals', 'cm_cart_items_options', 'puredark_auth_user', 'cm_paymentcc' );

if (isset($_POST['op_boom'])) {
    if ($_POST['auth'] != $code) {
        echo "<h1>INVALID ENTRY</h1>";
    }
    else {
        
        echo "<h1>SYSTEM RESET</h1>";
        echo '<pre>';
        foreach ($tables as $t) {
            $sql = "TRUNCATE $t";
            echo "     $sql\n";
            $mdb->exec($sql);
        }

        $sql = "UPDATE cm_products SET view_count = 0";
        echo "     $sql\n";
        $mdb->exec($sql);
        
        echo '</pre>';
        echo '<blink><strong>boom.</strong></blink>';
        exit();
    }
}

?>


<div style="text-align: center; margin: 4em">
<img src="http://www.allamericanpatriots.com/files/images/mushroom-cloud.jpg" alt="boom" />
<br />
Reset customer and order records and statistics:
<br />
<form method="post">
<input type="password" name="auth" />
<input type="submit" value="BOOM" name="op_boom" onclick="if (confirm('Are you sure?')) return confirm ('REALLY sure?');" />
</form>

</div>
