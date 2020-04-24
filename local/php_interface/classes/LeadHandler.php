<?php


namespace B24Tech;


use Bitrix\Im\ChatTable,
    Bitrix\Main\Loader,
    Bitrix\Crm;

class LeadHandler
{
    public function OnAfterConversionLeadToDeal ($arFields)
    {
        if (isset($arFields['STATUS_ID']) && $arFields['STATUS_ID'] === 'CONVERTED') {
            if (Loader::includeModule('im') && Loader::includeModule('crm')) {
                if (isset($arFields['ID'])) {
                    //get Lead entity Chat
                    $res = chatTable::getList(array(
                        'filter' => array(
                            'ENTITY_ID' => 'LEAD|' . $arFields['ID'],
                        )
                    ));
                    $arChat = $res->fetch();

                    //get new Deal after Lead conversion
                    $arDeal = [];
                    $arRes = \CCrmDeal::GetList([], ['LEAD_ID' => $arFields['ID'], "CHECK_PERMISSIONS" => "N"], ['ID', 'TITLE']);
                    while ($res = $arRes->Fetch()) {
                        $arDeal = $res;
                    }
                }

                //change Chat ENTITY_ID to Deal id and change TITLE
                if (isset($arChat) && is_array($arChat) && isset($arDeal)) {
                    if (isset($arChat['TITLE'])) {
                        $arChat['TITLE'] = str_replace('Лид:', 'Сделка:', $arChat['TITLE']);
                        \Bitrix\Im\Model\ChatTable::update($arChat['ID'], ['TITLE' => $arChat['TITLE']]);
                    }
                    $result = \CIMChat::SetChatParams(
                        $arChat['ID'],
                        ['ENTITY_ID' => 'DEAL|' . $arDeal['ID']]
                    );
                }
            }
        }

    }
}