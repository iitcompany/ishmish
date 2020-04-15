<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */
use Bitrix\Main\Loader,
    Bitrix\Highloadblock\HighloadBlockTable as HLBT;

Loader::includeModule("crm");
Loader::includeModule("iblock");
Loader::includeModule("sale");

class SchedulePayments extends CBitrixComponent
{
    const HL = 3;
    const HL_PERIOD = 5;
    const PLATFORM_ID = 143;
    const TARGET_ID = 145;

    public $LAST_ERROR;
    public $GROUP_ID;
    public $loadData = false;

    protected function prepareData()
    {
        global $APPLICATION;

        $group_id = isset($_REQUEST['url']) && strlen($_REQUEST['url']) ? preg_replace("/[^0-9]/", '', $_REQUEST['url']) : 0;

        $this->GROUP_ID = $group_id > 0 ? $group_id : $this->arParams['GROUP_ID'];
        $this->arResult = CSocNetGroup::GetByID($this->GROUP_ID);

        $this->arResult['FIELDS'] = $this->getTableFields();
        $this->arResult['PERIOD_FIELDS'] = $this->getPeriodFields();

        $arPeriods = array();
        foreach ($this->getPeriods() as $period) {

            $arItems = $this->getItemByPeriod($period['ID']);

            foreach ($arItems as $items) {
                foreach ($items as $item) {
                    $this->arResult['ITEMS'][$item['ID']] = $item;
                }
            }

            $arPeriods[$period['ID']] = $period;
            $arPeriods[$period['ID']]['ITEMS'] = $arItems;
        }
        $this->arResult['PERIODS'] = array_reverse($arPeriods, true);

        if ($this->arResult['ID']) {
            $APPLICATION->SetTitle('График платежей: ' . $this->arResult['NAME']);
        }
        $this->loadData = true;

        $arSumFields = array();
        foreach ($this->arResult['FIELDS'] as $FIELD) {
            if ($FIELD['CALC']) {
                $arSumFields[$FIELD['FIELD_NAME']] = 0;
            }
        }

        //Перебираем периоды
        foreach ($this->arResult['PERIODS'] as $key => $PERIOD) {
            //Перебераем записи
            foreach ($PERIOD['ITEMS'] as $k => $arItems) {

                $this->arResult['SUM'][$PERIOD['ID']][$k] = $arSumFields;

                //Перебераем поля
                foreach ($arItems as $arItem) {

                    //Перебераем поля калькуляции
                    foreach ($arSumFields as $CODE => $sum) {
                        if (isset($_REQUEST[$arItem['ID']][$CODE])) {
                            $arItem[$CODE] = floatval(str_replace(',','.', $_REQUEST[$arItem['ID']][$CODE]));
                        } else {
                            $arItem[$CODE] = isset($arItem[$CODE]) ? floatval($arItem[$CODE]) : 0;
                        }

                        $this->arResult['SUM'][$PERIOD['ID']][$k][$CODE] = $this->arResult['SUM'][$PERIOD['ID']][$k][$CODE] + $arItem[$CODE];
                    }


                    foreach ($arItem as $CODE => $vl) {
                        if ($CODE != 'ID' && $CODE != 'UF_PLATFORM') {
                            if (!isset($this->arResult['SUM'][$PERIOD['ID']][$k][$CODE])) {
                                $this->arResult['SUM'][$PERIOD['ID']][$k][$CODE] = ' ';
                            }
                        }
                    }
                }
            }
            $this->arResult['TOTAL_SUM'][$PERIOD['ID']] = $arSumFields;
            foreach ($this->arResult['SUM'][$PERIOD['ID']] as $id => $field) {
                foreach ($arSumFields as $CODE => $vl) {
                    if (isset($field[$CODE])) {
                        $field[$CODE] = $field[$CODE] ? $field[$CODE] : 0;
                        $this->arResult['TOTAL_SUM'][$PERIOD['ID']][$CODE] = $this->arResult['TOTAL_SUM'][$PERIOD['ID']][$CODE] + $field[$CODE];
                    }
                }
            }
        }
    }

    protected function getItemByPeriod($period_id) {
        $arResult = array();
        $entity = GetEntityDataClass(self::HL);
        $rsData = $entity::getList(array(
            'order' => array(
                'ID'=>'ASC'
            ),
            'select' => array('*'),
            'filter' => array('UF_PERIOD' => $period_id)
        ));
        while($el = $rsData->fetch()) {
            $arResult[$el['UF_PLATFORM']][] = $el;
        }
        return $arResult;
    }

