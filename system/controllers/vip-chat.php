<?php

/*$db2->query('
CREATE TABLE IF NOT EXISTS `users_vip_chat` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `date` varchar(128) COLLATE utf8_persian_ci NOT NULL,
  `next_date` varchar(128) COLLATE utf8_persian_ci NOT NULL,
  `baste` int(2) NOT NULL DEFAULT "0",
  `trak` varchar(128) COLLATE utf8_persian_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_persian_ci AUTO_INCREMENT=1 ;');
*/
	$this->load_langfile('inside/global.php');

	$this->load_langfile('outside/contacts.php');

	



	$D->page_title	= 'کاربر ویژه چت-'.$C->SITE_TITLE;

	if( !$this->network->id ) {
		$this->redirect('home');
	}
	if( !$this->user->is_logged ) {
		$this->redirect('home');
	}

	$D->submit	= FALSE;

	$D->error	= FALSE;

	$D->errmsg	= '';
	$D->sabad=false;
	$D->pay=false;
	$D->baste=array();


	include_once('helpers/nusoap.php');
	
	$D->tabs = array('sabad','submited');
	if(!in_array($this->param('tab'),$D->tabs)){
$this->redirect('dashboard');
	}
if($this->network->get_vip_chat($this->user->info->id)){
$D->error = true;
	$D->errmsg	= 'قبلا دوره ای را پرداخت کردید';
	$this->load_template('vip-chat.php');
	exit;

}
	if(isset($_POST['select']) && isset($_POST['what']) && $this->param('tab') == "sabad"){
	

	$D->sabad=true;
	 $a= intval($_POST['what']);
    $it = 'VIP_CHAT_'.$a;
	if(!$it){
	$D->error	= true;
	$D->errmsg	= 'خطا در بسته انتخابی';
	$this->load_template('vip-chat.php');
	exit;
	}
	if(!$D->error){
$D->baste= array( 'amount'=> $C->$it ,'pri'=>$a ) ;
$this->user->sess['PAY_VIP_CHAT'] = $D->baste;
///////////////////////
$MerchantID = 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx';
$Amount = ($C->$it); //Amount will be based on Toman  - Required
$Description = 'کاربر ویژه چت - '.$C->SITE_TITLE;  // Required
$Email = $this->user->info->email; // Optional
$Mobile =''; // Optional
$CallbackURL = $C->SITE_URL.'vip-chat/tab:submited/'; // Required
	
	
	// URL also Can be https://ir.zarinpal.com/pg/services/WebGate/wsdl
	$client = new nusoap_client('https://de.zarinpal.com/pg/services/WebGate/wsdl', 'wsdl'); 
	$client->soap_defencoding = 'UTF-8';
	$result = $client->call('PaymentRequest', array(
													array(
															'MerchantID' 	=> $MerchantID,
															'Amount' 		=> $Amount,
															'Description' 	=> $Description,
															'Email' 		=> $Email,
															'Mobile' 		=> $Mobile,
															'CallbackURL' 	=> $CallbackURL
														)
													)
	);
//////////////////////////////////////////

if($result['Status'] == 100)
	{
$this->user->sess['VIP_CHAT_ALLOW_SESSION'] = $result['Authority'];
$this->user->sess[$result['Authority']] = $this->user->sess['PAY_VIP_CHAT'];
unset($this->user->sess['PAY_ADD_ZARIN']);
$this->redirect('https://www.zarinpal.com/pg/StartPay/'.$result['Authority']);
		
	} else {
		$D->error = true;
		$D->errmsg = 'خطای شماره '.$result['Status'];
		$this->load_template('vip-chat.php');
		exit;
	}

}
	}

	
elseif(isset($_GET['Authority']) && isset($this->user->sess['VIP_CHAT_ALLOW_SESSION']) && $_GET['Authority'] == $this->user->sess['VIP_CHAT_ALLOW_SESSION'] && $tab="submited"){
	$MerchantID = 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx';
 $Authority = $this->user->sess['VIP_CHAT_ALLOW_SESSION'];
 $au =$Authority;
$basteha = $this->user->sess[$au];
$Amount = $amount =	$basteha['amount'];
$pri = 	$basteha['pri'];


 $trak = 	'';//$_GET['refID'];








$client = new nusoap_client('https://de.zarinpal.com/pg/services/WebGate/wsdl', 'wsdl'); 
		$client->soap_defencoding = 'UTF-8';
		$result = $client->call('PaymentVerification', array(
															array(
																	'MerchantID'	 => $MerchantID,
																	'Authority' 	 => $Authority,
																	'Amount'	 	 => $Amount
																)
															)
		);
		
		



if(trim($result['Status']) !== '100'){
		$D->error = true;
		$D->errmsg = 'خطای شماره '.$result['Status'];
		$this->load_template('vip-chat.php');
		exit;
		}



$D->trak = $result['RefID'];









if(!$D->error){

$time= time();
$priud = $pri * 60 * 60 * 24 * 30;
$next_time = $time+$priud;
$db2->query('INSERT INTO users_vip_chat SET baste="'.$pri.'",user_id="'.$this->user->id.'", date="'.$time.'",next_date="'.$next_time.'", trak="'.$D->trak.'"');
unset($this->user->sess[$au]);
unset($this->user->sess['VIP_CHAT_ALLOW_SESSION']);

$D->submit = true;
}else{
$this->load_template('vip-chat.php');
exit;
}
}else{

	$D->error	= true;
	$D->errmsg	= 'صفحه در دسترس نیست';
$this->redirect('dashboard');
exit;
}
	
	$this->load_template('vip-chat.php');
	

?>