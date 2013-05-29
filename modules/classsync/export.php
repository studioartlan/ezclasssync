<?php

$http = eZHTTPTool::instance();
$Result = array();
$tpl = eZTemplate::factory();

if (empty($Params['classID']) && !$http->hasPostVariable('ExportIDArray')) {
    $classes = eZContentClass::fetchAllClasses();
    $tpl->setVariable('classes', $classes);
    $Result['content'] = $tpl->fetch('design:classsync/classlist.tpl');
} else {

    if (!empty($Params['classID'])) {
        $classesToExport = array($Params['classID']);
    } else {
        $classesToExport = $http->postVariable('ExportIDArray');
    }

    if (!empty($classesToExport)) {
        // create zip file
        $zipFileName = tempnam(sys_get_temp_dir(), "sync");
        $zip = new ZipArchive();
        if ($zip->open($zipFileName, ZIPARCHIVE::CREATE) !== TRUE) {
            $Result['content'] = 'Cannot create zip';
            return $module->handleError(eZError::KERNEL_NOT_AVAILABLE, 'kernel');
        }

        // now add all classes to export
        foreach ($classesToExport as $classID) {

            $contentClass = eZContentClass::fetch($classID);
            if ($contentClass !== null) {
                $sync = new eZClassSyncData($contentClass);
                $zip->addFromString(
                    $sync->getClassName() . '.json', json_encode($sync->export2json(), JSON_NUMERIC_CHECK)
                );
            }
        }

        // return zip
        $zip->close();

        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="class_export_json.zip"');
        header("Content-Length: " . filesize($zipFileName));
        readfile($zipFileName);

        // remove temp file
        unlink($zipFileName);
        ezExecution::cleanExit();
    } else {
        return $module->handleError(eZError::KERNEL_NOT_AVAILABLE, 'kernel');
    }
}


$Result['path'] = array(array('text' => 'Class Sync: Export'));