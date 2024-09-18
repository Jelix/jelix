<?php
/**
 * Initialize all defines and includes necessary files.
 *
 * @package  jelix
 * @subpackage core
 *
 * @author   Laurent Jouanneau
 * @contributor Loic Mathaud, Julien Issler
 *
 * @copyright 2005-2023 Laurent Jouanneau
 * @copyright 2007 Julien Issler
 *
 * @see     http://www.jelix.org
 * @licence  GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */

/*
 * Version number of Jelix.
 *
 * @name  JELIX_VERSION
 *
 * @deprecated
 * @see jFramework::version()
 */
define('JELIX_VERSION', '1.8.13-pre');

/*
 * base of namespace path used in xml files of jelix
 * @name  JELIX_NAMESPACE_BASE
 */
define('JELIX_NAMESPACE_BASE', 'http://jelix.org/ns/');

define('JELIX_LIB_PATH', __DIR__.'/');
define('JELIX_LIB_CORE_PATH', JELIX_LIB_PATH.'core/');
define('JELIX_LIB_UTILS_PATH', JELIX_LIB_PATH.'utils/');
define('LIB_PATH', dirname(JELIX_LIB_PATH).'/');

define('BYTECODE_CACHE_EXISTS', function_exists('opcache_compile_file') || function_exists('apc_cache_info') || function_exists('eaccelerator_info') || function_exists('xcache_info'));

error_reporting(E_ALL | E_STRICT);

require JELIX_LIB_CORE_PATH.'jApp.class.php';

require JELIX_LIB_CORE_PATH.'jAppInstance.php';

require JELIX_LIB_CORE_PATH.'jFramework.class.php';

require JELIX_LIB_CORE_PATH.'jelix_api.php';

require JELIX_LIB_CORE_PATH.'jICoordPlugin.iface.php';

require JELIX_LIB_CORE_PATH.'jISelector.iface.php';

require JELIX_LIB_CORE_PATH.'jIActionSelector.iface.php';

require JELIX_LIB_CORE_PATH.'jBasicErrorHandler.class.php';

require JELIX_LIB_CORE_PATH.'jException.class.php';

require JELIX_LIB_CORE_PATH.'jHttpErrorException.class.php';

require JELIX_LIB_CORE_PATH.'jConfig.class.php';

require JELIX_LIB_CORE_PATH.'jSelector.class.php';

require JELIX_LIB_CORE_PATH.'jServer.class.php';

require JELIX_LIB_CORE_PATH.'jUrlBase.class.php';

require JELIX_LIB_CORE_PATH.'jUrlAction.class.php';

require JELIX_LIB_CORE_PATH.'jUrl.class.php';

require JELIX_LIB_CORE_PATH.'jCoordinator.class.php';

require JELIX_LIB_CORE_PATH.'jController.class.php';

require JELIX_LIB_CORE_PATH.'jRequest.class.php';

require JELIX_LIB_CORE_PATH.'jResponse.class.php';

require JELIX_LIB_CORE_PATH.'jBundle.class.php';

require JELIX_LIB_CORE_PATH.'jLocale.class.php';

require JELIX_LIB_CORE_PATH.'jLog.class.php';

require JELIX_LIB_CORE_PATH.'jIncluder.class.php';

require JELIX_LIB_CORE_PATH.'jSession.class.php';

require JELIX_LIB_CORE_PATH.'Services.php';

require JELIX_LIB_UTILS_PATH.'Utilities.php';

/**
 * @deprecated use \Jelix\Routing\UrlMapping\CustomUrlHandlerInterface instead
 */
interface jIUrlSignificantHandler
{
    public function parse($url);

    public function create($urlact, $url);
}

/*
 * contains path for the jelix_autoload function
 * @global array $gLibPath
 * @name $gLibPath
 * @see jelix_autoload()
 */
$GLOBALS['gLibPath'] = array(
    'Config' => JELIX_LIB_PATH.'core/',
    'Selector' => JELIX_LIB_PATH.'core/selector/',
    'Db' => JELIX_LIB_PATH.'db/',
    'Dao' => JELIX_LIB_PATH.'dao/',
    'FormsControl' => JELIX_LIB_PATH.'forms/controls/',
    'Forms' => JELIX_LIB_PATH.'forms/',
    'Event' => JELIX_LIB_PATH.'events/',
    'Tpl' => JELIX_LIB_PATH.'tpl/',
    'Controller' => JELIX_LIB_PATH.'controllers/',
    'Auth' => JELIX_LIB_PATH.'auth/',
    'Installer' => JELIX_LIB_PATH.'installer/',
    'KV' => JELIX_LIB_PATH.'kvdb/',
);

$GLOBALS['gLibClassPath'] = array(
    'jIInstallerComponent' => JELIX_LIB_PATH.'installer/jIInstallerComponent.iface.php',
    'jIFormsDatasource' => JELIX_LIB_PATH.'forms/jIFormsDatasource.iface.php',
    'jIFormsDatasource2' => JELIX_LIB_PATH.'forms/jIFormsDatasource2.iface.php',
    'jIFormsDynamicDatasource' => JELIX_LIB_PATH.'forms/jIFormsDynamicDatasource.iface.php',
);

/**
 * function used by php to try to load an unknown class.
 *
 * @param mixed $class
 */
