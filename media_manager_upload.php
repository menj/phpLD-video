<?php 
/*#################################################################*\
|# Licence Number 029O-0000-02T0-0200
|# -------------------------------------------------------------   #|
|# Copyright (c)2023 PHP Link Directory.                           #|
|# http://www.phplinkdirectory.com                                 #|
\*#################################################################*/
	 
/*#################################################################*\
|# Licence Number 0MWJ-0125-116F-0214
|# -------------------------------------------------------------   #|
|# Copyright (c)2014 PHP Link Directory.                           #|
|# http://www.phplinkdirectory.com                                 #|
\*#################################################################*/
	 

	 

require_once 'init.php';
/**
 * Handle file uploads via XMLHttpRequest
 */
class qqUploadedFileXhr {
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {    
        $input = fopen("php://input", "r");
        $temp = tmpfile();
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);
       

        //if ($realSize != $this->getSize()){            
        //    return false;
       // }
        
        $target = fopen($path, "w");        
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);
        
        return true;
    }
    function getName() {
        return $_GET['qqfile'];
    }
    function getSize() {
	return 100;
	return $_ENV['CONTENT_LENGTH'];
	/*
        if (isset($_SERVER["CONTENT_LENGTH"])){
            return (int)$_SERVER["CONTENT_LENGTH"];            
        } else {
	 return 0;
        }      
	*/
    }   
}

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class qqUploadedFileForm {  
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {
        if(!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)){
            return false;
        }
        return true;
    }
    function getName() {
        return $_FILES['qqfile']['name'];
    }
    function getSize() {
        return $_FILES['qqfile']['size'];
    }
}

class qqFileUploader {
    private $allowedExtensions = array();
    private $sizeLimit = 10485760;
    private $file;

    function __construct(array $allowedExtensions = array(), $sizeLimit = 10485760){
        $allowedExtensions = array_map("strtolower", $allowedExtensions);
            
        $this->allowedExtensions = $allowedExtensions;        
        $this->sizeLimit = $sizeLimit;       

        if (isset($_GET['qqfile'])) {
            $this->file = new qqUploadedFileXhr();
        } elseif (isset($_FILES['qqfile'])) {
            $this->file = new qqUploadedFileForm();
        } else {
            $this->file = false; 
        }
    }
    
    /**
     * Returns array('success'=>true) or array('error'=>'error message')
     */
    function handleUpload($uploadDirectory, $replaceOldFile = FALSE){
        if (!is_writable($uploadDirectory)){
            return array('error' => "Server error. Upload directory isn't writable. ($uploadDirectory)");
        }
        
        if (!$this->file){
            return array('error' => 'No files were uploaded.');
        }
        
        $size = $this->file->getSize();
        
        if ($size == 0) {
            return array('error' => 'File is empty');
        }
        
        if ($size > $this->sizeLimit) {
            return array('error' => 'File is too large');
        }
        
        $pathinfo = pathinfo($this->file->getName());
        $filename = $pathinfo['filename'];
        //$filename = md5(uniqid());
        $ext = $pathinfo['extension'];

        if($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)){
            $these = implode(', ', $this->allowedExtensions);
            return array('error' => 'File has an invalid extension, it should be one of '. $these . '.');
        }
        
        if(!$replaceOldFile){
            /// don't overwrite previous files that were uploaded
            while (file_exists($uploadDirectory . $filename . '.' . $ext)) {
                $filename .= rand(10, 99);
            }
        }
        
        if ($this->file->save($uploadDirectory . $filename . '.' . $ext)){
            return array('success'=>true, 'path' => $uploadDirectory . $filename . "." . $ext, 'filename' => $filename. "." . $ext);
        } else {
            return array('error'=> 'Could not save uploaded file.' .
                'The upload was cancelled, or server error encountered');
        }
        
    }    
}

session_start();


// list of valid extensions, ex. array("jpeg", "xml", "bmp")
//$save_path =dirname( __file__ ). "/uploads/tmp/";
$user_id='0';
if($_REQUEST['admin'])
{
  if(!empty($_SESSION['phpld']['adminpanel']['id']))
    $user_id = $_SESSION['phpld']['adminpanel']['id'];  
}
else{
if(!empty($_SESSION['phpld']['user']['id']))
    $user_id = $_SESSION['phpld']['user']['id'];
}

 if (!is_dir((dirname( __file__ ) . '/uploads/media/'))) {
    mkdir(dirname( __file__ ) . '/uploads/media');
}
 if (!is_dir((dirname( __file__ ) . '/uploads/media/' . $user_id))) {
        if (mkdir(dirname( __file__ ) . '/uploads/media/' . $user_id,0777, true)) {
    } else {
        print "Directory create Error: " . dirname( __file__ ) . '/uploads/media/' . $user_id;
    }
}
$save_path = dirname( __file__ ). "/uploads/media/".$user_id."/";

if(!is_dir($save_path))
    mkdir($save_path,0777);

$allowedExtensions = array('jpeg','jpg','gif','png');

// max file size in bytes
$sizeLimit = 6 * 1024 * 1024;

$uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
$result = $uploader->handleUpload($save_path);

if ($result['success']) {
    $item['ID'] = $db->GenID($tables['media_manager_items']['name'].'_SEQ');
    $item['FILE_NAME'] = $result['filename'];
    $item['USER_ID'] = $user_id;
    $item['TYPE'] = 'image';
    if (db_replace('media_manager_items', $item, 'ID')==0)
    unlink($result['success']);
    else{
	$result['file_path'] = "/".$user_id."/".$result['filename'];
	$image_size = getimagesize($result['path']);
	$result['IMAGE_WIDTH'] = $image_size[0] ;
	$result['IMAGE_HEIGHT'] = $image_size[1] ;
	$result['ID'] =  $item['ID'];
    }
}

// to pass data through iframe you will need to encode all html tags
echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);

