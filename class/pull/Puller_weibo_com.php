<?php
namespace org\opencomb\oauthcommentadapter\pull ;

use org\opencomb\oauth\adapter\AdapterManager;
use net\daichen\oauth\OAuthCommon;

class Puller_weibo_com extends AbstractPuller{
	public function commentCount(){
		if( $this->iComments_count < 0 ){
			$arr = $this->pull( array() , 'createPullCommentCount' ) ;
			$this->iComments_count = (int)$arr[0]['comments'] ;
		}
		return $this->iComments_count ;
	}
	
	public function pullOlder($iFrom,$iCount){
		$arrParams = array(
			'count' => $iCount ,
		);
		if(!empty($iFrom)){
			$arrParams['page'] = $iFrom ;
		}
		$arr = $this->pull( $arrParams , 'createPullCommentMulti' ) ;
		
		if(is_array($arr)){
			$arrOlder = $arr ;
		}else{
			var_dump($arr);
			$arrOlder = array() ;
		}
		return $arrOlder ;
	}
	
	public function getNextOlderFrom($iFrom){
		if(empty($iFrom)){
			$iFrom = 1 ;
		}
		return $iFrom +1 ;
	}
	
	private $iComments_count = -1 ;
}
