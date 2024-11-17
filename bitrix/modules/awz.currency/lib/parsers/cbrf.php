<?php

namespace Awz\Currency\Parsers;

use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Web\Json;

class CbRf
{

    const PROXY_URLS = [
        //'https://www.cbr-xml-daily.ru/daily_json.js'
    ];

    public static function getCursExternal($date, $proxy=0){

        $result = new Result();

        $httpClient = new HttpClient([
            'socketTimeout'=>10,
            'streamTimeout'=>10,
        ]);
        $httpClient->setHeaders(array(
            "Content-Type"=> "application/xml",
            "Accept-Language"=>"ru"
        ));
        $httpClient->disableSslVerification();

        $url = 'http://www.cbr.ru/scripts/XML_daily.asp?date_req='.
            date("d.m.Y",strtotime($date));
        if($proxy){
            $proxyUrls = self::PROXY_URLS;
            if(!isset($proxyUrls[($proxy-1)])){
                $result->addError(new Error('proxy url not found'));
                return $result;
            }else{
                $url = $proxyUrls[($proxy-1)].'&url='.urlencode($url);
            }
        }

        $res = $httpClient->get($url);

        if(!$res){

            $result->addError(new Error('empty response'));

        }else{

            try {
                //print_r($res);
                $xml = new \SimpleXMLElement($res);

                $cursList = [];
                foreach($xml->Valute as $rowOb){
                    $cursList[] = [
                        'CharCode'=>(string)$rowOb->CharCode,
                        'Nominal'=>(int)$rowOb->Nominal,
                        'Name'=>(string)$rowOb->Name,
                        'Value'=>str_replace(',','.',(string)$rowOb->Value),
                        'NumCode'=>(string)$rowOb->NumCode,
                        'Date'=>date('d.m.Y',strtotime($date))
                    ];
                }
                $result->setData(array('result'=>$cursList));

                /*
                [@attributes] => Array
                    (
                        [ID] => R01010
                    )

                [NumCode] => 036
                [CharCode] => AUD
                [Nominal] => 1
                [Name] => Австралийский доллар
                [Value] => 16,0102
                */

                if(empty($cursList)){
                    $result->addError(
                        new Error('no values')
                    );
                }else if(!isset($cursList[0]['CharCode'])){
                    $result->addError(
                        new Error('error formats')
                    );
                }

            }catch (\Exception  $ex){
                $result->addError(
                    new Error($ex->getMessage(), $ex->getCode())
                );
            }

        }

        return $result;

    }

}