<?php
    /**
     * File containing: Scripts class
     * 
     * @copyright Copyright 2013 {@link http://www.onevoice.no One Voice AS} 
     *
     * @since 08. July 2013, v. 1.00
     * @author Kenneth Gulbrandsøy <kenneth@onevoice.no>
     */

    namespace OneVoice\Composer;
    use Composer\Script\Event;
    use Composer\Factory;

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
         * @since 08. July 2013, v. 1.00
         * @return void
         */
        public static function perform(Event $objEvent) 
        {
            $aryJSON = json_decode(file_get_contents(Factory::getComposerFile()), true);
            if(isset($aryJSON["delete"])) {
                Scripts::delete($objEvent, Scripts::getVendorDir($objEvent), $aryJSON["delete"]);
            }
        }// perform
        
        
        /**
         * Get vendor directory
         * 
         * @param \Composer\Script\Event $objEvent
         * 
         * @since 14. september 2015, v. 1.10
         * @return string
         */
        private static function getVendorDir($objEvent)
        {
            $strVendorDir = $objEvent->getComposer()->getConfig()->get('vendor-dir');

            // Check if path is absolute
            if (substr($strVendorDir, 0, 1) === '/' || substr($strVendorDir, 1, 1) === ':') {
                return $strVendorDir;
            }

            if(getenv('COMPOSER')) {
                return trim(getenv('COMPOSER')).DIRECTORY_SEPARATOR.$strVendorDir;
            }

            return getcwd().DIRECTORY_SEPARATOR.$strVendorDir;
        }// getVendorDir
        
        
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
         * @param Event $objEvent
         * @param string $strVendorDir Composer vendor directory (full path)
         * @param array $aryDelete Parameters
         * 
         * @since 08. July 2013, v. 1.00
         * @return void
         */
        private static function delete(Event $objEvent, $strVendorDir, $aryDelete)
        {
            $objIO = $objEvent->getIO();
            foreach($aryDelete as $strPackage => $mxdFileSet) {
                $strPackageDir = "$strVendorDir/$strPackage";
                if(!is_dir($strPackageDir)) {
                    trigger_error("[$strPackage] not deleted: Directory [$strPackageDir] not found.");
                }
                
                $aryFileSet = Scripts::toFileSet($mxdFileSet, "delete");
                if($aryFileSet === "*") {
                    $aryInclude = "*";
                    $aryExclude = array();
                } else {
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
                    if(is_file($strPath)) {
                        unlink($strPath);
                        $objIO->overwrite("  Deleted $strPath", false);
                    } elseif(is_dir($strPath) && count(glob($strPath."*/*")) === 0) {
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
         * @param mixed $mxdData File selection data
         * @param string $strTarget Target in selection date
         * @param array $aryDefault Default value when empty target
         * 
         * @since 08. July 2013, v. 1.00
         * @return mixed
         */
        private static function toFileSet($mxdData, $strTarget, $aryDefault=null)
        {
            if(isset($mxdData[$strTarget])) {
                $mxdData = $mxdData[$strTarget];
            } elseif(isset($aryDefault)) {
                $mxdData = $aryDefault; 
            }
            
            if(!($mxdData === "*" || is_array($mxdData))) {
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
         * @return string
         */
        private static function toRegex($strRoot, $aryInclude, $aryExclude) 
        {
            if($aryInclude === "*" && $aryExclude === "*") {
                return ".*";
            }
            
            $strExclude = "\.|\.\.";
            if(is_array($aryExclude)) {
                foreach($aryExclude as $strRegex) {
                    $strExclude .= "|";
                    $strExclude .= rtrim($strRoot.DIRECTORY_SEPARATOR.preg_quote($strRegex), DIRECTORY_SEPARATOR);
                }
                $strExclude = "(?<!$strExclude)";
            }
            
            if(is_array($aryInclude)) {
                $strRegex = "";
                foreach($aryInclude as $strInclude) {
                    if($strRegex) {
                        $strRegex .= "|";
                    }
                    $strRegex .= rtrim($strRoot.DIRECTORY_SEPARATOR.preg_quote($strInclude), DIRECTORY_SEPARATOR).$strExclude;
                }
            } else {
                $strRegex = ".*$strExclude";
            }
            
            return $strRegex;
        }// toRegex


    }// Scripts


