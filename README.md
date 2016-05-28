# php_forms
Object Oriented approach to HTML forms.

This php script encapsulates the generation of HTML forms in one place, making it easier to update and redesign

# Usage Example:

<?php
	$form = new form(array('fomr name','here.php','POST','fomr#id','form#class','encription',' extra','js'));
	$text = new text_field(array('text label','text class', 'text id', 'text name', 'text', 'defualt text','min="15"','js goes here'));
	$password = new text_field(array('password: ','pass*class', 'pass#Id', 'pass name', 'password', 'dodo', "",''));
	$radio = new radio(array('radio#name','radio#class','extra="stuff"',''), array(array('male','male label','checked'),array('female','female label','')));
	$checkbox = new checkbox(array('multi-id','multi-class','multi-extra','multi-js'),array(array("ff-name",'ff-vale','ffffff','checked'),array("xx-name",'xx-vale','xxxxxx',''),array("cc-name",'cc-vale','cccc','')));
	$textarea = new textarea(array('area#name','area label','area*id','area-class','area-extra'));
	$submit = new button(array('submit','submit-name','submit-class','submit-id','submit','',''));
	$f->add_elements(array($text,$password,$radio,$checkbox,$textarea,$submit));
	$f->generate_html();
	echo $f->get_html();
?>