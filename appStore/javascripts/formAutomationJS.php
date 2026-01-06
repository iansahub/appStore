<?php 
/*
this document deliberately loads without a namescript of its own so that it belongs to the current namescript whichever ...form.php file 
it is inclded in

this document is rendered by PHP so as to ensure that the namespace variable is available to write the JS in such a way that it does not
clash with other forms which might be embeded on the same parent page running their own version of this script.
*/
?>
			<script>
				<?php /*these are namespace-specific scripts so should be included whenever the content of the body is included.
				if there are multiple files contributing to the body of a page, there will be multiple verisions of this 
				script block included, each taken from their respective (included) file. */?>
				if (typeof <?php echo $nameSpaceID;?>  == 'undefined') {
					var <?php echo $nameSpaceID;?> = {};//create a namespace for this file if it hasnt been created.
				}
				
				var locale = '<?php echo $$nameSpaceID['templateVar']['loc']['val']; ?>';
				var xhr = [];
				var xhrCounter = 0;
				
				//var date = new Date(1626419097000);
				//console.log(date.toLocaleString(locale,{year: 'numeric', month: '2-digit', day: '2-digit', hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:false }));
			
				<?php echo $nameSpaceID;?>.preSubmitCheck = function(){
					if(document.getElementById('<?php echo $nameSpaceID . "_form";?>').checkValidity() == true){
						document.getElementById("<?php echo $nameSpaceID . "_form";?>").submit();
					}else{
						var ele = document.getElementById("<?php echo $nameSpaceID . "_form";?>").querySelectorAll('input, select, textarea');
						//[].forEach.call(e.form.querySelectorAll('input, select, textarea'), (h)=>{
					
						for (i = 0; i < ele.length; i++) { 
							myfield = ele[i];
							if(myfield.classList.contains('ignoreClientsideErrors')){
								//do nothing. DOM elements with 'ignoreClientsideErrors' class should have their form attribute set to "" 
							}else{
								if(myfield.checkValidity() == false){
									<?php echo $nameSpaceID;?>.selectCustomErrorMessage(myfield);
									myfield.focus();
									var event = new Event('input', { bubbles: true, cancelable: true,});
									myfield.dispatchEvent(event);	
									break;
								}
							}
						}
					}	
				}
	
	
				
	<?php echo $nameSpaceID;?>.init = function(){
		
		
		//event.submitter polyfil. needed for safari pre-15.4 released 13/3/22
		var lastBtn = null
		document.addEventListener('click',function(e){
			if (!e.target.closest) return;
			lastBtn = e.target.closest('button, input[type=submit]');
		}, true);
		document.addEventListener('submit',function(e){
			if (e.submitter) return;
			var canditates = [document.activeElement, lastBtn];
			for (var i=0; i < canditates.length; i++) {
				var candidate = canditates[i];
				if (!candidate) continue;
				if (!candidate.form) continue;
				if (!candidate.matches('button, input[type=button], input[type=image]')) continue;
				e.submitter = candidate;
				return;
			}
			e.submitter = e.target.querySelector('button, input[type=button], input[type=image]');
		}, true);


		
		//template - hide items which should only be included in the top window (not in iframes or embedded document objects)
		//and if login is required and this is the top level document, display the login form
		if(window.self !== window.top) {
			[].forEach.call(document.querySelectorAll('.topWindowOnly'), (e)=>{
				e.style.display = 'none';
			});
			
		}
		
		//cant check authenticated here at present because if i am in debug mode, it starts writing debug html here, where JS only is expected. 
		
		<?php //if(authenticated() == "false" && $$nameSpaceID['templateVar']['includeSecurity']['val'] === true){?>
			//$('#modal-form-login').modal('show');
		<?php //}?>
	
		
		
		window.addEventListener('storage', function(event) {
			//alert('storage was updated');
			if(sessionStorage.getItem('newImagesDataStream') !== null){
				alert('process storage');
				alert('sessionStorage.form');
			}
		});
		
		

		//var source = new EventSource("/klogin/sse.php");
		//source.onmessage = function(event) { alert('sse gave me a message')};
		
	

		//template - set initial error reporting  for any errors passed in URL (which php has sumarized to JSONErrors js object)
		if(document.getElementById('<?php echo $nameSpaceID . "_form";?>')){
			
			//var JSONErrors = JSON.parse('{"medName": ["hello","its me"],"longDescription": ["bonjour"]}');
			//console.log(JSONErrors);
			
			//console.log('<?php //var_dump($$nameSpaceID['templateVar']['JSONErrors']['val']);?>');
			
			
			
			var JSONErrors = JSON.parse('<?php echo json_encode($$nameSpaceID['templateVar']['JSONErrors']['val']); ?>');
			
			
			if(JSONErrors === null){JSONErrors = JSON.parse('{}')};
			//console.log(JSONErrors);
			[].forEach.call(document.getElementById('<?php echo $nameSpaceID . "_form";?>').querySelectorAll('input, select, textarea'), (e)=>{
				
				if(e.classList.contains('ignoreClientsideErrors')){
					//do nothing
				}else{
					<?php //disable inbuilt error messaging and attach my own errors and handling.?>
					e.addEventListener( "invalid", function( event ) {
						event.preventDefault();
						<?php echo $nameSpaceID;?>.selectCustomErrorMessage(this);
					}, true );
					
					<?php //handle any errors passed in the querystring or 'naturally ocurring'?>
		
					if(e.name in JSONErrors){
						var a = "";
						JSONErrors[e.name].forEach(function(obj) {
							a += obj + ", ";
							a = a.slice(0, -2); 
						})
						e.setCustomValidity(a);
						//displayCustomErrorInfoMessage(e);
						showCustomErrorInfoMessage(e);
					}else{
						e.checkValidity();
					}
					

					e.addEventListener( "blur", function( event ){
							//disable submit buttons if the form contains errors
							if(e.form){
								[].forEach.call(e.form.querySelectorAll('button[type="submit"], input[type="submit"]'), (g)=>{
										g.disabled = !e.form.checkValidity();
										if(g.disabled){g.setAttribute("disabled","disabled");}
								}, true );
							}
							
					}, true );
					
					
					e.addEventListener("focus", function( event ){
						e.reportValidity();
					});
										
					e.addEventListener("input", function( event ){
						e.reportValidity();
						
						if(e.validity.valid){
							e.setCustomValidity('');
							document.getElementById(e.id +'Error').innerHTML ='&nbsp';
							document.getElementById(e.id +'Container').classList.remove('govuk-form-group--error');
						}else{
							document.getElementById(e.id +'Container').classList.add('govuk-form-group--error');
							<?php echo $nameSpaceID;?>.selectCustomErrorMessage(e);
						}
					});
				}//end of IF ignoreClientsideErrors
			
			});
		}
		
		//when any form on the page is made dirty, enable all of its submit buttons IF there are no errors on the form
		//dont think i need this now i have class disableIfFormIsInvalid
		[].forEach.call(document.querySelectorAll('form'), (e)=>{
			e.addEventListener("input", function () {
				[].forEach.call(this.querySelectorAll('button[type="submit"], input[type="submit"]'), (f)=>{
					[].forEach.call(this.querySelectorAll('#'+this.id +" #"+f.id), (g)=>{
						//g.disabled = false;
						//alert(g.disabled);
						//g.disabled = !e.checkValidity();
					}, true );
				}, true );
				
				
				
			}, true );
		});
		

		//------------------------tagcloud control automation-----------------------------------
		
		//when a tag's delete button is clicked, remove the tag
		function tagDeleteButtonClick(e) {
			//calculate the tagcloud to target.
			var cloud = this.closest('ul');
			this.parentNode.remove();
			refreshTagCloudSubmittableInput(cloud);
			
		};
		
		function createNewTags(e){
			

			//e is the element which contains a string value which is to be converted into a tag/tags
			console.log('createnewtags can see e and this');
			console.log(e);
			console.log(e);
			
			
			
			var newTag = {};
			newTag[0] = document.createElement('li');
				newTag[0].classList.add('govuk-tag--blue');
				newTag[0].classList.add('tagCloud-tag');
				
			var newSpan = {};
			newSpan[0] = document.createElement('span');
				newSpan[0].classList.add('tagCloud-tag-label');
				
			var newButton = {};
			newButton[0] = document.createElement('button');
				newButton[0].classList.add('tagDeleteButton');
				newButton[0].tabIndex = '99';
			
			var newSup = {};
			newSup[0] = document.createElement('sup')
			
			var newText = {};
			newText[0] = document.createTextNode('x');
			newSup[0].appendChild(newText[0]);
			
			newButton[0].appendChild(newSup[0]);
			newTag[0].appendChild(newButton[0]);

			//e is the element which contains a string value which is to be converted into a tag/tags
			var parts = e.value.split(/(?:,| |;)+/); //split the string currently within the input box by comma, space and semicolon
			parts.forEach((part, i) => {
				if(part != ' ' && part !=''){
					newSpan[i+1] = newSpan[0].cloneNode(true);
					newText[i+1] = newText[0].cloneNode(true);
					newText[i+1].nodeValue = part;
					newSpan[i+1].appendChild(newText[i+1]);
					
					newTag[i+1] = newTag[0].cloneNode(true);
					newTag[i+1].prepend(newSpan[i+1]);
					
					[].forEach.call(newTag[i+1].querySelectorAll('.tagDeleteButton'),(e)=>{
						e.addEventListener("click", tagDeleteButtonClick);
					});
									
					e.closest('ul').insertBefore(newTag[i+1], e.parentNode)

				}
			});
			e.value = "";
			refreshTagCloudSubmittableInput(e.closest('ul'));
		}
		
		
		function refreshTagCloudSubmittableInput(tagcloudULElement){
			var a = "";
			[].forEach.call(tagcloudULElement.getElementsByTagName('li'), (tag)=>{
				a += tag.firstChild.innerHTML + ";" ;
			}, true );
			document.getElementById(tagcloudULElement.dataset.inputfield).value = a;
		}
		
		
		//attach a click event to all tag's delete buttons
		[].forEach.call(document.querySelectorAll('.tagDeleteButton'), (e)=>{
			e.addEventListener("click", tagDeleteButtonClick);
		});
	
		//make the cloud's non-form-submittable text input box respond to key presses which indicate it is ready to convert its contents to tags
		[].forEach.call(document.querySelectorAll('.tagcloudInput'), (e)=>{
			
			e.addEventListener("blur", function (exitedFrom) {
				createNewTags(exitedFrom.target);				
			});
			
			e.addEventListener("keyup", function (pressed) {
				if(pressed.key == ' ' || pressed.key == ";" || pressed.key == "Enter" || pressed.key == ","){
					createNewTags(e);
				}	
			}, true );
			
			e.addEventListener("paste", function () {
				event.preventDefault(); //don't just paste
				e.value = (event.clipboardData || window.clipboardData).getData("text");
				createNewTags(e);
			}, true );
			
			
			
		});	
		

		
		//---------------------end of tagcloud control automation-------------------------------------		

	} //end of .init()
				
			
	

	

	//template version
	<?php echo $nameSpaceID;?>.selectCustomErrorMessage = function (that){
		that.setCustomValidity(" ");
		if(that.form){
			[].forEach.call(that.form.querySelectorAll('.disableIfFormIsInvalid'), (g)=>{g.setAttribute("disabled","disabled");},true);
		}
		if(that.validity.badInput){
			//likely echoing a that.setCustomValidity('xxxx'); statement but echoing entire 
			//command not just the xxxx allows entire struture of the decsion to be language-specific. 
			<?php echo $$nameSpaceID['DOM']['selectCustomErrorMessageJS']['badInput'] ?? '"DOMContent.php needs a JS error for \'bad input\'"'.PHP_EOL;?>
		}else if(that.validity.rangeOverflow){
			<?php echo $$nameSpaceID['DOM']['selectCustomErrorMessageJS']['rangeOverflow'] ?? '"DOMContent.php needs a JS error for \'range overflow\'"'.PHP_EOL;?>
		}else if(that.validity.rangeUnderflow){
			<?php echo $$nameSpaceID['DOM']['selectCustomErrorMessageJS']['rangeUnderflow'] ?? '"DOMContent.php needs a JS error for \'range underflow\'"'.PHP_EOL;?>
		}else if(that.validity.stepMismatch){
			<?php echo $$nameSpaceID['DOM']['selectCustomErrorMessageJS']['stepMismatch'] ??  '"DOMContent.php needs a JS error for \'step mismatch\'"' .PHP_EOL;?>
		}else if(that.validity.tooLong){
			<?php echo $$nameSpaceID['DOM']['selectCustomErrorMessageJS']['tooLong']?? '"DOMContent.php needs a JS error for \'too long\'"' .PHP_EOL;?>
		}else if(that.validity.typeMismatch){
			<?php echo $$nameSpaceID['DOM']['selectCustomErrorMessageJS']['typeMismatch'] ?? '"DOMContent.php needs a JS error for \'type mismatch\'"' .PHP_EOL;?>
		}else if(that.validity.valueMissing){
			<?php echo $$nameSpaceID['DOM']['selectCustomErrorMessageJS']['valueMissing'] ?? '"DOMContent.php needs a JS error for \'value missing\'"' .PHP_EOL;?>
		}else if(that.validity.patternMismatch){
			<?php echo $$nameSpaceID['DOM']['selectCustomErrorMessageJS']['patternMismatch'] ?? '"DOMContent.php needs a JS error for \'pattern mismatch\'"' .PHP_EOL;?>
		}else if(that.validity.tooShort){
			<?php echo $$nameSpaceID['DOM']['selectCustomErrorMessageJS']['tooShort'] ?? '"DOMContent.php needs a JS error for \'too short\'"' .PHP_EOL;?>
		}else{
			that.setCustomValidity(""); //it wasnt invalid afterall
			//document.getElementById(that.id + "ErrorText").innerHTML = "";//nbsp
			document.getElementById(that.id + "Error").innerHTML = "&nbsp;";
			if(that.form){
				[].forEach.call(that.form.querySelectorAll('.disableIfFormIsInvalid'), (g)=>{g.removeAttribute("disabled");},true);
			}
		}
		//displayCustomErrorInfoMessage(that);
		showCustomErrorInfoMessage(that);
	}			
	</script>
	