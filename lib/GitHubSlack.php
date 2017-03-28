<?php
include 'api.php';

use Milo\Github;

class gitHubSlack {
	public $filename = 'tmp.txt';
	public $channel = 'slack-channel-here'; 
	public $username = 'slack-username';
	public $icon = 'picture-url';
	
	protected $github_token = '781e84d2f179cdb14b00da29656b0dcd56c829a1';
	protected $webhook_url	= 'https://hooks.slack.com/services/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';
	
	private function saveRepoId($data) {
		$handle = fopen($this->filename, 'w');
		fwrite($handle, serialize($data)); 
		fclose($handle); 
	}
	
	private function getPreviousRepoId() {
		$string_data = file_get_contents($this->filename);
		return unserialize($string_data);
	}
	
	private function postToSlack($full_name, $repo_url, $message = NULL, $attachments = []) {		
		$data 	= "payload=" . json_encode(array(         
								    'channel'       =>  "#".$this->channel,
								    'text'          =>  $message,
								    'icon_url'    	=>  $this->icon,
								    'username'		=>  $this->username,
								    'attachments'	=>  [$attachments]
								));         
		 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->$webhook_url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($ch);
		echo var_dump($result);
		
		if($result === false) {
			echo 'Curl error: ' . curl_error($ch);
		}
		 
		curl_close($ch);
	}
	
	public function searchRepo($keyword = 'php') {
		$query 		= $keyword.'+pushed:<='.date("Y-m-d");
		$no_item 	= 5;
		$sort_by 	= 'updated';
		$order 		= 'desc';
		
		$data = [     
				    'q'			=>  $query,
				    'per_page'  =>  $no_item,
				    'sort'    	=> 	$sort_by,
				    'order'		=> 	$order
				];	
		
		$api = new Github\Api;
		$token = new Milo\Github\OAuth\Token($this->github_token);
		$api->setToken($token);
		
		$repositorySearchResponse = $api->get('/search/repositories', $data);
		$repositorySearchData = $api->decode($repositorySearchResponse);
		
		$this->postToSlack($r->full_name, $r->html_url, "What's new on GitHub repo");
		
		foreach($repositorySearchData->items as $r) {
			$fullRepoResponse = $api->get('/repos/:owner/:repo', ['owner' => $r->owner->login, 'repo' => $r->name]);
			$fullRepoData = $api->decode($fullRepoResponse);
			
			$date = new DateTime($r->pushed_at);
			$date->setTimeZone(new DateTimeZone('Asia/Kuala_Lumpur'));
			$update_at = $date->format('r');
			
			$owner = $r->owner;
			
			$fields = [
				[
				'title' => 'Description',
				'value' => $r->description
				]
			];
			
			$attachments = array(
				'fallback' 		=> 'Opps!',
				'color' 		=> '#EFD01B', 
				'author_name' 	=> $owner->login,
	            'author_link' 	=> $owner->url,
	            'author_icon' 	=> $owner->avatar_url,
	            'title' 		=> $r->name,
	            'title_link'	=> $r->html_url,
				'fields'   		=> $fields,
				'footer' 		=> "Last updated at $update_at",
	            'footer_icon'	=> 'https://image.flaticon.com/icons/png/128/59/59252.png'
			);
			
			// Validate duplication
			if(!in_array($r->id, $this->getPreviousRepoId())) {
				$this->postToSlack($r->full_name, $r->html_url, '', $attachments);
			}
			
			$temp_id[] = $r->id;
		}
		
		// Update repo's id
		$this->saveRepoId($temp_id);
	}

}