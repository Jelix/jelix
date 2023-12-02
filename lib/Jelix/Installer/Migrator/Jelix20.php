<?php
/**
 * @package     jelix
 *
 * @author      Laurent Jouanneau
 * @copyright   2023 Laurent Jouanneau
 *
 * @see        http://www.jelix.org
 * @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */

namespace Jelix\Installer\Migrator;

use Jelix\Core\App;
use Jelix\FileUtilities\Directory;
use Jelix\IniFile\IniModifier;
use Jelix\Routing\UrlMapping\XmlMapModifier;
use Jelix\Routing\UrlMapping\XmlRedefinedMapModifier;

/**
 * Migration from Jelix 1.8 to Jelix 2.0
 *
 * The process should be idempotent
 */
class Jelix20
{
    /**
     * the object responsible on the results output.
     *
     * @var \Jelix\Installer\Reporter\ReporterInterface
     */
    protected $reporter;

    /**
     * @var \Jelix\IniFile\IniReader
     */
    protected $defaultConfigIni;

    public function __construct(\Jelix\Installer\Reporter\ReporterInterface $reporter)
    {
        $this->reporter = $reporter;
        $this->defaultConfigIni = new \Jelix\IniFile\IniReader(\Jelix\Core\Config\AppConfig::getDefaultConfigFile());
    }

    protected function getUrlMapModifier($mainConfigIni)
    {
        $urlFile = $mainConfigIni->getValue('significantFile', 'urlengine');
        if ($urlFile == null) {
            $urlFile = 'urls.xml';
        }
        $urlXmlFileName = App::appSystemPath($urlFile);
        $urlMapModifier = new XmlMapModifier($urlXmlFileName, true);
        return $urlMapModifier;
    }

    protected function getLocalUrlMapModifier($urlMapModifier, $localConfigIni)
    {
        $urlFile = $localConfigIni->getValue('localSignificantFile', 'urlengine');
        if ($urlFile == null) {
            $urlFile = 'localurls.xml';
        }
        $urlLocalXmlFileName = App::varConfigPath($urlFile);
        if (!file_exists($urlLocalXmlFileName)) {
            return null;
        }
        $urlLocalMapModifier = new XmlRedefinedMapModifier($urlMapModifier, $urlLocalXmlFileName);
        return $urlLocalMapModifier;
    }


    public function migrate()
    {
        $this->reporter->message('Start migration to Jelix 2.0.0', 'notice');
        $mainConfigIni = new IniModifier(App::appSystemPath('mainconfig.ini.php'));
        $urlMapModifier = $this->getUrlMapModifier($mainConfigIni);

        $this->removeEntrypointsForScripts($urlMapModifier, true);

        $this->reporter->message('Migration to Jelix 2.0.0 is done', 'notice');

    }


    public function localMigrate()
    {
        $localConfigIni = new IniModifier(App::varConfigPath('localconfig.ini.php'));
        $mainConfigIni = new IniModifier(App::appSystemPath('mainconfig.ini.php'));

        $urlMapModifier = $this->getUrlMapModifier($mainConfigIni);
        $localUrlMapModifier = $this->getLocalUrlMapModifier($urlMapModifier, $localConfigIni);

        $this->removeEntrypointsForScripts($localUrlMapModifier, false);

        Directory::create(App::buildPath());
        file_put_contents(App::buildPath('.dummy'), '');

        $this->reporter->message('Migration of local configuration to Jelix 2.0.0 is done', 'notice');
    }


    public function removeEntrypointsForScripts(XmlMapModifier $urlMapModifier, $onlyApp)
    {
        $frameworkFileName = App::appSystemPath('framework.ini.php');
        if ($onlyApp) {
            $frameworkInfos = new \Jelix\Core\Infos\FrameworkInfos($frameworkFileName);
        }
        else {
            $localFrameworkFileName = App::varConfigPath('localframework.ini.php');
            $frameworkInfos = new \Jelix\Core\Infos\FrameworkInfos($frameworkFileName, $localFrameworkFileName);
        }

        $scriptsPath = App::appPath('scripts');

        foreach($frameworkInfos->getEntryPoints() as $ep) {
            if ($ep->getType() == 'cmdline') {

                if ($onlyApp) {
                    $conf = App::appSystemPath($ep->getConfigFile());
                    if (file_exists($conf)) {
                        unlink($conf);
                        $this->reporter->message('Delete configuration app/system/'.$ep->getConfigFile().' for scripts/'.$ep->getFile().' (not supported anymore)', 'notice');
                    }
                    else {
                        $this->reporter->message('Cannot remove configuration app/system/'.$ep->getConfigFile().' of scripts/'.$ep->getFile().' : it is not found', 'warning');
                    }
                }
                else {
                    $conf = App::varConfigPath($ep->getConfigFile());
                    if (file_exists($conf)) {
                        unlink($conf);
                        $this->reporter->message('Delete configuration var/config/'.$ep->getConfigFile().' for scripts/'.$ep->getFile().' (not supported anymore)', 'notice');
                    }
                    else {
                        $this->reporter->message('Cannot remove configuration var/config/'.$ep->getConfigFile().' of scripts/'.$ep->getFile().' : it is not found', 'warning');
                    }
                }

                $file = $scriptsPath.'/'.$ep->getFile();
                if( file_exists($file)) {
                    $this->reporter->message('Delete scripts/'.$ep->getFile().' (not supported anymore)', 'notice');
                    unlink($file);
                }
                else {
                    $this->reporter->message('The file scripts/'.$ep->getFile().' must be deleted  (not supported anymore), but it is not found', 'warning');
                }

                if ($urlMapModifier) {
                    $urlMapModifier->removeEntryPoint($ep->getId());
                }

                $frameworkInfos->removeEntryPointInfo($ep->getId());
            }
        }

        if ($urlMapModifier) {
            $urlMapModifier->save();
        }
        $frameworkInfos->save();
    }


}
