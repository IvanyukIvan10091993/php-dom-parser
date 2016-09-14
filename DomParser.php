<?php

class DomParser
{
    private static $domObjInsertors = [
        'a' => 'SELF::insertA',
        'li' => 'SELF::insertLi',
        'ul' => 'SELF::insertUl',
    ];

    private static function computeDomObj(&$objXpath, &$urlString)
    {
        return SELF::computeXpath($urlString)->query($objXpath)[0];
    }
    private static function computeXpath(&$urlString)
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        @$dom->loadHTML(file_get_contents($urlString));
        return new DOMXPath($dom);
    }
    private static function insertA(&$data, &$domObj)
    {
        $data['name'] = $domObj->nodeValue;
        $data['href'] = $domObj->getAttribute('href');
    }
    private static function insertDomObj(&$data, &$domObj) {
        if (array_key_exists($domObj->nodeName, SELF::$domObjInsertors)) {
            call_user_func_array(SELF::$domObjInsertors[$domObj->nodeName], [&$data, &$domObj]);
        }
    }
    private static function insertLi(&$data, &$domObj)
    {
        $dataObj = [];
        foreach ($domObj->childNodes as $child) {
            SELF::insertDomObj($dataObj, $child);
        }
        $data[] = $dataObj;
    }
    private static function insertUl(&$data, &$domObj)
    {
        $dataObj = [];
        foreach ($domObj->childNodes as $child) {
            SELF::insertDomObj($dataObj, $child);
        }
        $data['children'] = $dataObj;
    }

    function parseMenu($objXpath, $urlString)
    {
        $data = [];
        $domObj = SELF::computeDomObj($objXpath, $urlString);

        SELF::insertDomObj($data, $domObj);

        echo '<pre>';
        print_r($data['children']);
        echo '</pre>';
    }
}

DomParser::parseMenu('//*[@id="tabs1_"]/ul', 'http://www.duim24.ru/');

?>
