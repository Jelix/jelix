<?php
/**
* @package     testapp
* @subpackage  jelix_tests module
* @author      Laurent Jouanneau
* @contributor
* @copyright   2009-2017 Laurent Jouanneau
* @link        http://jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
* @since 1.2
*/

require_once(JELIX_LIB_PATH.'installer/jInstaller.class.php');


class testInstallerAppInfos extends \Jelix\Core\Infos\AppInfos {

    function save() {
        return true;
    }
}

class testInstallerProjectParser extends \Jelix\Core\Infos\ProjectXmlParser {

    protected function createInfos() {
        return new testInstallerAppInfos($this->path, true);
    }
}

class testFrameworkInfos extends \Jelix\Core\Infos\FrameworkInfos
{
    function save() {
        return true;
    }
}

class testInstallerModuleInfos extends \Jelix\Core\Infos\ModuleInfos {

    function save() {
        return true;
    }
}

class testInstallerModuleParser extends \Jelix\Core\Infos\ModuleXmlParser {

    protected function createInfos() {
        return new testInstallerModuleInfos($this->path, true);
    }
}




class testInstallerGlobalSetup extends \Jelix\Installer\GlobalSetup {

    public $configContent = array();

    function __construct ($frameworkFileName = null,
                          $mainConfigFileName = null,
                          $localConfigFileName = null,
                          $urlXmlFileName = null,
                          $urlLocalXmlFileName = null
    ) {
        foreach(array(
            'index', 'rest', 'soap', 'jsonrpc', 'xmlrpc', 'cmdline'
                ) as $epName
        ) {
            $this->configContent[$epName.'/config.ini.php'] = array(
                'dbProfils'=>"default",
                "disableInstallers"=>false,
                "enableAllModules"=>false,
                'modules'=>array(
                ),
                'urlengine'=>array('urlScriptId'=>$epName,
                    'urlScript'=>"/$epName.php",
                    'urlScriptPath'=>"/",
                    'urlScriptName'=>"$epName.php",
                    'urlScriptId'=>"$epName",
                    'urlScriptIdenc'=>"$epName"
                ),
                '_allModulesPathList'=>array(
                ),
                '_allBasePath'=>array(
                    0=>"/app/lib/jelix-modules/",
                    1=>"/app/testapp/modules/",
                    2=>"/app/lib/jelix-plugins/cache/",
                ),
                '_modulesPathList'=>array(
                ),
            );
        }

        if (!$frameworkFileName) {
            $frameworkFileName = testFrameworkInfos::load();
        }

        parent::__construct($frameworkFileName,
            $mainConfigFileName,
            $localConfigFileName,
            $urlXmlFileName,
            $urlLocalXmlFileName
        );

    }

    function setInstallerIni($installerIni) {
        $this->installerIni = $installerIni;
    }

    protected function createEntryPointObject($configFile, $file, $type) {
        return new testInstallerEntryPoint($this,
            $configFile, $file, $type,
            (object) $this->configContent[$configFile]);
    }

    protected function createComponentModule($name, $path) {
        $moduleSetupList = $this->mainEntryPoint->getConfigObj()->modules;
        $moduleInfos = new \Jelix\Installer\ModuleStatus($name, $path, $moduleSetupList);

        if (in_array($name, array('jelix','jacl', 'jacl2db','jacldb','jauth','jauthdb','jsoap'))) {
            return new \Jelix\Installer\ModuleInstallerLauncher($moduleInfos, $this);
        }
        else {
            return new testInstallerComponentModule($moduleInfos, $this);
        }
    }
}


class testInstallerComponentModule extends \Jelix\Installer\ModuleInstallerLauncher {

}

class testInstallerEntryPoint extends \Jelix\Installer\EntryPoint {

