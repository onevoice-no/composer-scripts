<?php

    /**
     * File containing: Scripts class
     * 
     * @copyright Copyright 2013 {@link http://www.onevoice.no One Voice AS} 
     *
     * @since 08. July 2013, v. 1.00
     * 
     * @author Kenneth Gulbrands�y <kenneth@onevoice.no>
     */

    namespace OneVoice\Composer;
    
    use Composer\Script\Event;

    /**
     * Scripts class
     * 
     * @package cim\Composer
     */
    class Scripts
    {
        /**
         * Perform custom scripts
         * 
         * @param \Composer\Script\Event $objEvent
         * 
         * @since 08. July 2013, v. 8.00
         * 
         * @return void
         */
        public static function perform(Event $objEvent) 
        {
            $strRootDir = realpath(dirname(__FILE__)."/../../../");
            $strJSON = file_get_contents("$strRootDir/composer.json");
            $aryJSON = json_decode($strJSON, true);
            if(isset($aryJSON["delete"])) 
            {
                Scripts::delete($objEvent, $strRootDir, $aryJSON["delete"]);
            }
                        
        }// perform
        
        
        /**
         * Perform "delete" script
         * 
         * Definition example (in composer.json)
         * 
         * <code>
         * "delete": {
         *      "vendor/package": { 
         *          "include": "*",
         *          "exclude": [
         *              "folder/sub/folder",
         *              "folder/file.php"
         *          ]
         *       }
         * }
         * </code>
         * 
         * @param \Composer\Script\Event $objEvent
         * @param string $strRootDir CIM (installation) directory
         * @param array $aryDelete Parameters
         * 
         * @since 08. July 2013, v. 1.00
         * 
         * @return void
         */
        private static function delete(Event $objEvent, $strRootDir, $aryDelete) 
        {
            $objIO = $objEvent->getIO();
            $objConfig = $objEvent->getComposer()->getConfig();
            $strVendorDir = $objConfig->get("vendor-dir");
            foreach($aryDelete as $strPackage => $mxdFileSet)
            {
                $strPackageDir = "$strRootDir/$strVendorDir/$strPackage";
                if(!is_dir($strPackageDir))
                {
                    trigger_error("[$strPackage] not deleted: Directory [$strPackageDir] not found.");
                }
                
                $aryFileSet = Scripts::toFileSet($mxdFileSet, "delete");
                if($aryFileSet === "*")
                {
                    $aryInclude = "*";
                    $aryExclude = array();
                }
                else 
                {
                    $aryInclude = Scripts::toFileSet($aryFileSet, "include", "*");
                    $aryExclude = Scripts::toFileSet($aryFileSet, "exclude", array());
                }
                
                $strMatch = Scripts::toRegex($strPackage, $aryInclude, $aryExclude);
                $objDir = new \RecursiveIteratorIterator(
                    new \RecursiveRegexIterator(
                        new \RecursiveDirectoryIterator(
                            $strPackageDir
                        ), 
                        "#$strMatch$#"
                    ), 
                    \RecursiveIteratorIterator::CHILD_FIRST
                );        
                $objIO->write("Deleting $strPackage ...");
                foreach ($objDir as $objFile) {
                    $strPath = $objFile->getPathname();
                    if(is_file($strPath)){
                        unlink($strPath);
                        $objIO->overwrite("  Deleted $strPath", false);
                    }
                    else if(is_dir($strPath) && count(glob($strPath."*/*")) === 0)
                    {
                        rmdir($strPath);
                        $objIO->overwrite("  Deleted $strPath", false);
                    }
                }
                $objIO->overwrite("Deleting $strPackage ...DONE");
            }
            
        }// delete        
        
        
        /**
         * Get file set from file selection data
         * 
         * @param type $mxdData File selection data
         * @param type $strTarget Target in selection date
         * @param type $aryDefault Default value when empty target
         * 
         * @since 08. July 2013, v. 1.00
         * 
         * @return mixed
         */
        private static function toFileSet($mxdData, $strTarget, $aryDefault=null)
        {
            if(isset($mxdData[$strTarget]))
            {
                $mxdData = $mxdData[$strTarget];
            } 
            else if(isset($aryDefault))
            {
                $mxdData = $aryDefault; 
            }
            
            if(!($mxdData === "*" || is_array($mxdData)))
            {
                trigger_error('"'.$strTarget.'" only accepts "*" and [<files>]');
            }
            return $mxdData;
        }// toFileSet
        
        
        /**
         * Get PCRE compatible regex pattern
         * 
         * @param string $strRoot File set root
         * @param array $aryInclude Include file set
         * @param array $aryExclude Exclude file set
         * 
         * @since 09. July 2013, v. 1.00
         * 
         * @return string
         */
        private static function toRegex($strRoot, $aryInclude, $aryExclude) 
        {
            if($aryInclude === "*" && $aryExclude === "*" ) return ".*";
            
            $strExclude = "\.|\.\.";
            if(is_array($aryExclude))
            {
                foreach($aryExclude as $strRegex)
                {
                    $strExclude .= "|";
                    $strExclude .= $strRoot.DIRECTORY_SEPARATOR.preg_quote($strRegex);
                }
                $strExclude = "(?<!$strExclude)";
            }
            
            if(is_array($aryInclude))
            {
                $strRegex = "";
                foreach($aryInclude as $strInclude)
                {
                    if($strRegex) $strRegex .= "|";
                    $strRegex .= $strRoot.DIRECTORY_SEPARATOR.preg_quote($strInclude).$strExclude;
                }
            }
            else $strRegex = ".*$strExclude";
            
            return $strRegex;
        }// toRegex


    }// Scripts


    