<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

global $INTRANET_TOOLBAR;

$component = $this->getComponent();

if (CModule::IncludeModule('intranet'))
{
    $INTRANET_TOOLBAR->Show();
}

include("util_group_menu.php");


$APPLICATION->IncludeComponent(
    'b24tech:schedule.payments',
    '',
    array(
        'GROUP_ID' => $arResult['VARIABLES']['group_id']
    )
);