<?php
require_once "../initTsugi.php";
require_once "../util/PHPExcel.php";

if ( $USER->instructor ) {

    $ct_id = $_SESSION["ct_id"];

    $main = new \CT\CT_Main($ct_id);
    $exercises = $main->getExercises();

    $rowCounter = 1;

    $exerciseTotal = count($exercises);

    $exportFile = new PHPExcel();

    $exportFile->setActiveSheetIndex(0)->setCellValue('A1', 'Student');
    $exportFile->setActiveSheetIndex(0)->setCellValue('B1', 'Username');
    $exportFile->setActiveSheetIndex(0)->setCellValue('C1', 'Date of Submission');

    $exportFile->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
    $exportFile->getActiveSheet()->getStyle('B1')->getFont()->setBold(true);
    $exportFile->getActiveSheet()->getStyle('C1')->getFont()->setBold(true);

    $exportFile->getActiveSheet()->getColumnDimension('A')->setWidth(20);
    $exportFile->getActiveSheet()->getColumnDimension('B')->setWidth(10);
    $exportFile->getActiveSheet()->getColumnDimension('C')->setWidth(25);

    $letters = range('C','Z');
    for($x = 1; $x<=$exerciseTotal; $x++){
        $col1 = $x * 2 + 1;
        $exportFile->getActiveSheet()->setCellValueByColumnAndRow($col1, $rowCounter, "Exercise ".$x);

        $cell_name = $letters[$x]."1";
        $exportFile->getActiveSheet()->getStyle($cell_name)->getFont()->setBold(true);
    }

    $StudentList = \CT\CT_User::getUsersWithAnswers($ct_id);

    $columnIterator = $exportFile->getActiveSheet()->getColumnIterator();
    $columnIterator->next();

    foreach ($StudentList as $student ) {
        if (!$student->isInstructor($CONTEXT->id)) {
            $rowCounter++;

            $Email = $student->getEmail();
            $UserName = explode("@",$Email);

            $Modified1 = $student->getMostRecentAnswerDate($ct_id);
            $Modified  =  new DateTime($Modified1);

            $displayName = $student->getDisplayname();
            $displayName = trim($displayName);

            $lastName = (strpos($displayName, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $displayName);
            $firstName = trim( preg_replace('#'.$lastName.'#', '', $displayName ) );

            $exportFile->getActiveSheet()->setCellValue('A'.$rowCounter, $lastName.', '.$firstName);

            $exportFile->getActiveSheet()->setCellValue('B'.$rowCounter, $UserName[0]);
            $exportFile->getActiveSheet()->setCellValue('C'.$rowCounter, $Modified->format('m/d/y - h:i A '));

            $col = 3;
            foreach ($exercises as $exercise ) {
                $QID = $exercise->getExerciseId();
                $A="";

                $answer = $student->getAnswerForExercise($QID, $ct_id);
                if (is_object($answer) && (!is_null($answer->getAnswerId()))) {
                    $A = $answer->getAnswerTxt();
                    $A = str_replace("&#39;", "'", $A);
                }
                $modifiedAnswer = $answer->getModified();
                $modifiedAnswerDate = new DateTime($modifiedAnswer);
                $exportFile->getActiveSheet()->setCellValueByColumnAndRow($col, $rowCounter, $A);
                $exportFile->getActiveSheet()->setCellValueByColumnAndRow($col + 1, $rowCounter, $modifiedAnswerDate->format('m/d/y - h:i A'));
                $col+=2;
            }
        }
    }
    $columnIterator->next();

    $exportFile->getActiveSheet()->setTitle('Code_Test');

    foreach($exportFile->getActiveSheet()->getColumnDimension() as $col) {
        $col->setAutoSize(true);
    }
    $exportFile->getActiveSheet()->calculateColumnWidths();

    $filename = 'CodeTest-'.$CONTEXT->title.'-Results.xls';

    // Redirect output to a client’s web browser (Excel5)
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename='.$filename);
    header('Cache-Control: max-age=0');
    // If you're serving to IE over SSL, then the following may be needed
    header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
    header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
    header ('Pragma: public'); // HTTP/1.0
    $objWriter = PHPExcel_IOFactory::createWriter($exportFile, 'Excel5');
    $objWriter->save('php://output');
} else {
    header( 'Location: '.addSession('../student-home.php')) ;
}


