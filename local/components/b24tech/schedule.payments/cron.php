#!/usr/bin/php
<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

//блокировка повторного запуска
$lockFile = __FILE__.'.lock';
$hasFile = file_exists($lockFile);
$lockFp = fopen($lockFile, 'w');

if (!flock($lockFp, LOCK_EX | LOCK_NB)) {
    die('Sorry, one more script is running.');
}
if ($hasFile) {
    echo 'The previous running has been completed with an error.';
}

use Bitrix\Main\Loader;

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
set_time_limit(0);
define("SITE_ID", "s1");

$_SERVER["DOCUMENT_ROOT"] = "/home/bitrix/www";
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");



Loader::includeModule("crm");
Loader::includeModule("socialnetwork");

CBitrixComponent::includeComponentClass("b24tech:schedule.payments");

$periodEntityClass = GetEntityDataClass(SchedulePayments::HL_PERIOD);
$itemsEntityClass = GetEntityDataClass(SchedulePayments::HL);

$rs = CSocNetGroup::GetList(array(), array('CLOSED' => 'N'), array('ID', 'NAME'));

while ($arGroup = $rs->Fetch()) {
    $rsResult = $periodEntityClass::getList(
        array(
            'order' => array(
                'ID' => 'desc'
            ),
            'filter' => array(
                'UF_GROUP_ID' => $arGroup['ID'],
                'UF_AUTO_RENEWAL' => '86',
                'UF_DEFAULT' => 1,
               // 'UF_STATUS' => false
            )
        )
    );

    while ($arResult = $rsResult->fetch()) {
        $datetime = new DateTime($arResult['UF_DATE_END']);
        $closeDate = strtotime($datetime->format('Y-m-d'));

        $curdatetime = new DateTime(date('d-m-Y'));
        $currentDate = $curdatetime->modify('+2 days');
        $curDate = strtotime($currentDate->format('Y-m-d'));

        if ($closeDate <= $curDate) {
            $rsDefaultPeriod = $periodEntityClass::getList(
                array(
                    'filter' => array(
                        'UF_GROUP_ID' => $arGroup['ID'],
                        '!UF_DEFAULT' => '1',
                        //'UF_STATUS' => false
                    ),
                    'order' => array(
                        'UF_DATE_START' => 'DESC'
                    )
                )
            );
            $arLastPeriod = $rsDefaultPeriod->fetch();

            paymentLog($arGroup['ID'], 'GroupID:');
            paymentLog($arResult['ID'], 'PeriodID:');
            dump('GroupID: ' .$arGroup['ID']);
            dump('PeriodID: '.$arResult['ID']);

            if ($arLastPeriod['ID']) {
                dump('DefaultPeriodID: '.$arLastPeriod['ID']);
                paymentLog($arLastPeriod['ID'], 'DefaultPeriodID:');

                $datetimeperiodsstart = new DateTime($arLastPeriod['UF_DATE_START']);
                if ($datetimeperiodsstart->format('d') == '01') {
                    //$periodDateStart = $datetimeperiodsstart->modify('+1 day');
                }
                $periodDateStart = $datetimeperiodsstart->modify('+1 month');

                $datetimeperiodsend = new DateTime($arLastPeriod['UF_DATE_END']);
                $periodDateEnd = $datetimeperiodsend->modify('+1 month');

                $arPeriodFields = array(
                    'UF_DATE_START'   => $periodDateStart->format('d.m.Y'),
                    'UF_DATE_END'     => $periodDateEnd->format('d.m.Y'),
                    //'UF_PAYMENT_TYPE' => $arResult['UF_PAYMENT_TYPE'],
                    'UF_PAYMENT_TYPE' => 110,
                    //'UF_PAYMENT_PLAN' => $arDefaultPeriod['UF_PAYMENT_PLAN'],
                    'UF_PAYMENT_PLAN' => $arResult['UF_PAYMENT_PLAN'],
                    //'UF_PAYMENT_FACT' => $arDefaultPeriod['UF_PAYMENT_FACT'],
                    'UF_PAYMENT_FACT' => 0,
                    'UF_PAYMENT_DATE' => '',
                    'UF_AUTO_RENEWAL' => $arLastPeriod['UF_AUTO_RENEWAL'],
                    'UF_CLIENT_PAY'   => 107,
                    'UF_GROUP_ID'     => $arResult['UF_GROUP_ID'],
                    'UF_NAME'         => $arResult['UF_NAME'],
                    'UF_BALANCE'      => $arLastPeriod['UF_BALANCE'],
                    'UF_BALANCE_FACT' => 0,
                );

                $result = $periodEntityClass::add($arPeriodFields);
                if ($result->isSuccess()) {
                    $totalMustPay = 0;
                    $periodEntityClass::update($arLastPeriod['ID'], array('UF_STATUS' => 'Y'));
                    $PERIOD_ID = $result->getId();

                    $arPlatforms = SchedulePayments::getListValues(SchedulePayments::PLATFORM_ID);
                    $arTargets = SchedulePayments::getListValues(SchedulePayments::TARGET_ID);

                    foreach ($arPlatforms as $platform) {
                        foreach ($arTargets as $target) {
                            $mustPay = SchedulePayments::getMustPay($arLastPeriod['ID'], $platform['ID'], $target['ID']);
                            $totalMustPay = $totalMustPay + $mustPay;
                            $fields = array(
                                'UF_PERIOD' => $PERIOD_ID,
                                'UF_PLATFORM' => $platform['ID'],
                                'UF_TARGET' => $target['ID'],
                                'UF_BALANCE' => 0,
                                'UF_BALANCE_SPEND' => 0,
                                'UF_BUDGET_SPEND' => 0,
                                'UF_BUDGET_PERIOD' => 0,
                                'UF_SPEND_NDS' => 0,
                                'UF_PAID' => 0,
                                'UF_MUST_PAY' => $mustPay,
                                'UF_COMMENT' => ' ',
                                //'UF_CREDIT' => self::getDefaultValueByCode('UF_CREDIT')
                            );
                            $itemsEntityClass::add($fields);
                        }
                    }
                    $periodEntityClass::update($arResult['ID'], array('UF_BALANCE' => $arLastPeriod['UF_PAYMENT_PLAN'] - $totalMustPay));
                } else {
                    dump($result->getErrors());
                    paymentLog($result->getErrors(), 'Error: ');
                }
            } else {
                dump('DefaultPeriodID: не указан');
                paymentLog(array('не указан'), 'DefaultPeriodID: ');
            }
        }

        dump('----');
        paymentLog(array('----'), '');
    }


}

function paymentLog($data = array(), $type = '') {
    $log = '[' . date('D M d H:i:s Y', time()) . '] ';
    if ($type) {
        $log .= $type . ': ';
    }
    $log .= json_encode($data, JSON_UNESCAPED_UNICODE);
    $log .= "\n";
    file_put_contents(dirname(__FILE__) . "/payment_log_". date('d_m_Y') .".log", $log, FILE_APPEND);
}