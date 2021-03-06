<?php
/**
  * FORM BUILDER CLASS
  *
  * Crea l'html del form utilizzando i parametri passati dalla funzione
  * function.form.php.
  *
  * @author Marco Pozzato  <marco@kleis.it>
  * @param int $id ID del form (record sul db)
  * @param array $attributes Attributi del form
  * @return str $form
  * @version 1.2
  */

class formBuilder {

  protected $action;
  protected $approvazione;
  protected $captcha = 'nessuno';
  protected $captchaConfig = array();
  protected $captchaLabel = 'Codice di sicurezza';
  protected $cblabelclass = 'cblabel';
  protected $checkfields;
  protected $class;
  protected $input_class = 'text';
  protected $button_class = 'btn';
  protected $submit_button_class = 'submit';
  protected $reset_button_class = 'reset';
  protected $implicit_label_class = 'inline';
  protected $container_element = 'div';
  protected $debug = false;
  protected $error;
  protected $fields = array();
  protected $fieldset = array();
  protected $hiddenfields = array();
  protected $haschecks = false;
  protected $hook = array();
  protected $hide_label = false;
  protected $id;
  protected $informativa;
  protected $lastFs;
  protected $maxFileSize = 3000000;
  protected $method;
  protected $multipart = 0;
  protected $privacy = false;
  protected $privacyLabel = 'Privacy';
  protected $required_mark = '*';
  protected $resetLabel = 'Reset';
  protected $submitLabel = 'Submit';
  protected $tab = 1;
  protected $validateRules = array();
  protected $error1;
  protected $error2;
  protected $error3;
  protected $error4;
  protected $buttons = true;
  protected $additional_buttons = array();
  protected $inline_buttons = false;
  protected $reset_button = true;
  protected $submit_button = true;
  protected $xhtml = true;
  protected $include_script = true;
  protected $ajax_submit = false;


  function __construct($id) {
    $this->setAttributes(array(
      'id'=>$id,
      'method'=>'post',
      'captchaConfig'=>array(
        'width'=>300,
        'height'=>80,
        'chars'=>5,
        'dict'=>1,
        'lines'=>1,
        'noise'=>1
        )
    ));
  }


	function setAttributes($params) {
		if(!empty($params) && is_array($params)) {
			$objArr = array();
			$keyLookupArr = array();

			foreach($this as $key=>$value) {
				$objArr[$key] = $value;
				$keyLookupArr[strtolower($key)] = $key;
			}

			foreach($params as $key=>$value) {
				$key = strtolower($key);
				if(array_key_exists($key, $keyLookupArr)) {
					$key = $keyLookupArr[$key];
					if(is_array($this->$key) && !empty($this->$key)) {
						if(is_array($value))
							$this->$key = array_merge($this->$key, $value);
					} else $this->$key = $value;

				} elseif(array_key_exists("attributes", $objArr)){
					$this->attributes[$key] = $value;
        }
			}
		}
	}

  function addFieldset($id, $legend) {
    $this->fieldset[$id] = array(
      'legend' => $legend,
      'fields' => array()
      );
    $this->lastFs = $id;
  }


