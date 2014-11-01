<?php 
class search extends Component {
	
	/**
	 * @param string $id
	 * @return SearchPlugin
	 */
	public function getPlugin($id) {
		require_once("component/search/SearchPlugin.inc");
		foreach (PNApplication::$instance->components as $c)
			foreach ($c->getPluginImplementations() as $pi)
				if ($pi instanceof SearchPlugin)
					if ($pi->getId() == $id) return $pi;
		return null;
	}
	
}
?>