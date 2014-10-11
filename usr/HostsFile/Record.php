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
    
    require_once(__DIR__ . '/Exception.php');
    
    /**
     * A record in the hosts file.
     * @copyright 2014 Vladimir P.
     * @license MIT
     */
    final class Record {
        /**
         * IP address of this record.
         * @var string|null
         */
        private $ip = null;
        
        /**
         * Hostnames that will be forwarded to this record's IP address.
         * @var string[]
         */
        private $hostnames = [];
        
        /**
         * Comment attached to this record.
         * @var string|null
         */
        private $comment = null;
        
        /**
         * Initializes the record with given values.
         * @param string|null $ip
         * @param string[] $hostnames
         * @param string|null $comment
         * @return Record
         */
        public function createNew($ip, $hostnames = [], $comment = null) {
            $this->ip = (strlen($ip) === 0) ? null : $ip;
            $this->hostnames = $hostnames;
            $this->comment = (strlen($comment) === 0) ? null : $comment;
            
            return $this;
        }
        
        /**
         * Initializes the record using a line from the hosts file.
         * @param string $line Line from the hosts file.
         * @return Record
         */
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
            $this->hostnames = array_slice($fields, 1);
            
            return $this;
        }
        
        /**
         * Returns a line that would represent this record in the hosts file.
         * @return string
         * @throws Exception If the record cannot be represented as a valid hosts file entry.
         */
        public function __toString() {
            $fields = [];
            
            if($this->ip === null xor count($this->hostnames) === 0)
                throw Exception('A record must have both an IP address and a hostname or neither of them.');
            elseif($this->ip !== null && count($this->hostnames) !== 0) {
                $fields[] = $this->ip;
                $fields[] = implode(' ', $this->hostnames);
            }
            
            if($this->comment !== null)
                $fields[] = '#' . $this->comment;
            
            return implode("\t", $fields) . "\r\n";
        }
        
        /**
         * Returns the IP address for this record. If it has no IP address, returns null.
         * @return string|null
         */
        public function getIP() {
            return $this->ip;
        }
        
        /**
         * Returns the array of host names in this record.
         * @return string[]
         */
        public function getHostnames() {
            return $this->hostnames;
        }
        
        /**
         * Adds a hostname to this record.
         * @param string $hostname
         * @throws Exception If this record already has given hostname.
         */
        public function addHostname($hostname) {
            $key = $this->getHostnameKey($hostname);
            if($key !== false)
                throw new Exception('Hostname is already included in this record!');
            
            $this->hostnames[] = $hostname;
        }
        
        /**
         * Removes given host name from this record. NOTE: it is possible to remove the only host name from a record.
         * @param string $hostname
         */
        public function removeHostname($hostname) {
            $key = $this->getHostnameKey($hostname);
            if($key === false)
                throw new RuntimeException('Hostname does not belong to this record!');
            
            unset($this->hostnames[$key]);
            $this->hostnames = array_values($this->hostnames);
        }
        
        /**
         * Returns the key in $this->hostnames array for a given host name.
         * @param string $hostname
         * @retutn int|boolean Key in the array or false if that host name does not belong to this record.
         */
        private function getHostnameKey($hostname) {
            return array_search(strtolower($hostname), array_map('strtolower', $this->hostnames), true);
        }
        
        /**
         * Splits a string by whitespace characters.
         * @param string $str
         * @return string[]
         */
        private static function splitByWhitespace($str) {
            $segments = [];
            
            $currentSegment = '';
            for($i = 0, $len = strlen($str); $i < $len; ++$i) {
                $isWhitespace = ctype_space($str[$i]);
                if(!$isWhitespace)
                    $currentSegment .= $str[$i];
                
                if(($isWhitespace || $i === ($len - 1)) && strlen($currentSegment) !== 0) {
                    $segments[] = $currentSegment;
                    $currentSegment = '';
                }
            }
            
            return $segments;
        }
    }