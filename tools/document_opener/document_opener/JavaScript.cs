using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

namespace document_opener
{
    class JavaScript
    {
        public static string js = 
            "function PNDocumentOpener(opener_port,server,server_port,php_session_cookie,php_session_id,pn_version) {\n"+
            "  this.version = '"+DocumentOpener.version+"';\n"+
            "  this.request = function(url,data,handler) {\n"+
            "    var xhr = new XMLHttpRequest();\n"+
            "    xhr.open(data?'POST':'GET','http://localhost:'+opener_port+'/'+url,true);\n"+
            "    xhr.onreadystatechange = function() {\n"+
            "      if (this.readyState != 4) return;\n"+
            "      handler(this);\n"+
            "    };\n"+
            "    xhr.send(data);\n"+
            "  };\n"+
            "  this.openDocument = function(document_id, version_id, storage_id, storage_revision, filename, readonly) {\n" +
            "    var locker = lock_screen(null,'Downloading document...');\n"+
            "    this.request('open_document','doc='+document_id+'&version='+version_id+'&id='+storage_id+(storage_revision?'&revision='+storage_revision:'')+'&filename='+encodeURIComponent(filename)+'&readonly='+(readonly?'true':'false')+'&server='+server+'&port='+server_port+'&session_name='+php_session_cookie+'&session_id='+php_session_id+'&pn_version='+pn_version,function(xhr){\n" +
            "      unlock_screen(locker);\n"+
            "    });\n"+
            "  };\n"+
            "}\n"
            ;
    }
}
