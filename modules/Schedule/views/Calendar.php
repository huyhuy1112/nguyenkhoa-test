<?php

class Schedule_Calendar_View extends Vtiger_Index_View {

    public function process(Vtiger_Request $request) {

        header("Location: index.php?module=Calendar&view=Calendar&app=SUPPORT");
        exit;
    }

}