    protected function checkCredit($period_id)
    {
        if ($this->loadData === false) {
            $this->prepareData();
        }

        $totalCreditMoney = $this->arResult['TOTAL_SUM'][$period_id]['UF_CREDITMONEY'];

        return $totalCreditMoney > 0 ? false : true;


    }
    public static function getRecordFieldValue($field_name, $period_id, $platforrm_id, $target_id)
    {
        $entity = GetEntityDataClass(self::HL);
        $rsData = $entity::getList(array(
            'order' => array(
                'ID'=>'ASC'
            ),
            'select' => array('*'),
            'filter' => array(
                'UF_PERIOD' => $period_id,
                'UF_PLATFORM' => $platforrm_id,
                'UF_TARGET' => $target_id,
            )
        ));
        while($el = $rsData->fetch()) {
            if (isset($el[$field_name]) && $el[$field_name] > 0) {
                return $el[$field_name];
            }
        }
        return 0;
    }
    protected function calculate($id, $period = false)
    {
        //if ($this->loadData === false) {
            $this->prepareData();
       // }


        if ($period == false) {
            $arRecord = $this->arResult['ITEMS'][$id];

            //калькуляция
            $arRecordFields = array();
            //$arRecord['UF_PAID'] = intval($arRecord['UF_PAID'], 2) > 0 ? intval($arRecord['UF_PAID'], 2) : 0;
            //$arRecord['UF_CREDITMONEY'] = intval($arRecord['UF_CREDITMONEY'], 2) > 0 ? intval($arRecord['UF_CREDITMONEY'], 2) : 0;

            switch ($arRecord['UF_PLATFORM']) {
                case '68': //vk
                    $arRecordFields['UF_SPEND_NDS'] = ($arRecord['UF_PAID'] + $arRecord['UF_CREDITMONEY']) * 0.99; // Тратим включая НДС
                    break;
                case '69': //fb
                case '70': //inst
                    $arRecordFields['UF_SPEND_NDS'] = ($arRecord['UF_PAID'] + $arRecord['UF_CREDITMONEY']) * 0.94 * 0.92 * 0.8; // Тратим включая НДС
                    break;
                case '71':
                    $arRecordFields['UF_SPEND_NDS'] = ($arRecord['UF_PAID'] + $arRecord['UF_CREDITMONEY']) / 1.2; // Тратим включая НДС
                    break;
            }
            $arRecordFields['UF_SPEND_NDS'] = round(floatval($arRecordFields['UF_SPEND_NDS']), 2);
            $arRecordFields['UF_BUDGET_PERIOD'] = $arRecordFields['UF_SPEND_NDS'] - $arRecord['UF_BUDGET_SPEND']; // Бюджет осталось в периоде
            //$arRecordFields['UF_BALANCE'] = ''; // Остатки остатков

            $entity = GetEntityDataClass(self::HL);

            $rs = $entity::update($id, $arRecordFields);
            /*dump($arRecord);
            dump($arRecordFields);
            dump('---');*/
        }

    }

    protected function getDefaultValueByCode($code)
    {
        if ($this->loadData === false) {
            $this->prepareData();
        }

        if (isset($this->arResult['FIELDS'][$code]['VALUES'])) {
            foreach ($this->arResult['FIELDS'][$code]['VALUES'] as $FIELD) {
                if ($FIELD['DEF'] == 'Y') {
                    return $FIELD['ID'];
                }
            }
        }
    }

    protected function getPeriodByID($period_id)
    {
        $entity = GetEntityDataClass(self::HL_PERIOD);
        $rsData = $entity::getList(array(
            'order' => array(
                'ID'=>'ASC'
            ),
            'select' => array('*'),
            'filter' => array('ID' => $period_id)
        ));
        return $rsData->fetch();
    }
    protected function getPeriods()
    {
        $arResult = array();
        $entity = GetEntityDataClass(self::HL_PERIOD);
        $rsData = $entity::getList(array(
            'order' => array(
                'UF_DATE_START'=>'ASC'
            ),
            'select' => array('*'),
            'filter' => array('UF_GROUP_ID' => $this->GROUP_ID)
        ));
        while($el = $rsData->fetch()){
            $arResult[$el['ID']] = $el;
        }

        return $arResult;
    }

