var i=1;
var d=[];
var t=[];
var e=[];
var f="";
  
document.addEventListener("DOMContentLoaded", function(event) { 

  var items = document.getElementsByClassName('day');
  for (var i = 0; i < items.length; i++) {
    items[i].addEventListener('click', clicknew);
  }

  // add to array and display
  function clicknew(event) {
    var id = this.id.substring(1).replace("_",".").replace("_",".");
	var hm = document.getElementById('hm').value;
	if ( !d.includes( id+' '+hm ) ){
      document.getElementById(this.id).classList.remove("numberCircle1");
      document.getElementById(this.id).classList.add("numberCircle2");
	  d.push ( id+' '+hm );
	  e.push( "" );
	}
    rebuild(); 
  } 

  // remove from array and display
  function clickrm(event) {
    var id = this.id.substring(1).replace("_",".").replace("_",".");
    var complexid = 'i' + id.split(" ")[0].replace(".","_").replace(".","_");
    for (i = 0; i <= d.length; i++) {
      if (d[i] == id) {
        d.splice(i, 1);
        e.splice(i, 1);
      }
    }
	v=true;
    for (i = 0; i <= d.length-1; i++) {
      if ( d[i].includes( id.split(" ")[0] ) ) v=false;
    }
	if (v) {
	  document.getElementById(complexid).classList.remove("numberCircle2");
	  document.getElementById(complexid).classList.add("numberCircle1");
    }
    rebuild();
  } 

  // store value before change
  function clickf(event) {
    if (this.id.substring(0,1) == "o") { f = document.getElementById(this.id).value + " " + document.getElementById('t'+this.id.substring(1)).value; }
    if (this.id.substring(0,1) == "t") { f = document.getElementById('o'+this.id.substring(1)).value + " " + document.getElementById(this.id).value; }
    if (this.id.substring(0,1) == "c") { f = document.getElementById('o'+this.id.substring(1)).value + " " + document.getElementById('t'+this.id.substring(1)).value; }
  } 

  // change
  function clickc(event) {
    if (this.id.substring(0,1) == "o") { cd = document.getElementById(this.id).value + " " + document.getElementById('t'+this.id.substring(1)).value; ce = document.getElementById('c'+this.id.substring(1)).value; }
    if (this.id.substring(0,1) == "t") { cd = document.getElementById('o'+this.id.substring(1)).value + " " + document.getElementById(this.id).value; ce = document.getElementById('c'+this.id.substring(1)).value; }
    if (this.id.substring(0,1) == "c") { cd = document.getElementById('o'+this.id.substring(1)).value + " " + document.getElementById('t'+this.id.substring(1)).value; ce = document.getElementById(this.id).value;}
    for (i = 0; i <= d.length; i++) {
      if (d[i] == f ) {
        d.splice(i, 1);
        e.splice(i, 1);
      }
    }
	d.push(cd);
	e.push(ce);
  } 

  function rebuild() {
    // clear
    var table = document.getElementById('mytable');
    table.innerHTML = "";

    // rebuild
    var tr = table.insertRow();
    tr.id = "row1a";
    var td = tr.insertCell();
    td.align="right";
    td.valign="top";

    var tr = table.insertRow();
    tr.id = "row1b";
    var td = tr.insertCell();
    td.align="right";
    td.valign="top";

    var tr = table.insertRow();
    tr.id = "row1c";
    var td = tr.insertCell();
    td.align="right";
    td.valign="top";

    var tr = table.insertRow();
    tr.id = "row2";
    var td = tr.insertCell();
    td.innerHTML = '<br><input type="text" id="p" name="p" placeholder="Name">';

    // new display
    cols = d.length;
    for (var i = 0; i < cols; i++) {
	  ds = d[i].split(" ");

      var td = document.getElementById("row1a").insertCell();
      td.className = "tdlocal";
      td.id = "td1a_"+i;
      td.innerHTML = '<div class="rm" id="d' + d[i] + '">&#10005;</div><input class="df" id="o'+i+'" name="o'+i+'" value="'+ds[0]+'">';

      var td = document.getElementById("row1b").insertCell();
      td.className = "tdlocal";
      td.id = "td1b_"+i;
      td.innerHTML = '<input class="df" id="t'+i+'" name="t'+i+'" value="'+ds[1]+'">';

      var td = document.getElementById("row1c").insertCell();
      td.className = "tdlocal";
      td.id = "td1c_"+i;
      td.innerHTML = '<input class="df" placeholder="Text" id="c'+i+'" name="c'+i+'" value="'+e[i]+'">';

      var td = document.getElementById("row2").insertCell();
      td.className = "tdlocal";
      td.id = "td2_"+i;
      td.innerHTML = '<div class="divcheckbox"><input type="checkbox" value="1" class="checkboxFiveInput" id="p'+i+'" name="p'+i+'" checked="checked"/><\/div>';

      document.getElementById('d'+d[i]).addEventListener('click', clickrm);

      document.getElementById('o'+i).addEventListener('click', clickf, true);
      document.getElementById('t'+i).addEventListener('click', clickf, true);
      document.getElementById('c'+i).addEventListener('click', clickf, true);

      document.getElementById('o'+i).addEventListener('change', clickc);
      document.getElementById('t'+i).addEventListener('change', clickc);
      document.getElementById('c'+i).addEventListener('change', clickc);
    }
    document.getElementById("i").value = cols;
  }

  document.getElementById('formsubmit').addEventListener('click', cform);

  function cform(event) {
    if (document.getElementById('event').value == "") {
      document.getElementById('event').classList.add("inputBorder");
      document.getElementById('event').focus();
      event.preventDefault();
    }
   if (document.getElementById('o0').value == "") {
      document.getElementById('o0').classList.add("inputBorder");
      document.getElementById('o0').focus();
	  event.preventDefault();
    }
  }

});

