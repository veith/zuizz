<?php

ZU::load_class('stemmer.de', 'core/search');


class ZUINDEX
{

    private $stopwords; //array of words not to index

    function __construct()
    {
        $this->loadstopwords();


    }

    function index($string)
    {
        $numberlist = array();
        $wordlist = array();
        $datelist = array();
        /* Remove whitespace from beginning and end of string: */
        /* Try to remove all HTML-tags: */
        $string = preg_replace('/&\w;/', '', strip_tags(trim($string)));

        /* Extract all words matching the regexp from the current line: */

        preg_match_all('/(*UTF8)\b\w+\b/', $string, $words);
        $tmp = $words[0];
        /* Stemmen */
        foreach ($words[0] as $key => $word) {
            if (is_numeric($word)) {
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
        $wordlist = array_unique($words[0], SORT_STRING);

        //d{1,5} genau anschauen
        preg_match_all("/(\d|-)?(\d|,|\')*\.?\d{1,5}/", $string, $numbers);
        //preg_match_all("/(\d{1,2}[\.\/](\d{1,2}|\s?\D+\s?|\s?\w{3,12}\s?)[\.\/]\s?\d{2,4})/", $string, $numbers);
        preg_match_all("/(\d{1,2}[\.\/](\d{1,2}[\.\/]|\s?\w{3}[\.\/]\s?|\s?\w{3,12}\s?)\d{2,4})/sm", $string, $numbers);
        foreach($numbers[0] as $key => $num){
            $numberlist[] = str_replace(array(',',"'"),'',$num);
        }

        $numberlist = array_unique($numberlist);

        setlocale(LC_TIME, "de_DE");
        foreach($numberlist as $date){
           echo $date . '==>' . date('d.m.Y', strtotime($date))   . "\n";
        }
        ZU::print_array($numberlist);

        echo date('d.m.Y', 1355000000). "\n";
        echo date('d.m.Y', 1356000000). "\n";
        echo date('d.m.Y', 1357000000). "\n";
        echo date('d.m.Y', 1358000000). "\n";
        echo date('d.m.Y', 1359000000). "\n";
        echo date('d.m.Y', 1360000000). "\n";
        echo date('d.m.Y', 1361000000). "\n";
        echo date('d.m.Y', 1362000000). "\n";

    }

    private function loadstopwords()
    {
        include('stopwords.php');
        $this->stopwords = $stopword;

    }


}