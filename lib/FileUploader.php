<?php
 
class FileUploader {

    private $uploadDirectory;
    private $validMIME;
    private $validExtensions;
    private $maxFileSize;

    private $files;

    private $errorsList = array();

    public function upload($moveFiles = false)
    {
        try
        {
            $this->parse();
        }
        catch (Exception $e)
        {
            //no files to upload
            return false;
        }

        $this->areFilesUploaded();
        $this->getFilesToUpload();
        $this->verifyMIME();
        $this->verifyExtensions();
        $this->verifyMaxFileSize();

        return ($moveFiles) ? $this->moveUploadedFiles($this->files()) : $this->files();
    }

    public function moveUploadedFiles($filesToMove)
    {
        if(!$filesToMove)
        {
            return false;
        }

        if(!$this->uploadDirectory)
        {
            throw new Exception('Upload directory is not specified!');
        }

        foreach ($filesToMove as $file)
        {
            if (!isset($file['new_name']))
            {
                $file['new_name'] = $file['name'];
            }

            //TODO: better error checking
            if (is_uploaded_file($file['tmp_name']))
            {
                move_uploaded_file($file['tmp_name'], $this->uploadDirectory.$file['new_name']);
            }
        }

        return true;
    }

    public function count()
    {
        return count($this->files());
    }

    public function files()
    {
        return (empty($this->files)) ? null : $this->files;
    }

    public function setUploadDirectory($path)
    {
        if (!is_dir($path) || !is_writable($path))
        {
            throw new Exception('Specified upload directory is not a directory or is not writable!');
        }

        $this->uploadDirectory = $path;
    }

    public function setValidMIME($MIME = array())
    {
        $this->validMIME = array_map('strtolower', $MIME);
    }

    public function setValidExtensions($extensions = array())
    {
        $this->validExtensions = array_map('strtolower', $extensions);
    }

    public function setMaxFileSize($size)
    {
        $this->maxFileSize = $size;
    }

    public function clearErrorsList()
    {
        $this->errorsList = array();
    }

    public function getErrors()
    {
        return $this->errorsList;
    }

    private function parse()
    {
        $this->files = array();

        if ($this->isFILESArrayEmpty())
        {
            Throw new Exception ('There is no files to upload!');
        }

        foreach(array_keys($_FILES) as $key)
        {
            $i = $this->count();

            if (is_array($_FILES[$key]['name']))
            {
                //file input`s name parameter was an array
                foreach (array('name', 'type', 'tmp_name', 'error', 'size') as $field)
                {
                    $j = $i;

                    foreach ($_FILES[$key][$field] as $value)
                    {
                        //TODO: move $this->files[$j]['input_name'] = $key; outside foreach
                        $this->files[$j]['input_name'] = $key;
                        $this->files[$j][$field] = $value;
                        $j++;
                    }
                }
            }
            else
            {
                //file input`s name parameter was not an array
                $this->files[$i]['input_name'] = $key;
                foreach ($_FILES[$key] as $key2 => $value)
                {
                    $this->files[$i][$key2] = $value;
                }
            }
        }
    }

    private function areFilesUploaded()
    {
        $filesWithError = array();

        foreach ($this->files() as $file)
        {
            if ($file['error'] != UPLOAD_ERR_OK && $file['error'] != UPLOAD_ERR_NO_FILE)
            {
                $filesWithError[] = $file;
            }
        }

        if (!empty($filesWithError))
        {
            foreach ($filesWithError as $file)
            {
                $this->addError('File: '.$file['name'].' (error code: '.$file['error'].')');
            }

            Throw new Exception ('Not all files were uploaded correctly!');
        }

        return true;
    }

    private function getFilesToUpload()
    {
        $filesToUpload = array();

        foreach ($this->files() as $file)
        {
            if ($file['error'] != UPLOAD_ERR_NO_FILE)
            {
                $filesToUpload[] = $file;
            }
        }

        $this->files = $filesToUpload;
    }

    private function verifyMIME()
    {
        if (!$this->validMIME)
        {
            return;
        }

        $filesWithError = array();

        foreach ($this->files() as $file)
        {
            if (!in_array($file['type'], $this->validMIME))
            {
                $filesWithError[] = $file;
            }
        }

        if (!empty($filesWithError))
        {
            foreach ($filesWithError as $file)
            {
                $this->addError('File: '.$file['name'].' is not allowed to upload (wrong MIME type).');
            }

            Throw new Exception ('Some of uploaded files have wrong MIME type!');
        }
    }

    private function verifyExtensions()
    {
        if (!$this->validExtensions)
        {
            return;
        }

        $filesWithError = array();

        foreach ($this->files() as $file)
        {
            if (!in_array($this->fileExtension($file['name']), $this->validExtensions))
            {
                $filesWithError[] = $file;
            }
        }

        if (!empty($filesWithError))
        {
            foreach ($filesWithError as $file)
            {
                $this->addError('File: '.$file['name'].' have got extension which is not allowed.');
            }

            Throw new Exception ('Some of uploaded files have wrong extension!');
        }
    }

    private function verifyMaxFileSize()
    {
        if (!$this->maxFileSize)
        {
            return;
        }

        $filesWithError = array();

        foreach ($this->files() as $file)
        {
            if ($file['size'] > $this->maxFileSize)
            {
                $filesWithError[] = $file;
            }
        }

        if (!empty($filesWithError))
        {
            foreach ($filesWithError as $file)
            {
                $this->addError('File: '.$file['name'].' is bigger than maximum allowed size: '.$this->maxFileSize.'b.');
            }

            Throw new Exception ('Some of uploaded files are too big.');
        }
    }

    private function isFILESArrayEmpty()
    {
        foreach(array_keys($_FILES) as $key)
        {
            //file input`s name parameter was an array
            if (is_array($_FILES[$key]['name']))
            {
                foreach($_FILES[$key]['name'] as $value)
                {
                    if (!empty($value))
                    {
                        //some file was sent
                        return false;
                    }
                }
            }
            else
            {
                if (!empty($_FILES[$key]['name']))
                {
                    //some file was sent
                    return false;
                }
            }
        }

        return true;
    }

    private function addError($errorMessage)
    {
        if ($errorMessage != '')
        {
            $this->errorsList[] = array("message" => $errorMessage);
        }
    }

    private function fileExtension($filename)
    {
        $tmp = explode('.', strtolower($filename));
        return end($tmp);
    }
}

?>
