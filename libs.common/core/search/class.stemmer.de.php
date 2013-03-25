<?php
/**
 * PHP5 Implementation of the German Porter Stemmer algorithm.
 * m() and replace() is  borrowed from the implementation by Richard Heyes (http://www.phpguru.org/).
 *
 * Definition mainly from http://snowball.tartarus.org/algorithms/german/stemmer.html
 *
 * This version stemms also cute forms like Kätzchen => katz, Männchen => mann away
 * Licence BSD
 *
 * Veith Zäch 19.01.2013
 *
 */

class PorterStemmerDE
{

    /**
     * Stems a word
     *
     * @param  string $word Word to stem
     * @return string       Stemmed word
     */
    public static function Stem($word)
    {

        if (mb_strlen($word) <= 2) {
            return mb_strtolower($word);
        }
        $word = self::ss2s($word);
        $word = self::unumlaut($word);
        $word = mb_strtolower($word);
        $word = self::unniedlich($word);
        $word = self::step1($word);
        $word = self::step2($word);
        $word = self::step3($word);
        $word = self::unpraefix($word);
        $word = self::undoubleconsonantending($word);


        return $word;
    }


    /**
     * ß in ss
     */
    private static function ss2s($word)
    {
        return str_replace('ß', 'ss', $word);

    }

    /**
     * undoubleconsonantending
     */
    private static function undoubleconsonantending($word)
    {

        if (!is_numeric(substr($word, -2, 1)) &&substr($word, -2, 1) == substr($word, -1, 1)) {
            return substr($word, 0, -1);
        } else {
            return $word;
        }

    }

    /**
     *   umlaute
     */
    private static function unumlaut($word)
    {
        return str_replace(array('ä', 'ö', 'ü'), array('a', 'o', 'u'), $word);

    }

    /**
     * unniedlich
     */
    private static function unniedlich($word)
    {
        self::sufixreplace($word, 'chens', '', 0)
            || self::sufixreplace($word, 'chen', '', 0);
        return $word;
    }

    /**
     * unpraefix
     */
    private static function unpraefix($word)
    {
            self::praefixreplace($word, 'ander', 'ander') ||
            self::praefixreplace($word, 'andr', 'andr') ||
            self::praefixreplace($word, 'abge', '', 1) ||
            self::praefixreplace($word, 'ab', '', 1) ||
            self::praefixreplace($word, 'an', '', 1) ||
            self::praefixreplace($word, 'auf', '', 1) ||
            self::praefixreplace($word, 'aus', '', 1) ||
            self::praefixreplace($word, 'bei', '', 1) ||
            self::praefixreplace($word, 'ein', '', 1) ||
            self::praefixreplace($word, 'los', '', 1) ||
            self::praefixreplace($word, 'mit', '', 1) ||
            self::praefixreplace($word, 'nach', '', 1) ||
            self::praefixreplace($word, 'her', '', 1) ||
            self::praefixreplace($word, 'hin', '', 1) ||
            self::praefixreplace($word, 'um', '', 1) ||
            self::praefixreplace($word, 'vor', '', 1) ||
            self::praefixreplace($word, 'weg', '', 1) ||
            self::praefixreplace($word, 'zuruck', '', 1) ||
            self::praefixreplace($word, 'zurecht', '', 1) ||
            self::praefixreplace($word, 'zusammen', '', 1) ||
            self::praefixreplace($word, 'zu', '');
        return $word;
    }

    /*
     * Step 1:
     * Search for the longest among the following suffixes,
     *
     * (a) em   ern   er
     * (b) e   en   es
     * (c) s (preceded by a valid s-ending)
     *
     * and delete if in R1. (Of course the letter of the valid s-ending is not necessarily in R1.) If an ending of group (b) is deleted, and the ending is preceded by niss, delete the final s.
     *
     * (For example, äckern -> äck, ackers -> acker, armes -> arm, bedürfnissen -> bedürfnis)
     *
     */
    private static function step1($word)
    {
        self::sufixreplace($word, 'em', '', 0) || self::sufixreplace($word, 'ern', '', 0) || self::sufixreplace($word, 'erm', '', 0) || self::sufixreplace($word, 'er', '', 0);
        self::sufixreplace($word, 'e', '', 0) || self::sufixreplace($word, 'en', '', 0) || self::sufixreplace($word, 'es', '', 0);

        // valid s endings
        if (substr($word, -1) == 's') {
            if (in_array(substr($word, -2, 1), array("b", "d", "f", "g", "h", "k", "l", "m", "n", "r", "t"))) {
                self::sufixreplace($word, 's', '', 0);
            }
        }
        return $word;
    }

