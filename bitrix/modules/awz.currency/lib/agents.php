<?php

namespace Awz\Currency;

use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Config\Option;

class Agents {

    const MODULE = 'awz.currency';

    public static function getDayRb(): string
    {
        $checkActive = true;
        if(Option::get(self::MODULE, "LOAD_NBRB", "N","") === 'N')
        {
            $checkActive = false;
        }

        if($checkActive){
            //курсы устанавливаются наперед, можно получить заранее еще в предыдущем дне
            $date = date('d.m.Y', time()+60*60*6);
            CursTable::updateCurs($date, 'nbrb');
        }

        return "\\Awz\\Currency\\Agents::getDayRb();";
    }

    public static function getDayRf(): string
    {
        $checkActive = true;
        if(Option::get(self::MODULE, "LOAD_CBRF", "N","") === 'N')
        {
            $checkActive = false;
        }
        if($checkActive){
            //курсы устанавливаются наперед, можно получить заранее еще в предыдущем дне
            $date = date('d.m.Y', time()+60*60*6);
            CursTable::updateCurs($date, 'cbrf');
        }

        return "\\Awz\\Currency\\Agents::getDayRf();";
    }

    public static function updateBxCurs(){
        if(Option::get(Agents::MODULE, "LOAD_BX_MAIN", "N","")=='Y' &&
            Loader::includeModule('currency'))
        {
            $baseCurrency = \CCurrency::GetBaseCurrency();
            $currencyList = CursTable::getCurs(date('d.m.Y'), $baseCurrency=='BYN' ? 'nbrb' : CursTable::DEF_PROVIDER);

            foreach($currencyList as $curs){
                $cursValue = \CCurrency::GetByID($curs['CODE']);
                if($cursValue['AMOUNT_CNT']!=$curs['AMOUNT_CNT']){
                    $curs['AMOUNT'] = round($curs['AMOUNT'] * $cursValue['AMOUNT_CNT']/$curs['AMOUNT_CNT'], 4);
                    $curs['AMOUNT_CNT'] = 1;
                }
                //echo'<pre>';print_r($curs);echo'</pre>';
                if($cursValue['AMOUNT'] != $curs['AMOUNT']){
                    \CCurrency::Update($curs['CODE'],[
                        'AMOUNT'=>$curs['AMOUNT'],
                        'AMOUNT_CNT '=>$cursValue['AMOUNT_CNT'],
                        'CURRENCY'=>$cursValue['CURRENCY'],
                        'SORT'=>$cursValue['SORT'],
                        'NUMCODE'=>$cursValue['NUMCODE'],
                        'BASE'=>$cursValue['BASE'],
                        'MODIFIED_BY'=>$cursValue['MODIFIED_BY']
                    ]);
                }
            }
        }
        return "\\Awz\\Currency\\Agents::updateBxCurs();";
    }

}