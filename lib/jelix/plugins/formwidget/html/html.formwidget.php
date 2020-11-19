<?php
/**
 * @package     jelix
 * @subpackage  forms_widget_plugin
 *
 * @author      Laurent Jouanneau
 * @contributor Julien Issler, Dominique Papin
 *
 * @copyright   2006-2017 Laurent Jouanneau, 2008-2011 Julien Issler, 2008 Dominique Papin
 *
 * @see        http://www.jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
class htmlFormWidget extends \jelix\forms\HtmlWidget\RootWidget
{
    public function outputHeader($builder)
    {
        $conf = jApp::config()->urlengine;
        $collection = jApp::config()->webassets['useCollection'];
        $jquery = jApp::config()->{'webassets_'.$collection}['jquery.js'];

        // no scope into an anonymous js function, because jFormsJQ.tForm is used by other generated source code
        $js = "jFormsJQ.selectFillUrl='".jUrl::get('jelix~jforms:getListData')."';\n";
        $js .= 'jFormsJQ.config = {locale:'.$builder->escJsStr(jApp::config()->locale).
                ',basePath:'.$builder->escJsStr(jApp::urlBasePath()).
                ',jqueryPath:'.$builder->escJsStr($conf['jqueryPath']).
                ',jqueryFile:'.$builder->escJsStr($jquery).
                ',jelixWWWPath:'.$builder->escJsStr($conf['jelixWWWPath'])."};\n";
        $js .= "jFormsJQ.tForm = new jFormsJQForm('".$builder->getName()."','".
            $builder->getForm()->getSelector()."','".
            $builder->getForm()->getContainer()->formId."');\n";
        $js .= 'jFormsJQ.tForm.setErrorDecorator(new '.$builder->getOption('errorDecorator')."());\n";
        $js .= "jFormsJQ.declareForm(jFormsJQ.tForm);\n";
        $this->addJs($js);
        $this->builder = $builder;
    }

    public function outputFooter($builder)
    {
        $js = "jQuery(document).ready(function() { var c, c2;\n".$this->js.$this->finalJs.'});';
        $container = $builder->getForm()->getContainer();
        $container->privateData['__jforms_js'] = $js;
        $formId = $container->formId;
        $formName = $builder->getForm()->getSelector();
        echo '<script type="text/javascript" src="'.\jUrl::get(
            'jelix~jforms:js',
            array('__form' => $formName, '__fid' => $formId)
        ).'"></script>';
    }
}
