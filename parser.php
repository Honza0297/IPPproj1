<?php
/**
 * Created by PhpStorm.
 * Jan Beran (xberan43)
 * Date: from 10.02.2019 to
 * Filter-type script for analysis of language IPP2019.
 */

//Constants are defined here
define("ERR_HEADER", 21);
define("ERR_LEX_SYN", 22);
define("ERR_MISC", 23);

define("LITERAL", "literal");
define("SYMBOL", "symbol");
define("LABEL", "label");
define("VARIABLE", "variable");
define("TYPE", "type");

//Global stuff here:
$arr = array();
$three_arg_opcodes = array("ADD", "SUB", "MUL", "IDIV", "LT", "GT", "EQ", "AND", "OR", "NOT", "STRI2INT", "CONCAT", "GETCHAR", "SETCHAR", "JUMPIFEQ", "JUMPIFNEQ"); # jumps have label sym sym, others have var sym sym
$two_arg_opcodes = array("MOVE", "INT2CHAR", "READ", "STRLEN", "TYPE"); # move, inttochar, strlen  = var sym, read = var type,
$one_arg_opcodes = array("DEFVAR", "CALL", "PUSHS", "POPS", "WRITE", "LABEL", "JUMP", "EXIT", "DPRINT");
$no_arg_opcodes = array("CREATEFRAME", "PUSHFRAME", "POPFRAME", "RETURN", "BREAK" );

//Regexes are defined here:


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
        return ERR_MISC;
    }
    return 0;
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
    $err_code = 0;
    print_info($splitted_line, 3);
    if($err_code = check_num_of_operands($splitted_line, 3)) return $err_code;
    if($splitted_line[0] == "JUMPIFEQ" || $splitted_line == "JUMPIFNEQ")
    {
        if($err_code = check_syntax(LABEL, $splitted_line[1])) return $err_code;
    }
    else
    {
        if($err_code = check_syntax(VARIABLE, $splitted_line[1])) return $err_code;
    }

    if ($err_code = check_syntax(SYMBOL, $splitted_line[2])) return $err_code;

    check_syntax(SYMBOL, $splitted_line[3]); #Here we are returning $err_code every time, no need to if it like the others
    return $err_code;
}


function two_op_check($splitted_line)
{
    $err_code = 0;
    print_info($splitted_line, 2);
    if($err_code = check_num_of_operands($splitted_line, 2)) return $err_code;

    if($err_code = check_var($splitted_line[1])) {
        return $err_code;
    }

    if($splitted_line[0] = "READ") #No need to check $err_code, returning in every case
    {
       check_syntax(TYPE, $splitted_line[2]);
    }
    else
    {
        check_syntax(SYMBOL, $splitted_line[2]);
    }
    return $err_code;
    #todo xml stuff here
}


function one_op_check($splitted_line)
{
    $err_code = 0;
    print_info($splitted_line, 1);
    if($err_code = check_num_of_operands($splitted_line, 1)) return $err_code;
    return $err_code;
    #TODO Check appropiate value by the opnames
    #TODO xml stuff here
 }


function no_op_check($splitted_line)
{
    $err_code = 0;
    print_info($splitted_line, 0);
    if($err_code = check_num_of_operands($splitted_line, 0)) return $err_code;
    #TODO XML stuff here
}


function prepare_line($line)
{
    $line = explode("#", $line)[0];
    $line = trim($line);
    $line = preg_replace("([\t ]+)", " ", $line);
    return explode(" ", $line); # split by " "
}
function check_type($var)
{
     if (preg_match("/^(bool|int|string)$/", $var))
         return 0;
     return ERR_LEX_SYN;
}
function check_var($var)
{
 if(preg_match("/^(GF|LF|TF)@[a-zA-z_\-$%&*!?]{1}[a-zA-z0-9_\-$%&*!?]*/", $var))
     return 0;
 return ERR_LEX_SYN;
}
function check_literal($var)
{
    if(preg_match("(int@-?[0-9]+|bool@(true|false)|nil@nil|string@[\\\w]+)", $var))
        return 0;
    return ERR_LEX_SYN;
}
function check_label($var)
{
    if(preg_match("/^[a-zA-z_\-$%&*!?]{1}[a-zA-z0-9_\-$%&*!?]*$/", $var))
        return 0;
    return ERR_LEX_SYN;
}
/**
 * @param $type: Type of syntax control: symbol(var OR literal), literal or label
 */
