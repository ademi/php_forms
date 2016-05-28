<?php
require_once('session.php');			//session scripts
require_once('recaptcha.php');			//captcha scripts


class formKey{
    private $current_key='';
    private $old_key='';
    private $name='';

    public function __construct(){
        session_class::start(); 		//if not started start sessions
    }
	
	/************************************
		Function: CreateKey
		input	: Form Name
			generates a random key for the given form 
			and stores it in a session to prevent cross
			site attacks
	*************************************/
    public function createKey($name){
        $this->name=$name;
		
        //in case the form has an already saved key then save it
        if(session_class::exists($this->name))
			$this->old_key =  session_class::get($this->name);
		
		// generate the key and save it to a session
        $this->current_key=md5($_SERVER['REMOTE_ADDR'].mt_rand());	
        $_SESSION[$this->name] = $this->current_key;
    }
	
	//provide get methods
    public function getFormKey()        {return $this->current_key;}
    public function getFormKeyName()    {return $this->name;}
	
	// validate the key on submission (to make sure its our site's form that's submitting data)
    public function validate(){
        if(common::user_input($this->name)==$this->old_key){
            return TRUE;
        }
        else return FALSE;
    }
}
/*************
 Abstract class to make sure every decendent class has two main methods 
 to generate and to get the html text;
 *************/
 
abstract class genericForm{
    abstract public function generate_html();
    abstract public function get_html(); 
}

/********************************
Class Form:
	serves as main container which composes all the html fields and generates
	the final html text.

*********************************/
class form extends genericForm{
	
    private $html_output='';
    private $elements   = array();		//where all form's element will be contained	
    private $secret     = NULL;			// weteher the forms requires a secret key
    
	// Captcha constants
    private $captcha    =   '';
    private $recaptcha_secret ='****************************';
    private $response =NULL;
	
    private $details    =array();		// contains the html attributes that goes with the form tag
	
	// the mask with which the final output is built
    private $template   ='_js <form _action _method _name _id _encryption _extra><div _class> _elements </div></form>';
    private $tags       =array('_name','_id','_class','_action','_method','_encryption','_extra','_js');
	
	// 
    private $replacements   =array();
    public function __construct($details,$secret=TRUE){
        foreach($details as $key=>$val){
            if($val =='')           $this->replacements[]='';
            else if($key == 'extra') $this->replacements[]=' '.$val.' ';
            else if($key == 'js')$this->replacements[]='<script>'.$val.'</script>';
            else                    $this->replacements[]=$key.'="'.$val.'" ';
        }
        if($secret == TRUE){
            $this->details=$details;
            $this->secret = TRUE;
            $this->secret = new formKey($details['name'].'formKey');
        }
    }
    public function get_html(){return $this->html_output;}
    public function verify($captcha=TRUE){
        if($this->secret->validate()){
            if($captcha == TRUE && ENABLE_CAPTCHA === TRUE){
                $response =$this->verify_captcha();
                if($response->success == true) return TRUE;
                else return FALSE;
            }
            return true;
        }
        else{
            common::error_page ('INTERNAL ERROR OCCURED');
            exit();
            return false;
        }
    }
    public function verify_captcha(){
        $reCaptcha = new ReCaptcha($this->recaptcha_secret);
        return $reCaptcha->verifyResponse($_SERVER["REMOTE_ADDR"],$_POST["g-recaptcha-response"]);
    }

    public function add_elements($elements){
        foreach($elements as $element)$this->elements[]=$element;
    }
    public function generate_html($elements=NULL){
        $hidden='';
        if($this->secret!=NULL){
            $this->secret->createKey($this->details['name'].'form_key');
            $hidden = "<input type='hidden' name='".$this->secret->getFormKeyName()."' id='form_key' value='".$this->secret->getFormKey()."' />";
            if(ENABLE_CAPTCHA === TRUE)$this->captcha = '<div class="g-recaptcha" data-sitekey="6LcybBwTAAAAAP5vRHZAWF_JNfj64VNi3EPjtvMc" data-theme="dark"></div>';
        }
        if($elements !=NULL)$this->add_elements ($elements);
        $e ='';
         foreach($this->elements as $element){
            $element->generate_html();
            $e .= $element->get_html();
        }
        $this->html_output= str_replace($this->tags, $this->replacements, $this->template);
        $this->html_output= str_replace('_elements', $e, $this->html_output);
        $this->html_output= str_replace('_before_submiting', $hidden.$this->captcha, $this->html_output);
        if($this->secret !=NULL && ENABLE_CAPTCHA === TRUE)$this->html_output .='<script src="https://www.google.com/recaptcha/api.js"></script>';
        return $this->html_output;
    }
}
class hidden     extends genericForm{
    private $template   ='<input type="hidden" _name _id _value />_extra _js';
    private $tags =         array('_name','_id','_value','_extra','_js');//array('name'=>'','id'=>"",'value'=>"",'extra'=>"",'js'=>'')
    private $replacements=  array();
    public function __construct($details) {
        foreach($details as $key=>$val){
            if($val =='')           $this->replacements[]='';
            else if($key == 'extra') $this->replacements[]=' '.$val.' ';
            else if($key == 'js')       $this->replacements[]='<script>'.$val.'</script>';
            else                    $this->replacements[]=$key.'="'.$val.'" ';
        }
    }
    
