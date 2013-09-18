<?php




class ZUINDEX
{

    private $stopwords; //array of words not to index
    private $monate = array('januar' => 'jan', 'februar ' => 'feb', 'marz' => 'mar', 'mai' => 'may', 'juni' => 'jun', 'juli' => 'jul', 'august' => 'aug', 'september' => 'sep', 'oktober' => 'oct', 'november' => 'nov', 'dezember' => 'dec', 'dez' => 'dec');

    function __construct()
    {
        $this->lang = ZU::get_interface_lang();
        ZU::load_class("stemmer." . $this->lang, 'core/search');
        $this->loadstopwords();
        $this->today = mktime(0, 0, 0);
    }

    function parse($string)
    {
        $list = array('words' => array(), 'numbers' => array(), 'dates' => array());

        /* Remove whitespace from beginning and end of string: */
        /* Try to remove all HTML-tags: */
        $string = preg_replace('/&\w;/', '', strip_tags(trim($string)));

        // wortverbinder -
        preg_match_all('/(*UTF8)\w{3,}(-\w{3,})*/', $string, $wv);
        foreach ($wv[0] as $word) {
            $string = str_replace($word, str_replace('-', ' ', $word), $string);
        }

        /* Extract all words */
        preg_match_all('/(*UTF8)\b\S+\b/', $string, $words);
        $words[0] = array_unique($words[0], SORT_STRING);
        /* Stemmen */
        foreach ($words[0] as $key => $word) {
            // remove numbers
            if (preg_match("/(\d|-)?(\d|,|\')*\.?\d{1,5}/", $word, $numbers)) {
                unset($words[0][$key]);
            } else {
                if (strlen($word) < 2) {
                    unset($words[0][$key]);
                } else {
                    $stemm = $this->stemm($word);
                    if (in_array($stemm, $this->stopwords)) {
                        unset($words[0][$key]);
                    } else {
                        $list['words'][] = array($word, substr(soundex("{$word}"), 1, 3), $stemm, substr(soundex("{$stemm}"), 1, 3));
                    }
                }
            }
        }


        //Text Dates

        preg_match_all("/(*UTF8)(\d{1,2})[\.?\ ?](\b\w+\D\b)[\.?\ ?](\d{2,4})/", $string, $numbers);
        $s = 'PorterStemmer' . $this->lang;
        foreach ($numbers[0] as $key => $num) {
            // string dates sind zu 98% dmy
            $numbers[2][$key] = $s::unumlaut($numbers[2][$key]);
            if (isset($this->monate[strtolower($numbers[2][$key])])) {
                $monat = $this->monate[strtolower($numbers[2][$key])];
            } else {
                $monat = $numbers[2][$key];
            }

            $timestamp = strtotime("{$numbers[1][$key]} {$monat} {$numbers[3][$key]}");
            if ($timestamp != $this->today) {
                $list['dates'][] = array($num, $timestamp, log10((abs($timestamp - $this->today) / 86400)), ($timestamp - $this->today) / 86400);
            } else {
                $list['dates'][] = array($num, $timestamp, 0, 0);
            }
            $string = str_replace($num, '', $string);
        }

        //Dates
        preg_match_all("/(\d{1,4})[\.\/-](\d{1,2})[\.\/-](\d{1,4})/", $string, $numbers);


        foreach ($numbers[0] as $key => $num) {

            //dmy  ymd mdy
            // k2>12 => d  length(n) > 2 => y
            $mode = 'dmy';
            if (count($numbers[1][$key]) == 4 || $numbers[1][$key] > 31) {
                if ($numbers[2][$key] > 12) {
                    //ymd
                    $mode = 'ydm';
                } else {
                    //ymd
                    $mode = 'ymd';
                }
            }

            if (count($numbers[3][$key]) == 4 || $numbers[3][$key] > 31) {
                if ($numbers[2][$key] > 12) {
                    //mdy
                    $mode = 'mdy';
                } else {
                    //dmy
                    $mode = 'dmy';
                }
            }
            switch ($mode) {
                case 'dmy':
                    $timestamp = mktime(0, 0, 0, $numbers[2][$key], $numbers[1][$key], $numbers[3][$key]);
                    break;
                case 'mdy':
                    $timestamp =mktime(0, 0, 0, $numbers[1][$key], $numbers[2][$key], $numbers[3][$key]);
                    break;
                case 'ymd':
                    $timestamp = mktime(0, 0, 0, $numbers[2][$key], $numbers[3][$key], $numbers[1][$key]);
                    break;
                case 'ydm':
                    $timestamp =  mktime(0, 0, 0, $numbers[3][$key], $numbers[2][$key], $numbers[1][$key]);
                    break;
            }
            if ($timestamp != $this->today) {
                $list['dates'][] = array($num, $timestamp, log10((abs($timestamp - $this->today) / 86400)), ($timestamp - $this->today) / 86400, $mode);
            } else {
                $list['dates'][] = array($num, $timestamp, 0, 0 ,$mode);
            }

            $string = str_replace($num, '', $string);

        }


        // numbers
        //d{1,5} genau anschauen (max 5 Dezimalstellen)
        preg_match_all("/(\d|-)?(\d|,|\')*\.?\d{1,5}/", $string, $numbers);
        $numbers[0] = array_unique($numbers[0]);
        foreach ($numbers[0] as $key => $num) {
            $snum = str_replace(array(',', "'"), array('.', ''), $num);
            $list['numbers'][] = array($num, $snum, log10(abs($snum)));
        }


        return $list;


    }

    private function stemm($word)
    {
        $s = 'PorterStemmer' . $this->lang;
        return $s::Stem($word);
    }

    private function loadstopwords()
    {

        include("stopwords.{$this->lang}.php");
        $this->stopwords = $stopword;

    }


}