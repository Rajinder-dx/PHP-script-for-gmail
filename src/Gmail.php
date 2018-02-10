<?php 
namespace GrabGmail;
use GrabGmail\Mime;

class Gmail extends Mime{

	private $email; // String: Email Address.
	private $password; // String: Gmail Password.
	private $from; // String: Fetch Email from matching email address in "From" field.
	private $to; // String: Fetch Email from matching email address in "To" field.	
	private $email_number; // Array: Assign Gamil Message Number to this array if you want to skip some of the emails.


	/**
     * set Data member values
     *
     * @param string $property
     * @param string $value
     */
	public function __set($property, $value){		

		if(property_exists($this, $property)){

			$this->$property = $value;
		}else{
			echo "{$property} doesn't exist!";
		}
	}


	/**
     * Get Emails from Gmail Account
     *
     * @param Array $email_number
     * @param string $value
     * @return Array
     */
	public function getMessages($email_number) {				

		
		$this->email_number = $email_number;

		$server = '{imap.gmail.com:993/imap/ssl/novalidate-cert}';									
		$connection = @imap_open($server, $this->email, $this->password);	

		$error = imap_errors();

		if(isset($error[0])){
			return "fail";
		}		
		if($connection){
			
			$data = $this->getemails($connection, $server);							
			return $data;
		}		
	}
	
	
	/**
     * Get Emails from Gmail Account
     *
     * @param Object $connection
     * @param Object $server
     * @return Array
     */
	function getemails($connection, $server){
		

		if($this->to == null){
			$emails = imap_search($connection,'FROM "'.$this->from.'"');
		}else{
			$emails = imap_search($connection,'TO "'.$this->to.'"');
		}
		
		$messages = [];
		$response = []; 				
		if(is_array($emails)){

			$number=1;			
			$lmt=0;
			foreach( array_reverse($emails) as $email_number){	
									
				$overview = imap_fetch_overview($connection,$email_number,0);			
				$output=$this->mail_mime_to_array($connection,$email_number,$parse_headers=false);

				$body=html_entity_decode($this->getfinalmessage($output));		
				if(empty($body)){
					$body = trim(substr(imap_body($connection, $email_number), 0, 10000));
				}
				$body=html_entity_decode(preg_replace("/[^[:alnum:][:punct:]]/"," ",$body));
				$this->data['Message']['body']=$body;
			
				$str = @$overview[0]->from;
				if(preg_match('/>/',$str)){

					$pattern = array( '/</', '/>/'); 
					$replace = array( ' ', ' '); 
					$temp=preg_replace($pattern, $replace, $str); 
					$arr=(explode(" ",trim($temp)));
					$last=count($arr)-1;
					$lastname=count($arr)-2;
					$middelname=count($arr)-3;
					$firstname=count($arr)-4;
					$email_from= $arr[$last];
					$email_from_name=@$arr[$firstname].'&nbsp;'.@$arr[$middelname].'&nbsp;'.@$arr[$lastname];

				}else{

					$email_from =$str;   
					$email_from_name=$str;
				}
				$subj = $overview[0]->subject;

				$response['subject']=html_entity_decode($subj);
				$response['read']=$overview[0]->seen;
				$response['msgno']=$overview[0]->msgno;
				$response['cc'] =$this->getCc($connection,$email_number);
				$response['bcc'] = $this->getBcc($connection,$email_number);

				$str=@$overview[0]->to;
				if(preg_match('/>/',$str)){
					$pattern = array( '/</', '/>/'); 
					$replace = array( ' ', ' '); 
					$temp=preg_replace($pattern, $replace, $str); 
					$arr=(explode(" ",trim($temp)));
					$last=count($arr)-1;
					$email_to= $arr[$last];
				}else{
					$email_to=$str;
				}

				$response['from']=$email_from;
				$response['from_name']=$email_from_name;
				$response['to']=$email_to;  							
				$response['date'] = @$overview[0]->date;		

				
				/* Set email limit for 50 email. this is increasable*/
				if($lmt==50) {
					break;
				}
				
				if(!in_array($response['msgno'], $this->email_number)){

					$response['files'] = $this->get_imap_attachement(false, $connection, $email_number);
					$messages[] =  $this->data; 
					$lmt++;
				}				
			}
		}
		return $messages;
	}


