<?php
/**
 * Created by PhpStorm.
 * Jan Beran (xberan43)
 * Date: from 10.02.2019 to 27.2.2019
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

$three_arg_opcodes = array("ADD", "SUB", "MUL", "IDIV", "LT", "GT", "EQ", "AND", "OR", "NOT", "STRI2INT", "CONCAT", "GETCHAR", "SETCHAR", "JUMPIFEQ", "JUMPIFNEQ"); # jumps have label sym sym, others have var sym sym
$two_arg_opcodes = array("MOVE", "INT2CHAR", "READ", "STRLEN", "TYPE"); # move, inttochar, strlen  = var sym, read = var type,
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
 * checks for a blankline
 * @param $str: line
 * @return bool
 */
function blankline($str)
{
    return preg_match("([\t ]+)", $str) == 1;
}

/**
 * Throws an error. May be upgrades during time.
 * * @param $err_code: err code for throwing
 */
function throw_err($err_code)
{
    exit($err_code);
}

/**
 * Checks for appropiate number of parameters on base of the list of x-operand opcodes
 * @param $splitted_line: Opcode with operands
 * @param $num_of_operands: Assumed number of operands
 * @return int
 */
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

/**
 * Ladicí funkce. Bude ostraněna pri release.
 * @param $splitted_line
 * @param $num_of_operands
 */
function print_info($splitted_line, $num_of_operands)
{
    print("----------------------------\n");
    print($num_of_operands."-operand instruction: ".$splitted_line[0]."\n");
    for ($n = 1; $n < $num_of_operands+1;$n++) #this weird indexing because first operands has index 1
    {
        print("Operand number ".$n.": ".$splitted_line[$n]."\n");
    }
}

/**
 * Checks everything about three-op opcodes.
 * @param $splitted_line: Opcode with operands
 * @return int
 */
function three_op_check($splitted_line)
{
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

/**
 * Checks everything about two-op opcodes.
 * @param $splitted_line: Opcode with operands
 * @return int
 */
function two_op_check($splitted_line)
{
    print_info($splitted_line, 2);
    if($err_code = check_num_of_operands($splitted_line, 2)) return $err_code;

    if($err_code = check_var($splitted_line[1])) return $err_code;

    if($splitted_line[0] == "READ") #No need to check $err_code, returning in every case
    {
       if($err_code = check_syntax(TYPE, $splitted_line[2])) return $err_code;
    }
    else
    {
        if($err_code = check_syntax(SYMBOL, $splitted_line[2])) return $err_code;

    }

    return $err_code;
}

/**
 * Checks everything about one-op opcodes.
 * @param $splitted_line: Opcode with operand
 * @return int
 */
function one_op_check($splitted_line)
{
    print_info($splitted_line, 1);
    if($err_code = check_num_of_operands($splitted_line, 1)) return $err_code;
    switch($splitted_line[0])
    {
        case "DEFVAR":
        case "POPS":
            if($err_code = check_syntax(VARIABLE, $splitted_line[1])) return $err_code;
            break;
        case "CALL":
        case "JUMP":
        case "LABEL":
            if($err_code = check_syntax(LABEL, $splitted_line[1])) return $err_code;
            break;
        case "PUSHS":
        case "WRITE":
        case "EXIT":
            if ($err_code = check_syntax(SYMBOL, $splitted_line[1])) return $err_code;
        break;
    }
    return $err_code;
 }

/**
 * Checks everything about no-op opcodes.
 * @param $splitted_line: Opcode
 * @return int
 */
function no_op_check($splitted_line)
{
    print_info($splitted_line, 0);
    if($err_code = check_num_of_operands($splitted_line, 0)) return $err_code;
   return 0; ##if err, this is the cause
}

/**
 * Prepares line: Deletes comments, all groups of whitechars changes to one space and then explodes the line to words (space == delimiter)
 * @param $line
 * @return array
 */
function prepare_line($line)
{
    $line = explode("#", $line)[0];
    $line = trim($line);
    $line = preg_replace("([\t ]+)", " ", $line);
    return explode(" ", $line); # split by " "
}

/**
 * checks if $var is a type identifier.
 * @param $var
 * @return int
 */
function check_type($var)
{
     if (preg_match("/^(bool|int|string)$/", $var))
         return 0;
     return ERR_LEX_SYN;
}

/**
 * Checks if $var is a variable.
 * @param $var
 * @return int
 */
function check_var($var)
{
 if(preg_match("/^(GF|LF|TF)@[a-zA-z_\-$%&*!?]{1}[a-zA-z0-9_\-$%&*!?]*/", $var))
     return 0;
 return ERR_LEX_SYN;
}

/**
 * Checks if $var is a literal (int@5 or string@meow for example).
 * @param $var
 * @return int
 */
function check_literal($var)
{
    if(preg_match("(int@-?[0-9]+|bool@(true|false)|nil@nil|string@[\\\\\w]+)", $var))
        return 0;

    return ERR_LEX_SYN;
}

/**
 * Checks if $var is label
 * @param $var
 * @return int
 */
function check_label($var)
{
    if(preg_match("/^[a-zA-z_\-$%&*!?]{1}[a-zA-z0-9_\-$%&*!?]*$/", $var))
        return 0;
    return ERR_LEX_SYN;
}

/**
 * Checks if $str is of type $type
 * @param $type
 * @param $str
 * @return int 0 if OK, non-zero value if not
 */
function check_syntax($type, $str)
{
    switch($type)
    {
        case SYMBOL:
            $err = check_var($str);
            if($err != 0)  return check_literal($str);
            else return $err;
        case LITERAL:
            return check_literal($str);
        case LABEL:
            return check_label($str);
        case VARIABLE:
            return check_var($str);
        case TYPE:
            return check_type($str);
        default:
            return ERR_MISC;
    }
}

/**
 * Gets value of a operand (for type and label returns everything, for others only the part after @ char)
 * @param $str
 * @param $type
 * @return mixed
 */
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
}