function jelix_autoload($class)
{
    if (stripos($class, 'jelix') === 0) {
        $class = str_replace(
            array('Jelix', '\\'),
            array('jelix', DIRECTORY_SEPARATOR),
            $class
        );
        if (strpos($class, '/Forms/') !== false) {
            $f = LIB_PATH.str_replace('Forms', 'forms', $class).'.php';
        } elseif (strpos($class, '/Core/') !== false) {
            $f = LIB_PATH.str_replace('Core', 'core', $class).'.php';
        } else {
            $f = LIB_PATH.$class.'.php';
        }
    } elseif (preg_match('/^j(Dao|Selector|Tpl|Event|Db|Controller|Forms(?:Control)?|Auth|Config|Installer|KV).*/i', $class, $m)) {
        $f = $GLOBALS['gLibPath'][$m[1]].$class.'.class.php';
    } elseif (preg_match('/^cDao(?:Record)?_(.+)_Jx_(.+)_Jx_(.+)$/', $class, $m)) {
        // for DAO which are stored in sessions for example
        if (!jApp::isModuleEnabled($m[1])) {
            //this may happen if we have several entry points, but the current one does not have this module accessible
            return;
        }
        $s = new jSelectorDaoDb($m[1].'~'.$m[2], '', $m[3]);
        if (jApp::config()->compilation['checkCacheFiletime']) {
            // if it is needed to check the filetime, then we use jIncluder
            // because perhaps we will have to recompile the dao before the include
            jIncluder::inc($s);
        } else {
            $f = $s->getCompiledFilePath();
            // we should verify that the file is here and if not, we recompile
            // (case where the temp has been cleaned, see bug #6062 on berlios.de)
            if (!file_exists($f)) {
                jIncluder::inc($s);
            } else {
                require $f;
            }
        }

        return;
    } elseif (isset($GLOBALS['gLibClassPath'][$class])) {
        $f = $GLOBALS['gLibClassPath'][$class];
    } else {
        $f = JELIX_LIB_UTILS_PATH.$class.'.class.php';
    }

    if (file_exists($f)) {
        require $f;
    }
}

spl_autoload_register('jelix_autoload');

/**
 * check if the application is opened. If not, it displays the yourapp/install/closed.html
 * file with a http error (or lib/jelix/installer/closed.html), and exit.
 * This function should be called in all entry point, before the creation of the coordinator.
 *
 * @see jAppManager
 *
 * @todo migrate the code to jAppManager or jApp
 */
function checkAppOpened()
{
    if (!jApp::isInit()) {
        header('HTTP/1.1 500 Internal Server Error');
        header('Content-type: text/html');
        echo 'checkAppOpened: jApp is not initialized!';

        exit(1);
    }
    if (file_exists(jApp::varConfigPath('CLOSED'))) {
        $message = file_get_contents(jApp::varConfigPath('CLOSED'));

        if (jServer::isCLI()) {
            echo 'Application closed.'.($message ? "\n{$message}\n" : "\n");

            exit(1);
        }

        // note: we are not supposed to have the configuration loaded here
        // so we cannot use the selected theme or any other configuration parameters
        // like calculated basePath. We mimic what it is done into the configuration compiler
        $basePath = jApp::urlBasePath();
        if ($basePath == null) {
            try {
                $urlScript = $_SERVER[jConfigCompiler::findServerName()];
                $basePath = substr($urlScript, 0, strrpos($urlScript, '/')) . '/';
            } catch (Exception $e) {
                $basePath = '/';
            }
            $themePath = 'themes/default/';
        }
        else {
            $themePath = 'themes/'.jApp::config()->theme.'/';
        }

        // html file installed for the current instance of the application
        if (file_exists(jApp::varPath($themePath.'closed.html'))) {
            $file = jApp::varPath($themePath.'closed.html');
        }
        else if (file_exists(jApp::varPath('themes/closed.html'))) {
            $file = jApp::varPath('themes/closed.html');
        }
        // html file provided by the application
        elseif (file_exists(jApp::appPath('install/closed.html'))) {
            $file = jApp::appPath('install/closed.html');
        }
        // default html file
        else {
            $file = JELIX_LIB_PATH.'installer/closed.html';
        }

        header('HTTP/1.1 503 Application not available');
        header('Content-type: text/html');
        echo str_replace(array(
            '%message%',
            '%basePath%',
            '%themePath%',
        ), array(
            $message,
            $basePath,
            $themePath
        ), file_get_contents($file));

        exit(1);
    }
}

/**
 * check if the application is not installed. If the app is installed, an
 * error message appears and the scripts ends.
 * It should be called only by some scripts
 * like an installation wizard, not by an entry point.
 *
 * @todo migrate the code to jAppManager or jApp
 */
function checkAppNotInstalled()
{
    if (isAppInstalled()) {
        if (jServer::isCLI()) {
            echo "Application is installed. The script cannot be runned.\n";
        } else {
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-type: text/plain');
            echo "Application is installed. The script cannot be runned.\n";
        }

        exit(1);
    }
}

/**
 * @todo migrate the code to jAppManager or jApp
 */
function isAppInstalled()
{
    return file_exists(jApp::varConfigPath('installer.ini.php'));
}
