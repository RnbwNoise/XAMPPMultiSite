<?php
    /**
     * XAMPPMultiSite
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
    
    /** Represents the hosts file. */
    final class HostsFile {
        private $filename;
        private $records = [];
        
        /** Creates a hosts file reader/writer. */
        public function __construct($filename) {
            $contents = file($filename);
            if($contents === false)
                throw new RuntimeException('Cannot read the hosts file!');
            
            $this->filename = $filename;
            $this->records = array_map(function($line) {
                return (new HostsRecord())->createFromLine($line);
            }, $contents);
        }
        
        /** Writes records from this object into the hosts file. */
        public function write() {
            $result = file_put_contents($this->filename,
                array_reduce($this->records, function($carry, $record) { return $carry . (string)$record; }, ''));
            if($result === false)
                throw new RuntimeException('Cannot write the hosts file!');
        }
        
        /** Adds an alias for a given host name. */
        public function addAlias($ip, $hostName) {
            if($this->getRecordForHostName($hostName) !== null)
                throw new RuntimeException('Host name already has an alias!');
            
            $record = $this->getRecordForIP($ip);
            if($record === null) {
                $record = (new HostsRecord())->createNew($ip);
                $this->records[] = $record;
            }
            
            $record->addHostName($hostName);
        }
        
        /**
         * Removes an alias for a given host name. If its record doesn't have any other host names, it will be deleted.
         */
        public function removeAlias($hostName) {
            $record = $this->getRecordForHostName($hostName);
            if($record === null)
                throw new RuntimeException('Host name doesn\'t have an alias!');
            
            $record->removeHostName($hostName);
            
            if(count($record->getHostNames()) === 0)
                $this->removeRecord($record);
        }
        
        /** Returns a record with given IP address. If there is no such record, returns null. */
        private function getRecordForIP($ip) {
            $ip = strtolower($ip);
            foreach($this->records as $record) {
                if($ip === strtolower($record->getIP()))
                    return $record;
            }
            return null;
        }
        
        /** Returns a record containing given host name. If there is no such record, returns null. */
        private function getRecordForHostName($hostName) {
            $hostName = strtolower($hostName);
            foreach($this->records as $record) {
                if(in_array($hostName, array_map('strtolower', $record->getHostNames()), true))
                    return $record;
            }
            return null;
        }
        
        /** Removes a given record. Returns false if a record is not in this file. */
        private function removeRecord(HostsRecord $record) {
            $key = array_search($record, $this->records, true);
            if($key === false)
                return false;
            
            unset($this->records[$key]);
            $this->records = array_values($this->records);
        }
    }
    
    /** Represents a single record (line) in the hosts file. */
    final class HostsRecord {
        private $ip = null;
        private $hostNames = [];
        private $comment = null;
        
        /** Initializes this record using given values. */
        public function createNew($ip, $hostNames = [], $comment = null) {
            $this->ip = (strlen($ip) === 0) ? null : $ip;
            $this->hostNames = $hostNames;
            $this->comment = (strlen($comment) === 0) ? null : $comment;
            
            return $this;
        }
        
        /** Initializes this record using a line from the hosts file. */
        public function createFromLine($line) {
            $line = trim($line);
            if(strlen($line) === 0)
                return $this;
            
            $commentPosition = strpos($line, '#');
            if($commentPosition !== false) {
                $this->comment = substr($line, $commentPosition + 1);
                $line = substr($line, 0, $commentPosition);
            }
            
            $fields = self::splitByWhitespace($line);
            if(count($fields) < 2)
                return $this;
            $this->ip = $fields[0];
            $this->hostNames = array_slice($fields, 1);
            
            return $this;
        }
        
        /** Returns a line that represents this record in the hosts file. */
        public function __toString() {
            $fields = [];
            
            if($this->ip !== null && count($this->hostNames) !== 0) {
                $fields[] = $this->ip;
                $fields[] = implode(' ', $this->hostNames);
            }
            
            if($this->comment !== null)
                $fields[] = '#' . $this->comment;
            
            return implode("\t", $fields) . "\r\n";
        }
        
        /** Returns the IP address for this record. If it has no IP address, returns null. */
        public function getIP() {
            return $this->ip;
        }
        
        /** Returns the array of host names in this record. */
        public function getHostNames() {
            return $this->hostNames;
        }
        
        /** Adds a host name to this record. */
        public function addHostName($hostName) {
            $key = $this->getHostNameKey($hostName);
            if($key !== false)
                throw new RuntimeException('Host name is already included in this record!');
            
            $this->hostNames[] = $hostName;
        }
        
        /**
         * Removes given host name from this record. NOTE: it is possible to remove the only host name from a record.
         */
        public function removeHostName($hostName) {
            $key = $this->getHostNameKey($hostName);
            if($key === false)
                throw new RuntimeException('Host name does not belong to this record!');
            
            unset($this->hostNames[$key]);
            $this->hostNames = array_values($this->hostNames);
        }
        
        /**
         * Returns the key in $this->hostNames for a given host name. Returns false if that host name does not belong to
         * this record.
         */
        private function getHostNameKey($hostName) {
            return array_search(strtolower($hostName), array_map('strtolower', $this->hostNames), true);
        }
        
        /** Splits a string by whitespace characters. */
        private static function splitByWhitespace($string) {
            $segments = [];
            
            $currentSegment = '';
            for($i = 0, $len = strlen($string); $i < $len; ++$i) {
                $isWhitespace = ctype_space($string[$i]);
                if(!$isWhitespace)
                    $currentSegment .= $string[$i];
                
                if(($isWhitespace || $i === ($len - 1)) && strlen($currentSegment) !== 0) {
                    $segments[] = $currentSegment;
                    $currentSegment = '';
                }
            }
            
            return $segments;
        }
    }