    public function get_html(){return $this->html_output;}
    public function generate_html() {
        $this->html_output= str_replace($this->tags, $this->replacements, $this->template);
    }
}
class text_field extends genericForm{
    private $template   ='<div _class>_js<label> _label <input _id _name _type _default _extra></label></div>';
    private $tags =         array('_name','_class','_id','_label','_type','_default','_extra','_js');//array('name'=>'','class'=>'','id'=>'','label'=>'','type'=>'','default'=>'','extra'=>'','js'=>'');
    private $replacements=  array();
    public function __construct($details) {
        //if(count($details)!=count($this->tags))report_error('unbalanced elements passed to form object','at '.__FILE__.' LINE: '.__LINE__);
        foreach($details as $key=>$val){
            if($val =='')           $this->replacements[]='';
            else if($key == 'extra'|| $key =='label') $this->replacements[]=' '.$val.' ';
            else if($key =='js')    $this->replacements[]='<script> '.$val.'</script>';
            else                    $this->replacements[]=$key.'="'.$val.'" ';
        }
    }
    
    public function get_html(){return $this->html_output;}
    public function generate_html() {
        $this->html_output= str_replace($this->tags, $this->replacements, $this->template);
    }
}

class textarea extends genericForm{
    private $template   = '<div _class><label>_label<textarea _class _id _name _extra>_default</textarea></label></div>';
    private $tags       =array('_name','_label','_id','_class','_default','_extra');//array('name'=>,'label'=>,'id'=>,'class'=>,'default'=>'',extra'=>)
    private $replacements   =array();
    
    public function __construct($details) {
        //if(count($details)!=count($this->tags))report_error('unbalanced elements passed to form object','at '.__FILE__.' LINE: '.__LINE__);
        foreach($details as $key=>$val){
            if($val =='')           $this->replacements[]='';
            else if($key == 'extra'||$key =='label'||$key=='default') $this->replacements[]=' '.$val.' ';
            else if($key =='js')    $this->replacements[]='<script> '.$val.'</script>';
            else                    $this->replacements[]=$key.'="'.$val.'" ';
        }
    }
    public function get_html(){return $this->html_output;}
    public function generate_html() {
        $this->html_output= str_replace($this->tags, $this->replacements, $this->template);
    }        
}

class radio extends genericForm{
    private $container_template     ='<div class="_class">_js _inputs</div>';
    private $inputs_template        ='<label ><input _value _name type="radio" _valu" _checked _extra>_label</label>';
    private $tags                   =array('_name','_class','_extra','_js');
    private $input_tags             =array('_value','_label','_checked');
    private $pairs                  =array();
    private $replacements           =array();
    public function __construct($details,$pairs) {
//        if(count($details)!=count($this->tags))report_error('unbalanced elements passed to form object','at '.__FILE__.' LINE: '.__LINE__);
        foreach($details as $key=>$val){
            if($val =='')           $this->replacements[]='';
            else if($key == 'extra'||$key == '_label') $this->replacements[]=' '.$val.' ';
            else if($key == 'js')       $this->replacements[]='<script>'.$val.'</script>';
            else                    $this->replacements[]=$key.'="'.$val.'" ';
        }
        
         foreach($pairs as $pair){
             $temp =array();
             foreach($pair as $key=>$val){
                if($val =='')           $temp[]='';
                else if($key ='label')  $temp[]=$val;
                else                    $temp[]=$key.'="'.$val.'" ';
                 
            }
            $this->pairs[] = $temp;
         }
         }
    public function get_html(){return $this->html_output;}
    public function add_pair($id,$label){
        $this->pairs[$id]=$label;
    }
    public function generate_html() {
        $inputs='';
        foreach($this->pairs as $array)$inputs .=str_replace ( $this->input_tags,$array, $this->inputs_template);
        $this->html_output= str_replace('_inputs', $inputs, $this->container_template);
        $this->html_output= str_replace($this->tags, $this->replacements, $this->html_output);
    }
}