  function addField($name, $label, $value='', $tipo='text', $obbligatorio=0, $formato='', $options=array(), $fieldset='', $hint='', $disabled=false) {
    $identifier = 'f-'.$this->sanitizeName($name);


    if ($this->hide_label) {
      $label_tag = null;
    } else {
      $label_tag  = "  <label for=\"{$identifier}\">".trim($label).($obbligatorio==1 ? ' '.$this->required_mark : '')."</label>";
      //$label_tag = "  <!--[if lt IE 9 ]>{$label_tag}<]-->";
    }

    switch($tipo){
      case 'html':
        $input = $value;
        $label_tag = '';
        $class = ' class="full"';
        $hint = '';
        break;
      case 'html_short':
        $input = $value;
        $label_tag = '';
        $class = '';
        $hint = '';
        break;
      case 'button':
        $label_tag = $hint = '';
        $input = $this->button($name, $label, $value, $formato);
        $class = ' class="full"';
        break;
      case 'textarea':
        $input = $this->textArea($name, $identifier, $value, $obbligatorio, $label);
        $class = ' class="full"';
        break;
      case 'password':
        $input = $this->inputPassword($name, $identifier, $value, $obbligatorio, $hint, $label);
        break;
      case 'file':
        $input = $this->fileInput($name, $identifier, $obbligatorio, $hint);
        break;
      case 'checkbox':
        $label_tag = '';
        $input = $this->checkboxInput($name, $identifier, $value, $obbligatorio, $options, $hint, $label);
        break;
      case 'radio':
        $input = $this->radioInput($name, $identifier, $value, $obbligatorio, $options, $hint);
        break;
      case 'select':
        $input = $this->comboBox($name, $identifier, $value, $obbligatorio, $options, $hint, $disabled);
        break;
      case 'hidden':
        $this->addHiddenField($name, $value);
        $label_tag = '';
        $input = $hint = '';
        break;
      case 'text':
      default:
        $input = $this->textInput($name, $identifier, $value, $obbligatorio, $formato, $hint, $label);
        break;
    }

    if($input!='') {
      //$f  = "<div{$class}>\n";
      $f  = "<{$this->container_element}>\n";
      $f .= $label_tag.PHP_EOL;
      $f .= $input.PHP_EOL;
      $f .= ($hint) ? "<span class=\"hint\">{$hint}</span>" : '';
      $f .= "</{$this->container_element}>\n";

      if($fieldset){
        $this->fieldset[$fieldset]['fields'][] = $f;
      } else {
        $this->fields[] = $f;
      }
    }
    $this->hook[$name] = $label;
  }


  function render(){
    $enctype = '';
    if($this->multipart==true){
      $enctype = ' enctype="multipart/form-data"';
      $this->addHiddenField('MAX_FILE_SIZE', $this->maxFileSize);
    }
    $this->addHiddenField('action', 'submit');
    $this->addHiddenField('formId', $this->id);

    $class = ($this->class!='') ? " class=\"{$this->class}\"" : false;
    $tot   = count($this->fieldset);

    $form  = "<form{$enctype} id=\"form{$this->id}\"{$class} method=\"{$this->method}\" action=\"{$this->action}\">\n";
    $form .= $this->error;
    if($tot){
      // ci sono fieldset, aggiungo i campi speciali all'ultimo
      if($this->captcha!='nessuno'){
        $this->fieldset[$this->lastFs]['fields'][] = $this->insertCaptcha($this->captcha);
      }

      if($this->privacy==true){
        $this->fieldset[$this->lastFs]['fields'][] = $this->insertDisclaimer();
      }

      if($this->inline_buttons){
        $this->fieldset[$this->lastFs]['fields'][] = $this->insertInlineButtons($this->buttons);
      } else {
        $this->fieldset[$this->lastFs]['fields'][] = $this->insertButtons($this->buttons);
      }


      foreach($this->fieldset as $k => $s){
        $form .= "  <fieldset id=\"fs{$k}\">\n";
        if($s['legend']!='')
          $form .= "    <legend><span>{$s['legend']}</span></legend>\n";
        $form .= implode(PHP_EOL, $s['fields']);
        $form .= "  </fieldset>\n";
      }

    } else {
      // non ci sono fieldset, inserisco i campi nel form
      $form .= implode(PHP_EOL, $this->fields);

      if($this->captcha!='nessuno'){
        $form .= $this->insertCaptcha($this->captcha);
      }

      if($this->privacy==true){
        $form .= $this->insertDisclaimer();
      }

      if($this->inline_buttons){
        $form .= $this->insertInlineButtons($this->buttons);
      } else {
        $form .= $this->insertButtons($this->buttons);
      }
    }
    $form .= "</form>\n";

    if ($this->include_script)
      $form .= $this->validateScript();

    return $form;
  }


  function errorMsg($error){
    $this->hook['captcha'] = $this->captchaLabel;
    $this->hook['privacy'] = 'privacy';

    $ret  = "<div class=\"alert alert-error\">\n";
    if (is_array($error)){
      $ret .= '<strong>'.$this->checkfields.'</strong>';
      $ret .= "<ul>\n";
      foreach($error as $k=>$v){
        $ret .= "<li>{$this->hook[$k]}</li>\n";
      }
      $ret .= "</ul>\n";
    } else {
      $ret .= $error;
    }
    $ret .= "</div>\n";
    $this->error = $ret;
  }


  function addHiddenField($name, $value) {
    $this->hiddenfields[] = "  <input type=\"hidden\" name=\"{$name}\" value=\"{$value}\"{$this->tagClosure()}>";
  }


