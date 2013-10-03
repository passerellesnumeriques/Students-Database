package org.pn.jsdoc;

import java.io.File;
import java.io.FileInputStream;

import org.mozilla.javascript.CompilerEnvirons;
import org.mozilla.javascript.Parser;
import org.mozilla.javascript.ast.AstRoot;
import org.mozilla.javascript.ast.Comment;

public class JavaScriptDocGenerator {

	public static void main(String[] args) {
		String src_path = args[0];
		try {
			File src_file = new File(src_path);
			FileInputStream in = new FileInputStream(src_file);
			byte[] buf = new byte[100000];
			int nb = in.read(buf);
			in.close();
			String src = new String(buf,0,nb);
			
		    CompilerEnvirons environment = new CompilerEnvirons();
	        //environment.setErrorReporter(testErrorReporter);
	        environment.setRecordingComments(true);
	        environment.setRecordingLocalJsDocComments(true);
			Parser p = new Parser(environment);
	        AstRoot script = p.parse(src, null, 0);
//	        for (Comment c : script.getComments()) {
//	        	System.err.println("Comment: "+c.getValue());
//	        	System.err.println("Enclosing function: "+c.getEnclosingFunction());
//	        	System.err.println("Enclosing scope:"+c.getEnclosingScope());
//	        	System.err.println("Next:"+c.getNext());
//	        	//System.err.println("Scope: "+c.getScope());
//	        }
	        System.out.print(script.debugPrint());
		} catch (Exception e) {
			e.printStackTrace();
		}
	}
	
}
