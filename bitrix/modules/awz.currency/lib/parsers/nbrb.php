<?php

namespace Awz\Currency\Parsers;

use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Web\Json;

class NbRb {

    const PROXY_URLS = [

    ];


    public static function getCursExternal($date, $proxy=0){

        $result = new Result();

        $httpClient = new HttpClient();
        $httpClient->setHeaders(array(
            "Content-Type"=> "application/json",
            "Accept-Language"=>"ru"
        ));
        $httpClient->disableSslVerification();

        $url = 'https://www.nbrb.by/api/exrates/rates?ondate='.
            date('Y-n-j',strtotime($date)).'&periodicity=0';
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

                $json = Json::decode($res);
                $result->setData(array('result'=>$json));

                /*
                [Cur_ID] => 440
                [Date] => 2022-05-30T00:00:00
                [Cur_Abbreviation] => AUD
                [Cur_Scale] => 1
                [Cur_Name] => Австралийский доллар
                [Cur_OfficialRate] => 1.8577
                */

                if(empty($json)){
                    $result->addError(
                        new Error('no values')
                    );
                }else if(!isset($json[0]['Cur_ID'])){
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