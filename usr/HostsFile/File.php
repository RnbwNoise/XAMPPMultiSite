<?php
    /**
     * Copyright (c) 2014 Vladimir P.
     * 
     * Permission is hereby granted, free of charge, to any person obtaining a copy
     * of this software and associated documentation files (the "Software"), to deal
     * in the Software without restriction, including without limitation the rights
     * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
     * copies of the Software, and to permit persons to whom the Software is
     * furnished to do so, subject to the following conditions:
     * 
     * The above copyright notice and this permission notice shall be included in
     * all copies or substantial portions of the Software.
     * 
     * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
     * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
     * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
     * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
     * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
     * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
     * THE SOFTWARE.
     */
    
    namespace HostsFile;
    
    require_once(__DIR__ . '/Record.php');
    require_once(__DIR__ . '/Exception.php');
    require_once(__DIR__ . '/IOException.php');
    
    /**
     * Represents the hosts file.
     * @copyright 2014 Vladimir P.
     * @license MIT
     */
    final class File {
        /**
         * Path to the file.
         * @var string
         */
        private $filename;
        
        /**
         * Array of records in this file.
         * @var Record[]
         */
        private $records = [];
        
        /**
         * @param string $filename Path to the file.
         * @throws IOException If it is not possible to read the file.
         */
        public function __construct($filename) {
            $contents = file($filename);
            if($contents === false)
                throw new IOException('Cannot read the hosts file!');
            
            $this->filename = $filename;
            $this->records = array_map(function($line) {
                return (new Record())->createFromLine($line);
            }, $contents);
        }
        
        /**
         * Writes the hosts file.
         * @throws IOException If it is not possible to write the file.
         */
        public function write() {
            $result = file_put_contents($this->filename,
                array_reduce($this->records, function($carry, $record) { return $carry . (string)$record; }, ''));
            if($result === false)
                throw new IOException('Cannot write the hosts file!');
        }
        
        /**
         * Adds an alias for a given hostname.
         * @param string $ip
         * @param string $hostname
         * @throws Exception If the hostname already has an alias.
         */
        public function addAlias($ip, $hostname) {
            if($this->getRecordForHostname($hostname) !== null)
                throw new Exception('Hostname already has an alias!');
            
            $record = $this->getRecordForIP($ip);
            if($record === null) {
                $record = (new Record())->createNew($ip);
                $this->records[] = $record;
            }
            
            $record->addHostname($hostname);
        }
        
        /**
         * Removes an alias for a given hostname. If its record doesn't have any other host names, it will be deleted.
         * @param string $hostname The host name of an alias to be deleted.
         * @throws Exception If the host name doesn't have a corresponding record.
         */
        public function removeAlias($hostname) {
            $record = $this->getRecordForHostname($hostname);
            if($record === null)
                throw new Exception('Hostname doesn\'t have an alias!');
            
            $record->removeHostname($hostname);
            
            if(count($record->getHostnames()) === 0)
                $this->removeRecord($record);
        }
        
        /**
         * Returns a record with given IP address.
         * @param string $ip IP address of a record.
         * @return Record|null Record or null if an appropriate record was not found.
         */
        private function getRecordForIP($ip) {
            $ip = strtolower($ip);
            foreach($this->records as $record) {
                if($ip === strtolower($record->getIP()))
                    return $record;
            }
            return null;
        }
        
        /**
         * Returns a record containing given hostname.
         * @param string $hostname Hostname of a record.
         * @return Record|null Record or null if an appropriate record was not found.
         */
        private function getRecordForHostname($hostname) {
            $hostname = strtolower($hostname);
            foreach($this->records as $record) {
                if(in_array($hostname, array_map('strtolower', $record->getHostnames()), true))
                    return $record;
            }
            return null;
        }
        
        /**
         * Removes a given record.
         * @param Record $record A record to be removed.
         * @return boolean True if the record was deleted, false otherwise.
         */
        private function removeRecord(Record $record) {
            $key = array_search($record, $this->records, true);
            if($key === false)
                return false;
            
            unset($this->records[$key]);
            $this->records = array_values($this->records);
        }
    }