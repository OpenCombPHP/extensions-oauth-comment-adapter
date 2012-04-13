<?php
namespace org\opencomb\oauthcommentadapter;

use org\jecat\framework\system\Application;
use net\daichen\oauth\OAuthCommon;
use net\daichen\oauth\Http;
use org\opencomb\userstate\CreateState;
use org\opencomb\oauth\adapter\AuthAdapterException;
use org\opencomb\platform\ext\Extension;
use org\opencomb\oauth\adapter\AdapterManager;
use org\jecat\framework\db\DB;
use org\jecat\framework\auth\IdManager;
use org\jecat\framework\message\Message;
use org\opencomb\coresystem\mvc\controller\Controller ;
use org\jecat\framework\db\sql\StatementFactory;
use org\jecat\framework\db\ExecuteException ;

class PullComment extends Controller
{
	public function createBeanConfig()
	{
		$aOrm = array(
			'model:user' => array(
					'orm' => array(
						'table' => 'coresystem:user' ,
						'hasOne:info' => array(
							'table' => 'coresystem:userinfo' ,
						) ,
						'hasOne:auser' => array(
							'table' => 'oauth:user' ,
							'keys'=>array('uid','suid'),
							'fromkeys'=>'uid',
							'tokeys'=>'uid',
						) ,
						'hasMany:friends'=>array(	//一对多
								'fromkeys'=>'uid',
								'tokeys'=>'to',
								'table'=>'friends:subscription',
								'keys'=>array('from','to'),
						),
				) ,
			) ,
			/**
			 * 用来快速获取，判断认证信息
			 */
			'model:auser' => array(
					'orm' => array(
						'table' => 'oauth:user' ,
						'keys'=>array('uid','suid'),
					) ,
			) ,
			
			'model:checkUid' => array(
					'orm' => array(
						'table' => 'oauth:user' ,
						'keys'=>array('uid','suid'),
					) ,
			) ,
			
			'model:state' => array(
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
			) ,
			'model:stateauther' => array(
					'orm' => array(
							'table' => 'oauth:user' ,
							'keys'=>array('uid','suid'),
					) ,
			) ,
			'model:ocomment' => array(
				'list' => 1,
				'orm' => array(
					'table' => 'oauthcommentadapter:ocomment' ,
					'belongsTo:comment' =>array(
						'table' => 'comment:comment',
						'fromkeys' => 'cid' ,
						'tokeys' => 'cid' ,
					),
				),
			) ,
		) ;
		
		return  $aOrm;
	}
	public function process()
	{
		if(!$this->params->has('tid')){
			$this->messageQueue ()->create ( Message::error, "缺少信息,无法找到评论" );
			return;
		}
		$this->state->load($this->params->get('tid'));
		$aSetting = Application::singleton()->extensions()->extension('comment')->setting() ;
		$nWaitTime = (int)$aSetting->item('/commentFromOtherWeb','commentTime',300) ;
		//有几个网站存在这个state就拉几个网站的评论
		foreach($this->state->child('ostate')->childIterator() as $ostate){
			
			//豆瓣不支持评论接口
			if($ostate['service'] === 'douban.com'){
				continue;
			}
			
			//需要新的评论,结果却没有到该拉评论的时间限制
			if( !$this->params->has('oldcommentes') && ($ostate['pullcommenttime'] + $nWaitTime) > time() )
			{
				continue;
			}
			
			//state的作者信息,包括在本站的和在对方网站上的,目前只有renren需要
			$auther = null ;
			if($ostate['service'] == 'renren.com'){
				$this->stateauther->loadSql('uid = @1 and service = @2' , $this->state['uid'] , $ostate['service']);
				$auther = $this->stateauther;
			}
			
			//取一个用户的认证来拉取评论,挑最早用过的,避免超对方网站限制
			if(!$this->auser->loadSql('service = @1 and valid = @2 and token <> @3' ,$ostate['service'], 1 ,'' )){
				continue;
			}
			try{
				$aPuller = pull\PullerFactory::singleton()->create($ostate['service']);
				$aPuller->setAuserModel($this->auser);
				$aPuller->setOstateModel($ostate);
				$aPuller->setAutherModel($auther);
				
				$aComment = comment\CommentFactory::singleton()->create($ostate['service']) ;
				
// 				$iCount = $aPuller->commentCount() ;
// 				echo '调试信息 : ';
// 				echo __METHOD__,' line:',__LINE__,' ';
// 				echo '共有评论',$iCount,'条<br />';
				
				$iCount = 20 ;
				
				// pullOlder 第一个参数为 null 时，拉取最新评论
				$olderFrom = null ;
				$arrList = $aPuller->pullOlder( $olderFrom ,$iCount);
				$nextfrom = $aPuller->getNextOlderFrom($olderFrom);
				
// 				if($ostate['service'] == 'renren.com'){
// 					echo '<pre>';
// 					var_dump($arrList);
// 					echo "</pre>";
// 				}
				foreach($arrList as $arrComment){
					if($ostate['service'] == 't.qq.com'){
						$arrComment['user']['id'] = $arrComment['name'];
					}
					
					if(is_array($arrComment) ){
						$aComment->setArray($arrComment);
						$this->saveCommentToDb($aComment,$nextfrom,$ostate);
					}else{
						echo '>>>>>>1:';var_dump($arrComment);
					}
				}
				
				
				$tid = $this->params->get('tid');
				$iPage = $this->params->get('comment_paginator');
				if(empty($iPage)){
					$iPage = 1 ;
				}else{
					$iPage = (int)$iPage ;
				}
				
				$iCountPerPage = 10 ;
				
// 				$aCloneCritera = clone $this->modelOcomment->prototype()->criteria() ;
// 				$aCloneCritera->setLimit($iCountPerPage , ($iPage -1)*$iCountPerPage );
// 				$aCloneCritera->addOrderBy('comment.create_time');
				$this->modelOcomment->clearChildren() ;
				$this->modelOcomment->load();
				
				$nextOlderFrom = null ;
				
				$aIter = $this->modelOcomment->childIterator() ;
				$aIter->last();
				if($aIter->valid() && $ss = $aIter->current()){
					$nextOlderFrom = $ss->data('nextOlderFrom');
				
					$arrList = $aPuller->pullOlder($nextOlderFrom,$iCount);
					$nextfrom = $aPuller->getNextOlderFrom($nextOlderFrom);
					foreach($arrList as $arrComment){
						if(is_array($arrComment) ){
							//腾讯返回的用户信息特殊
							if($ostate['service'] == 't.qq.com'){
								$arrComment['user']['id'] = $arrComment['name'];
							}
							if($ostate['service'] == 'renren.com'){
// 								var_dump($arrComment);
							}
							$aComment->setArray($arrComment);
							$this->saveCommentToDb($aComment,$nextfrom,$ostate);
						}else{
							echo '>>>>>>>2:';var_dump($arrComment);
						}
					}
				}
				
			}catch(AuthAdapterException $e){
				$this->createMessage(Message::error,$e->messageSentence(),$e->messageArgvs()) ;
				$this->messageQueue()->display() ;
				return ;
			}
			$this->updateActionTime() ;
		}
	}
	
