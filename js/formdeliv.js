
if(typeof document.getElementById('id_typeelm')!=="undefined" && document.getElementById('id_typeelm')!= null){
	document.getElementById('id_typeelm').onchange=function(){
		if (document.getElementById('id_typeelm').value == '0') {//type ressource
			document.getElementById('id_headerupload').style.display = 'block';
		}else{//type livrable
			document.getElementById('id_headerupload').style.display = 'none';
		}
	};
}
