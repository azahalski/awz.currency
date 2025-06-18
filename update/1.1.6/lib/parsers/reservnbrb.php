<?php

namespace Awz\Currency\Parsers;

use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Web\Json;

class ReservNbRb {

    const API_URL = 'https://api.zahalski.dev/bitrix/services/main/ajax.php?action=awz:bxorm.api.hook.call&app=1&key=public&method=currency.pubcursnbrb';

    const MAX_TIMEOUT = 12;

    public static function getCursExternal($date, $proxy=0){

        $result = new Result();

        $httpClient = new HttpClient();
        $httpClient->setHeaders(array(
            "Content-Type"=> "application/json",
            "Accept-Language"=>"ru"
        ));
        $httpClient->disableSslVerification();
        $httpClient->setTimeout(static::MAX_TIMEOUT);
        $httpClient->setStreamTimeout(static::MAX_TIMEOUT);

        $res = $httpClient->get(static::API_URL.'&date='.$date);

        if(!$res){
            $result->addError(new Error('empty response'));
        }else{

            try {

                $json = Json::decode($res);
                $result->setData(array('result'=>$json));

                /*
                {
                "status": "success",
                "data": {
                    "result": {
                        "AUD": {
                            "ID": "34151",
                            "CODE": "AUD",
                            "AMOUNT": "1.9399",
                            "AMOUNT_CNT": "1",
                            "CURS_DATE": "2025-06-18T00:00:00+03:00",
                            "DESC_CODE": "AUD",
                            "DESC_IS_MONTH": "N",
                            "DESC_NAME": "..."
                        }
                    }
                },
                "errors": []
                }
                */

                if(empty($json)){
                    $result->addError(
                        new Error('no values')
                    );
                }else if(!isset($json['data']['result']['USD']['AMOUNT'])){
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