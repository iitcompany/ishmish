<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

define('STOP_STATISTICS', true);
define("NOT_CHECK_PERMISSIONS", true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('DisableEventsCheck', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('XHR_REQUEST', true);

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule('tasks');
CModule::IncludeModule('socialnetwork');

if ($_REQUEST['action']) {
    $arResult = array();
    switch ($_REQUEST['action']) {
        case 'get_group':
            $rs = CTasks::GetList(array(), array('ID' => $_REQUEST['task_id'], 'CHECK_PERMISSIONS' => 'N'), array('ID', 'GROUP_ID', 'TITLE'));
            while ($ar = $rs->Fetch()) {
                $arGroup = CSocNetGroup::GetByID($ar['GROUP_ID']);
                if ($arGroup['NAME']) {
                    $arResult[$ar['ID']] = htmlspecialchars_decode($arGroup['NAME'].': '.$ar['TITLE']);
                }
            }
            break;
    }
    echo json_encode($arResult);
    die();
}