  function textInput($name, $identifier, $value='', $obbligatorio=0, $formato='', $hint='', $label) {
    $class  = $this->input_class;
    $class .= ($obbligatorio==1) ? ' required' : '';
    $class .= ($formato!='text') ? ' '.$formato : '';
    $index = $this->tabIndex();
    $ph    = ($this->hide_label) ? " placeholder=\"{$label}\"" : '';

    $ret = "  <input type=\"text\" name=\"{$name}\" id=\"{$identifier}\" class=\"{$class}\" value=\"{$value}\" tabindex=\"{$index}\"{$ph}{$this->tagClosure()}>";
    return $ret;
  }


  function inputPassword($name, $identifier, $value='', $obbligatorio=0, $hint='', $label) {
    $class  = $this->input_class;
    $class .= ($obbligatorio==1) ? ' required' : '';
    $index = $this->tabIndex();
    $ph    = ($this->hide_label) ? " placeholder=\"{$label}\"" : '';

    $ret = "  <input type=\"password\" name=\"{$name}\" id=\"{$identifier}\" class=\"{$class}\" value=\"{$value}\" autocomplete=\"off\" tabindex=\"{$index}\"{$ph}{$this->tagClosure()}>";
    return $ret;
  }


  function textArea($name, $identifier, $value='', $obbligatorio=0, $label) {
    $class = ($obbligatorio==1) ? ' required' : '';
    $index = $this->tabIndex();
    $ph    = ($this->hide_label) ? " placeholder=\"{$label}\"" : '';

    $ret = "  <textarea name=\"{$name}\" id=\"{$identifier}\" class=\"{$class}\" rows=\"4\" cols=\"60\"{$ph} tabindex=\"{$index}\"></textarea>";
    return $ret;
  }


  function fileInput($name, $identifier, $obbligatorio=0) {
    $this->multipart = true;
    $class  = $this->input_class;
    $class .= ($obbligatorio==1) ? ' required' : '';
    $index = $this->tabIndex();

    $ret = "  <input type=\"file\" name=\"{$name}\" id=\"{$identifier}\" class=\"{$class}\" tabindex=\"{$index}\"{$this->tagClosure()}>";
    return $ret;
  }


  function checkboxInput($name, $identifier, $value='', $obbligatorio=0, $options=array(), $hint, $label) {
    $this->haschecks = true;
    $class  = 'checkbox';
    $class .= ($obbligatorio==1) ? ' required' : '';
    $ret    = '';

    if(count($options)>0){
      $i = '';
      foreach($options as $k=>$v){
        $index = $this->tabIndex();
        $checked = ($v['selezionato']==1) ? ' checked="checked"' : '';
        $ret .= <<<EOPTS
        <label class="{$this->implicit_label_class}">
          <input type="checkbox" name="{$name}[]" id="{$identifier}{$i}" class="{$class}" value="{$k}"{$checked}
            tabindex="{$index}"{$this->tagClosure()}> {$v['label']}</label>
EOPTS;
        $i ++;
      }
    } else {
      $index = $this->tabIndex();
      $ret = "  <label class=\"{$this->implicit_label_class}\"><input type=\"checkbox\" name=\"{$name}\" id=\"{$identifier}\" class=\"{$class}\" value=\"{$value}\" tabindex=\"{$index}\"{$this->tagClosure()}> {$label}</label>";
    }
    return $ret;
  }


  function radioInput($name, $identifier, $value='', $obbligatorio=0, $options=array()) {
    $this->haschecks = true;
    $class  = 'radio';
    $class .= ($obbligatorio==1) ? ' required' : '';
    $ret    = '';

    if(count($options)>0){
      $i = '';
      $sel = false;
      foreach($options as $k=>$v){
        $index = $this->tabIndex();
        if(!$sel && $v['selezionato']==1){
          $checked = ' checked="checked"';
          $sel = true;
        } else $checked = '';
/*
        $ret .= <<<EOPTS
        <input type="radio" name="{$name}[]" id="{$identifier}{$i}" class="{$class}" value="{$k}"{$checked}
          tabindex="{$index}"{$this->tagClosure()}><span class="{$this->cblabelclass}">{$v['label']}</span>
EOPTS;
*/
        $ret .= <<<EOPTS
        <label class="{$this->implicit_label_class}">
          <input type="radio" name="{$name}[]" id="{$identifier}{$i}" class="{$class}" value="{$k}"{$checked}
            tabindex="{$index}"{$this->tagClosure()}> {$v['label']}</label>
EOPTS;
        //<br{$this->tagClosure()}>
        $i ++;
      }
    }
    return $ret;
  }


