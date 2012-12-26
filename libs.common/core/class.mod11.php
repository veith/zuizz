<?php

class  M11
{
    /*
    *  Gewicht ist 3,	5,	9,	7,	8,	6,	4,	2
    *  im array umgekehrt um 1 math op zu sparen.
    */
    private static $weight = array(2, 4, 6, 8, 7, 9, 5, 3);
    private static $theprimenumber = 99991;
    private static $thejumper = 11; // abstand zwischen den Nummern

    /*
     * Prüfe Nummer auf korrektheit
     */
    static function check($numberstring)
    {
        $number = substr($numberstring, 0, -1);
        $checksum = intval(substr($numberstring, -1));
        if ($checksum == self::getChecksum($number) && self::getIdFromNumber($number) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /*
     * Gesammtnummer aus Zahl bilden
     */
    static function generatePNumber($number)
    {
        return intval($number . self::getChecksum($number));
    }

    /*
     * Checksumme berechnen
     */
    static function getChecksum($number)
    {
        $number = strrev($number);
        $sum = 0;
        foreach (self::$weight as $index => $val) {
            if (isset($number[$index])) {
                $sum += $val * $number[$index];
            }
        }
        return $sum % 11;
    }

    /*
     * ID aus Riesenzahl erhalten (ohne checksumme)
     */
    static function getIdFromNumber($number)
    {
        $id = ($number - self::$theprimenumber) / self::$thejumper;
        if (is_int($id)) {
            return $id;
        } else {
            return false;
        }

    }

    /*
    * ID aus Riesenzahl erhalten (mit checksumme)
    */
    static function getIdFromPNumber($number)
    {
        $id = (substr($number, 0, -1) - self::$theprimenumber) / self::$thejumper;
        if (is_int($id) && $id > 0) {
            return $id;
        } else {
            return false;
        }

    }

    /*
     * Riesenzahl mit checksumme aus ID und prim berechnen
     */
    static function generateNumber($id, $checksum = true)
    {
        if ($checksum) {
            $number = $id * self::$thejumper + self::$theprimenumber;
            return self::generatePNumber($number);
        } else {
            return $id * self::$thejumper + self::$theprimenumber;
        }
    }

}