<?php

ZU::load_class('stemmer.de', 'core/search');


class ZUINDEX
{

    private $stopwords; //array of words not to index

    function __construct()
    {
        $this->loadstopwords();


    }

    function parse($string)
    {
        $list = array('words'=>array(),'numbers'=>array(), 'dates'=>array());

        /* Remove whitespace from beginning and end of string: */
        /* Try to remove all HTML-tags: */
        $string = preg_replace('/&\w;/', '', strip_tags(trim($string)));
        // wortverbinder -
        preg_match_all('/(*UTF8)\w+(-\w+)*/',$string,$wv);

        foreach ($wv[0] as $word) {



            $string = str_replace($word,str_replace('-',' ',$word),$string);
        }


        /* Extract all words */
        preg_match_all('/(*UTF8)\b\S+\b/', $string, $words);

        /* Stemmen */
        foreach ($words[0] as $key => $word) {
            if (preg_match("/(\d|-)?(\d|,|\')*\.?\d{1,5}/", $word, $numbers)) {
           // if (is_numeric($word)) {
                unset($words[0][$key]);
            } else {
                if (strlen($word) < 2) {
                    unset($words[0][$key]);
                } else {
                    $words[0][$key] = PorterStemmerDE::Stem($word);
                    if (in_array($word, $this->stopwords)) {
                        unset($words[0][$key]);
                    }
                }
            }
        }

        // duplikate entfernen
        $list['words'] = array_unique($words[0], SORT_STRING);

        //Dates
        preg_match_all("/(\d{1,2}[\.\/](\d{1,2}|\s?\D+\s?|\s?\w{3,12}\s?)[\.\/]\s?\d{2,4})/", $string, $numbers);
        foreach($numbers[0] as $key => $num){
            $list['dates'][] = $num;
            $string = str_replace($num,'',$string);
        }
        $list['dates'] = array_unique($list['dates']);

        // numbers
        //d{1,5} genau anschauen (max 5 Dezimalstellen)
        preg_match_all("/(\d|-)?(\d|,|\')*\.?\d{1,5}/", $string, $numbers);
        foreach($numbers[0] as $key => $num){
            $list['numbers'][] = str_replace(array(',',"'"),array('.', ''),$num);
        }
        $list['numbers'] = array_unique($list['numbers']);


        return $list;



    }

    private function loadstopwords()
    {

        include('stopwords.php');
        $this->stopwords = $stopword;

    }


}