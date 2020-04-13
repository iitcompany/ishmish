<?
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */
use Bitrix\Main\Application;

define('STOP_STATISTICS', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('DisableEventsCheck', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('XHR_REQUEST', true);

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

$request = Application::getInstance()->getContext()->getRequest();

$arParams = $request->get('PARAMS') ?: array();

$APPLICATION->IncludeComponent(
    'b24tech:crm.deal.schedule.payments',
    '',
    $arParams
);