    function __construct(\Jelix\Installer\GlobalSetup $globalSetup,
                         $configFile, $file, $type, $configContent) {
        $this->type = $type;
        $this->_isCliScript = ($type == 'cmdline');
        $this->configFileName = $configFile;
        $this->scriptName =  ($this->isCliScript()?$file:'/'.$file);
        $this->file = $file;
        $this->globalSetup = $globalSetup;

        $appSystemPath = \jApp::appSystemPath($configFile);
        if (!file_exists($appSystemPath)) {
            \jFile::createDir(dirname($appSystemPath));
            file_put_contents($appSystemPath, ';<' . '?php die(\'\');?' . '>');
        }
        $varConfigPath = \jApp::varConfigPath($configFile);

        $this->appEpConfigIni = new testInstallerIniFileModifier($appSystemPath);
        $this->localEpConfigIni = new testInstallerIniFileModifier($varConfigPath);

        $this->config = $configContent;
    }
    
    function getEpId() {
        return str_replace('.php', '', $this->file);
    }
}

/**
 *
 */
class testInstallReporter implements \Jelix\Installer\Reporter\ReporterInterface {
    use \Jelix\Installer\Reporter\ReporterTrait;

    public $startCounter = 0;

    public $endCounter = 0;
    
    public $messages = array();

    function start() {
        $this->startCounter ++;
    }

    function message($message, $type='') {
        $this->addMessageType($type);
        $this->messages[] = array($message, $type);
    }

    function end() {
        $this->endCounter ++;
    }
}


/**
 * ini file modifier without file load/save supports
 */
class testInstallerIniFileModifier extends \Jelix\IniFile\IniModifier {

    function __construct($filename, $initialContent='') {
        $this->filename = $filename;
        if ($initialContent != '') {
            $this->parse(preg_split("/(\r\n|\n|\r)/", $initialContent));
        }
    }

    public function save($chmod=null) {
        $this->modified = false;
    }

    public function saveAs($filename) {}
}

/**
 * mockup class for Jelix\Installer\Installer
 */
class testInstallerMain extends \Jelix\Installer\Installer {

    public $moduleXMLDesc = array();

    function __construct ($reporter) {
        $this->reporter = $reporter;
        $this->messages = new \Jelix\Installer\Checker\Messages('en');

        copy (jApp::appSystemPath('urls.xml'), jApp::tempPath('installer_urls.xml'));
        $this->globalSetup = new testInstallerGlobalSetup(null, null, null, jApp::tempPath('installer_urls.xml'));

        $nativeModules = array('jelix','jacl', 'jacl2db','jacldb','jauth','jauthdb','jsoap');
        $config = jApp::config();
        foreach ($this->globalSetup->configContent as $ep=>$conf) {
            
            foreach($nativeModules as $module) {
                $this->globalSetup->configContent[$ep]['modules'][$module.'.enabled'] = ($module == 'jelix');
                $this->globalSetup->configContent[$ep]['modules'][$module.'.dbprofile'] = 'default';
                $this->globalSetup->configContent[$ep]['modules'][$module.'.installed'] = 0;
                $this->globalSetup->configContent[$ep]['modules'][$module.'.version'] = jFramework::version();
                $this->globalSetup->configContent[$ep]['_modulesPathList'][$module] = $config->_modulesPathList[$module];
                $this->globalSetup->configContent[$ep]['_allModulesPathList'][$module] = $config->_modulesPathList[$module];
            }
        }

    }

    function testAddModule($name, $moduleXML, $enabled = false, $installed = 0, $version = '1.0', $dbprofile='default') {
        $this->moduleXMLDesc[$name] = $moduleXML;
        foreach($this->globalSetup->configContent as $ep=>$conf) {
            $this->globalSetup->configContent[$ep]['_allModulesPathList'][$name] = "/app/test/modules/$name/";
            $this->globalSetup->configContent[$ep]['_modulesPathList'][$name] = "/app/test/modules/$name/";
            $this->globalSetup->configContent[$ep]['modules'][$name.'.enabled'] = $enabled;
            $this->globalSetup->configContent[$ep]['modules'][$name.'.dbprofile'] = $dbprofile;
            $this->globalSetup->configContent[$ep]['modules'][$name.'.installed'] = $installed;
            $this->globalSetup->configContent[$ep]['modules'][$name.'.version'] = $version;
        }   
    }
}
