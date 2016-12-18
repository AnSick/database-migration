<?php

/**
 * Simple logger.
 * @author AnSick
 */
class Logger {
    // Log levels
    const debug = 3;
    const info = 2;
    const error = 1;

    private $current_level;
    private $file;

    public function __construct($file_name, $level) {
        $this->current_level = $level;
        $this->file = fopen($file_name, 'a+');
        fwrite($this->file, PHP_EOL . 'Log started [' . date('Y-m-d h:i:s', time()) . ']' . PHP_EOL);
    }

    public function __destruct() {
        fwrite($this->file, 'Log stopped [' . date('Y-m-d h:i:s', time()) . ']' . PHP_EOL);
        fclose($this->file);
    }

    private function log($level, $str) {
        if ($this->current_level >= $level) {
            fwrite($this->file, $str . PHP_EOL);
        }
    }

    public function debug($str) {
        $this->log(Logger::debug, '[Debug] ' . $str);
    }

    public function info($str) {
        $this->log(Logger::info, '[ Info] ' . $str);
    }

    public function error($str) {
        $this->log(Logger::error, '[Error] ' . $str);
    }
}