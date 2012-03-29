<?php
namespace org\opencomb\oauthcommentadapter\pull ;

use org\jecat\framework\mvc\model\IModel ;
use org\opencomb\oauth\adapter\AdapterManager;
use net\daichen\oauth\OAuthCommon;

abstract class AbstractPuller implements IPuller{
	public function setAuserModel(IModel $aUser){
		$this->aUser = $aUser ;
	}
	
	public function setOstateModel(IModel $aOstate){
		$this->aOstate = $aOstate ;
	}
	
	public function setAutherModel(IModel $aAuther = null){
		$this->aAuther = $aAuther ;
	}
	
	protected function getService(){
		$sClassName = get_class($this) ;
		$sNsSlash = preg_replace('`\\\\`','\\\\\\\\',__NAMESPACE__);
		if( 0 === preg_match( '`^'.$sNsSlash.'\\\\Puller_(([a-z0-9]*_)*[a-z0-9]*)$`',$sClassName,$arrMatch) ){
			echo __METHOD__,'error';
		}
		
		return str_replace('_','.',$arrMatch[1]);
	}
	
	protected function pull( array $arrParams , $sFunName ){
		$sService = $this->getService() ;
		
		$aAdapter = AdapterManager::singleton()->createApiAdapter($sService) ;
		$aRs = $aAdapter->$sFunName($this->aUser, $this->aOstate,$arrParams , $this->aAuther);
		$OAuthCommon = new OAuthCommon("",  "");
		$aRsT = $OAuthCommon -> multi_exec();
		$arr = json_decode($aRsT[$sService],true);
		
		if( !is_array($arr)){
			var_dump($aRsT);
		}
		
		return $arr ;
	}
	
	protected $aUser = null ;
	protected $aOstate = null ;
	protected $aAuther = null ;
}
