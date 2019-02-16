<?php
/**
 * Created by PhpStorm.
 * Jan Beran (xberan43)
 * Date: from 10.02.2019 to
 * Filter-type script for analysis of language IPP2019.
 */


//Global stuff here:
$linearr = array();
$input_header = ".IPPcode19";

//Start reading from STDIN and check for header .IPPcode19
$f = fopen( 'php://stdin', 'r' );
    /*For long code use this and paste the code to input.txt file:
    $f = fopen( 'input.txt', 'r' );*/
$line = trim(fgets($f)); //trim because get rid of newline at the end
if ($line != $input_header)
{
    fwrite(STDERR, "Bad or missing header \".IPPcode19\" in input! Current one is ");
    fwrite(STDERR, $line);
    exit(21);
}




while( $line = fgets( $f ) ) {
    $linearr[] = $line;
}
fclose($f);
?>