<?php

require_once('wh40kRenderer.php');

class Upload {

    private static function ProcessHtml($inPath){
        # escape these:
        $tmp = file_get_contents($inPath);
        $tmp = str_replace('& ', '&amp; ', $tmp);
        file_put_contents($inPath, $tmp);
        return ("/var/tmp/".$fileName);
    }

    private static function ProcessRos($inPath){
        return $inPath;
    }

    private static function ProcessRosz($inPath){
        $supicousMessage = "Not a valid rosz file.";
        $fileId = uniqid();
        $zip = new ZipArchive;
        $res = $zip->open($inPath);

        if($res == TRUE) {

            Upload::ValidateZipFile($zip);

            $zip->extractTo('/var/tmp/'.$fileId);
            $extractedFileName = $zip->getNameIndex(0);
            $zip->close();

            rename('/var/tmp/'.$fileId.'/'.$extractedFileName, '/var/tmp/'.$fileId.'.ros');

            unlink($inPath); // removing roszfile
            rmdir('/var/tmp/'.$fileId); //assuming the directory is empty. But it should be because the Validate Method makes sure of that
        } else {
            throw new Exception(supicousMessage." error 40000"); 
        }

        return Upload::ProcessRos('/var/tmp/'.$fileId.'.ros');    
    }

    private static function ValidateZipFile($zip)
    {
        if ($zip->numFiles != 1) {// if battlescribe changes the rosz format, this will break.
            $zip->close();
            throw new Exception(supicousMessage." error 40001");
        }

        if (substr(strtolower($zip->getNameIndex(0)), -4) != '.ros'){ //look if we actually have a .ros file in out file
            $zip->close();
            throw new Exception(supicousMessage." error 40002");
        }

        $fileinfo = $zip->statIndex(0);
        if ($fileinfo['size'] > 5000000){ //if the size is larger than 5MB, it is very suspicious
            $zip->close();
            throw new Exception(supicousMessage." error 40003");
        }
        return true;
    }

    public static function Process($input){
    
    $fileId = uniqid();
    $path_parts = pathinfo($input['name']);
    $fileName = $fileId.'.'.$path_parts['extension'];

    $inPath = "/var/tmp/".$fileName;

    switch (strtolower($path_parts['extension'])) {
        case 'html':
            move_uploaded_file($input['tmp_name'], $inPath);
            return Upload::ProcessHtml($inPath);

        case 'rosz':
            move_uploaded_file($input['tmp_name'], $inPath);    
            return Upload::ProcessRosz($inPath);

        case 'ros':
            move_uploaded_file($input['tmp_name'], $inPath);
            return Upload::ProcessRos($inPath);
        
        default:
            throw new Exception("No file uploaded");
            break;
        }
    }
}

