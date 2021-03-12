<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-plugin/xml-sitemap/qa-xml-sitemap.php
	Description: Page module class for XML sitemap plugin


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

class qa_xml_sitemap_dynamic
{
	public function option_default($option)
	{
		switch ($option) {
			case 'is_generator_by_random':
				return true;
			case 'max_question_url_num_in_xml':	
			    return 38000;
			case 'max_user_url_num_in_xml':	
			    return 10000;
			case 'max_tag_url_num_in_xml':	
			    return 500;
			case 'max_category_url_num_in_xml':	
			    return 500;
		}
	}


	public function admin_form()
	{
		require_once QA_INCLUDE_DIR . 'util/sort.php';

		$saved = false;

		if (qa_clicked('xml_sitemap_save_button')) {
			qa_opt('max_question_url_num_in_xml',(int)qa_post_text('max_question_url_num_in_xml'));
			qa_opt('is_generator_by_random', (int)qa_post_text('is_generator_by_random'));
			
			if (!QA_FINAL_EXTERNAL_USERS){
				qa_opt('max_user_url_num_in_xml',(int)qa_post_text('max_user_url_num_in_xml'));
			}

			if (qa_using_tags())
				qa_opt('max_tag_url_num_in_xml',(int)qa_post_text('max_tag_url_num_in_xml'));

			if (qa_using_categories()) {
				qa_opt('max_category_url_num_in_xml',(int)qa_post_text('max_category_url_num_in_xml'));
			}
			$saved = true;
		}elseif (qa_clicked('donate_zhao_guangyue')) {
				qa_redirect_raw('https://paypal.me/guangyuezhao');
		}

		$form = array(
			'ok' => $saved ? 'XML sitemap settings saved' : null,

			'fields' => array(
				'is_generator_by_random'=>array(
					'label' => 'Is generator by random',
					'type' => 'checkbox',
					'value' => (int)qa_opt('is_generator_by_random'),
					'tags' => 'name="is_generator_by_random"',
			    ),
			    'max_question_url_num_in_xml'=>array(
					'label' => 'Max question url number in one site map xml file(all option total count should lower than 50000):',
					'tags' => 'NAME="max_question_url_num_in_xml"',
					'value' => (int)qa_opt('max_question_url_num_in_xml'),
					'type' => 'number',
				)
			),
	
			'buttons' => array(
				array(
					'label' => 'Save Changes',
					'tags' => 'name="xml_sitemap_save_button"',
				),
				array(
						'label' => 'Donate',
						'tags' => 'NAME="donate_zhao_guangyue"',
				)
			),
		);

		if (!QA_FINAL_EXTERNAL_USERS) {
			$form['fields']['users'] = array(
				'label' => 'Max user url number in one site map xml file(all option total count should lower than 50000):',
				'tags' => 'NAME="max_user_url_num_in_xml"',
				'value' => (int)qa_opt('max_user_url_num_in_xml'),
				'type' => 'number',
			);
		}

		if (qa_using_tags()) {
			$form['fields']['tagqs'] = array(
				'label' => 'Max tag url number in one site map xml file(all option total count should lower than 50000):',
				'tags' => 'NAME="max_tag_url_num_in_xml"',
				'value' => (int)qa_opt('max_tag_url_num_in_xml'),
				'type' => 'number',
			);
		}

		if (qa_using_categories()) {
			$form['fields']['categoryqs'] = array(
				'label' => 'Max category url number in one site map xml file(all option total count should lower than 50000):',
				'tags' => 'NAME="max_category_url_num_in_xml"',
				'value' => (int)qa_opt('max_category_url_num_in_xml'),
				'type' => 'number',
			);
		}
		return $form;
	}


	public function suggest_requests()
	{
		return array(
			array(
				'title' => 'XML Sitemap Dynamic',
				'request' => 'sitemap_dynamic.xml',
				'nav' => null, // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
			),
		);
	}


	public function match_request($request)
	{   
		return ($request == 'sitemap_dynamic.xml');
	}


	public function process_request($request)
	{
		//echo "process_request";
		@ini_set('display_errors', 0); // we don't want to show PHP errors inside XML
		$this->write_xml_header();
		$this->write_question_urls();
		$this->write_category_urls();
		$this->write_user_urls();
		$this->write_tags_urls();
		$this->write_xml_end();
	}
	
	private function write_question_urls(){
		$fectch_num_one_time = 50;
		$max_url_num = qa_opt('max_question_url_num_in_xml');
		$order_by = qa_opt('is_generator_by_random')?" rand() ":" postid ";
		$sql = "SELECT postid FROM ^posts WHERE type='Q' ORDER BY ". $order_by. " LIMIT ".$fectch_num_one_time;
		$this->write_url_to_xml($sql,$max_url_num,$fectch_num_one_time,'question','postid');
	}
	
	private function write_user_urls(){
		$fectch_num_one_time = 30;
		$max_url_num = qa_opt('max_user_url_num_in_xml');
		$order_by = qa_opt('is_generator_by_random')? " rand() " : " userid ";
		$sql = "SELECT userid, handle FROM ^users ORDER BY ". $order_by. " LIMIT ".$fectch_num_one_time;
		$this->write_url_to_xml($sql,$max_url_num,$fectch_num_one_time,'user','handle');
	}
	
	private function write_tags_urls(){
		$fectch_num_one_time = 10;
		$max_url_num = qa_opt('max_tag_url_num_in_xml');
		$order_by = qa_opt('is_generator_by_random')? " rand() " : " wordid ";
		$sql = "SELECT wordid, word ,tagcount FROM ^words WHERE tagcount>0 ORDER BY ". $order_by. " LIMIT ".$fectch_num_one_time;
		$this->write_url_to_xml($sql,$max_url_num,$fectch_num_one_time,'tag','word');
	}
	
	private function write_category_urls(){
		$fectch_num_one_time = 10;
		$max_url_num = qa_opt('max_tag_url_num_in_xml');
		$order_by = qa_opt('is_generator_by_random')? " rand() " : " categoryid ";	
		$sql = "SELECT categoryid, title FROM ^categories ORDER BY ". $order_by. " LIMIT ".$fectch_num_one_time;
		$this->write_url_to_xml($sql,$max_url_num,$fectch_num_one_time,'questions','title');
		
	}
	
	private function write_url_to_xml($sql, $max_num, $fectch_num_one_time,$type,$urlkey){
		$wite_url_num  = 0;
		while (($wite_url_num + $fectch_num_one_time) <= $max_num) {
			$results = qa_db_query_sub($sql);
			foreach ($results as $result) {
				if($type=='question'){
					echo $this->sitemap_output($result[$urlkey],0.5);			
				}else{
					echo $this->sitemap_output($type.'/'.$result[$urlkey],0.5);
				}
				$wite_url_num= $wite_url_num+1;
			}
		} 
	}
	
	private function sitemap_output($request, $priority)
	{
		$host_url=$this->getSiteUrl();
		return "\t<url>\n" .
			"\t\t<loc>" . qa_xml(qa_path($request, null, $host_url)) . "</loc>\n" .
			"\t\t<priority>" . max(0, min(1.0, $priority)) . "</priority>\n" .
			"\t</url>\n";
	}
	
	private function write_xml_header(){
		header('Content-type: text/xml; charset=utf-8');
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
	}
	
	private function write_xml_end(){
		echo "</urlset>\n";
	}
	
	private function getSiteUrl(){
		//echo "uuu";
		return 'http://'.$_SERVER['HTTP_HOST'].'/';
	}

}
