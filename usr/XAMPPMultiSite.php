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
    
    require_once(__DIR__ . '/HostsFile.php');
    require_once('Config.php'); // PEAR::Config is already included in XAMPP
    
    const LOCALHOST = '127.0.0.1';
    
    $options = getopt('', [ 'install', 'remove', 'localhost:', 'sites:', 'ignore:', 'vhosts:' ]);
    
    if((!isset($options['install']) && !isset($options['remove']))
       || !isset($options['localhost']) || $options['localhost'] === false
       || !isset($options['sites']) || $options['sites'] === false
       || !isset($options['vhosts']) || $options['vhosts'] === false) {
        echo "(Un)registers all directories in a given directory as local websites.\n",
             "\n",
             "Usage: {$argv[0]} [--install|--remove] --localhost --sites [--ignore] --vhosts\n",
             "    --install   Causes the script to register websites.\n",
             "    --remove    Causes the script to unregister websites.\n",
             "    --localhost Path to localhost root directory in your XAMPP installation.\n",
             "    --sites     Path to the directory that contains local websites that are\n",
             "                represented by its subdirectories. The contents of a\n",
             "                subdirectory is the root of a website and its name is the\n",
             "                domain name of a website.\n",
             "    --ignore    Comma-delimited list of directories in that shouldn't be\n",
             "                treated as websites.\n",
             "    --vhosts    Path to the virtual hosts config file.\n";
        exit();
    }
    
    $isInstall = isset($options['install']);
    $localhostPath = $options['localhost'];
    
    $siteDirectoryPaths = getSubdirectories($options['sites'],
                                            (!isset($options['ignore']) || $options['ignore'] === false)
                                                ? [] : explode(',', $options['ignore']));

    $vhostsConfigPath = $options['vhosts'];
    
    echo "Reading configuration files:\n";
    
    $hostsFile = null;
    try {
        $hostsFile = new HostsFile(getenv('windir') . '/system32/drivers/etc/hosts');
        echo "    Hosts file: done\n";
    }
    catch(Exception $e) {
        echo '    Hosts file:', $e->getMessage(), "\n";
        echo "Fatal error!\n";
        exit();
    }
    
    $vhostsConfig = (new Config())->parseConfig($vhostsConfigPath, 'apache');
    if(!PEAR::isError($vhostsConfig))
        echo "    Virtual hosts file: done\n";
    else {
        echo '    Virtual hosts file: ', $vhostsConfig->getMessage(), "\n";
        echo "Fatal error!\n";
        exit();
    }
    
    if($isInstall) {
        try {
            addVirtualHost($vhostsConfig, 'localhost', $localhostPath, false);
            echo "Added localhost entry to virtual hosts file.\n";
        }
        catch(Exception $e) {
            echo "Localhost entry already exists in virtual hosts file.\n";
        }
        
        echo "Registering websites:\n";
        foreach($siteDirectoryPaths as $siteDirectoryPath) {
            $siteDomain = basename($siteDirectoryPath);
            echo '    Registering ', $siteDomain, ": \n";
            
            try {
                $hostsFile->addAlias(LOCALHOST, $siteDomain);
                echo "        Hosts file: done\n";
            }
            catch(Exception $e) {
                echo '        Hosts file: ', $e->getMessage(), "\n";
            }
            
            try {
                addVirtualHost($vhostsConfig, $siteDomain, $siteDirectoryPath);
                echo "        Virtual hosts file: done\n";
            }
            catch(Exception $e) {
                echo '        Virtual hosts file: ', $e->getMessage(), "\n";
            }
        }
    }
    else {
        echo "Unregistering websites:\n";
        foreach($siteDirectoryPaths as $siteDirectoryPath) {
            $siteDomain = basename($siteDirectoryPath);
            echo '    Unegistering ', $siteDomain, ": \n";
            
            try {
                $hostsFile->removeAlias($siteDomain);
                echo "        Hosts file: done\n";
            }
            catch(Exception $e) {
                echo '        Hosts file: ', $e->getMessage(), "\n";
            }
            
            try {
                removeVirtualHost($vhostsConfig, $siteDomain);
                echo "        Virtual hosts file: done\n";
            }
            catch(Exception $e) {
                echo '        Virtual hosts file: ', $e->getMessage(), "\n";
            }
        }
    }
    
    echo "Writing changes:\n";
    
    try {
        $hostsFile->write();
        echo "    Hosts file: done\n";
    }
    catch(Exception $e) {
        echo '    Hosts file: ', $e->getMessage(),"\n";
        echo "Fatal error!\n";
        exit();
    }
    
    if(file_put_contents($vhostsConfigPath, $vhostsConfig->toString('apache')) !== false)
        echo "    Virtual hosts config file: done\n";
    else {
        echo "    Virtual hosts config file: Cannot write the file!\n";
        echo "Fatal error!\n";
        exit();
    }
    
    echo "\nDone! Please restart httpd!\n";
    
    /** Returns paths to all subdirectories in $parentPath that are not listed in $ignoredBasenames. */
    function getSubdirectories($parentPath, $ignoredBasenames) {
        $paths = [];
        foreach(scandir($parentPath) as $basename) {
            $path = realpath($parentPath . '/' . $basename);
            if($basename === '.' || $basename === '..' || in_array($basename, $ignoredBasenames, true) || !is_dir($path))
                continue;
            $paths[] = $path;
        }
        return $paths;
    }
    
    /** Adds VirtualHost section to the virtual hosts config file. */
    function addVirtualHost(Config_Container $config, $serverName, $rootPath, $describeDirectory = true) {
        if(_getVirtualHostSection($config, $serverName) !== null)
            throw new RuntimeException('VirtualHost is already registered!');
        
        $config->createBlank();
        $virtualHost = $config->createSection('VirtualHost', [ '*:80' ]);
        $virtualHost->createComment('WARNING: automatically generated section, any modifications will be lost!');
        $virtualHost->createDirective('ServerName', $serverName);
        $virtualHost->createDirective('DocumentRoot', '"' . $rootPath . '"');
        if($describeDirectory) {
            $directory = $virtualHost->createSection('Directory', [ '"' . $rootPath . '"' ]);
            $directory->createDirective('Options', 'Indexes FollowSymLinks Includes ExecCGI');
            $directory->createDirective('AllowOverride', 'All');
            $directory->createDirective('Require', 'all granted');
        }
    }
    
    /** Removes VirtualHost section from the virtual hosts config file. */
    function removeVirtualHost(Config_Container $config, $serverName) {
        $section = _getVirtualHostSection($config, $serverName);
        if($section === null)
            throw new RuntimeException('VirtualHost is not registered!');
        $section->removeItem();
    }
    
    /**
     * Finds VirtualHost section int the virtual hosts config file with a given server name. Returns null if the
     * section doesn't exist.
     */
    function _getVirtualHostSection(Config_Container $config, $serverName) {
        foreach($config->children as $section) {
            if($section->type !== 'section' || $section->name !== 'VirtualHost')
                continue;
            foreach($section->children as $directive) {
                if($directive->type === 'directive' && $directive->name === 'ServerName'
                   && $directive->content === $serverName)
                    return $section;
            }
        }
        return null;
    }