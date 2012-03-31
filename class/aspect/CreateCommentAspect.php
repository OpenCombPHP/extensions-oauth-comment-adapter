<?php
namespace org\opencomb\oauthcommentadapter\aspect ;

use org\jecat\framework\message\Message;
use org\jecat\framework\bean\BeanFactory;
use org\jecat\framework\mvc\model\db\Model;
use org\jecat\framework\lang\aop\jointpoint\JointPointMethodDefine;

class CreateCommentAspect
{
	/**
	 * @pointcut
	 */
	public function pointcutProcess()
	{
		return array(
			new JointPointMethodDefine('org\\opencomb\\comment\\CreateComment','process')
		) ;
	}
	
	/**
	 * @advice around
	 * @for pointcutProcess
	 */
	private function process()
	{
		aop_call_origin() ;
		
		$arrStateBean = array(
				'class' => 'model' ,
				'orm' => array(
						'table' => 'userstate:state' ,
						'keys'=>array('stid'),
						'hasMany:ostate'=>array(
								'fromkeys'=>'stid',
								'tokeys'=>'stid',
								'table'=>'oauth:state',
								'keys'=>array('stid','service'),
						),
			) ,
		);
		$aStateModel = BeanFactory::singleton()->createBean($arrStateBean,'userstate');
		$aStateModel->load(array($this->params->get('tid')),array('stid'));
		
		if($aId = IdManager::singleton()->currentId()){
			$arrOUserBean = array(
					'class'=>'model',
					'list'=> true,
					'orm' => array(
							'table' => 'oauth:user' ,
							'keys'=>array('uid','suid'),
					) ,
			);
			$aOUserModel = BeanFactory::singleton()->createBean($arrOUserBean,'oauth');
			$aOUserModel->load(array( $aId->userId() , '1'),array('uid' , 'valid'));
			
			$arrOUserHasOAuth = array();
			$arrOUserHasOAuthModel = array();
			foreach($aOUserModel as $aOUser){
				$arrOUserHasOAuth[] = $aOUser['service'];
				$arrOUserHasOAuthModel[$aOUser['service']] = array('token'=>$aOUser->token, 'token_secret'=>$aOUser->token_secret);
			}
			
			//获得此微博在对应网站上的id
			$arrTid = array();
			foreach($aStateModel->child('ostate')->childIterator() as $ostate){
				if( !in_array( $ostate['service'] , $arrOUserHasOAuth) ){
					continue;
				}
				
				$arrTid[$ostate['service']] = $ostate['sid'];
				switch($ostate['service']) {
					case 'sohu.com' :
						$this->commentView->variables()->set('sohuCheckable',true) ;
						break;
					case '163.com' :
						$this->commentView->variables()->set('wyCheckable',true) ;
						break;
					case 'renren.com' :
						$this->commentView->variables()->set('renrenCheckable',true) ;
						break;
					case 't.qq.com' :
						$this->commentView->variables()->set('qqCheckable',true) ;
						break;
					case 'weibo.com' :
						$this->commentView->variables()->set('weiboCheckable',true) ;
						break;
				}
			}
		}
		
		if($this->commentView->isSubmit ( $this->params )){
			//原createcomment的数据是否保存成功,不成功就没有保存任何其他数据的必要
			if(!$this->modelComment->hasSerialized()){
				return;
			}
			
			//如果不需要同步到其他网站就退出
			if(!$arrSendTo = $this->params->get('sendto')){
				return;
			}
			
			//$this->modelComment;
			$arrOCommentBean = array(
					'class'=>'model',
					'orm' => array(
							'table' => 'oauthcommentadapter:ocomment' ,
					) ,
			);
			$aOCommentModel = BeanFactory::singleton()->createBean($arrOCommentBean,'oauthcommentadapter');
			
			if($this->params->has('pid') && $this->params->get('pid') !== '0'){
				$arrOPCommentBean = array(
						'class'=>'model',
						'list'=>true,
						'orm' => array(
								'table' => 'oauthcommentadapter:ocomment' ,
						) ,
				);
				$aOPCommentModel = BeanFactory::singleton()->createBean($arrOPCommentBean,'oauthcommentadapter');
				$aOPCommentModel->load( array($this->params->get('pid') ,array('cid' ) ));
				$arrOPcomment = array();
				foreach($aOPCommentModel->childIterator() as $aPComment){
					$arrOPcomment[$aPComment['service']] = $aPComment;
				}
			}
			
			foreach($arrSendTo as $sSendTo){
				if($sSendTo == 'weibo.com'){
					$arrOtherParams = array('id'=> $arrTid[$sSendTo] , 'comment'=> urlencode($this->modelComment['content']));
					if( isset($arrOPcomment[$sSendTo])){
						$arrOtherParams['cid'] = $arrOPcomment[$sSendTo]->data('tcid');
					}
				}else if($sSendTo == 'sohu.com'){
					$arrOtherParams = array('id'=> $arrTid[$sSendTo] , 'comment'=> urlencode($this->modelComment['content']));
				}else if($sSendTo == '163.com'){
					$arrOtherParams = array('id'=> $arrTid[$sSendTo] , 'status'=> $this->modelComment['content']);
				}else if($sSendTo == 't.qq.com'){
					$arrOtherParams = array('reid'=> $arrTid[$sSendTo] , 'content'=> $this->modelComment['content']);
				}else if($sSendTo == 'renren.com'){
					//作者在service网站上的用户信息
					$arrRenrenUserBean = array(
							'class'=>'model',
							'orm' => array(
									'table' => 'oauth:user' ,
									'keys'=>array('uid','suid'),
							) ,
					);
					$aRenrenUserModel = BeanFactory::singleton()->createBean($arrRenrenUserBean,'oauth');
					$aRenrenUserModel->load(array( $aStateModel['uid'] ,'renren.com', '1'),array('uid'  , 'service' , 'valid'));
					
					if(!$aRenrenUserModel){
						continue;
					}
// 					$aRenrenUserModel->printStruct();
					
					$arrOtherParams = array(
							'status_id'=> $arrTid[$sSendTo] , 
							'owner_id'=> $aRenrenUserModel['suid'] ,
							'content'=> $this->modelComment['content'],
							);
				}
				
// 				var_dump($arrOtherParams);
				
			try{
					$aAdapter = \org\opencomb\oauth\adapter\AdapterManager::singleton()->createApiAdapter($sSendTo) ;
					$aRs = @$aAdapter->pushCommentMulti($arrOUserHasOAuthModel[$sSendTo], $this->modelComment , $arrOtherParams);
				}catch(\org\opencomb\oauth\adapter\AuthAdapterException $e){
// 					var_dump($e->messageSentence());
// 					$this->createMessage(Message::error,$e->messageSentence(),$e->messageArgvs()) ;
					$this->messageQueue()->display() ;
					continue ;
				}
				
				$OAuthCommon = new \net\daichen\oauth\OAuthCommon("",  "");
				$aRsT = $OAuthCommon -> multi_exec();
// 				var_dump($aRsT);
				if($sSendTo !== 'renren.com'){
// 					var_dump(json_decode($aRsT[$sSendTo] , true));
					$arrReport = json_decode($aRsT[$sSendTo] , true);
				}
				
				if($sSendTo == 'weibo.com'){
					$sReportId = $arrReport['id'];
					$sReportUserId = $arrReport['user']['id'];
					$sReportUsername = $arrReport['user']['name'];
				}else if($sSendTo == 'sohu.com'){
					$sReportId = $arrReport['id'];
					$sReportUserId = $arrReport['user']['id'];
					$sReportUsername = $arrReport['user']['screen_name'];
				}else if($sSendTo == '163.com'){
					$sReportId = $arrReport['id'];
					$sReportUserId = $arrReport['user']['id'];
					$sReportUsername = $arrReport['user']['name'];
				}else if($sSendTo == 't.qq.com'){
					$sReportId = $arrReport['data']['id'];
					$sReportUserId = $arrOUserHasOAuthModel[$sSendTo]['suid'];   //腾讯上帐号和name通用
					$sReportUsername = $arrOUserHasOAuthModel[$sSendTo]['suid']; //腾讯上帐号和name通用
				}else if($sSendTo == 'renren.com'){
					try{
						$aAdapter = \org\opencomb\oauth\adapter\AdapterManager::singleton()->createApiAdapter($sSendTo) ;
						$aRs = @$aAdapter->createPullCommentMulti(
								$arrOUserHasOAuthModel[$sSendTo], 
								array('sid'=>$arrTid[$sSendTo]) ,
								array(),
								array( 'suid'=>$aRenrenUserModel['suid'] )
							);
					}catch(\org\opencomb\oauth\adapter\AuthAdapterException $e){
						$this->messageQueue()->display() ;
						continue ;
					}
					
					$OAuthCommon = new \net\daichen\oauth\OAuthCommon("",  "");
					$aRsT = $OAuthCommon -> multi_exec();
					$arrReport = json_decode($aRsT[$sSendTo] , true);
					
					foreach($arrReport as $arrOneReport){
						if($arrOneReport['text'] === $arrOtherParams['content']){
							$sReportId = $arrOneReport['comment_id'];
							$sReportUserId = $arrOneReport['uid'];
							$sReportUsername = $arrOneReport['name'];
							break;
						}
					}
				}
				
				//保存到自己的数据库
				$aOCommentModel->setData('cid' , $this->modelComment['cid'] );
				$aOCommentModel->setData('service' , $sSendTo );
				$aOCommentModel->setData('tcid' , $sReportId );
				$aOCommentModel->setData('tuid' , $sReportUserId );
				$aOCommentModel->setData('tusername' , $sReportUsername );
				
				if (!$aOCommentModel->save ())
				{
					continue;
				}
				$aOCommentModel->printStruct();
			}
		}else{
			if(!IdManager::singleton()->currentId()){
				
			}
		}
	}
}