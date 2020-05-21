<?
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

use Bitrix\Main\Loader;

function AutoPeriodsCreate()
{
    Loader::includeModule("crm");
    Loader::includeModule("socialnetwork");

    CBitrixComponent::includeComponentClass("b24tech:schedule.payments");

    $periodEntityClass = GetEntityDataClass(SchedulePayments::HL_PERIOD);
    $itemsEntityClass = GetEntityDataClass(SchedulePayments::HL);

    $rs = CSocNetGroup::GetList(array(), array('CLOSED' => 'N'), array('ID', 'NAME'));

    while ($arGroup = $rs->Fetch()) {
        //Получаем шаблон в группе
        $rsResult = $periodEntityClass::getList(
            array(
                'order' => array(
                    'ID' => 'desc'
                ),
                'filter' => array(
                    'UF_GROUP_ID' => $arGroup['ID'],
                    //'UF_GROUP_ID' => 3,
                    'UF_AUTO_RENEWAL' => 86,
                    'UF_DEFAULT' => 1
                )
            )
        );

        $arTemplatePeriod = $rsResult->fetch();
        //Если шаблон есть
        if ($arTemplatePeriod['ID']) {


            //Получаем последний период
            $rsPeriod = $periodEntityClass::getList(
                array(
                    'filter' => array(
                        'UF_GROUP_ID' => $arGroup['ID'],
                        //'UF_GROUP_ID' => 20,
                    ),
                    //'limit' => 1,
                    'order' => array(
                        'UF_DATE_START' => 'DESC'
                    )
                )
            );

            $arLastPeriod = $rsPeriod->fetch();
            //while ($arLastPeriod = $rsPeriod->fetch()) {

                /*dump('arGroup: '.$arGroup['ID']);
                dump('arTemplatePeriod: '.$arTemplatePeriod['ID']);
                dump('arLastPeriod: '.$arLastPeriod['ID']);
                dump('arLastPeriod: '.$arLastPeriod['UF_STATUS']);
                dump('arLastPeriod: '.$arLastPeriod['UF_DEFAULT']);
                dump('---------------');*/

                //if ($arLastPeriod['UF_STATUS'] != 'Y' && $arLastPeriod['UF_DEFAULT'] != '1') {
                if ($arLastPeriod['UF_STATUS'] != 'Y') {

                    //Перебераем все периоды
                    $endDate = new DateTime($arLastPeriod['UF_DATE_END']);
                    $endDate = strtotime($endDate->format('Y-m-d'));

                    $currentDate = new DateTime(date('d-m-Y'));
                    $currentDate = $currentDate->modify('+15 days');
                    $currentDate = strtotime($currentDate->format('Y-m-d'));

                    //Если дата завершения меньше текущей даты+15дней
                    if ($endDate <= $currentDate) {

                        $nextDateStart = new DateTime($arLastPeriod['UF_DATE_START']);
                        $nextDateStart = $nextDateStart->modify('+1 month');

                        $nextDateEnd = new DateTime($arLastPeriod['UF_DATE_END']);

                        $nextDateEndDay = $nextDateEnd->format('d');
                        $lastMonthDay = date('t', $nextDateEnd->format('d.m.Y'));

                        if ($nextDateEndDay == $lastMonthDay) {
                            $nextMonth = floatval($nextDateEnd->format('m'));
                            $nextYear = floatval($nextDateEnd->format('Y'));
                            $nextDay = floatval($nextDateEnd->format('d'));

                            if ($nextMonth == 12) {
                                $nextMonth = 1;
                                $nextYear = $nextYear + 1;
                            } else {
                                $nextMonth = $nextMonth + 1;
                                if ($nextMonth < 10) {
                                    $nextMonth = '0'.$nextMonth;
                                }
                            }

                            $nextLastDay = date("t", strtotime($nextYear.'-'.$nextMonth.'-01'));
                            $nextDateEnd = $nextLastDay.'.'.$nextMonth.'.'.$nextYear;

                        } else {
                            $nextDateEnd = $nextDateEnd->modify('+1 month');
                            $nextDateEnd = $nextDateEnd->format('d.m.Y');
                        }

                        //Формируем поля для нового периода
                        $arPeriodFields = array(
                            'UF_DATE_START' => $nextDateStart->format('d.m.Y'),
                            'UF_DATE_END' => $nextDateEnd,
                            //'UF_PAYMENT_TYPE' => $arResult['UF_PAYMENT_TYPE'],
                            'UF_PAYMENT_TYPE' => 110,
                            'UF_PAYMENT_PLAN' => $arTemplatePeriod['UF_PAYMENT_PLAN'],
                            //'UF_PAYMENT_FACT' => $arDefaultPeriod['UF_PAYMENT_FACT'],
                            'UF_PAYMENT_FACT' => 0,
                            'UF_PAYMENT_DATE' => '',
                            'UF_AUTO_RENEWAL' => $arLastPeriod['UF_AUTO_RENEWAL'],
                            'UF_CLIENT_PAY' => 107,
                            'UF_GROUP_ID' => $arTemplatePeriod['UF_GROUP_ID'],
                            'UF_NAME' => $arTemplatePeriod['UF_NAME'],
                            'UF_BALANCE' => $arTemplatePeriod['UF_BALANCE'],
                            'UF_BALANCE_FACT' => 0,
                            'UF_BALANCE_CREDIT' => 0,
                            'UF_CREDIT' => 0
                        );
                        $result = $periodEntityClass::add($arPeriodFields);
                        if ($result->isSuccess()) {
                            //Обновляем статус периода на обновленный
                            $periodEntityClass::update($arLastPeriod['ID'], array('UF_STATUS' => 'Y'));

                            $totalMustPay = 0;
                            $PERIOD_ID = $result->getId();

                            /*dump($arGroup['ID']);
                            dump($PERIOD_ID);
                            dump('----');*/

                            $arPlatforms = SchedulePayments::getListValues(SchedulePayments::PLATFORM_ID);
                            $arTargets = SchedulePayments::getListValues(SchedulePayments::TARGET_ID);

                            foreach ($arPlatforms as $platform) {
                                foreach ($arTargets as $target) {
                                    $mustPay = SchedulePayments::getRecordFieldValue('UF_MUST_PAY', $arLastPeriod['ID'], $platform['ID'], $target['ID']);
                                    $creditMoney = SchedulePayments::getRecordFieldValue('UF_CREDITMONEY', $arLastPeriod['ID'], $platform['ID'], $target['ID']);

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
                                        'UF_CREDITMONEY' => 0,
                                        'UF_COMMENT' => ' '
                                    );
                                    $itemsEntityClass::add($fields);
                                }
                            }
                            //$periodEntityClass::update($PERIOD_ID, array('UF_BALANCE_FACT' => $arLastPeriod['UF_PAYMENT_FACT'] - $totalMustPay));
                        }
                    }
                }
            //}
        }
    }
    return 'AutoPeriodsCreate();';
}