    protected function getPeriodFields()
    {
        $arFields = array();
        $entity = 'HLBLOCK_' . self::HL_PERIOD;
        $dbRes = CUserTypeEntity::GetList(
            array(),
            array('ENTITY_ID' => $entity, 'LANG' => 'ru')
        );
        while ($arRes = $dbRes->Fetch()) {
            if ($arRes['SHOW_IN_LIST'] == 'Y') {
                if ($arRes['USER_TYPE_ID'] == 'enumeration') {
                    $arRes['VALUES'] = self::getListValues($arRes['ID']);
                }
                $arFields[$arRes['FIELD_NAME']] = $arRes;
            }
        }
        return $arFields;
    }

    protected function getTableFields()
    {
        $arFieldsCalc = array(
            'UF_BALANCE',
            'UF_BALANCE_SPEND',
            'UF_BUDGET_SPEND',
            'UF_BUDGET_PERIOD',
            'UF_SPEND_NDS',
            'UF_PAID',
            'UF_MUST_PAY',
            'UF_CREDITMONEY'
        );
        $arFields = array(
            'INDEX' => array(
                'USER_TYPE_ID' => 'none',
                'LIST_COLUMN_LABEL' => '№',
                'FIELD_NAME' => 'INDEX',
                'CALC' => false,
                'CALC_TITLE' => '<b>Итого:</b>'
            )
        );
        $entity = 'HLBLOCK_' . self::HL;
        $dbRes = CUserTypeEntity::GetList(
            array('SORT' => 'ASC'),
            array('ENTITY_ID' => $entity, 'LANG' => 'ru')
        );
        while ($arRes = $dbRes->Fetch()) {
            if ($arRes['SHOW_IN_LIST'] == 'Y') {
                if ($arRes['USER_TYPE_ID'] == 'enumeration') {
                    $arRes['VALUES'] = self::getListValues($arRes['ID']);
                }
                $calc = false;
                foreach ($arFieldsCalc as $CODE) {
                    if ($CODE == $arRes['FIELD_NAME']) {
                        $calc = true;
                    }
                }
                $arRes['CALC'] = $calc;
                $arFields[$arRes['FIELD_NAME']] = $arRes;
            }
        }
        return $arFields;
    }

    public static function getListValues($id)
    {
        $arResult = array();
        $obEnum = new CUserFieldEnum;
        $rsEnum = $obEnum->GetList(array(), array("USER_FIELD_ID" => $id));
        while ($ar = $rsEnum->Fetch()) {
            $arResult[$ar['ID']] = $ar;
        }
        return $arResult;
    }

    public static function getSchedulePayment($arFilter = array(), $ID = false)
    {
        if ($ID > 0) {
            $arFilter['ID'] = $ID;
        }
        $entity = GetEntityDataClass(self::HL);
        $rsData = $entity::getList(array(
            'order' => array(
                'UF_INVOICE_DATE'=>'ASC'
            ),
            'select' => array('*'),
            'filter' => $arFilter
        ));

        return $rsData->fetch();
    }

    protected function getSchedulePaymentsList($dealID)
    {
        $arSchedulePaymentsList = array();
        $entity = GetEntityDataClass(self::HL);
        $rsData = $entity::getList(array(
            'order' => array(
                'ID'=>'ASC'
            ),
            'select' => array('*'),
            'filter' => array(
                'UF_DEAL_ID' => $dealID
            )
        ));
        while($el = $rsData->fetch()){
            $arSchedulePaymentsList[$el['ID']] = $el;
        }
        return $arSchedulePaymentsList;
    }

    public function checkRules($isPeriod = false)
    {
        $this->prepareData();
        return $this->arResult;
    }

    public function duplicatePeriod($id)
    {
        $arPeriodFields = $this->getPeriodByID($id);
        if (strlen($arPeriodFields['UF_NAME']) > 0) {
            $arPeriodFields['UF_NAME'] .= ' (Копия)';
        }
        if ($arPeriodFields['UF_DEFAULT'] == 1) {
            $arPeriodFields['UF_DEFAULT'] = 0;
        }
        unset($arPeriodFields['ID']);
        $entityPeriod = GetEntityDataClass(self::HL_PERIOD);
        $entity = GetEntityDataClass(self::HL);
        $arRes = $entityPeriod::add($arPeriodFields);
        if ($arRes->isSuccess()) {
            $PERIOD_ID = $arRes->getId();
            $arPeriodItems = $this->getItemByPeriod($_REQUEST['id']);
            foreach ($arPeriodItems as $UF_PLATFORM => $arItems) {
                foreach ($arItems as $UF_TARGET => $arItem) {
                    unset($arItem['ID']);
                    $arItem['UF_PERIOD'] = $PERIOD_ID;
                    $entity::add($arItem);
                }
            }
            return true;
        } else {
            return $arRes->getErrorMessages();
        }
    }