class checkbox extends genericForm{
    private $container_template     ='<div _class >_js  <label _class><input type="checkbox" _name _id _value _checked _extra>_label</label></div>';
    private $tags                   =array('_name','_id','_class','_value','_label','_checked','_extra','_js');//array('name'=>'','id'=>'',class=>'','value'=>'','label'=>'','checked'=>'',extra=>'','js'=>'')
    private $replacements           =array();
    public function __construct($details) {
//        if(count($details)!=count($this->tags))report_error('unbalanced elements passed to form object','at '.__FILE__.' LINE: '.__LINE__);
        foreach($details as $key=>$val){
            if($val =='')           $this->replacements[]='';
            else if($key == 'extra'||$key =='label') $this->replacements[]=' '.$val.' ';
            else if($key == 'js')       $this->replacements[]='<script>'.$val.'</script>';
            else                    $this->replacements[]=$key.'="'.$val.'" ';
        }
    }
    public function get_html(){return $this->html_output;}

    public function generate_html() {
        $this->html_output= str_replace($this->tags, $this->replacements, $this->container_template);
    }   
}
/*
<div class=''>
<select name="formGender"id="_id" class="_class">
  <option value="">Select...</option>
  <option value="M">Male</option>
  <option value="F">Female</option>
</select></div>
 */
class select extends genericForm{
    private $container_template = '<div _class> _js<label>  <select _name _id _extra>_inputs</select></label></div>';
    private $pair_template = '<option _value _checked>_label</option>';
    private $tags =array('_name','_id','_class','_js','_extra');//array('name'=>,'id'=>,'class'=>,'js'=>)
    private $pair_tags =array('_value','_label','_checked');//array('value'=>,'label'=>,'selected')
    private $pairs;
    private $replacements;
    private $html_output;
    
    public function __construct($details,$pairs){
//        if(count($details)!=count($this->tags))report_error('unbalanced elements passed to form object','at '.__FILE__.' LINE: '.__LINE__);
        foreach($details as $key=>$val){
            if($val =='')               $this->replacements[]='';
            else if($key == 'extra')    $this->replacements[]=' '.$val.' ';
            else if($key == 'js')       $this->replacements[]='<script>'.$val.'</script>';
            else                        $this->replacements[]=$key.'="'.$val.'" ';
        }
        
         foreach($pairs as $pair){
             $temp =array();
             foreach($pair as $key=>$val){
                if($val =='')           $temp[]='';
                else if($key =='label')  $temp[]=' '.$val.' ';
                else                    $temp[]=$key.'="'.$val.'" ';
                 
            }
            $this->pairs[] = $temp;
         }
    }
    public function get_html() {return $this->html_output;}
    public function generate_html() {
        $options ='';
        foreach($this->pairs as $array){$options .=str_replace ($this->pair_tags, $array, $this->pair_template);}
        $this->html_output= str_replace('_inputs', $options, $this->container_template);
        $this->html_output= str_replace($this->tags, $this->replacements, $this->html_output);
    }
}

class button extends genericForm{
    private $template   ='_before_submiting<div _class>_js<input _type _id  _name _label _extra /></div>';
    private $tags       =array('_label','_name','_class','_id','_type','_extra','_js');//array('label'=>'','name'=>'','class'=>'','id'=>'','type'=>'','extra'=>'','js'=>'')
    private $replacements=  array();
    public function __construct($details) {
        //if(count($details)!=count($this->tags))report_error('unbalanced elements passed to form object','at '.__FILE__.' LINE: '.__LINE__);
        foreach($details as $key=>$val){
            if($val =='')           $this->replacements[]='';
            
            else if($key == 'extra')$this->replacements[]=$val;
            else if($key =='label'||$key=='value') $this->replacements[]='value ="'.$val.'"';
            else if($key == 'js')   $this->replacements[]='<script>'.$val.'</script>';
            else                    $this->replacements[]=$key.'="'.$val.'" ';
        }
    }
    
    public function get_html(){return $this->html_output;}
    public function generate_html() {
        $this->html_output= str_replace($this->tags, $this->replacements, $this->template);
    }
}
//
//$f = new form(array('fomr name','here.php','POST','fomr#id','form#class','encription',' extra','js'));
//$t = new text_field(array('text label','text class', 'text id', 'text name', 'text', 'defualt text','min="15"','js goes here'));
//$p = new text_field(array('password: ','pass*class', 'pass#Id', 'pass name', 'password', 'dodo', "",''));
//$r = new radio(array('radio#name','radio#class','extra="stuff"',''), array(array('male','male label','checked'),array('female','female label','')));
//$m = new checkbox(array('multi-id','multi-class','multi-extra','multi-js'),array(array("ff-name",'ff-vale','ffffff','checked'),array("xx-name",'xx-vale','xxxxxx',''),array("cc-name",'cc-vale','cccc','')));
//$a = new textarea(array('area#name','area label','area*id','area-class','area-extra'));
//$s = new select(array('select-name','select-id','select-class','select#js'), array(array('_name','_id','_class','_js'),array('s','ssssss','selected'),array('m','mmmmm','')));
//$b = new button(array('submit','submit-name','submit-class','submit-id','submit','',''));
//$f->add_elements(array($t,$p,$r,$a,$s,$m,$b));
//$f->generate_html();
//echo 'from outside <br />'.$f->get_html();