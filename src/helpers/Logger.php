<?php

class Logger
{
    private $logFile;

    public function __construct($filePath)
    {
        $this->logFile = $filePath;
    }

    public function info($message, $data = [])
    {
        $this->write("INFO", $message, $data);
    }

    public function error($message, $data = [])
    {
        $this->write("ERROR", $message, $data);
    }

    private function write($level, $message, $data = [])
    {
        $entry = "[" . date("Y-m-d H:i:s") . "] [$level] $message";

        if (!empty($data)) {
            $entry .= " | " . json_encode($data, JSON_PRETTY_PRINT);
        }

        $entry .= PHP_EOL;

        file_put_contents($this->logFile, $entry, FILE_APPEND);
    }
}