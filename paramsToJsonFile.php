<?php
//NOTE! if pure JSON output is desired then comment line 65 and UNcomment line 64!
 
//script that will convert all POST and GET params to json format and write to a file.
//for example: use with IFTTT 

//Note: POST params take priority over GET params!
//Note: a timestamp is manually added

////////// SETUP
$filename="foo.txt";
$maxDataItems=50;
////////// END SETUP

$debug=false;

$allParams=createArrayFromParams();

/////////////// SETUP:
$ignoreParams=array("TweetEmbedCode", "LocationMapImageUrl"); //define the items we DO NOT want (they will be filtered)
$requiredItems=array("UserName", "Text"); //define the items that we MUST have. data will not be added if any of these are missing e.g. array("d", "x") requires data elements: 'd' and 'x'
///////////////


//create array with only the items that we want
$toAdd=array();
foreach ($allParams as $param_name => $param_val) {
    if (!isItemInArray($param_name, $ignoreParams)) {
        $toAdd[$param_name] = $param_val;
    }
}

//has there been an update attempt
if (count($toAdd)>0) {
    //check all is well
    $approved=true;
    $err="";
    foreach ($requiredItems as $param_name) {
        if (!isItemInArray($param_name, array_keys($toAdd))) {
            $approved=false;
            $err .= "missing:" . $param_name . "\n";
        }
    }

    //debug & failed?
    if ($debug && $err!="") {echo("<pre>\n" . $err . "</pre>");}
}

//var_dump(json_encode($toAdd));

if ($approved) {
    //manually add a timestamp
    $toAdd[t] = date("Y-m-d H:i:s");
    
    //addJsonToFile($json);
    if ($debug) {echo("Adding: <pre>" . prettyPrint(json_encode($toAdd)). "</pre>");}
    addJsonToFile($toAdd);
    echo("ok");
} else {
    $contents=file_get_contents($filename);
    if ($debug) {
        echo("<pre>" . prettyPrint($contents). "</pre>");
    } else {
        //echo($contents);
      	echo("<pre>" . prettyPrint($contents). "</pre>"); //testing phase so we show nicely
    }
}


//var_dump($allParams);

function addJsonToFile($jsonToAdd) {
    global $debug, $filename, $maxDataItems;
    $defaultFileOuter='{
        "v": "1",
        "d": [
            {"t": "1970-01-01 11:22:33", "d":"It\'s early days!"},
            {"t": "1970-01-01 11:22:33", "d":"Nothing is here yet :("}
        ]
    }';
        
    $fp = fopen($filename, "a+");
    
    if (flock($fp, LOCK_EX)) {
        
        $filesize=filesize($filename);    
        
        if (!$filesize || $filesize<1) {
            $contents=$defaultFileOuter;
        } else {
            $contents=fread($fp, $filesize);
        }
        
        $json=json_decode($contents, true);

        $dataLength=count($json["d"]);

        while ($dataLength>$maxDataItems) {
            array_shift($json["d"]);
            $dataLength=count($json["d"]);
        }

        $json["d"][]=$jsonToAdd; //add the new data
        
        if ($debug) {echo("Updated:<br><pre>" . prettyPrint(json_encode($json)) . "</pre>");}
        ftruncate($fp, 0); //we want to remove everything in the current file.
        $contents=json_encode($json);
        fwrite($fp, $contents);

        //sleep(5); //can use to check that PHP works well with multiple attempts to write (PHP automatically queue's write attempts)
        flock($fp, LOCK_UN);
    }
} 

//make sure $array is NOT an associative array - use array_keys(arrayObj) to get an array with just the keys when calling if required!
function isItemInArray($item, $array, $debug) {
    $return=false;
    foreach ($array as $i) {
        if ($debug) {echo("testing: " . $item . "==" . $i . "<br>");}
        if ($i==$item) {
            $return=true;
            break;
        }
        //var_dump($i);echo("<br>");
    }
    return $return;
}


function createArrayFromParams(){
    //array_merge fails if 1 is empty so we need a couple of tests
    if ($_POST && $_GET) {
        return array_merge ( 
            // note the sequence : POST vars overwrite any GET vars
            filter_input_array(INPUT_GET, FILTER_SANITIZE_SPECIAL_CHARS),
            filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS) 
        );
    }
    if ($_GET) {
        return filter_input_array(INPUT_GET, FILTER_SANITIZE_SPECIAL_CHARS);
    }    
    if ($_POST) {
        return filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
    }
}


//from: https://stackoverflow.com/questions/6054033/pretty-printing-json-with-php
function prettyPrint( $json )
{
    $result = '';
    $level = 0;
    $in_quotes = false;
    $in_escape = false;
    $ends_line_level = NULL;
    $json_length = strlen( $json );

    for( $i = 0; $i < $json_length; $i++ ) {
        $char = $json[$i];
        $new_line_level = NULL;
        $post = "";
        if( $ends_line_level !== NULL ) {
            $new_line_level = $ends_line_level;
            $ends_line_level = NULL;
        }
        if ( $in_escape ) {
            $in_escape = false;
        } else if( $char === '"' ) {
            $in_quotes = !$in_quotes;
        } else if( ! $in_quotes ) {
            switch( $char ) {
                case '}': case ']':
                    $level--;
                    $ends_line_level = NULL;
                    $new_line_level = $level;
                    break;

                case '{': case '[':
                    $level++;
                case ',':
                    $ends_line_level = $level;
                    break;

                case ':':
                    $post = " ";
                    break;

                case " ": case "\t": case "\n": case "\r":
                    $char = "";
                    $ends_line_level = $new_line_level;
                    $new_line_level = NULL;
                    break;
            }
        } else if ( $char === '\\' ) {
            $in_escape = true;
        }
        if( $new_line_level !== NULL ) {
            $result .= "\n".str_repeat( "\t", $new_line_level );
        }
        $result .= $char.$post;
    }

    return $result;
}

?>


