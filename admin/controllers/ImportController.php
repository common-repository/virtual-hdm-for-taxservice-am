<?php

function importTaxServiceData()
{
    if (!class_exists('importTaxServiceController')) {
        class ImportTaxServiceController
        {

            public function __construct()
            {
                $this->importFile();
            }

            public function importFile()
            {
                global $wpdb;

                try{
                    if (isset($_FILES) && !empty($_FILES)) {
                        $file = $_FILES['file'];
                        $path = $file['tmp_name'];
                        $filename = $file['name'];
                        $ext = pathinfo($filename, PATHINFO_EXTENSION);
                        if ($ext == "sql") {
                            $sql = file_get_contents($path);
                            $explodedSql = explode(';', $sql);
                            foreach ($explodedSql as $item) {
                                if (trim($item)) {
                                    $wpdb->get_results(trim($item));
                                }
                            }
                            echo json_encode(['success' => true, 'message' => 'ֆայլը հաջողությամբ ներբեռնված է']);
                            exit;
                        } else {
                            echo json_encode(['success' => false, 'message' => 'ֆայլի Ֆորմատի խնդիր']);
                            exit;
                        }

                    }
                }catch (\Exception $exception){
                    echo json_encode(['success' => false, 'message' => 'ֆայլի ներբեռնման խնդիր']);
                    exit;
                }
            }
        }
    }

    new ImportTaxServiceController();
}
