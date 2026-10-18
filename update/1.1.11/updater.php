<?
$moduleId = "awz.currency";
if(IsModuleInstalled($moduleId)) {
    $eventManager = \Bitrix\Main\EventManager::getInstance();
	$eventManager->registerEventHandler(
		'awz.allxls', 'onAwzAllxlsGetMacros',
		$moduleId, '\\Awz\\Currency\\Handlers', 'onAwzAllxlsGetMacros'
	);
	$eventManager->registerEventHandler(
		'awz.allxls', 'onAwzAllxlsExpandMacros',
		$moduleId, '\\Awz\\Currency\\Handlers', 'onAwzAllxlsExpandMacros'
	);
}