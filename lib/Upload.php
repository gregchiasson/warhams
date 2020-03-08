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
        $fileId = uniqid();
        $zip = new ZipArchive;
        $res = $zip->open($inPath);

        if($res == TRUE) {
            $zip->extractTo('/var/tmp/'.$fileId);
            $zip->close();

            // grabbing the first ros file and moving it to the /tmp folder
            foreach (glob('/var/tmp/'.$fileId.'/*.ros') as $file) {
                rename($file, '/var/tmp/'.$fileId.'.ros');
                break;
            }
            unlink($inPath);
            delete_directory('/var/tmp/'.$fileId);
        } else {
            throw new Exception("Not a valid rosz file.");   
        }

        return Upload::ProcessRos('/var/tmp/'.$fileId.'.ros');    
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

