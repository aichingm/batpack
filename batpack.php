<?php
namespace batpack;

class Archive{

    private $files = [];
    private $injectedScript = "";
    private $automaticallyUnpack = false;
    private $destination = "%cd%";

    public function __construct($automaticallyUnpack){
        $this->automaticallyUnpack = $automaticallyUnpack;
    }

    public function setInjectedScript($script){
        $this->injectedScript = $script;
    }

    public function addFile($filename, $content){
        $this->files[$filename] = $content;
    }

    private function pack($filename, $contents, $maxLength = 7000){
        $packed = "";
        $contents_b64 = base64_encode($contents);
        $chunks = str_split($contents_b64, $maxLength);
        $packed .= ":batpack_$filename".PHP_EOL;
        $packed .= "echo -----BEGIN CERTIFICATE----- > %1\\$filename.b64".PHP_EOL;
        foreach($chunks as $chunk){
            $chunk = str_replace("+", "^+", $chunk);
            $chunk = str_replace("=", "^=", $chunk);
            $packed .= "echo $chunk >> %1\\$filename.b64".PHP_EOL;
        }
        $packed .= "echo -----END CERTIFICATE----- >> %1\\$filename.b64".PHP_EOL;
        $packed .= "certutil -decode %1\\$filename.b64 %1\\$filename".PHP_EOL;
        $packed .= "del %1\\$filename.b64".PHP_EOL;
        $packed .= "EXIT /B 0".PHP_EOL;
        return $packed;
    }

    public function __toString(){
        $bat = "@echo off".PHP_EOL;
        $bat .= "echo unpacking...".PHP_EOL;
        if($this->automaticallyUnpack){
            $bat .= "call :batpack_unpack_all ".$this->destination.PHP_EOL;
        }
        $bat .= $this->injectedScript;
        $bat .= "EXIT /B %ERRORLEVEL%".PHP_EOL;
        $bat .= ":batpack_unpack_all".PHP_EOL;
        foreach($this->files as $filename => $content){
            $bat .= "call :batpack_$filename %1".PHP_EOL;
        }
        $bat .= "EXIT /B 0".PHP_EOL;
        foreach($this->files as $filename => $content){
            $bat .= $this->pack($filename, $content);
        }
        return $bat;
    }

}



if(basename(__FILE__) == $_SERVER["PHP_SELF"]){
function usage($code=0){
        echo <<<EOF
    batpack - scriptable batch archive
    batpack.php [-flags] [-s script ] file1 [file#]

    -a   automatically unpack all files
    -s   inject a script in to the extraction routine. If used with -a the files
         will be un packed in the cwd before the script a executed

EOF;
        exit(0);
}
    array_shift($argv);
    $flags = "";
    $files = [];
    foreach($argv as $arg){
        if(strlen($arg) >= 2 && $arg[0] == '-' && $arg[1] != '-'){
            $flags .= $arg;
        }else{
            $files[] = $arg;
        }
    }
    $_flags = array_unique(str_split($flags, 1));
    $flags = [];
    foreach($_flags as $flag){
        $flags[$flag] = true;
    }


    if(count($files) == 0){
        usage(1);
    }

    foreach($files as $file){
        if(!is_file($file)){
            echo "$file\n";
            usage(2);
        }
    }

    $archive = new Archive(isset($flags['a']));
    if(isset($flags['s'])){
        $archive->setInjectedScript(file_get_contents($files[0]));
        array_shift($files);
    }

    foreach($files as $file){
        $archive->addFile($file, file_get_contents($file));
    }
    echo $archive;

}

