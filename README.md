# php_forms
Object Oriented approach to HTML forms.

This php script encapsulates the generation of HTML forms in one place, making it easier to update and redesign

# Usage Example:

<?php <br />
	$form = new form(array('fomr name','here.php','POST','fomr#id','form#class','encription',' extra','js'));<br/>
	$text = new text_field(array('text label','text class', 'text id', 'text name', 'text', 'defualt text','min="15"','js goes here'));<br />
	$password = new text_field(array('password: ','pass*class', 'pass#Id', 'pass name', 'password', 'dodo', "",''));<br />
	$radio = new radio(array('radio#name','radio#class','extra="stuff"',''), array(array('male','male label','checked'),array('female','female label','')));<br />
	$checkbox = new checkbox(array('multi-id','multi-class','multi-extra','multi-js'),array(array("ff-name",'ff-vale','ffffff','checked'),array("xx-name",'xx-vale','xxxxxx',''),array("cc-name",'cc-vale','cccc','')));<br />
	$textarea = new textarea(array('area#name','area label','area*id','area-class','area-extra'));<br />
	$submit = new button(array('submit','submit-name','submit-class','submit-id','submit','',''));<br />
	$form->add_elements(array($text,$password,$radio,$checkbox,$textarea,$submit));<br />
	$form->generate_html();<br />
	echo $f->get_html();<br />
?>