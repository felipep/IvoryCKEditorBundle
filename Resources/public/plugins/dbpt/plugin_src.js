/*
Copyright (c) 2009-11, Macronsoftware - petrj. All rights reserved.
*/
var ename = '';
CKEDITOR.plugins.add("dbpt", {
	requires: ['iframedialog'],

	init : function(a) {

		CKEDITOR.dialog.addIframe('dbpt_dialog', 'Duden Korrektor f�r CKEditor', this.path + 'dialogs/spellchecker.html', 800, 600, function(){});

		//var b = a.addCommand("dbpt",new CKEDITOR.dialogCommand("dbpt"));
		var b = a.addCommand("dbpt",{
			exec: odbpt_onclick
		});
		ename = a.name;
		b.modes = {
			wysiwyg : 1,
			source : 1
		};

		b.canUndo = false;

		a.ui.addButton("DBPT", {
			label: "Korrektur starten",//"DBPT",//a.lang.about.title,
			command: "dbpt",
			icon : this.path + "images/start.gif"
		});
        
		CKEDITOR.dialog.add("dbpt", this.path + "js/dbpt.js");

		//opt
		CKEDITOR.dialog.addIframe('dbptopt_dialog', 'DBPT Options', this.path + 'dialogs/options.html', 500, 450, function(){});
		b = a.addCommand("dbptopt",{
			exec: dbptopt_onclick
		});
		a.ui.addButton("DBPT Options", {
			label: "Optionen anzeigen",//"DBPT Options",//a.lang.about.title,
			command: "dbptopt",
			icon : this.path + "images/options.gif"
		});
		CKEDITOR.dialog.add("dbptopt", this.path + "js/dbpt.js");
		//dict
		CKEDITOR.dialog.addIframe('dbptdict_dialog', 'DBPT Dictionary', this.path + 'dialogs/userdic.html', 480, 600, function(){});
		b = a.addCommand("dbptdict",{
			exec: dbptdict_onclick
		});
		a.ui.addButton("DBPT Dictionary", {
			label: "Benutzerw�rterbuch anzeigen",//"DBPT Dictionary",//a.lang.about.title,
			command: "dbptdict",
			icon : this.path + "images/userdic.gif"
		});
		CKEDITOR.dialog.add("dbptdict", this.path + "js/dbpt.js");
		//exc
		CKEDITOR.dialog.addIframe('dbptexc_dialog', 'DBPT Exceptions', this.path + 'dialogs/exceptdic.html', 480, 650, function(){});
		b = a.addCommand("dbptexc",{
			exec: dbptexc_onclick
		});
		a.ui.addButton("DBPT Exceptions", {
			label: "Ausnahmew�rterbuch anzeigen",//"DBPT Exceptions",//a.lang.about.title,
			command: "dbptexc",
			icon : this.path + "images/excdic.gif"
		});
		CKEDITOR.dialog.add("dbptexc", this.path + "js/dbpt.js");
		//login
		CKEDITOR.dialog.addIframe('dbptlogin_dialog', 'DBPT Login', this.path + 'dialogs/login.html', 510, 209, function(){});
		b = a.addCommand("dbptlogin",{
			exec: dbptlogin_onclick
		});
		a.ui.addButton("DBPT Login", {
			label: "DBPT Login",//a.lang.about.title,
			command: "dbptlogin",
			icon : this.path + "images/login.gif"
		});
		CKEDITOR.dialog.add("dbptlogin", this.path + "js/dbpt.js");
	}

});

function odbpt_onclick(e)
{
	// run when custom button is clicked
	e.openDialog('dbpt_dialog');
}

function dbptopt_onclick(e)
{
	e.openDialog('dbptopt_dialog');
}

function dbptdict_onclick(e)
{
	e.openDialog('dbptdict_dialog');
}

function dbptexc_onclick(e)
{
	e.openDialog('dbptexc_dialog');
}

function dbptlogin_onclick(e)
{
	e.openDialog('dbptlogin_dialog');
}