<?php
/**
 * Created by PhpStorm.
 * Jan Beran (xberan43)
 * Date: from 10.02.2019 to
 * Filter-type script for analysis of language IPP2019.
 */

//print("&lt;?xml version=\"1.0\" encoding=\"UTF-8\"?&gt;");

$f = fopen( 'php://stdin', 'r' );
//$f = fopen( 'input.txt', 'r' );
while( $line = fgets( $f ) ) {
    echo($line);
}
fclose( $f );
?>