var DBPT_LANG="de";var DBPT_LEVEL="spellgram";var DBPT_STYLE="duden";var DBPT_FOREIGN_WORDS=0;var DBPT_COLLOQUIAL_WORDS=0;var DBPT_DIALECT=0;var DBPT_OBSOLETE_WORDS=0;var DBPT_SENTLENGTH=0;var DBPT_COOKIE_NOTICE="Enable cookies for correct plugin work.";var DBPT_COOKIE_NAME="dbpt";var options={path:"/",expires:365};function cookie_setup(config){config=$.cookie(DBPT_COOKIE_NAME);if(!config){config=new Array();config["lang"]=DBPT_LANG;config["level"]=DBPT_LEVEL;config["stylew"]=DBPT_STYLE;config["foreign_words"]=DBPT_FOREIGN_WORDS;config["colloquial_words"]=DBPT_COLLOQUIAL_WORDS;config["dialect"]=DBPT_DIALECT;config["obsolete_words"]=DBPT_OBSOLETE_WORDS;config["sentlength"]=DBPT_SENTLENGTH;config=cookie_save(config)}else{config=cookie_load(config)}if(!$.cookie(DBPT_COOKIE_NAME))alert(DBPT_COOKIE_NOTICE);return config}function cookie_save(config){var c='';for(i in config){c+=i+"="+config[i]+";"}$.cookie(DBPT_COOKIE_NAME,c,options);return config}function cookie_load(config){var tmp=config.split(";");config=new Array();var itmp='';for(i in tmp){itmp=tmp[i].split("=");config[itmp[0]]=itmp[1]}return config}