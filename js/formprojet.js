

document.getElementById('fitem_id_type').style.display = 'none';
if(document.getElementsByName("typeprojet")[0].value=='0'){//si c'est un type de projet
document.getElementsByName("visible")[0].value = 0;//on rend le type de projet caché par defaut
}
window.onload = function ()
{
	if(typeof document.getElementsByName('choixprojet')[0]!=="undefined" && document.getElementsByName('choixprojet')[0]!= null ){
		if(typeof document.getElementsByName('hideproject')[0]==="undefined" && document.getElementsByName('hideproject')[0]== null ){
			if(document.getElementsByName('choixprojet')[0].value==2){
				if(document.getElementsByName('choixprojet')[0]){
					document.getElementById('fitem_id_type').style.display = 'block';
					document.getElementsByName("typeprojet")[0].value= document.getElementById('id_type').value;
					var t =setTimeout('callprojectdata()',2500);
				}
			}
		}
	}
	if(typeof document.getElementsByName('choixprojet')[0]!=="undefined" && document.getElementsByName('choixprojet')[0]!= null ){
		if(document.getElementsByName('choixprojet')[0].value==1){//type projet
				document.getElementById('fitem_id_projectconfidential').style.display = 'none';
				document.getElementById('fitem_id_projectstart').style.display = 'none';
				document.getElementById('fitem_id_projectend').style.display = 'none';
				document.getElementById('fitem_id_commanditaire').style.display = 'none';
		}
	}
}
function callprojectdata(){
	setprojectdata(document.getElementById('id_type').value);
}
if(typeof document.getElementById('id_choixprojet')!=="undefined" && document.getElementById('id_choixprojet')!= null){
	document.getElementsByName("visible")[0].value = 0;//on rend le type de projet caché par defaut
	document.getElementById('id_choixprojet').onchange=function(){
		if (document.getElementById('id_choixprojet').value == '2') {//projet
			document.getElementById('fitem_id_type').style.display = 'block';
			document.getElementById('fitem_id_projectconfidential').style.display = 'block';
			document.getElementById('fitem_id_projectstart').style.display = 'block';
			document.getElementById('fitem_id_projectend').style.display = 'block';
			document.getElementById('fitem_id_commanditaire').style.display = 'block';
			document.getElementById('fitem_id_projectgrpid').style.display = 'none';
			document.getElementsByName("typeprojet")[0].value= document.getElementById('id_type').value;
			setprojectdata(document.getElementById("id_type").value);
			document.getElementsByName("visible")[0].value = 1;//on rend le projet visible par defaut
		}else{//type projet
			document.getElementById('fitem_id_type').style.display = 'none';
			document.getElementById('fitem_id_projectconfidential').style.display = 'none';
			document.getElementById('fitem_id_projectstart').style.display = 'none';
			document.getElementById('fitem_id_projectend').style.display = 'none';
			document.getElementById('fitem_id_commanditaire').style.display = 'none';
			document.getElementById('fitem_id_projectgrpid').style.display = 'block';
			document.getElementsByName("typeprojet")[0].value='0';
			document.getElementsByName("visible")[0].value = 0;//on rend le type de projet caché par defaut
		}
	};
}
document.getElementById('id_type').onchange=function(){
	document.getElementsByName("typeprojet")[0].value= document.getElementById('id_type').value;
	setprojectdata(document.getElementById("id_type").value);
};

/* cacher les champs non utilisés */
document.getElementById('fitem_id_assessmentstart').style.display = 'none';
document.getElementById('fitem_id_timeunit').style.display = 'none';
document.getElementById('fitem_id_costunit').style.display = 'none';
document.getElementById('fitem_id_assessmentstart').style.display = 'none';
document.getElementById('fitem_id_allownotifications').style.display = 'none';
document.getElementById('fitem_id_enablecvs').style.display = 'none';
document.getElementById('fitem_id_useriskcorrection').style.display = 'none';
/* cacher les fieldset non utilisés */
document.getElementById('id_features').style.display = 'none';
document.getElementById('id_headeraccess').style.display = 'none';
document.getElementById('id_headergrading').style.display = 'none';
//document.getElementById('id_modstandardelshdr').style.display = 'none';

function setprojectdata (idType) {

	Y.io(M.cfg.wwwroot + '/mod/project/ajax/ajax.php', {
		//The needed paramaters
		//TODO : Check has_capability sur l'appel ajax
		data: {action: 'getdatatype',
			   id: 'id',
			   idtype: idType,
			   sesskey: M.cfg.sesskey
		},

		timeout: 5000, //5 seconds for timeout I think it is enough.

		//Define the events.
		on: {
			start : function(transactionid) {
				//spinner.show();
			},
			success : function(transactionid, xhr){
				var response = xhr.responseText;
				var projtype = Y.JSON.parse(response);
				/*window.setTimeout(function(e) {
					spinner.hide();
				}, 250);
				*/
				document.getElementById('id_projectconfidential').value = projtype.projectconfidential;
				tinyMCE.get('id_introeditor').setContent(projtype.intro);
			},
			failure : function(transactionid, xhr) {
				var msg = {
					name : xhr.status+' '+xhr.statusText,
					message : xhr.responseText
				};
				return new M.core.exception(msg);
				//~ this.ajax_failure(xhr);
				//spinner.hide();
			}
		},
		context:this
	});
}