/**
 * Gets type of the operands $str: int, bool, string, nil, label, type, var
 * @param $str
 * @return string
 */
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

/**
 * Function adds an instruction to a xml tree
 * @param object $xml_tree
 * @param $opcode
 * @param $order
 * @return object
 */
function add_instruction(object $xml_tree, $opcode, $order)
{
    $instruction = $xml_tree->addChild("instruction"); #druhy argument = element uvnitr: <> toto<>
    /** @var object $instruction */
    $instruction->addAttribute("opcode",$opcode);
    $instruction->addAttribute("order", $order);
    return $instruction;
}

/**
 * Function add an argument to a xml element instruction
 * @param object $instruction
 * @param $argname
 * @param $arg_value
 */
function add_argument(object $instruction, $argname, $arg_value)
{
    $type = arg_get_type($arg_value);
    $arg = $instruction->addChild($argname, arg_get_value($arg_value, $type));
    /** @var object $arg */
    $arg->addAttribute("type",$type);
}

/**
 * Checks for --help
 * @param $argv
 */
function check_help($argv)
{
    if(count($argv) == 2 && $argv[1] == "--help")
    {
        print("This is a help for parser.php\n");
        print("Application converts code in IPPcode19 from STDIN to its XML representation and prints it on STDOUT.");
    }
}


check_help($argv);
//Start reading from STDIN and check for header .IPPcode19
$f = fopen( 'php://stdin', 'r' );
    /*For long code use this and paste the code to input.txt file:
        only for dev, will be deleted*/
    //$f = fopen( 'input.txt', 'r' );
$xml_output = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"utf-8\"?><program language='IPPcode19'></program>"); //set xml output

//Checking header
$line = prepare_line(fgets($f));
if(count($line) == 1) check_header($line[0]);
else throw_err(ERR_HEADER);


$err = 0;
$counter = 1;
while($line = fgets($f)) {
    $splitted_line = prepare_line($line);
    $instruction = add_instruction($xml_output, $splitted_line[0], $counter);
    $counter++;

    if(in_array($splitted_line[0], $three_arg_opcodes)){
        if ($err = three_op_check($splitted_line) != 0) throw_err($err);
        add_argument($instruction, "arg1", $splitted_line[1]);
        add_argument($instruction, "arg2", $splitted_line[2]);
        add_argument($instruction, "arg3", $splitted_line[3]);
    }
    elseif(in_array($splitted_line[0], $two_arg_opcodes)){
        if ($err = two_op_check($splitted_line) != 0) throw_err($err);
        add_argument($instruction, "arg1", $splitted_line[1]);
        add_argument($instruction, "arg2", $splitted_line[2]);
    }
    elseif(in_array($splitted_line[0], $one_arg_opcodes)){
        if ($err = one_op_check($splitted_line) != 0) throw_err($err);
        add_argument($instruction, "arg1", $splitted_line[1]);
    }
    elseif(in_array($splitted_line[0], $no_arg_opcodes)){
        if ($err = no_op_check($splitted_line) != 0) throw_err($err);
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
