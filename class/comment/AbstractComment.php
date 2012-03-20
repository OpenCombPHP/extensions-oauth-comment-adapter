<?php
namespace org\opencomb\oauthcommentadapter\comment ;

abstract class AbstractComment implements IComment{
	public function toHtml(){
		$img = $this->img() ;
		$name = $this->name() ;
		$text = $this->text() ;
		$time = $this->time() ;
		$sHtml = <<<HTML
<li>
	<img src="$img" />
	$name
	:
	$text
	<br />
	$time
</li>
HTML;
'<li>';
		return $sHtml ;
	}
	
	public function setArray(array $arr){
		$this->arr = $arr ;
	}
	
	protected function img(){
		return $this->arr['user']['profile_image_url'] ;
	}
	
	protected function name(){
		return $this->arr['user']['screen_name'] ;
	}
	
	public function text(){
		return $this->arr['text'] ;
	}
	
	public function tcid(){
		return $this->arr['id'] ;
	}
	
	public function tuid(){
		return $this->arr['user']['id'] ;
	}
	
	protected function time(){
		return $this->arr['created_at'] ;
	}
	
	public function service(){
		$sClassName = get_class($this) ;
		$sNsSlash = preg_replace('`\\\\`','\\\\\\\\',__NAMESPACE__);
		if( 0 === preg_match( '`^'.$sNsSlash.'\\\\Comment_(([a-z0-9]*_)*[a-z0-9]*)$`',$sClassName,$arrMatch) ){
			echo __METHOD__,'error';
		}
		
		return str_replace('_','.',$arrMatch[1]);
	}
	
	protected $arr = array() ;
}
