<?
$aMenuLinks = Array(
	Array(
		"Списки", 
		"/services/lists/", 
		Array(), 
		Array(), 
		"CBXFeatures::IsFeatureEnabled('Lists')" 
	),
	Array(
		"Контакт-центр", 
		"/services/contact_center/", 
		Array(), 
		Array(), 
		"" 
	),
	Array(
		"Журнал изменений", 
		"/services/event_list.php", 
		Array(), 
		Array(), 
		"CBXFeatures::IsFeatureEnabled('EventList')" 
	),
	Array(
		"Телефония", 
		"/services/telephony/", 
		Array(), 
		Array(), 
		"CModule::IncludeModule(\"voximplant\") && SITE_TEMPLATE_ID !== \"bitrix24\" && Bitrix\\Voximplant\\Security\\Helper::isMainMenuEnabled()" 
	)
);
?>