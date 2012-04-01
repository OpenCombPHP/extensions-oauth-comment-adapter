<?php
namespace org\opencomb\oauthcommentadapter\comment ;

class Comment_renren_com extends AbstractComment{
	public function username(){
		return $this->arr['name'] ;
	}
	
	public function avatar(){
		return $this->arr['tinyurl'] ;
	}
	
	public function create_time(){
		$sTime = $this->arr['time'] ;
		
		return strtotime($sTime);
	}
	
	public function tcid(){
		if(isset($this->arr['comment_id'])){
			return $this->arr['comment_id'] ;
		}else{
			return 0;
		}
	}
	
	public function tuid(){
		return $this->arr['uid'];
	}
	
	public function verified(){
		return $this->arr['verified'] ;
	}
}