    /*
     *
     * Step 2:
     * Search for the longest among the following suffixes,
     *
     * (a) en   er   est
     * (b) st (preceded by a valid st-ending, itself preceded by at least 3 letters)
     *
     * and delete if in R1.
     *
     * (For example, derbsten -> derbst by step 1, and derbst -> derb by step 2, since b is a valid st-ending, and is preceded by just 3 letters)
     *
     */
    private static function step2($word)
    {
        self::sufixreplace($word, 'en', '', 0) || self::sufixreplace($word, 'er', '', 0) || self::sufixreplace($word, 'est', '', 0);
        // valid s endings
        if (substr($word, -2) == 'st') {
            if (in_array(substr($word, -3, 1), array("b", "d", "f", "g", "h", "k", "l", "m", "n", "t"))) {
                self::sufixreplace($word, 'st', '', 0);
            }
        }
        return $word;
    }


    /**
     * Step 3: d-suffixes (*)
     *   Search for the longest among the following suffixes, and perform the action indicated.
     *
     *   end   ung
     *   delete if in R2
     *   if preceded by ig, delete if in R2 and not preceded by e
     *
     *   ig   ik   isch
     *   delete if in R2 and not preceded by e
     *
     *   lich   heit
     *   delete if in R2
     *   if preceded by er or en, delete if in R1
     *
     *   keit
     *   delete if in R2
     *   if preceded by lich or ig, delete if in R2
     */
    private static function step3($word)
    {
        self::sufixreplace($word, 'end', '', 0) || self::sufixreplace($word, 'ung', '', 0);
        self::sufixreplace($word, 'ig', '', 0) || self::sufixreplace($word, 'ik', '', 0) || self::sufixreplace($word, 'isch', '', 0);
        self::sufixreplace($word, 'lich', '', 0) || self::sufixreplace($word, 'heit', '', 0);
        self::sufixreplace($word, 'keit', '', 0) || self::sufixreplace($word, 'lein', '', 0);
        self::sufixreplace($word, 'zier', 'z', 0);

        return $word;
    }


    /**
     * Replaces the first string with the second, at the end of the string. If third
     * arg is given, then the preceding string must match that m count at least.
     *
     * @param  string $str   String to check
     * @param  string $check Ending to check for
     * @param  string $repl  Replacement string
     * @param  int    $m     Optional minimum number of m() to meet
     * @return bool          Whether the $check string was at the end
     *                       of the $str string. True does not necessarily mean
     *                       that it was replaced.
     */
    private static function sufixreplace(&$str, $check, $repl, $m = null)
    {

        $len = 0 - strlen($check);
        if (strlen($str) + $len < 3) {
            return false;
        }
        if (substr($str, $len) == $check) {
            $substr = substr($str, 0, $len);

            if (is_null($m) || self::m($substr) > $m) {
                $str = $substr . $repl;
            }

            return true;
        }

        return false;
    }

    private static function praefixreplace(&$str, $check, $repl, $m = null)
    {
        $len = strlen($check);
        if (strlen($str) - $len < 3) {
            return false;
        }

        if (substr($str, 0, $len) == $check) {
            $substr = substr($str, $len);
            if (is_null($m) || self::m($substr) > $m) {
                $str = $repl . $substr;
            }
            //gemacht
            return true;
        } else {

            // nicht gemacht
            return false;
        }

    }

    /**
     * What, you mean it's not obvious from the name?
     *
     * m() measures the number of consonant sequences in $str. if c is
     * a consonant sequence and v a vowel sequence, and <..> indicates arbitrary
     * presence,
     *
     * <c><v>       gives 0
     * <c>vc<v>     gives 1
     * <c>vcvc<v>   gives 2
     * <c>vcvcvc<v> gives 3
     *
     * @param  string $str The string to return the m count for
     * @return int         The m count
     */
    private static function m($str)
    {
        $c = '(?:[bcdfghjklmnpqrstvwxz]|(?<=[aeiou])y|^y)';
        $v = '(?:[aeiouyäöü]|(?<![aeiouäöü])y)';

        $str = preg_replace("#^$c+#", '', $str);
        $str = preg_replace("#$v+$#", '', $str);

        preg_match_all("#($v+$c+)#", $str, $matches);

        return count($matches[1]);
    }


}
