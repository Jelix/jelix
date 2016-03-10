<?php
/**
* @package     jelix-scripts
* @author      Laurent Jouanneau
* @contributor Loic Mathaud
* @copyright   2005-2016 Laurent Jouanneau
* @link        http://jelix.org
* @licence     GNU General Public Licence see LICENCE file or http://www.gnu.org/licenses/gpl.html
*/
namespace Jelix\DevHelper\Command;
use Symfony\Component\Console\Application;


error_reporting(E_ALL);
define ('JELIX_SCRIPTS_PATH', __DIR__.'/../');

if (!\jServer::isCLI()) {
    echo "Error: you're not allowed to execute this script outside a command line shell.\n";
    exit(1);
}
require(JELIX_SCRIPTS_PATH.'includes/JelixScript.class.php');
if (!\jApp::isInit()) {
    echo "Error: should run within an application\n";
    exit(1);
}

\jApp::setEnv('jelix-scripts');
\JelixScript::checkTempPath();

$jelixScriptConfig = \JelixScript::loadConfig();

$application = new Application();
$application->add(new InstallApp($jelixScriptConfig));
$application->add(new CreateCtrl($jelixScriptConfig));
$application->add(new CreateModule($jelixScriptConfig));
$application->add(new CreateEntryPoint($jelixScriptConfig));
$application->add(new ClearTemp($jelixScriptConfig));
$application->add(new CloseApp($jelixScriptConfig));
$application->add(new OpenApp($jelixScriptConfig));

if(!defined('DECLARE_MYCOMMANDS')) {
    $application->run();
}