function check_syntax($type, $str)
{
    switch($type)
    {
        case SYMBOL:
            $err = check_var($str);
            if($err == 0) return $err;
            else return check_literal($str);
        case LITERAL:
            return check_literal($str);
        case LABEL:
            return check_label($str);
        case VARIABLE:
            return check_var($str);
        case TYPE:
            return check_type($str);
    }
}

function arg_get_value($str, $type)
{
    #if var or label or type-> return all
    if(in_array($type, array("type", "label", "var")))
        return $str;
    else
    {
        preg_match("((?<=@).+)", $str, $match);
        return $match[0];
    }

    #((?<=@).+) regex for getting everything after @
}

#int, bool, string, nil, label, type, var
function arg_get_type($str)
{
   if(preg_match("(int|bool|string|nil)", $str))
   {
       if(preg_match("(^[^@\s]*$)", $str)) #only a type
           return "type";
       else #it is a literal type@value
       {
           preg_match("((^[^@\s]*))", $str, $substr);
           return $substr[0];
       }
   }
   else #var or label
   {
   if(preg_match("(^(GF|LF|TF))", $str))
   {
       return "var" ;
   }
   else return "label";
   }
}
//Start reading from STDIN and check for header .IPPcode19
//$f = fopen( 'php://stdin', 'r' );
    /*For long code use this and paste the code to input.txt file:*/
$f = fopen( 'input.txt', 'r' );
$xml_output = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"utf-8\"?><program language='IPPcode19'></program>");

$line = prepare_line(fgets($f));
if(count($line) == 1) check_header($line[0]);
else throw_err(ERR_HEADER);
$err = 0;
$counter = 1;
while($line = fgets($f)) {
    $splitted_line = prepare_line($line);
    $instruction = $xml_output->addChild("instruction"); #druhy argument = element uvnitr: <> toto<>
    $instruction->addAttribute("opcode",$splitted_line[0]);
    $instruction->addAttribute("order", $counter);
    $counter++;

    if(in_array($splitted_line[0], $three_arg_opcodes)){
        if ($err = three_op_check($splitted_line) != 0) throw_err($err);
        $type = arg_get_type($splitted_line[1]);
        $arg1 = $instruction->addChild("arg1", arg_get_value($splitted_line[1], $type));
        $arg1->addAttribute("type",$type);

        $type = arg_get_type($splitted_line[2]);
        $arg2 = $instruction->addChild("arg2",arg_get_value($splitted_line[2], $type) );
        $arg2->addAttribute("type",$type);


        $type = arg_get_type($splitted_line[3]);
        $arg3 = $instruction->addChild("arg3",arg_get_value($splitted_line[3], $type));
        print(arg_get_value($splitted_line[3], $type));
        $arg3->addAttribute("type",$type);


        $arr[] = $splitted_line;
    }
    elseif(in_array($splitted_line[0], $two_arg_opcodes)){
        if ($err = two_op_check($splitted_line) != 0) throw_err($err);
        $type = arg_get_type($splitted_line[1]);
        $arg1 = $instruction->addChild("arg1", arg_get_value($splitted_line[1], $type));
        $arg1->addAttribute("type",$type);

        $type = arg_get_type($splitted_line[2]);
        $arg2 = $instruction->addChild("arg2",arg_get_value($splitted_line[2], $type) );
        $arg2->addAttribute("type",$type);

        $arr[] = $splitted_line;
    }
    elseif(in_array($splitted_line[0], $one_arg_opcodes)){
        if ($err = one_op_check($splitted_line) != 0) throw_err($err);
        $type = arg_get_type($splitted_line[1]);
        $arg1 = $instruction->addChild("arg1", arg_get_value($splitted_line[1], $type));
        $arg1->addAttribute("type",$type);

        $arr[] = $splitted_line;
    }
    elseif(in_array($splitted_line[0], $no_arg_opcodes)){
        if ($err = no_op_check($splitted_line) != 0) throw_err($err);
        $arr[] = $splitted_line;
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


print(preg_replace("(><)",">\n<", $xml_output->asXML()));

fclose($f);
exit(0);
?>