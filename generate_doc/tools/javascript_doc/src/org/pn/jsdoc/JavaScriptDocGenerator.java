package org.pn.jsdoc;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.IOException;

import org.mozilla.javascript.CompilerEnvirons;
import org.mozilla.javascript.Parser;
import org.mozilla.javascript.ast.AstRoot;
import org.pn.jsdoc.model.Global;

public class JavaScriptDocGenerator {

	public static void main(String[] args) {
		try {
	        Global global = new Global();
	        File www = new File(args[0]);
			File component = new File(www, "component");
			for (File comp : component.listFiles()) {
				if (!comp.isDirectory()) continue;
				File stati = new File(comp, "static");
				if (!stati.exists()) continue;
				browse(stati, global, comp.getName(), "component/"+comp.getName()+"/");
			}
			global.evaluate();
			File out = new File(args[1]);
			out.delete();
			out.createNewFile();
			FileOutputStream fout = new FileOutputStream(out);
			fout.write("var jsdoc = ".getBytes());
			fout.write(global.generate("  ").getBytes());
			fout.write(";".getBytes());
			fout.close();
		} catch (Exception e) {
			e.printStackTrace();
		}
	}
	
	private static void browse(File dir, Global global, String component_name, String path) throws IOException {
		for (File f : dir.listFiles()) {
			if (f.isDirectory()) {
				browse(f, global, component_name, path+f.getName()+"/");
				continue;
			}
			String filename = f.getName();
			if (!filename.endsWith(".js")) continue;
			System.out.println("Analyzing "+path+f.getName());
			FileInputStream in = new FileInputStream(f);
			byte[] buf = new byte[100000];
			int nb = in.read(buf);
			in.close();
			String src = new String(buf,0,nb);
			
		    CompilerEnvirons environment = new CompilerEnvirons();
	        environment.setRecordingComments(true);
	        environment.setRecordingLocalJsDocComments(true);
			Parser p = new Parser(environment);
	        AstRoot script = p.parse(src, null, 0);
	        global.parse(path+f.getName(), script);
		}
	}
	
}
