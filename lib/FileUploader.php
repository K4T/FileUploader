<?php
 
class FileUploader {

    private $uploadDirectory;
    private $validMIME;
    private $validExtensions;
    private $maxFileSize;

    private $files;

    public function upload($moveFiles = false)
    {
        $this->parse();
        $this->areFilesUploaded();
        $this->getFilesToUpload();
        $this->verifyMIME();
        $this->verifyExtensions();
        $this->verifyMaxFileSize();

        return ($moveFiles) ? $this->moveUploadedFiles($this->files()) : $this->files();
    }

    public function moveUploadedFiles($files)
    {
        if(!$this->uploadDirectory)
        {
            throw new Exception('Upload directory is not specified!');
        }

        foreach ($files as $file)
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
        return count($this->files);
    }

    public function files()
    {
        return $this->files;
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
        $this->validMIME = $MIME;
    }

    public function setValidExtensions($extensions = array())
    {
        $this->validExtensions = $extensions;
    }

    public function setMaxFileSize($size)
    {
        $this->maxFileSize = $size;
    }

    private function parse()
    {
        $keys = array_keys($_FILES);

        foreach (array('name', 'type', 'tmp_name', 'error', 'size') as $field)
        {
            $i = 0;
            foreach ($_FILES[$keys[0]][$field] as $value)
            {
                $this->files[$i][$field] = $value;
                $i++;
            }
        }
    }

    private function areFilesUploaded()
    {
        $filesWithError = array();
        $exceptionString = 'Upload failed: <br />';

        foreach ($this->files as $file)
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
                $exceptionString .= 'File: '.$file['name'].' (error code: '.$file['error'].') <br />';
            }

            Throw new Exception ($exceptionString);
        }

        return true;
    }

    private function getFilesToUpload()
    {
        $files = array();

        foreach ($this->files as $file)
        {
            if ($file['error'] != UPLOAD_ERR_NO_FILE)
            {
                $files[] = $file;
            }
        }

        $this->files = $files;
    }

    private function verifyMIME()
    {
        if (!$this->validMIME)
        {
            return;
        }

        $filesWithError = array();
        $exceptionString = 'Upload failed: <br />';

        foreach ($this->files as $file)
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
                $exceptionString .= 'File: '.$file['name'].' is not allowed to upload. <br />';
            }

            Throw new Exception ($exceptionString);
        }
    }

    private function verifyExtensions()
    {
        if (!$this->validExtensions)
        {
            return;
        }

        $filesWithError = array();
        $exceptionString = 'Upload failed: <br />';

        foreach ($this->files as $file)
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
                $exceptionString .= 'File: '.$file['name'].' have got extension which is not allowed. <br />';
            }

            Throw new Exception ($exceptionString);
        }
    }

    private function verifyMaxFileSize()
    {
        if (!$this->maxFileSize)
        {
            return;
        }

        $filesWithError = array();
        $exceptionString = 'Upload failed: <br />';

        foreach ($this->files as $file)
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
                $exceptionString .= 'File: '.$file['name'].' is bigger than maximum allowed size: '.$this->maxFileSize.'b. <br />';
            }

            Throw new Exception ($exceptionString);
        }
    }

    private function fileExtension($filename)
    {
        $tmp = explode('.', $filename);
        return end($tmp);
    }
}

?>
