<?php
namespace org\opencomb\oauthcommentadapter\pull ;

use org\opencomb\oauth\adapter\AdapterManager;
use net\daichen\oauth\OAuthCommon;

class Puller_163_com extends AbstractPuller{
	public function commentCount(){
		if( $this->iComments_count < 0 ){
			$arr = $this->pull( array() , 'createPullCommentCount' ) ;
			$this->iComments_count = (int)$arr['comments_count'] ;
		}
		return $this->iComments_count ;
	}
	
	public function pullOlder($iFrom,$iCount){
		$arrParams = array(
			'since_id' => $iFrom ,
			'count' => $iCount ,
		);
		$arr = $this->pull( $arrParams , 'createPullCommentMulti' ) ;
		
		if(is_array($arr)){
			$arrOlder = $arr ;
		}else{
			$arrOlder = array() ;
		}
		
		$arrLast = end($arrOlder) ;
		$iLastId = $arrLast['id'] ;
		$this->arrNextOlderFrom[$iFrom] = $iLastId ;
		
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
