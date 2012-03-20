<?php
namespace org\opencomb\oauthcommentadapter\pull ;

use org\opencomb\oauth\adapter\AdapterManager;
use net\daichen\oauth\OAuthCommon;

/*
	page 从 1 开始计数，并且第1页是最新的
	每页 10 个，似乎没有办法修改
*/
class Puller_sohu_com extends AbstractPuller{
	const NUM_PER_PAGE = 10 ;
	public function commentCount(){
		if( $this->iComments_count < 0 ){
			$arr = $this->pull( array() , 'createPullCommentCount' ) ;
			
			$this->iComments_count = (int)$arr['comments_count'] ;
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
			$arrOlder = array() ;
		}
		
		return $arrOlder ;
	}
	
	public function getNextOlderFrom($iFrom){
		if(empty($iFrom)){
			return 2 ;
		}else{
			return $iFrom + 1 ;
		}
	}
	
	private $iComments_count = -1 ;
}
