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
            "  this.frame = document.createElement('IFRAME');\n"+
            "  this.frame.style.width = '1px';\n"+
            "  this.frame.style.height = '1px';\n" +
            "  this.frame.style.position = 'absolute';\n" +
            "  this.frame.style.top = '-100px';\n" +
            "  this.frame.tabindex = '-1';\n" +
            "  this.frame.src = 'http://localhost:'+opener_port+'/frame'\n" +
            "  window.top.document.body.appendChild(this.frame);\n" +
            "  this.request = function(url,data,id) {\n" +
            "    getIFrameWindow(this.frame).postMessage({data:data,url:url,id:id},'http://localhost:'+opener_port);\n" +
            "  };\n"+
            "  this.openDocument = function(document_id, version_id, storage_id, storage_revision, filename, readonly) {\n" +
            "    var locker = lock_screen(null,'Downloading document...');\n"+
            "    var id = generateID();\n"+
            "    var listener = function(event) {\n"+
            "      if (event.origin != 'http://localhost:'+opener_port) return;\n"+
            "      if (event.data.id != id) return;\n" +
            "      window.top.removeEventListener('message', listener);\n" +
            "      unlock_screen(locker);\n" +
            "      if (event.data.status != 200) { error_dialog('We are unable to contact the PN Document Opener software. Please try to restart it.'); window.top.pndocuments._connected_port = -1; }\n" +
            "    }\n" +
            "    window.top.addEventListener('message', listener, false);\n"+
            "    this.request('/open_document','doc='+document_id+'&version='+version_id+'&id='+storage_id+(storage_revision?'&revision='+storage_revision:'')+'&filename='+encodeURIComponent(filename)+'&readonly='+(readonly?'true':'false')+'&server='+server+'&port='+server_port+'&session_name='+php_session_cookie+'&session_id='+php_session_id+'&pn_version='+pn_version,id);\n" +
            "  };\n"+
            "  this.update = function(ondone) {\n" +
            "    var locker = lock_screen(null,'Updating PN Document Opener...');\n" +
            "    var id = generateID();\n" +
            "    var listener = function(event) {\n" +
            "      if (event.origin != 'http://localhost:'+opener_port) return;\n" +
            "      if (event.data.id != id) return;\n" +
            "      window.top.removeEventListener('message', listener);\n" +
            "      unlock_screen(locker);\n" +
            "      if (event.data.status != 200) { error_dialog('We are unable to contact the PN Document Opener software. Please try to restart it.'); window.top.pndocuments._connected_port = -1; }\n" +
            "      ondone();\n"+
            "    }\n" +
            "    window.top.addEventListener('message', listener, false);\n" +
            "    this.request('/update','server='+server+'&port='+server_port+'&session_name='+php_session_cookie+'&session_id='+php_session_id+'&pn_version='+pn_version,id);\n" +
            "  };\n" +
            "}\n"
            ;

        public static string frame =
            "<html><body><script type='text/javascript'>"+
            "function receiveMessage(event) {\n" +
            "  var xhr = new XMLHttpRequest();\n" +
            "  xhr.open(event.data.data?'POST':'GET',event.data.url,true);\n" +
            "  xhr.onreadystatechange = function() {\n" +
            "    if (this.readyState != 4) return;\n" +
            "    window.top.postMessage({id:event.data.id,status:xhr.status,response:xhr.responseText},'*');\n"+
            "  };\n" +
            "  xhr.send(event.data.data);\n" +
            "}\n"+
            "window.addEventListener('message', receiveMessage, false);\n"+
            "</script></body></html>"
            ;
    }
}