	/**
	 * 测试用户是否存在，不存在就创建
	 * @param unknown_type $aUserInfo
	 * @param unknown_type $service
	 */
	public function checkUid($aUserInfo,$service)
	{
		if(empty($aUserInfo['username']))
		{
			return false;
		}
		$aId = IdManager::singleton()->currentId() ;
// 		if(!$aId){
// 			return;
// 		}
		$this->checkUid->clearData();
		$this->checkUid->loadSql('service = @1 and suid = @2' , $service , $aUserInfo['username'] );
		
		if( $this->checkUid->isEmpty())
		{
			$this->user->clearData();
			$this->user->setData("username",$service."#".$aUserInfo['username']);
			$this->user->setData("password",md5($service."#".$aUserInfo['username'])) ;
			$this->user->setData("registerTime",time()) ;
		
			$this->user->setData('auser.service',$service);
			$this->user->setData('auser.suid',$aUserInfo['username']);
			$this->user->setData('auser.verified',$aUserInfo['verified']);
		
			$this->user->setData("info.nickname",$aUserInfo['username']);
			$this->user->setData("info.avatar",$aUserInfo['avatar']);
			if($aId){
				$this->user->child("friends")->createChild()->setData("from",$aId->userId());
			}
			$this->user->save() ;
			
			$uid = $this->user->uid;
		}else{
			foreach($this->checkUid->childIterator() as $oAuser){
				$uid = $oAuser->uid;
			}
			if(empty($uid)){
				$uid = $this->checkUid->uid ;
			}
		}
		return $uid;
	}
	
	public function saveCommentToDb(comment\IComment $aComment,$nextfrom,$ostate){
		try {
			// check Comment already in DB
			$tcid = $aComment->tcid() ;
			$tuid = $aComment->tuid() ;
			$service = $aComment->service() ;
			
			$this->modelOcomment->load(
				array(
					$tcid,
					$tuid,
					$service,
				),
				array(
					'tcid',
					'tuid',
					'service',
				)
			);
			
			// 已经有了，直接返回
			if( ! $this->modelOcomment->isEmpty() ){
				return ;
			}
			
			// check userinfo
			$arrUserInfo = array(
				'username' => $aComment->username() ,
				'avatar' => $aComment->avatar() ,
				'verified' => $aComment->verified() ,
			);
			$uid = $this->checkUid($arrUserInfo,$ostate['service']);
			
			// save to db comment
			$tid = $this->params->get('tid');
			
			$aInsert = StatementFactory::singleton() -> createInsert('comment_comment');
			$aInsert->setData('tid',$tid);
			$aInsert->setData('uid',$uid);
			$aInsert->setData('type','userstate');
			$aInsert->setData('content',$aComment->text() );
			$aInsert->setData('create_time',$aComment->create_time() );
			DB::singleton()->execute($aInsert) ;
			
			$cid = DB::singleton()->lastInsertId();
			
			// save to db ocomment
			$aInsert = StatementFactory::singleton() -> createInsert('oauthcommentadapter_ocomment');
			$aInsert->setData('cid',$cid);
			$aInsert->setData('tcid',$aComment->tcid() );
			$aInsert->setData('tuid',$aComment->tuid() );
			$aInsert->setData('tusername',$aComment->username() );
			$aInsert->setData('service',$aComment->service() );
			$aInsert->setData('nextOlderFrom',$nextfrom );
			DB::singleton()->execute($aInsert) ;
			
		} catch ( ExecuteException $e ) {
			echo $e->message (),'<br />';
		}
	}
	
	public function updateActionTime(){
		$this->auser->setData('actiontime',time());
		$this->auser->save() ;
	}
}