	/**
     * Get Email's from Attachments
     *
     * @param boolean $delete_emails
     * @param Object $mbox
     * @param Int $number
     * @return Array
     */
	function get_imap_attachement($delete_emails=false, $mbox, $number) { 

		$find=false;
		$message = array();
		$message["attachment"]["type"][0] = "text";
		$message["attachment"]["type"][1] = "multipart";
		$message["attachment"]["type"][2] = "message";
		$message["attachment"]["type"][3] = "application";
		$message["attachment"]["type"][4] = "audio";
		$message["attachment"]["type"][5] = "image";
		$message["attachment"]["type"][6] = "video";
		$message["attachment"]["type"][7] = "other";		
		$structure = imap_fetchstructure($mbox, $number ); 
		$files = array(); 

		if(isset($structure->parts)) {

			$parts = $structure->parts;
			$fpos=2;

			for($i = 1; $i < count($parts); $i++) {
				$tmp=((array)$parts[$i]);
				$message["pid"][$i] = ($i);
				$part = $parts[$i];		
				if(@$tmp['disposition'] == "attachment" || @$tmp['disposition'] == "ATTACHMENT") {
		
					$savedirpath = "../files";
					$filename="";
					$mege = "";
					$data = "";		     		
					$message["type"][$i] = $message["attachment"]["type"][$part->type] . "/" . strtolower($part->subtype);
					$message["subtype"][$i] = strtolower($part->subtype);
					$ext=$part->subtype;
					$params = $part->dparameters;
					$filename = $part->dparameters[0]->value;					
					$filename = $number . '-' .$filename;
					$mege = imap_fetchbody($mbox,$number,$fpos);  
					$uploadfile = $savedirpath.$filename;							
					$files[] = $savedirpath.$filename;					
					$fp = fopen($savedirpath.$filename,"w");
					$data = $this->getdecodevalue($mege, $part->type);	
					fputs($fp,$data);
					fclose($fp);
					$fpos+= 1;
					$find = true;					
				} 
			}	
		} 
		return 	$files;
	}


	/**
     * Get CC Email address 
     *
     * @param Object $connection
     * @param Int $mid
     * @return String
     */
	function getCc($connection, $mid){

		$data="";
		$overview =imap_headerinfo($connection,$mid);
		foreach((array)$overview as $k =>$v){
			$header[$k]=$v;
		}
		
		$cc=@(array)$header['cc'];
		foreach($cc as $value){
			$mailbox=((array)$value);
			$host=((array)$value);
			$Cc[]=$mailbox['mailbox'].'@'.$host['host'];
		}
		$data=@(implode(", ",$Cc));
		return $data;
	}


	/**
     * Get BCC Email address 
     *
     * @param Object $connection
     * @param Int $mid
     * @return String
     */
	function getBcc($connection, $mid){

		$data="";
		$overview =imap_headerinfo($connection,$mid);
		foreach((array)$overview as $k =>$v){
			$header[$k]=$v;
		}
		
		$cc=@(array)$header['bcc']; 
		foreach($cc as $value){
			$mailbox=((array)$value);
			$host=((array)$value);
			$Cc[]=$mailbox['mailbox'].'@'.$host['host'];
		}
		$data=@(implode(", ",$Cc));
		return $data;
	}


	/**
     * Get Attachment file's type
     *
     * @param Object $message
     * @param string $coding
     * @return String
     */
	function getdecodevalue($message, $coding) {

		switch($coding) {
			case 0:
			case 1:
				$message = imap_8bit($message);
				break;
			case 2:
				$message = imap_binary($message);
				break;
			case 3:
			case 5:
				$message=imap_base64($message);
				break;
			case 4:
				$message = imap_qprint($message);
				break;
		}
		return $message;
	}
}
?>