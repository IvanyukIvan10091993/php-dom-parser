<?php

class DomParser
{
    private static $domObjInsertorsCategory = [
        'a' => 'insertAnchorCategory',
        'li' => 'insertLiCategory',
        'ul' => 'insertUl',
    ];
    private static $domObjInsertorsItems = [
    ];

    private $categoryXpath = '';
    private $itemsXpath = '';
    private $paginationXpath = '';
    private $urlString = '';
    private $waitTime = 0;

    private function computeDomObjects($xpath, $urlString)
    {
        $domObjects = [];
        $domNodeList = $this->computeXpath($urlString)->query($xpath);
        foreach ($domNodeList as $domObject) {
            $domObjects[] = $domObject;
        }
        return $domObjects;
    }
    private function computeXpath($urlString)
    {
        sleep($this->waitTime); // Эта строчка здесь для того, чтобы сервер не решил, что мы бот
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        @$dom->loadHTML(file_get_contents($urlString));
        return new DOMXPath($dom);
    }
    private function computeLastPageNumber($urlString)
    {
        123 // Should check if pagen found
        echo $urlString;
        return array_pop(explode('=', $this->computeDomObjects($this->paginationXpath, $urlString)[0]->getAttribute('href')));
    }
    private function hasNoSubcategories(&$dataObj)
    {
        return (!array_key_exists('subcategories', $dataObj)) ? true : false;
    }
    private function insertAnchorCategory(&$data, $domObj)
    {
        $data['name'] = $domObj->nodeValue;
        $data['href'] = $domObj->getAttribute('href');
    }
    private function insertDomObjCategory(&$data, $domObj) {
        if (array_key_exists($domObj->nodeName, SELF::$domObjInsertorsCategory)) {
            call_user_func_array([$this, SELF::$domObjInsertorsCategory[$domObj->nodeName]], [&$data, $domObj]);
        }
    }
    private function insertDomObjItems(&$data, $domObj) {
        if (array_key_exists($domObj->nodeName, SELF::$domObjInsertorsItems)) {
            call_user_func_array([$this, SELF::$domObjInsertorsItems[$domObj->nodeName]], [&$data, $domObj]);
        }
    }
    private function insertLiCategory(&$data, $domObj)
    {
        $dataObj = [];
        foreach ($domObj->childNodes as $child) {
            $this->insertDomObjCategory($dataObj, $child);
        }
        if ($this->hasNoSubcategories($dataObj)) {
            $this->parseItems($dataObj);
        }
        $data[] = $dataObj;
    }
    private function insertUl(&$data, $domObj)
    {
        $dataObj = [];
        foreach ($domObj->childNodes as $child) {
            $this->insertDomObjCategory($dataObj, $child);
        }
        $data['subcategories'] = $dataObj;
    }
    private function parseItems(&$dataObj)
    {
        $itemsUrlString = rtrim($this->urlString, "/") . $dataObj['href'];
        $lastPageNumber = $this->computeLastPageNumber($itemsUrlString);
        for ($i = 1, $len = $lastPageNumber + 1; $i < $len; $i++) {
            $url = $itemsUrlString . '?sort=PROPERTY_ostatok&method=DESC&PAGEN_1=' . $i;
            $domObjects = $this->computeDomObjects($this->itemsXpath, $url);
            foreach ($domObjects as $domObject) {
                echo "<pre>";
                echo $i;
                echo "\n";
                print_r($domObject->getAttribute('href'));
                echo "</pre>";
                flush();
                ob_flush();
            }
        }
        //die();
    }

    public function __construct($categoryXpath, $itemsXpath, $paginationXpath, $waitTime, $urlString)
    {
        $this->categoryXpath = $categoryXpath;
        $this->itemsXpath = $itemsXpath;
        $this->urlString = $urlString;
        $this->waitTime = $waitTime;
        $this->paginationXpath = $paginationXpath;
    }
    public function parseCategory()
    {
        set_time_limit(0); // Эта строчка здесь для того, чтобы php не прекращал выполнение скрипта после стандартных 30 секунд работы
        $data = [];
        $domObjects = $this->computeDomObjects($this->categoryXpath, $this->urlString);

        foreach ($domObjects as $domObject) {
            $this->insertDomObjCategory($data, $domObject);
        }
    }
}

$parser = new DomParser('//*[@id="tabs1_"]/ul', '//*[contains(@class, "tovar-col tovar2")]/a', '//*[contains(@class, "pager-last")]/a', 1, 'http://www.duim24.ru/');
$parser->parseCategory();

?>