    public function executeComponent()
    {
        if (isset($_REQUEST['action'])) {
            global $APPLICATION;
            $APPLICATION->RestartBuffer();
            $arResponse = array();
            $entity = GetEntityDataClass(self::HL);
            $entityPeriod = GetEntityDataClass(self::HL_PERIOD);

            $arFields = array();
            foreach ($_REQUEST as $code => $value) {
                if (strlen($value) > 0) {
                    if (stripos($code, 'UF_') !== false) {
                        $isDate = false;
                        if (stripos($code, 'DATE') !== false) {
                            $value = date_format(date_create($value), 'd.m.Y');

                        } else {
                            $value = floatval(str_replace(',', '.', $value));
                        }
                        $arFields[$code] = $value;
                    }
                }
            }
            $arFields['UF_BALANCE'] = 0;
            switch ($_REQUEST['action']) {
                case 'create_period':
                    $arFields['UF_BALANCE'] = $arFields['UF_PAYMENT_PLAN'] ?: 0;
                    $arFields['UF_BALANCE_FACT'] = $arFields['UF_PAYMENT_FACT'] ?: 0;
                    $arFields['UF_PAYMENT_TYPE'] = 110;
                    $arFields['UF_CLIENT_PAY'] = 107;

                    $result = $entityPeriod::add($arFields);
                    $arPlatforms = self::getListValues(self::PLATFORM_ID);
                    $arTargets = self::getListValues(self::TARGET_ID);

                    if (!$result->isSuccess()) {
                        $arResponse['result'] = false;
                        $arResponse['message'] = $result->getErrorMessages();
                    } else {
                        $period_id = $result->getId();

                        foreach ($arPlatforms as $platform) {
                            foreach ($arTargets as $target) {
                                $fields = array(
                                    'UF_PERIOD' => $period_id,
                                    'UF_PLATFORM' => $platform['ID'],
                                    'UF_TARGET' => $target['ID'],
                                    'UF_BALANCE' => 0,
                                    'UF_BALANCE_SPEND' => 0,
                                    'UF_BUDGET_SPEND' => 0,
                                    'UF_BUDGET_PERIOD' => 0,
                                    'UF_SPEND_NDS' => 0,
                                    'UF_PAID' => 0,
                                    'UF_MUST_PAY' => 0,
                                    'UF_CREDITMONEY' => 0,
                                    'UF_COMMENT' => ' ',
                                    'UF_CREDIT' => self::getDefaultValueByCode('UF_CREDIT')
                                );
                                $entity::add($fields);
                            }
                        }

                        $arResponse['result'] = true;
                        $arResponse['message'] = '';
                    }
                    break;
                case 'update_period':
                    $this->prepareData();
                    $error = false;
                    $mustPayTotal = $this->arResult['TOTAL_SUM'][$_REQUEST['period_id']]['UF_MUST_PAY'];
                    $paidTotal = $this->arResult['TOTAL_SUM'][$_REQUEST['period_id']]['UF_PAID'];
                    $creditTotal = $this->arResult['TOTAL_SUM'][$_REQUEST['period_id']]['UF_CREDITMONEY'];

                    if ($arFields['UF_CLIENT_PAY'] == 106) {
                        $arRequiredFields = array(
                            'UF_PAYMENT_FACT', 'UF_PAYMENT_DATE'
                        );
                        foreach ($arRequiredFields as $code) {
                            if (!isset($arFields[$code]) || empty($arFields[$code])) {
                                $error = true;
                                $arResponse['message'][$code] = 'Это поле обязательно для заполнения!';
                            }
                            if ($code == 'UF_PAYMENT_FACT' && floatval($arFields[$code]) < 0) {
                                $error = true;
                                $arResponse['message'][$code] = 'Это поле не может быть меньше 0!';
                            }
                        }
                        if (self::checkCredit($_REQUEST['period_id']) === false) {
                            //$error = true;
                            //$arResponse['message']['system'] = 'Обратите внимание на поле "Кредит"!';
                        }
                    } elseif ($arFields['UF_CLIENT_PAY'] == 107) {
                        $arFields['UF_BALANCE_FACT'] = 0;
                        $arFields['UF_PAYMENT_FACT'] = 0;
                        $arFields['UF_PAYMENT_DATE'] = '';
                        foreach ($this->arResult['PERIODS'][$_REQUEST['period_id']]['ITEMS'] as $arItems) {
                            foreach ($arItems as $arItem) {
                                $entity::update($arItem['ID'], array(
                                        'UF_PAID' => 0,
                                        'UF_SPEND_NDS' => 0
                                    )
                                );
                            }
                        }
                    }

                    if ($creditTotal > $arFields['UF_CREDIT']) {
                        $error = true;
                        $arResponse['message']['UF_CREDIT'] = 'Поле "Кредит" не может быть меньше суммы полей "Кредит"!';
                    }

                    if ($arFields['UF_PAYMENT_PLAN'] == 0 && $mustPayTotal > 0) {
                        $error = true;
                        $arResponse['message']['UF_PAYMENT_PLAN'] = 'Поле "Оплата - план" не может быть меньше суммы полей "Должны оплатить"!';
                    }

                    $arFields['UF_BALANCE'] = round(floatval($arFields['UF_PAYMENT_PLAN'] - $mustPayTotal), 2);

                    if ($arFields['UF_PAYMENT_FACT']) {
                        $arFields['UF_BALANCE_FACT'] = round(floatval($arFields['UF_PAYMENT_FACT']) - $paidTotal, 2);
                    }
                    if ($arFields['UF_PAYMENT_FACT'] > $arFields['UF_PAYMENT_PLAN']) {
                        $error = true;
                        $arResponse['message']['UF_PAYMENT_FACT'] = 'Поле "Оплата - факт" не может быть больше поля "Оплата - план"!';
                    }
                    if ($arFields['UF_CLIENT_PAY'] == 106 && $arFields['UF_PAYMENT_FACT'] == 0) {
                        $error = true;
                        $arResponse['message']['UF_PAYMENT_FACT'] = 'Измените поле "Оплата - факт"!';
                    }

                    if ($error == false) {
                        $result = $entityPeriod::update($_REQUEST['period_id'], $arFields);
                        if (!$result->isSuccess()) {
                            $arResponse['result'] = false;
                            $arResponse['message'] = $result->getErrorMessages();
                        } else {
                            $arResponse['result'] = true;
                            $arResponse['message'] = '';
                        }
                    } else {
                        $arResponse['result'] = false;
                    }

                    break;
                case 'copy_period':
                    $rs = $this->duplicatePeriod($_REQUEST['id']);
                    $arResponse['result'] = $rs;
                    $arResponse['message'] = $rs == true ? 'Период успешно сопирован!' : $rs;
                    break;
                case 'delete_period':
                    foreach ($this->getItemByPeriod($_REQUEST['period_id']) as $items) {
                        foreach ($items as $item) {
                            $entity::delete($item['ID']);
                        }
                    };

                    $result = $entityPeriod::delete($_REQUEST['period_id']);
                    if (!$result->isSuccess()) {
                        $arResponse['result'] = false;
                        $arResponse['message'] = $result->getErrorMessages();
                    } else {
                        $arResponse['result'] = true;
                        $arResponse['message'] = '';
                    }
                    break;
                case 'save':
                    $this->prepareData();
                    $arPeriod = $this->getPeriodByID($_REQUEST['period_id']);

                   // $creditTotal = $this->arResult['TOTAL_SUM'][$_REQUEST['period_id']]['UF_CREDITMONEY'];
                    $paidTotal = $this->arResult['TOTAL_SUM'][$_REQUEST['period_id']]['UF_PAID'];
                    $mustPayTotal = $this->arResult['TOTAL_SUM'][$_REQUEST['period_id']]['UF_MUST_PAY'];
                    $creditTotal = $this->arResult['TOTAL_SUM'][$_REQUEST['period_id']]['UF_CREDITMONEY'];

                    $paymentPlan = floatval($this->arResult['PERIODS'][$_REQUEST['period_id']]['UF_PAYMENT_PLAN']);
                    $paymentFact = floatval($this->arResult['PERIODS'][$_REQUEST['period_id']]['UF_PAYMENT_FACT']);
                    
                    $balancePlan = $paymentPlan - $mustPayTotal;
                    $balanceFact = $paymentFact - $paidTotal;

                    $totalSum = round($paidTotal, 2);

                    $isError = false;
                    if (floatval($creditTotal) > floatval($arPeriod['UF_CREDIT'])) {
                        $isError = true;
                        $arResponse['message']['system'] = 'Сумма полей "Кредит" не может быть больше поля "Кредит"!';
                    }
                    if (floatval($mustPayTotal) > floatval($arPeriod['UF_PAYMENT_PLAN'])) {
                        $isError = true;
                        $arResponse['message']['system'] = 'Сумма полей "Должны оплатить" не может быть больше поля "Оплачено - план"!';
                    }
                    if (floatval($creditTotal + $paymentFact) > floatval($arPeriod['UF_PAYMENT_PLAN'])) {
                        $isError = true;
                        $arResponse['message']['system'] = 'Сумма полей "Кредит" и "Оплачено" не может быть больше поля "Оплачено - план"!';
                    }
                    if (floatval($totalSum) > floatval($arPeriod['UF_PAYMENT_PLAN'])) {
                        $isError = true;
                        $arResponse['message']['system'] = 'Сумма полей "Оплачено" не может быть больше поля "Оплата - план"!';
                    }
                    if (floatval($totalSum) > floatval($arPeriod['UF_PAYMENT_FACT'])) {
                        $isError = true;
                        $arResponse['message']['system'] = 'Сумма полей "Оплачено" не может быть больше поля "Оплата - факт"!';
                    }

                    if ($isError === false) {
                        foreach ($_REQUEST as $key => $arFields) {
                            if ($key !== 'action' && $key !== 'period_id' && $key !== 'url') {
                                $error = false;

                                foreach ($arFields as $code => &$arField) {
                                    $arField = str_replace(',','.', $arField);

                                    switch ($code) {
                                        case 'UF_MUST_PAY':
                                        case 'UF_PAID':
                                        case 'UF_CREDITMONEY':
                                            $arField = floatval($arField);
                                            if ($arField < 0) {
                                                $error = true;
                                                $arResponse['message'][$code] = 'Не может быть отрицательным значением!';
                                            } else {
                                                $arField = round($arField, 2);
                                            }
                                            break;
                                    }
                                    if ($code == 'UF_PAID') {
                                        if (round($arPeriod['UF_PAYMENT_FACT'], 2) <= 0 && $arField > 0) {
                                            $error = true;
                                            $arResponse['message']['system'] = 'Ошибка, поле "План-факт" должно быть заполнено!';
                                        } else {
                                            $paidTotal = $paidTotal + round($arField, 2);
                                        }
                                    }
                                }
                                if ($error === false) {
                                    $result = $entity::update($key, $arFields);
                                    $this->calculate($key);
                                    if (!$result->isSuccess()) {
                                        $arResponse['result'] = false;
                                        $arResponse['message'][] = $result->getErrorMessages();
                                    } else {
                                        $entityPeriod::update($_REQUEST['period_id'],
                                            array(
                                                'UF_BALANCE' => round(floatval($balancePlan), 2),
                                                'UF_BALANCE_FACT' => round(floatval($balanceFact), 2),
                                            )
                                        );
                                        $arResponse['result'] = true;
                                        $arResponse['message'] = '';
                                    }
                                } else {
                                    $arResponse['result'] = false;
                                }
                            }
                        }
                    } else {
                        $arResponse['result'] = false;
                    }

                    break;
                case 'set_default':
                    $this->prepareData();
                    foreach ($this->arResult['PERIODS'] as $PERIOD) {
                        //if ($PERIOD['UF_DEFAULT'] == '1') {
                            $entityPeriod::update($PERIOD['ID'], array('UF_DEFAULT' => 0, 'UF_AUTO_RENEWAL' => 87));
                        //}
                    }
                    $entityPeriod::update($_REQUEST['id'], array('UF_DEFAULT' => 1,  'UF_AUTO_RENEWAL' => 86));
                    $arResponse['result'] = true;
                    $arResponse['message'] = 'Шаблон установлен!';
                    break;
                case 'update':
                    $this->prepareData();
                    $this->includeComponentTemplate();
                    break;
                case 'delete':
                    $result = $entity::delete($_REQUEST['id']);
                    if (!$result->isSuccess()) {
                        $arResponse['result'] = false;
                        $arResponse['message'] = $result->getErrorMessages();
                    } else {
                        $arResponse['result'] = true;
                        $arResponse['message'] = '';
                    }
                    break;

            }
            if ($arResponse) {
                echo json_encode($arResponse);
            }
            die();
        } else {
            $this->prepareData();
            $this->includeComponentTemplate();
        }
    }
}