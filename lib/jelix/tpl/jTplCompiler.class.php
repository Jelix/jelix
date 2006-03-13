<?php
/**
* @package     jelix
* @subpackage  jtpl
* @version     $Id$
* @author      Jouanneau Laurent
* @contributor Mathaud Loic (version standalone)
* @copyright   2005-2006 Jouanneau laurent
* @copyright   2006 Mathaud Loic
* @link        http://www.jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

class jTplCompiler
#ifndef JTPL_STANDALONE
    implements jISimpleCompiler {
#else
    {

    private $_locales;
#endif
    private $_literals;

    private  $_vartype = array(T_CHARACTER, T_CONSTANT_ENCAPSED_STRING, T_DNUMBER,
    T_ENCAPSED_AND_WHITESPACE, T_LNUMBER, T_OBJECT_OPERATOR, T_STRING, T_WHITESPACE,T_ARRAY);

    private  $_assignOp = array(T_AND_EQUAL, T_DIV_EQUAL, T_MINUS_EQUAL, T_MOD_EQUAL,
    T_MUL_EQUAL, T_OR_EQUAL, T_PLUS_EQUAL, T_PLUS_EQUAL, T_SL_EQUAL,
    T_SR_EQUAL, T_XOR_EQUAL);

    private  $_op = array(T_BOOLEAN_AND, T_BOOLEAN_OR, T_EMPTY, T_INC, T_ISSET,
    T_IS_EQUAL, T_IS_GREATER_OR_EQUAL, T_IS_IDENTICAL, T_IS_NOT_EQUAL,
    T_IS_NOT_IDENTICAL, T_IS_SMALLER_OR_EQUAL, T_LOGICAL_AND,
    T_LOGICAL_OR, T_LOGICAL_XOR, T_SR, T_SL, T_DOUBLE_ARROW);

    private $_allowedInVar;
    private $_allowedInExpr;
    private $_allowedAssign;

    private $_pluginPath=array();

    private $_modifier = array('upper'=>'strtoupper', 'lower'=>'strtolower',
        'escxml'=>'htmlspecialchars', 'strip_tags'=>'strip_tags', 'escurl'=>'rawurlencode',
        'capitalize'=>'ucwords'
    );

    private $_blockStack=array();

    private $_sourceFile;
    private $_currentTag;

    function __construct(){
        $this->_allowedInVar = array_merge($this->_vartype, $this->_op);
        $this->_allowedInExpr = array_merge($this->_vartype, $this->_op);
        $this->_allowedAssign = array_merge($this->_vartype, $this->_assignOp, $this->_op);
#ifdef JTPL_STANDALONE
        require_once(JTPL_LOCALES_PATH.$GLOBALS['jTplConfig']['lang'].'.php');
        $this->_locales = $GLOBALS['jTplConfig']['locales'];
#endif
    }

#ifdef JTPL_STANDALONE
    public function compile($tplFile){
        $this->_sourceFile = $tplFile;
        $cachefile = JTPL_CACHE_PATH . basename($tplFile);

#else
    public function compile($selector){
        $this->_sourceFile = $selector->getPath();
        $cachefile = $selector->getCompiledFilePath();

        jContext::push($selector->module);
#endif

        if(!file_exists($this->_sourceFile)){
#ifdef JTPL_STANDALONE
            trigger_error(sprintf($this->_locales['errors.tpl.not.found'], $this->_sourceFile), E_USER_ERROR);
#else
            trigger_error(jLocale::get('jelix~errors.tpl.not.found',array($this->_sourceFile)),E_USER_ERROR);
#endif
        }

        $tplcontent = file_get_contents ( $this->_sourceFile);

        preg_match_all("!{literal}(.*?){/literal}!s", $tplcontent, $_match);

        $this->_literals = $_match[1];

        $tplcontent = preg_replace("!{literal}(.*?){/literal}!s", '{literal}', $tplcontent);

        $result = preg_replace_callback("/{((.).*?)}/s", array($this,'_callback'), $tplcontent);

        $header ="<?php \n";
        foreach($this->_pluginPath as $path=>$ok){
           $header.=' require_once(\''.$path."');\n";
        }
#ifdef JTPL_STANDALONE
        $header.='function template_'.md5($tplFile).'($t){'."\n?>";
#else
        $header.='function template_'.md5($selector->module.'_'.$selector->resource).'($t){'."\n?>";
#endif
        $result = $header.$result."<?php \n}\n?>";

        $result = preg_replace('/\?>\n?<\?php/', '', $result);
#ifdef JTPL_STANDALONE
		$_dirname = dirname($cachefile);
		if (!@is_writable($_dirname)) {
			// cache_dir not writable, see if it exists
			if (!@is_dir($_dirname)) {
				trigger_error (sprintf($this->_locales['file.directory.notexists'], $_dirname), E_USER_ERROR);
				return false;
			}
			trigger_error (sprintf($this->_locales['file.directory.notwritable'], $cachefile, $_dirname), E_USER_ERROR);
			return false;
		}
		
		// write to tmp file, then rename it to avoid
		// file locking race condition
		$_tmp_file = tempnam($_dirname, 'wrt');
		
		if (!($fd = @fopen($_tmp_file, 'wb'))) {
			$_tmp_file = $_dirname . '/' . uniqid('wrt');
			if (!($fd = @fopen($_tmp_file, 'wb'))) {
				trigger_error(sprintf($this->_locales['file.write.error'], $cachefile, $_tmp_file), E_USER_ERROR);
				return false;
			}
		}
		
		fwrite($fd, $result);
		fclose($fd);
		
		// Delete the file if it allready exists (this is needed on Win,
		// because it cannot overwrite files with rename()
		if (preg_match("/^(\w+).*$/", PHP_OS, $m)) {
			$os=$m[1];
		} else {
			$os = PHP_OS;
		}
		$isWindows = (strtolower($os) == 'win');
		if ($isWindows && file_exists($cachefile)) {
			@unlink($cachefile);
		}
		@rename($_tmp_file, $cachefile);
		@chmod($cachefile, 0664);
#else
        $file = new jFile();
        $file->write($cachefile, $result);

        jContext::pop();
#endif
        return true;
    }

    public function _callback($matches){
       list(,$tag, $firstcar) = $matches;
       if(!preg_match('/^\$|@|[a-zA-Z\/]$/',$firstcar)){
#ifdef JTPL_STANDALONE
           trigger_error(sprintf($this->_locales['errors.tpl.tag.syntax.invalid'], $tag, $this->_sourceFile),E_USER_ERROR);
#else
            trigger_error(jLocale::get('jelix~errors.tpl.tag.syntax.invalid',array($tag,$this->_sourceFile)),E_USER_ERROR);
#endif
           return '';
       }
       $this->_currentTag = $tag;
       if($firstcar == '$' || $firstcar == '@'){
          return  '<?php echo '.$this->_parseVariable($tag).'; ?>';
       }else{
          if(!preg_match('/^(\/?[a-zA-Z0-9_]+)(?:(?:\s+(.*))|(?:\((.*)\)))?$/',$tag,$m)){
#ifdef JTPL_STANDALONE
             trigger_error(sprintf($this->_locales['errors.tpl.tag.function.invalid'], $tag, $this->_sourceFile),E_USER_ERROR);
#else
             trigger_error(jLocale::get('jelix~errors.tpl.tag.function.invalid',array($tag,$this->_sourceFile)),E_USER_ERROR);
#endif
             return '';
          }
          if(count($m) == 4){
             $m[2] = $m[3];
          }
          if(!isset($m[2])) $m[2]='';

          return '<?php '.$this->_parseFunction($m[1],$m[2]).'?>';
       }
    }

   private function _parseVariable($expr){
      $tok = explode('|',$expr);
      $res = $this->_parseFinal(array_shift($tok),$this->_allowedInVar);

      foreach($tok as $modifier){
         if(!preg_match('/^(\w+)(?:\:(.*))?$/',$modifier,$m)){
#ifdef JTPL_STANDALONE
            trigger_error(sprintf($this->_locales['errors.tpl.tag.modifier.invalid'], $this->_currentTag, $modifier, $this->_sourceFile),E_USER_ERROR);
#else
            trigger_error(jLocale::get('jelix~errors.tpl.tag.modifier.invalid',array($this->_currentTag,$modifier,$this->_sourceFile)),E_USER_ERROR);
#endif
            return '';
         }

         $targs=array($res);

         if( ! $path = $this->_getPlugin('modifier',$m[1])){
            if(isset($this->_modifier[$m[1]])){
               $res = $this->_modifier[$m[1]].'('.$res.')';
            }else{
#ifdef JTPL_STANDALONE
               trigger_error(sprintf($this->_locales['errors.tpl.tag.modifier.unknow'], $this->_currentTag, $m[1], $this->_sourceFile),E_USER_ERROR);
#else
                trigger_error(jLocale::get('jelix~errors.tpl.tag.modifier.unknow',array($this->_currentTag,$m[1],$this->_sourceFile)),E_USER_ERROR);
#endif               
               return '';
            }
         }else{
            if(isset($m[2])){
               $args = explode(':',$m[2]);

               foreach($args as $arg){
                  $targs[] = $this->_parseFinal($arg,$this->_allowedInVar);
               }
            }
            $res = 'jtpl_modifier_'.$m[1].'('.implode(',',$targs).')';
            $this->_pluginPath[$path] = true;
          }
      }
      return $res;
   }

   private function _parseFunction($name,$args){

       switch($name){
         case 'if':
            $res = 'if('.$this->_parseFinal($args,$this->_allowedInExpr).'):';
            array_push($this->_blockStack,'if');
            break;
         case 'else':
            if(end($this->_blockStack) !='if'){
#ifdef JTPL_STANDALONE
              trigger_error(sprintf($this->_locales['errors.tpl.tag.block.end.missing'], end($this->_blockStack), $this->_sourceFile),E_USER_ERROR);
#else
                trigger_error(jLocale::get('jelix~errors.tpl.tag.block.end.missing',array(end($this->_blockStack),$this->_sourceFile)),E_USER_ERROR);
#endif
            }
            $res = 'else:';
            break;
         case 'elseif':
            if(end($this->_blockStack) !='if'){
#ifdef JTPL_STANDALONE
              trigger_error(sprintf($this->_locales['errors.tpl.tag.block.end.missing'], end($this->_blockStack), $this->_sourceFile),E_USER_ERROR);
#else
              trigger_error(jLocale::get('jelix~errors.tpl.tag.block.end.missing',array(end($this->_blockStack),$this->_sourceFile)),E_USER_ERROR);
#endif
            }
            $res = 'elseif('.$this->_parseFinal($args,$this->_allowedInExpr).'):';
            break;
         case 'foreach':
            $res = 'foreach('.$this->_parseFinal($args,array(T_AS, T_DOUBLE_ARROW,T_STRING, T_OBJECT_OPERATOR), array(';','!')).'):';
            array_push($this->_blockStack,'foreach');
            break;
         case 'while':
            $res = 'while('.$this->_parseFinal($args,$this->_allowedInExpr).'):';
            array_push($this->_blockStack,'while');
            break;

         case '/foreach':
         case '/if':
         case '/while':
            $short = substr($name,1);
            if(end($this->_blockStack) !=$short){
#ifdef JTPL_STANDALONE
              trigger_error(sprintf($this->_locales['errors.tpl.tag.block.end.missing'], end($this->_blockStack), $this->_sourceFile),E_USER_ERROR);
#else
              trigger_error(jLocale::get('jelix~errors.tpl.tag.block.end.missing',array(end($this->_blockStack),$this->_sourceFile)),E_USER_ERROR);
#endif
            }
            array_pop($this->_blockStack);
            $res='end'.$short.';';
            break;

         case 'assign':
              $res = $this->_parseFinal($args,$this->_allowedAssign);
              break;
         case 'ldelim': $res ='{'; break;
         case 'rdelim': $res ='}'; break;
         case 'literal':
            if(count($this->_literals)){
               $res = '?>'.array_shift($this->_literals).'<?php ';
            }else{
#ifdef JTPL_STANDALONE
               trigger_error(sprintf($this->_locales['errors.tpl.tag.block.end.missing'], 'literal', $this->_sourceFile),E_USER_ERROR);
#else
               trigger_error(jLocale::get('jelix~errors.tpl.tag.block.end.missing',array('literal',$this->_sourceFile)),E_USER_ERROR);
#endif
            }
            break;
         case '/literal':
#ifdef JTPL_STANDALONE
            trigger_error(sprintf($this->_locales['errors.tpl.tag.block.begin.missing'], 'literal', $this->_sourceFile),E_USER_ERROR);
#else
            trigger_error(jLocale::get('jelix~errors.tpl.tag.block.begin.missing',array('literal',$this->_sourceFile)),E_USER_ERROR);
#endif
            break;
         default:
            if( ! $path = $this->_getPlugin('function',$name)){
#ifdef JTPL_STANDALONE
                trigger_error(sprintf($this->_locales['errors.tpl.tag.function.unknow'], $name, $this->_sourceFile),E_USER_ERROR);
#else
                trigger_error(jLocale::get('jelix~errors.tpl.tag.function.unknow',array($name,$this->_sourceFile)),E_USER_ERROR);
#endif
                $res='';
            }else{
                $argfct=$this->_parseFinal($args,$this->_allowedAssign);
                $res = 'jtpl_function_'.$name.'( $t'.(trim($argfct)!=''?','.$argfct:'').');';
                $this->_pluginPath[$path] = true;
            }
       }

       return $res;
    }

    /*

    -------
    if:        op, autre, var
    foreach:   T_AS, T_DOUBLE_ARROW, T_VARIABLE, @locale@
    for:       autre, fin_instruction
    while:     op, autre, var
    assign:    T_VARIABLE puis assign puis autre, ponctuation, T_STRING
    echo:      T_VARIABLE/@locale@ puis autre + ponctuation
    modificateur: serie de autre s�par� par une virgule

    tous : T_VARIABLE, @locale@

    */

    private function _parseFinal($string, $allowed=array(), $exceptchar=array(';')){

       $tokens = token_get_all('<?php '.$string.'?>');

       $result ='';
       $first = true;
       $inLocale = false;
       $locale='';
       $bracketcount=$sqbracketcount=0;
       $firstok = array_shift($tokens);

       // il y a un bug, parfois le premier token n'est pas T_OPEN_TAG...
       if($firstok== '<' && $tokens[0] == '?' && is_array($tokens[1])
       && $tokens[1][0] == T_STRING && $tokens[1][1] == 'php'){
          array_shift($tokens);
          array_shift($tokens);
       }

       foreach($tokens as $tok){
          if(is_array($tok)){
             list($type,$str)= $tok;
             $first=false;
             if($type== T_CLOSE_TAG){

                continue;
             }

             if($type == T_STRING && $inLocale){
                $locale.=$str;
             }elseif($type == T_VARIABLE && $inLocale){
                $locale.='\'.$t->_vars[\''.substr($str,1).'\'].\'';
             }elseif($type == T_VARIABLE){
                $result.='$t->_vars[\''.substr($str,1).'\']';
             }elseif($type == T_WHITESPACE || in_array($type, $allowed)){
                $result.=$str;
             }else{
#ifdef JTPL_STANDALONE
                trigger_error(sprintf($this->_locales['errors.tpl.tag.phpsyntax.invalid'], $this->_currentTag, $str, $this->_sourceFile),E_USER_ERROR);
#else
                trigger_error(jLocale::get('jelix~errors.tpl.tag.phpsyntax.invalid',array($this->_currentTag,$str,$this->_sourceFile)),E_USER_ERROR);
#endif
                return '';
             }

          }else{
             if($tok == '@'){
                if($inLocale){
                   $inLocale = false;
                   if($locale==''){
#ifdef JTPL_STANDALONE
                      trigger_error(sprintf($this->_locales['errors.tpl.tag.locale.invalid'], $this->_currentTag, $this->_sourceFile),E_USER_ERROR);
#else
                      trigger_error(jLocale::get('jelix~errors.tpl.tag.locale.invalid',array($this->_currentTag,$this->_sourceFile)),E_USER_ERROR);
#endif
                      return '';
                   }else{
#ifdef JTPL_STANDALONE
                      $result.='${$GLOBALS[\'jTplConfig\'][\'localesGetter\']}(\''.$locale.'\')';
#else
                      $result.='jLocale::get(\''.$locale.'\')';
#endif
                      $locale='';
                   }
                }else{
                   $inLocale=true;
                }
            }elseif($inLocale && ($tok=='.' || $tok =='~') ){
               $locale.=$tok;
            }elseif($inLocale || in_array($tok,$exceptchar) || ($first && $tok !='!')){
#ifdef JTPL_STANDALONE
               trigger_error(sprintf($this->_locales['errors.tpl.tag.character.invalid'], $this->_currentTag, $tok, $this->_sourceFile),E_USER_ERROR);
#else
               trigger_error(jLocale::get('jelix~errors.tpl.tag.character.invalid',array($this->_currentTag,$tok,$this->_sourceFile)),E_USER_ERROR);
#endif
               return '';
            }elseif($tok =='('){
               $bracketcount++;$result.=$tok;
            }elseif($tok ==')'){
               $bracketcount--;$result.=$tok;
            }elseif($tok =='['){
               $sqbracketcount++;$result.=$tok;
            }elseif($tok ==']'){
               $sqbracketcount--;$result.=$tok;
            }else{
               $result.=$tok;
            }
            $first=false;
         }

      }

      if($bracketcount != 0 || $sqbracketcount !=0){
#ifdef JTPL_STANDALONE
         trigger_error(sprintf($this->_locales['errors.tpl.tag.bracket.error'], $this->_currentTag, $this->_sourceFile),E_USER_ERROR);
#else
         trigger_error(jLocale::get('jelix~errors.tpl.tag.bracket.error',array($this->_currentTag,$this->_sourceFile)),E_USER_ERROR);
#endif
      }

      return $result;
    }


    private function _getPlugin($type, $name){
#ifdef JTPL_STANDALONE
        $treq = 'html';
#else
        global $gJCoord, $gJConfig;
        $treq = $gJCoord->response->getType();
#endif
        $foundPath='';

#ifdef JTPL_STANDALONE
        if(isset($GLOBALS['jTplConfig']['tplpluginsPathList'][$treq])){
            foreach($GLOBALS['jTplConfig']['tplpluginsPathList'][$treq] as $path){
#else
        if(isset($gJConfig->tplpluginsPathList[$treq])){
            foreach($gJConfig->tplpluginsPathList[$treq] as $path){
#endif
                $foundPath=$path.$type.'.'.$name.'.php';

                if(file_exists($foundPath)){
                    return $foundPath;
                }
            }
        }
#ifdef JTPL_STANDALONE
        if(isset($GLOBALS['jTplConfig']['tplpluginsPathList']['common'])){
            foreach($GLOBALS['jTplConfig']['tplpluginsPathList']['common'] as $path){
#else
        if(isset($gJConfig->tplpluginsPathList['common'])){
            foreach($gJConfig->tplpluginsPathList['common'] as $path){
#endif
                $foundPath=$path.$type.'.'.$name.'.php';
                if(file_exists($foundPath)){
                    return $foundPath;
                }
            }
        }
        return '';
    }
}

/*
function showtokens($arr){

return;
echo '<table border="1" style="font-size:0.7em">';
foreach($arr as $tok){

   if(is_array($tok)){
      echo '<tr><td>',token_name($tok[0]), '</td><td>',htmlspecialchars($tok[1]),"</td></tr>\n";
   }else
      echo '<tr><td colspan="2">',$tok, "</td></tr>\n";

}
echo '</table><hr/>';

}

function showtoken($tok){
return;

echo '<table border="1" style="font-size:0.7em">';
   if(is_array($tok)){
      echo '<tr><td>',token_name($tok[0]), '</td><td>',htmlspecialchars($tok[1]),"</td></tr>\n";
   }else
      echo '<tr><td colspan="2">',$tok, "</td></tr>\n";
echo '</table><hr/>';

}
*/


?>
