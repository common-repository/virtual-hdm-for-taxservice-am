<?php

function exportSettingsAndData()
{
    if (!class_exists('ExportTaxServiceController')) {
        class ExportTaxServiceController
        {

            public function __construct()
            {
                $this->exportFileAndSave();
            }

            public function exportFileAndSave()
            {
               $exportFilePath = ExportTaxService::exportSettingsAndData();
               $certificateFilePath = get_option('hkd_tax_service_upload_file_path');
               echo json_encode(['exportFilePath' => $exportFilePath,'certificateFilePath' => $certificateFilePath, 'success' => true]);
               exit;
            }
        }
    }

    new ExportTaxServiceController();
}
