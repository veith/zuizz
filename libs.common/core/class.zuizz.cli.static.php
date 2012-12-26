<?php

class ZUCLI {

    /**
     * Displays progress indicator
     * @param int $current current value
     * @param int $total max value
     * @param string $label label for progress indicator
     * @param int $size size for progress indicator
     * @return int exit code
     */
    static function progressBar($current=0, $total=100, $label="", $size=50) {

        //Don't have to call $current=0
        //Bar status is stored between calls
        static $bars;
        $new_bar = false;
        if (!isset($bars[$label])) {
            $new_bar = TRUE;
            fputs(STDOUT,"$label Progress:\n");
        }

        if ($current == $bars[$label]) {
            return 0;
        }

        //Percentage round off for a more clean, consistent look
        $perc = round(($current/$total) * 100, 2);
        for ($i = strlen($perc); $i <= 4; $i++) {
            // percent indicator must be four characters, if shorter, add some spaces
            $perc = ' '.$perc;
        }

        $total_size = $size + $i + 3;

        // if it's not first go, remove the previous bar
        if(!$new_bar) {
            for($place = $total_size; $place > 0; $place--) {
                // echo a backspace (hex:08) to remove the previous character
                echo "\x08";
            }
        }

        // save bar status for next call
        $bars[$label]=$current;

        // output the progress bar as it should be
        for ($place = 0; $place <= $size; $place++) {
            if ($place <= ($current / $total * $size)) {
                // output green spaces if we're finished through this point
                echo '[42m [0m';
            } else {
                // or grey spaces if not
                echo '[47m [0m';
            }
        }

        // end a bar with a percent indicator
        echo " $perc%";

        // if it's the end, add a new line
        if ($current == $total) {
            echo "\n";
            unset($bars[$label]);
        }
    }

}