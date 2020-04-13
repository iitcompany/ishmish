<?php
namespace Bitrix\Sale\PaySystem;

use Bitrix\Sale\Internals\Input\Date;
use Bitrix\Sale\Order,
    Bitrix\Sale\Registry,
    Bitrix\Sale\BusinessValue,
    Bitrix\Sale\PaySystem,
    Bitrix\Main\Application,
    Bitrix\Highloadblock\HighloadBlockTable as HLBT;

define("NOT_CHECK_PERMISSIONS", true);
define('STOP_STATISTICS', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('DisableEventsCheck', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('XHR_REQUEST', true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
global $USER_FIELD_MANAGER;

const RECEIPT_QUEUE_HL = 7;

\Bitrix\Main\Loader::includeModule("sale");
\Bitrix\Main\Loader::includeModule("crm");

$arRequest = (array)json_decode(file_get_contents("php://input"), true);

if (isset($arRequest) && is_array($arRequest) && !empty($arRequest))
{
    //Логируем полученные данные
    paymentLog($arRequest, 'Request');

    //Формируем данные
    $paymentTxID = $arRequest['tx_id'];                                                       //ID платежа с _
    $paymentExtID  = $arRequest['payment_id'];                                                //ID платежа платежной системы
    $paymentBxID = (int) preg_replace('/_\d{1,4}$/', '', $paymentTxID);    //ID платежа с системе Битрикс24
    $amount = $arRequest['base_amount'] ?: 0;                                                 //Сумма
    $signature = $arRequest['signature'] ?: '';                                               //Проверочная подпись
    $orderId = $paymentBxID;

    //Если есть данные по чеку подключаем обработчик по чекам
    if (isset($arRequest['receipt_data']) && is_array($arRequest['receipt_data']))
    {
        //Логируем полученные данные
        paymentLog(array(), 'Успех receipt callback');

        $receiptData = $arRequest["receipt_data"];
        $fields = $receiptData['fields'];
        $paymoPaymentID = $fields['payment'];

        //Логируем callback в HL блоки
        paymentLogHL($paymentBxID, 'receipt', $arRequest);

        //Получаем счет
        $arInvoice = \CCrmInvoice::GetByID($orderId, false);

        $checkQueue = GetEntityDataClassHL(7);

        //добавление в очередь на получение чека
        $rs = $checkQueue::add(array(
            "UF_JSON_DATA" => json_encode($arRequest),
            "UF_DEAL_ID" => $arInvoice['UF_DEAL_ID'],
            "UF_INVOICE_ID" => $orderId,
            "UF_PAYMO_PAYMENT_ID" => $paymoPaymentID,
            "UF_DOCUMENT_NUMBER" => $fields['document_number'],
            "UF_FS_NUMBER" => $fields['fs_number'],
            "UF_STATUS" => "N",
        ));
        if ($rs) {
            //Логируем полученные данные
            paymentLog(array(), 'Добавлена запись для получения чека');
        } else {
            //Логируем полученные данные
            paymentLog(array(), 'Ошибка создания записи для получения чека');
        }
        exit(json_encode(array("result" => true)));
    }
    else
    {
        $registry = Registry::getInstance('CRM_INVOICE');
        $orderClassName = $registry->getOrderClassName();


        $bCorrectPayment = true;
        if (!($order = $orderClassName::load($paymentBxID)) )
        {
            $bCorrectPayment = false;
            $errorMessage = $paymentBxID . ' is not found';

            //Логируем полученные данные
            paymentLog(array(), 'Ошибка ' . $paymentBxID . ' is not found');
        }

        //Если проверка успешна, переходим к следующему шагу, если нет, логируем и останавливаем процесс
        if ($bCorrectPayment)
        {
            //Логируем полученные данные
            paymentLog(array(), 'Успех bCorrectPayment');

            $collection = $order->getPaymentCollection();
            $payment = $collection[0];

            // $payment = $collection->getItemById($paymentBxID);
            $context = Application::getInstance()->getContext();
            $request = $context->getRequest();
            $item = PaySystem\Manager::searchByRequest($request);

            //Получение настроек для формирования подписи платежной системы из Битрикс24
            $secretKey = BusinessValue::getValueFromProvider($payment, 'SHOP_SECRET_KEY', 'PAYSYSTEM_' . $item['ID']);
            $hashType = BusinessValue::getValueFromProvider($payment, 'PAYMO_HASH_ALGO', 'PAYSYSTEM_' . $item['ID']);

            //Проверка на заполнение полей в Битрикс24
            if ((isset($secretKey) && !empty($secretKey)) && (isset($hashType) && !empty($hashType)))
            {
                //Логируем полученные данные
                paymentLog(array(), 'Успех secretKey и hashType');

                //Формирование подписи
                $signatureCheck = $hashType == 'md5' ? md5($paymentTxID . $amount . $secretKey) :
                    hash('sha256', $paymentTxID . $amount . $secretKey);

                //Проверка подписи
                if ($signatureCheck == $signature)
                {
                    //Логируем полученные данные
                    paymentLog(array(), 'Успех signatureCheck');

                    //Проверка на тип callback'а (start или finish)
                    if (!isset($arRequest['result']))
                    {
                        //start callback
                        //Логируем полученные данные
                        paymentLog(array(), 'Успех start callback');

                        //Логируем callback в HL блоки
                        paymentLogHL($paymentBxID, 'start', $arRequest);

                        //Если счет уже оплачен, оснавливаем
                        if ($payment->isPaid())
                        {
                            exit(json_encode(array("result" => false, "reason" => 'payment is payed')));
                        }
                        else
                        {

                            //Проверяем полечунные поля на заполненность, если нет, останавливаем
                            if ($arRequest['extra']['ls'] && $arRequest['extra']['receipt'])
                            {
                                //Отправляем result = true на первый callback
                                paymentLog(array('orderID' => $orderId, 'result' => true), 'Start callback');
                                exit(json_encode(array("result" => true)));
                            }
                            else
                            {
                                //Логируем полученные данные
                                paymentLog(array(), 'Ошибка Не отправлен ls или address или receipt');

                                exit(json_encode(array("result" => false, "reason" => "Не отправлен ls или address или receipt")));
                            }
                        }
                    }
                    else
                    {
                        //finish callback
                        //Логируем полученные данные
                        paymentLog(array(), 'Успех finish callback');

                        //Логируем callback в HL блоки
                        paymentLogHL($paymentBxID, 'finish', $arRequest);

                        $bStatus = $arRequest['result'];
                        $statusCode = $arRequest['status'];

                        //Если успешный ответ
                        if ($bStatus)
                        {
                            //Логируем полученные данные
                            paymentLog(array(), 'Успех bStatus');

                            // Получаем ID пользовательского поля, в которое будем сохранять id платежа
                            $save_tx_id_arr = BusinessValue::getMapping('SAVE_TX_ID', 'PAYSYSTEM_' . $item['ID'], $payment->getPersonTypeId());
                            $save_tx_id_field = ($save_tx_id_arr)? $save_tx_id_arr['PROVIDER_VALUE'] : '';

                            // Получаем ID пользовательского поля, в которое будем сохранять абсолютный id платежа в системе PAYMO
                            $save_paymo_id_arr = BusinessValue::getMapping('SAVE_PAYMO_ID', 'PAYSYSTEM_' . $item['ID'], $payment->getPersonTypeId());
                            $save_paymo_id_field = ($save_paymo_id_arr)? $save_paymo_id_arr['PROVIDER_VALUE'] : '';

                            //Формируем поля для оплаты
                            $fields = array(
                                "PS_STATUS_CODE"        => $statusCode,
                                "PS_STATUS_DESCRIPTION" => "PAYMO",
                                "PS_SUM"                => $order->getField('PRICE'),
                                "PS_STATUS"             => ($bStatus) ? "Y" : "N",
                                "PS_CURRENCY"           => "RUB",
                                "PS_RESPONSE_DATE"      => new \Bitrix\Main\Type\DateTime(),
                            );

                            // Сохраняем id платежа в пользовательское поле
                            if ($save_tx_id_field)
                            {
                                $USER_FIELD_MANAGER->Update('CRM_INVOICE', $orderId, array(
                                    $save_tx_id_field => $paymentTxID,    //ID пдатежа с _
                                    'UF_CRM_5C98A2C9899EF' => 4382,       //способ платежа - Paymo
                                ));
                            }

                            // Сохраняем абсолютный id платежа в пользовательское поле
                            if ($save_paymo_id_field)
                            {
                                $USER_FIELD_MANAGER->Update('CRM_INVOICE', $orderId, array(
                                    $save_paymo_id_field => $paymentExtID
                                ));
                            }

                            //Проводим оплату счета
                            $result = new PaySystem\ServiceResult();
                            $result->setOperationType(PaySystem\ServiceResult::MONEY_COMING);
                            $result->setPsData($fields);
                            $payment->setPaid(($bStatus) ? "Y" : "N");
                            $psData = $result->getPsData();
                            if ($psData)
                            {
                                $payment->setFields($psData);
                            }
                            $order->save();

                                //Логируем полученные данные
                                paymentLog(array(), 'Успех IncludeModule("crm")');

                                //Получаем данные по счету
                                $arInvoice = \CCrmInvoice::GetByID($orderId, false);

                                //Проверяем есть ли прикрепленная сделка
                                if (isset($arInvoice['UF_DEAL_ID']) && $arInvoice['UF_DEAL_ID'] > 0)
                                {
                                    //Логируем полученные данные
                                    paymentLog($arInvoice['UF_DEAL_ID'], 'Успех UF_DEAL_ID');

                                    //Получаем данные по прикрепленной сделке
                                    $rsDeal = \CCrmDeal::GetList(
                                        array(),
                                        array('=ID' => $arInvoice['UF_DEAL_ID'], 'CHECK_PERMISSIONS' => 'N')
                                    );
                                    $arDeal = $rsDeal->Fetch();

                                    //Формируем поля для сделки
                                    $arDealFields = array(
                                        'STAGE_ID' => 'PREPARATION',                                          //Изменяем статус сделки
                                        'UF_CRM_1553506852' => $arDeal['UF_CRM_1553506852'] ?
                                            $arDeal['UF_CRM_1553506852'].', '. $paymentTxID : $paymentTxID,   //ID платежа с _
                                        'UF_CRM_1553506926' => 4379,                                          //Способ оплаты - paymo
                                        'UF_CRM_1545861648' => 4373,                                          //Способ подтвержд - автоматич
                                        'UF_CRM_5C1A214547A70' => new \Bitrix\Main\Type\DateTime(),           //Дата оплаты
                                    );

                                    //Производим обновление полей сделки
                                    $deal = new \CCrmDeal(false);
                                    $deal->update($arInvoice['UF_DEAL_ID'], $arDealFields);

                                    //Подключаем модуль bizproc, для запуска бизнес процесса
                                    if (\CModule::IncludeModule("bizproc"))
                                    {
                                        //Логируем полученные данные
                                        paymentLog(array(), 'Успех IncludeModule("bizproc")');

                                        $errors = [];
                                        \CBPDocument::StartWorkflow(60, ['crm', 'CCrmDocumentDeal', 'DEAL_' . $arInvoice['UF_DEAL_ID']], [], $errors);
                                    }

                                }

                            exit(json_encode(array("result" => true)));
                        }
                    }
                }
                else
                {
                    paymentLog(array('signatureCheck' => $signatureCheck, 'signature' => $signature), 'Signature check');
                    exit(json_encode(array("result" => false, "reason" => 'signature is wrong')));
                }
            }
            else
            {
                paymentLog(array('secretKey' => $secretKey, 'hashType' => $hashType), 'Empty fields');
                exit(json_encode(array("result" => false, "reason" => 'empty secret key or hash type')));
            }
        }
        else
        {
            paymentLog(array('orderID' => $orderId, 'orderBxID' => $paymentBxID), $errorMessage);
            exit(json_encode(array('result' => false, 'reason' => $errorMessage)));
        }
    }
}

/**
 * Отправка писем в случае рассинхрона
 *
 * @param $orderID
 * @param $orderExtID
 */
function sendNotification($orderID, $orderExtID) {
    $arEmails = array(
        'simbirevas@pik-comfort.ru', 'mg@razvitie1c.ru', 'lukmanof92@gmail.com', 'eg@b24.tech',
        'selishchevso@pik-comfort.ru', 'projdovgd@pik-comfort.ru', 'gushchinamv@pik-comfort.ru'
    );

    $subject = 'Ошибка при оплате заказа №' . $orderID . "\n";

    $message = 'Ошибка при оплате заказа №' . $orderID . "\n";
    if ($orderExtID) {
        $subject = 'BxID: ' . $orderExtID . "\n";
    }

    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
    $headers .= 'To: simbirevas@pik-comfort.ru <simbirevas@pik-comfort.ru>, lukmanof92@gmail.com <lukmanof92@gmail.com>, mg@razvitie1c.ru <mg@razvitie1c.ru>, eg@b24.tech <eg@b24.tech>';
    $headers .= 'From: Birthday Reminder <info@bitrix.pik-comfort.ru>';

    mail(implode(',', $arEmails), $subject, $message, $headers);
}
/**
 * Логирование данных оплаты
 *
 * @param array $data
 * @param string $type
 */
function paymentLog($data = array(), $type = '') {
    $log = '[' . date('D M d H:i:s Y', time()) . '] ';
    if ($type) {
        $log .= $type . ': ';
    }
    $log .= json_encode($data, JSON_UNESCAPED_UNICODE);
    $log .= "\n";
    file_put_contents(dirname(__FILE__) . "/payment.log", $log, FILE_APPEND);
}

/**
 * Поэтапное логирование в HL блоки
 *
 * @param $orderID
 * @param $type
 * @param $request
 * @throws \Bitrix\Main\ObjectException
 * @throws \Bitrix\Main\SystemException
 */
function paymentLogHL($orderID, $type, $request) {
    $entityClass = GetEntityDataClassHL(8);
    $arFields = array(
        'UF_INVOICE_ID' => $orderID,
        'UF_START_TIME' => new \Bitrix\Main\Type\DateTime(),
        'UF_START_RESPONSE' => json_encode($request, JSON_UNESCAPED_UNICODE),
        'UF_CALLBACK_TYPE' => $type,
        'UF_PAYSYSTEM' => 'Paymo'
    );

    $entityClass::add($arFields);
}

/**
 * @param $HlBlockId
 * @return \Bitrix\Main\ORM\Data\DataManager|bool
 * @throws \Bitrix\Main\SystemException
 */
function GetEntityDataClassHL($HlBlockId) {
    if (empty($HlBlockId) || $HlBlockId < 1)
    {
        return false;
    }
    if (\CModule::IncludeModule("highloadblock")) {
        $hlblock = HLBT::getById($HlBlockId)->fetch();
        $entity = HLBT::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();
        return $entity_data_class;
    } else {
        return false;
    }
}