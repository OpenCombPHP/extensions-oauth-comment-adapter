<?php
namespace org\opencomb\oauthcommentadapter\pull ;

use org\jecat\framework\lang\Object ;
use org\opencomb\oauth\adapter\AuthAdapterException;

class PullerFactory extends Object{
	/**
	 * @param $sService string 服务名字
	 *     如 weibo.com
	 * @return IPuller
	 */
	public function create($sService){
		$sServiceClass = str_replace('.','_',$sService) ;
		
		$sClassName = __NAMESPACE__.'\\Puller_'.$sServiceClass;
		
		if( class_exists( $sClassName ) ){
			return new $sClassName ;
		}else{
			throw new AuthAdapterException('未实现的service:%s',$sService);
			return null ;
		}
	}
}