function getLastDayOfMonth($dateInISO8601)
{
    // Проверяем дату на корректность
    $date = explode('-', $dateInISO8601);
    if ( !checkdate ( $date[1] , $date[2] , $date[0] ) )
        return false;

    $start = new DateTime( $dateInISO8601 );
    $end = new DateTime( $dateInISO8601 );
    $end->add( new DateInterval( 'P2M' ) );
    $interval = new DateInterval( 'P1D' );
    $daterange = new DatePeriod($start, $interval, $end);

    $prev = $start;
    // Проходимся по периодам, если номер месяца
    // предыдущего периода не совпадает с текущим номером месяца
    // то возвращаем последний день предыдущего месяца
    foreach ($daterange as $date)
    {
        if ($prev->format('m') != $date->format('m') )
            return  (int) $prev->format('d');

        $prev = $date;
    }

    return false;
}

function paymentLog($data = array(), $type = '')
{
    $log = '[' . date('D M d H:i:s Y', time()) . '] ';
    if ($type) {
        $log .= $type . ': ';
    }
    $log .= json_encode($data, JSON_UNESCAPED_UNICODE);
    $log .= "\n";
    file_put_contents(dirname(__FILE__) . "/payment_log_" . date('d_m_Y') . ".log", $log, FILE_APPEND);
}

function CreateHappyBirthdayChat()
{
    $arWorkersCompany = [];
    $rsUsers = CUser::GetList(
        $by = 'personal_birthday', $order = 'asc',
        ['UF_DEPARTMENT' => ['1', '4'], 'ACTIVE' => 'Y'],
        ['FIELDS' => ['ID', 'NAME', 'LAST_NAME', 'PERSONAL_BIRTHDAY']]
    );
    $currentDate = new DateTime(date('d-m-Y'));
    $currentDate = $currentDate->modify('+7 days');
    $currentDate = strtotime($currentDate->format('Y-m-d'));
    while ($arUser = $rsUsers->Fetch()) {
        $arUser['IS_BIRTHDAY'] = 'N';
        if ($arUser['PERSONAL_BIRTHDAY_DATE']) {
            $birthDay = new DateTime(date("d-m", strtotime($arUser['PERSONAL_BIRTHDAY_DATE'])).'-'.date('Y'));
            $birthDay = strtotime($birthDay->format('Y-m-d'));
            if ($birthDay == $currentDate) {
                $arUser['IS_BIRTHDAY'] = 'Y';
            }
        }
        $arWorkersCompany[$arUser['ID']] = $arUser;
    }
        foreach ($arWorkersCompany as $arWorker) {
            if ($arWorker['IS_BIRTHDAY'] == 'Y') {
                Loader::includeModule('im');
                $chatTitle = 'День рождения сотрудника '.$arWorker['NAME'].' '.$arWorker['LAST_NAME'].' - '.$arWorker['PERSONAL_BIRTHDAY'];
                $arGuests = $arWorkersCompany;
                unset($arGuests[$arWorker['ID']]);
                $chat = new CIMChat;
                $chat->Add(array(
                    'TITLE' => $chatTitle,
                    'COLOR' => 'RED',
                    'TYPE' => IM_MESSAGE_CHAT,
                    'AUTHOR_ID' => '1',
                    'AVATAR_ID' => CFile::SaveFile(CFile::MakeFileArray($_SERVER['DOCUMENT_ROOT'] . '/local/img/birthday_logo.png'), 'im'),
                    'USERS' => array_keys($arGuests),
                ));
            }
        }

    return "CreateHappyBirthdayChat();";
}