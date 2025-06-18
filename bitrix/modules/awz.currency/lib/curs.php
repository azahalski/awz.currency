<?php

namespace Awz\Currency;

use Awz\Currency\Parsers\ReservNbRb;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Awz\Currency\Parsers\NbRb;
use Awz\Currency\Parsers\CbRf;

Loc::loadMessages(__FILE__);

class CursTable extends Entity\DataManager
{

    const DEF_PROVIDER = 'cbrf';

    public static function getFilePath()
    {
        return __FILE__;
    }

    public static function getTableName()
    {
        return 'b_awz_currency';
    }

    public static function getMap()
    {
        return array(
            new Entity\IntegerField('ID', array(
                    'primary' => true,
                    'autocomplete' => false,
                    'title'=>Loc::getMessage('AWZ_CURRENCY_CURS_FIELD_ID')
                )
            ),
            new Entity\StringField('CODE', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_CURRENCY_CURS_FIELD_CODE')
                )
            ),
            new Entity\StringField('PROVIDER', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_CURRENCY_CURS_FIELD_PROVIDER')
                )
            ),
            new Entity\StringField('AMOUNT', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_CURRENCY_CURS_FIELD_AMOUNT')
                )
            ),
            new Entity\IntegerField('AMOUNT_CNT', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_CURRENCY_CURS_FIELD_AMOUNT_CNT')
                )
            ),
            new Entity\DatetimeField('CURS_DATE', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_CURRENCY_CURS_FIELD_CURS_DATE')
                )
            )
        );
    }

    /**
     * конверсия из одной валюты в другую
     *
     * @param string $cFrom код валюты откуда конверсия
     * @param string $cTo код валюты куда конверсия
     * @param float $summ сумма
     * @param string $date дата курса
     * @param string $provider провайдер
     * @return Result
     */
    public static function convert(string $cFrom, string $cTo, float $summ, string $date="", string $provider = self::DEF_PROVIDER)
    {

        //print_r([$cFrom, $cTo, $summ, $date, $provider]);
        $result = new Result();

        if(!$date){
            $date = date('d.m.Y');
        }

        $currencyList = self::getCurs($date, $provider);

        if($provider === 'nbrb'){
            $currencyList['BYN'] = array(
                'AMOUNT_CNT'=>1,
                'AMOUNT'=>1
            );
            $currencyList['BYR'] = array(
                'AMOUNT_CNT'=>10000,
                'AMOUNT'=>1
            );
        }elseif($provider == self::DEF_PROVIDER){
            $currencyList['RUB'] = array(
                'AMOUNT_CNT'=>1,
                'AMOUNT'=>1
            );
            $currencyList['RUR'] = array(
                'AMOUNT_CNT'=>1,
                'AMOUNT'=>1
            );
        }

        if(!isset($currencyList[$cFrom])){
            $result->addError(new Error(Loc::getMessage('AWZ_CURRENCY_CURS_FIELD_CURS_ERR1').' '.$cFrom.' '.Loc::getMessage('AWZ_CURRENCY_CURS_FIELD_CURS_ERR_DATE').' '.$date));
            return $result;
        }

        if(!isset($currencyList[$cTo])){
            $result->addError(new Error(Loc::getMessage('AWZ_CURRENCY_CURS_FIELD_CURS_ERR1').' '.$cTo.' '.Loc::getMessage('AWZ_CURRENCY_CURS_FIELD_CURS_ERR_DATE').' '.$date));
            return $result;
        }

        $fromValue = $summ;
        if($cFrom != 'BYN' && $provider === 'nbrb'){
            $fromValue = $fromValue * $currencyList[$cFrom]['AMOUNT'] / $currencyList[$cFrom]['AMOUNT_CNT'];
            $fromValue = round($fromValue, 4);
        }elseif($cFrom != 'RUB' && $cFrom != 'RUR' && $provider == self::DEF_PROVIDER){
            $fromValue = $fromValue * $currencyList[$cFrom]['AMOUNT'] * $currencyList[$cFrom]['AMOUNT_CNT'];
            $fromValue = round($fromValue, 4);
        }
        $resultValue = 0;
        if($fromValue>0){
            $normalizeCurs = $currencyList[$cTo]['AMOUNT'] / $currencyList[$cTo]['AMOUNT_CNT'];
            $resultValue = $fromValue / $normalizeCurs;
            $resultValue = round($resultValue, 4);
        }
        $result->setData(array('result'=>$resultValue, 'curs_from'=>$currencyList[$cFrom], 'curs_to'=>$currencyList[$cTo]));
        return $result;

    }

    /**
     * Возвращает курсы на дату
     *
     * @param $date дата
     * @param $provider провайдер
     * @param $type тип курса (бывают курсы на месяц)
     * @return array|mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getCurs($date, $provider = self::DEF_PROVIDER, $type='day'){

        if(strtotime($date) < (time()-2*86400*365)){
            return [];
        }
        if(strtotime($date) > (time()+0.4*86400)){
            return [];
        }

        $obCache = \Bitrix\Main\Data\Cache::createInstance();

        $dateCache = ceil(strtotime($date)/86400);

        static $cacheCurs;

        if(isset($cacheCurs[$dateCache.'_'.$provider]))
            return $cacheCurs[$dateCache.'_'.$provider];

        $cacheTime = 3600;
        $cache_id = "cm_list_".$dateCache.'_'.$provider;
        $cache_dir = "/awz/currency_check/_".$dateCache.'_'.$provider;

        if( $obCache->initCache($cacheTime,$cache_id,$cache_dir)){
            $check = $obCache->GetVars();
        }elseif( $obCache->startDataCache()){

            $check = self::getList(
                [
                    'select' => ['ID'],
                    'filter' => [
                        '=PROVIDER'=>$provider,
                        '=CURS_DATE' => DateTime::createFromTimestamp(strtotime($date))
                    ],
                    'limit' => 1
                ]
            )->fetch();

            if (!$check){
                self::updateCurs($date, $provider);
            }

            $obCache->endDataCache($check);

        }

        $cacheTime = 86400*365;
        $cache_id = "cm_list_".$dateCache.'_'.$provider;
        $cache_dir = "/awz/currency/_".$dateCache.'_'.$provider;

        if( $obCache->initCache($cacheTime,$cache_id,$cache_dir) ){
            $currencyList = $obCache->GetVars();
        }elseif( $obCache->startDataCache()){
            $res = self::getList(
                [
                    'select'=> ['*'],
                    'filter'=> [
                        '=PROVIDER'=>$provider,
                        '=CURS_DATE'=>DateTime::createFromTimestamp(strtotime($date))
                    ]
                ]
            );
            $currencyList = [];
            while($data = $res->fetch()){
                $currencyList[$data['CODE']] = $data;
            }
            if(empty($currencyList)){
                $obCache->abortDataCache();
            }else{
                $obCache->endDataCache($currencyList);
            }
        }

        $cacheCurs[$dateCache.'_'.$provider] = $currencyList;

        if(empty($currencyList) && Option::get(Agents::MODULE, "LOAD_PREW", "N","")==='Y'){
            return self::getLastActiveCurs(DateTime::createFromTimestamp(strtotime($date)), $provider);
        }

        return $currencyList;

    }

    /**
     * Возвращает последний доступный курс на дату
     *
     * @param DateTime $date дата
     * @param string $provider провайдер
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getLastActiveCurs(DateTime $date, string $provider = self::DEF_PROVIDER)
    {
        $resLastDate = self::getList(
            [
                'select'=> ['CURS_DATE'],
                'filter'=> ['=PROVIDER'=>$provider, '<=CURS_DATE'=>$date],
                'limit'=>1,
                'order'=>['CURS_DATE'=>'DESC']
            ]
        )->fetch();
        $currencyList = [];
        if($resLastDate){
            $resCurs = self::getList(
                [
                    'select'=> ['*'],
                    'filter'=> ['=CURS_DATE'=>$resLastDate['CURS_DATE']]
                ]
            );
            while($data = $resCurs->fetch()){
                $currencyList[$data['CODE']] = $data;
            }
        }
        return $currencyList;
    }

    /**
     * Обновление курсов
     *
     * @param string $date дата
     * @param string $provider провайдер
     * @param int $proxy счетчик прокси
     * @return void
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function updateCurs($date, $provider = self::DEF_PROVIDER, $proxy=0)
    {

        $activeCodes = explode(',',Option::get(Agents::MODULE, "LOAD_CODES", "",""));

        if($provider === 'nbrb'){
            $res = NbRb::getCursExternal($date);
            if(!$res->isSuccess() && Option::get(Agents::MODULE, "LOAD_NBRB2", "","")=="Y"){
                $res = ReservNbRb::getCursExternal($date);
            }
            if($res->isSuccess()){
                $data = $res->getData();
                $currencyList = $data['result']['data']['result'] ?? $data['result'];

                foreach($currencyList as $curs){

                    /* проверка ключей если курсы пришли с zahalski.dev */
                    $curs['Cur_Abbreviation'] = $curs['CODE'] ?? $curs['Cur_Abbreviation'];
                    $curs['Cur_OfficialRate'] = $curs['AMOUNT'] ?? $curs['Cur_OfficialRate'];
                    $curs['Cur_Scale'] = $curs['AMOUNT_CNT'] ?? $curs['Cur_Scale'];
                    $curs['Date'] = $curs['CURS_DATE'] ?? $curs['Date'];

                    if(!empty($activeCodes) && !in_array($curs['Cur_Abbreviation'], $activeCodes))
                        continue;

                    $r = self::getList(array(
                        'select'=>array('ID'),
                        'filter'=>array(
                            '=PROVIDER'=>$provider,
                            '=CODE'=>$curs['Cur_Abbreviation'],
                            '=CURS_DATE'=>DateTime::createFromTimestamp(strtotime($curs['Date']))
                        ),
                        'limit'=>1
                    ))->fetch();

                    $fields = array(
                        'PROVIDER'=>$provider,
                        'CODE'=>$curs['Cur_Abbreviation'],
                        'AMOUNT'=>round($curs['Cur_OfficialRate'], 4),
                        'AMOUNT_CNT'=>$curs['Cur_Scale'],
                        'CURS_DATE'=>DateTime::createFromTimestamp(strtotime($curs['Date'])),
                    );

                    if($r){
                        self::update(array('ID'=>$r['ID']), $fields);
                    }else{
                        self::add($fields);
                    }

                }
            }
        }elseif($provider == self::DEF_PROVIDER){
            $res = CbRf::getCursExternal($date);
            if($res->isSuccess()){
                $data = $res->getData();
                $currencyList = $data['result'];

                foreach($currencyList as $curs){

                    if(!empty($activeCodes) && !in_array($curs['CharCode'], $activeCodes))
                        continue;

                    $r = self::getList(array(
                        'select'=>array('ID'),
                        'filter'=>array(
                            '=PROVIDER'=>$provider,
                            '=CODE'=>$curs['CharCode'],
                            '=CURS_DATE'=>DateTime::createFromTimestamp(strtotime($curs['Date']))
                        ),
                        'limit'=>1
                    ))->fetch();

                    $fields = array(
                        'PROVIDER'=>$provider,
                        'CODE'=>$curs['CharCode'],
                        'AMOUNT'=>round($curs['Value'], 4),
                        'AMOUNT_CNT'=>$curs['Nominal'],
                        'CURS_DATE'=>DateTime::createFromTimestamp(strtotime($curs['Date'])),
                    );

                    if($r){
                        self::update(array('ID'=>$r['ID']), $fields);
                    }else{
                        self::add($fields);
                    }

                }
            }
        }

        if($res->isSuccess()) {
            if ($proxy) {
                \CEventLog::Add(array(
                        'SEVERITY' => 'DEBUG',
                        'AUDIT_TYPE_ID' => 'RESPONSE',
                        'MODULE_ID' => Agents::MODULE,
                        'DESCRIPTION' => Loc::getMessage('AWZ_CURRENCY_CURS_FIELD_CURS_LOG1').".\n" . Loc::getMessage('AWZ_CURRENCY_CURS_FIELD_CURS_LOG1_LBLDATE').": " . $date . ', '.Loc::getMessage('AWZ_CURRENCY_CURS_FIELD_CURS_LOG1_LBLPROV').': ' . $provider,
                    )
                );
            } else {
                \CEventLog::Add(array(
                        'SEVERITY' => 'DEBUG',
                        'AUDIT_TYPE_ID' => 'RESPONSE',
                        'MODULE_ID' => Agents::MODULE,
                        'DESCRIPTION' => Loc::getMessage('AWZ_CURRENCY_CURS_FIELD_CURS_LOG2').".\n" . Loc::getMessage('AWZ_CURRENCY_CURS_FIELD_CURS_LOG1_LBLDATE').": " . $date . ', '.Loc::getMessage('AWZ_CURRENCY_CURS_FIELD_CURS_LOG1_LBLPROV').': ' . $provider,
                    )
                );
            }
        }else{
            \CEventLog::Add(array(
                    'SEVERITY' => 'ERROR',
                    'AUDIT_TYPE_ID' => 'RESPONSE',
                    'MODULE_ID' => Agents::MODULE,
                    'DESCRIPTION' => Loc::getMessage('AWZ_CURRENCY_CURS_FIELD_CURS_LOG3')."."."\n". Loc::getMessage('AWZ_CURRENCY_CURS_FIELD_CURS_LOG1_LBLDATE').": ".$date."\n".implode("\n",$res->getErrorMessages()).', '.Loc::getMessage('AWZ_CURRENCY_CURS_FIELD_CURS_LOG1_LBLPROV').': '.$provider,
                )
            );

            if($proxy < 1){
                $proxyCnt = $proxy+1;
                return self::updateCurs($date, $provider, $proxyCnt);
            }
        }

    }

    public static function onAfterAdd(Event $event)
    {
        if(Option::get(Agents::MODULE, "LOAD_BX", "N","")=='Y' &&
            Loader::includeModule('currency'))
        {
            $fields = $event->getParameter('fields');
            $baseCurrency = \CCurrency::GetBaseCurrency();
            $arFields = array(
                "RATE" => $fields['AMOUNT'],
                "RATE_CNT" => $fields['AMOUNT_CNT'],
                "CURRENCY" => $fields['CODE'],
                "DATE_RATE" => $fields['CURS_DATE']->format("d.m.Y")
            );
            if($baseCurrency == 'BYN' && $fields['PROVIDER']=='nbrb'){
                \CCurrencyRates::Add($arFields);
            }
            if($baseCurrency == 'RUB' && $fields['PROVIDER']==self::DEF_PROVIDER){
                \CCurrencyRates::Add($arFields);
            }
        }
    }

}