<!DOCTYPE html>

<!--[if IE 8]> <html lang="en" class="ie8"> <![endif]-->

<!--[if IE 9]> <html lang="en" class="ie9"> <![endif]-->

<!--[if !IE]><!--> <html lang="en"> <!--<![endif]-->

<!-- BEGIN HEAD -->

<head>

	<meta charset="utf-8" />

	<title><?php echo $data['pageTitle']?></title>

	<meta content="width=device-width, initial-scale=1.0" name="viewport" />

	<meta content="manage" name="description" />

	<meta content="webdev" name="author" />

	<!-- BEGIN GLOBAL MANDATORY STYLES -->
	<?php echo $data['includeJsAndCss'][1];?>
	<!-- END GLOBAL MANDATORY STYLES -->

	<link rel="shortcut icon" href="<?php echo Doo::conf()->global?>bootstrap/media/image/favicon.ico" />
	<script type="text/javascript">
        var BASEURL = "<?php echo Doo::conf()->APP_URL?>";
        var APPURL = "<?php echo appurl('')?>";
        var autoSubmit = true;
    </script>

</head>

<!-- END HEAD -->

<!-- BEGIN BODY -->

<body class="page-header-fixed">
	<div id="form"><?php echo $data['content']?></div>
	<hr>
	<div id="ajaxResult"></div>
</body>
<?php echo $data['includeJsAndCss'][0];?>

<!-- END BODY -->
	<script type="text/javascript">
	$(document).ready(function(){
		// $('#myForm').ajaxForm(function() { 
  //           alert("Thank you for your comment!"); 
  //       });
    var options = { 
        target:        '#ajaxResult',   // target element(s) to be updated with server response 
        beforeSubmit:  showRequest,  // pre-submit callback 
        success:       showResponse,  // post-submit callback 
 
        // other available options: 
        //url:       url         // override for form's 'action' attribute 
        //type:      type        // 'get' or 'post', override for form's 'method' attribute 
        //dataType:  null        // 'xml', 'script', or 'json' (expected server response type) 
        //clearForm: true        // clear all form fields after successful submit 
        //resetForm: true        // reset the form after successful submit 
 
        // $.ajax options can be used here too, for example: 
        timeout:   15000 
    }; 

 	// pre-submit callback 
	function showRequest(formData, jqForm, options) { 
	    // formData is an array; here we use $.param to convert it to a string to display it 
	    // but the form plugin does this for you automatically when it submits the data 
	    var queryString = $.param(formData); 
	 
	    // jqForm is a jQuery object encapsulating the form element.  To access the 
	    // DOM element for the form do this: 
	    // var formElement = jqForm[0]; 
	 	/*
	    alert('About to submit: \n\n' + queryString); 
	 	*/
	    // here we could return false to prevent the form from being submitted; 
	    // returning anything other than false will allow the form submit to continue 
	    return true; 
	} 
	 
	// post-submit callback 
	function showResponse(responseText, statusText)  { 
	    // for normal html responses, the first argument to the success callback 
	    // is the XMLHttpRequest object's responseText property 
	 
	    // if the ajaxForm method was passed an Options Object with the dataType 
	    // property set to 'xml' then the first argument to the success callback 
	    // is the XMLHttpRequest object's responseXML property 
	 
	    // if the ajaxForm method was passed an Options Object with the dataType 
	    // property set to 'json' then the first argument to the success callback 
	    // is the json data object returned by the server 
	 	/*
	    alert('status: ' + statusText + '\n\nresponseText: \n' + responseText + 
	        '\n\nThe output div should have already been updated with the responseText.'); 
*/
	} 
    // bind form using 'ajaxForm' 
    $('#myForm').ajaxForm(options); 
	if(autoSubmit){
		$('#submit').click();
		$('#ajaxResult').height(1500);
	}
	});
	</script>
</html>