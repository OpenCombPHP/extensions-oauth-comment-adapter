<?php
namespace org\opencomb\oauthcommentadapter\pull ;

use org\opencomb\oauth\adapter\AdapterManager;
use net\daichen\oauth\OAuthCommon;
use org\opencomb\oauth\adapter\AuthAdapterException;

class Puller_t_qq_com extends AbstractPuller{
	public function commentCount(){
		if( $this->iComments_count < 0 ){
			$arr = $this->pull( array() , 'createPullCommentCount' ) ;
			if($arr['errcode']){
				throw new AuthAdapterException('拉取评论总数时发生错误：%s',$arr['msg']);
			}
			$id = $this->aOstate['sid'] ;
			if(!empty($arr['data'][$id]))$this->iComments_count = (int)($arr['data'][$id]);
		}
		return $this->iComments_count ;
	}
	
	public function pullOlder($iFrom,$iCount){
		if($this->commentCount() <= 0 ) return array() ;
		if(empty($iFrom)){
			$arrParams = array();
		}else{
			$arrParams = array(
				'pageflag' => 1 ,
				'pagetime' => $iFrom ,
			);
		}
		$arrParams['reqnum'] = $iCount ;
		
		$arr = $this->pull( $arrParams , 'createPullCommentMulti' ) ;
		$arr = $arr['data']['info'] ;
		
		if(is_array($arr)){
			$arrOlder = $arr ;
		}else{
			$arrOlder = array() ;
		}
		
		if(!empty($arrOlder)){
			$arrLast = end($arrOlder) ;
			$iLastId = $arrLast['timestamp'] ;
			$this->arrNextOlderFrom[$iFrom] = $iLastId ;
		}
		
		return $arrOlder ;
	}
	
	public function getNextOlderFrom($iFrom){
		if(isset($this->arrNextOlderFrom[$iFrom])){
			return $this->arrNextOlderFrom[$iFrom] ;
		}else{
			return '';
		}
	}
	
	private $iComments_count = -1 ;
	
	private $arrNextOlderFrom = array () ;
}
