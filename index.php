<?php

    require_once ('lib/FileUploader.php');

    $fu = new FileUploader();

    try
    {
        $fu->setUploadDirectory('upload/');
        $fu->setValidMIME(array('application/octet-stream',
                                'application/x-compressed', 'application/x-zip-compressed', 'application/zip', 'multipart/x-zip',
                                'image/jpeg', 'image/pjpeg', 'image/jpeg', 'image/pjpeg'));

        $fu->setValidExtensions(array('zip', 'jpg', 'jpeg'));
        $fu->setMaxFileSize(500000);

        $files = $fu->upload();

    /*
        foreach ($this->files as $files)
        {
            foreach ($files as $file)
            {
                //do with file what you want :)
            }
        }
    */

        $fu->moveUploadedFiles($files);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }

?>

<form method="post" action="" enctype="multipart/form-data">
<p>Upload File: <input name="uploadOne" type="file" class="inputtext" /></p>
<p>Upload File 1: <input name="upload[]" type="file" class="inputtext" /></p>
<p>Upload File 2: <input name="upload[]" type="file" class="inputtext" /></p>
<p>Upload2 File 1: <input name="upload2[]" type="file" class="inputtext" /></p>
<input type="submit" name="submit" value="Upload File" />
</form