  function comboBox($name, $identifier, $value='', $obbligatorio=0, $options=array(), $hint, $disabled) {
    if(count($options)==0) return;

    $class = ($obbligatorio==1) ? ' class="required"' : '';
    $index = $this->tabIndex();
    $sel = false;
    $ret = '';
    $readonly = '';

    if($disabled){
      $readonly = ' disabled="disabled"';
      $ret .= "<input type=\"hidden\" name=\"{$name}\" value=\"{$value}\"/>";
    }
    $ret .= "  <select name=\"{$name}\" id=\"{$identifier}\"{$class} tabindex=\"{$index}\"{$readonly}>\n";
    foreach($options as $k=>$v){
      $index = $this->tabIndex();
      if(! $sel
        && (( $value=='' && $v['selezionato']==1)
           || $k==$value)
        ){
        $checked = ' selected="selected"';
        $sel = true;
      } else $checked = '';

      $ret .= "    <option value=\"{$k}\"{$checked}>{$v['label']}</option>\n";
    }
    $ret .= "  </select>\n";
    return $ret;
  }


  function button($name='', $label, $value='', $formato='submit') {
    $index = $this->tabIndex();
    $class = $this->button_class.' '.$formato;
    $ret = "  <button type=\"{$formato}\" name=\"{$name}\" id=\"b{$name}\" class=\"{$class}\" value=\"{$value}\" tabindex=\"{$index}\"><span>{$label}</span></button>";
    return $ret;
  }


  function insertCaptcha($type){
    $ret = '';
    switch($type){
      case 'nessuno':
        break;
      case 'synCaptcha':
        $ret = $this->setSynCaptcha();
        break;
      case 'reCaptcha':
        $ret = $this->setReCaptcha();
        break;
      case 'honeypot':
        $ret = $this->setHoneypot();
        break;
      default:
        $ret = $this->setSecurityImage();
        break;
    }
    return $ret;
  }


  private function setSecurityImage(){
    extract($this->captchaConfig);
    $src = "/public/CaptchaSecurityImages.php?width={$width}&height={$height}&character={$chars}";
    $ret = <<<EOSC
      <div class="full">
        <img src="{$src}" width="{$width}" height="{$height}" class="captcha" alt=""{$this->tagClosure()}>
        <label for="fcaptcha">{$this->captchaLabel}</label>
        <input type="text" name="captcha" id="fcaptcha" class="text required" tabindex="{$this->tabIndex()}"{$this->tagClosure()}>
      </div>
EOSC;
    $this->validateRules[] = 'captcha:{required:true, remote:"/public/server/validate_captcha.php"}';
    return $ret;
  }


  private function setSynCaptcha(){
    extract($this->captchaConfig);
    $src = "/public/lib/syncaptcha/synCaptcha.php?width={$width}&amp;height={$height}&amp;characters={$chars}&amp;use_dict={$dict}&amp;lines={$lines}&amp;noise={$noise}";
    $ret = <<<EOSC
      <div class="full">
        <img src="{$src}" width="{$width}" height="{$height}" class="captcha" alt=""{$this->tagClosure()}>
        <label for="fcaptcha">{$this->captchaLabel}</label>
        <input type="text" name="captcha" id="fcaptcha" class="text required" tabindex="{$this->tabIndex()}"{$this->tagClosure()}>
      </div>
EOSC;
    $this->validateRules[] = 'captcha:{required:true, remote:"/public/server/validate_captcha.php"}';
    return $ret;
  }


  private function setReCaptcha(){
    require_once('./recaptchalib.php');
    $publickey = '6LeldL8SAAAAALRJlfPAx3T1ZlGK8CwpyJtfUuP1';
    $ret = recaptcha_get_html($publickey);
    $this->validateRules[] = 'recaptcha_response_field:{ required:true, remote:{url:"/public/server/validate_recaptcha.php", type:"post", data:{recaptcha_challenge_field: function(){ return $("#recaptcha_challenge_field").val();} }}}';
    return $ret;
  }


  function setHoneypot(){
    $ret = <<<EOSC
    <div class="hp"><input type="text" value="" class="text" name="twitter_account" id="twitter_account"/></div>
EOSC;
    return $ret;
  }

