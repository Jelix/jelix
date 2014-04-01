<?php
/**
 * @author     Laurent Jouanneau
 * @copyright  2005-2014 Laurent Jouanneau
 *   Idea of this class was picked from the Copix project (CopixInclude, Copix 2.3dev20050901, http://www.copix.org)
 * @link       http://www.jelix.org
 * @licence    GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */
namespace Jelix\Core;

/**
 * This object is responsible to load cache files.
 * Some jelix files needs to be compiled in PHP (templates, daos etc..) and their
 * correspondant php content are stored in a cache file.
 * Includer verify that cache file exists, and if not, it calls the correspondant compiler.
 * Finally, it includes the cache.
 * @author     Laurent Jouanneau
 * @copyright  2001-2014 Laurent Jouanneau
 * @licence    GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html .
 */
class Includer {
    /**
     * list of loaded cache file.
     * It avoids to do all verification when a file is include many time
     * @var array
     */
    protected static $_includedFiles = array();

    /**
     * This is a static class, so private constructor
     */
    private function __construct() {}

    /**
     * includes cache of the correspondant file selector
     * check the cache, compile if needed, and include the cache
     * @param    Selector\SelectorInterface   $aSelectorId    the selector corresponding to the file
    */
    public static function inc($aSelector) {

        $cachefile = $aSelector->getCompiledFilePath();

        if ($cachefile == '' || isset(self::$_includedFiles[$cachefile])){
            return;
        }

        $mustCompile = App::config()->compilation['force'] || !file_exists($cachefile);

        if (!$mustCompile) {
            // if the cache file has been compiled with checkCacheFiletime=on
            // it verify itself if it is valid
            $isValid = require($cachefile);
            if ($isValid === true) {
                self::$_includedFiles[$cachefile]=true;
                return;
            }
        }

        $sourcefile = $aSelector->getPath();

        if ($sourcefile == '' || !file_exists($sourcefile)) {
            throw new \jException('jelix~errors.includer.source.missing',array( $aSelector->toString(true)));
        }

        $compiler = $aSelector->getCompiler();
        if (!$compiler || !$compiler->compile($aSelector)) {
            throw new \jException('jelix~errors.includer.source.compile',array( $aSelector->toString(true)));
        }
        require($cachefile);
        self::$_includedFiles[$cachefile]=true;
    }

    /**
     * include a cache file which is the results of the compilation of multiple file sotred in multiple modules
    * @param    array    $aType
    *    = array(
    *    'compilator class name',
    *    'relative path of the compilator class file to lib/jelix/',
    *    'foo.xml', // file name to compile (in each modules)
    *    'foo.php',  //cache filename
    *    );
    */
    public static function incAll($aType){

        $cachefile = App::tempPath('compiled/'.$aType[3]);
        if (isset(self::$_includedFiles[$cachefile])) {
            return;
        }

        $config = App::config();
        $mustCompile = $config->compilation['force'] || !file_exists($cachefile);

        if (!$mustCompile && $config->compilation['checkCacheFiletime']) {
            $compiledate = filemtime($cachefile);
            foreach ($config->_modulesPathList as $module=>$path) {
                $sourcefile = $path.$aType[2];
                if (is_readable ($sourcefile)) {
                    if (filemtime($sourcefile) > $compiledate) {
                        $mustCompile = true;
                        break;
                    }
                }
            }
        }

        if ($mustCompile) {
            if ($aType[1]) {
                require_once(JELIX_LIB_PATH.$aType[1]);
            }
            $compiler = new $aType[0];
            $compileok = true;
            foreach ($config->_modulesPathList as $module=>$path) {
                $compileok = $compiler->compileItem($path.$aType[2], $module);
                if (!$compileok) {
                    break;
                }
            }

            if ($compileok) {
                $compiler->endCompile($cachefile);
                require($cachefile);
                self::$_includedFiles[$cachefile] = true;
            }
        }
        else {
            require($cachefile);
            self::$_includedFiles[$cachefile] = true;
        }
    }
}
