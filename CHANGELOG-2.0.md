# Changes into Jelix 2.0

- minimum PHP version is 8.1.0

- Many Jelix classes are now under a namespace, but some classes with old names
  still exist to ease the transition, although it is recommended to use new name
  as these old classes are deprecated.

- `jApp::coord()` is replaced by `\Jelix\Core\App::router()`

- The `charset` configuration property is deprecated. Everything in Jelix works
  with the UTF-8 charset from now. Locales must be only in UTF-8.

- project.xml can be replaced by a json file named jelix-app.json
- module.xml can be replaced by a json file named jelix-module.json

- module.xml: 'creator' and 'contributor' elements changed to 'author'
- module.xml: 'minversion' and 'maxversion' are changed to 'version'
    Same syntax in this new attribute as in composer.json

- Remove support of infoIDSuffix from jelix-scripts.ini files

- Functions declared into the namespace `Jelix\Utilities` are now into the namespace `Jelix\Core\Utilities`

- the script runtests.php and the unit test mechanism for modules
  (tests inside modules) are now gone. See upgrade instructions.
- the modules jacl and jacldb are not provided anymore. Use jacl2 and jacl2db instead.

- remove support of the deprecated command line scripts system of Jelix <=1.6. Only Symphony console scripts are supported from now.

- remove the deprecated jforms builder based on `jFormsBuilderBase` and `jFormsBuilderHtml`
  and so, builders named `legacy.html` or `legacy.htmllight`
- remove binding feature from jClasses

## changes in jDb

jDb is now relying on [JelixDatabase](https://github.com/jelix/JelixDatabase).
The `jDb` class is still existing, but most of internal classes of jDb
are gone and replaced by classes of JelixDatabase:

- `jDbConnection` and `jDbPDOConnection` are replaced by objects implementing `Jelix\Database\ConnectionInterface`
- `jDbResultSet` and `jDbPDOResultSet` are replaced by objects implementing `Jelix\Database\ResultSetInterface`
- `jDbParameters` is deprecated and replaced by `\Jelix\Database\AccessParameters`
- `jDbTools` is  replaced by objects implementing `Jelix\Database\Schema\SqlToolsInterface`
- `jDbSchema` is replaced by objects implementing `Jelix\Database\Schema\SchemaInterface`
- `jDbIndex`, `jDbConstraint`, `jDbUniqueKey`, `jDbPrimaryKey`, `jDbReference`,
  `jDbColumn`, `jDbTable` are replaced by some classes of the `Jelix\Database\Schema\` namespace.
- `jDbUtils::getTools()` is deprecated and is replaced by `\Jelix\Database\Connection::getTools()` 
- `jDbWidget` is deprecated and replaced by `Jelix\Database\Helpers`
- `jDaoDbMapper::createTableFromDao()` returns an object `\Jelix\Database\Schema\TableInterface` instead of `jTable`

Plugins for jDb (aka "drivers"), implementing connectors etc, are not supported
anymore.

All error messages are now only in english. No more `jelix~db.*` locales.

## changes in jDao

jDao is now relying on [JelixDao](https://github.com/jelix/JelixDao).
The `jDao` class is still the main class to use to load and use Dao.
Some internal classes are gone.

- `jDaoFactoryBase` is replaced by objects implementing `Jelix\Dao\DaoFactoryInterface`
- `jDaoRecordBase` is replaced by objects implementing `Jelix\Dao\DaoRecordInterface`
- `jDaoGenerator` and `jDaoParser` are removed
- `jDaoMethod` is replaced by `Jelix\Dao\Parser\DaoMethod`
- `jDaoProperty` is replaced by `Jelix\Dao\Parser\DaoProperty`
- `jDaoConditions` and `jDaoCondition` are deprecated and replaced by 
  `\Jelix\Dao\DaoConditions` and `\Jelix\Dao\DaoCondition`.
- `jDaoXmlException` is deprecated. The parser generates `Jelix\Dao\Parser\ParserException` instead.

New classes:

- `jDaoContext`
- `jDaoHooks`


Plugins for jDaoCompiler (type 'daobuilder'), are not supported anymore.

All error messages are now only in english. No more `jelix~daoxml.*` and `jelix~dao.*` locales.

## test environment

- upgrade PHPUnit to 8.5.0


## internal


## deprecated

- `App::initPaths()` and `jApp::initPaths()`: the `$scriptPath` parameter is deprecated and not used anymore
- `\Jelix\Installer\EntryPoint::isCliScript()` (it returns always false from now)
- constant `JELIX_SCRIPTS_PATH`. Its value is now `<vendor path>/lib/Jelix/DevHelper/`.

## removed classes and methods

- `jJsonRpc`
- `JelixTestSuite`, `junittestcase`, `junittestcasedb`
- `jAuth::reloadUser()`
- `jIUrlSignificantHandler`
- `App::appConfigPath()`, `App::configPath()`
- `jHttpResponseException`
- `jResponseHtml::$_CSSIELink` `jResponseHtml::$_JSIELink` `jResponseHtml::getJSIELinks` `jResponseHtml::setJSIELinks` `jResponseHtml::getCSSIELinks` `jResponseHtml::setCSSIELinks`
- `jResponseStreamed`
- `jEvent::clearCache()`, `Jelix\Event\Event::clearCache()`
- `jFormsDaoDatasource::getDependentControls()`
- `jFormsControlCaptcha::$question`, `jFormsControlCaptcha::initExpectedValue()`
- `Jelix\Forms\HtmlWidget\RootWidget::$builder`
- `jFile::getMimeType()`, `jFile::shortestPath()`, `jFile::normalizePath()`
- `jIniFile`, `jIniFileModifier`, `jIniMultiFilesModifier`
- `jFormsBuilderBase`, `jFormsBuilderHtml`, `htmlJformsBuilder`, `htmllightJformsBuilder`
- `jClassBinding`, `jClasses::createBinded()`, `jClasses::getBindedService()`, `jClasses::bind()`, `jClasses::resetBindings()`  

From the command line scripts system of Jelix <=1.6:

- `jApp::scriptsPath()`, `App::scriptsPath()`, `AppInstance::$scriptsPath`, 
- `jControllerCmdLine`, `jCmdLineRequest`, `jResponseCmdline`, `jCmdlineCoordinator`, `jCmdUtils`
- `Jelix\DevHelper\CommandConfig::$layoutScriptsPath`


## Removed modules

- jacl and jacldb. Use jacl2 and jacl2db instead.

## Removed plugins

- kvdb: file2

## other removes:

- `lib/jelix-scripts`. Its DevHelper classes and templates have been moved to `lib/Jelix`
- `lib/jelix-scripts/includes/cmd.inc.php` and `lib/jelix-scripts/includes/scripts.inc.php`
_ `j_jquerypath` variable in templates

- remove support of configuration parameters
  - `enableAllModules`
  - `disableInstallers`
  - `loadClasses` from the `sessions` section