  function insertDisclaimer(){
    $ret = <<<EOPD
      <div class="full">
        <p class="disclaimer">{$this->informativa}</p>
        <label class="{$this->implicit_label_class}">
          <input type="checkbox" name="privacy" id="fprivacy" class="checkbox required" value="1" tabindex="{$this->tabIndex()}"{$this->tagClosure()}>
          {$this->approvazione}
        </label>
      </div>
EOPD;
    return $ret;
  }


  function insertButtons($buttons){
    $ret  = "<div class=\"button-wrap\">\n";
    $ret .= implode(PHP_EOL, $this->hiddenfields).PHP_EOL;
    if($buttons==true){
      if($this->submit_button==true){
        $class = $this->button_class.' '.$this->submit_button_class;
        $ret .= "  <button type=\"submit\" class=\"{$class}\" tabindex=\"{$this->tabIndex()}\">{$this->submitLabel}</button>\n";
      }
      if($this->reset_button==true){
        $class = $this->button_class.' '.$this->reset_button_class;
        $ret .= "  <button type=\"reset\" class=\"{$class}\">{$this->resetLabel}</button>\n";
      }

      if(count($this->additional_buttons)>0){
        foreach($this->additional_buttons as $b){
          $ret .= $b;
        }
      }
    }
    $ret .= "</div>\n";
    return $ret;
  }


  function insertInlineButtons($buttons){
    $ret  = "<div><br{$this->tagClosure()}>\n";
    $ret .= implode(PHP_EOL, $this->hiddenfields).PHP_EOL;
    if($buttons==true){
      if($this->submit_button==true){
        $class = $this->button_class.' '.$this->submit_button_class;
        $ret .= "  <button type=\"submit\" class=\"{$class}\" tabindex=\"{$this->tabIndex()}\">{$this->submitLabel}</button>\n";
      }
      if($this->reset_button==true){
        $class = $this->button_class.' '.$this->reset_button_class;
        $ret .= "  <button type=\"reset\" class=\"{$class}\">{$this->resetLabel}</button>\n";
      }
    }
    $ret .= "</div>\n";
    return $ret;
  }


  function addButtons($button){
    $this->additionalButtons[] = $button;
  }


  function validateScript(){
    $params = array();
    // campi particolari
    if($this->haschecks){
      $params[] = "    highlight: function(element){ $(element).addClass('error').siblings('span.{$this->cblabelclass}').addClass('error-field');}";
      $params[] = "    unhighlight: function(element, errorClass, validClass){ $(element).removeClass('error').siblings('span.{$this->cblabelclass}').removeClass('error-field'); }";
    }

    // regole particolari
    if(is_array($this->validateRules) && count($this->validateRules)>0){
      $params[] = "    rules:{".implode(",\n", $this->validateRules)."}";
    }

    if ($this->ajax_submit) {
      $params[] = <<<EOSB
      submitHandler: function(form) {
        var _form = $(form), data = _form.serialize(), action = _form.attr('action');
        $.post(action, data)
          .done(function(res){
            console.log('message: '+res.message);
            if (res.status == 'ok'){
              $('#form{$this->id}-wrapper').replaceWith(res.message);
            } else {
              $('#form{$this->id}').prepend(res.message);
            }
          })
          .fail(function() {
            alert( "error" );
          })
        return false;
      }
EOSB;
    }


    $js  = "<script type=\"text/javascript\">\n".($this->xhtml==true ? "//<![CDATA[\n" : '');
    $js .= "  $(document).ready(function(){\n";
    $js .= "    $.validator.messages.required=\"".$this->error1."\";\n";
    $js .= "    $.validator.messages.email=\"".$this->error2."\";\n";
    $js .= "    $.validator.messages.remote=\"".$this->error3."\";\n";
    $js .= "    $.validator.messages.equalTo=\"".$this->error4."\";\n";
    $js .= "    $(\"#form{$this->id}\").validate({\n  ".implode(",\n  ", $params)."\n    });\n";
    $js .= "  });\n";
    $js .= ($this->xhtml==true ? "//]]>\n" : '')."</script>\n";

    return $js;
  }

  function sanitizeName($str){
    $str = preg_replace('/[^a-z0-9]+/i', '_', str_replace(']', '', $str));
    return $str;
  }


  protected function tabIndex(){
    // todo: convertire id testuale in numero
    return $this->id.($this->tab ++);
  }


  protected function tagClosure(){
    if($this->xhtml==true) return ' /';
  }

}

//end class formBuilder
