<?php

namespace IconicCodes\LightView;

use Parsedown;
use TinyHTML;

class LightView {

	private $blocks = array();
	public  $cache_path = 'cache/pages/';
	public  $cache_enabled = false;
	public  $views_path  = __DIR__ . "/app/";
	public  $includes_path = __DIR__ . "/app/includes/";

	private static $_instance = null;
	
	public static function getInstance($callback) {
		if (self::$_instance == null) {
			self::$_instance = new self;
			$callback(self::$_instance);
		}
		return self::$_instance;
	}


	function view($file, $data = array()) {
		$cached_file = $this->cache($file);
		extract($data, EXTR_SKIP);
		include $cached_file;
	}

	function cache($file) {
		if (!file_exists($this->cache_path)) {
			mkdir($this->cache_path, 0744);
		}
		$cached_file = $this->cache_path . str_replace(array('/', '.html'), array('_', ''), $file . '.php');
		if (!$this->cache_enabled || !file_exists($cached_file) || filemtime($cached_file) < filemtime($this->views_path . $file)) {
			$code = $this->includeFiles($file, false, null, true);
			$code = $this->compileCode($code);
			include_once "./class/TinyHTML.php";
			$minify = new TinyHTML([]);
			$code = $minify->minify($code);
			file_put_contents($cached_file, '<?php class_exists(\'' . __CLASS__ . '\') or exit; ?>' . PHP_EOL . $code);
		}
		return $cached_file;
	}

	function clearCache() {
		foreach (glob($this->cache_path . '*') as $file) {
			unlink($file);
		}
	}

	function compileCode($code) {
		$code = $this->compileBlockMarkdown($code);
		// $code = $this->compileYieldMarkdown($code);
		$code = $this->compileBlock($code);
		$code = $this->compileYield($code);
		$code = $this->compileSkipEchosp1($code);
		$code = $this->compileEscapedEchos($code);
		$code = $this->compileEchos($code);
		$code = $this->compileMD($code);
		$code = $this->compilePHP($code);
		$code = $this->compileReplaceCodeBlack($code);
		$code = $this->compileSkipEchosp2($code);
		return $code;
	}

	function includeFiles($file, $is_included = false, $customLayoutPath = null, $first_run = false) {
		$filename = ($is_included ? ($customLayoutPath ?? $this->includes_path) : $this->views_path) . $file;
		$code = file_get_contents($filename);
		if ($first_run == true) {
			$layout = $this->findClosestLayoutFile(dirname($this->views_path . $file));
			$code = "{% layout $layout %}" . $code;
		}

		preg_match_all('/{% ?(layout|include) ?\'?(.*?)\'? ?%}/i', $code, $matches, PREG_SET_ORDER);
		foreach ($matches as $value) {
			$code = str_replace($value[0], $this->includeFiles($value[2], true, ($value[1] == 'layout' ? '' : $this->includes_path), false), $code);
		}
		$code = preg_replace('/{% ?(layout|include) ?\'?(.*?)\'? ?%}/i', '', $code);
		return $code;
	}

	function findClosestLayoutFile($directory) {
		while ($directory !== '/') {
			$layoutPath = $directory . '/__layout.html';
	
			if (file_exists($layoutPath)) {
				return $layoutPath;
			}
	
			$directory = dirname($directory);
		}
	
		return null;
	}


	function compileMD($code) {
		preg_match_all('/{% ?(markdown) ?\'?(.*?)\'? ?%}/i', $code, $matches, PREG_SET_ORDER);
		include_once "./class/Parsedown.php";
		$parser = new Parsedown();
		foreach ($matches as $value) {
			$code = str_replace($value[0], $parser->text(file_get_contents($value[2])) , $code);
		}
		$code = preg_replace('/{% ?(markdown) ?\'?(.*?)\'? ?%}/i', '', $code);
		
		return $code;
	}

	function compilePHP($code) {
		return preg_replace('~\{%\s*(.+?)\s*\%}~is', '<?php $1 ?>', $code);
	}


	function compileEchos($code) {
		return preg_replace('~\{!\s*(.+?)\s*\!}~is', '<?php echo $1 ?>', $code);
	}

	function compileSkipEchosp1($code) {
		return preg_replace('~\@{{\s*(.+?)\s*\}}~is', '@<< $1 >>@', $code);
	}

	function compileSkipEchosp2($code) {
		return preg_replace('~\@<<\s*(.+?)\s*\>>@~is', '{{ $1 }}', $code);
	}

	function compileEscapedEchos($code) {
		return preg_replace('~\{{\s*(.+?)\s*\}}~is', '<?php echo htmlentities($1, ENT_QUOTES, \'UTF-8\') ?>', $code);
	}

	function compileBlock($code) {
		preg_match_all('/{% ?block ?(.*?) ?%}(.*?){% ?endblock ?%}/is', $code, $matches, PREG_SET_ORDER);
		foreach ($matches as $value) {
			if (!array_key_exists($value[1], $this->blocks)) $this->blocks[$value[1]] = '';
			if (strpos($value[2], '@parent') === false) {
				$this->blocks[$value[1]] = $value[2];
			} else {
				$this->blocks[$value[1]] = str_replace('@parent', $this->blocks[$value[1]], $value[2]);
			}
			$code = str_replace($value[0], '', $code);
		}
		return $code;
	}
	function compileBlockMarkdown($code) {
		preg_match_all('/{% ?markdown ?(.*?) ?%}(.*?){% ?endmarkdown ?%}/is', $code, $matches, PREG_SET_ORDER);
		include_once "./class/Parsedown.php";
		$parser = new Parsedown();
		foreach ($matches as $value) {
			if (!array_key_exists($value[1], $this->blocks)) $this->blocks[$value[1]] = '';
			if (strpos($value[2], '@parent') === false) {
				$this->blocks[$value[1]] = $parser->text($value[2]);
			} else {
				// $parser = new Parsedown;
				$this->blocks[$value[1]] = str_replace('@parent', $this->blocks[$value[1]], $value[2]);
			}
			$code = str_replace($value[0], '', $code);
		}
		return $code;
	}

	function compileReplaceCodeBlack($code) {
		preg_match_all('/{\( (.*?) \)}/is', $code, $matches, PREG_SET_ORDER);
		foreach ($matches as $value) {
			$Function = explode(',', $value[1]);
			$data = call_user_func($Function[0], $Function);
			$code = str_replace($value[0], $data, $code);
		}

		return $code;
	}
	function compileYield($code) {
		foreach ($this->blocks as $block => $value) {
			$code = preg_replace('/{% ?yield ?' . $block . ' ?%}/', $value, $code);
		}
		$code = preg_replace('/{% ?yield ?(.*?) ?%}/i', '', $code);
		return $code;
	}
	function compileYieldMarkdown($code) {
		foreach ($this->blocks as $block => $value) {
			$code = preg_replace('/{% ?yieldMarkdown ?' . $block . ' ?%}/', $value, $code);
		}
		$code = preg_replace('/{% ?yieldMarkdown ?(.*?) ?%}/i', '', $code);
		return $code;
	}
}
