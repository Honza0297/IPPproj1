<?php
/**
 * Created by PhpStorm.
 * Jan Beran (xberan43)
 * Date: from 10.02.2019 to
 * Filter-type script for analysis of language IPP2019.
 */
define("ERR_HEADER", 21);
define("ERR_LEX_SYN", 22);
define("ERR_MISC", 23);

define("LITERAL", "literal");
define("SYMBOL", "symbol");
define("LABEL", "label");

//Global stuff here:
$linearr = array();
$three_arg_opcodes = array("ADD", "SUB", "MUL", "IDIV", "LT", "GT", "EQ", "AND", "OR", "NOT", "STRI2INT", "CONCAT", "GETCHAR", "SETCHAR", "JUMPIFEQ", "JUMPIFNEQ");
$two_arg_opcodes = array("MOVE", "INT2CHAR", "READ", "STRLEN", "TYPE");
$one_arg_opcodes = array("DEFVAR", "CALL", "PUSHS", "POPS", "WRITE", "LABEL", "JUMP", "EXIT", "DPRINT");
$no_arg_opcodes = array("CREATEFRAME", "PUSHFRAME", "POPFRAME", "RETURN", "BREAK" );


/*
 * Function which check for header (.IPPcode19)
 * current version is case insensitive
 */
function check_header($line)
{
    $line = strtolower(trim($line));
    $input_header = ".ippcode19";
    if ($line != $input_header)
    {
        fwrite(STDERR, "Bad or missing header \".IPPcode19\" in input! Current one is ".$line);
        throw_err(ERR_HEADER);
    }
    print("----------------------------\n");
    print("Header found\n");
    return;
}

/**
 * @param $str
 * @return bool
 */
function blankline($str)
{
    return preg_match("([\t ]+)", $str) == 1;
}


function throw_err($err_code)
{
    exit($err_code);
}


function check_num_of_operands($splitted_line, $num_of_operands)
{
    if (count($splitted_line) != $num_of_operands+1) #opcode + operands
    {
        fwrite(STDERR, $num_of_operands."-operand opcode passed with more or less parameters. \n
        Real number of them is ".(count($splitted_line)-1));
        throw_err(ERR_MISC);
    }
}


function print_info($splitted_line, $num_of_operands)
{
    print("----------------------------\n");
    print($num_of_operands."-operand instruction: ".$splitted_line[0]."\n");
    for ($n = 1; $n < $num_of_operands+1;$n++) #thiss weird indexing because first operands has index 1
    {
        print("Operand number ".$n.": ".$splitted_line[$n]."\n");
    }
}
function three_op_check($splitted_line)
{
    print_info($splitted_line, 3);
    check_num_of_operands($splitted_line, 3);
}


function two_op_check($splitted_line)
{
    print_info($splitted_line, 2);
    check_num_of_operands($splitted_line, 2);
}


function one_op_check($splitted_line)
{
    print_info($splitted_line, 1);
    check_num_of_operands($splitted_line, 1);
}


function no_op_check($splitted_line)
{
    print_info($splitted_line, 0);
    check_num_of_operands($splitted_line, 0);
}


function prepare_line($line)
{
    $line = explode("#", $line)[0];
    $line = trim($line);
    $line = preg_replace("([\t ]+)", " ", $line);
    $linearr[] = $line;
    return explode(" ", $line); # split by " "
}

/**
 * @param $type: Type of syntax control: symbol(var OR literal), literal or label
 */
function check_syntax($type, $str)
{
    switch($type)
    {
        case SYMBOL:
            return (check_var($str) || check_literal($str));
        case LITERAL:
            return check_literal($str);
        case LABEL:
            return check_label($str);
    }
}
//Start reading from STDIN and check for header .IPPcode19
//$f = fopen( 'php://stdin', 'r' );
    /*For long code use this and paste the code to input.txt file:*/

$f = fopen( 'input.txt', 'r' );
$line = prepare_line(fgets($f));
if( count($line) == 1) check_header($line[0]);
else throw_err(ERR_HEADER);

while($line = fgets($f)) {
    $splitted_line = prepare_line($line);

    if(in_array($splitted_line[0], $three_arg_opcodes)){
        three_op_check($splitted_line);
    }
    elseif(in_array($splitted_line[0], $two_arg_opcodes)){
        two_op_check($splitted_line);
    }
    elseif(in_array($splitted_line[0], $one_arg_opcodes)){
        one_op_check($splitted_line);
    }
    elseif(in_array($splitted_line[0], $no_arg_opcodes)){
        no_op_check($splitted_line); #check only for one
    }
    else
    {
        $line = implode($splitted_line);
        if($line == "" || blankline($line)) #If there is a comment or a blankline (only whitespaces)
        {
            print("----------------------------\n");
            print("Commentary or a blankline:".$line."\n");
            continue;
        }

        throw_err(ERR_LEX_SYN);
    }

}
print("----------------------------\n");
exit(666);
$xml_output = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"utf-8\"?><program></program>");
$instruction = $xml_output->addChild("instruction");
$instruction->addAttribute("opcode","MOVE");
print($xml_output->asXML());
exit(0);


fclose($f);
?>